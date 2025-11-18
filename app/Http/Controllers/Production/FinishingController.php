<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FinishingJob;
use App\Models\FinishingJobLine;
use App\Models\InventoryStock;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinishingController extends Controller
{
    public function index()
    {
        $jobs = FinishingJob::with('operator')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('production.finishing.index', [
            'jobs' => $jobs,
        ]);
    }

    /**
     * Create: ambil stok dari WIP-FIN sebagai sumber finishing
     */
    public function create(Request $request)
    {
        // kalau finishing ada operator, bisa pakai role 'finishing' / 'qc' / 'sewing' sesuai kebutuhan
        $operators = Employee::orderBy('name')->get();

        // Gudang sumber: WIP-FIN
        $fromWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WIP-FIN'],
            ['name' => 'WIP Finishing', 'type' => 'wip']
        );

        // Gudang tujuan: FG
        $toWarehouse = Warehouse::firstOrCreate(
            ['code' => 'FG'],
            ['name' => 'Finished Goods', 'type' => 'fg']
        );

        // Ambil stok WIP-FIN yang qty > 0
        $stocks = InventoryStock::query()
            ->where('warehouse_id', $fromWarehouse->id)
            ->where('qty', '>', 0)
            ->with(['item', 'lot'])
            ->orderBy('item_code')
            ->get();

        return view('production.finishing.create', [
            'operators' => $operators,
            'fromWarehouse' => $fromWarehouse,
            'toWarehouse' => $toWarehouse,
            'stocks' => $stocks,
        ]);
    }

    /**
     * Store: simpan job finishing (DRAFT)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'operator_id' => ['nullable', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:500'],

            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],

            'lines' => ['required', 'array'],
            'lines.*.stock_id' => ['required', 'integer', 'exists:inventory_stocks,id'],
            'lines.*.qty_source' => ['required', 'numeric', 'min:0'],
            'lines.*.qty_ok' => ['required', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'lines.*.unit' => ['required', 'string', 'max:16'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $linesInput = collect($data['lines'])
            ->filter(function ($line) {
                return ($line['qty_ok'] ?? 0) > 0 || ($line['qty_reject'] ?? 0) > 0;
            })
            ->values();

        if ($linesInput->isEmpty()) {
            return back()->withErrors(['msg' => 'Isi minimal satu baris qty OK / Reject.'])->withInput();
        }

        // Ambil stok WIP-FIN dari DB untuk validasi qty_source tidak > stok
        $stockIds = $linesInput->pluck('stock_id')->all();

        $stocks = InventoryStock::query()
            ->whereIn('id', $stockIds)
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($linesInput as $idx => $line) {
            $stock = $stocks->get($line['stock_id']);

            if (!$stock) {
                $errors["lines.$idx.stock_id"] = 'Stok WIP-FIN tidak ditemukan.';
                continue;
            }

            $qtySource = (float) $line['qty_source'];
            $qtyOk = (float) $line['qty_ok'];
            $qtyReject = (float) $line['qty_reject'];
            $totalOut = $qtyOk + $qtyReject;

            if ($totalOut <= 0) {
                $errors["lines.$idx.qty_ok"] = 'Qty OK + Reject harus lebih dari 0.';
                continue;
            }

            if ($totalOut > $qtySource + 1e-6) {
                $errors["lines.$idx.qty_ok"] =
                    "Qty OK + Reject ({$totalOut}) tidak boleh melebihi Qty Sumber ({$qtySource}).";
            }

            if ($qtySource > $stock->qty + 1e-6) {
                $errors["lines.$idx.qty_source"] =
                    "Qty Sumber ({$qtySource}) tidak boleh melebihi stok WIP-FIN ({$stock->qty}).";
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        DB::beginTransaction();

        try {
            $date = $data['date'];

            // Generate kode: FIN-YYMMDD-###
            $running = FinishingJob::whereDate('date', $date)->count() + 1;
            $code = 'FIN-' . date('ymd', strtotime($date)) . '-' . str_pad($running, 3, '0', STR_PAD_LEFT);

            $job = FinishingJob::create([
                'code' => $code,
                'date' => $date,
                'operator_id' => $data['operator_id'] ?? null,
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
            ]);

            foreach ($linesInput as $line) {
                $stock = $stocks->get($line['stock_id']);

                FinishingJobLine::create([
                    'finishing_job_id' => $job->id,
                    'lot_id' => $stock->lot_id,
                    'item_id' => $stock->item_id,
                    'item_code' => $stock->item_code,
                    'qty_source' => $line['qty_source'],
                    'qty_ok' => $line['qty_ok'],
                    'qty_reject' => $line['qty_reject'],
                    'unit' => $line['unit'],
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('production.finishing.show', $job->id)
                ->with('success', 'Finishing disimpan sebagai DRAFT.');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['msg' => 'Gagal menyimpan finishing: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(FinishingJob $finishingJob)
    {
        $finishingJob->load(['operator', 'fromWarehouse', 'toWarehouse', 'lines.item', 'lines.lot']);

        $totalOk = $finishingJob->lines->sum('qty_ok');
        $totalReject = $finishingJob->lines->sum('qty_reject');
        $totalAll = $totalOk + $totalReject;

        return view('production.finishing.show', [
            'job' => $finishingJob,
            'totals' => [
                'ok' => $totalOk,
                'reject' => $totalReject,
                'all' => $totalAll,
            ],
        ]);
    }

    /**
     * POSTING Finishing:
     * - keluar stok dari WIP-FIN (qty_source)
     * - masuk stok FG (qty_ok)
     * - reject ke gudang REJECT / WIP-REJECT (opsional)
     */
    public function post(Request $request, FinishingJob $finishingJob)
    {
        $finishingJob->load(['lines']);

        if ($finishingJob->status === 'posted') {
            return back()->withErrors(['msg' => 'Dokumen sudah diposting.']);
        }

        if ($finishingJob->lines->isEmpty()) {
            return back()->withErrors(['msg' => 'Tidak ada detail finishing untuk diposting.']);
        }

        DB::beginTransaction();

        try {
            $date = $finishingJob->date;
            $refCode = $finishingJob->code;
            $fromId = $finishingJob->from_warehouse_id;
            $toId = $finishingJob->to_warehouse_id;

            foreach ($finishingJob->lines as $line) {
                // TODO: panggil InventoryService, contoh:
                // 1) Keluarkan qty_source dari WIP-FIN
                /*
                InventoryService::mutate(
                warehouseId: $fromId,
                itemId: $line->item_id,
                lotId: $line->lot_id,
                qtyOut: $line->qty_source,
                unit: $line->unit,
                date: $date,
                refCode: $refCode,
                note: 'Finishing - sumber WIP-FIN'
                );
                 */

                // 2) Masukkan qty_ok ke FG
                /*
                if ($line->qty_ok > 0) {
                InventoryService::mutate(
                warehouseId: $toId,
                itemId: $line->item_id,
                lotId: $line->lot_id,
                qtyIn: $line->qty_ok,
                unit: $line->unit,
                date: $date,
                refCode: $refCode,
                note: 'Finishing OK ke FG'
                );
                }
                 */

                // 3) Qty Reject finishing bisa diarahkan ke gudang REJECT (opsional)
            }

            $finishingJob->status = 'posted';
            $finishingJob->posted_at = Carbon::now();
            $finishingJob->save();

            DB::commit();

            return redirect()
                ->route('production.finishing.show', $finishingJob->id)
                ->with('success', 'Finishing berhasil diposting.');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['msg' => 'Gagal posting finishing: ' . $e->getMessage()]);
        }
    }
}
