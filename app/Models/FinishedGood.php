<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinishedGood extends Model
{
    use HasFactory;

    protected $table = 'finished_goods';

    protected $fillable = [
        'production_batch_id',
        'item_id',
        'item_code',
        'warehouse_id',
        'source_lot_id',
        'qty',
        'variant',
        'notes',
        'lot_id',
        'unit',
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
     */

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function sourceLot()
    {
        return $this->belongsTo(Lot::class, 'source_lot_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS / HELPERS
    |--------------------------------------------------------------------------
     */

    public function getDisplayLabelAttribute(): string
    {
        $code = $this->item_code;
        $qty = number_format($this->qty, 2);
        $wh = $this->warehouse?->code ?? '-';

        return "{$code} • {$qty} pcs @ {$wh}";
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

}
