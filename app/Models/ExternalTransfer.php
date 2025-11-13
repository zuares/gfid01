<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalTransfer extends Model
{
    protected $table = 'external_transfers';

    protected $fillable = [
        'code',
        'process', // cutting / sewing
        'operator_code',
        'from_warehouse_id',
        'to_warehouse_id',
        'date',
        'status', // draft, sent, partially_received, received, posted, canceled
        'material_value_est',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(ExternalTransferLine::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ExternalReceipt::class);
    }
}
