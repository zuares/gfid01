<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    use HasFactory;

    protected $table = 'production_batches';

    protected $fillable = [
        'code',
        'date',
        'process',
        'status',
        'external_transfer_id',
        'lot_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'operator_code',
        'input_qty',
        'input_uom',
        'output_total_pcs',
        'output_items_json',
        'waste_qty',
        'remain_qty',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'input_qty' => 'float',
        'output_total_pcs' => 'integer',
        'waste_qty' => 'float',
        'remain_qty' => 'float',
        'output_items_json' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
     */

    public function externalTransfer()
    {
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }

    public function lot()
    {
        // Untuk cutting, ini LOT kain utama
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS / HELPERS
    |--------------------------------------------------------------------------
     */

    public function isCutting(): bool
    {
        return $this->process === 'cutting';
    }

    public function isSewing(): bool
    {
        return $this->process === 'sewing';
    }

    public function isFinishing(): bool
    {
        return $this->process === 'finishing';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function getOutputItemsListAttribute()
    {
        // Helper kecil untuk tampilan: kumpulan item hasil cutting
        $items = $this->output_items_json ?? [];
        // contoh return: "K7BLK: 300 pcs, K5BLK: 150 pcs"
        $parts = [];
        foreach ($items as $code => $qty) {
            $parts[] = "{$code}: {$qty} pcs";
        }
        return implode(', ', $parts);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
     */

    public function scopeCutting($q)
    {
        return $q->where('process', 'cutting');
    }

    public function scopeSewing($q)
    {
        return $q->where('process', 'sewing');
    }

    public function scopeFinishing($q)
    {
        return $q->where('process', 'finishing');
    }

    public function scopeForProcess($q, string $process)
    {
        return $q->where('process', $process);
    }

    public function scopeStatus($q, string $status)
    {
        return $q->where('status', $status);
    }
}
