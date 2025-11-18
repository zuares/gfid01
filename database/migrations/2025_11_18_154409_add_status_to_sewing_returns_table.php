<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sewing_returns', 'status')) {
                $table->string('status', 20)->default('draft')->after('notes');
            }
            if (!Schema::hasColumn('sewing_returns', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sewing_returns', function (Blueprint $table) {
            if (Schema::hasColumn('sewing_returns', 'posted_at')) {
                $table->dropColumn('posted_at');
            }
            if (Schema::hasColumn('sewing_returns', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
