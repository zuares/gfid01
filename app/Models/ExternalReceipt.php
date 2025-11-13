<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalReceipt extends Model
{
    protected $table = 'external_receipts';

    protected $fillable = [
        'external_transfer_id',
        'code',
        'date',
        'status', // draft / posted
        'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExternalReceiptLine::class);
    }
}
