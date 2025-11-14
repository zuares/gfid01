<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_transfer_lines', function (Blueprint $table) {
            $table->id();

            // HEADER
            $table->foreignId('external_transfer_id')
                ->constrained('external_transfers')
                ->onDelete('cascade');

            // LOT & ITEM
            $table->foreignId('lot_id')
                ->constrained('lots');
            $table->foreignId('item_id')
                ->constrained('items');

            // QTY KIRIM
            $table->decimal('qty', 12, 2);
            $table->string('uom', 10)->default('kg');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['external_transfer_id']);
            $table->index(['lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_transfer_lines');
    }
};
