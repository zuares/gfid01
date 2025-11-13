<?php

// app/Http/Controllers/Production/ExternalTransferController.php
namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ExternalTransfer;
use App\Services\ExternalTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExternalTransferController extends Controller
{
    public function __construct(protected ExternalTransferService $svc)
    {}

    public function index()
    {
        $rows = ExternalTransfer::withCount('lines')
            ->orderByDesc('id')
            ->paginate(30);

        return view('production.external.index', compact('rows'));
    }

    public function create(Request $request)
    {
        // Semua gudang
        $warehouses = DB::table('warehouses')
            ->select('id', 'code', 'name')
            ->orderBy('code')
            ->get();

        // Tentukan gudang asal yang dipakai:
        // 1. dari query string ?from_warehouse_id=...
        // 2. kalau kosong → coba cari KONTRAKAN
        // 3. kalau masih kosong → pakai gudang pertama
        $fromWarehouseId = $request->integer('from_warehouse_id');

        if (!$fromWarehouseId) {
            $kontrakan = $warehouses->firstWhere('code', 'KONTRAKAN');
            $fromWarehouseId = $kontrakan->id ?? ($warehouses->first()->id ?? null);
        }

        // LOT aktif per gudang asal
        if (Schema::hasTable('inventory_stocks')) {
            // Versi pakai inventory_stocks (lebih akurat per gudang)
            $lots = DB::table('inventory_stocks')
                ->join('lots', 'lots.id', '=', 'inventory_stocks.lot_id')
                ->join('items', 'items.id', '=', 'lots.item_id')
                ->select(
                    'lots.id',
                    'lots.code as lot_code',
                    'items.id as item_id',
                    'items.code as item_code',
                    'items.name as item_name',
                    'inventory_stocks.qty as initial_qty',
                    'lots.unit as uom'
                )
                ->where('inventory_stocks.warehouse_id', $fromWarehouseId)
                ->where('inventory_stocks.qty', '>', 0)
                ->orderByDesc('lots.updated_at')
                ->limit(300)
                ->get();
        } else {
            // Fallback kalau belum pakai inventory_stocks
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
        }

        // Operator (boleh filter role cutting kalau mau)
        $employees = collect();
        if (Schema::hasTable('employees')) {
            $employees = DB::table('employees')
                ->select('code', 'name', 'role', 'active')
                ->where('active', 1)
                ->orderBy('code')
                ->get();
        }

        return view('production.external.create', [
            'lots' => $lots,
            'warehouses' => $warehouses,
            'employees' => $employees,
            'fromWarehouseId' => $fromWarehouseId,
        ]);
    }

    /**
     * Simpan External Transfer (Makloon)
     * - Format gudang eksternal: PROSES-EXT-EMPCODE (CUT-EXT-MRF / SEW-EXT-MRF)
     * - Jika gudang belum ada → auto create
     * - Status awal: draft (nanti kirim stok di action send())
     */
    public function store(Request $request)
    {
        // dd($request->input());
        $validated = $request->validate([
            'process' => 'required|in:cutting,sewing',
            'operator_code' => 'required|string',
            'date' => 'nullable|date', // input tetap boleh, tapi kita override
            'from_warehouse_id' => 'required|integer',
            'to_warehouse_code' => 'required|string', // diisi otomatis dari view
            'note' => 'nullable|string',
            'material_value_est' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $process = $validated['process']; // cutting / sewing
            $operatorCode = $validated['operator_code']; // MRF, BBI, dll
            $now = now(); // waktu submit
            $fromWhId = (int) $validated['from_warehouse_id'];
            $toWhCode = $validated['to_warehouse_code']; // CUT-EXT-MRF / SEW-EXT-MRF
            $note = $validated['note'] ?? null;
            $estValue = $validated['material_value_est'] ?? 0;

            // 1️⃣ Pastikan gudang tujuan ada (kalau belum, buat)
            $warehouse = DB::table('warehouses')
                ->where('code', $toWhCode)
                ->first();

            if (!$warehouse) {
                $processName = $process === 'sewing' ? 'Makloon Sewing' : 'Makloon Cutting';

                $toWhId = DB::table('warehouses')->insertGetId([
                    'code' => $toWhCode, // contoh: CUT-EXT-MRF
                    'name' => "{$processName} {$operatorCode}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $toWhId = $warehouse->id;
            }

            // 2️⃣ Generate kode dokumen external transfer
            //    - proses cutting → CUT-EXT-YYMMDD-MRF-001
            //    - proses sewing  → SEW-EXT-YYMMDD-MRF-001
            $prefix = $process === 'sewing' ? 'SEW-EXT' : 'CUT-EXT';

            $countToday = DB::table('external_transfers')
                ->whereDate('date', $now->toDateString())
                ->where('operator_code', $operatorCode)
                ->where('code', 'like', $prefix . '%')
                ->count();

            $sequence = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . $now->format('ymd') . '-' . $operatorCode . '-' . $sequence;

            // 3️⃣ Insert header external_transfer (pakai Eloquent biar enak)
            /** @var \App\Models\ExternalTransfer $ext */
            $ext = ExternalTransfer::create([
                'code' => $code,
                'process' => $process,
                'operator_code' => $operatorCode,
                'from_warehouse_id' => $fromWhId,
                'to_warehouse_id' => $toWhId,
                'date' => $now->toDateString(), // pakai waktu submit
                'status' => 'draft', // awalnya DRAFT
                'material_value_est' => $estValue,
                'note' => $note,
            ]);

            // 4️⃣ Insert detail LOT yang dipilih
            $lines = $request->input('lines', []);

            foreach ($lines as $line) {
                $qty = isset($line['qty']) ? (float) $line['qty'] : 0;
                if ($qty <= 0) {
                    continue;
                }

                $ext->lines()->create([
                    'lot_id' => $line['lot_id'],
                    'item_id' => $line['item_id'],
                    'qty' => $qty,
                    'unit' => $line['unit'],
                    'note' => $line['note'] ?? null,
                ]);
            }

            // ❌ Tidak mutasi stok di sini.
            // ✅ Mutasi stok dilakukan saat action "send()" di ExternalTransferService.
        });

        return redirect()
            ->route('production.external.index')
            ->with('success', 'External transfer makloon berhasil dibuat (DRAFT).');
    }

    public function send($id)
    {
        $t = ExternalTransfer::with('lines')->findOrFail($id);
        $this->svc->send($t);
        return back()->with('ok', "Transfer {$t->code} dikirim (status SENT).");
    }

    public function receiveForm($id)
    {
        $t = ExternalTransfer::with('lines')->findOrFail($id);
        return view('production.external.receive', compact('t'));
    }

    public function receiveStore(Request $r, $id)
    {
        $t = ExternalTransfer::with('lines')->findOrFail($id);

        $r->validate([
            'date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.transfer_line_id' => 'required|integer',
            'lines.*.received_qty' => 'nullable|numeric|min:0',
            'lines.*.defect_qty' => 'nullable|numeric|min:0',
        ]);

        $this->svc->receive($t, [
            'date' => $r->date,
            'note' => $r->note,
            'lines' => $r->lines,
        ]);

        return redirect()
            ->route('production.external.index')
            ->with('ok', "Penerimaan external untuk {$t->code} diposting.");
    }

    public function post($id)
    {
        $t = ExternalTransfer::findOrFail($id);
        $this->svc->post($t, $memo = request('memo'));

        return back()->with('ok', "Transfer {$t->code} POSTED (jurnal).");
    }
}
