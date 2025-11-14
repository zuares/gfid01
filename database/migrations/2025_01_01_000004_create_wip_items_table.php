<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_items', function (Blueprint $table) {
            $table->id();

            // Sumber batch produksi (misal dari cutting)
            $table->foreignId('production_batch_id')
                ->nullable()
                ->constrained('production_batches')
                ->nullOnDelete();

            // Item WIP (misal: K7BLK, K5BLK, dsb.)
            $table->foreignId('item_id')
                ->constrained('items');
            $table->string('item_code', 50); // simpan juga kode untuk cepat

            // Stok WIP tersimpan di warehouse mana
            $table->foreignId('warehouse_id')
                ->constrained('warehouses');

            // Dari LOT kain apa (untuk tracing)
            $table->foreignId('source_lot_id')
                ->nullable()
                ->constrained('lots')
                ->nullOnDelete();

            // Tahap WIP: hasil cutting, hasil sewing, dll.
            $table->enum('stage', ['cutting', 'sewing', 'finishing'])
                ->default('cutting');

            // Qty WIP yang masih tersedia
            $table->decimal('qty', 12, 2)->default(0);

            // Opsional: catatan kecil
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index untuk performa
            $table->index(['warehouse_id', 'item_id']);
            $table->index(['stage']);
            $table->index(['item_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_items');
    }
};
