<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_picks', function (Blueprint $t) {
            $t->id();

            $t->string('code', 64)->unique(); // SEW-PICK-YYMMDD-###
            $t->date('date');

            // operator jahit
            $t->foreignId('operator_id')
                ->constrained('employees');

            // gudang asal: biasanya WIP-SEW
            $t->foreignId('from_warehouse_id')
                ->constrained('warehouses');

            // gudang tujuan: SEW-EXT-[EMP]
            $t->foreignId('to_warehouse_id')
                ->constrained('warehouses');

            $t->string('status', 32)->default('posted'); // draft/posted kalau mau
            $t->text('notes')->nullable();

            $t->foreignId('created_by')
                ->nullable()
                ->constrained('users');

            $t->timestamps();

            $t->index('date');
            $t->index('operator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_picks');
    }
};
