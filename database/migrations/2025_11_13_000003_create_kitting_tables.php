
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header kitting job
        if (!Schema::hasTable('kitting_jobs')) {
            Schema::create('kitting_jobs', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique(); // KIT-YYYYMMDD-###
                $t->string('batch_code'); // link ke production_batches (string)
                $t->date('date');
                $t->string('operator_code')->nullable(); // petugas kitting internal
                $t->unsignedBigInteger('warehouse_output_id'); // default: WIP-SEW (siap dijahit)
                $t->string('status')->default('posted'); // simple: langsung posted
                $t->string('note')->nullable();
                $t->timestamps();
            });
        }

        // Detail per SKU: konsumsi komponen & output BSJ set
        if (!Schema::hasTable('kitting_lines')) {
            Schema::create('kitting_lines', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('kitting_job_id');

                // SKU hasil (produk BSJ set; pakai item SKU yang sama dengan finished goods)
                $t->unsignedBigInteger('product_item_id');
                $t->integer('qty_output_sets'); // berapa set BSJ yang dibuat
                $t->string('lot_output_code'); // LOT-<SKU>-BSJ-YYYYMMDD-<OPR>-###

                // Komponen kain potong (pcs)
                $t->unsignedBigInteger('kain_lot_id')->nullable();
                $t->integer('kain_qty_used')->default(0);

                // Komponen rib pcs
                $t->unsignedBigInteger('rib_lot_id')->nullable();
                $t->integer('rib_qty_used')->default(0);

                // Komponen karet pcs
                $t->unsignedBigInteger('karet_lot_id')->nullable();
                $t->integer('karet_qty_used')->default(0);

                // Komponen tali pcs
                $t->unsignedBigInteger('tali_lot_id')->nullable();
                $t->integer('tali_qty_used')->default(0);

                $t->string('note')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kitting_lines');
        Schema::dropIfExists('kitting_jobs');
    }
};
