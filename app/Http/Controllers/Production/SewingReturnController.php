<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SewingPickLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SewingReturnController extends Controller
{
    public function index(Request $request)
    {
        $operatorId = $request->input('operator_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Operator jahit untuk filter
        $operators = Employee::where('role', 'sewing')
            ->orderBy('name')
            ->get();

        $query = SewingReturn::query()
            ->with(['operator', 'fromWarehouse', 'toWarehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($operatorId) {
            $query->where('operator_id', $operatorId);
        }

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $returns = $query->paginate(25)->withQueryString();

        return view('production.sewing_returns.index', [
            'returns' => $returns,
            'operators' => $operators,
            'operatorId' => $operatorId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function show(SewingReturn $sewingReturn)
    {
        $sewingReturn->load([
            'operator',
            'fromWarehouse',
            'toWarehouse',
            'lines.sewingPickLine.sewingPick',
        ]);

        $totalOk = $sewingReturn->lines->sum('qty_ok');
        $totalReject = $sewingReturn->lines->sum('qty_reject');
        $totalAll = $totalOk + $totalReject;

        $totals = [
            'ok' => $totalOk,
            'reject' => $totalReject,
            'all' => $totalAll,
        ];

        return view('production.sewing_returns.show', [
            'sewingReturn' => $sewingReturn,
            'totals' => $totals,
        ]);
    }

    public function create(Request $request)
    {
        $operatorId = $request->input('operator_id');

        // 🔹 Hanya operator yang role-nya sewing
        $operators = Employee::where('role', 'sewing')
            ->orderBy('name')
            ->get();

        $operator = $operatorId ? $operators->firstWhere('id', (int) $operatorId) : null;
        $fromWarehouse = null;

        // 🔹 From Gudang otomatis: SEW-EXT-EMPCODE
        if ($operator) {
            $empCode = $operator->code ?? ('EMP-' . $operator->id);
            $fromWarehouse = Warehouse::firstOrCreate(
                ['code' => 'SEW-EXT-' . $empCode],
                [
                    'name' => 'Gudang Jahit ' . $empCode,
                    'type' => 'external_sew',
                ]
            );
        }

        // 🔹 To Gudang otomatis: WIP-FIN
        $toWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WIP-FIN'],
            [
                'name' => 'WIP Finishing',
                'type' => 'wip',
            ]
        );

        // 🔹 Data detail: hanya kalau operator dipilih
        $pickLines = collect();

        if ($operator) {
            $pickQuery = SewingPickLine::query()
                ->with(['sewingPick']) // biar bisa tampil code & date
            // total setor OK per line
                ->withSum('sewingReturns as total_ok', 'qty_ok')
            // total setor Reject per line
                ->withSum('sewingReturns as total_reject', 'qty_reject')
                ->orderByDesc('id');

            // filter per operator
            $pickQuery->whereHas('sewingPick', function ($q) use ($operatorId) {
                $q->where('operator_id', $operatorId);
            });

            // filter lagi by gudang operator (hasil ambil jahit)
            if ($fromWarehouse) {
                $pickQuery->whereHas('sewingPick', function ($q) use ($fromWarehouse) {
                    $q->where('to_warehouse_id', $fromWarehouse->id);
                });
            }

            $allLines = $pickQuery->limit(200)->get();

            // 🔹 Hitung sisa = qty ambil - (total_ok + total_reject)
            //     dan ambil hanya yang masih punya sisa > 0
            $pickLines = $allLines->filter(function ($line) {
                $picked = (float) $line->qty;
                $returned = (float) ($line->total_ok ?? 0) + (float) ($line->total_reject ?? 0);
                $remain = $picked - $returned;

                // simpan ke property supaya kalau mau dipakai di Blade juga bisa
                $line->remain = $remain;

                return $remain > 0.0001; // masih ada yang bisa disetor
            })->values();
        }

        return view('production.sewing_returns.create', [
            'operators' => $operators,
            'operatorId' => $operatorId,
            'operator' => $operator,
            'fromWarehouse' => $fromWarehouse,
            'toWarehouse' => $toWarehouse,
            'pickLines' => $pickLines,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:500'],

            'lines' => ['required', 'array'],
            'lines.*.sewing_pick_line_id' => ['required', 'integer', 'exists:sewing_pick_lines,id'],
            'lines.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'lines.*.selected' => ['nullable'], // checkbox
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ], [
            'lines.*.sewing_pick_line_id.required' => 'Terjadi kesalahan: data ambil jahit tidak lengkap.',
        ]);

        $operatorId = (int) $data['operator_id'];

        // 🔹 Ambil hanya baris yang DICENTANG dan ada nilai OK/Reject
        $linesInput = collect($data['lines'])
            ->filter(function ($line) {
                $selected = !empty($line['selected']); // dicentang
                $qtyOk = (float) ($line['qty_ok'] ?? 0);
                $qtyReject = (float) ($line['qty_reject'] ?? 0);

                return $selected && ($qtyOk > 0 || $qtyReject > 0);
            })
            ->values();

        if ($linesInput->isEmpty()) {
            return back()
                ->withErrors(['msg' => 'Tidak ada baris yang dipilih dan diisi Qty OK/Reject.'])
                ->withInput();
        }

        // 🔹 Ambil SewingPickLine dari DB + total retur saat ini
        $pickLineIds = $linesInput->pluck('sewing_pick_line_id')->all();

        $pickLines = SewingPickLine::query()
            ->with(['sewingPick'])
            ->withSum('sewingReturns as total_ok', 'qty_ok')
            ->withSum('sewingReturns as total_reject', 'qty_reject')
            ->whereIn('id', $pickLineIds)
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($linesInput as $idx => $line) {
            $pickLineId = (int) $line['sewing_pick_line_id'];
            $model = $pickLines->get($pickLineId);

            if (!$model) {
                $errors["lines.$idx.sewing_pick_line_id"] = "Data ambil jahit tidak ditemukan.";
                continue;
            }

            // Pastikan operator header = operator di SewingPick
            if ((int) $model->sewingPick->operator_id !== $operatorId) {
                $errors["lines.$idx.sewing_pick_line_id"] =
                    "Baris ini bukan milik operator yang dipilih.";
                continue;
            }

            $qtyOk = (float) ($line['qty_ok'] ?? 0);
            $qtyReject = (float) ($line['qty_reject'] ?? 0);
            $totalInput = $qtyOk + $qtyReject;

            if ($totalInput <= 0) {
                $errors["lines.$idx.qty_ok"] =
                    "Qty OK + Reject harus lebih dari 0 untuk baris yang dipilih.";
                continue;
            }

            $picked = (float) $model->qty;
            $returned = (float) ($model->total_ok ?? 0) + (float) ($model->total_reject ?? 0);
            $remain = $picked - $returned;

            // 🔴 VALIDASI UTAMA:
            // total yang disetor (OK + Reject, setor kali ini) tidak boleh > sisa
            if ($totalInput > $remain + 1e-6) {
                $errors["lines.$idx.qty_ok"] =
                    "Qty OK + Reject ({$totalInput}) tidak boleh melebihi Sisa di penjahit ({$remain}) "
                    . "untuk dokumen " . ($model->sewingPick->code ?? ('LINE #' . $model->id));
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // 🔹 SIMPAN DATA + MUTASI INVENTORY
        DB::beginTransaction();

        try {
            $date = $data['date'];
            $operator = Employee::findOrFail($operatorId);

            // Generate kode header
            $running = SewingReturn::whereDate('date', $date)->count() + 1;
            $code = 'SEW-RET-' . date('ymd', strtotime($date)) . '-' . str_pad($running, 3, '0', STR_PAD_LEFT);

            // Hitung gudang from/to sama seperti di create()
            $empCode = $operator->code ?? ('EMP-' . $operator->id);

            $fromWarehouse = Warehouse::firstOrCreate(
                ['code' => 'SEW-EXT-' . $empCode],
                [
                    'name' => 'Gudang Jahit ' . $empCode,
                    'type' => 'external_sew',
                ]
            );

            $toWarehouse = Warehouse::firstOrCreate(
                ['code' => 'WIP-FIN'],
                [
                    'name' => 'WIP Finishing',
                    'type' => 'wip',
                ]
            );

            // ⚠️ Pastikan kolom-kolom ini ADA di tabel sewing_returns
            $header = SewingReturn::create([
                'code' => $code,
                'date' => $date,
                'operator_id' => $operatorId,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Detail + mutasi stok
            foreach ($linesInput as $line) {
                $pickLineId = (int) $line['sewing_pick_line_id'];
                $model = $pickLines->get($pickLineId);

                if (!$model) {
                    continue;
                }

                $qtyOk = (float) ($line['qty_ok'] ?? 0);
                $qtyReject = (float) ($line['qty_reject'] ?? 0);
                $totalInput = $qtyOk + $qtyReject;

                if ($totalInput <= 0) {
                    continue;
                }

                // Simpan detail return
                SewingReturnLine::create([
                    'sewing_return_id' => $header->id,
                    'sewing_pick_line_id' => $model->id,
                    'lot_id' => $model->lot_id,
                    'item_id' => $model->item_id,
                    'item_code' => $model->item_code,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'unit' => $model->unit ?? 'pcs',
                    'notes' => $line['notes'] ?? null,
                ]);

                $unit = $model->unit ?? 'pcs';
                $category = 'wip'; // atau sesuaikan dengan skema kamu

                // 🔁 1) KELUARKAN total (OK + Reject) dari gudang jahit operator (SEW-EXT-EMPCODE)
                InventoryService::reduceStockLot([
                    'warehouse_id' => $fromWarehouse->id,
                    'lot_id' => $model->lot_id,
                    'unit' => $unit,
                    'qty' => $totalInput,
                    'type' => 'SEW_RETURN_OUT',
                    'ref_code' => $code,
                    'note' => 'Setor jahit dari ' . ($operator->name ?? $empCode),
                    'date' => $date,
                    'category' => $category,
                ]);

                // 🔺 2) MASUKKAN hanya Qty OK ke gudang WIP-FIN
                if ($qtyOk > 0) {
                    InventoryService::addStockLot([
                        'warehouse_id' => $toWarehouse->id,
                        'lot_id' => $model->lot_id,
                        'unit' => $unit,
                        'qty' => $qtyOk,
                        'type' => 'SEW_RETURN_OK_IN',
                        'ref_code' => $code,
                        'note' => 'Setor jahit OK ke WIP-FIN',
                        'date' => $date,
                        'category' => $category,
                    ]);
                }

                // Catatan: Qty Reject di sini dianggap scrap / hilang.
                // Kalau nanti kamu punya gudang REJECT (misal REJ-SEW),
                // tinggal tambah satu lagi InventoryService::addStockLot() ke gudang itu.
            }
            DB::commit();

            return redirect()
                ->route('production.sewing_returns.index')
                ->with('success', 'Setor jahit berhasil disimpan dan stok dipindahkan ke WIP-FIN.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['msg' => 'Error: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function post(Request $request, SewingReturn $sewingReturn)
    {
        // Reload dengan relasi lines
        $sewingReturn->load(['lines']);

        if ($sewingReturn->status === 'posted') {
            return back()->withErrors(['msg' => 'Dokumen ini sudah diposting.'])->withInput();
        }

        if ($sewingReturn->lines->isEmpty()) {
            return back()->withErrors(['msg' => 'Tidak ada detail baris untuk diposting.'])->withInput();
        }

        DB::beginTransaction();

        try {
            $date = $sewingReturn->date;
            $refCode = $sewingReturn->code;
            $fromId = $sewingReturn->from_warehouse_id;
            $toId = $sewingReturn->to_warehouse_id;

            foreach ($sewingReturn->lines as $line) {
                // ✅ Barang OK pindah dari gudang jahit ke WIP-FIN
                if ($line->qty_ok > 0) {
                    // Contoh pseudo-code, sesuaikan dengan InventoryService kamu:
                    /*
                InventoryService::transfer(
                fromWarehouseId: $fromId,
                toWarehouseId: $toId,
                itemId: $line->item_id,
                lotId: $line->lot_id,
                qty: $line->qty_ok,
                unit: $line->unit,
                date: $date,
                refCode: $refCode,
                note: 'Setor Jahit OK'
                );
                 */
                }

                // ✅ Barang REJECT juga keluar dari gudang penjahit
                if ($line->qty_reject > 0) {
                    // Tergantung desainmu, bisa:
                    // - masuk gudang REJECT
                    // - atau tetap ke WIP-FIN dengan flag reject
                    // Contoh pseudo:

                    /*
                InventoryService::transfer(
                fromWarehouseId: $fromId,
                toWarehouseId: $toId, // atau gudang REJECT khusus
                itemId: $line->item_id,
                lotId: $line->lot_id,
                qty: $line->qty_reject,
                unit: $line->unit,
                date: $date,
                refCode: $refCode,
                note: 'Setor Jahit REJECT'
                );
                 */
                }
            }

            // Update status dokumen
            $sewingReturn->status = 'posted';
            $sewingReturn->posted_at = Carbon::now();
            $sewingReturn->save();

            DB::commit();

            return redirect()
                ->route('production.sewing_returns.show', $sewingReturn->id)
                ->with('success', 'Setor jahit berhasil diposting.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['msg' => 'Gagal posting setor jahit: ' . $e->getMessage()])
                ->withInput();
        }
    }

}
