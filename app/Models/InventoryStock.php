<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $table = 'inventory_stocks';
    protected $fillable = ['warehouse_id', 'lot_id', 'qty', 'unit'];

    public function warehouse()
    {return $this->belongsTo(Warehouse::class);}
    public function lot()
    {return $this->belongsTo(Lot::class);}
}
