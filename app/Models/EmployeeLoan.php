<?php

// app/Models/EmployeeLoan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id', 'amount', 'description', 'date', 'status',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
