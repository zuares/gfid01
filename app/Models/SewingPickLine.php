<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SewingPickLine extends Model
{
    use HasFactory;

    protected $table = 'sewing_pick_lines';

    protected $fillable = [
        'sewing_pick_id',
        'bundle_id',
        'item_id',
        'item_code',
        'qty',
        'unit',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    /* ==========
     * RELASI
     * ========== */

    public function sewingPick()
    {
        return $this->belongsTo(SewingPick::class, 'sewing_pick_id');
    }

    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'stock_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    // 🔥 WAJIB ADA! Untuk total OK dan Reject.
    public function sewingReturns()
    {
        return $this->hasMany(SewingReturnLine::class, 'sewing_pick_line_id');
    }

    public function bundle()
    {
        return $this->belongsTo(CuttingBundle::class, 'bundle_id');
    }
}
