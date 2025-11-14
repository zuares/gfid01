<?php
namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ExternalTransfer;
use App\Models\ExternalTransferLine;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Services\ExternalTransferService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExternalTransferController extends Controller
{
    public function __construct(
        protected ExternalTransferService $svc, InventoryService $inv
    ) {}
    /**
     * List semua dokumen external transfer.
     * Bisa difilter berdasarkan proses (cutting/sewing/finishing) dan status.
     */
    public function index(Request $request)
    {
        $process = $request->get('process'); // optional
        $status = $request->get('status'); // optional

        $rows = ExternalTransfer::withCount('lines')
            ->when($process, fn($q) => $q->where('process', $process))
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(30)
            ->appends($request->only('process', 'status'));

        return view('production.external_transfers.index', compact('rows', 'process', 'status'));
    }

    /**
     * Form buat dokumen external transfer baru.
     * Contoh: kirim kain (LOT) ke vendor cutting.
     */
    public function create(Request $request)
    {

        // ====== 1) Ambil semua gudang ======
        $warehouses = Warehouse::orderBy('code')->get();

        // Default gudang "KONTRAKAN"
        $defaultFrom = $warehouses->firstWhere('code', 'KONTRAKAN');

        // from_warehouse_id prioritas:
        //   - old() dari validasi
        //   - query string ?from_warehouse_id=...
        //   - default KONTRAKAN
        $fromWarehouseId = old(
            'from_warehouse_id',
            $request->get('from_warehouse_id', $defaultFrom->id ?? null)
        );

        // ====== 2) Ambil data karyawan (operator) ======
        $employees = Employee::orderBy('code')
            ->get(['id', 'code', 'name', 'role']);

        // Nilai proses & operator dari old / query
        $process = old('process', $request->get('process', 'cutting'));
        $operatorCode = old('operator_code', $request->get('operator_code'));

        // Mapping 3 huruf proses
        $procMap = [
            'cutting' => 'CUT',
            'sewing' => 'SEW',
            'finishing' => 'FIN',
            'other' => 'OTH',
        ];
        $proc3 = $procMap[$process] ?? strtoupper(substr($process, 0, 3));

        // Ambil 3 huruf pertama dari kode operator
        $emp3 = $operatorCode
        ? strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $operatorCode), 0, 3))
        : null;

        // Bentuk kode otomatis gudang tujuan: EXT-CUT-MRF
        $autoToWarehouseCode = $emp3 ? "EXT-{$proc3}-{$emp3}" : null;

        // Cari gudang dengan code = autoToWarehouseCode
        $autoToWarehouseId = null;
        if ($autoToWarehouseCode) {
            $autoToWarehouseId = optional(
                $warehouses->firstWhere('code', $autoToWarehouseCode)
            )->id;
        }

        // ====== 3) Ambil LOT berdasarkan gudang asal (inventory_stocks) ======
        $lots = Lot::query()
            ->join('items', 'items.id', '=', 'lots.item_id')
            ->join('inventory_stocks as s', 's.lot_id', '=', 'lots.id')
            ->when($fromWarehouseId, function ($q) use ($fromWarehouseId) {
                $q->where('s.warehouse_id', $fromWarehouseId);
            })
            ->orderByDesc('lots.updated_at')
            ->limit(500)
            ->get([
                'lots.id',
                'lots.code as lot_code',
                'items.id as item_id',
                'items.code as item_code',
                'items.name as item_name',
                'lots.unit as uom',
                's.qty as stock_remain',
                's.warehouse_id',
            ]);
        return view('production.external_transfers.create', [
            'warehouses' => $warehouses,
            'lots' => $lots,
            'employees' => $employees,
            'defaultProcess' => $process,
            'defaultFromWarehouseId' => $fromWarehouseId,
            'autoToWarehouseId' => $autoToWarehouseId,
            'autoToWarehouseCode' => $autoToWarehouseCode,
        ]);
    }
    /**
     * Simpan dokumen external transfer baru (status = draft).
     */
    public function store(Request $request)
    {
        /**
         * ========== AUTOCREATE to_warehouse (CUT-EXT-OPERATOR) ==========
         * Contoh: operator_code = MRF  →  warehouse code = CUT-EXT-MRF
         * Dijalankan sebelum validate.
         */
        if ($request->process === 'cutting' && $request->operator_code) {

            // bentukan kode gudang tujuan
            $whCode = 'CUT-EXT-' . strtoupper($request->operator_code);

            // data default gudang
            $warehouseData = [
                'code' => $whCode,
                'name' => 'Cutting External ' . strtoupper($request->operator_code),
            ];

            // kalau di tabel warehouses ada kolom "type", isi 'external'
            if (Schema::hasColumn('warehouses', 'type')) {
                $warehouseData['type'] = 'external';
            }

            // kalau ada kolom "is_active", set true
            if (Schema::hasColumn('warehouses', 'is_active')) {
                $warehouseData['is_active'] = true;
            }

            // cari atau buat gudang
            $toWarehouse = Warehouse::firstOrCreate(
                ['code' => $whCode],
                $warehouseData
            );

            // paksa request pakai gudang ini sebagai tujuan
            $request->merge([
                'to_warehouse_id' => $toWarehouse->id,
            ]);
        }

        // ===== VALIDASI SETELAH MERGE =====
        $request->validate([
            'date' => ['required', 'date'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'process' => ['required', 'in:cutting,sewing,finishing,other'],
            'operator_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.uom' => ['required', 'string', 'max:10'],
            'lines.*.notes' => ['nullable', 'string'],
        ], [
            'lines.required' => 'Minimal satu baris LOT harus diisi.',
        ]);

        DB::transaction(function () use ($request) {
            $date = Carbon::parse($request->date);

            // Generate kode dokumen
            $code = $this->generateCode($request->process, $date);

            /** @var ExternalTransfer $header */
            $header = ExternalTransfer::create([
                'code' => $code,
                'date' => $date->toDateString(),
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'operator_code' => $request->operator_code,
                'process' => $request->process,
                'status' => 'draft',
                'notes' => $request->notes,
            ]);

            foreach ($request->lines as $line) {
                ExternalTransferLine::create([
                    'external_transfer_id' => $header->id,
                    'lot_id' => $line['lot_id'],
                    'item_id' => $line['item_id'],
                    'qty' => $line['qty'],
                    'uom' => $line['uom'],
                    'notes' => $line['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('external-transfers.index')
            ->with('success', 'Dokumen external transfer berhasil dibuat (status: draft).');
    }

    /**
     * Tampilkan detail satu dokumen external transfer.
     */
    public function show(ExternalTransfer $externalTransfer)
    {
        $externalTransfer->load(['fromWarehouse', 'toWarehouse', 'lines.lot', 'lines.item']);

        return view('production.external_transfers.show', [
            'row' => $externalTransfer,
        ]);
    }

    /**
     * Form edit dokumen (hanya jika masih draft).
     */
    public function edit(ExternalTransfer $externalTransfer)
    {
        if ($externalTransfer->status !== 'draft') {
            return redirect()
                ->route('external-transfers.show', $externalTransfer)
                ->with('error', 'Dokumen yang sudah dikirim tidak dapat diedit.');
        }

        $warehouses = Warehouse::orderBy('code')->get();

        $lots = DB::table('lots')
            ->join('items', 'items.id', '=', 'lots.item_id')
            ->select(
                'lots.id',
                'lots.code as lot_code',
                'items.id as item_id',
                'items.code as item_code',
                'items.name as item_name',
                'lots.initial_qty',
                DB::raw('COALESCE(lots.stock_remain, lots.initial_qty) as stock_remain'),
                'lots.unit as uom'
            )
            ->orderByDesc('lots.updated_at')
            ->limit(300)
            ->get();

        $externalTransfer->load('lines.lot', 'lines.item');

        return view('production.external.edit', [
            'row' => $externalTransfer,
            'warehouses' => $warehouses,
            'lots' => $lots,
        ]);
    }

    /**
     * Update dokumen external transfer (draft).
     */
    public function update(Request $request, ExternalTransfer $externalTransfer)
    {
        if ($externalTransfer->status !== 'draft') {
            return redirect()
                ->route('external-transfers.show', $externalTransfer)
                ->with('erroror', 'Dokumen yang sudah dikirim tidak dapat diubah.');
        }

        $request->validate([
            'date' => ['required', 'date'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'process' => ['required', 'in:cutting,sewing,finishing,other'],
            'operator_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required|numeric|min:0.01'],
            'lines.*.uom' => ['required', 'string', 'max:10'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $externalTransfer) {
            $date = Carbon::parse($request->date);

            $externalTransfer->update([
                'date' => $date->toDateString(),
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'operator_code' => $request->operator_code,
                'process' => $request->process,
                'notes' => $request->notes,
            ]);

            // hapus semua line lama, insert ulang (simple)
            $externalTransfer->lines()->delete();

            foreach ($request->lines as $line) {
                ExternalTransferLine::create([
                    'external_transfer_id' => $externalTransfer->id,
                    'lot_id' => $line['lot_id'],
                    'item_id' => $line['item_id'],
                    'qty' => $line['qty'],
                    'uom' => $line['uom'],
                    'notes' => $line['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('external-transfers.show', $externalTransfer)
            ->with('success', 'Dokumen external transfer berhasil diperbarui.');
    }

    /**
     * Ubah status dokumen dari draft → sent.
     * Biasanya dipakai saat barang benar-benar keluar dari gudang.
     */
    public function send(int $id, InventoryService $inv)
    {
        // Ambil dokumen + lines secara manual
        $t = ExternalTransfer::with('lines')->findOrFail($id);

        if ($t->status !== 'draft') {
            return back()->with('error', 'Hanya dokumen draft yang bisa dikirim.');
        }
        if ($t->lines->isEmpty()) {
            return back()->with('error', "Dokumen {$t->code} tidak punya detail LOT.");
        }

        try {
            $date = $t->date?->toDateString() ?? now()->toDateString();
            $ref = $t->code;
            $note = "ExternalTransfer {$t->code}";

            DB::transaction(function () use ($t, $inv, $date, $ref, $note) {
                foreach ($t->lines as $ln) {
                    $inv->transfer(
                        fromWarehouseId: $t->from_warehouse_id,
                        toWarehouseId: $t->to_warehouse_id,
                        lotId: $ln->lot_id,
                        qty: (float) $ln->qty,
                        unit: $ln->uom,
                        refCode: $ref,
                        note: $note,
                        date: $date,
                    );
                }

                $t->update([
                    'status' => 'sent',
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim dokumen: ' . $e->getMessage());
        }

        return back()->with('success', "Dokumen {$t->code} berhasil dikirim dan stok berpindah gudang.");
    }

    /**
     * Konfirmasi bahwa barang sudah diterima di tujuan (sent → received).
     */
    public function receive(ExternalTransfer $externalTransfer)
    {
        if ($externalTransfer->status !== 'sent') {
            return back()->with('error', 'Hanya dokumen dengan status sent yang bisa diterima.');
        }

        $externalTransfer->update([
            'status' => 'received',
        ]);

        // Di sini nanti bisa sambungkan ke InventoryService / mutasi stok

        return back()->with('success', "Dokumen {$externalTransfer->code} telah dikonfirmasi diterima (status: received).");
    }

    /**
     * Tandai dokumen sebagai selesai / done.
     * Misalnya setelah seluruh cycle proses bahan dari dokumen ini selesai.
     */
    public function done(ExternalTransfer $externalTransfer)
    {
        if (!in_array($externalTransfer->status, ['received', 'sent'])) {
            return back()->with('error', 'Hanya dokumen sent/received yang bisa ditandai selesai.');
        }

        $externalTransfer->update([
            'status' => 'done',
        ]);

        return back()->with('success', "Dokumen {$externalTransfer->code} telah ditandai selesai (status: done).");
    }

    /**
     * Hapus / batalkan dokumen.
     * Untuk keamanan, batasi hanya ketika status = draft.
     */
    public function destroy(ExternalTransfer $externalTransfer)
    {
        if ($externalTransfer->status !== 'draft') {
            return back()->with('error', 'Hanya dokumen draft yang dapat dihapus.');
        }

        $code = $externalTransfer->code;

        DB::transaction(function () use ($externalTransfer) {
            $externalTransfer->lines()->delete();
            $externalTransfer->delete();
        });

        return redirect()
            ->route('external-transfers.index')
            ->with('success', "Dokumen {$code} berhasil dihapus.");
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER: Generate Kode Dokumen
    |--------------------------------------------------------------------------
     */

    /**
     * Generate kode dokumen:
     * - prefix tergantung process, contoh:
     *   - cutting  → EXT-CUT-YYMMDD-###
     *   - sewing   → EXT-SEW-YYMMDD-###
     *   - default  → EXT-OTH-YYMMDD-###
     */
    protected function generateCode(string $process, Carbon $date): string
    {
        $prefix = match ($process) {
            'cutting' => 'EXT-CUT',
            'sewing' => 'EXT-SEW',
            'finishing' => 'EXT-FIN',
            default => 'EXT-OTH',
        };

        $ymd = $date->format('ymd');

        $countToday = ExternalTransfer::where('process', $process)
            ->whereDate('date', $date->toDateString())
            ->count();

        $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$ymd}-{$seq}";
    }
}
