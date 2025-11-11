<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // FLC280BLK, K7BLK (tanpa embel-embel)
            $t->string('name');
            $t->string('uom', 16)->default('pcs'); // material biasanya 'kg'
            $t->enum('type', ['material', 'finished'])->default('material');
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
