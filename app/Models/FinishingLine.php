<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinishingLine extends Model
{
    use HasFactory;

    protected $table = 'finishing_lines';

    protected $fillable = [
        'finishing_job_id',
        'item_id',
        'item_code',
        'qty_wip',
        'qty_ok',
        'qty_reject',
        'unit',
        'fg_item_id',
        'fg_item_code',
        'notes',
    ];

    protected $casts = [
        'qty_wip' => 'decimal:2',
        'qty_ok' => 'decimal:2',
        'qty_reject' => 'decimal:2',
    ];

    // ===== RELASI =====

    public function job()
    {
        return $this->belongsTo(FinishingJob::class, 'finishing_job_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function fgItem()
    {
        return $this->belongsTo(Item::class, 'fg_item_id');
    }
}
