<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExternalTransferController extends Controller
{
    // Form kirim kain ke tukang cutting (auto-batch per operator)
    public function create()
    {
        // LOT bahan utama (aktif)
        $lots = DB::table('lots')
            ->join('items', 'items.id', '=', 'lots.item_id')
            ->select(
                'lots.id',
                'lots.code as lot_code',
                'items.id as item_id',
                'items.code as item_code',
                'items.name as item_name',
                'lots.initial_qty',
                'lots.unit as uom'
            )
            ->where('lots.initial_qty', '>', 0)
            ->orderByDesc('lots.updated_at')
            ->limit(300)
            ->get();

        // Operator cutting (karyawan role=cutting)
        $employees = DB::table('employees')
            ->select('code', 'name')
            ->where('active', 1)
            ->where('role', 'cutting')
            ->orderBy('code')
            ->get();

        // Gudang bahan default
        $warehouses = DB::table('warehouses')
            ->select('id', 'name', 'code')
            ->orderBy('name')->get();

        return view('production.external.send_create', compact('lots', 'employees', 'warehouses'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'date' => ['required', 'date'],
            'operator_code' => ['required', 'string', 'max:20'],
            'warehouse_from_id' => ['required', 'integer', 'exists:warehouses,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.uom' => ['required', 'string', 'max:16'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $op = $data['operator_code'];
        $today = date('Y-m-d', strtotime($data['date']));

        return DB::transaction(function () use ($data, $op, $today) {
            // 1) Pastikan warehouse eksternal untuk operator ada
            $extCode = 'EXT-' . strtoupper($op);
            $whTo = DB::table('warehouses')->where('code', $extCode)->first();
            if (!$whTo) {
                $whToId = DB::table('warehouses')->insertGetId([
                    'name' => 'Lokasi Eksternal ' . $op,
                    'code' => $extCode,
                    'is_external' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $whToId = $whTo->id;
            }

            // 2) Ambil / buat batch per operator untuk hari ini
            $batch = DB::table('production_batches')
                ->where('operator_code', $op)
                ->where('date', $today)
                ->whereIn('status', ['draft', 'in_progress'])
                ->first();

            if (!$batch) {
                $seq = (DB::table('production_batches')
                        ->whereDate('date', $today)
                        ->count()) + 1;

                $batchCode = sprintf('PROD-%s-%s-%03d', date('Ymd', strtotime($today)), strtoupper($op), $seq);

                DB::table('production_batches')->insert([
                    'code' => $batchCode,
                    'date' => $today,
                    'operator_code' => $op,
                    'status' => 'in_progress',
                    'notes' => $data['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $batchCode = $batch->code;
            }

            // 3) Buat dokumen transfer eksternal
            $seq2 = (DB::table('external_transfers')->whereDate('date', $today)->count()) + 1;
            $extCodeDoc = sprintf('CUT-EXT-%s-%03d', date('Ymd', strtotime($today)), $seq2);

            $extId = DB::table('external_transfers')->insertGetId([
                'code' => $extCodeDoc,
                'batch_code' => $batchCode,
                'date' => $today,
                'operator_code' => $op,
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $whToId,
                'status' => 'sent',
                'note' => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['lines'] as $ln) {
                DB::table('external_transfer_lines')->insert([
                    'external_transfer_id' => $extId,
                    'lot_id' => $ln['lot_id'],
                    'item_id' => $ln['item_id'],
                    'qty' => $ln['qty'],
                    'uom' => $ln['uom'],
                    'note' => $ln['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Mutasi stok: TRANSFER_OUT (WH-BAHAN → EXT-OPR)
                // Gunakan InventoryService kamu kalau tersedia; sementara contoh langsung insert:
                // Di sistemmu sudah ada InventoryService::mutate(...)? Panggil itu di sini.
                // Contoh pseudo (aktifkan sesuai servicemu):
                // app(InventoryService::class)->mutate(
                //     warehouseId: $data['warehouse_from_id'],
                //     lotId: $ln['lot_id'],
                //     type: 'TRANSFER_OUT',
                //     qtyIn: 0,
                //     qtyOut: (float)$ln['qty'],
                //     unit: $ln['uom'],
                //     refCode: $extCodeDoc,
                //     note: 'Kirim ke ' . $extCode
                // );
                // app(InventoryService::class)->mutate(
                //     warehouseId: $whToId,
                //     lotId: $ln['lot_id'],
                //     type: 'TRANSFER_IN',
                //     qtyIn: (float)$ln['qty'],
                //     qtyOut: 0,
                //     unit: $ln['uom'],
                //     refCode: $extCodeDoc,
                //     note: 'Terima dari WH-BAHAN'
                // );
            }

            return redirect()
                ->route('production.cutting.receive.create')
                ->with('ok', "Terkirim: {$extCodeDoc}. Batch: {$batchCode}. Lanjut terima hasil cutting.");
        });
    }
}
