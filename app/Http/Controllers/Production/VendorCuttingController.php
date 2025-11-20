<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingBundle;
use App\Models\ExternalTransfer;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Lot;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\ProductionCost;
use Illuminate\Http\Request;

class VendorCuttingController extends Controller
{
    /**
     * INDEX
     * - Operator cutting: lihat kiriman ke gudang cutting miliknya
     * - Owner/Admin: lihat semua external transfer terkait cutting
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee ?? null;

        // ambil role, bisa dari user atau employee
        $role = $user->role ?? ($employee->role ?? null);

        $q = ExternalTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'productionBatch'])
            ->orderByDesc('date');

        // Jika BUKAN owner/admin → filter per gudang cutting operator
        if (!in_array($role, ['owner', 'admin'])) {
            if (!$employee || !$employee->cutting_warehouse_id) {
                abort(403, 'Employee ini belum di-mapping ke gudang cutting.');
            }

            $cuttingWarehouseId = $employee->cutting_warehouse_id;

            $q->where('to_warehouse_id', $cuttingWarehouseId);
        }

        // Hanya status yang relevan untuk cutting
        $q->whereIn('status', ['SENT', 'sent', 'BATCHED', 'batched']);

        // Filter pencarian kode (optional)
        if ($search = $request->get('q')) {
            $q->where('code', 'like', "%{$search}%");
        }

        $transfers = $q->paginate(20);

        return view('production.vendor_cutting.index', compact('transfers', 'role'));
    }

    /**
     * FORM terima bahan & buat batch cutting
     */
    public function receiveForm(Request $request, ExternalTransfer $externalTransfer)
    {
        $user = $request->user();
        $employee = $user->employee ?? null;
        $role = $user->role ?? ($employee->role ?? null);

        // Owner/admin boleh buka semua, cutting harus sesuai gudang
        if (!in_array($role, ['owner', 'admin'])) {
            $cuttingWarehouseId = $employee->cutting_warehouse_id ?? null;

            if ($externalTransfer->to_warehouse_id !== $cuttingWarehouseId) {
                abort(403, 'Tidak boleh memproses transfer untuk gudang lain.');
            }
        }

        // Kalau sudah punya batch, langsung redirect ke showBatch
        if ($externalTransfer->productionBatch) {
            return redirect()
                ->route('production.vendor_cutting.batches.show', $externalTransfer->productionBatch->id);
        }

        $externalTransfer->load(['lines.lot', 'fromWarehouse', 'toWarehouse']);

        return view('production.vendor_cutting.confirm_receive', [
            'transfer' => $externalTransfer,
        ]);
    }

