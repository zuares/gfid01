<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cutting header
        Schema::create('cuttings', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // CUT-YYYYMMDD-###
            $t->date('date_start')->nullable();
            $t->date('date_end')->nullable();
            $t->foreignId('operator_id')->nullable()->constrained('users');
            $t->string('status', 20)->default('process'); // process|done
            $t->text('note')->nullable();
            $t->timestamps();
        });

        // Bahan dipakai cutting (LOT kain keluar)
        Schema::create('cutting_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cutting_id')->constrained('cuttings')->cascadeOnDelete();
            $t->foreignId('lot_id')->constrained('lots'); // material lot
            $t->decimal('qty_used', 12, 2);
            $t->string('unit', 16)->default('kg');
            $t->timestamps();
        });

        // Output cutting = bundle pcs produk (tetap kode SKU)
        Schema::create('cutting_outputs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cutting_id')->constrained('cuttings')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('items'); // SKU jadi, ex: K7BLK
            $t->date('date')->nullable();
            $t->decimal('qty', 12, 2);
            $t->string('unit', 16)->default('pcs');
            $t->string('bundle_code')->unique(); // unik
            $t->string('status', 20)->default('bundled'); // bundled|sent|received|done
            $t->timestamps();
        });

        // Serah terima sewing (bundle → penjahit)
        Schema::create('sewing_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // TRS-SJH-YYYYMMDD-###
            $t->foreignId('cutting_output_id')->constrained('cutting_outputs');
            $t->foreignId('operator_id')->nullable()->constrained('users'); // penjahit
            $t->date('date');
            $t->decimal('qty', 12, 2);
            $t->string('unit', 16)->default('pcs');
            $t->string('status', 20)->default('sent'); // sent|received|done
            $t->text('note')->nullable();
            $t->timestamps();
        });

        // Hasil sewing (saat inilah stok barang jadi masuk sebagai PRODUCTION_IN)
        Schema::create('sewing_outputs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sewing_transfer_id')->constrained('sewing_transfers');
            $t->foreignId('item_id')->constrained('items'); // SKU finished yang sama, ex: K7BLK
            $t->date('date');
            $t->decimal('qty', 12, 2);
            $t->string('unit', 16)->default('pcs');
            $t->string('status', 20)->default('done');
            $t->timestamps();
        });

        // Payroll rates per role/item
        Schema::create('payroll_rates', function (Blueprint $t) {
            $t->id();
            $t->enum('role', ['cutting', 'jahit']);
            $t->foreignId('item_id')->nullable()->constrained('items'); // null = default
            $t->decimal('rate', 14, 0);
            $t->timestamps();
        });

        // Payroll entries (pembukuan per transaksi produksi)
        Schema::create('payroll_entries', function (Blueprint $t) {
            $t->id();
            $t->enum('role', ['cutting', 'jahit']);
            $t->foreignId('user_id')->nullable()->constrained('users'); // operator
            $t->foreignId('item_id')->constrained('items');
            $t->date('date');
            $t->decimal('qty', 12, 2);
            $t->decimal('rate', 14, 0);
            $t->decimal('amount', 14, 0);
            $t->string('ref_code')->nullable(); // relaks, untuk jejak
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_rates');
        Schema::dropIfExists('sewing_outputs');
        Schema::dropIfExists('sewing_transfers');
        Schema::dropIfExists('cutting_outputs');
        Schema::dropIfExists('cutting_lines');
        Schema::dropIfExists('cuttings');
    }
};
