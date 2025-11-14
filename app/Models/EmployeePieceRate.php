<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePieceRate extends Model
{
    use HasFactory;

    protected $table = 'employee_piece_rates';

    protected $fillable = [
        'employee_id',
        'process',
        'item_id',
        'rate_per_piece',
        'effective_from',
        'effective_to',
        'active',
    ];

    protected $casts = [
        'rate_per_piece' => 'float',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }
}
