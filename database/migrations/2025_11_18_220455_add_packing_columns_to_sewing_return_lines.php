<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            // Qty yang sudah dipacking (default: 0)
            $table->decimal('packed_qty', 12, 2)
                ->default(0)
                ->after('qty_ok');

            // Terakhir kali dipacking
            $table->timestamp('last_packed_at')
                ->nullable()
                ->after('packed_qty');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->dropColumn(['packed_qty', 'last_packed_at']);
        });
    }
};
