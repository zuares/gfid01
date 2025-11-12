<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuttingReceiptController extends Controller
{
    public function create()
    {
        // Ambil batch in_progress per operator (hari ini)
        $batches = DB::table('production_batches')
            ->whereIn('status', ['in_progress', 'cutting_done'])
            ->orderByDesc('date')
            ->limit(50)->get();

        // Ambil operator list
        $employees = DB::table('employees')
            ->select('code', 'name')
            ->where('active', 1)->where('role', 'cutting')
            ->orderBy('code')->get();

        // Item hasil (SKU) — filter item tipe produk (misal category 'SKU')
        $items = DB::table('items')->select('id', 'code', 'name')->orderBy('code')->get();

        // Transfer eksternal terakhir (opsional)
        $exts = DB::table('external_transfers')->orderByDesc('date')->limit(50)->get();

        return view('production.cutting.receive_create', compact('batches', 'employees', 'items', 'exts'));
    }

    // Simpan DRAFT
    public function storeDraft(Request $r)
    {
        $data = $r->validate([
            'date' => ['required', 'date'],
            'batch_code' => ['required', 'string'],
            'operator_code' => ['required', 'string'],
            'external_transfer_id' => ['nullable', 'integer', 'exists:external_transfers,id'],
            'note' => ['nullable', 'string', 'max:255'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.bundle_count' => ['nullable', 'integer', 'min:0'],
            'lines.*.pcs_per_bundle' => ['nullable', 'integer', 'min:0'],
            'lines.*.good_qty' => ['required', 'integer', 'min:0'],
            'lines.*.defect_qty' => ['required', 'integer', 'min:0'],
        ]);

        $today = date('Y-m-d', strtotime($data['date']));

        $rcCode = sprintf('RCUT-%s-%03d', date('Ymd', strtotime($today)),
            (DB::table('cutting_receipts')->whereDate('date', $today)->count()) + 1
        );

        return DB::transaction(function () use ($data, $rcCode, $today) {
            $rcId = DB::table('cutting_receipts')->insertGetId([
                'code' => $rcCode,
                'batch_code' => $data['batch_code'],
                'date' => $today,
                'operator_code' => $data['operator_code'],
                'external_transfer_id' => $data['external_transfer_id'] ?? null,
                'status' => 'draft',
                'note' => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // buat lot sementara (akan diisi saat POST)
            foreach ($data['lines'] as $ln) {
                DB::table('cutting_receipt_lines')->insert([
                    'cutting_receipt_id' => $rcId,
                    'item_id' => $ln['item_id'],
                    'bundle_count' => $ln['bundle_count'] ?? 0,
                    'pcs_per_bundle' => $ln['pcs_per_bundle'] ?? 20,
                    'good_qty' => $ln['good_qty'],
                    'defect_qty' => $ln['defect_qty'],
                    'note' => $ln['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->route('production.cutting.receive.create')
                ->with('ok', "Draft penerimaan dibuat: {$rcCode}. Silakan POST setelah dicek.");
        });
    }

    // POST = konfirmasi → mutasi stok + jurnal + generate LOT final
    public function post($id, Request $r)
    {
        // Ambil header + lines
        $rc = DB::table('cutting_receipts')->where('id', $id)->first();
        if (!$rc || $rc->status !== 'draft') {
            return back()->with('err', 'Dokumen tidak ditemukan / bukan draft.');
        }
        $lines = DB::table('cutting_receipt_lines')->where('cutting_receipt_id', $id)->get();
        $batch = DB::table('production_batches')->where('code', $rc->batch_code)->first();

        // Lokasi eksternal operator & WH-WIP-CUT
        $extWh = DB::table('warehouses')->where('code', 'EXT-' . strtoupper($rc->operator_code))->first();
        $wipCut = DB::table('warehouses')->where('code', 'WIP-CUT')->first();
        if (!$wipCut) {
            $wipCutId = DB::table('warehouses')->insertGetId([
                'name' => 'WIP Cutting',
                'code' => 'WIP-CUT',
                'is_external' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } else { $wipCutId = $wipCut->id;}

        $today = $rc->date;

        DB::transaction(function () use ($rc, $lines, $batch, $extWh, $wipCutId, $today) {
            foreach ($lines as $i => $ln) {
                // Generate LOT untuk good & defect bila qty > 0
                if ($ln->good_qty > 0) {
                    $lotGood = sprintf('LOT-%s-CUT-%s-%s-%03d',
                        self::itemCode($ln->item_id), date('Ymd', strtotime($today)),
                        strtoupper($rc->operator_code), $i + 1
                    );
                    DB::table('cutting_receipt_lines')->where('id', $ln->id)->update(['good_lot_code' => $lotGood]);

                    // TODO: create lots row for good_lot if needed
                    // Mutasi: TRANSFER_IN dari eksternal → WIP-CUT
                    // Mutasi: PRODUCTION_IN (optional) ke WIP-CUT (jika kamu ingin pisahkan dari transfer)
                    // Panggil InventoryService sesuai sistemmu:
                    // app(InventoryService::class)->mutate($wipCutId, $lotIdGood, 'PRODUCTION_IN', $ln->good_qty, 0, 'pcs', $rc->code, 'Cutting good');
                }

                if ($ln->defect_qty > 0) {
                    $lotDef = sprintf('LOT-%s-DEF-%s-%s-%03d',
                        self::itemCode($ln->item_id), date('Ymd', strtotime($today)),
                        strtoupper($rc->operator_code), $i + 1
                    );
                    DB::table('cutting_receipt_lines')->where('id', $ln->id)->update(['defect_lot_code' => $lotDef]);

                    // TODO: create lots row for defect_lot if reworkable else adjustment scrap
                    // app(InventoryService::class)->mutate($wipCutId, $lotIdDef, 'PRODUCTION_IN', $ln->defect_qty, 0, 'pcs', $rc->code, 'Cutting defect');
                }
            }

            // Update status dokumen
            DB::table('cutting_receipts')->where('id', $rc->id)->update([
                'status' => 'posted',
                'updated_at' => now(),
            ]);

            // Opsional: update status batch → cutting_done bila sudah cukup
            DB::table('production_batches')->where('code', $rc->batch_code)->update([
                'status' => 'cutting_done',
                'updated_at' => now(),
            ]);

            // TODO Jurnal (ringkas): Dr WIP–Cutting / Cr Persediaan Bahan
            // app(JournalService::class)->postProductionCuttingSummary($rc->code, $today, $amount);
        });

        return back()->with('ok', "Penerimaan {$rc->code} sudah di-POST.");
    }

    private static function itemCode($itemId)
    {
        $it = DB::table('items')->select('code')->where('id', $itemId)->first();
        return $it ? $it->code : 'SKU';
    }
}
