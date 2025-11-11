<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Lot;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Supplier;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /** List + search + filter tipe item (opsional) */
    public function index(Request $r)
    {
        // App\Http\Controllers\Purchasing\PurchaseController@index
        $q = trim((string) request('q', ''));
        $status = request('status'); // draft|posted
        $supp = request('supplier'); // supplier_id
        $range = request('range'); // "2025-11-01 s/d 2025-11-11"

        $rows = \App\Models\PurchaseInvoice::with('supplier')
            ->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$q}%"));
            }))
            ->when($status, fn($qq) => $qq->where('status', $status))
            ->when($supp, fn($qq) => $qq->where('supplier_id', $supp))
            ->when($range, function ($qq) use ($range) {
                $range = trim($range ?? '');
                if (preg_match('~^(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})$~', $range, $m)) {
                    $qq->whereBetween('date', [$m[1], $m[2]]);
                }
            })
            ->orderByDesc('date')
            ->orderByDesc('id') // ✅ urutan stabil dari yang terbaru
            ->paginate(20);

        $suppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name']);

        return view('purchasing.invoices.index', compact('q', 'status', 'supp', 'range', 'suppliers', 'rows'));
    }

    /** Tampilkan detail 1 invoice pembelian */
    public function show(PurchaseInvoice $invoice)
    {
        // eager load relasi supaya hemat query
        $invoice->load([
            'supplier:id,code,name,phone',
            'warehouse:id,code,name',
            'lines' => function ($q) {
                $q->select('id', 'purchase_invoice_id', 'item_id', 'item_code', 'qty', 'unit', 'unit_cost')
                    ->with(['item:id,code,name,uom,type'])
                    ->orderBy('id');
            },
        ]);

        // hitung subtotal per baris & total
        $lines = $invoice->lines->map(function ($l) {
            $subtotal = (float) $l->qty * (float) $l->unit_cost;
            return [
                'id' => $l->id,
                'item_code' => $l->item_code,
                'item_name' => $l->item?->name,
                'type' => $l->item?->type, // material | pendukung | finished
                'qty' => (float) $l->qty,
                'unit' => $l->unit,
                'unit_cost' => (float) $l->unit_cost,
                'subtotal' => $subtotal,
            ];
        });

        $grandTotal = $lines->sum('subtotal');

        return view('purchasing.invoices.show', [
            'invoice' => $invoice,
            'lines' => $lines,
            'grandTotal' => $grandTotal,
        ]);
    }

    /** Form create */
    /** Form create */
    public function create(Request $r)
    {
        // Supplier untuk dropdown
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'code']);

        // Jenis item default (untuk state awal filter di UI)
        $filterType = $r->get('type', 'material'); // 'material' | 'pendukung' | 'finished'

        // KIRIM SEMUA ITEM ke view (biar saat ganti filter di UI tidak kosong)
        $itemsAll = Item::orderBy('name')->get(['id', 'code', 'name', 'uom', 'type']);

        // Default gudang penerimaan = KONTRAKAN
        $kontrakanId = DB::table('warehouses')->where('code', 'KONTRAKAN')->value('id') ?? DB::table('warehouses')->orderBy('id')->value('id');

        // Note: di Blade ganti const itemsAll = @json($itemsAll);
        return view('purchasing.invoices.create', compact('suppliers', 'itemsAll', 'filterType', 'kontrakanId'));
    }

    /** Simpan pembelian + LOT + mutasi PURCHASE_IN ke KONTRAKAN */
    // public function store(Request $r, \App\Services\InventoryService $inv, JournalService $journal)
    // {
    //     $data = $r->validate([
    //         'date' => ['required', 'date'],
    //         'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
    //         'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
    //         'note' => ['nullable', 'string'],
    //         'is_cash' => ['nullable', 'boolean'], // tambahkan di form kalau mau tunai
    //         'lines' => ['required', 'array', 'min:1'],
    //         'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
    //         'lines.*.qty' => ['required', 'numeric', 'min:0.001'],
    //         'lines.*.unit' => ['required', 'string', 'max:16'],
    //         'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
    //     ]);

    //     $datePart = date('ymd', strtotime($data['date']));
    //     $prefix = "INV-BKU-{$datePart}-";
    //     $invCode = $prefix . str_pad((string) ($this->nextSeq($prefix)), 3, '0', STR_PAD_LEFT);

    //     DB::transaction(function () use ($data, $invCode, $inv, $journal) {
    //         $invoice = \App\Models\PurchaseInvoice::create([
    //             'code' => $invCode,
    //             'date' => $data['date'],
    //             'supplier_id' => $data['supplier_id'],
    //             'warehouse_id' => $data['warehouse_id'],
    //             'note' => $data['note'] ?? null,
    //             'status' => 'posted',
    //         ]);

    //         $grand = 0;

    //         foreach ($data['lines'] as $line) {
    //             $item = \App\Models\Item::findOrFail($line['item_id']);
    //             $qty = (float) $line['qty'];
    //             $unit = (string) $line['unit'];
    //             $cost = (float) $line['unit_cost'];

    //             \App\Models\PurchaseInvoiceLine::create([
    //                 'purchase_invoice_id' => $invoice->id,
    //                 'item_id' => $item->id,
    //                 'item_code' => $item->code,
    //                 'qty' => $qty,
    //                 'unit' => $unit,
    //                 'unit_cost' => $cost,
    //             ]);

    //             $subtotal = $qty * $cost;
    //             $grand += $subtotal;

    //             // LOT pembelian
    //             $lotCode = \App\Support\LotCode::nextMaterial($item->code, new \DateTime($data['date']));
    //             $lotId = DB::table('lots')->insertGetId([
    //                 'item_id' => $item->id,
    //                 'code' => $lotCode,
    //                 'unit' => $unit,
    //                 'initial_qty' => $qty,
    //                 'unit_cost' => $cost,
    //                 'date' => $data['date'],
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ]);

    //             // Mutasi persediaan (KONTRAKAN)
    //             $inv->mutate(
    //                 $invoice->warehouse_id,
    //                 $lotId,
    //                 'PURCHASE_IN',
    //                 $qty,
    //                 0,
    //                 $unit,
    //                 $invoice->code,
    //                 "Pembelian {$item->code}",
    //                 $invoice->date->toDateString() . ' 00:00:00'
    //             );
    //         }

    //         // Jurnal sederhana (Persediaan vs Hutang/Kas)
    //         $isCash = (bool) ($data['is_cash'] ?? false);
    //         $journal->postPurchase(
    //             refCode: $invoice->code,
    //             date: $invoice->date->toDateString(),
    //             amount: $grand,
    //             cash: $isCash,
    //             memo: $data['note'] ?? null,
    //         );
    //     });

    //     return redirect()
    //         ->route('purchasing.invoices.index')
    //         ->with('ok', "Pembelian {$invCode} & jurnal tersimpan.");

    // }
    public function store(Request $r, \App\Services\InventoryService $inv, JournalService $journal)
    {
        $data = $r->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_cash' => ['nullable', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'string'], // terima string agar bisa normalisasi "1.000,50"
            'lines.*.unit' => ['required', 'string', 'max:16'],
            'lines.*.unit_cost' => ['required', 'string'], // terima string
        ]);

        // Normalisasi angka Indonesia --> float
        $norm = function ($s): float {
            $s = trim((string) $s);
            // buang spasi non-breaking
            $s = str_replace("\xc2\xa0", ' ', $s);
            // hapus semua titik pemisah ribuan
            $s = str_replace('.', '', $s);
            // ganti koma (desimal id) ke titik
            $s = str_replace(',', '.', $s);
            // filter karakter valid
            if (!preg_match('~^-?\d+(\.\d+)?$~', $s)) {
                abort(422, 'Format angka tidak valid.');
            }
            return (float) $s;
        };

        foreach ($data['lines'] as &$line) {
            $line['qty'] = $norm($line['qty']);
            $line['unit_cost'] = $norm($line['unit_cost']);
            if ($line['qty'] <= 0) {
                abort(422, 'Qty tidak boleh <= 0');
            }

            if ($line['unit_cost'] < 0) {
                abort(422, 'Harga tidak boleh < 0');
            }

        }
        unset($line);

        $datePart = date('ymd', strtotime($data['date']));
        $prefix = "INV-BKU-{$datePart}-";
        $invCode = $prefix . str_pad((string) ($this->nextSeq($prefix)), 3, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($data, $invCode, $inv, $journal) {
            $invoice = \App\Models\PurchaseInvoice::create([
                'code' => $invCode,
                'date' => $data['date'],
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'note' => $data['note'] ?? null,
                'status' => 'posted',
            ]);

            $grand = 0.0;

            foreach ($data['lines'] as $line) {
                $item = \App\Models\Item::findOrFail($line['item_id']);
                $qty = (float) $line['qty'];
                $unit = (string) $line['unit'];
                $cost = (float) $line['unit_cost'];

                \App\Models\PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'item_code' => $item->code,
                    'qty' => $qty,
                    'unit' => $unit,
                    'unit_cost' => $cost,
                ]);

                $grand += $qty * $cost;

                // LOT pembelian
                $lotCode = \App\Support\LotCode::nextMaterial($item->code, new \DateTime($data['date']));
                $lotId = DB::table('lots')->insertGetId([
                    'item_id' => $item->id,
                    'code' => $lotCode,
                    'unit' => $unit,
                    'initial_qty' => $qty,
                    'unit_cost' => $cost,
                    'date' => $data['date'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Mutasi persediaan (gudang tujuan)
                $inv->mutate(
                    $invoice->warehouse_id,
                    $lotId,
                    'PURCHASE_IN',
                    $qty,
                    0,
                    $unit,
                    $invoice->code,
                    "Pembelian {$item->code}",
                    $invoice->date->toDateString() . ' 00:00:00'
                );
            }

            // Jurnal: 1201 vs 1101/2101 + memo
            $isCash = (bool) ($data['is_cash'] ?? false);
            $journal->postPurchase(
                refCode: $invoice->code,
                date: $invoice->date->toDateString(),
                amount: $grand,
                cash: $isCash,
                memo: $data['note'] ?? null,
            );
        });

        return redirect()->route('purchasing.invoices.index')
            ->with('ok', "Pembelian {$invCode} & jurnal tersimpan.");
    }

    /** AJAX: harga terakhir per supplier+item */
    public function lastPrice(Request $r)
    {
        $supplierId = (int) $r->get('supplier_id');
        $itemId = (int) $r->get('item_id');

        if (!$supplierId || !$itemId) {
            return response()->json(['ok' => false, 'msg' => 'supplier_id dan item_id wajib diisi'], 422);
        }

        $last = PurchaseInvoiceLine::with(['invoice:id,date,supplier_id,code'])
            ->lastPrice($supplierId, $itemId)
            ->first();

        if (!$last) {
            return response()->json(['ok' => true, 'data' => null]);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'unit_cost' => (float) $last->unit_cost,
                'unit' => $last->unit,
                'date' => optional($last->invoice->date)->format('Y-m-d'),
                'inv_code' => $last->invoice->code,
            ],
        ]);
    }

    /** AJAX: riwayat pembelian ringkas per supplier+item (n terakhir) */
    public function history(Request $r)
    {
        $supplierId = (int) $r->get('supplier_id');
        $itemId = (int) $r->get('item_id');
        $limit = max(1, (int) $r->get('limit', 10));

        $rows = PurchaseInvoiceLine::query()
            ->with(['invoice:id,date,code,supplier_id'])
            ->where('item_id', $itemId)
            ->whereHas('invoice', fn($w) => $w->where('supplier_id', $supplierId))
            ->orderByDesc(
                \DB::raw("(select date from purchase_invoices where purchase_invoices.id = purchase_invoice_lines.purchase_invoice_id)")
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'purchase_invoice_id', 'item_id', 'item_code', 'qty', 'unit', 'unit_cost']);

        $data = $rows->map(function ($x) {
            return [
                'date' => optional($x->invoice->date)->format('Y-m-d'),
                'inv_code' => $x->invoice->code,
                'item_code' => $x->item_code,
                'qty' => (float) $x->qty,
                'unit' => $x->unit,
                'unit_cost' => (float) $x->unit_cost,
            ];
        });

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /** Hitung next sequence untuk INV-BKU-YYMMDD-### */
    protected function nextSeq(string $prefix): int
    {
        // Ambil suffix numeric paling besar dari kode yang match prefix
        $last = DB::table('purchase_invoices')
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code') // karena zero-pad, sorting string aman
            ->value('code');

        if (!$last) {
            return 1;
        }

        // Format: INV-BKU-YYMMDD-###
        $suffix = (int) preg_replace('~^' . preg_quote($prefix, '~') . '~', '', $last);
        return $suffix + 1;
    }

}
