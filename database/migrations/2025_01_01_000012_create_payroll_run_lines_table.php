<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_run_id')
                ->constrained('payroll_runs')
                ->onDelete('cascade');

            $table->foreignId('employee_id')
                ->constrained('employees');

            // proses: cutting / sewing / finishing
            $table->enum('process', ['cutting', 'sewing', 'finishing']);

            // optional: per item
            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();

            // total pcs dalam periode ini
            $table->integer('total_pcs')->default(0);

            // rate yang dipakai
            $table->decimal('rate_per_piece', 12, 2)->default(0);

            // total gaji per baris
            $table->decimal('amount', 14, 2)->default(0);

            // optional, banyak batch yang masuk ke baris ini
            $table->integer('batch_count')->default(0);

            // untuk audit: list kode batch, dsb.
            $table->json('details_json')->nullable();

            $table->timestamps();
            $table->decimal('bonus_amount', 14, 2)->default(0)->after('amount');
            $table->decimal('deduction_amount', 14, 2)->default(0)->after('bonus_amount');
            $table->decimal('total_payable', 14, 2)->default(0)->after('deduction_amount');

            $table->index(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_lines');
    }
};
