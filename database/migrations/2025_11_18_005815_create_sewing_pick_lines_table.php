<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_pick_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sewing_pick_id');

            $table->unsignedBigInteger('wip_item_id')->nullable(); // kalau kamu sudah punya tabel wip_items
            $table->unsignedBigInteger('lot_id'); // supaya bisa dipakai InventoryService
            $table->unsignedBigInteger('item_id');
            $table->string('item_code', 100);

            $table->decimal('qty', 18, 2); // jumlah yang DIAMBIL
            $table->string('unit', 16)->default('pcs');

            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('sewing_pick_id')->references('id')->on('sewing_picks')->cascadeOnDelete();
            // $table->foreign('wip_item_id')->references('id')->on('wip_items');
            $table->foreign('lot_id')->references('id')->on('lots');
            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_pick_lines');
    }
};
