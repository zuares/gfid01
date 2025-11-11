<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->timestamps();
        });

        // purchase headers
        Schema::create('purchase_invoices', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->date('date');
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->foreignId('warehouse_id')->constrained('warehouses'); // KONTRAKAN
            $t->string('status', 16)->default('posted'); // draft|posted
            $t->text('note')->nullable();
            $t->timestamps();
        });

        // purchase lines
        Schema::create('purchase_invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('items');
            $t->string('item_code');
            $t->decimal('qty', 12, 2);
            $t->string('unit', 16);
            $t->decimal('unit_cost', 14, 0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_lines');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('suppliers');
    }
};
