<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMutation;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MutationController extends Controller
{
    public function index(Request $r)
    {
        // ==== VALIDATION ====
        $data = $r->validate([
            'item_code' => ['nullable', 'string', 'max:64'],
            'warehouse' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'in:PURCHASE_IN,CUTTING_USE,PRODUCTION_IN,TRANSFER_OUT,TRANSFER_IN,ADJUSTMENT,SALE_OUT'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
            'sort' => ['nullable', 'in:date_desc,date_asc,created_desc,created_asc'],

        ]);

        $itemCode = $data['item_code'] ?? null;
        $warehouse = $data['warehouse'] ?? null;
        $type = $data['type'] ?? null;
        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;
        $q = trim((string) ($data['q'] ?? ''));
        $perPage = $data['per_page'] ?? 30;
        $sort = $data['sort'] ?? 'date_desc';

        $hasItemCodeCol = Schema::hasColumn('inventory_mutations', 'item_code');

        // ==== BASE FILTER (TANPA SELECT & ORDER DULU) ====
        $base = DB::table('inventory_mutations as m')
            ->leftJoin('lots as l', 'm.lot_id', '=', 'l.id')
            ->leftJoin('items as it', 'l.item_id', '=', 'it.id')
            ->when($itemCode, function ($w) use ($itemCode, $hasItemCodeCol) {
                // filter efisien ke kolom yang tersedia
                if ($hasItemCodeCol) {
                    return $w->where('m.item_code', $itemCode)
                        ->orWhere(function ($x) use ($itemCode) {
                            $x->whereNull('m.item_code')
                                ->where('it.code', $itemCode);
                        });
                }
                return $w->where('it.code', $itemCode);
            })
            ->when($warehouse, fn($w) => $w->where('m.warehouse_id', $warehouse))
            ->when($type, fn($w) => $w->where('m.type', $type))
            ->when($dateFrom, fn($w) => $w->whereDate('m.date', '>=', $dateFrom))
            ->when($dateTo, fn($w) => $w->whereDate('m.date', '<=', $dateTo))
            ->when($q, fn($w) => $w->where(function ($x) use ($q) {
                $x->where('m.ref_code', 'like', "%{$q}%")
                    ->orWhere('m.note', 'like', "%{$q}%")
                    ->orWhere('it.name', 'like', "%{$q}%")
                    ->orWhere('it.code', 'like', "%{$q}%");
            }));

        // ==== KPI TOTAL (HITUNG DULU, TANPA SELECT LAIN) ====
        $kpiQuery = clone $base;
        $kpi = $kpiQuery->selectRaw('SUM(m.qty_in) as tin, SUM(m.qty_out) as tout')->first();
        $totalIn = (float) ($kpi->tin ?? 0);
        $totalOut = (float) ($kpi->tout ?? 0);

        // ==== SELECT UNTUK LIST (SETELAH KPI) ====
        $listQuery = clone $base;
        $listQuery->select([
            'm.id',
            'm.ref_code',
            'm.type',
            'm.qty_in',
            'm.qty_out',
            'm.unit',
            'm.date',
            'm.note',
            'm.warehouse_id',
            DB::raw('COALESCE(m.item_code, it.code) as item_code'),
            DB::raw('COALESCE(l.unit_cost, 0) as unit_cost'),
        ]);

        // ==== SORT ====
        // $listQuery = match ($sort) {
        //     'date_asc' => $listQuery->orderBy('m.date')->orderBy('m.id'),
        //     'created_asc' => $listQuery->orderBy('m.created_at')->orderBy('m.id'),
        //     'created_desc' => $listQuery->orderByDesc('m.created_at')->orderByDesc('m.id'),
        //     default => $listQuery->orderByDesc('m.date')->orderByDesc('m.id'),
        // };
        // Ganti total sorting menjadi hanya created_at
        $listQuery->orderByDesc('m.created_at')->orderByDesc('m.id');

        // ==== PAGINATION ====
        $rows = $listQuery->paginate($perPage)->withQueryString();

        // ==== GROUP PER TANGGAL UNTUK VIEW ====
        $grouped = [];
        foreach ($rows as $row) {
            $dateKey = \Carbon\Carbon::parse($row->date)->format('Y-m-d');
            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = ['items' => [], 'sum_in' => 0, 'sum_out' => 0, 'net' => 0];
            }
            $grouped[$dateKey]['items'][] = $row;
            $grouped[$dateKey]['sum_in'] += (float) ($row->qty_in ?? 0);
            $grouped[$dateKey]['sum_out'] += (float) ($row->qty_out ?? 0);
            $grouped[$dateKey]['net'] = $grouped[$dateKey]['sum_in'] - $grouped[$dateKey]['sum_out'];
        }

        // ==== TAMBAHAN UNTUK VIEW ====
        $itemInfo = $itemCode
        ? DB::table('items')->where('code', $itemCode)->first(['code', 'name', 'uom'])
        : null;

        $warehouses = DB::table('warehouses')->orderBy('name')->get(['id', 'code', 'name']);

        $warehouseName = null;
        if ($warehouse) {
            $wh = $warehouses->firstWhere('id', (int) $warehouse);
            if ($wh) {
                $warehouseName = "{$wh->code} — {$wh->name}";
            }

        }

        return view('inventory.mutations.index', [
            'rows' => $rows,
            'grouped' => $grouped,
            'itemInfo' => $itemInfo,
            'itemCode' => $itemCode, // untuk reuse di link
            'warehouseId' => $warehouse, // untuk reuse di link
            'warehouseName' => $warehouseName,
            'warehouses' => $warehouses,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'sort' => $sort,
            'perPage' => $perPage,
            'q' => $q,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'type' => $type,
        ]);
    }

    public function show(int $id)
    {
        $mutation = InventoryMutation::query()
            ->with([
                'warehouse:id,name,code',
                'lot:id,code,unit,unit_cost,item_id',
                'lot.item:id,code,name',
            ])
            ->findOrFail($id);

        $prev = InventoryMutation::where('id', '<', $mutation->id)
            ->orderByDesc('id')->first(['id']);
        $next = InventoryMutation::where('id', '>', $mutation->id)
            ->orderBy('id')->first(['id']);

        // Sumber invoice pembelian (jika ada)
        $purchaseSource = null;
        if ($mutation->ref_code) {
            $pi = PurchaseInvoice::where('code', $mutation->ref_code)->first(['id', 'code']);
            if ($pi) {
                $purchaseSource = [
                    'invoice_id' => $pi->id,
                    'invoice_code' => $pi->code,
                ];
            }
        }

        // Pasangan transfer (IN/OUT) dengan ref_code & lot_id sama
        $transferPartner = null;
        if (in_array($mutation->type, ['TRANSFER_IN', 'TRANSFER_OUT'], true) && $mutation->ref_code) {
            $pair = InventoryMutation::query()
                ->where('ref_code', $mutation->ref_code)
                ->where('lot_id', $mutation->lot_id)
                ->where('id', '!=', $mutation->id)
                ->with('warehouse:id,name,code')
                ->get(['id', 'warehouse_id', 'type']);

            if ($pair->isNotEmpty()) {
                $from = $mutation->type === 'TRANSFER_IN'
                ? $pair->firstWhere('type', 'TRANSFER_OUT')?->warehouse?->name
                : $mutation->warehouse?->name;

                $to = $mutation->type === 'TRANSFER_OUT'
                ? $pair->firstWhere('type', 'TRANSFER_IN')?->warehouse?->name
                : $mutation->warehouse?->name;

                if ($from || $to) {
                    $transferPartner = ['from' => $from ?? '—', 'to' => $to ?? '—'];
                }
            }
        }

        // Riwayat mutasi untuk LOT yang sama (kronologis)
        $lotHistory = null;
        if ($mutation->lot_id) {
            $lotHistory = InventoryMutation::query()
                ->where('lot_id', $mutation->lot_id)
                ->orderBy('date')->orderBy('id')
                ->with('warehouse:id,name,code')
                ->get(['id', 'warehouse_id', 'type', 'qty_in', 'qty_out', 'unit', 'date', 'lot_id']);
        }

        return view('inventory.mutations.show', compact(
            'mutation', 'prev', 'next', 'purchaseSource', 'transferPartner', 'lotHistory'
        ));
    }
}
