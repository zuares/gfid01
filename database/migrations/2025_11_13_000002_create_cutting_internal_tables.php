<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header pekerjaan cutting internal (konversi kg/m → pcs komponen)
        if (!Schema::hasTable('cutting_internal_jobs')) {
            Schema::create('cutting_internal_jobs', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique(); // CUT-INT-YYYYMMDD-###
                $t->date('date');
                $t->string('operator_code')->nullable(); // karyawan internal (opsional)
                $t->unsignedBigInteger('warehouse_from_id'); // biasanya WH-BAHAN/WH-KOMP
                $t->unsignedBigInteger('warehouse_to_id'); // WH-WIP-COMP (hasil komponen pcs)
                $t->string('status')->default('posted'); // keep simple: langsung posted
                $t->string('note')->nullable();
                $t->timestamps();
            });
        }

        // Detail per LOT input & hasil pcs
        if (!Schema::hasTable('cutting_internal_lines')) {
            Schema::create('cutting_internal_lines', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('cutting_internal_job_id');
                $t->unsignedBigInteger('item_id'); // contoh: RIB280BLK
                $t->unsignedBigInteger('lot_id'); // LOT input (kg/m)
                $t->decimal('qty_in', 14, 4); // jumlah bahan yang dipakai (kg/m)
                $t->string('uom_in', 16); // 'kg' atau 'm'
                $t->integer('pcs_output'); // hasil aktual pcs
                $t->string('lot_output_code'); // LOT komponen (pcs), ex: LOT-RIB280BLKPCS-YYYYMMDD-###
                $t->string('note')->nullable();
                $t->timestamps();
            });
        }

        // Siapkan gudang output default (WH-WIP-COMP) jika kolom warehouses sudah ada
        if (Schema::hasTable('warehouses')) {
            if (!Schema::hasColumn('warehouses', 'code')) {
                Schema::table('warehouses', function (Blueprint $t) {
                    $t->string('code')->unique()->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_internal_lines');
        Schema::dropIfExists('cutting_internal_jobs');
    }
};
