<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_piece_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            // process: cutting / sewing / finishing
            $table->enum('process', ['cutting', 'sewing', 'finishing']);

            // optional: tarif khusus per item (kalau null → berlaku semua item)
            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();

            // nominal per pcs
            $table->decimal('rate_per_piece', 12, 2);

            // masa berlaku
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index(['employee_id', 'process']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_piece_rates');
    }
};
