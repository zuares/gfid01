<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // PR-2501-W01, dll.
            $table->date('start_date');
            $table->date('end_date');

            // proses apa yang dihitung: cutting / sewing / finishing / all
            $table->enum('process', ['cutting', 'sewing', 'finishing', 'all'])
                ->default('sewing');

            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft');

            $table->decimal('total_amount', 14, 2)->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])
                ->default('unpaid')
                ->after('status');

            $table->timestamp('posted_at')->nullable()->after('payment_status');

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('posted_at');

            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
