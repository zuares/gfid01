<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ExternalTransfer;
use App\Models\Item;
use App\Models\ProductionBatch;
use App\Models\WipItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorCuttingController extends Controller
{
    /**
     * List dokumen external transfer (cutting) yang siap / sedang diproses vendor.
     * Hanya ambil yang process = cutting dan status sent / received.
     */
    public function index()
    {
        $rows = ExternalTransfer::withCount('lines')
            ->where('process', 'cutting')
            ->whereIn('status', ['sent', 'received'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(30);

        return view('production.vendor_cutting.index', compact('rows'));
    }

    /**
     * Form proses cutting untuk satu external transfer.
     * - tampilkan info pengiriman & lot kain
     * - input hasil cutting (barang setengah jadi / WIP)
     */
    public function create(ExternalTransfer $externalTransfer)
    {
        // safety: hanya boleh cutting & status sent/received
        if ($externalTransfer->process !== 'cutting' || !in_array($externalTransfer->status, ['sent', 'received'])) {
            return redirect()
                ->route('vendor-cutting.index')
                ->with('error', 'Dokumen ini tidak bisa diproses cutting.');
        }

        $externalTransfer->load(['fromWarehouse', 'toWarehouse', 'lines.lot', 'lines.item']);

        // Hitung total qty kain (input)
        $inputQty = $externalTransfer->lines->sum('qty');
        $inputUom = optional($externalTransfer->lines->first())->uom ?? 'kg';

        // Item barang jadi / WIP yang boleh dipilih sebagai hasil cutting.
        // Untuk sementara, ambil semua items; nanti bisa difilter by type.
        $finishedItems = Item::orderBy('code')
            ->select('id', 'code', 'name')
            ->limit(500)
            ->get();

        return view('production.vendor_cutting.create', [
            't' => $externalTransfer,
            'inputQty' => $inputQty,
            'inputUom' => $inputUom,
            'finishedItems' => $finishedItems,
        ]);
    }

    /**
     * Simpan hasil cutting:
     * - jika status awal sent → ubah ke received (konfirmasi bahan diterima)
     * - buat 1 production_batch (process = cutting)
     */
    public function store($externalTransferId, Request $request)
    {
        $t = ExternalTransfer::with('lines')
            ->where('process', 'cutting')
            ->whereIn('status', ['sent', 'received'])
            ->findOrFail($externalTransferId);
        // Validasi input form
        $data = $request->validate([
            'input_qty' => ['required', 'numeric', 'min:0'],
            'input_uom' => ['nullable', 'string', 'max:10'],
            'waste_qty' => ['nullable', 'numeric', 'min:0'],
            'remain_qty' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.item_code' => ['required', 'string', 'max:50'],
            'results.*.item_name' => ['nullable', 'string', 'max:255'],
            'results.*.qty' => ['required', 'numeric', 'min:1'],
        ], [
            'results.required' => 'Minimal satu baris hasil cutting harus diisi.',
        ]);

        // Susun outputItems dulu untuk bisa validasi item master
        $outputItems = [];
        foreach ($data['results'] as $row) {
            $code = trim($row['item_code']);
            $qty = (float) $row['qty'];

            if ($code === '' || $qty <= 0) {
                continue;
            }

            if (!isset($outputItems[$code])) {
                $outputItems[$code] = 0;
            }
            $outputItems[$code] += $qty;
        }

        if (empty($outputItems)) {
            throw ValidationException::withMessages([
                'results' => 'Tidak ada data hasil cutting yang valid.',
            ]);
        }

        // Validasi bahwa semua item_code ada di tabel items
        $codes = array_keys($outputItems);
        $itemsFound = Item::whereIn('code', $codes)->pluck('id', 'code');

        $missing = array_diff($codes, $itemsFound->keys()->all());
        if (count($missing) > 0) {
            throw ValidationException::withMessages([
                'results' => 'Kode item berikut belum terdaftar di master items: ' . implode(', ', $missing),
            ]);
        }

        DB::transaction(function () use ($t, $data, $outputItems, $itemsFound) {
            // 1) Update status external_transfer
            if ($t->status === 'sent') {
                $t->status = 'received';
            }
            // Anggap setelah input hasil cutting, dokumen ini selesai
            $t->status = 'done';
            $t->save();

            // 2) Hitung output_total_pcs
            $outputTotal = array_sum($outputItems);

            // 3) Ambil lot & uom dari baris pertama
            $firstLine = $t->lines->first();
            $lotId = $firstLine?->lot_id;
            $uom = $data['input_uom'] ?: ($firstLine?->uom ?? 'kg');

            // 4) Generate kode batch cutting
            $today = now();
            $prefix = 'BCH-CUT';
            $countToday = ProductionBatch::whereDate('date', $today->toDateString())
                ->where('process', 'cutting')
                ->count();
            $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . $today->format('ymd') . '-' . $seq;

            // 5) Insert production_batch (cutting)
            $batch = ProductionBatch::create([
                'code' => $code,
                'date' => $today->toDateString(),
                'process' => 'cutting',
                'status' => 'done',
                'external_transfer_id' => $t->id,
                'lot_id' => $lotId,
                'from_warehouse_id' => $t->to_warehouse_id, // cutting terjadi di lokasi tujuan
                'to_warehouse_id' => $t->to_warehouse_id,
                'operator_code' => $t->operator_code,

                'input_qty' => $data['input_qty'],
                'input_uom' => $uom,

                'output_total_pcs' => $outputTotal,
                'output_items_json' => $outputItems,

                'waste_qty' => $data['waste_qty'] ?? 0,
                'remain_qty' => $data['remain_qty'] ?? 0,

                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // 6) Insert stok WIP hasil cutting ke wip_items
            //    Stage: 'cutting', lokasi stok: to_warehouse_id dari external_transfer
            $warehouseId = $t->to_warehouse_id;
            $sourceLotId = $lotId;

            foreach ($outputItems as $code => $qty) {
                $itemId = $itemsFound[$code] ?? null;
                if (!$itemId) {
                    continue; // harusnya tidak terjadi karena sudah divalidasi
                }

                WipItem::create([
                    'production_batch_id' => $batch->id,
                    'item_id' => $itemId,
                    'item_code' => $code,
                    'warehouse_id' => $warehouseId,
                    'source_lot_id' => $sourceLotId,
                    'stage' => 'cutting',
                    'qty' => $qty,
                    'notes' => 'Auto WIP dari cutting ' . $batch->code,
                ]);
            }

            // 7) TODO: integrasi dengan modul Inventory (mutasi stok kain & stok WIP)
        });

        return redirect()
            ->route('vendor-cutting.index')
            ->with('success', "Hasil cutting untuk {$t->code} berhasil disimpan dan stok WIP dibuat.");
    }

    /**
     * Generate kode batch cutting:
     * BCH-CUT-YYMMDD-###
     */
    protected function generateBatchCode(string $date): string
    {
        $d = Carbon::parse($date);
        $prefix = 'BCH-CUT';
        $ymd = $d->format('ymd');

        $countToday = ProductionBatch::where('process', 'cutting')
            ->whereDate('date', $d->toDateString())
            ->count();

        $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$ymd}-{$seq}";
    }
}
