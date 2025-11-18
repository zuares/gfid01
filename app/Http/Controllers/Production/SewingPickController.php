<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SewingPick; // sesuaikan nama model WIP-mu
use App\Models\SewingPickLine;
use App\Models\Warehouse;
use App\Models\WipItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SewingPickController extends Controller
{

    public function index(Request $request)
    {
        $operatorId = $request->input('operator_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = SewingPick::query()
            ->with(['operator', 'fromWarehouse', 'toWarehouse', 'lines'])
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

        $picks = $query->paginate(20);

        // hanya operator jahit
        $operators = Employee::where('role', 'jahit')
            ->orderBy('name')
            ->get();

        return view('production.sewing_picks.index', [
            'picks' => $picks,
            'operators' => $operators,
            'operatorId' => $operatorId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function show(SewingPick $sewingPick)
    {
        $sewingPick->load([
            'fromWarehouse',
            'toWarehouse',
            'lines',
        ]);

        return view('production.sewing_picks.show', compact('sewingPick'));
    }

    public function create(Request $request)
    {

        $wipWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WIP-SEW'],
            ['name' => 'WIP Siap Jahit', 'type' => 'wip']
        );

        $wipItems = WipItem::query()
            ->where('stage', 'cutting')
            ->where('status', 'available')
            ->where('warehouse_id', $wipWarehouse->id)
            ->orderBy('item_code')
            ->get();

        // HANYA operator dengan role 'jahit'
        $operators = Employee::where('role', 'sewing')
            ->orderBy('name')
            ->get();

        return view('production.sewing_picks.create', [
            'wipItems' => $wipItems,
            'operators' => $operators,
            'wipWarehouse' => $wipWarehouse,
        ]);
    }

    public function store(Request $request)
    {
        // Pastikan gudang WIP-SEW ada (stok siap dijahit)
        $wipWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WIP-SEW'],
            ['name' => 'WIP Siap Jahit', 'type' => 'wip']
        );

        $data = $request->validate([
            'date' => ['required', 'date'],
            'operator_id' => ['required', 'integer', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:500'],

            'lines' => ['required', 'array'],
            'lines.*.selected' => ['nullable'], // checkbox
            'lines.*.wip_item_id' => ['nullable', 'integer'],
            'lines.*.lot_id' => ['required', 'integer'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.item_code' => ['required', 'string', 'max:100'],
            'lines.*.qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit' => ['required', 'string', 'max:16'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $date = $data['date'];

        $operator = Employee::findOrFail($data['operator_id']);
        $opCode = $operator->code ?? ('OP-' . $operator->id);

        // Tujuan gudang otomatis: SEW-EXT-EMPCODE (stok jahit yang sedang dipegang operator)
        $toWarehouse = Warehouse::firstOrCreate(
            ['code' => 'SEW-EXT-' . $opCode],
            [
                'name' => 'Gudang Jahit ' . $opCode,
                'type' => 'external_sew', // bebas, sesuaikan skema kamu
            ]
        );

        /** @var InventoryService $inventory */
        $inventory = app(InventoryService::class);

        DB::transaction(function () use ($data, $date, $wipWarehouse, $toWarehouse, $inventory) {

            // Generate kode: SEW-PICK-YYMMDD-###
            $running = SewingPick::whereDate('date', $date)->count() + 1;
            $code = 'SEW-PICK-' . date('ymd', strtotime($date)) . '-' . str_pad($running, 3, '0', STR_PAD_LEFT);

            // Header
            $header = SewingPick::create([
                'code' => $code,
                'date' => $date,
                'operator_id' => $data['operator_id'],
                'from_warehouse_id' => $wipWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $fromWh = $wipWarehouse->id; // stok siap dijahit (WIP-SEW)
            $toWh = $toWarehouse->id; // stok jahit di operator (SEW-EXT-OP)

            foreach ($data['lines'] as $row) {

                // Hanya proses yang di-checklist
                $selected = !empty($row['selected']);
                if (!$selected) {
                    continue;
                }

                $qty = isset($row['qty']) ? (float) $row['qty'] : 0.0;
                if ($qty <= 0) {
                    continue;
                }

                $lotId = (int) $row['lot_id'];
                $unit = $row['unit'];

                // 🔹 Simpan detail dokumen ambil jahit
                $line = SewingPickLine::create([
                    'sewing_pick_id' => $header->id,
                    'wip_item_id' => $row['wip_item_id'] ?? null,
                    'lot_id' => $lotId,
                    'item_id' => $row['item_id'],
                    'item_code' => $row['item_code'],
                    'qty' => $qty,
                    'unit' => $unit,
                    'notes' => $row['notes'] ?? null,
                ]);

                // 🔹 Mutasi stok "fisik" via InventoryService:
                //    dari WIP-SEW (siap dijahit) → SEW-EXT-OP (sedang dipegang operator)
                $inventory->transfer(
                    fromWarehouseId: $fromWh,
                    toWarehouseId: $toWh,
                    lotId: $lotId,
                    qty: $qty,
                    unit: $unit,
                    refCode: $header->code,
                    note: 'Ambil jahit (line #' . $line->id . ')',
                    date: $date,
                    category: 'wip_sewing'
                );

                // 🔹 Update WIP item (sisa stok siap dijahit)
                if (!empty($row['wip_item_id'])) {
                    /** @var WipItem|null $wip */
                    $wip = WipItem::find($row['wip_item_id']);

                    if ($wip) {
                        $before = (float) $wip->qty;
                        $after = max(0, $before - $qty); // JIKA TIDAK DIAMBIL SEMUA → otomatis tersisa

                        $wip->qty = $after;

                        // kalau masih ada sisa → tetap available
                        // kalau habis → tandai sudah dipindah semua
                        if ($after <= 0) {
                            $wip->status = 'moved'; // atau 'used', bebas naming
                        } else {
                            $wip->status = 'available';
                        }

                        $wip->save();

                        // Catatan:
                        //  - after = "sisa stok siap diambil" di gudang WIP-SEW (per bundle/lot)
                        //  - stok jahit yang SDH DIAMBIL per operator bisa dilihat di inventory_stocks
                        //    untuk warehouse code = SEW-EXT-[KODE OP].
                    }
                }
            }
        });

        return redirect()
            ->route('production.sewing_picks.index')
            ->with('success', 'Dokumen ambil jahit berhasil disimpan & stok sudah terupdate (termasuk sisa stok jahit).');
    }

}
