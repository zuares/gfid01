<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::create('employee_loans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees');

            $t->decimal('amount', 12, 2); // kasbon
            $t->string('description')->nullable();
            $t->date('date');

            $t->enum('status', ['unpaid', 'paid'])->default('unpaid');

            $t->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
