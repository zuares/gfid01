<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('lots', 'date')) {
            Schema::table('lots', function (Blueprint $t) {
                $t->date('date')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Aman: biarkan tanpa drop
        // if (Schema::hasColumn('lots', 'date')) { Schema::table('lots', fn($t)=>$t->dropColumn('date')); }
    }
};
