<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_items', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('production_batch_id');
            $t->unsignedBigInteger('item_id');
            $t->string('item_code', 64);

            $t->string('stage', 32)->default('cutting'); // cutting, sewing, dsb
            $t->decimal('qty', 18, 4); // qty OK hasil QC
            $t->string('unit', 16)->default('pcs');

            $t->unsignedBigInteger('warehouse_id'); // biasanya KONTRAKAN
            $t->string('status', 32)->default('available'); // in_qc, available, used, dll

            $t->text('notes')->nullable();

            $t->timestamps();

            $t->index(['production_batch_id', 'stage']);
            $t->index(['item_code', 'warehouse_id']);

            $t->foreign('production_batch_id')
                ->references('id')->on('production_batches')
                ->cascadeOnDelete();

            $t->foreign('item_id')
                ->references('id')->on('items');

            $t->foreign('warehouse_id')
                ->references('id')->on('warehouses');

            $t->unsignedBigInteger('cutting_bundle_id')->nullable()->after('production_batch_id');
            $t->unsignedBigInteger('lot_id')->nullable()->after('cutting_bundle_id');

            // index pendukung
            $t->index(['warehouse_id', 'stage', 'status'], 'wip_items_wh_stage_status_idx');

            // foreign key (optional, sesuaikan nama tabel & kolom)
            $t->foreign('cutting_bundle_id')
                ->references('id')->on('cutting_bundles')
                ->nullOnDelete();

            $t->foreign('lot_id')
                ->references('id')->on('lots');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_items');
    }
};
