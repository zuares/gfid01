<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Supplier;
use App\Services\InventoryService;
use App\Services\JournalService;
use App\Services\PurchasePaymentService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{

    public function __construct(
        protected InventoryService $inv,
        protected JournalService $journal,
        protected PurchasePaymentService $pps,
    ) {}
    /** List + search + filter tipe item (opsional) + filter payment status */
    public function index(Request $r)
    {
        $q = trim((string) $r->get('q', ''));
        $status = $r->get('status'); // draft|posted
        $supp = $r->get('supplier'); // supplier_id
        $range = $r->get('range'); // "YYYY-MM-DD s/d YYYY-MM-DD"
        $pay = $r->get('payment'); // unpaid|partial|paid   <-- kamu pakai 'payment'

        $base = PurchaseInvoice::query()->with('supplier')
            ->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$q}%"));
            }))
            ->when($status, fn($qq) => $qq->where('status', $status))
            ->when($supp, fn($qq) => $qq->where('supplier_id', $supp))
            ->when($pay, fn($qq) => $qq->where('payment_status', $pay))
            ->when($range, function ($qq) use ($range) {
                $range = trim($range ?? '');
                if (preg_match('~^(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})$~', $range, $m)) {
                    $qq->whereBetween('date', [$m[1], $m[2]]);
                }
            });

        // === KPI stats (pakai clone supaya filter-nya sama persis) ===
        $stats = [
            'count' => (clone $base)->count(),
            'total' => (float) (clone $base)->sum('grand_total'),
            'paid' => (float) (clone $base)->sum('paid_amount'),
        ];
        $stats['remain'] = max(0, $stats['total'] - $stats['paid']);

        // Data tabel (pagination)
        $rows = (clone $base)
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20)->appends($r->query());

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('purchasing.invoices.index', compact('q', 'status', 'supp', 'range', 'suppliers', 'rows', 'pay', 'stats'));
    }

    /** Tampilkan detail 1 invoice pembelian (lengkap dengan payment ringkas) */
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
            'payments:id,purchase_invoice_id,date,amount,method,ref_no,note',
        ]);

        // hitung subtotal per baris & total (grandTotal view-side, sedangkan kolom grand_total diset saat store)
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

        // angka ringkas untuk header pembayaran
        $paidAmount = (float) ($invoice->paid_amount ?? 0);
        $grandCol = (float) ($invoice->grand_total ?? $grandTotal); // fallback aman bila migration belum jalan
        $sisa = max(0, $grandCol - $paidAmount);

        return view('purchasing.invoices.show', [
            'invoice' => $invoice,
            'lines' => $lines,
            'grandTotal' => $grandTotal,
            'paidAmount' => $paidAmount,
            'grandColumn' => $grandCol,
            'sisa' => $sisa,
        ]);
    }

    public function editLines(PurchaseInvoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return redirect()
                ->route('purchasing.invoices.show', $invoice)
                ->with('error', 'Hanya invoice dengan status DRAFT yang bisa diedit.');
        }

        $invoice->load(['supplier', 'warehouse', 'lines.item']);

        return view('purchasing.edit', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Update qty & harga per baris invoice.
     */
    public function updateLines(Request $request, PurchaseInvoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return redirect()
                ->route('purchasing.invoices.show', $invoice)
                ->with('error', 'Invoice yang sudah diposting tidak bisa diubah.');
        }

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'next_action' => ['nullable', 'in:preview,post'],
        ]);

        $linesData = $validated['lines'];
        $nextAction = $validated['next_action'] ?? 'preview';

        DB::transaction(function () use ($invoice, $linesData) {
            $subtotal = 0;

            foreach ($linesData as $lineId => $lineInput) {
                $qty = isset($lineInput['qty']) ? (float) $lineInput['qty'] : 0;
                $price = isset($lineInput['unit_cost']) ? (float) $lineInput['unit_cost'] : 0;

                $line = $invoice->lines()->whereKey($lineId)->first();
                if (!$line) {
                    continue;
                }

                $line->qty = $qty;
                $line->unit_cost = $price;
                $line->save();

                $subtotal += $qty * $price;
            }

            $invoice->grand_total = $subtotal + (float) ($invoice->other_costs ?? 0);
            $invoice->save();
        });

        // Kalau hanya preview → balik ke halaman show
        if ($nextAction === 'preview') {
            return redirect()
                ->route('purchasing.invoices.show', $invoice)
                ->with('success', 'Detail invoice berhasil diperbarui (draft).');
        }

        // Kalau tombol "Simpan & Post" ditekan:
        // Panggil logic post yang sudah ada
        // Sesuaikan pemanggilan ini dengan method post() kamu
        return $this->post($request, $invoice);
    }

    /** Form create */
    public function create(Request $r)
    {
        // === Supplier dropdown ===
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'code']);

        // === Default filter tipe item (material|pendukung|finished) ===
        $filterType = $r->get('type', 'material');

        // === Kirim SEMUA item ke FE (biar bisa gonta-ganti filter tanpa reload) ===
        $itemsAll = Item::orderBy('name')->get(['id', 'code', 'name', 'uom', 'type']);

        // === Default gudang tujuan = KONTRAKAN (fallback: gudang pertama) ===
        $kontrakanId = DB::table('warehouses')->where('code', 'KONTRAKAN')->value('id') ?? \DB::table('warehouses')->orderBy('id')->value('id');

        // === (Opsional) nilai awal untuk DP & idempotency di FE ===
        $defaults = [
            'pay_amount' => 0,
            'pay_method' => 'cash',
            'pay_ref_no' => null,
            '_idem' => 'IDEM-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(3)),
        ];

        return view('purchasing.invoices.create', compact(
            'suppliers', 'itemsAll', 'filterType', 'kontrakanId', 'defaults'
        ));
    }

    /** Simpan pembelian + LOT + mutasi PURCHASE_IN; set grand_total/paid_amount/payment_status */
    /** Simpan pembelian sebagai DRAFT (tanpa LOT, mutasi, jurnal). */
    public function store(
        Request $r,
        InventoryService $inv,
        JournalService $journal,
        PurchasePaymentService $pps
    ) {
        // ===== Validasi =====
        $data = $r->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:255'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'string'],
            'lines.*.unit' => ['required', 'string', 'max:16'],
            'lines.*.unit_cost' => ['required', 'string'],

            'other_costs' => ['nullable', 'string'],

            // pembayaran saat create diabaikan untuk draft (boleh kirim tapi tidak dipakai)
            'pay_amount' => ['nullable', 'numeric', 'min:0'],
            'pay_method' => ['nullable', 'in:cash,bank,transfer,other'],
            'pay_ref_no' => ['nullable', 'string', 'max:64'],

            '_idem' => ['nullable', 'string', 'max:64'],
        ]);

        // ===== Normalisasi angka ID → float =====
        $norm = function ($s): float {
            $s = trim((string) $s);
            $s = str_replace("\xc2\xa0", ' ', $s);
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            if ($s === '') {
                return 0.0;
            }

            if (!preg_match('~^-?\d+(\.\d+)?$~', $s)) {
                abort(422, 'Format angka tidak valid.');
            }

            return (float) $s;
        };

        foreach ($data['lines'] as &$line) {
            $line['qty'] = max(0.0, $norm($line['qty']));
            $line['unit_cost'] = max(0.0, $norm($line['unit_cost']));
            if ($line['qty'] <= 0) {
                abort(422, 'Qty tidak boleh <= 0');
            }

            if ($line['unit_cost'] < 0) {
                abort(422, 'Harga tidak boleh < 0');
            }

        }
        unset($line);

        $otherCosts = 0.0;
        if (array_key_exists('other_costs', $data) && $data['other_costs'] !== null && $data['other_costs'] !== '') {
            $otherCosts = max(0.0, $norm($data['other_costs']));
        }

        $trxDate = \Carbon\Carbon::parse($data['date'])->toDateString();

        // ===== Generate code INV-BKU-YYMMDD-### =====
        $datePart = date('ymd', strtotime($trxDate));
        $prefix = "FPB-{$datePart}-";
        $invCode = $prefix . str_pad((string) ($this->nextSeq($prefix)), 3, '0', STR_PAD_LEFT);

        // (Opsional) Idempotensi jika kamu sudah menambah kolom idempotency_key
        if (!empty($data['_idem'])) {
            $exists = DB::table('purchase_invoices')->where('idempotency_key', $data['_idem'])->exists();
            if ($exists) {
                return redirect()->route('purchasing.invoices.index')->with('ok', "Pembelian {$invCode} sudah tercatat.");
            }
        }

        DB::transaction(function () use ($data, $invCode, $otherCosts, $trxDate) {
            /** @var \App\Models\PurchaseInvoice $invoice */
            $invoice = PurchaseInvoice::create([
                'code' => $invCode,
                'date' => now(),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'note' => $data['note'] ?? null,
                'status' => 'draft', // <— SIMPAN DRAFT
                'grand_total' => 0,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'other_costs' => $otherCosts, // jika kolom ada
                'idempotency_key' => $data['_idem'] ?? null, // jika kolom ada
            ]);

            // Simpan detail TANPA LOT/MUTASI
            $grand = 0.0;
            foreach ($data['lines'] as $line) {
                $item = \App\Models\Item::findOrFail($line['item_id']);
                $qty = (float) $line['qty'];
                $unit = (string) $line['unit'];
                $cost = (float) $line['unit_cost'];

                PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'item_code' => $item->code,
                    'qty' => $qty,
                    'unit' => $unit,
                    'unit_cost' => $cost,
                ]);

                $grand += $qty * $cost;
            }

            // Hitung grand total (ikut other_costs)
            $grand = round($grand + (float) $otherCosts, 2);

            // Draft: paid_amount tetap 0 & payment_status 'unpaid' (abaikan input pay_amount)
            $invoice->forceFill([
                'grand_total' => $grand,
                'paid_amount' => 0.0,
                'payment_status' => 'unpaid',
            ])->save();
        });

        return redirect()
            ->route('purchasing.invoices.index')
            ->with('ok', "Draft pembelian {$invCode} tersimpan.");
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
                DB::raw("(select date from purchase_invoices where purchase_invoices.id = purchase_invoice_lines.purchase_invoice_id)")
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

    public function post(Request $r, PurchaseInvoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', "Invoice {$invoice->code} sudah diposting atau dibatalkan.");
        }

        $this->performPosting($invoice);

        return redirect()
            ->route('purchasing.invoices.show', $invoice)
            ->with('ok', "Invoice {$invoice->code} berhasil diposting.");
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

    /**
     * Logic utama posting invoice (diambil dari method post() lama)
     */
    protected function performPosting(PurchaseInvoice $invoice): void
    {
        // Reload relasi minimal
        $invoice->load(['lines.item:id,code', 'payments', 'supplier:id,name', 'warehouse:id,code']);

        // Kalau bukan draft, skip saja
        if ($invoice->status !== 'draft') {
            return;
        }

        DB::transaction(function () use ($invoice) {
            $trxDate = Carbon::parse($invoice->date)->toDateString();

            // === 1) Hitung ulang GRAND TOTAL (lines + other_costs)
            $grand = 0.0;
            foreach ($invoice->lines as $ln) {
                $grand += (float) $ln->qty * (float) $ln->unit_cost;
            }
            $grand = round($grand + (float) ($invoice->other_costs ?? 0), 2);

            // === 2) Generate LOT per line + Mutasi PURCHASE_IN
            foreach ($invoice->lines as $ln) {
                $itemCode = $ln->item_code ?? $ln->item?->code; // fallback
                $lotCode = \App\Support\LotCode::nextMaterial((string) $itemCode, new DateTime($trxDate));

                $lotId = DB::table('lots')->insertGetId([
                    'item_id' => $ln->item_id,
                    'code' => $lotCode,
                    'unit' => $ln->unit,
                    'initial_qty' => $ln->qty,
                    'unit_cost' => $ln->unit_cost,
                    'date' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->inv->mutate(
                    $invoice->warehouse_id,
                    $lotId,
                    'PURCHASE_IN',
                    (float) $ln->qty,
                    0.0,
                    (string) $ln->unit,
                    $invoice->code,
                    "Pembelian {$itemCode}",
                    $trxDate . ' 00:00:00'
                );
            }

            // === 3) Voucher 1: JURNAL INVOICE (Dr Persediaan, Cr Hutang) FULL GRAND
            $this->journal->postPurchaseSplit(
                refCode: $invoice->code,
                date: $trxDate,
                inventoryAmount: $grand,
                cashPaid: 0.0, // tidak kredit kas di voucher invoice
                payableRemain: $grand, // seluruh nilai ke Hutang
                cashAccountNote: null,
                memo: $invoice->note
            );

            // === 4) Voucher 2: JURNAL PEMBAYARAN (jika SUDAH ada payments)
            if ($invoice->payments && $invoice->payments->count() > 0) {
                foreach ($invoice->payments as $p) {
                    if ((float) $p->amount <= 0) {
                        continue;
                    }

                    $this->journal->postPaymentPurchase(
                        refCode: $invoice->code . '/PAY-' . $p->id,
                        date: Carbon::parse($p->date)->toDateString(),
                        amount: (float) $p->amount,
                        method: (string) $p->method,
                        memo: $p->note
                    );
                }
            }

            // === 5) Update header: status, paid_amount, payment_status
            $invoice->forceFill([
                'grand_total' => $grand,
                'status' => 'posted',
            ])->save();

            // Recalc (hitung paid_amount & payment_status dari tabel payments)
            $this->pps->recalc($invoice->fresh('payments'));
        });
    }

}

/** Helper kecil untuk cek schema tanpa import facade */
if (!function_exists('Schema')) {
    function Schema()
    {
        return app('db.schema');
    }
}
