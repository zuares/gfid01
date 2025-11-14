<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalTransferLine extends Model
{
    use HasFactory;

    protected $table = 'external_transfer_lines';

    protected $fillable = [
        'external_transfer_id',
        'lot_id',
        'item_id',
        'qty',
        'uom',
        'notes',
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
     */

    public function header()
    {
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }

    public function transfer()
    {
        // alias lain biar enak dipanggil: $line->transfer
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }

    public function lot()
    {
        // ini yang lagi dipanggil: lines.lot
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function item()
    {
        // ini yang bisa dipakai: lines.item
        return $this->belongsTo(Item::class, 'item_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS / HELPERS
    |--------------------------------------------------------------------------
     */

    public function getDisplayNameAttribute(): string
    {
        $itemCode = $this->item?->code ?? '';
        $lotCode = $this->lot?->code ?? '';
        return trim($itemCode . ' • ' . $lotCode);
    }
}
