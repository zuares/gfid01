<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom 'category' kalau belum ada
        if (!Schema::hasColumn('items', 'category')) {
            Schema::table('items', function (Blueprint $t) {
                $t->string('category')->nullable(); // 'bahan' | 'produk' | null
            });
        }

        // Tambah kolom 'uom' kalau belum ada
        if (!Schema::hasColumn('items', 'uom')) {
            Schema::table('items', function (Blueprint $t) {
                $t->string('uom', 10)->default('pcs');
            });
        }

        // (Opsional) Tambah 'active' kalau belum ada
        if (!Schema::hasColumn('items', 'active')) {
            Schema::table('items', function (Blueprint $t) {
                $t->boolean('active')->default(true);
            });
        }
    }

    public function down(): void
    {
        // Biasanya tidak kita drop agar aman di DB existing
        // Kalau mau rollback spesifik, uncomment baris di bawah:
        // Schema::table('items', function (Blueprint $t) {
        //     if (Schema::hasColumn('items', 'category')) $t->dropColumn('category');
        //     if (Schema::hasColumn('items', 'uom')) $t->dropColumn('uom');
        //     if (Schema::hasColumn('items', 'active')) $t->dropColumn('active');
        // });
    }
};
