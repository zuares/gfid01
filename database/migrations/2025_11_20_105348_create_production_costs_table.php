<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_costs', function (Blueprint $t) {
            $t->id();

            // 🔑 Kunci ke LOT atau ITEM (boleh salah satu diisi, atau dua-duanya)
            $t->unsignedBigInteger('lot_id')->nullable(); // kalau mau HPP per LOT kain
            $t->unsignedBigInteger('item_id')->nullable(); // kalau mau HPP per item FG

            // Tahap produksi: raw_material / cutting / sewing / finishing / other
            $t->string('stage', 32);

            // Qty dasar yang jadi pembagi HPP (kg/pcs)
            $t->decimal('qty_base', 18, 4)->default(0);

            // Total biaya di tahap ini
            $t->decimal('amount', 18, 2)->default(0);

            // Biaya per unit (amount / qty_base), boleh diisi atau dihitung dinamis
            $t->decimal('cost_per_unit', 18, 6)->default(0);

            // Sumber referensi biaya (invoice, vendor cutting, payroll, finishing, dll)
            $t->string('source_type', 64)->nullable(); // 'purchase_invoice', 'vendor_cutting', 'payroll_sewing', ...
            $t->unsignedBigInteger('source_id')->nullable();

            $t->text('notes')->nullable();

            $t->timestamps();

            // INDEX
            $t->index(['lot_id', 'stage']);
            $t->index(['item_id', 'stage']);
            $t->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_costs');
    }
};
