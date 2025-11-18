<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingJobLine extends Model
{
    protected $fillable = [
        'packing_job_id',
        'sewing_return_line_id',
        'item_id',
        'item_code',
        'lot_id',
        'qty',
        'unit',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    // ----------------------
    // RELATIONS
    // ----------------------

    public function packingJob(): BelongsTo
    {
        return $this->belongsTo(PackingJob::class);
    }

    public function sewingReturnLine(): BelongsTo
    {
        return $this->belongsTo(SewingReturnLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
