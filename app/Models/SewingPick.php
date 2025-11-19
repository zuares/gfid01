<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SewingPick extends Model
{
    use HasFactory;

    protected $table = 'sewing_picks';

    protected $fillable = [
        'code',
        'date',
        'operator_id',
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

    /* ==========
     * RELASI
     * ========== */

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
        return $this->hasMany(SewingPickLine::class, 'sewing_pick_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
