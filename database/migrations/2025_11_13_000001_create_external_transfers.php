<?php

// database/migrations/2025_11_13_000001_create_external_transfers.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // CUT-EXT-YYMMDD-EMP-### atau SEW-EXT-YYMMDD-EMP-###
            $t->enum('process', ['cutting', 'sewing']); // jenis makloon
            $t->string('operator_code'); // EMP / vendor code
            $t->unsignedBigInteger('from_warehouse_id'); // gudang internal asal
            $t->unsignedBigInteger('to_warehouse_id'); // gudang tujuan eksternal (per operator)
            $t->date('date'); // tanggal pengiriman
            $t->enum('status', [
                'draft', // baru dibuat
                'sent', // sudah dikirim (stok sudah keluar -> masuk gudang eksternal)
                'partially_received',
                'received', // seluruh baris sudah diterima
                'posted', // sudah dijurnal
                'canceled',
            ])->default('draft');
            $t->decimal('material_value_est', 18, 2)->default(0);
            $t->text('note')->nullable();
            $t->timestamps();

            $t->index(['process', 'operator_code', 'status']);
        });

        Schema::create('external_transfer_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('external_transfer_id');
            $t->unsignedBigInteger('lot_id'); // lot yang dikirim
            $t->unsignedBigInteger('item_id'); // redundan untuk kemudahan query
            $t->decimal('qty', 18, 4);
            $t->string('unit', 20);
            $t->decimal('received_qty', 18, 4)->default(0); // akumulatif penerimaan balik
            $t->decimal('defect_qty', 18, 4)->default(0); // dicatat saat receive
            $t->text('note')->nullable();
            $t->timestamps();

            $t->foreign('external_transfer_id')->references('id')->on('external_transfers')->cascadeOnDelete();
        });

        Schema::create('external_receipts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('external_transfer_id');
            $t->string('code')->unique(); // RCV-EXT-YYMMDD-EMP-###
            $t->date('date'); // tanggal diterima
            $t->enum('status', ['draft', 'posted'])->default('draft');
            $t->text('note')->nullable();
            $t->timestamps();

            $t->foreign('external_transfer_id')->references('id')->on('external_transfers')->cascadeOnDelete();
        });

        Schema::create('external_receipt_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('external_receipt_id');
            $t->unsignedBigInteger('transfer_line_id'); // refer ke baris kirim
            $t->decimal('received_qty', 18, 4); // qty pulang (good)
            $t->decimal('defect_qty', 18, 4)->default(0);
            $t->text('note')->nullable();
            $t->timestamps();

            $t->foreign('external_receipt_id')->references('id')->on('external_receipts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_receipt_lines');
        Schema::dropIfExists('external_receipts');
        Schema::dropIfExists('external_transfer_lines');
        Schema::dropIfExists('external_transfers');
    }
};
