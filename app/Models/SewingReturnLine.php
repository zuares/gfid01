<?php

namespace App\Models;

use App\Models\Item;
use App\Models\Lot;
use App\Models\SewingPickLine;
use App\Models\SewingReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewingReturnLine extends Model
{
    protected $table = 'sewing_return_lines';

    protected $fillable = [
        'sewing_return_id',
        'sewing_pick_line_id', // kalau kolom ini ada
        'item_id',
        'item_code',
        'lot_id',
        'qty_ok',
        'qty_reject',
        'unit',
        'notes',
        'packed_qty',
    ];

    protected $casts = [
        'qty_ok' => 'decimal:2',
        'qty_reject' => 'decimal:2',
        'packed_qty' => 'decimal:2',
        'last_packed_at' => 'datetime',
    ];

    // 🔹 SISA YANG BELUM DIPACKING
    public function getRemainingToPackAttribute(): float
    {
        return max(0, (($this->qty_ok ?? 0) - ($this->packed_qty ?? 0)));
    }

    // ==========================
    // RELATIONS
    // ==========================

    // Header: SewingReturn
    public function sewingReturn(): BelongsTo
    {
        return $this->belongsTo(SewingReturn::class);
    }

    // (opsional) ke baris ambil jahit, kalau kamu pakai
    public function sewingPickLine(): BelongsTo
    {
        return $this->belongsTo(SewingPickLine::class);
    }

    // Item master (K7BLK, dsb.)
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // LOT kain / lot produksi
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
