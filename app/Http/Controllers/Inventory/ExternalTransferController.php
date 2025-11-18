<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ExternalTransfer;
use App\Models\ExternalTransferLine;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExternalTransferController extends Controller
{
    public function index()
    {
        $transfers = ExternalTransfer::with(['fromWarehouse', 'toWarehouse'])
            ->orderBy('date', 'desc')
            ->orderBy('code', 'desc')
            ->paginate(20);

        return view('inventory.external_transfers.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        // Semua gudang (buat dropdown "Dari Gudang")
        $warehouses = Warehouse::orderBy('name')->get();

        // Default proses = cutting (kalau tidak dipilih)
        $defaultProcess = $request->get('process', 'cutting');

        // Default "Dari Gudang" = KONTRAKAN (kalau ada), bisa dioverride via query param
        $defaultFromWarehouse = $warehouses->firstWhere('code', 'KONTRAKAN');
        $fromWarehouseId = $request->get('from_warehouse_id');

        if (!$fromWarehouseId && $defaultFromWarehouse) {
            $fromWarehouseId = $defaultFromWarehouse->id;
        }

        $defaultFromWarehouseId = $fromWarehouseId;

        // Semua karyawan (nanti di Blade difilter by role = process)
        $employees = Employee::orderBy('name')->get();

        // Operator yang dipilih (dari query / old form)
        $operatorCode = $request->get('operator_code', $request->old('operator_code'));

        // LOT: hanya yang punya stok di gudang "from_warehouse"
        // Kita join lots + items + inventory_stocks untuk dapat stock_remain per LOT per gudang
        $lotsQuery = Lot::query()
            ->selectRaw('
            lots.id,
            lots.item_id,
            lots.code as lot_code,
            lots.unit as uom,
            items.code as item_code,
            items.name as item_name,
            COALESCE(SUM(inventory_stocks.qty), 0) as stock_remain
        ')
            ->join('items', 'items.id', '=', 'lots.item_id')
            ->leftJoin('inventory_stocks', function ($q) use ($fromWarehouseId) {
                $q->on('inventory_stocks.lot_id', '=', 'lots.id');
                if ($fromWarehouseId) {
                    $q->where('inventory_stocks.warehouse_id', $fromWarehouseId);
                }
            })
            ->groupBy('lots.id', 'lots.item_id', 'lots.code', 'lots.unit', 'items.code', 'items.name')
            ->orderBy('lots.code');

        // Hanya tampilkan LOT yang punya stok > 0
        $lots = $lotsQuery
            ->having('stock_remain', '>', 0)
            ->get();

        // Auto code gudang tujuan: CUT-EXT-[EMP] (atau SEW/FIN sesuai process)
        $autoToWarehouseCode = null;
        if ($operatorCode) {
            $autoToWarehouseCode = $this->generateAutoToWarehouseCode($defaultProcess, $operatorCode);
        }

        return view('inventory.external_transfers.create', [
            'warehouses' => $warehouses,
            'lots' => $lots,
            'employees' => $employees,
            'defaultProcess' => $defaultProcess,
            'defaultFromWarehouseId' => $defaultFromWarehouseId,
            'autoToWarehouseCode' => $autoToWarehouseCode,
        ]);
    }

    public function edit(ExternalTransfer $transfer)
    {
        // Hanya ubah status & catatan, tidak ubah lines / stok
        return view('inventory.external_transfers.edit', compact('transfer'));
    }
    public function show(ExternalTransfer $transfer)
    {

        $transfer->load(['fromWarehouse', 'toWarehouse', 'lines.lot.item']);
        return view('inventory.external_transfers.show', compact('transfer'));
    }
    public function update(Request $request, ExternalTransfer $transfer)
    {
        $data = $request->validate([
            'status' => ['required', 'in:sent,received,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transfer->update($data);

        return redirect()
            ->route('inventory.external_transfers.show', $transfer->id)
            ->with('success', 'Status External Transfer berhasil diperbarui.');
    }

/**
 * Map process + operator_code → kode gudang vendor, misal:
 * cutting + MRF → CUT-EXT-MRF
 */
    protected function generateAutoToWarehouseCode(string $process, string $operatorCode): string
    {
        $proc = strtolower($process);

        switch ($proc) {
            case 'cutting':
                $proc3 = 'CUT';
                break;
            case 'sewing':
                $proc3 = 'SEW';
                break;
            case 'finishing':
                $proc3 = 'FIN';
                break;
            case 'other':
                $proc3 = 'OTH';
                break;
            default:
                $proc3 = strtoupper(substr($proc, 0, 3));
                break;
        }

        $op3 = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $operatorCode), 0, 3));

        return "{$proc3}-EXT-{$op3}";
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'process' => ['required', 'in:cutting,sewing,finishing,other'],
            'operator_code' => ['required', 'string', 'max:50'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:500'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.uom' => ['nullable', 'string', 'max:16'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ], [
            'lines.required' => 'Minimal harus ada 1 LOT yang dipilih.',
        ]);

        $user = Auth::user();

        // 🔹 Ambil service sekali di luar transaction
        $inventory = app(InventoryService::class);

        DB::transaction(function () use ($validated, $user, $inventory) {

            $date = $validated['date'];
            $process = $validated['process'];
            $operatorCode = $validated['operator_code'];
            $fromWarehouse = (int) $validated['from_warehouse_id'];
            $notes = $validated['notes'] ?? null;
            $linesInput = $validated['lines'];

            // ==== 1. Tentukan / buat gudang tujuan berdasarkan process + operator ====
            $toWarehouseCode = $this->generateAutoToWarehouseCode($process, $operatorCode);

            /** @var Warehouse $toWarehouse */
            $toWarehouse = Warehouse::firstOrCreate(
                ['code' => $toWarehouseCode],
                [
                    'name' => $toWarehouseCode . ' (Vendor)',
                    'type' => 'external',
                ]
            );

            $toWarehouseId = $toWarehouse->id;

            // ==== 2. Generate kode dokumen EXT-YYYYMMDD-### ====
            $dateStr = date('Ymd', strtotime($date));
            $prefix = 'EXT-' . $dateStr . '-';

            $last = ExternalTransfer::where('code', 'like', $prefix . '%')
                ->orderBy('code', 'desc')
                ->first();

            $nextNumber = $last
            ? ((int) substr($last->code, strlen($prefix))) + 1
            : 1;

            $code = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // ==== 3. Buat header external_transfer ====
            /** @var ExternalTransfer $transfer */
            $transfer = ExternalTransfer::create([
                'code' => $code,
                'date' => $date,
                'process' => $process,
                'operator_code' => $operatorCode,
                'from_warehouse_id' => $fromWarehouse,
                'to_warehouse_id' => $toWarehouseId,
                'status' => 'sent',
                'notes' => $notes,
                'created_by' => $user?->id,
            ]);

            // ==== 4. Simpan detail per LOT + mutasi stok per LOT ====
            foreach ($linesInput as $line) {
                $qty = (float) ($line['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                /** @var Lot $lot */
                $lot = Lot::with('item')->findOrFail($line['lot_id']);

                $uom = $line['uom'] ?? $lot->unit ?? ($lot->item->default_unit ?? 'm');

                // 4a. simpan line
                ExternalTransferLine::create([
                    'external_transfer_id' => $transfer->id,
                    'lot_id' => $lot->id,
                    'item_id' => $lot->item_id,
                    'item_code' => $lot->item->code,
                    'qty' => $qty,
                    'unit' => $uom,
                    'notes' => $line['notes'] ?? null,
                ]);

                // 4b. mutasi stok LOT: from -> to via InventoryService->transfer()
                $inventory->transfer(
                    fromWarehouseId: $fromWarehouse,
                    toWarehouseId: $toWarehouseId,
                    lotId: $lot->id,
                    qty: $qty,
                    unit: $uom,
                    refCode: $code,
                    note: "External transfer {$process} ke {$operatorCode}",
                    date: $date,
                    category: 'rawmaterial', // kirim kain ke vendor
                );
            }
        });

        return redirect()
            ->route('inventory.external_transfers.index')
            ->with('success', 'External Transfer berhasil dibuat & stok LOT sudah dipindahkan ke gudang vendor.');
    }

    // index() dll bisa kamu isi belakangan
}
