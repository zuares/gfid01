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
            $t->unsignedBigInteger('warehouse_id');
            $t->unsignedBigInteger('lot_id');
            $t->unsignedBigInteger('item_id');
            $t->string('item_code');
            $t->string('unit', 16);
            $t->decimal('qty', 18, 4)->default(0);
            $t->timestamps();

            $t->unique(['warehouse_id', 'lot_id', 'unit']); // 1 gudang, 1 lot, 1 unit
        });

        // jejak mutasi (ledger)
        Schema::create('inventory_mutations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('warehouse_id');
            $t->unsignedBigInteger('lot_id');
            $t->unsignedBigInteger('item_id');
            $t->string('item_code');
            $t->string('type'); // PURCHASE_IN, CUTTING_OUT, etc.
            $t->decimal('qty_in', 18, 4)->default(0);
            $t->decimal('qty_out', 18, 4)->default(0);
            $t->string('unit', 16);
            $t->string('ref_code')->nullable();
            $t->string('note')->nullable();
            $t->date('date'); // cukup DATE saja
            $t->timestamps();

            $t->index(['warehouse_id', 'lot_id']);
            $t->index(['item_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_mutations');
        Schema::dropIfExists('inventory_stocks');
    }
};
