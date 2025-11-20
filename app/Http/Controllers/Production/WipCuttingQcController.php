<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
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

    /**
     * Detail batch (setelah QC / untuk lihat isi).
     */
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
     *
     * Di sini sekalian dikirim:
     *  - totalLotUsed  => berapa LOT unik yang dipakai
     *  - lotsGrouped   => per LOT: kode LOT, total qty, dan item apa saja yang pakai LOT tsb
     */
    public function edit(ProductionBatch $batch)
    {
        // Pastikan ini batch cutting
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        // Hanya boleh QC kalau status waiting_qc / qc_in_progress
        if ($batch->status !== 'waiting_qc' && $batch->status !== 'qc_in_progress') {
            abort(403, 'Batch ini tidak dalam status waiting_qc.');
        }

        $batch->load([
            'materials.lot',
            'materials.item',
            'bundles.lot',
            'bundles.item',
        ]);

        // Hitung LOT yang dipakai + item apa saja yang pakai LOT tsb
        $lotsGrouped = $batch->materials
            ->filter(function ($m) {
                // hanya material yang punya LOT & ITEM
                return $m->lot && $m->item;
            })
            ->groupBy('lot_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'lot_id' => $first->lot_id,
                    'lot_code' => optional($first->lot)->code,

                    // pakai qty_planned kalau ada, fallback ke qty
                    'total_qty_planned' => (float) $rows->sum(function ($r) {
                        return $r->qty_planned ?? $r->qty ?? 0;
                    }),

                    // unit fallback: material.unit → item.unit → 'kg'
                    'unit' => $first->unit ?? optional($first->item)->unit ?? 'kg',

                    // item code unik yang pakai LOT ini
                    'items' => $rows->pluck('item.code')
                        ->filter()
                        ->unique()
                        ->values(),
                ];
            })
            ->values();

        $totalLotUsed = $lotsGrouped->count();

        return view('production.wip_cutting_qc.edit', [
            'batch' => $batch,
            'lotsGrouped' => $lotsGrouped,
            'totalLotUsed' => $totalLotUsed,
        ]);
    }

    /**
     * Simpan hasil QC Cutting + mutasi stok LOT & WIP-SEW.
     */
    public function update(Request $request, ProductionBatch $batch, InventoryService $inventory)
    {
        $qtyPlanned = (float) $request->input('qty_planned', 0);

        // Pastikan ini batch cutting & statusnya masih boleh di-QC
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        if (!in_array($batch->status, ['waiting_qc', 'qc_in_progress'], true)) {
            abort(403, 'Batch ini tidak dalam status waiting_qc / qc_in_progress.');
        }

        // Load relasi yang dibutuhkan
        $batch->load([
            'materials.lot',
            'materials.item',
            'bundles.lot',
            'bundles.item',
        ]);

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
            $bundle = $batch->bundles->firstWhere('id', $row['id']);

            if (!$bundle) {
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

            // 🔹 Gudang asal = gudang cutting
            $fromWarehouseId = $batch->to_warehouse_id;
            $txnDate = $batch->date_received ?? now()->toDateString();

            // 1️⃣ WIP per ITEM (hasil cutting) → dari bundles
            $wipByItem = []; // [item_code => ['item_id' => int, 'qty' => float, 'unit' => string]]

            foreach ($data['bundles'] as $row) {
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

                if ($qtyOk <= 0) {
                    continue;
                }

                // unit fallback: bundle.unit → item.unit → 'pcs'
                $unit = $bundle->unit ?? optional($bundle->item)->unit ?? 'pcs';

                $itemId = $bundle->item_id;
                $itemCode = $bundle->item_code ?? optional($bundle->item)->code;

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

            // 2️⃣ Pemakaian LOT (bahan kain) → dari tabel materials, BUKAN dari bundles
            //    Supaya 1 LOT cuma dikurang sekali sesuai qty_planned (kg/meter),
            //    tidak dobel karena banyak bundle.
            $lotSummary = $batch->materials
                ->filter(function ($m) {
                    return $m->lot; // hanya yang punya LOT
                })
                ->groupBy('lot_id')
                ->map(function ($rows) {
                    $first = $rows->first();

                    return [
                        'lot_id' => $first->lot_id,
                        'lot_code' => optional($first->lot)->code,
                        // total bahan yang dipakai dari LOT ini (kg/meter)
                        'qty_used' => (float) $rows->sum(function ($r) {
                            return $r->qty_planned ?? $r->qty ?? 0;
                        }),
                        // unit bahan: material.unit → item.unit → 'kg'
                        'unit' => $first->unit ?? optional($first->item)->unit ?? 'kg',
                    ];
                })
                ->values()
                ->all(); // jadi array biasa

            // 🔥 STEP 2A: Kurangi stok kain per LOT di gudang cutting (RAW → LOT BAHAN)
            if ($fromWarehouseId && !empty($lotSummary)) {
                foreach ($lotSummary as $row) {
                    if (($row['qty_used'] ?? 0) <= 0) {
                        continue;
                    }

                    // pakai method LOT-based yang sudah ada: mutate()
                    $inventory->mutate(
                        warehouseId: $fromWarehouseId,
                        lotId: $row['lot_id'],
                        type: 'CUTTING_USE', // tipe mutasi pemakaian kain
                        qtyIn: 0.0,
                        qtyOut: $row['qty_used'],
                        unit: $row['unit'],
                        refCode: $batch->code,
                        note: 'Pemakaian kain hasil QC Cutting (berdasarkan qty_planned per LOT)',
                        date: $txnDate,
                        category: 'raw'
                    );
                }
            }

            // 🔥 STEP 2B: Tambah stok WIP siap jahit di WIP-SEW per item (hasil cutting: K7BLK, K5BLK, ...)
            foreach ($wipByItem as $itemCode => $info) {
                if ($info['qty'] <= 0) {
                    continue;
                }

                $inventory->mutateItem(
                    warehouseId: $wipSewWarehouse->id,
                    itemId: $info['item_id'],
                    itemCode: $itemCode,
                    type: 'WIP_CUT_IN', // tipe mutasi masuk WIP dari cutting
                    qtyIn: $info['qty'],
                    qtyOut: 0.0,
                    unit: $info['unit'],
                    refCode: $batch->code,
                    note: 'Hasil QC Cutting OK → WIP-SEW (per item)',
                    date: $txnDate,
                    category: 'wip'
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
