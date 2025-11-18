<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_job_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('packing_job_id');
            $table->unsignedBigInteger('sewing_return_line_id');

            $table->unsignedBigInteger('item_id');
            $table->string('item_code', 100);

            $table->unsignedBigInteger('lot_id')->nullable();

            $table->decimal('qty', 12, 2);
            $table->string('unit', 20)->default('pcs');

            $table->timestamps();

            $table->foreign('packing_job_id')
                ->references('id')->on('packing_jobs')
                ->cascadeOnDelete();

            $table->foreign('sewing_return_line_id')
                ->references('id')->on('sewing_return_lines')
                ->restrictOnDelete();

            $table->foreign('item_id')
                ->references('id')->on('items')
                ->cascadeOnUpdate();

            $table->foreign('lot_id')
                ->references('id')->on('lots')
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_job_lines');
    }
};
