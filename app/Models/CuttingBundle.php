<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuttingBundle extends Model
{
    use HasFactory;

    protected $table = 'cutting_bundles';

    protected $fillable = [
        'production_batch_id',
        'lot_id',
        'item_id',
        'item_code',
        'bundle_code',
        'bundle_no',
        'qty_cut',
        'qty_ok', // ⬅ TAMBAH
        'qty_reject', // ⬅ TAMBAH
        'unit',
        'status',
        'notes',
    ];

    protected $casts = [
        'qty_cut' => 'decimal:2',
        'qty_ok' => 'decimal:2',
        'qty_reject' => 'decimal:2',
    ];

    /* =====================
     * RELASI
     * ===================== */

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function sewingProgress()
    {
        return $this->hasOne(SewingBundleProgress::class);
    }

}
