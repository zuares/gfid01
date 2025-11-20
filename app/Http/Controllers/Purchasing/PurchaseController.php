<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ProductionCost;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Supplier;
use App\Services\InventoryService;
use App\Services\JournalService;
use App\Services\PurchasePaymentService;
use App\Support\LotCode;
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

    /**
     * List invoice + search + filter status + filter supplier + filter payment status.
     */
    public function index(Request $r)
    {
        $base = $this->buildIndexQuery($r);

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
            ->paginate(20)
            ->appends($r->query());

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        // variabel filter untuk view
        $q = trim((string) $r->get('q', ''));
        $status = $r->get('status');
        $supp = $r->get('supplier');
        $range = $r->get('range');
        $pay = $r->get('payment');

        return view('purchasing.invoices.index', compact(
            'q',
            'status',
            'supp',
            'range',
            'suppliers',
            'rows',
            'pay',
            'stats'
        ));
    }

    /**
     * Detail 1 invoice pembelian (lengkap dengan ringkasan pembayaran).
     */
    public function show(PurchaseInvoice $invoice)
    {
        // eager load relasi supaya hemat query
        $invoice->load([
            'supplier:id,code,name,phone',
            'warehouse:id,code,name',
            'lines' => function ($q) {
                $q->select('id', 'purchase_invoice_id', 'item_id', 'item_code', 'qty', 'unit', 'unit_cost')
                    ->with(['item:id,code,name,unit,type'])
                    ->orderBy('id');
            },
            'payments:id,purchase_invoice_id,date,amount,method,ref_no,note',
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

    /**
     * Form edit lines (qty & harga) hanya untuk DRAFT.
     */
    public function editLines(PurchaseInvoice $invoice)
    {
        if (!$this->isDraft($invoice)) {
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
        if (!$this->isDraft($invoice)) {
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
            }

            // hitung ulang grand_total header (lines + other_costs)
            $invoice->refresh(); // pastikan lines terbaru
            $invoice->grand_total = $this->calculateGrandTotal($invoice);
            $invoice->save();
        });

        // Kalau hanya preview → balik ke halaman show
        if ($nextAction === 'preview') {
            return redirect()
                ->route('purchasing.invoices.show', $invoice)
                ->with('success', 'Detail invoice berhasil diperbarui (draft).');
        }

        // Kalau tombol "Simpan & Post" ditekan:
        return $this->post($request, $invoice);
    }

    /**
     * Form create invoice pembelian.
     */
    public function create(Request $request)
    {
        // === Supplier dropdown ===
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'code']);

        // === Default filter tipe item (material|pendukung|finished) ===
        $filterType = $request->get('type', 'material');

        // === Kirim SEMUA item ke FE (biar bisa gonta-ganti filter tanpa reload) ===
        // gunakan kolom 'unit' (bukan uom)
        $itemsAll = Item::orderBy('name')->get(['id', 'code', 'name', 'unit', 'type']);

        // === Default gudang tujuan = KONTRAKAN (fallback: gudang pertama) ===
        $kontrakanId = DB::table('warehouses')
            ->where('code', 'KONTRAKAN')
            ->value('id') ?? DB::table('warehouses')->orderBy('id')->value('id');

        // === (Opsional) nilai awal untuk DP & idempotency di FE ===
        $defaults = [
            'pay_amount' => 0,
            'pay_method' => 'cash',
            'pay_ref_no' => null,
            '_idem' => 'IDEM-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(3)),
        ];

        return view('purchasing.invoices.create', compact(
            'suppliers',
            'itemsAll',
            'filterType',
            'kontrakanId',
            'defaults',
        ));
    }

    /**
     * Simpan pembelian sebagai DRAFT (tanpa LOT, mutasi, jurnal).
     */
    public function store(Request $r)
    {
        // ===== Validasi =====
        $data = $r->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:255'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required'], // bisa numeric/string (hidden dari JS)
            'lines.*.unit' => ['required', 'string', 'max:16'],
            'lines.*.unit_cost' => ['required'], // bisa numeric/string (hidden dari JS)

            'other_costs' => ['nullable'], // bisa numeric/string

            // pembayaran saat create diabaikan untuk draft
            'pay_amount' => ['nullable', 'numeric', 'min:0'],
            'pay_method' => ['nullable', 'in:cash,bank,transfer,other'],
            'pay_ref_no' => ['nullable', 'string', 'max:64'],

            '_idem' => ['nullable', 'string', 'max:64'],
        ]);

        // ===== Normalisasi angka → float (mendukung string / numeric) =====
        foreach ($data['lines'] as &$line) {
            $line['qty'] = round(max(0.0, $this->normalizeNumber($line['qty'])), 2);
            $line['unit_cost'] = round(max(0.0, $this->normalizeNumber($line['unit_cost'])), 2);

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
            $otherCosts = round(max(0.0, $this->normalizeNumber($data['other_costs'])), 2);
        }

        $trxDate = \Carbon\Carbon::parse($data['date'])->toDateString();

        // ===== Generate code FPB-YYMMDD-### =====
        $datePart = date('ymd', strtotime($trxDate));
        $prefix = "FPB-{$datePart}-";
        $invCode = $prefix . str_pad((string) $this->nextSeq($prefix), 3, '0', STR_PAD_LEFT);

        // (Opsional) Idempotensi jika kamu sudah menambah kolom idempotency_key
        if (!empty($data['_idem'])) {
            $exists = DB::table('purchase_invoices')
                ->where('idempotency_key', $data['_idem'])
                ->exists();

            if ($exists) {
                return redirect()
                    ->route('purchasing.invoices.index')
                    ->with('success', "Pembelian {$invCode} sudah tercatat.");
            }
        }

        DB::transaction(function () use ($data, $invCode, $otherCosts, $trxDate) {
            /** @var \App\Models\PurchaseInvoice $invoice */
            $invoice = PurchaseInvoice::create([
                'code' => $invCode,
                'date' => $trxDate, // simpan sesuai tanggal transaksi
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'note' => $data['note'] ?? null,
                'status' => 'draft',
                'grand_total' => 0,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'other_costs' => $otherCosts,
                'idempotency_key' => $data['_idem'] ?? null,
            ]);

            // Simpan detail TANPA LOT/MUTASI
            $grand = 0.0;

            foreach ($data['lines'] as $line) {
                $item = Item::findOrFail($line['item_id']);

                $qty = (float) $line['qty']; // sudah dinormalisasi
                $unit = (string) $line['unit'];
                $cost = (float) $line['unit_cost']; // sudah dinormalisasi

                PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'item_code' => $item->code,
                    'qty' => $qty,
                    'unit' => $unit,
                    'unit_cost' => $cost,
                ]);

                $grand += round($qty * $cost, 2);
            }

            // Hitung grand total (ikut other_costs)
            $grand = round($grand + (float) $otherCosts, 2);

            // Draft: paid_amount tetap 0 & payment_status 'unpaid'
            $invoice->forceFill([
                'grand_total' => $grand,
                'paid_amount' => 0.0,
                'payment_status' => 'unpaid',
            ])->save();
        });

        return redirect()
            ->route('purchasing.invoices.index')
            ->with('success', "Draft pembelian {$invCode} tersimpan.");
    }

    /**
     * AJAX: harga terakhir per supplier+item.
     */
    public function lastPrice(Request $r)
    {
        $supplierId = (int) $r->get('supplier_id');
        $itemId = (int) $r->get('item_id');

        if (!$supplierId || !$itemId) {
            return response()->json([
                'success' => false,
                'msg' => 'supplier_id dan item_id wajib diisi',
            ], 422);
        }

        $last = PurchaseInvoiceLine::with(['invoice:id,date,supplier_id,code'])
            ->lastPrice($supplierId, $itemId)
            ->first();

        if (!$last) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'unit_cost' => (float) $last->unit_cost,
                'unit' => $last->unit,
                'date' => optional($last->invoice->date)->format('Y-m-d'),
                'inv_code' => $last->invoice->code,
            ],
        ]);
    }

    /**
     * AJAX: riwayat pembelian ringkas per supplier+item (n terakhir).
     */
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

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Posting invoice: buat LOT, mutasi stok, dan jurnal.
     */
    public function post(Request $r, PurchaseInvoice $invoice)
    {
        if (!$this->isDraft($invoice)) {
            return back()->with('error', "Invoice {$invoice->code} sudah diposting atau dibatalkan.");
        }

        $this->performPosting($invoice);

        return redirect()
            ->route('purchasing.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->code} berhasil diposting.");
    }

    /**
     * Hitung next sequence untuk FPB-YYMMDD-###.
     */
    protected function nextSeq(string $prefix): int
    {
        $last = DB::table('purchase_invoices')
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code') // karena zero-pad, sorting string aman
            ->value('code');

        if (!$last) {
            return 1;
        }

        $suffix = (int) preg_replace('~^' . preg_quote($prefix, '~') . '~', '', $last);

        return $suffix + 1;
    }

    /**
     * Logic utama posting invoice (create LOT, mutasi, jurnal).
     */
    // protected function performPosting(PurchaseInvoice $invoice): void
    // {
    //     // Reload relasi minimal
    //     $invoice->load(['lines.item:id,code', 'payments', 'supplier:id,name', 'warehouse:id,code']);
    //     // Kalau bukan draft, skip saja
    //     if (!$this->isDraft($invoice)) {
    //         return;
    //     }

    //     DB::transaction(function () use ($invoice) {
    //         $trxDate = Carbon::parse($invoice->date)->toDateString();
    //         dd($invoice);
    //         // === 1) Hitung ulang GRAND TOTAL (lines + other_costs)
    //         $grand = $this->calculateGrandTotal($invoice);

    //         // === 2) Generate LOT per line + Mutasi PURCHASE_IN
    //         foreach ($invoice->lines as $ln) {
    //             $itemCode = $ln->item_code ?? $ln->item?->code;
    //             $lotCode = \App\Support\LotCode::nextMaterial((string) $itemCode, new DateTime($trxDate));

    //             $lotId = DB::table('lots')->insertGetId([
    //                 'item_id' => $ln->item_id,
    //                 'code' => $lotCode,
    //                 'unit' => $ln->unit,
    //                 'initial_qty' => $ln->qty,
    //                 'unit_cost' => $ln->unit_cost,
    //                 'date' => now(),
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ]);
    //             $this->inv->mutate(
    //                 $invoice->warehouse_id,
    //                 $lotId,
    //                 'PURCHASE_IN',
    //                 (float) $ln->qty,
    //                 0.0,
    //                 (string) $ln->unit,
    //                 $invoice->code,
    //                 "Pembelian {$itemCode}",
    //                 $trxDate . ' 00:00:00'
    //             );
    //         }

    //         // // === 3) Voucher 1: JURNAL INVOICE (Dr Persediaan, Cr Hutang) FULL GRAND
    //         // $this->journal->postPurchaseSplit(
    //         //     refCode: $invoice->code,
    //         //     date: $trxDate,
    //         //     inventoryAmount: $grand,
    //         //     cashPaid: 0.0, // tidak kredit kas di voucher invoice
    //         //     payableRemain: $grand, // seluruh nilai ke Hutang
    //         //     cashAccountNote: null,
    //         //     memo: $invoice->note
    //         // );

    //         // // === 4) Voucher 2: JURNAL PEMBAYARAN (jika sudah ada payments)
    //         // if ($invoice->payments && $invoice->payments->count() > 0) {
    //         //     foreach ($invoice->payments as $p) {
    //         //         if ((float) $p->amount <= 0) {
    //         //             continue;
    //         //         }

    //         //         $this->journal->postPaymentPurchase(
    //         //             refCode: $invoice->code . '/PAY-' . $p->id,
    //         //             date: Carbon::parse($p->date)->toDateString(),
    //         //             amount: (float) $p->amount,
    //         //             method: (string) $p->method,
    //         //             memo: $p->note
    //         //         );
    //         //     }
    //         // }

    //         // === 5) Update header: status, paid_amount, payment_status
    //         $invoice->forceFill([
    //             'grand_total' => $grand,
    //             'status' => 'posted',
    //         ])->save();

    //         // === CATAT BIAYA RAW MATERIAL KE production_costs ===

    //         // pastikan relasi lines sudah di-load atau panggil $invoice->load('lines');
    //         $invoice->loadMissing('lines');
    //         foreach ($invoice->lines as $line) {
    //             // 🚨 SESUAIKAN NAMA FIELD DENGAN PUNYAMU
    //             $itemId = $line->item_id; // item kain
    //             $qty = (float) $line->qty; // qty beli (biasanya kg)
    //             $unitCost = (float) $line->unit_cost; // harga per kg
    //             $amount = $qty * $unitCost; // total biaya baris ini
    //             // dd($line);
    //             if ($qty <= 0 || $amount <= 0) {
    //                 continue;
    //             }
    //             ProductionCost::create([
    //                 'lot_id' => $lotId ?? null, // kalau line punya lot_id, isi; kalau belum, boleh null dulu
    //                 'item_id' => $itemId,

    //                 'stage' => 'raw_material',
    //                 'qty_base' => $qty, // nanti HPP per kg = amount / qty_base
    //                 'amount' => $amount,
    //                 'cost_per_unit' => $amount / max(1, $qty),

    //                 'source_type' => 'purchase_invoice',
    //                 'source_id' => $invoice->id,
    //                 'notes' => 'Biaya kain dari purchase ' . ($invoice->code ?? $invoice->id),
    //             ]);

    //         }

    //         // Recalc (hitung paid_amount & payment_status dari tabel payments)
    //         $this->pps->recalc($invoice->fresh('payments'));
    //     });
    // }

