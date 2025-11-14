<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\FinishedGood;
use App\Models\Lot;
use App\Models\ProductionBatch;
use App\Models\WipItem;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinishingController extends Controller
{
    /**
     * Daftar WIP hasil sewing yang siap finishing / menjadi FG.
     */
    public function index()
    {
        $wips = WipItem::with(['item', 'warehouse', 'productionBatch'])
            ->stage('sewing')
            ->available()
            ->orderBy('warehouse_id')
            ->orderBy('item_code')
            ->paginate(50);

        return view('production.finished.index', compact('wips'));
    }

    public function show(FinishedGood $fg)
    {
        $fg->load(['item', 'warehouse', 'lot', 'sourceLot']);
        return view('production.finished.show', compact('fg'));
    }
    /**
     * Form buat batch finishing dari 1 WIP hasil sewing.
     */
    public function create($wipItemId)
    {
        $wip = WipItem::with(['item', 'warehouse', 'productionBatch', 'sourceLot'])
            ->stage('sewing')
            ->available()
            ->findOrFail($wipItemId);

        return view('production.finished.create', [
            'wip' => $wip,
        ]);
    }

    /**
     * Simpan batch finishing:
     * - kurangi qty WIP sewing
     * - buat production_batch (finishing)
     * - insert ke tabel finished_goods
     */
    public function store($wipItemId, Request $request)
    {
        $wip = WipItem::with(['item', 'warehouse', 'productionBatch'])
            ->stage('sewing')
            ->available()
            ->findOrFail($wipItemId);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'operator_code' => ['nullable', 'string', 'max:100'],
            'qty_to_finish' => ['required', 'numeric', 'min:1'],
            'fg_qty' => ['required', 'numeric', 'min:1'],
            'reject_qty' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['qty_to_finish'] > $wip->qty) {
            throw ValidationException::withMessages([
                'qty_to_finish' => 'Qty finishing tidak boleh melebihi stok WIP ('
                . number_format($wip->qty, 2) . ' pcs).',
            ]);
        }

        DB::transaction(function () use ($wip, $data) {
            $wip->refresh();
            if ($data['qty_to_finish'] > $wip->qty) {
                throw ValidationException::withMessages([
                    'qty_to_finish' => 'Stok WIP sudah berubah, silakan muat ulang halaman.',
                ]);
            }

            $date = Carbon::parse($data['date']);
            $qtyToFinish = (float) $data['qty_to_finish'];
            $fgQty = (float) $data['fg_qty'];
            $rejectQty = (float) ($data['reject_qty'] ?? 0);

            $fgUnit = 'pcs';

            $lotPrefix = 'LOT-FG-' . $wip->item_code . '-' . $date->format('ymd');
            $running = Lot::where('code', 'like', $lotPrefix . '%')->count() + 1;
            $lotCode = $lotPrefix . '-' . str_pad($running, 3, '0', STR_PAD_LEFT);

            $fgLot = Lot::create([
                'item_id' => $wip->item_id,
                'code' => $lotCode,
                'unit' => 'pcs',
                'initial_qty' => $fgQty,
                'unit_cost' => 0,
                'date' => $date->toDateString(),
            ]);

            // 1) Generate kode batch finishing
            $date = \Carbon\Carbon::parse($data['date']);
            $prefix = 'BCH-FIN';
            $countToday = ProductionBatch::whereDate('date', $date->toDateString())
                ->where('process', 'finishing')
                ->count();
            $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . $date->format('ymd') . '-' . $seq;

            // 2) Buat production_batch (finishing)
            $outputItems = [
                $wip->item_code => $fgQty,
            ];

            $batch = ProductionBatch::create([
                'code' => $code,
                'date' => $date->toDateString(),
                'process' => 'finishing',
                'status' => 'done',

                'external_transfer_id' => $wip->productionBatch?->external_transfer_id,
                'lot_id' => $wip->source_lot_id,

                'from_warehouse_id' => $wip->warehouse_id,
                'to_warehouse_id' => $wip->warehouse_id, // hasil FG disimpan di gudang ini

                'operator_code' => $data['operator_code'] ?: null,

                'input_qty' => $qtyToFinish,
                'input_uom' => 'pcs',

                'output_total_pcs' => $fgQty,
                'output_items_json' => $outputItems,

                'waste_qty' => $rejectQty,
                'remain_qty' => max($wip->qty - $qtyToFinish, 0),

                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // 3) Kurangi stok WIP sewing
            $wip->qty = $wip->qty - $qtyToFinish;
            $wip->save();
            // 4) Insert barang jadi ke finished_goods
            $fg = FinishedGood::create([
                'production_batch_id' => $batch->id,
                'item_id' => $wip->item_id,
                'item_code' => $wip->item_code,
                'lot_id' => $fgLot->id,
                'warehouse_id' => $wip->warehouse_id,
                'unit' => $fgUnit,
                'qty' => $fgQty,
                'source_lot_id' => $wip->source_lot_id,
                'variant' => null,
                'notes' => 'FG dari finishing ' . $batch->code,
            ]);
            // 5) TODO: integrasi ke modul Inventory FG (mutasi stok, dsb.)
            InventoryService::addStockLot([
                'warehouse_id' => $fg->warehouse_id,
                'lot_id' => $fg->lot_id, // LOT FG baru
                'item_id' => $fg->item_id,
                'item_code' => $fg->item_code,
                'unit' => $fg->unit, // 'pcs'
                'qty' => $fg->qty,
                'date' => $date->toDateString(),
                'type' => 'FG_IN',
                'ref_code' => $batch->code,
                'note' => 'FG dari finishing ' . $batch->code,
            ]);

        });

        return redirect()
            ->route('finishing.index')
            ->with('ok', 'Batch finishing berhasil dibuat dan stok FG ditambahkan.');
    }
}
