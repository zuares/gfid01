<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // KONTRAKAN, RUMAH
            $t->string('name');
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
