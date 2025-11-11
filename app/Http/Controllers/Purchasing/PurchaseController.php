<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Lot;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /** List + search + filter tipe item (opsional) */
    public function index(Request $r)
    {
        $q = trim((string) $r->get('q', ''));
        $status = $r->get('status'); // draft|posted
        $type = $r->get('type'); // material|pendukung|finished (filter baris)
        $supp = $r->get('supplier'); // supplier_id

        $rows = PurchaseInvoice::query()
            ->with(['supplier:id,name', 'warehouse:id,code,name'])
            ->when($q, fn($w) => $w->q($q))
            ->when($status, fn($w) => $w->where('status', $status))
            ->when($supp, fn($w) => $w->where('supplier_id', $supp))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        // info filter
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        return view('purchasing.invoices.index', compact('rows', 'q', 'status', 'type', 'suppliers', 'supp'));
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
                    ->with(['item:id,code,name,uom,type']);
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
        $kontrakanId = DB::table('warehouses')->where('code', 'KONTRAKAN')->value('id');

        // Note: di Blade ganti const itemsAll = @json($itemsAll);
        return view('purchasing.invoices.create', compact('suppliers', 'itemsAll', 'filterType', 'kontrakanId'));
    }

    /** Simpan pembelian + LOT + mutasi PURCHASE_IN ke KONTRAKAN */
    public function store(Request $r, \App\Services\InventoryService $inv)
    {
        $data = $r->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'], // penerimaan awal (KONTRAKAN)
            'note' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit' => ['required', 'string', 'max:16'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        // Kode invoice sebagai ref_code mutasi
        $datePart = date('ymd', strtotime($data['date']));
        $prefix = "INV-BKU-{$datePart}-";
        $nextSeq = str_pad((string) ($this->nextSeq($prefix)), 3, '0', STR_PAD_LEFT);
        $invCode = $prefix . $nextSeq;

        DB::transaction(function () use ($data, $invCode, $inv) {
            /** @var \App\Models\PurchaseInvoice $invoice */
            $invoice = \App\Models\PurchaseInvoice::create([
                'code' => $invCode,
                'date' => $data['date'],
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'note' => $data['note'] ?? null,
                'status' => 'posted',
            ]);

            foreach ($data['lines'] as $line) {
                $item = \App\Models\Item::findOrFail($line['item_id']);
                $qty = (float) $line['qty'];
                $unit = (string) $line['unit'];
                $cost = (float) $line['unit_cost'];

                // Simpan baris invoice
                $pl = \App\Models\PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'item_code' => $item->code,
                    'qty' => $qty,
                    'unit' => $unit,
                    'unit_cost' => $cost,
                ]);

                // Buat LOT: untuk pembelian (material/pendukung/finished)
                // Format LOT-{ITEM}-{YYYYMMDD}-###
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

                // Mutasi PURCHASE_IN (kompatibel dengan MutationController pairing logic)
                // - ref_code: kode invoice (sama untuk semua baris → memudahkan tracing)
                // - lot_id: lot baru
                // - date: pakai invoice->date (bukan now()), biar filter tanggal cocok

                $inv->mutate(
                    $invoice->warehouse_id,
                    $lotId,
                    'PURCHASE_IN',
                    $qty,
                    0,
                    $unit,
                    $invoice->code, // <— ref_code
                    "Pembelian {$item->code}",
                    $invoice->date->toDateString() . ' 00:00:00'
                );
            }
        });

        return redirect()->route('purchasing.invoices.index')->with('ok', 'Pembelian tersimpan.');
    }

    /** AJAX: harga terakhir per supplier+item */
    public function lastPrice(Request $r)
    {
        $supplierId = (int) $r->get('supplier_id');
        $itemId = (int) $r->get('item_id');

        if (!$supplierId || !$itemId) {
            return response()->json(['ok' => false, 'msg' => 'supplier_id dan item_id wajib diisi'], 422);
        }

        $last = PurchaseInvoiceLine::query()
            ->with(['invoice:id,date,supplier_id,code'])
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
        $max = DB::table('purchase_invoices')
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("max(substr(code, length(?) + 1, 10)) as suffix", [$prefix])
            ->value('suffix');

        $num = (int) $max;
        return $num + 1;
    }
}
