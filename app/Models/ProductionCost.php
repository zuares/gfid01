<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCost extends Model
{
    use HasFactory;

    protected $table = 'production_costs';

    protected $fillable = [
        'lot_id',
        'item_id',
        'stage',
        'qty_base',
        'amount',
        'cost_per_unit',
        'source_type',
        'source_id',
        'notes',
    ];

    protected $casts = [
        'qty_base' => 'decimal:4',
        'amount' => 'decimal:2',
        'cost_per_unit' => 'decimal:6',
    ];

    /* ==========
     * RELASI
     * ========== */

    // LOT kain (kalau kamu mau HPP per LOT)
    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    // Item (bisa bahan atau FG, tergantung yang kamu isi)
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // Sumber biaya (bisa morph ke PurchaseInvoice, VendorCutting, Payroll, FinishingJob, dll)
    public function source()
    {
        return $this->morphTo(null, 'source_type', 'source_id');
    }
}
