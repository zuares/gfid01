<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    /**
     * Report posisi stok per gudang.
     */
    public function index(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_type' => ['nullable', 'string', 'max:32'], // raw, wip_sewing, wip_fin, fg, external_sew
            'item_code' => ['nullable', 'string', 'max:64'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);

        $warehouseId = $data['warehouse_id'] ?? null;
        $warehouseType = $data['warehouse_type'] ?? null;
        $itemCode = $data['item_code'] ?? null;
        $q = $data['q'] ?? null;
        $perPage = $data['per_page'] ?? 50;

        // daftar gudang untuk filter dropdown
        $warehouses = Warehouse::orderBy('code')->get();

        $query = InventoryStock::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')
            ->join('items', 'items.id', '=', 'inventory_stocks.item_id')
            ->select(
                'inventory_stocks.*',
                'warehouses.code as wh_code',
                'warehouses.name as wh_name',
                'items.code as item_code',
                'items.name as item_name'
            )
            ->where('inventory_stocks.qty', '!=', 0)
            ->orderBy('wh_code')
            ->orderBy('items.code')
            ->orderBy('inventory_stocks.unit');

        // filter gudang spesifik
        if ($warehouseId) {
            $query->where('inventory_stocks.warehouse_id', $warehouseId);
        }

        // filter kategori gudang berbasis prefix code
        if ($warehouseType) {
            $query->where(function ($sub) use ($warehouseType) {
                switch ($warehouseType) {
                    case 'raw':
                    case 'rawmaterial':
                        $sub->where('warehouses.code', 'like', 'RAW%');
                        break;

                    case 'wip_sewing':
                        $sub->where('warehouses.code', 'like', 'WIP-SEW%');
                        break;

                    case 'wip_fin':
                        $sub->where('warehouses.code', 'like', 'WIP-FIN%');
                        break;

                    case 'fg':
                        $sub->where('warehouses.code', 'like', 'FG%');
                        break;

                    case 'external_sew':
                        // gudang operator jahit, misal SEW-EXT-EMPxxx
                        $sub->where('warehouses.code', 'like', 'SEW-EXT-%');
                        break;

                    default:
                        // kategori lain, nggak difilter
                        break;
                }
            });
        }

        if ($itemCode) {
            $query->where('items.code', $itemCode);
        }

        if ($q) {
            $qLike = '%' . $q . '%';
            $query->where(function ($sub) use ($qLike) {
                $sub->where('items.code', 'like', $qLike)
                    ->orWhere('items.name', 'like', $qLike)
                    ->orWhere('warehouses.code', 'like', $qLike)
                    ->orWhere('warehouses.name', 'like', $qLike);
            });
        }

        $stocks = $query->paginate($perPage)->withQueryString();

        // label kategori buat di view (kita mapping berdasarkan prefix wh_code di Blade)
        $categoryMap = [
            'raw' => 'Raw Material',
            'wip_sewing' => 'WIP Sewing',
            'wip_fin' => 'WIP Finishing',
            'fg' => 'Finished Goods',
            'external_sew' => 'Gudang Operator Jahit',
            'other' => 'Lainnya',
        ];

        return view('inventory.stock_position.index', [
            'stocks' => $stocks,
            'warehouses' => $warehouses,
            'warehouseId' => $warehouseId,
            'warehouseType' => $warehouseType,
            'itemCode' => $itemCode,
            'q' => $q,
            'perPage' => $perPage,
            'categoryMap' => $categoryMap,
        ]);
    }
}
