<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingReturn extends Model
{
    protected $fillable = [
        'code',
        'date',
        'operator_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'total_ok_qty',
        'total_reject_qty',
        'notes',
        'created_by',
        'posted_at',
        'sewing_rate', // ⬅️ tambah
        'sewing_fee', // ⬅️ tambah
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];

    // 🔹 Relasi

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
        return $this->hasMany(SewingReturnLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔹 Helper: total qty by accessor (kalau mau pakai hitungan on-the-fly)
    public function getTotalOkAttribute(): float
    {
        return (float) $this->lines->sum('qty_ok');
    }

    public function getTotalRejectAttribute(): float
    {
        return (float) $this->lines->sum('qty_reject');
    }

    public function getTotalAllAttribute(): float
    {
        return $this->total_ok + $this->total_reject;
    }
}
