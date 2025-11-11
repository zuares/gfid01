<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items');
            $t->string('code')->unique(); // LOT-FLC280BLK-YYYYMMDD-###
            $t->string('unit', 16)->default('kg');
            $t->decimal('initial_qty', 12, 2);
            $t->decimal('unit_cost', 14, 0)->default(0);
            $t->date('date');
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
