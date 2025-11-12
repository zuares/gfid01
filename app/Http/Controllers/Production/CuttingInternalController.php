<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuttingInternalController extends Controller
{
    public function create()
    {
        // Ambil LOT bahan yang tersedia (join items biar user lihat code & name)
        $lots = DB::table('lots')
            ->join('items', 'items.id', '=', 'lots.item_id')
            ->select(
                'lots.id',
                'lots.code as lot_code',
                'lots.initial_qty',
                'lots.unit as uom',
                'items.id as item_id',
                'items.code as item_code',
                'items.name as item_name'
            )
            ->where('lots.initial_qty', '>', 0)
            ->orderByDesc('lots.updated_at')
            ->limit(300)
            ->get();

        // Gudang (asal) & gudang hasil (tujuan)
        $warehouses = DB::table('warehouses')->select('id', 'name', 'code')->orderBy('name')->get();

        // Pastikan ada WH-WIP-COMP (untuk hasil pcs komponen)
        $whComp = DB::table('warehouses')->where('code', 'WIP-COMP')->first();
        if (!$whComp) {
            $whCompId = DB::table('warehouses')->insertGetId([
                'name' => 'WIP Komponen (pcs)',
                'code' => 'WIP-COMP',
                'is_external' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $whComp = DB::table('warehouses')->find($whCompId);
        }

        // Operator internal (opsional)
        $employees = DB::table('employees')
            ->select('code', 'name')
            ->where('active', 1)
            ->orderBy('code')
            ->get();

        // Riwayat terakhir untuk preview
        $last = DB::table('cutting_internal_jobs')->orderByDesc('id')->limit(10)->get();

        return view('production.cutting.internal.create', compact('lots', 'warehouses', 'employees', 'whComp', 'last'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'date' => ['required', 'date'],
            'operator_code' => ['nullable', 'string', 'max:20'],
            'warehouse_from_id' => ['required', 'integer', 'exists:warehouses,id'],
            'warehouse_to_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:255'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_in' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.uom_in' => ['required', 'string', 'max:16'],
            'lines.*.pcs_output' => ['required', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $today = date('Y-m-d', strtotime($data['date']));

        // Generate kode job
        $seq = (DB::table('cutting_internal_jobs')->whereDate('date', $today)->count()) + 1;
        $jobCode = sprintf('CUT-INT-%s-%03d', date('Ymd', strtotime($today)), $seq);

        DB::transaction(function () use ($data, $today, $jobCode) {
            // Insert header
            $jobId = DB::table('cutting_internal_jobs')->insertGetId([
                'code' => $jobCode,
                'date' => $today,
                'operator_code' => $data['operator_code'] ?? null,
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'status' => 'posted',
                'note' => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Loop lines
            foreach ($data['lines'] as $i => $ln) {
                // Buat LOT output komponen pcs
                $itemCode = DB::table('items')->where('id', $ln['item_id'])->value('code') ?? 'ITEM';
                $lotOut = sprintf('LOT-%sPCS-%s-%03d', strtoupper($itemCode), date('Ymd', strtotime($today)), $i + 1);

                DB::table('cutting_internal_lines')->insert([
                    'cutting_internal_job_id' => $jobId,
                    'item_id' => $ln['item_id'],
                    'lot_id' => $ln['lot_id'],
                    'qty_in' => $ln['qty_in'],
                    'uom_in' => $ln['uom_in'],
                    'pcs_output' => $ln['pcs_output'],
                    'lot_output_code' => $lotOut,
                    'note' => $ln['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // === MUTASI STOK ===
                // TODO: Panggil InventoryService kamu agar konsisten dengan modul lain
                // Contoh pseudo:
                // app(\App\Services\InventoryService::class)->mutate(
                //     warehouseId: $data['warehouse_from_id'],
                //     lotId: $ln['lot_id'],
                //     type: 'CUTTING_USE',
                //     qtyIn: 0,
                //     qtyOut: (float)$ln['qty_in'],
                //     unit: $ln['uom_in'],
                //     refCode: $jobCode,
                //     note: 'Cutting internal: bahan → pcs komponen'
                // );
                //
                // // Buat baris LOT output jika tabel lots kamu perlu diisi untuk komponen pcs
                $newLotId = DB::table('lots')->insertGetId([
                    'code' => $lotOut,
                    'item_id' => $ln['item_id'],
                    'initial_qty' => $ln['pcs_output'],
                    'unit' => 'pcs',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'date' => now(),
                ]);

                // app(\App\Services\InventoryService::class)->mutate(
                //     warehouseId: $data['warehouse_to_id'], // WH-WIP-COMP
                //     lotId: $newLotId,
                //     type: 'PRODUCTION_IN',
                //     qtyIn: (float)$ln['pcs_output'],
                //     qtyOut: 0,
                //     unit: 'pcs',
                //     refCode: $jobCode,
                //     note: 'Cutting internal output pcs'
                // );

                // === JURNAL (opsional, bisa dilakukan saat closing batch) ===
                // app(\App\Services\JournalService::class)->postInternalCuttingSummary(
                //     refCode: $jobCode,
                //     date: $today,
                //     amount: /* total biaya bahan yang terpakai jika kamu hitung di sini */
                // );
            }
        });

        return redirect()->route('production.cutting.internal.create')
            ->with('ok', "Cutting internal diposting: {$jobCode}");
    }
}
