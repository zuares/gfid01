<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_return_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sewing_return_id');

            $table->unsignedBigInteger('sewing_pick_line_id')->nullable(); // trace balik ke pengambilan
            $table->unsignedBigInteger('lot_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_code', 100);

            $table->decimal('qty_ok', 18, 2)->default(0); // hasil jahit OK yang disetor
            $table->decimal('qty_reject', 18, 2)->default(0); // hasil jahit REJECT
            $table->string('unit', 16)->default('pcs');

            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('sewing_return_id')->references('id')->on('sewing_returns')->cascadeOnDelete();
            $table->foreign('sewing_pick_line_id')->references('id')->on('sewing_pick_lines');
            $table->foreign('lot_id')->references('id')->on('lots');
            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_return_lines');
    }
};
