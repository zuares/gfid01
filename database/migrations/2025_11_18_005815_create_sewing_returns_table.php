<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_returns', function (Blueprint $t) {
            $t->id();

            $t->string('code', 64)->unique(); // SEW-RET-YYMMDD-###
            $t->date('date');

            // Operator jahit
            $t->foreignId('operator_id')
                ->constrained('employees');

            // Gudang asal: SEW-EXT-[OP]
            $t->foreignId('from_warehouse_id')
                ->constrained('warehouses');

            // Gudang tujuan: WIP-FIN (atau lain kalau nanti mau)
            $t->foreignId('to_warehouse_id')
                ->constrained('warehouses');

            $t->string('status', 32)->default('draft'); // draft / posted

            // Rekap qty (optional tapi enak buat laporan)
            $t->decimal('total_ok_qty', 18, 2)->default(0);
            $t->decimal('total_reject_qty', 18, 2)->default(0);

            $t->text('notes')->nullable();

            $t->foreignId('created_by')
                ->nullable()
                ->constrained('users');

            // Waktu posting (untuk jejak)
            $t->timestamp('posted_at')->nullable();

            $t->timestamps();

            // Index tambahan
            $t->index('date');
            $t->index('operator_id');
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_returns');
    }
};
