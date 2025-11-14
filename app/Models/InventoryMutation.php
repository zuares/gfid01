<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMutation extends Model
{
    protected $table = 'inventory_mutations';
    protected $fillable = [
        'warehouse_id',
        'lot_id',
        'item_id',
        'item_code',
        'type',
        'qty_in',
        'qty_out',
        'unit',
        'ref_code',
        'note',
        'date',
    ];

    protected $casts = ['date' => 'datetime'];

    public function warehouse()
    {return $this->belongsTo(Warehouse::class);}
    public function lot()
    {return $this->belongsTo(Lot::class);}
}
