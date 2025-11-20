<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\HppService;
use Illuminate\Http\Request;

class ItemHppController extends Controller
{
    public function show(Request $request, Item $item, HppService $hpp)
    {
        $summary = $hpp->getItemCostSummary($item->id);

        return view('reports.item_hpp.show', [
            'item' => $item,
            'summary' => $summary,
        ]);
    }
}
