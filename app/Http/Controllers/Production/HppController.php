<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Lot;
use App\Services\HppService;
use Illuminate\Http\Request;

class HppController extends Controller
{
    public function index(Request $request, HppService $hpp)
    {
        $q = $request->input('q');
        $type = $request->input('type', 'finished'); // default: hanya item finished

        $itemsQuery = Item::query();

        if ($type === 'finished') {
            $itemsQuery->where('type', 'finished');
        }

        if ($q) {
            $itemsQuery->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }

        $items = $itemsQuery
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        $summaries = [];
        foreach ($items as $item) {
            $summaries[$item->id] = $hpp->getItemCostSummary($item->id);
        }

        return view('production.hpp.index', [
            'items' => $items,
            'summaries' => $summaries,
            'q' => $q,
            'type' => $type,
        ]);
    }

    /**
     * DETAIL HPP per ITEM (final)
     */
    public function showItem(Item $item, HppService $hpp)
    {
        // $item->loadMissing(); // jaga-jaga kalau ada relasi standar

        $summary = $hpp->getItemCostSummary($item->id);

        return view('production.hpp.item.show', [
            'item' => $item,
            'summary' => $summary,
        ]);
    }

    /**
     * DETAIL HPP per LOT (raw + cutting, dll)
     */
    public function showLot(Lot $lot, HppService $hpp)
    {
        $lot->loadMissing('item');

        $summary = $hpp->getLotCostSummary($lot->id);

        // breakdown stok LOT per gudang
        $stocks = InventoryStock::with('warehouse')
            ->where('lot_id', $lot->id)
            ->orderBy('warehouse_id')
            ->get();

        return view('production.hpp.lot.show', [
            'lot' => $lot,
            'summary' => $summary,
            'stocks' => $stocks,
        ]);
    }
}
