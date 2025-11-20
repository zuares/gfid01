<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $t) {
            // total biaya cutting ke vendor (rupiah)
            $t->decimal('cutting_fee', 18, 2)->nullable()->after('notes');

            // optional: tarif per pcs (kalau mau)
            $t->decimal('cutting_rate', 18, 4)->nullable()->after('cutting_fee');
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $t) {
            $t->dropColumn(['cutting_fee', 'cutting_rate']);
        });
    }
};
