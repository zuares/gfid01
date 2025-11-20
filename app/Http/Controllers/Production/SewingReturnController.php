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

        // 🔹 Operator jahit untuk filter
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
            'lines.stock', // boleh tetap, meski kita belum pakai stock_id
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

        // 🔹 From Gudang: secara UI masih pakai SEW-EXT-EMPCODE (untuk filter)
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
                ->with(['sewingPick'])
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
        /** @var \App\Services\InventoryService $inventory */
        $inventory = app(InventoryService::class);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:500'],

            // ⬇️ biaya sewing
            'sewing_rate' => ['nullable', 'numeric', 'min:0'],
            'sewing_fee' => ['nullable', 'numeric', 'min:0'],

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

        // 🔹 Ambil SewingPickLine dari DB + total retur saat ini (untuk hitung sisa di penjahit)
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

            // 🔴 VALIDASI UTAMA: total disetor tidak boleh > sisa di penjahit
            if ($totalInput > $remain + 1e-6) {
                $errors["lines.$idx.qty_ok"] =
                    "Qty OK + Reject ({$totalInput}) tidak boleh melebihi Sisa di penjahit ({$remain}) "
                    . "untuk dokumen " . ($model->sewingPick->code ?? ('LINE #' . $model->id));
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // 🔹 Hitung biaya sewing (rate/fee) berdasarkan total OK di dokumen ini
        $totalOkAll = (float) $linesInput->sum(function ($row) {
            return (float) ($row['qty_ok'] ?? 0);
        });

        $sewingRate = isset($data['sewing_rate'])
        ? (float) $data['sewing_rate']
        : 0.0;

        $sewingFee = isset($data['sewing_fee'])
        ? (float) $data['sewing_fee']
        : 0.0;

        // Kalau fee kosong tapi ada rate & total OK → hitung otomatis
        if ($sewingFee <= 0 && $sewingRate > 0 && $totalOkAll > 0) {
            $sewingFee = $sewingRate * $totalOkAll;
        }

        // 🔹 SIMPAN DATA + MUTASI STOK via mutateItem (status = posted)
        DB::beginTransaction();

        try {
            $date = $data['date'];
            $operator = Employee::findOrFail($operatorId);

            // Generate kode header
            $running = SewingReturn::whereDate('date', $date)->count() + 1;
            $code = 'SEW-RET-' . date('ymd', strtotime($date)) . '-' . str_pad($running, 3, '0', STR_PAD_LEFT);

            // 🔑 Tentukan gudang asal dari dokumen ambil jahit (gudang operator = to_warehouse_id di SewingPick)
            $firstInput = $linesInput->first();
            $firstPick = $firstInput ? $pickLines->get((int) $firstInput['sewing_pick_line_id']) : null;
            $fromWarehouse = null;

            if ($firstPick && $firstPick->sewingPick && $firstPick->sewingPick->to_warehouse_id) {
                $fromWarehouse = Warehouse::find($firstPick->sewingPick->to_warehouse_id);
            }

            if (!$fromWarehouse) {
                throw new \RuntimeException('Gudang asal (operator) tidak ditemukan dari dokumen ambil jahit.');
            }

            // 🔹 To Gudang otomatis: WIP-FIN
            $toWarehouse = Warehouse::firstOrCreate(
                ['code' => 'WIP-FIN'],
                [
                    'name' => 'WIP Finishing',
                    'type' => 'wip',
                ]
            );

            // 🔹 Gudang WIP-DEFACT-BAHAN untuk hasil reject
            $defectWarehouse = Warehouse::firstOrCreate(
                ['code' => 'WIP-DEFECT-INCOMPLETE'],
                [
                    'name' => 'WIP DEFECT Bahan Atau Belum Lengkap',
                    'type' => 'wip_defect',
                ]
            );

            // Header langsung posted (+ simpan info biaya sewing)
            $header = SewingReturn::create([
                'code' => $code,
                'date' => $date,
                'operator_id' => $operatorId,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'notes' => $data['notes'] ?? null,
                'status' => 'posted',
                'posted_at' => Carbon::now(),
                'sewing_rate' => $sewingRate > 0 ? $sewingRate : null,
                'sewing_fee' => $sewingFee > 0 ? $sewingFee : null,
            ]);

            // Detail + mutasi stok per baris (pakai mutateItem)
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

                // 1️⃣ Simpan detail retur
                $returnLine = SewingReturnLine::create([
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

                // 2️⃣ Mutasi KELUAR dari gudang operator (WIP sewing berkurang, OK + Reject)
                $inventory->mutateItem(
                    warehouseId: $fromWarehouse->id,
                    itemId: $model->item_id,
                    itemCode: $model->item_code,
                    type: 'SEWING_RETURN_OUT',
                    qtyIn: 0,
                    qtyOut: $totalInput,
                    unit: $unit,
                    refCode: $header->code,
                    note: 'Setor jahit dari operator ' . ($operator->name ?? 'Tanpa Nama') .
                    ' (line retur #' . $returnLine->id . ')',
                    date: $date,
                    category: 'wip_sewing'
                );

                // 3️⃣ Mutasi MASUK ke WIP-FIN (hanya Qty OK)
                if ($qtyOk > 0) {
                    $inventory->mutateItem(
                        warehouseId: $toWarehouse->id,
                        itemId: $model->item_id,
                        itemCode: $model->item_code,
                        type: 'SEWING_RETURN_OK_IN',
                        qtyIn: $qtyOk,
                        qtyOut: 0,
                        unit: $unit,
                        refCode: $header->code,
                        note: 'Masuk WIP Finishing dari setor jahit operator ' . ($operator->name ?? 'Tanpa Nama') .
                        ' (line retur #' . $returnLine->id . ')',
                        date: $date,
                        category: 'wip_finishing'
                    );
                }

                // 4️⃣ Mutasi MASUK ke WIP-DEFACT-BAHAN (hanya Qty Reject)
                if ($qtyReject > 0) {
                    $inventory->mutateItem(
                        warehouseId: $defectWarehouse->id,
                        itemId: $model->item_id,
                        itemCode: $model->item_code,
                        type: 'SEWING_RETURN_REJECT_IN',
                        qtyIn: $qtyReject,
                        qtyOut: 0,
                        unit: $unit,
                        refCode: $header->code,
                        note: 'Masuk WIP Defact Bahan (Reject Jahit) dari operator ' . ($operator->name ?? 'Tanpa Nama') .
                        ' (line retur #' . $returnLine->id . ')',
                        date: $date,
                        category: 'wip_defect'
                    );
                }
            }

            // 🔥 Catat biaya sewing ke production_costs (kalau sewing_fee > 0)
            $this->recordSewingCost($header);

            DB::commit();

            return redirect()
                ->route('production.sewing_returns.show', $header->id)
                ->with('success', 'Setor jahit berhasil disimpan & langsung diposting. Stok, mutasi & biaya sewing sudah tercatat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['msg' => 'Error saat simpan & posting setor jahit: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Catat biaya sewing ke production_costs, dibagi per LOT berdasarkan Qty OK.
     */
    protected function recordSewingCost(SewingReturn $header): void
    {
        $totalFee = (float) ($header->sewing_fee ?? 0);

        if ($totalFee <= 0) {
            return;
        }

        $header->loadMissing('lines');

        // Hanya baris OK > 0
        $lines = $header->lines->filter(fn($ln) => (float) $ln->qty_ok > 0);

        if ($lines->isEmpty()) {
            return;
        }

        // ==== GROUP BY ITEM ID ====
        $grouped = $lines->groupBy('item_id');

        $totalOkAll = $lines->sum('qty_ok');
        if ($totalOkAll <= 0) {
            return;
        }

        foreach ($grouped as $itemId => $rows) {
            $qtyItem = (float) $rows->sum('qty_ok');

            if ($qtyItem <= 0) {
                continue;
            }

            // Proporsi biaya sewing untuk item ini
            $amount = $totalFee * ($qtyItem / $totalOkAll);
            $cpu = $amount / max(1, $qtyItem);

            \App\Models\ProductionCost::create([
                'lot_id' => null, // tidak pakai LOT
                'item_id' => $itemId, // biaya per item
                'stage' => 'sewing',
                'qty_base' => $qtyItem,
                'amount' => $amount,
                'cost_per_unit' => $cpu,
                'source_type' => 'sewing_return',
                'source_id' => $header->id,
                'notes' => 'Biaya sewing dari setor ' . $header->code,
            ]);
        }
    }

}
