<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_returns', function (Blueprint $t) {
            // tarif per pcs (optional)
            $t->decimal('sewing_rate', 18, 4)->nullable()->after('notes');

            // total fee setor jahit (rupiah)
            $t->decimal('sewing_fee', 18, 2)->nullable()->after('sewing_rate');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_returns', function (Blueprint $t) {
            $t->dropColumn(['sewing_rate', 'sewing_fee']);
        });
    }
};
