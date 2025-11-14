<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_goods', function (Blueprint $table) {
            $table->id();

            // batch produksi terakhir yang menghasilkan FG ini
            $table->foreignId('production_batch_id')
                ->nullable()
                ->constrained('production_batches')
                ->nullOnDelete();

            // Item barang jadi (K7BLK, K5BLK, dst.)
            $table->foreignId('item_id')
                ->constrained('items');
            $table->string('item_code', 50);

            // Gudang tempat simpan FG
            $table->foreignId('warehouse_id')
                ->constrained('warehouses');

            // Opsional: lot asal kain (traceability)
            $table->foreignId('source_lot_id')
                ->nullable()
                ->constrained('lots')
                ->nullOnDelete();

            // Qty barang jadi
            $table->decimal('qty', 12, 2)->default(0);

            // Opsional: misal size/variant kalau nanti kamu mau pecah per size
            $table->string('variant', 50)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['warehouse_id', 'item_id']);
            $table->index(['item_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_goods');
    }
};
