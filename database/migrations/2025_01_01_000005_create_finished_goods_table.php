
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_goods', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('production_batch_id')->nullable();
            $t->unsignedBigInteger('item_id');
            $t->unsignedBigInteger('lot_id'); // LOT khusus FG
            $t->unsignedBigInteger('warehouse_id');

            $t->string('item_code'); // sync dengan items.code
            $t->string('unit', 16)->default('pcs'); // sama seperti inventory_stocks.unit
            $t->decimal('qty', 18, 4)->default(0);

            $t->unsignedBigInteger('source_lot_id')->nullable(); // LOT kain asal (optional)
            $t->string('variant', 50)->nullable();
            $t->string('notes')->nullable();

            $t->timestamps();

            $t->index(['item_id', 'warehouse_id']);
            $t->index(['lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_goods');
    }
};
