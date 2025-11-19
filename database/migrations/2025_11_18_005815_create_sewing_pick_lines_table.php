<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_pick_lines', function (Blueprint $t) {
            $t->id();

            $t->foreignId('sewing_pick_id')
                ->constrained('sewing_picks')
                ->cascadeOnDelete();

            $t->foreignId('bundle_id')
                ->nullable()
                ->constrained('cutting_bundles');

            $t->foreignId('item_id')
                ->constrained('items');

            $t->string('item_code', 64);
            $t->decimal('qty', 18, 2);
            $t->string('unit', 16)->default('pcs');
            $t->string('notes', 255)->nullable();

            $t->timestamps();

            $t->index('item_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_pick_lines');
    }
};
