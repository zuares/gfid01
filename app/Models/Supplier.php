<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'code', 'name', 'phone', 'address',
    ];

    // Relasi pembelian (header)
    public function purchases()
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    // Harga terakhir per item (helper via query, lihat scope di PurchaseInvoiceLine)
}
