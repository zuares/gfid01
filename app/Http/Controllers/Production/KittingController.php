<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KittingController extends Controller
{
    public function create()
    {
        // Batch yang siap dikitting (sudah ada cutting), tapi fleksibel juga boleh in_progress
        $batches = DB::table('production_batches')
            ->whereIn('status', ['in_progress', 'cutting_done'])
            ->orderByDesc('date')->limit(50)->get();

        // SKU yang akan jadi BSJ-SET (pakai items yang kamu anggap produk)
        $items = DB::table('items')->select('id', 'code', 'name')->orderBy('code')->get();

        // Lots komponen pcs (kain potong / rib / karet / tali) → asumsikan unit pcs
        $lots_pcs = DB::table('lots')
            ->join('items', 'items.id', '=', 'lots.item_id')
            ->select('lots.id', 'lots.code as lot_code', 'lots.initial_qty', 'lots.unit', 'items.code as item_code', 'items.name as item_name')
            ->where('lots.unit', 'pcs')
            ->orderByDesc('lots.updated_at')
            ->limit(500)->get();

        // Pastikan gudang output WIP-SEW tersedia
        $wipSew = DB::table('warehouses')->where('code', 'WIP-SEW')->first();
        if (!$wipSew) {
            $wipSewId = DB::table('warehouses')->insertGetId([
                'name' => 'WIP Sewing (BSJ Set)',
                'code' => 'WIP-SEW',
                'is_external' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $wipSew = DB::table('warehouses')->find($wipSewId);
        }

        // Lokasi komponen default untuk mutasi keluar
        $wipCut = DB::table('warehouses')->where('code', 'WIP-CUT')->first(); // kain potong pcs
        $wipComp = DB::table('warehouses')->where('code', 'WIP-COMP')->first(); // rib/karet/tali pcs

        // Operator internal (opsional)
        $employees = DB::table('employees')->select('code', 'name')->where('active', 1)->orderBy('code')->get();

        // Riwayat kitting
        $history = DB::table('kitting_jobs')->orderByDesc('id')->limit(10)->get();

        return view('production.kitting.create', compact('batches', 'items', 'lots_pcs', 'wipSew', 'wipCut', 'wipComp', 'employees', 'history'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'date' => ['required', 'date'],
            'batch_code' => ['required', 'string'],
            'operator_code' => ['nullable', 'string', 'max:20'],
            'warehouse_output_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:255'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_output_sets' => ['required', 'integer', 'min:1'],

            // komponen (boleh null bila belum lengkap → tapi nanti akan batasi oleh qty min)
            'lines.*.kain_lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'lines.*.kain_qty_used' => ['nullable', 'integer', 'min:0'],

            'lines.*.rib_lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'lines.*.rib_qty_used' => ['nullable', 'integer', 'min:0'],

            'lines.*.karet_lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'lines.*.karet_qty_used' => ['nullable', 'integer', 'min:0'],

            'lines.*.tali_lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'lines.*.tali_qty_used' => ['nullable', 'integer', 'min:0'],
        ]);

        $today = date('Y-m-d', strtotime($data['date']));
        $seq = (DB::table('kitting_jobs')->whereDate('date', $today)->count()) + 1;
        $jobCode = sprintf('KIT-%s-%03d', date('Ymd', strtotime($today)), $seq);

        // Dapatkan operator dari batch untuk penomoran LOT output
        $batch = DB::table('production_batches')->where('code', $data['batch_code'])->first();
        $opr = $batch?->operator_code ?? ($data['operator_code'] ?? 'OPR');

        // Warehouse default sumber komponen:
        $wipCut = DB::table('warehouses')->where('code', 'WIP-CUT')->value('id'); // kain
        $wipComp = DB::table('warehouses')->where('code', 'WIP-COMP')->value('id'); // rib/karet/tali
        $wipCut = $wipCut ?: $data['warehouse_output_id']; // fallback aman
        $wipComp = $wipComp ?: $data['warehouse_output_id']; // fallback aman

        DB::transaction(function () use ($data, $today, $jobCode, $opr, $wipCut, $wipComp) {
            // Header
            $jobId = DB::table('kitting_jobs')->insertGetId([
                'code' => $jobCode,
                'batch_code' => $data['batch_code'],
                'date' => $today,
                'operator_code' => $data['operator_code'] ?? null,
                'warehouse_output_id' => $data['warehouse_output_id'],
                'status' => 'posted',
                'note' => $data['note'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($data['lines'] as $i => $ln) {
                // Output LOT untuk BSJ-SET
                $skuCode = DB::table('items')->where('id', $ln['product_item_id'])->value('code') ?? 'SKU';
                $lotOut = sprintf('LOT-%s-BSJ-%s-%s-%03d', strtoupper($skuCode), date('Ymd', strtotime($today)), strtoupper($opr), $i + 1);

                DB::table('kitting_lines')->insert([
                    'kitting_job_id' => $jobId,
                    'product_item_id' => $ln['product_item_id'],
                    'qty_output_sets' => $ln['qty_output_sets'],
                    'lot_output_code' => $lotOut,

                    'kain_lot_id' => $ln['kain_lot_id'] ?? null,
                    'kain_qty_used' => (int) ($ln['kain_qty_used'] ?? 0),

                    'rib_lot_id' => $ln['rib_lot_id'] ?? null,
                    'rib_qty_used' => (int) ($ln['rib_qty_used'] ?? 0),

                    'karet_lot_id' => $ln['karet_lot_id'] ?? null,
                    'karet_qty_used' => (int) ($ln['karet_qty_used'] ?? 0),

                    'tali_lot_id' => $ln['tali_lot_id'] ?? null,
                    'tali_qty_used' => (int) ($ln['tali_qty_used'] ?? 0),

                    'note' => $ln['note'] ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                // === MUTASI STOK (TODO: sambungkan ke InventoryService milikmu) ===
                // Kain potong pcs → keluar dari WIP-CUT
                if (!empty($ln['kain_lot_id']) && ($ln['kain_qty_used'] ?? 0) > 0) {
                    // app(\App\Services\InventoryService::class)->mutate(
                    //     warehouseId: $wipCut,
                    //     lotId: (int)$ln['kain_lot_id'],
                    //     type: 'PRODUCTION_USE',
                    //     qtyIn: 0,
                    //     qtyOut: (float)$ln['kain_qty_used'],
                    //     unit: 'pcs',
                    //     refCode: $jobCode,
                    //     note: 'Kitting: kain potong → BSJ set'
                    // );
                }

                // Rib/karet/tali pcs → keluar dari WIP-COMP
                foreach (['rib', 'karet', 'tali'] as $comp) {
                    $lotKey = $comp . '_lot_id';
                    $qtyKey = $comp . '_qty_used';
                    if (!empty($ln[$lotKey]) && ($ln[$qtyKey] ?? 0) > 0) {
                        // app(\App\Services\InventoryService::class)->mutate(
                        //     warehouseId: $wipComp,
                        //     lotId: (int)$ln[$lotKey],
                        //     type: 'PRODUCTION_USE',
                        //     qtyIn: 0,
                        //     qtyOut: (float)$ln[$qtyKey],
                        //     unit: 'pcs',
                        //     refCode: $jobCode,
                        //     note: 'Kitting: komponen ' . $comp
                        // );
                    }
                }

                // Buat LOT output BSJ set (jika schemas lots kamu butuh row baru)
                // $newLotId = DB::table('lots')->insertGetId([
                //     'code' => $lotOut,
                //     'item_id' => $ln['product_item_id'],
                //     'initial_qty' => $ln['qty_output_sets'],
                //     'unit' => 'pcs',
                //     'created_at' => now(), 'updated_at' => now(),
                // ]);

                // Output BSJ masuk ke WIP-SEW
                // app(\App\Services\InventoryService::class)->mutate(
                //     warehouseId: $data['warehouse_output_id'],
                //     lotId: $newLotId,
                //     type: 'PRODUCTION_IN',
                //     qtyIn: (float)$ln['qty_output_sets'],
                //     qtyOut: 0,
                //     unit: 'pcs',
                //     refCode: $jobCode,
                //     note: 'Kitting output → BSJ set'
                // );
            }

            // Optional: update status batch
            DB::table('production_batches')->where('code', $data['batch_code'])->update([
                'status' => 'kitting_done',
                'updated_at' => now(),
            ]);

            // === JURNAL (opsional; bisa juga saat closing batch sewing) ===
            // app(\App\Services\JournalService::class)->postKittingSummary($jobCode, $today, $amount);
        });

        return redirect()->route('production.kitting.create')->with('ok', "Kitting diposting: {$jobCode}");
    }
}