    /**
     * STORE: buat batch cutting dari external transfer
     */
    public function receiveStore(Request $request, ExternalTransfer $externalTransfer)
    {
        $user = $request->user();
        $employee = $user->employee ?? null;
        $role = $user->role ?? ($employee->role ?? null);

        if (!in_array($role, ['owner', 'admin'])) {
            $cuttingWarehouseId = $employee->cutting_warehouse_id ?? null;

            if ($externalTransfer->to_warehouse_id !== $cuttingWarehouseId) {
                abort(403, 'Tidak boleh memproses transfer untuk gudang lain.');
            }
        }

        // Jika sudah punya batch, jangan dobel
        if ($externalTransfer->productionBatch) {
            return redirect()
                ->route('production.vendor_cutting.batches.show', $externalTransfer->productionBatch->id)
                ->with('info', 'Dokumen ini sudah memiliki batch cutting.');
        }

        if (!in_array($externalTransfer->status, ['SENT', 'sent'])) {
            return back()->withErrors('Dokumen ini tidak dalam status SENT.');
        }

        $data = $request->validate([
            'date_received' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $externalTransfer->load('lines.lot');

        // Cek stok LOT di gudang tujuan
        foreach ($externalTransfer->lines as $line) {
            $stock = InventoryStock::where('warehouse_id', $externalTransfer->to_warehouse_id)
                ->where('lot_id', $line->lot_id)
                ->first();

            if (!$stock || $stock->qty < $line->qty) {
                return back()->withErrors(
                    'Stok LOT ' . ($line->lot->code ?? 'UNKNOWN') . ' di gudang cutting tidak cukup (qty: ' . ($stock->qty ?? 0) . ').'
                );
            }
        }

        $operatorCode = $employee->code ?? $user->name;

        // Generate kode batch
        $batchCode = $this->generateCuttingBatchCode();

        // Buat ProductionBatch
        $batch = ProductionBatch::create([
            'code' => $batchCode,
            'stage' => 'cutting',
            'status' => 'received',
            'operator_code' => $operatorCode,
            'from_warehouse_id' => $externalTransfer->from_warehouse_id,
            'to_warehouse_id' => $externalTransfer->to_warehouse_id,
            'external_transfer_id' => $externalTransfer->id,
            'date_received' => $data['date_received'],
            'notes' => $data['notes'] ?? null,
        ]);

        // Detail bahan
        foreach ($externalTransfer->lines as $line) {
            ProductionBatchMaterial::create([
                'production_batch_id' => $batch->id,
                'lot_id' => $line->lot_id,
                'item_id' => $line->item_id,
                'item_code' => $line->item_code,
                'qty_planned' => $line->qty,
                'unit' => $line->unit,
            ]);
        }

        // Update status external transfer → BATCHED
        $externalTransfer->update([
            'status' => 'BATCHED',
            'received_at' => now(), // pastikan kolom ini ada di migration external_transfers
        ]);

        return redirect()
            ->route('production.vendor_cutting.batches.show', $batch->id)
            ->with('success', 'Batch Cutting ' . $batch->code . ' berhasil dibuat.');
    }

    /**
     * Lihat detail batch cutting
     */

    public function showBatch(ProductionBatch $batch)
    {
        $batch->load([
            'materials.lot',
            'materials.item',
            'fromWarehouse',
            'toWarehouse',
            'externalTransfer',
            'bundles.lot',
            'bundles.item',
        ]);

        return view('production.vendor_cutting.show_batch', compact('batch'));
    }

    public function editResults(ProductionBatch $batch)
    {
        // pastikan memang batch cutting
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        $batch->load(['materials.lot', 'materials.item', 'bundles']);

        // LOT yang tersedia di batch (untuk dropdown)
        $lots = $batch->materials->map(function ($m) {
            return $m->lot;
        })->unique('id')->values();

        // Item hasil: sementara ambil semua item FG (atau sementara semua item dulu)
        $items = Item::orderBy('code')->get();

        // Bundles existing (kalau mau edit ulang)
        $bundles = $batch->bundles()->with(['lot', 'item'])->orderBy('bundle_no')->get();

        return view('production.vendor_cutting.bundles_edit', compact(
            'batch',
            'lots',
            'items',
            'bundles'
        ));
    }

    /**
     * STORE: simpan hasil cutting per iket (bundle).
     */
    public function updateResults(Request $request, ProductionBatch $batch)
    {
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        $data = $request->validate([
            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'bundles.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'bundles.*.bundle_code' => ['nullable', 'string', 'max:64'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            'bundles.*.qty_cut' => ['required', 'numeric', 'min:1'],
            'bundles.*.unit' => ['required', 'string', 'max:16'],
            'bundles.*.notes' => ['nullable', 'string', 'max:255'],

            'cutting_rate' => ['nullable', 'numeric', 'min:0'],
            'cutting_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bundlesInput = $data['bundles'];

        // Hapus dulu bundle lama, lalu insert ulang (simple approach)
        $batch->bundles()->delete();

        $seq = 1;

        foreach ($bundlesInput as $row) {
            $item = Item::find($row['item_id']);

            $bundleNo = $row['bundle_no'] ?? $seq;
            $bundleCode = $row['bundle_code'] ?: $this->generateBundleCode($batch, $item, $bundleNo);

            CuttingBundle::create([
                'production_batch_id' => $batch->id,
                'lot_id' => $row['lot_id'],
                'item_id' => $row['item_id'],
                'item_code' => $item->code,
                'bundle_code' => $bundleCode,
                'bundle_no' => $bundleNo,
                'qty_cut' => $row['qty_cut'],
                'unit' => $row['unit'],
                'status' => 'cut',
                'notes' => $row['notes'] ?? null,
            ]);

            $seq++;
        }

        if ($batch->status === 'received') {
            $batch->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $totalQtyCut = $batch->bundles()->sum('qty_cut');

        $cuttingRate = isset($data['cutting_rate'])
        ? (float) $data['cutting_rate']
        : 1000;

        $cuttingFee = isset($data['cutting_fee'])
        ? (float) $data['cutting_fee']
        : 0.0;

// Kalau cutting_fee kosong tapi ada rate dan qty → hitung otomatis
        if ($cuttingFee <= 0 && $cuttingRate > 0 && $totalQtyCut > 0) {
            $cuttingFee = $cuttingRate * $totalQtyCut;
        }
// Simpan ke batch (boleh saja 0/null kalau belum ada datanya)
        $batch->cutting_rate = $cuttingRate > 0 ? $cuttingRate : null;
        $batch->cutting_fee = $cuttingFee > 0 ? $cuttingFee : null;
        $batch->save();

        return redirect()
            ->route('production.vendor_cutting.batches.show', $batch->id)
            ->with('success', 'Hasil cutting per iket berhasil disimpan.');
    }

    public function sendToQc(ProductionBatch $batch)
    {
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        if ($batch->bundles()->count() == 0) {
            return back()->withErrors('Tidak bisa kirim ke QC. Belum ada hasil cutting.');
        }

        \DB::transaction(function () use ($batch) {

            // 1. Update status semua bundle → sent_qc
            $batch->bundles()->update([
                'status' => 'sent_qc',
            ]);

            // 2. Update batch
            $batch->update([
                'status' => 'waiting_qc',
                'finished_at' => $batch->finished_at ?? now(), // isi kalau belum pernah di-set
            ]);

            // 3. ❌ JANGAN lagi insert ke wip_items di sini
            //    WIP hasil cutting kita buat SETELAH QC DONE,
            //    lewat method QC update(), supaya:
            //    - qty_ok yang dipakai
            //    - punya lot_id & cutting_bundle_id per bundle
            $this->recordCuttingCost($batch);
        });

        return redirect()
            ->route('production.vendor_cutting.batches.show', $batch->id)
            ->with('success', 'Batch berhasil dikirim ke QC!');
    }

    protected function recordCuttingCost($batch): void
    {
        $totalFee = (float) ($batch->cutting_fee ?? 0);

        if ($totalFee <= 0) {
            return;
        }

        // Ambil total qty_cut per LOT dari bundles
        $lotQty = $batch->bundles()
            ->selectRaw('lot_id, SUM(qty_cut) as total_qty')
            ->groupBy('lot_id')
            ->get();

        if ($lotQty->isEmpty()) {
            return;
        }

        $totalQtyAll = (float) $lotQty->sum('total_qty');
        if ($totalQtyAll <= 0) {
            return;
        }

        foreach ($lotQty as $row) {
            $lotId = (int) $row->lot_id;
            $qtyLot = (float) $row->total_qty;

            if ($qtyLot <= 0) {
                continue;
            }

            // Ambil item hasil cutting utk LOT ini
            $itemId = $batch->bundles()
                ->where('lot_id', $lotId)
                ->value('item_id'); // <--- ambil item_id dari bundle
            // Proporsi biaya untuk LOT ini
            $amount = $totalFee * ($qtyLot / $totalQtyAll);
            $cpu = $amount / max(1, $qtyLot);

            ProductionCost::create([
                'lot_id' => $lotId,
                'item_id' => null, // <--- sudah isi item_id
                'stage' => 'cutting',
                'qty_base' => $qtyLot,
                'amount' => $amount,
                'cost_per_unit' => $cpu,

                'source_type' => 'vendor_cutting_batch',
                'source_id' => $batch->id,
                'notes' => 'Biaya cutting batch ' . $batch->code,
            ]);
        }
    }

    /**
     * Generator kode batch cutting
     */
    protected function generateCuttingBatchCode(): string
    {
        $date = now()->format('Ymd');

        $last = ProductionBatch::where('stage', 'cutting')
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        $seq = 1;

        if ($last) {
            $lastSeq = (int) substr($last->code, -3);
            $seq = $lastSeq + 1;
        }

        return 'BATCH-CUT-' . $date . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    protected function generateBundleCode(ProductionBatch $batch, Item $item, int $bundleNo): string
    {
        return sprintf(
            'BND-%s-%s-%03d',
            $item->code,
            $batch->id,
            $bundleNo
        );
    }

    /**
     * Catat biaya cutting ke production_costs.
     *
     * @param  \App\Models\VendorCuttingBatch  $batch
     *   ❗ Sesuaikan tipe model dengan model header vendor cutting kamu.
     */

}