// ...
    protected function performPosting(PurchaseInvoice $invoice): void
    {
        // Reload relasi minimal yang dibutuhkan
        $invoice->load([
            'lines.item:id,code',
            'payments',
            'supplier:id,name',
            'warehouse:id,code',
        ]);

        // Kalau bukan draft, skip saja
        if (!$this->isDraft($invoice)) {
            return;
        }

        DB::transaction(function () use ($invoice) {
            $trxDate = Carbon::parse($invoice->date)->toDateString();

            // === 1) Hitung ulang GRAND TOTAL (lines + other_costs kalau ada) ===
            $grand = $this->calculateGrandTotal($invoice);

            // === 2) Generate LOT per line + Mutasi PURCHASE_IN + catat biaya RAW MATERIAL ===
            foreach ($invoice->lines as $ln) {
                $itemCode = $ln->item_code ?? $ln->item?->code;

                // Kode LOT kain
                $lotCode = LotCode::nextMaterial(
                    (string) $itemCode,
                    new DateTime($trxDate)
                );

                // Insert LOT
                $lotId = DB::table('lots')->insertGetId([
                    'item_id' => $ln->item_id,
                    'code' => $lotCode,
                    'unit' => $ln->unit,
                    'initial_qty' => $ln->qty,
                    'unit_cost' => $ln->unit_cost,
                    'date' => $trxDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Mutasi stok PURCHASE_IN per LOT
                $this->inv->mutate(
                    warehouseId: $invoice->warehouse_id,
                    lotId: $lotId,
                    type: 'PURCHASE_IN',
                    qtyIn: (float) $ln->qty,
                    qtyOut: 0.0,
                    unit: (string) $ln->unit,
                    refCode: $invoice->code,
                    note: "Pembelian {$itemCode}",
                    date: $trxDate . ' 00:00:00',
                    category: 'raw_material'
                );

                // === 2b) CATAT BIAYA RAW MATERIAL KE production_costs ===
                $qty = (float) $ln->qty; // biasanya kg
                $unitCost = (float) $ln->unit_cost; // harga per kg
                $amount = $qty * $unitCost; // total biaya baris ini

                if ($qty > 0 && $amount > 0) {
                    ProductionCost::create([
                        'lot_id' => $lotId, // nempel ke LOT
                        'item_id' => $ln->item_id, // optional, boleh null kalau mau
                        'stage' => 'raw_material', // ⬅️ penting
                        'qty_base' => $qty, // base = qty beli (kg)
                        'amount' => $amount,
                        'cost_per_unit' => $unitCost, // Rp per kg (sementara)
                        'source_type' => 'purchase_invoice',
                        'source_id' => $invoice->id,
                        'notes' => 'Biaya kain dari purchase ' . ($invoice->code ?? $invoice->id),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // === 3) Update header invoice: status & grand_total ===
            $invoice->forceFill([
                'grand_total' => $grand,
                'status' => 'posted',
            ])->save();

            // === 4) Recalc pembayaran (paid_amount & payment_status) ===
            $this->pps->recalc($invoice->fresh('payments'));
        });
    }

    // ======================
    // Helper private methods
    // ======================

    /**
     * Query dasar untuk index() dengan semua filter.
     */
    protected function buildIndexQuery(Request $r)
    {
        $q = trim((string) $r->get('q', ''));
        $status = $r->get('status'); // draft|posted
        $supp = $r->get('supplier'); // supplier_id
        $range = $r->get('range'); // "YYYY-MM-DD s/d YYYY-MM-DD"
        $pay = $r->get('payment'); // unpaid|partial|paid

        $base = PurchaseInvoice::query()->with('supplier')
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                        ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($status, fn($qq) => $qq->where('status', $status))
            ->when($supp, fn($qq) => $qq->where('supplier_id', $supp))
            ->when($pay, fn($qq) => $qq->where('payment_status', $pay));

        if ($parsed = $this->parseDateRange($range)) {
            [$from, $to] = $parsed;
            $base->whereBetween('date', [$from, $to]);
        }

        return $base;
    }

    /**
     * Parse string "YYYY-MM-DD s/d YYYY-MM-DD" → [from, to] atau null.
     */
    protected function parseDateRange(?string $range): ?array
    {
        $range = trim((string) $range);
        if ($range === '') {
            return null;
        }

        if (!preg_match('~^(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})$~', $range, $m)) {
            return null;
        }

        return [$m[1], $m[2]];
    }

    /**
     * Normalisasi angka format Indonesia ke float.
     */
    protected function normalizeNumber($value): float
    {
        // Kalau sudah numeric (hasil hidden dari JS), langsung pakai
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (float) $value;
        }

        // Kalau masih format Indonesia (1.234,56)
        $s = trim((string) $value);
        $s = str_replace("\xc2\xa0", ' ', $s); // non-breaking space
        $s = str_replace('.', '', $s); // hapus pemisah ribuan
        $s = str_replace(',', '.', $s); // ubah koma desimal ke titik

        if ($s === '') {
            return 0.0;
        }

        if (!preg_match('~^-?\d+(\.\d+)?$~', $s)) {
            abort(422, 'Format angka tidak valid.');
        }

        return (float) $s;
    }

    /**
     * Hitung grand total dari lines + other_costs.
     */
    protected function calculateGrandTotal(PurchaseInvoice $invoice): float
    {
        $totalLines = 0.0;

        foreach ($invoice->lines as $ln) {
            $totalLines += (float) $ln->qty * (float) $ln->unit_cost;
        }

        return round($totalLines + (float) ($invoice->other_costs ?? 0), 2);
    }

    /**
     * Cek apakah invoice masih DRAFT.
     */
    protected function isDraft(PurchaseInvoice $invoice): bool
    {
        return $invoice->status === 'draft';
    }
}
