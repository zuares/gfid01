<?php

// database/migrations/2025_11_13_000010_create_employees_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // MRF, MYD, dll.
            $t->string('name');
            $t->enum('role', ['cutting', 'sewing', 'kitting', 'admin', 'owner', 'other'])->default('other');
            $t->boolean('active')->default(true);
            $t->string('phone')->nullable();
            $t->string('address')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
