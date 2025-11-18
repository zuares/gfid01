<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackingJob extends Model
{
    protected $fillable = [
        'code',
        'date',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ----------------------
    // RELATIONS
    // ----------------------

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PackingJobLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ----------------------
    // ACCESSORS / HELPERS
    // ----------------------

    public function getTotalQtyAttribute(): float
    {
        return (float) $this->lines->sum('qty');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}
