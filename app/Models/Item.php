<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'code', 'name', 'uom', 'type', // type: material|finished|pendukung
    ];

    public function scopeType($q, $type)
    {
        return $q->where('type', $type);
    }

    public function purchaseLines()
    {
        return $this->hasMany(PurchaseInvoiceLine::class, 'item_id');
    }
}
