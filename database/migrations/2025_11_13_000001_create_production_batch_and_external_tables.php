<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // production_batches (payung per operator)
        if (!Schema::hasTable('production_batches')) {
            Schema::create('production_batches', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique(); // PROD-YYYYMMDD-EMP-###
                $t->date('date');
                $t->string('operator_code'); // code karyawan tukang cutting
                $t->string('status')->default('in_progress'); // draft|in_progress|cutting_done|kitting_done|ready_to_sew|closed
                $t->string('notes')->nullable();
                $t->timestamps();
            });
        }

        // external_transfers (kirim kain ke lokasi eksternal operator)
        if (!Schema::hasTable('external_transfers')) {
            Schema::create('external_transfers', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique(); // CUT-EXT-YYYYMMDD-###
                $t->string('batch_code'); // FK by code (string ref)
                $t->date('date');
                $t->string('operator_code'); // MRF/MYD/RDN
                $t->unsignedBigInteger('warehouse_from_id'); // WH-BAHAN
                $t->unsignedBigInteger('warehouse_to_id'); // LOC-EXT-<OPR> (warehouse eksternal)
                $t->string('status')->default('sent'); // sent|partially_received|received
                $t->string('note')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('external_transfer_lines')) {
            Schema::create('external_transfer_lines', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('external_transfer_id');
                $t->unsignedBigInteger('lot_id'); // LOT bahan utama
                $t->unsignedBigInteger('item_id'); // item id dari LOT
                $t->decimal('qty', 14, 4);
                $t->string('uom', 16);
                $t->string('note')->nullable();
                $t->timestamps();
            });
        }

        // cutting_receipts (penerimaan hasil cutting eksternal - header)
        if (!Schema::hasTable('cutting_receipts')) {
            Schema::create('cutting_receipts', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique(); // RCUT-YYYYMMDD-###
                $t->string('batch_code'); // ref batch operator
                $t->date('date');
                $t->string('operator_code');
                $t->unsignedBigInteger('external_transfer_id')->nullable();
                $t->string('status')->default('draft'); // draft|posted
                $t->string('note')->nullable();
                $t->timestamps();
            });
        }

        // cutting_receipt_lines (hasil per SKU: good & defect)
        if (!Schema::hasTable('cutting_receipt_lines')) {
            Schema::create('cutting_receipt_lines', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('cutting_receipt_id');
                $t->unsignedBigInteger('item_id'); // SKU hasil (mis. K7BLK)
                $t->integer('bundle_count')->default(0); // jumlah iket (opsional)
                $t->integer('pcs_per_bundle')->default(20);
                $t->integer('good_qty')->default(0);
                $t->integer('defect_qty')->default(0);
                $t->string('good_lot_code')->nullable(); // LOT-<SKU>-CUT-YYYYMMDD-OPR-###
                $t->string('defect_lot_code')->nullable(); // LOT-<SKU>-DEF-YYYYMMDD-OPR-###
                $t->string('note')->nullable();
                $t->timestamps();
            });
        }

        // warehouses: tambahkan kolom is_external (kalau belum ada)
        if (Schema::hasTable('warehouses') && !Schema::hasColumn('warehouses', 'is_external')) {
            Schema::table('warehouses', function (Blueprint $t) {
                $t->boolean('is_external')->default(false)->after('name');
                $t->string('code')->unique()->nullable(); // agar bisa isi EXT-<OPR>
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_receipt_lines');
        Schema::dropIfExists('cutting_receipts');
        Schema::dropIfExists('external_transfer_lines');
        Schema::dropIfExists('external_transfers');
        Schema::dropIfExists('production_batches');
        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'is_external')) {
            Schema::table('warehouses', function (Blueprint $t) {
                $t->dropColumn(['is_external', 'code']);
            });
        }
    }
};
