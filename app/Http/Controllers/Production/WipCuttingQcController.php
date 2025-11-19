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

    public function update(Request $request, ProductionBatch $batch, InventoryService $inventory)
    {

        $qtyPlanned = (float) $request->input('qty_planned'); // total planned dari view

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

        DB::transaction(function () use ($batch, $data, &$totalOk, &$totalReject, $inventory) {

            // 🔹 Gudang tujuan WIP-SEW (hasil cutting siap jahit)
            $wipSewWarehouse = Warehouse::firstOrCreate(
                ['code' => 'WIP-SEW'],
                ['name' => 'WIP Siap Jahit', 'type' => 'wip']
            );

            // 🔹 Gudang asal = gudang cutting (vendor / internal)
            $fromWarehouseId = $batch->to_warehouse_id;

            // 🔹 Tanggal transaksi (boleh pakai date_received kalau ada)
            $txnDate = $batch->date_received ?? now()->toDateString();

            // Akumulasi qty OK:
            // 1) per LOT → untuk mengurangi stok kain
            // 2) per ITEM → untuk menambah stok WIP siap jahit (K7BLK, K5BLK, dst)
            $lotSummary = []; // [lot_id => ['qty_ok' => float, 'unit' => string]]
            $wipByItem = []; // [item_code => ['item_id' => int, 'qty' => float, 'unit' => string]]

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
                    $unit = $bundle->unit ?? 'pcs';

                    // 1) Akumulasi per LOT (untuk pemakaian kain)
                    if (!isset($lotSummary[$lotId])) {
                        $lotSummary[$lotId] = [
                            'qty_ok' => 0,
                            'unit' => $unit,
                        ];
                    }
                    $lotSummary[$lotId]['qty_ok'] += $qtyOk;

                    // 2) Akumulasi per ITEM (untuk stok WIP-SEW)
                    $itemCode = $bundle->item_code; // K7BLK / K5BLK / K3BLK
                    $itemId = $bundle->item_id;

                    if ($itemCode && $itemId) {
                        if (!isset($wipByItem[$itemCode])) {
                            $wipByItem[$itemCode] = [
                                'item_id' => $itemId,
                                'qty' => 0,
                                'unit' => $unit,
                            ];
                        }
                        $wipByItem[$itemCode]['qty'] += $qtyOk;
                    }
                }
            }
            // 🔥 STEP 2A: Kurangi stok kain per LOT di gudang cutting
            if ($fromWarehouseId) {
                foreach ($lotSummary as $lotId => $info) {
                    $inventory->mutate(
                        $fromWarehouseId,
                        $lotId,
                        'CUTTING_USE', // tipe mutasi pemakaian kain
                        0, // qty_in
                        $info['qty_ok'], // qty_out
                        $info['unit'],
                        $batch->code,
                        'Pemakaian kain hasil QC Cutting',
                        $txnDate,
                        'raw'
                    );
                }
            }

            // 🔥 STEP 2B: Tambah stok WIP siap jahit di WIP-SEW per item (K7BLK, K5BLK, ...)
            foreach ($wipByItem as $itemCode => $info) {
                $inventory->mutateItem(
                    $wipSewWarehouse->id,
                    $info['item_id'],
                    $itemCode,
                    'WIP_CUT_IN', // tipe mutasi masuk WIP dari cutting
                    $info['qty'], // qty_in
                    0, // qty_out
                    $info['unit'],
                    $batch->code,
                    'Hasil QC Cutting OK → WIP-SEW (per item)',
                    $txnDate,
                    'wip'
                );
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
