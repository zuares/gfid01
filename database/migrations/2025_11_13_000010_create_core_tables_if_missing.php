<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // employees
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->string('role')->nullable(); // cutting, sewing, admin, owner
                $t->boolean('active')->default(true);
                $t->timestamps();
            });
        }

        // warehouses
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique()->nullable();
                $t->string('name');
                $t->boolean('is_external')->default(false);
                $t->timestamps();
            });
        } elseif (!Schema::hasColumn('warehouses', 'is_external')) {
            Schema::table('warehouses', function (Blueprint $t) {
                $t->boolean('is_external')->default(false)->after('name');
            });
        }

        // items
        if (!Schema::hasTable('items')) {
            Schema::create('items', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->string('category')->nullable(); // bahan / produk
                $t->string('uom', 10)->default('pcs');
                $t->boolean('active')->default(true);
                $t->timestamps();
            });
        }

        // lots
        if (!Schema::hasTable('lots')) {
            Schema::create('lots', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->unsignedBigInteger('item_id');
                $t->decimal('initial_qty', 14, 4);
                $t->string('unit', 10);
                $t->date('date')->nullable();
                $t->timestamps();
            });
        } elseif (!Schema::hasColumn('lots', 'date')) {
            Schema::table('lots', function (Blueprint $t) {
                $t->date('date')->nullable()->after('unit');
            });
        }
    }

    public function down(): void
    {
        // jangan drop data real — biarkan kosong
    }
};
