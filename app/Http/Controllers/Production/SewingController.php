<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionBatch;
use App\Models\WipItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SewingController extends Controller
{
    /**
     * Daftar WIP hasil cutting yang siap dijahit.
     */
    public function index()
    {
        $wips = WipItem::with(['item', 'warehouse', 'productionBatch'])
            ->stage('cutting') // hanya WIP stage cutting
            ->available() // qty > 0
            ->orderBy('warehouse_id')
            ->orderBy('item_code')
            ->paginate(50);

        return view('production.sewing.index', compact('wips'));
    }

    /**
     * Form buat 1 batch sewing dari 1 WIP item (hasil cutting).
     */
    public function create($wipItemId)
    {
        $wip = WipItem::with(['item', 'warehouse', 'productionBatch', 'sourceLot'])
            ->stage('cutting')
            ->available()
            ->findOrFail($wipItemId);

        return view('production.sewing.create', [
            'wip' => $wip,
        ]);
    }

    /**
     * Simpan batch sewing:
     * - kurangi qty WIP cutting
     * - buat production_batch (sewing)
     * - buat WIP baru di stage 'sewing' (stok hasil jahit)
     */
    public function store($wipItemId, Request $request)
    {
        $wip = WipItem::with(['item', 'warehouse', 'productionBatch'])
            ->stage('cutting')
            ->available()
            ->findOrFail($wipItemId);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'operator_code' => ['nullable', 'string', 'max:100'],
            'qty_to_sew' => ['required', 'numeric', 'min:1'],
            'output_qty' => ['required', 'numeric', 'min:1'],
            'reject_qty' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['qty_to_sew'] > $wip->qty) {
            throw ValidationException::withMessages([
                'qty_to_sew' => 'Qty yang dijahit tidak boleh melebihi stok WIP ('
                . number_format($wip->qty, 2) . ' pcs).',
            ]);
        }

        DB::transaction(function () use ($wip, $data) {
            // Lock row WIP untuk menghindari race condition
            $wip->refresh();
            if ($data['qty_to_sew'] > $wip->qty) {
                throw ValidationException::withMessages([
                    'qty_to_sew' => 'Stok WIP sudah berubah, silakan muat ulang halaman.',
                ]);
            }

            $qtyToSew = (float) $data['qty_to_sew'];
            $outputQty = (float) $data['output_qty'];
            $rejectQty = (float) ($data['reject_qty'] ?? 0);

            // 1) Generate kode batch sewing
            $date = \Carbon\Carbon::parse($data['date']);
            $prefix = 'BCH-SEW';
            $countToday = ProductionBatch::whereDate('date', $date->toDateString())
                ->where('process', 'sewing')
                ->count();
            $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . $date->format('ymd') . '-' . $seq;

            // 2) Buat production_batch (sewing)
            $outputItems = [
                $wip->item_code => $outputQty,
            ];

            $batch = ProductionBatch::create([
                'code' => $code,
                'date' => $date->toDateString(),
                'process' => 'sewing',
                'status' => 'done',

                // kita bisa link ke batch cutting asal lewat production_batch_id di WIP
                'external_transfer_id' => $wip->productionBatch?->external_transfer_id,
                'lot_id' => $wip->source_lot_id,

                'from_warehouse_id' => $wip->warehouse_id,
                'to_warehouse_id' => $wip->warehouse_id, // hasil masih di gudang yang sama (WIP sewing)

                'operator_code' => $data['operator_code'] ?: null,

                'input_qty' => $qtyToSew,
                'input_uom' => 'pcs',

                'output_total_pcs' => $outputQty,
                'output_items_json' => $outputItems,

                // gunakan waste_qty untuk catat reject
                'waste_qty' => $rejectQty,
                'remain_qty' => max($wip->qty - $qtyToSew, 0),

                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // 3) Kurangi stok WIP cutting
            $wip->qty = $wip->qty - $qtyToSew;
            $wip->save();

            // 4) Tambah WIP baru di stage 'sewing' (stok hasil jahit)
            //    Kalau kamu ingin langsung jadi FG, nanti bisa diubah / ditambah tabel FG.
            \App\Models\WipItem::create([
                'production_batch_id' => $batch->id,
                'item_id' => $wip->item_id,
                'item_code' => $wip->item_code,
                'warehouse_id' => $wip->warehouse_id,
                'source_lot_id' => $wip->source_lot_id,
                'stage' => 'sewing',
                'qty' => $outputQty,
                'notes' => 'WIP hasil sewing ' . $batch->code,
            ]);

            // 5) TODO: integrasi dengan modul stok FG / finishing di tahap berikutnya
        });

        return redirect()
            ->route('sewing.index')
            ->with('success', 'Batch sewing berhasil dibuat dari WIP ' . $wip->item_code . '.');
    }
}
