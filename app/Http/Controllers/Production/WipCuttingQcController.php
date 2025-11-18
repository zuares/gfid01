<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingBundle;
use App\Models\ProductionBatch;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Proses QC: mark tiap iket OK/Reject, buat/ubah WIP.
 */

class WipCuttingQcController extends Controller
{
    /**
     * Daftar batch cutting yang menunggu QC.
     */
    public function index()
    {
        $batches = ProductionBatch::withCount(['bundles'])
            ->where('stage', 'cutting')
            ->where('status', 'waiting_qc')
            ->orderByDesc('date_received')
            ->paginate(20);

        return view('production.wip_cutting_qc.index', compact('batches'));
    }

    public function show(ProductionBatch $batch)
    {
        // Pastikan ini batch cutting
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        // Kalau mau batasi hanya yang sudah di-QC:
        // if (! in_array($batch->status, ['waiting_qc', 'qc_done'])) {
        //     abort(403, 'Batch ini belum dikirim ke QC.');
        // }

        $batch->load([
            'materials.lot',
            'materials.item',
            'bundles.lot',
            'bundles.item',
            'fromWarehouse',
            'toWarehouse',
            'externalTransfer',
        ]);

        return view('production.wip_cutting_qc.show', compact('batch'));
    }

    /**
     * Form QC untuk satu batch.
     */
    public function edit(ProductionBatch $batch)
    {
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        if ($batch->status !== 'waiting_qc' && $batch->status !== 'qc_in_progress') {
            abort(403, 'Batch ini tidak dalam status waiting_qc.');
        }

        $batch->load([
            'materials.lot',
            'materials.item',
            'bundles.lot',
            'bundles.item',
        ]);

        return view('production.wip_cutting_qc.edit', compact('batch'));
    }

    public function update(Request $request, ProductionBatch $batch)
    {
        // Pastikan ini batch cutting & statusnya masih boleh di-QC
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        if (!in_array($batch->status, ['waiting_qc', 'qc_in_progress'], true)) {
            abort(403, 'Batch ini tidak dalam status waiting_qc / qc_in_progress.');
        }

        // Eager load bundles di batch ini
        $batch->load('bundles');

        $data = $request->validate([
            'bundles' => ['required', 'array'],
            'bundles.*.id' => ['required', 'integer'],
            'bundles.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'bundles.*.qc_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $totalOk = 0;
        $totalReject = 0;

        // 🔎 STEP 0: Validasi awal — reject tidak boleh > qty_cut
        foreach ($data['bundles'] as $row) {
            /** @var \App\Models\CuttingBundle|null $bundle */
            $bundle = $batch->bundles->firstWhere('id', $row['id']);

            if (!$bundle) {
                // kalau id bundle aneh, skip saja (atau bisa juga dijadikan error)
                continue;
            }

            $qtyCut = (float) $bundle->qty_cut;
            $qtyReject = (float) $row['qty_reject'];

            if ($qtyReject > $qtyCut) {
                return back()
                    ->withErrors("Total reject untuk bundle {$bundle->bundle_code} melebihi qty cut ({$qtyCut}).")
                    ->withInput();
            }
        }

        DB::transaction(function () use ($batch, $data, &$totalOk, &$totalReject) {

            // 🔹 Gudang tujuan WIP-SEW (hasil cutting siap jahit)
            $wipSewWarehouse = Warehouse::firstOrCreate(
                ['code' => 'WIP-SEW'],
                ['name' => 'WIP Siap Jahit', 'type' => 'wip']
            );

            // 🔹 Gudang asal = gudang cutting (vendor / internal)
            $fromWarehouseId = $batch->to_warehouse_id;

            // Akumulasi qty OK per LOT (agar 1 LOT cukup 1x transfer)
            // format: [lot_id => ['qty_ok' => float, 'unit' => string]]
            $lotSummary = [];

            foreach ($data['bundles'] as $row) {
                /** @var \App\Models\CuttingBundle|null $bundle */
                $bundle = $batch->bundles->firstWhere('id', $row['id']);

                if (!$bundle) {
                    continue;
                }

                $qtyCut = (float) $bundle->qty_cut;
                $qtyReject = (float) $row['qty_reject'];
                $qtyOk = max($qtyCut - $qtyReject, 0);

                // 🔥 UPDATE cutting_bundles
                $bundle->update([
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'status' => 'qc_done',
                    'notes' => $row['qc_notes'] ?? $bundle->notes,
                ]);

                $totalOk += $qtyOk;
                $totalReject += $qtyReject;

                if ($qtyOk > 0) {
                    $lotId = $bundle->lot_id;
                    $unit = $bundle->unit ?? 'pcs'; // fallback pcs kalau tidak ada

                    if (!isset($lotSummary[$lotId])) {
                        $lotSummary[$lotId] = [
                            'qty_ok' => 0,
                            'unit' => $unit,
                        ];
                    }

                    $lotSummary[$lotId]['qty_ok'] += $qtyOk;
                }
            }

            // 🔥 STEP 2: Pindahkan total OK per LOT dari gudang cutting → WIP-SEW
            foreach ($lotSummary as $lotId => $info) {
                InventoryService::transferCuttingToSew([
                    'from_warehouse_id' => $fromWarehouseId, // gudang cutting (vendor/internal)
                    'to_warehouse_id' => $wipSewWarehouse->id, // WIP-SEW
                    'lot_id' => $lotId,
                    'qty' => $info['qty_ok'],
                    'unit' => $info['unit'],
                    'ref_code' => $batch->code,
                    'note' => 'Hasil QC Cutting OK → WIP-SEW',
                    'date' => now()->toDateString(),
                    'category' => 'wip',
                ]);
            }

            // 🔷 Ringkasan di header batch
            $batch->update([
                'status' => 'qc_done',
                'total_output_qty' => $totalOk,
                'total_reject_qty' => $totalReject,
            ]);
        });

        return redirect()
            ->route('production.wip_cutting_qc.show', $batch->id)
            ->with('success', "Hasil QC disimpan. OK: {$totalOk} pcs, Reject: {$totalReject} pcs.");
    }

}
