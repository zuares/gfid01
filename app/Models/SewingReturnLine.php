<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingReturnLine extends Model
{
    protected $fillable = [
        'sewing_return_id',
        'sewing_pick_line_id',
        'stock_id',
        'item_id',
        'item_code',
        'qty_ok',
        'qty_reject',
        'unit',
        'notes',
    ];

    // 🔹 Relasi

    public function sewingReturn()
    {
        return $this->belongsTo(SewingReturn::class);
    }

    public function sewingPickLine()
    {
        return $this->belongsTo(SewingPickLine::class, 'sewing_pick_line_id');
    }

    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'stock_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
