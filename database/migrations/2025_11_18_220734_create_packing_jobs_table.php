<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');

            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');

            $table->string('status')->default('draft'); // draft / posted
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by');

            $table->timestamps();

            // Foreign keys (optional tapi disarankan)
            $table->foreign('from_warehouse_id')
                ->references('id')->on('warehouses')
                ->cascadeOnUpdate();

            $table->foreign('to_warehouse_id')
                ->references('id')->on('warehouses')
                ->cascadeOnUpdate();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_jobs');
    }
};
