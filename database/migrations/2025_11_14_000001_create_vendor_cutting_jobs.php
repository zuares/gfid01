<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * ===========================================
         * 1) HEADER: vendor_cutting_jobs
         * ===========================================
         */
        Schema::create('vendor_cutting_jobs', function (Blueprint $t) {

            $t->id();
            $t->string('code')->unique();
            // contoh:
            // VND-CUT-251114-MRF-001

            $t->unsignedBigInteger('external_transfer_id');
            $t->string('operator_code'); // MRF, BBI, RDN, dll (vendor per operator)

            $t->date('date'); // tanggal proses cutting
            $t->enum('status', [
                'draft', // baru terbuat (belum hasil)
                'in_progress',
                'completed', // hasil cutting sudah diinput
                'posted', // sudah dimutasi stok & dijurnal (fase berikutnya)
                'canceled',
            ])->default('draft');

            $t->text('note')->nullable();

            $t->timestamps();

            // index
            $t->index(['operator_code', 'status']);
            $t->index(['external_transfer_id']);

            // FK
            $t->foreign('external_transfer_id')
                ->references('id')
                ->on('external_transfers')
                ->cascadeOnDelete();
        });

        /**
         * ===========================================
         * 2) DETAIL: vendor_cutting_job_lines
         * ===========================================
         */
        Schema::create('vendor_cutting_job_lines', function (Blueprint $t) {

            $t->id();

            $t->unsignedBigInteger('vendor_cutting_job_id');
            $t->unsignedBigInteger('external_transfer_line_id');

            // INPUT KAIN (kg)
            $t->decimal('input_qty', 18, 4)->default(0);

            // OUTPUT BSJ (pcs)
            $t->decimal('output_qty', 18, 4)->default(0);

            // item hasil cutting → nanti bisa dipetakan ke item.id
            // contoh: BSJ-SJR, BSJ-K7BLK, BSJ-K5BLK
            $t->string('output_item_code')->nullable();

            // keterangan per baris
            $t->text('remark')->nullable();

            $t->timestamps();

            // FK
            $t->foreign('vendor_cutting_job_id')
                ->references('id')
                ->on('vendor_cutting_jobs')
                ->cascadeOnDelete();

            $t->foreign('external_transfer_line_id')
                ->references('id')
                ->on('external_transfer_lines')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_cutting_job_lines');
        Schema::dropIfExists('vendor_cutting_jobs');
    }
};
