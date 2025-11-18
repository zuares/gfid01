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
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relations

    // header -> many lines
    public function lines()
    {
        return $this->hasMany(SewingPickLine::class);
    }

    // dari gudang (WIP-CUT)
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    // ke gudang (WIP-SEW / operator)
    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    // operator yang melakukan proses (optional)
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    // user yang membuat dokumen
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator()
    {return $this->belongsTo(User::class, 'created_by');}
}
