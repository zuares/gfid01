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
        'employee_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'notes',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];

    // ===== RELASI =====

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
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
        return $this->hasMany(FinishingLine::class, 'finishing_job_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
