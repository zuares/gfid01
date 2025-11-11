<?php

namespace App\Models;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'code', 'date', 'supplier_id', 'note', 'warehouse_id', 'status', // status: draft|posted
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function lines()
    {
        return $this->hasMany(PurchaseInvoiceLine::class, 'purchase_invoice_id');
    }

    // scope pencarian
    public function scopeQ($q, $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }

        return $q->where(function ($w) use ($term) {
            $w->where('code', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$term}%"));
        });
    }
}
