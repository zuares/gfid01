<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceLine extends Model
{
    protected $fillable = [
        'purchase_invoice_id', 'item_id', 'item_code', 'qty', 'unit', 'unit_cost',
    ];

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // === Harga terakhir untuk kombinasi supplier+item ===
    // Kembalikan baris terakhir berdasarkan tanggal invoice & id
    public function scopeLastPrice($q, int $supplierId, int $itemId)
    {
        return $q->where('item_id', $itemId)
            ->whereHas('invoice', fn($w) => $w->where('supplier_id', $supplierId))
            ->orderByDesc(
                DB::raw("(select date from purchase_invoices where purchase_invoices.id = purchase_invoice_lines.purchase_invoice_id)")
            )
            ->orderByDesc('id');
    }

}
