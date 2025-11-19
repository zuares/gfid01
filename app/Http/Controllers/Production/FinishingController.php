<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FinishingJob;
use App\Models\FinishingLine;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinishingController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // 🔹 List operator finishing (sementara ambil semua employee)
        // kalau nanti ada role khusus tinggal tambahkan where('role', 'finishing')
        $employees = Employee::orderBy('name')->get();

        $query = FinishingJob::query()
            ->with(['employee', 'fromWarehouse', 'toWarehouse', 'lines'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $jobs = $query->paginate(25)->withQueryString();

        return view('production.finishing.index', [
            'jobs' => $jobs,
            'employees' => $employees, // ✅ WAJIB
            'employeeId' => $employeeId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Detail satu dokumen finishing.
     */
    public function show(FinishingJob $finishingJob)
    {
        $finishingJob->load([
            'employee',
            'fromWarehouse',
            'toWarehouse',
            'lines.item',
            'lines.fgItem',
        ]);

        $totalOk = $finishingJob->lines->sum('qty_ok');
        $totalReject = $finishingJob->lines->sum('qty_reject');
        $totalAll = $totalOk + $totalReject;

        $totals = [
            'ok' => $totalOk,
            'reject' => $totalReject,
            'all' => $totalAll,
        ];

        return view('production.finishing.show', [
            'job' => $finishingJob,
            'totals' => $totals,
        ]);
    }
    public function create()
    {
        // Gudang WIP-FIN
        $fromWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WIP-FIN'],
            [
                'name' => 'WIP Finishing',
                'type' => 'wip',
            ]
        );

        // Gudang FG (Finished Goods)
        $toWarehouse = Warehouse::firstOrCreate(
            ['code' => 'FG'],
            [
                'name' => 'Finished Goods',
                'type' => 'fg',
            ]
        );

        // Ambil stok WIP-FIN per item (lot_id NULL)
        $stocks = InventoryStock::query()
            ->where('warehouse_id', $fromWarehouse->id)
            ->whereNull('lot_id')
            ->where('qty', '>', 0)
            ->with('item')
            ->orderBy('item_code')
            ->get();

        // Optional: karyawan finishing/packing
        $employees = Employee::orderBy('name')->get();

        return view('production.finishing.create', [
            'fromWarehouse' => $fromWarehouse,
            'toWarehouse' => $toWarehouse,
            'stocks' => $stocks, // list WIP-FIN yang bisa dipacking
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Services\InventoryService $inventory */
        $inventory = app(InventoryService::class);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:500'],

            'lines' => ['required', 'array'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.unit' => ['required', 'string', 'max:16'],
            'lines.*.qty_wip' => ['nullable', 'numeric', 'min:0'],
            'lines.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'lines.*.fg_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        // filter hanya baris yang ada qty_ok / qty_reject
        $linesInput = collect($data['lines'])
            ->filter(function ($line) {
                $qtyOk = (float) ($line['qty_ok'] ?? 0);
                $qtyReject = (float) ($line['qty_reject'] ?? 0);
                return $qtyOk > 0 || $qtyReject > 0;
            })
            ->values();

        if ($linesInput->isEmpty()) {
            return back()
                ->withErrors(['msg' => 'Tidak ada baris yang diisi Qty OK / Reject.'])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $date = $data['date'];

            // Gudang WIP-FIN (asal)
            $fromWarehouse = Warehouse::firstOrCreate(
                ['code' => 'WIP-FIN'],
                [
                    'name' => 'WIP Finishing',
                    'type' => 'wip',
                ]
            );

            // Gudang tujuan FG = KONTRAKAN
            $toWarehouse = Warehouse::firstOrCreate(
                ['code' => 'KONTRAKAN'],
                [
                    'name' => 'Gudang Kontrakan',
                    'type' => 'internal',
                ]
            );

            // Gudang untuk barang reject finishing
            $rejectWarehouse = Warehouse::firstOrCreate(
                ['code' => 'WIP-REJECT'],
                [
                    'name' => 'WIP Reject',
                    'type' => 'wip',
                ]
            );

            // Generate kode
            $running = FinishingJob::whereDate('date', $date)->count() + 1;
            $code = 'FIN-' . date('ymd', strtotime($date)) . '-' . str_pad($running, 3, '0', STR_PAD_LEFT);

            $job = FinishingJob::create([
                'code' => $code,
                'date' => $date,
                'employee_id' => $data['employee_id'] ?? null,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'status' => 'posted', // langsung posted
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'posted_at' => Carbon::now(),
            ]);

            foreach ($linesInput as $line) {
                $itemId = (int) $line['item_id'];
                $unit = $line['unit'];
                $qtyOk = (float) ($line['qty_ok'] ?? 0);
                $qtyReject = (float) ($line['qty_reject'] ?? 0);
                $qtyWip = (float) ($line['qty_wip'] ?? 0);
                $total = $qtyOk + $qtyReject;

                if ($total <= 0) {
                    continue;
                }

                // Item WIP
                /** @var \App\Models\Item $item */
                $item = Item::findOrFail($itemId);

                // Barang jadi (kalau tidak diisi, pakai item yg sama)
                $fgItemId = $line['fg_item_id'] ?? $itemId;
                $fgItem = Item::findOrFail($fgItemId);
                $fgItemCode = $fgItem->code;

                // 🔒 Cek stok WIP-FIN dulu biar tidak minus
                $sourceStock = InventoryStock::where('warehouse_id', $fromWarehouse->id)
                    ->where('item_id', $itemId)
                    ->whereNull('lot_id')
                    ->where('unit', $unit)
                    ->lockForUpdate()
                    ->first();

                if (!$sourceStock) {
                    throw new \RuntimeException("Stok WIP-FIN untuk item {$item->code} ({$unit}) tidak ditemukan.");
                }

                if ($total > $sourceStock->qty + 1e-6) {
                    throw new \RuntimeException("Qty finishing ({$total}) melebihi stok WIP-FIN ({$sourceStock->qty}) untuk item {$item->code}.");
                }

                // 1️⃣ Simpan detail
                FinishingLine::create([
                    'finishing_job_id' => $job->id,
                    'item_id' => $itemId,
                    'item_code' => $item->code,
                    'qty_wip' => $qtyWip ?: $sourceStock->qty, // snapshot opsional
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'unit' => $unit,
                    'fg_item_id' => $fgItemId,
                    'fg_item_code' => $fgItemCode,
                    'notes' => $line['notes'] ?? null,
                ]);

                // 2️⃣ MUTASI: Keluar dari WIP-FIN (OK + Reject)
                $inventory->mutateItem(
                    warehouseId: $fromWarehouse->id,
                    itemId: $itemId,
                    itemCode: $item->code,
                    type: 'WIP_FIN_OUT',
                    qtyIn: 0,
                    qtyOut: $total,
                    unit: $unit,
                    refCode: $job->code,
                    note: 'Finishing keluar dari WIP-FIN (OK + REJECT)',
                    date: $date,
                    category: 'wip_finishing'
                );

                // 3️⃣ MUTASI: Qty OK masuk KONTRAKAN sebagai FG
                if ($qtyOk > 0) {
                    $inventory->mutateItem(
                        warehouseId: $toWarehouse->id,
                        itemId: $fgItemId,
                        itemCode: $fgItemCode,
                        type: 'FG_IN',
                        qtyIn: $qtyOk,
                        qtyOut: 0,
                        unit: $unit,
                        refCode: $job->code,
                        note: 'Finishing OK masuk KONTRAKAN',
                        date: $date,
                        category: 'fg'
                    );
                }

                // 4️⃣ MUTASI: Qty Reject masuk WIP-REJECT
                if ($qtyReject > 0) {
                    $inventory->mutateItem(
                        warehouseId: $rejectWarehouse->id,
                        itemId: $itemId, // pakai item WIP-nya
                        itemCode: $item->code,
                        type: 'WIP_REJECT_IN',
                        qtyIn: $qtyReject,
                        qtyOut: 0,
                        unit: $unit,
                        refCode: $job->code,
                        note: 'Finishing REJECT masuk WIP-REJECT',
                        date: $date,
                        category: 'wip_reject'
                    );
                }
            }

            DB::commit();

            return redirect()
                ->route('production.finishing.show', $job->id)
                ->with('success', 'Finishing / packing berhasil disimpan. FG & WIP-REJECT sudah di-update via mutasi.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['msg' => 'Gagal simpan finishing: ' . $e->getMessage()])
                ->withInput();
        }
    }

}
