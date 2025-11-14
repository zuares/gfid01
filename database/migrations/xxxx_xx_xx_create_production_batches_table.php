<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();

            // IDENTITAS BATCH
            $table->string('code')->unique(); // BCH-CUT-250114-001, BCH-SEW-250118-005
            $table->date('date');

            // JENIS PROSES & STATUS
            $table->enum('process', ['cutting', 'sewing', 'finishing'])
                ->default('cutting');
            $table->enum('status', ['draft', 'in_progress', 'done', 'moved_to_sewing', 'cancelled'])
                ->default('draft');

            // RELASI DENGAN LOGISTIK
            $table->foreignId('external_transfer_id')
                ->nullable()
                ->constrained('external_transfers')
                ->nullOnDelete();

            // LOT HANYA WAJIB UNTUK CUTTING
            $table->foreignId('lot_id')
                ->nullable()
                ->constrained('lots')
                ->nullOnDelete();

            // GUDANG ASAL & TUJUAN
            $table->foreignId('from_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();
            $table->foreignId('to_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            // OPERATOR / VENDOR
            $table->string('operator_code')->nullable();

            // INPUT BAHAN (untuk cutting → kain, untuk sewing bisa diisi sebagai total pcs yang diproses)
            $table->decimal('input_qty', 12, 2)->default(0);
            $table->string('input_uom', 10)->nullable(); // kg / m / pcs

            // OUTPUT HASIL
            $table->integer('output_total_pcs')->default(0);

            // Hasil per item (K7BLK, K5BLK, dst) disimpan dalam bentuk JSON
            $table->json('output_items_json')->nullable();

            // SISA & WASTE (lebih relevan untuk cutting)
            $table->decimal('waste_qty', 12, 2)->default(0);
            $table->decimal('remain_qty', 12, 2)->default(0);

            $table->text('notes')->nullable();

            // AUDIT USER (opsional)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // INDEX TAMBAHAN
            $table->index(['date', 'process']);
            $table->index(['process', 'status']);
            $table->index(['operator_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
