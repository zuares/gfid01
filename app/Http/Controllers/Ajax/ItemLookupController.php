<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemLookupController extends Controller
{
    /**
     * AJAX search barang jadi (finished goods) untuk autocomplete.
     * Filter:
     *  - items.type = 'finished'
     *  - cari by code / name
     */
    public function searchFinished(Request $request)
    {
        $term = trim($request->get('q', ''));

        $query = DB::table('items')
            ->select('id', 'code', 'name', 'uom')
            ->where('type', 'finished');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', '%' . $term . '%')
                    ->orWhere('name', 'like', '%' . $term . '%');
            });
        }

        $items = $query
            ->orderBy('code')
            ->limit(20)
            ->get();

        // Format respons JSON sederhana
        return response()->json($items);
    }
}
