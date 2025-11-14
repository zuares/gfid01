<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRunLine extends Model
{
    use HasFactory;

    protected $table = 'payroll_run_lines';

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'process',
        'item_id',
        'total_pcs',
        'rate_per_piece',
        'amount',
        'bonus_amount',
        'deduction_amount',
        'total_payable',
        'batch_count',
        'details_json',
    ];

    protected $casts = [
        'total_pcs' => 'integer',
        'rate_per_piece' => 'float',
        'amount' => 'float',
        'bonus_amount' => 'float',
        'deduction_amount' => 'float',
        'total_payable' => 'float',
        'batch_count' => 'integer',
        'details_json' => 'array',
    ];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
