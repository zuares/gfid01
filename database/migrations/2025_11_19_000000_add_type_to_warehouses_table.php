<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('type', 32)
                ->nullable()
                ->after('name')
                ->index();
        });

        // Optional: isi nilai default berdasarkan code yang umum (silakan sesuaikan)
        DB::table('warehouses')->where('code', 'RAW')->update(['type' => 'raw_material']);
        DB::table('warehouses')->where('code', 'LIKE', 'LOT%')->update(['type' => 'lot_raw']);
        DB::table('warehouses')->where('code', 'LIKE', 'WIP-CUT%')->update(['type' => 'wip_cut']);
        DB::table('warehouses')->where('code', 'LIKE', 'WIP-SEW%')->update(['type' => 'wip_sew']);
        DB::table('warehouses')->where('code', 'LIKE', 'WIP-FIN%')->update(['type' => 'wip_fin']);
        DB::table('warehouses')->where('code', 'FG')->update(['type' => 'fg']);
        DB::table('warehouses')->where('code', 'LIKE', 'REJ-CUT%')->update(['type' => 'reject_cut']);
        DB::table('warehouses')->where('code', 'LIKE', 'REJ-SEW%')->update(['type' => 'reject_sew']);
        DB::table('warehouses')->where('code', 'LIKE', 'REJ-FIN%')->update(['type' => 'reject_fin']);
        DB::table('warehouses')->where('code', 'LIKE', 'REWORK%')->update(['type' => 'wip_reject']);
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
