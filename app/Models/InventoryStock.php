<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'warehouse_id',
        'lot_id',
        'item_id',
        'item_code',
        'unit',
        'qty',
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    // 🔹 Relasi ke Item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // 🔹 Relasi ke Lot
    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    // (kalau mau) relasi ke Warehouse
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'stock_id');
    }
}
