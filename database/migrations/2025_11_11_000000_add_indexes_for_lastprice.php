<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table: purchase_invoices
        Schema::table('purchase_invoices', function (Blueprint $t) {
            // Index gabungan supplier + date → untuk filter cepat by supplier
            $t->index(['supplier_id', 'date'], 'idx_purchase_invoices_supplier_date');
        });

        // Table: purchase_invoice_lines
        Schema::table('purchase_invoice_lines', function (Blueprint $t) {
            // Index item_id → untuk scopeLastPrice() & history()
            $t->index('item_id', 'idx_pil_item_id');

            // Index gabungan item_id + purchase_invoice_id → join cepat
            $t->index(['item_id', 'purchase_invoice_id'], 'idx_pil_item_invoice');

            // Index unit_cost → optional, bantu sorting jika query lastprice diubah pakai max()
            $t->index('unit_cost', 'idx_pil_unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $t) {
            $t->dropIndex('idx_purchase_invoices_supplier_date');
        });

        Schema::table('purchase_invoice_lines', function (Blueprint $t) {
            $t->dropIndex('idx_pil_item_id');
            $t->dropIndex('idx_pil_item_invoice');
            $t->dropIndex('idx_pil_unit_cost');
        });
    }
};
