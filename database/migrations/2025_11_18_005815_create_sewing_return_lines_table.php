<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_return_lines', function (Blueprint $t) {
            $t->id();

            // HEADER
            $t->unsignedBigInteger('sewing_return_id');

            // Link ke baris ambil jahit
            $t->unsignedBigInteger('sewing_pick_line_id')->nullable();

            // Stock sumber di gudang operator (SEW-EXT-EMP)
            $t->unsignedBigInteger('stock_id')->nullable(); // inventory_stocks.id

            // Info item
            $t->unsignedBigInteger('item_id'); // items.id
            $t->string('item_code', 64); // K7BLK, JGRK7BLK, dll

            // Qty
            $t->decimal('qty_ok', 18, 2)->default(0); // hasil OK → boleh ke WIP-FIN
            $t->decimal('qty_reject', 18, 2)->default(0); // hasil reject
            $t->string('unit', 16)->default('pcs');

            $t->string('notes', 255)->nullable();

            $t->timestamps();

            // INDEX
            $t->index('sewing_return_id');
            $t->index('sewing_pick_line_id');
            $t->index('stock_id');
            $t->index('item_id');
            $t->index('item_code');

            // FK
            $t->foreign('sewing_return_id')
                ->references('id')->on('sewing_returns')
                ->cascadeOnDelete();

            $t->foreign('sewing_pick_line_id')
                ->references('id')->on('sewing_pick_lines')
                ->nullOnDelete();

            $t->foreign('stock_id')
                ->references('id')->on('inventory_stocks')
                ->nullOnDelete();

            $t->foreign('item_id')
                ->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_return_lines');
    }
};
