<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // saldo per gudang per LOT
        Schema::create('inventory_stocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->foreignId('lot_id')->constrained('lots');
            $t->decimal('qty', 12, 2)->default(0);
            $t->string('unit', 16)->default('kg');
            $t->timestamps();
            $t->unique(['warehouse_id', 'lot_id']);
        });

        // jejak mutasi (ledger)
        Schema::create('inventory_mutations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->foreignId('lot_id')->constrained('lots');
            $t->string('ref_code')->nullable(); // INV-..., CUT-..., SJH-..., TRF-...
            $t->enum('type', [
                'PURCHASE_IN',
                'CUTTING_USE',
                'PRODUCTION_IN',
                'TRANSFER_OUT',
                'TRANSFER_IN',
                'ADJUSTMENT',
                'SALE_OUT',
            ]);
            $t->decimal('qty_in', 12, 2)->default(0);
            $t->decimal('qty_out', 12, 2)->default(0);
            $t->string('unit', 16);
            $t->dateTime('date');
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_mutations');
        Schema::dropIfExists('inventory_stocks');
    }
};
