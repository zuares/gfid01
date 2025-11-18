<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinishingJobLine extends Model
{
    use HasFactory;

    protected $table = 'finishing_job_lines';

    protected $fillable = [
        'finishing_job_id',
        'lot_id',
        'item_id',
        'item_code',
        'qty_source',
        'qty_ok',
        'qty_reject',
        'unit',
        'notes',
    ];

    protected $casts = [
        'qty_source' => 'float',
        'qty_ok' => 'float',
        'qty_reject' => 'float',
    ];

    public function job()
    {
        return $this->belongsTo(FinishingJob::class, 'finishing_job_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
