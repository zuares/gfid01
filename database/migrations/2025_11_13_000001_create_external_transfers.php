<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_transfers', function (Blueprint $table) {
            $table->id();

            // IDENTITAS DOKUMEN
            $table->string('code')->unique(); // EXT-CUT-250114-001
            $table->date('date');

            // GUDANG / LOKASI
            $table->foreignId('from_warehouse_id')
                ->constrained('warehouses');
            $table->foreignId('to_warehouse_id')
                ->constrained('warehouses');

            // SIAPA / UNTUK PROSES APA
            $table->string('operator_code')->nullable(); // vendor / operator
            $table->enum('process', ['cutting', 'sewing', 'finishing', 'other'])
                ->default('cutting');

            // STATUS SURAT JALAN
            $table->enum('status', ['draft', 'sent', 'received', 'done', 'cancelled']);

            $table->text('notes')->nullable();

            $table->timestamps();

            // INDEX TAMBAHAN
            $table->index(['date', 'process']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_transfers');
    }
};
