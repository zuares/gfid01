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
            $t->unsignedBigInteger('item_id');
            $t->string('code')->unique(); // LOT-XXX-YYYYMMDD-###
            $t->string('unit', 16);
            $t->decimal('initial_qty', 18, 4);
            $t->decimal('unit_cost', 18, 4)->default(0);
            $t->date('date'); // tanggal LOT dibuat
            $t->timestamps();
        });

    }
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
