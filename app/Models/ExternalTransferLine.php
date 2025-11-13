<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalTransferLine extends Model
{
    protected $table = 'external_transfer_lines';

    protected $fillable = [
        'external_transfer_id',
        'lot_id',
        'item_id',
        'qty',
        'unit',
        'received_qty',
        'defect_qty',
        'note',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }
}
