<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    /** Halaman utama stok per item */
    public function index(Request $r)
    {
        $data = $r->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'warehouse' => ['nullable', 'integer', 'min:1'],
            'min_qty' => ['nullable', 'numeric'],
            'only_positive' => ['nullable', 'in:on,1,true'],
            'sort' => ['nullable', 'in:qty_desc,qty_asc,updated_desc,updated_asc,item_code,item_name'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
            'export' => ['nullable', 'in:csv'],
        ]);

        $q = trim((string) ($data['q'] ?? ''));
        $warehouse = $data['warehouse'] ?? null;
        $sort = $data['sort'] ?? 'qty_desc';
        $perPage = $data['per_page'] ?? 30;
        $onlyPositive = isset($data['only_positive']);
        $minQty = $onlyPositive ? 0.0001 : ($data['min_qty'] ?? null);
        $export = $data['export'] ?? null;

        $aggBase = $this->baseAgg($q, $warehouse, $minQty);
        $listQuery = DB::query()->fromSub($aggBase, 't');
        $listQuery = $this->applySort($listQuery, $sort);

        // === EXPORT CSV ===
        if ($export === 'csv') {
            $rows = $listQuery->get(['item_code', 'item_name', 'total_qty', 'unit', 'last_updated']);
            $filename = 'inventory_per_item_' . date('Ymd_His') . '.csv';

            return new StreamedResponse(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Item Code', 'Item Name', 'Total Qty', 'Unit', 'Last Updated']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->item_code,
                        $r->item_name,
                        number_format((float) $r->total_qty, 2, '.', ''),
                        $r->unit,
                        $r->last_updated,
                    ]);
                }
                fclose($out);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ]);
        }

        // === PAGINATION + KPI ===
        $rows = $listQuery->paginate($perPage)->withQueryString();
        $kpi = [
            'total_item' => $rows->total(),
            'total_qty' => (float) DB::query()->fromSub($aggBase, 'x')->sum('total_qty'),
        ];
        $warehouses = DB::table('warehouses')->orderBy('name')->get(['id', 'code', 'name']);

        return view('inventory.stocks.index', compact(
            'rows', 'q', 'warehouse', 'sort', 'perPage', 'kpi', 'warehouses', 'onlyPositive'
        ));
    }

    /** Subquery agregasi stok per item */
    private function baseAgg(?string $q, ?int $warehouse, ?float $minQty)
    {
        $useItemCode = Schema::hasColumn('inventory_stocks', 'item_code');

        $base = $useItemCode
        ? DB::table('inventory_stocks')
            ->join('items', 'inventory_stocks.item_code', '=', 'items.code')
            ->when($warehouse, fn($w) => $w->where('inventory_stocks.warehouse_id', $warehouse))
            ->when($q, fn($w) => $w->where(fn($x) => $x
                    ->where('items.code', 'like', "%{$q}%")
                    ->orWhere('items.name', 'like', "%{$q}%")))
            ->groupBy('items.code', 'items.name')
            ->selectRaw('items.code AS item_code, items.name AS item_name')
            ->selectRaw('SUM(inventory_stocks.qty) AS total_qty')
            ->selectRaw('MAX(inventory_stocks.updated_at) AS last_updated')
            ->selectRaw('MIN(inventory_stocks.unit) AS unit')
        : DB::table('inventory_stocks')
            ->join('lots', 'inventory_stocks.lot_id', '=', 'lots.id')
            ->join('items', 'lots.item_id', '=', 'items.id')
            ->when($warehouse, fn($w) => $w->where('inventory_stocks.warehouse_id', $warehouse))
            ->when($q, fn($w) => $w->where(fn($x) => $x
                    ->where('items.code', 'like', "%{$q}%")
                    ->orWhere('items.name', 'like', "%{$q}%")))
            ->groupBy('items.code', 'items.name')
            ->selectRaw('items.code AS item_code, items.name AS item_name')
            ->selectRaw('SUM(inventory_stocks.qty) AS total_qty')
            ->selectRaw('MAX(inventory_stocks.updated_at) AS last_updated')
            ->selectRaw('MIN(inventory_stocks.unit) AS unit');

        if ($minQty !== null) {
            $base->having('total_qty', '>=', $minQty);
        }

        return $base;
    }

    /** Sorting helper */
    private function applySort($query, string $sort)
    {
        return match ($sort) {
            'qty_asc' => $query->orderBy('total_qty')->orderBy('item_code'),
            'updated_desc' => $query->orderByDesc('last_updated')->orderBy('item_code'),
            'updated_asc' => $query->orderBy('last_updated')->orderBy('item_code'),
            'item_name' => $query->orderBy('item_name'),
            'item_code' => $query->orderBy('item_code'),
            default => $query->orderByDesc('total_qty')->orderBy('item_code'),
        };
    }

    /** Breakdown per gudang + total mutasi IN/OUT */
    public function warehousesBreakdown(Request $r, string $itemCode)
    {
        $useItemCode = Schema::hasColumn('inventory_stocks', 'item_code');
        $unit = $r->get('unit');

        if ($useItemCode) {
            $rows = DB::table('inventory_stocks')
                ->join('warehouses', 'inventory_stocks.warehouse_id', '=', 'warehouses.id')
                ->where('inventory_stocks.item_code', $itemCode)
                ->when($unit, fn($q) => $q->where('inventory_stocks.unit', $unit))
                ->groupBy('warehouses.id', 'warehouses.code', 'warehouses.name')
                ->selectRaw('warehouses.id as wh_id, warehouses.code as wh_code, warehouses.name as wh_name')
                ->selectRaw('SUM(inventory_stocks.qty) AS qty')
                ->selectRaw('MIN(inventory_stocks.unit) AS unit')
                ->selectRaw('MAX(inventory_stocks.updated_at) AS last_updated')
                ->get();
        } else {
            $rows = DB::table('inventory_stocks')
                ->join('warehouses', 'inventory_stocks.warehouse_id', '=', 'warehouses.id')
                ->join('lots', 'inventory_stocks.lot_id', '=', 'lots.id')
                ->join('items', 'lots.item_id', '=', 'items.id')
                ->where('items.code', $itemCode)
                ->when($unit, fn($q) => $q->where('inventory_stocks.unit', $unit))
                ->groupBy('warehouses.id', 'warehouses.code', 'warehouses.name')
                ->selectRaw('warehouses.id as wh_id, warehouses.code as wh_code, warehouses.name as wh_name')
                ->selectRaw('SUM(inventory_stocks.qty) AS qty')
                ->selectRaw('MIN(inventory_stocks.unit) AS unit')
                ->selectRaw('MAX(inventory_stocks.updated_at) AS last_updated')
                ->get();
        }

        // === Tambah total mutasi IN / OUT per gudang
        foreach ($rows as $row) {
            $mut = DB::table('inventory_mutations')
                ->when($useItemCode,
                    fn($q) => $q->where('item_code', $itemCode),
                    fn($q) => $q->join('lots', 'inventory_mutations.lot_id', '=', 'lots.id')
                        ->join('items', 'lots.item_id', '=', 'items.id')
                        ->where('items.code', $itemCode))
                ->where('warehouse_id', $row->wh_id)
                ->selectRaw('SUM(qty_in) AS in_qty, SUM(qty_out) AS out_qty')
                ->first();

            $row->in_qty = (float) ($mut->in_qty ?? 0);
            $row->out_qty = (float) ($mut->out_qty ?? 0);
        }

        $kontrakan = $rows->filter(fn($x) => str_contains(mb_strtolower($x->wh_name), 'kontrakan'));
        $others = $rows->reject(fn($x) => str_contains(mb_strtolower($x->wh_name), 'kontrakan'));

        return view('inventory.stocks.partials.breakdown', compact('itemCode', 'kontrakan', 'others'))->render();
    }
}
