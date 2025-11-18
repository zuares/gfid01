<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_returns', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // SEW-RET-250118-001
            $table->date('date');

            $table->unsignedBigInteger('operator_id')->nullable(); // penjahit yang setor
            $table->unsignedBigInteger('from_warehouse_id'); // gudang operator (SEW-EXT-XXX)
            $table->unsignedBigInteger('to_warehouse_id'); // WIP-SEW / WIP-FINISH

            $table->string('status', 20)->default('draft'); // draft / posted / cancelled
            $table->string('notes', 500)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // $table->foreign('operator_id')->references('id')->on('employees');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_returns');
    }
};
