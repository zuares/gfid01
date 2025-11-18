<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingReturn extends Model
{
    protected $table = 'sewing_returns';

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

    // Jika kamu pakai timestamps, biarkan true
    public $timestamps = true;
    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
     */

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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(SewingReturnLine::class, 'sewing_return_id');
    }
}
