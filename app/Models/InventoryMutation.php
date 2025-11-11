<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMutation extends Model
{
    protected $table = 'inventory_mutations';
    protected $fillable = [
        'warehouse_id', 'lot_id', 'ref_code', 'type', 'qty_in', 'qty_out', 'unit', 'date', 'note',
    ];

    protected $casts = ['date' => 'datetime'];

    public function warehouse()
    {return $this->belongsTo(Warehouse::class);}
    public function lot()
    {return $this->belongsTo(Lot::class);}
}
