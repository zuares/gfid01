<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_picks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // SEW-PICK-250118-001
            $table->date('date'); // tanggal dokumen

            $table->unsignedBigInteger('operator_id')->nullable(); // penjahit, kalau sudah ada tabel employees/operator
            $table->unsignedBigInteger('from_warehouse_id'); // WIP-CUT
            $table->unsignedBigInteger('to_warehouse_id'); // gudang operator / WIP-SEW

            $table->string('status', 20)->default('draft'); // draft / posted / cancelled
            $table->string('notes', 500)->nullable();

            $table->unsignedBigInteger('created_by')->nullable(); // user id yang input
            $table->timestamps();

            // FK optional (hapus kalau belum ada tabelnya)
            // $table->foreign('operator_id')->references('id')->on('employees');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_picks');
    }
};
