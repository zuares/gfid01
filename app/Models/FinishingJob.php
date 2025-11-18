<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinishingJob extends Model
{
    use HasFactory;

    protected $table = 'finishing_jobs';

    protected $fillable = [
        'code',
        'date',
        'operator_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'notes',
        'status',
        'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];

    // Relasi
    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function lines()
    {
        return $this->hasMany(FinishingJobLine::class, 'finishing_job_id');
    }

    // Optional: helper label status
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'posted' => 'Posted',
            default => 'Draft',
        };
    }
}
