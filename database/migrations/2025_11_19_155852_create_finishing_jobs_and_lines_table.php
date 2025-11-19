<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finishing_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // FIN-251119-001
            $t->date('date');

            // Optional: siapa yang packing / QC finishing
            $t->unsignedBigInteger('employee_id')->nullable(); // employees.id

            // Gudang sumber & tujuan
            $t->unsignedBigInteger('from_warehouse_id'); // WIP-FIN
            $t->unsignedBigInteger('to_warehouse_id'); // FG

            $t->string('status', 32)->default('posted'); // posted langsung
            $t->text('notes')->nullable();

            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamp('posted_at')->nullable();

            $t->timestamps();

            $t->index('date');
            $t->index('employee_id');
            $t->index('from_warehouse_id');
            $t->index('to_warehouse_id');
            $t->index('status');
        });

        Schema::create('finishing_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('finishing_job_id');

            // Sumber WIP (per item)
            $t->unsignedBigInteger('item_id'); // item WIP (seringnya sama dengan FG)
            $t->string('item_code', 64);
            $t->decimal('qty_wip', 18, 2)->default(0); // stok WIP saat input (optional, display)
            $t->decimal('qty_ok', 18, 2)->default(0);
            $t->decimal('qty_reject', 18, 2)->default(0);
            $t->string('unit', 16)->default('pcs');

            // Barang jadi (boleh sama, boleh beda)
            $t->unsignedBigInteger('fg_item_id')->nullable();
            $t->string('fg_item_code', 64)->nullable();

            $t->text('notes')->nullable();

            $t->timestamps();

            $t->index('finishing_job_id');
            $t->index('item_id');
            $t->index('fg_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finishing_lines');
        Schema::dropIfExists('finishing_jobs');
    }
};
