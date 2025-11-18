<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WipItem extends Model
{
    use HasFactory;

    protected $table = 'wip_items';

    protected $fillable = [
        'production_batch_id',
        'item_id',
        'item_code',
        'stage',
        'qty',
        'unit',
        'warehouse_id',
        'status',
        'notes',
        'cutting_bundle_id',
        'lot_id',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
    ];

    /* ============
     * RELATION
     * ============ */

    public function batch()
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

    /* ============
     * SCOPES
     * ============ */

    public function scopeCutting($q)
    {
        return $q->where('stage', 'cutting');
    }

    public function scopeAvailable($q)
    {
        return $q->where('status', 'available');
    }
}
