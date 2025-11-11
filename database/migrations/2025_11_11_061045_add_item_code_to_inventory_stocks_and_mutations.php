<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambah kolom item_code (nullable) di kedua tabel
        Schema::table('inventory_stocks', function (Blueprint $t) {
            if (!Schema::hasColumn('inventory_stocks', 'item_code')) {
                $t->string('item_code')->nullable()->after('warehouse_id');
            }
        });
        Schema::table('inventory_mutations', function (Blueprint $t) {
            if (!Schema::hasColumn('inventory_mutations', 'item_code')) {
                $t->string('item_code')->nullable()->after('warehouse_id');
            }
        });

        // 2) Backfill dari lots -> items
        // Matikan sementara FK (jika RDBMS mendukung). Di SQLite, PRAGMA berlaku per koneksi.
        try {DB::statement('PRAGMA foreign_keys = OFF');} catch (\Throwable $e) {}

        // Backfill STOCKS
        // Gunakan UPDATE ... FROM (MySQL/PG) jika tersedia, atau subquery (aman untuk SQLite).
        DB::statement("
            UPDATE inventory_stocks
            SET item_code = (
                SELECT items.code
                FROM lots
                JOIN items ON items.id = lots.item_id
                WHERE lots.id = inventory_stocks.lot_id
                LIMIT 1
            )
            WHERE item_code IS NULL
        ");

        // Backfill MUTATIONS
        DB::statement("
            UPDATE inventory_mutations
            SET item_code = (
                SELECT items.code
                FROM lots
                JOIN items ON items.id = lots.item_id
                WHERE lots.id = inventory_mutations.lot_id
                LIMIT 1
            )
            WHERE item_code IS NULL
        ");

        try {DB::statement('PRAGMA foreign_keys = ON');} catch (\Throwable $e) {}

        // 3) Tambah index (performa)
        Schema::table('inventory_stocks', function (Blueprint $t) {
            // index gabungan sering dipakai untuk breakdown item per gudang
            $t->index(['item_code', 'warehouse_id'], 'inv_stocks_item_wh_idx');
            $t->index('updated_at', 'inv_stocks_updated_idx');
        });
        Schema::table('inventory_mutations', function (Blueprint $t) {
            $t->index(['item_code', 'warehouse_id'], 'inv_muts_item_wh_idx');
            $t->index('date', 'inv_muts_date_idx');
        });

        // 4) (Opsional) Tambah unique agregat per item di stocks — HANYA bila nanti kamu
        // menulis ke stocks per-item (tanpa LOT). Untuk hybrid, sebaiknya TUNDA dulu.
        // Schema::table('inventory_stocks', function (Blueprint $t) {
        //     $t->unique(['warehouse_id','item_code','unit'], 'inv_stocks_item_unique');
        // });
    }

    public function down(): void
    {
        // drop index aman walau di SQLite (jika belum ada akan diabaikan oleh Laravel)
        Schema::table('inventory_mutations', function (Blueprint $t) {
            $t->dropIndex('inv_muts_item_wh_idx');
            $t->dropIndex('inv_muts_date_idx');
            if (Schema::hasColumn('inventory_mutations', 'item_code')) {
                $t->dropColumn('item_code');
            }
        });
        Schema::table('inventory_stocks', function (Blueprint $t) {
            $t->dropIndex('inv_stocks_item_wh_idx');
            $t->dropIndex('inv_stocks_updated_idx');
            // $t->dropUnique('inv_stocks_item_unique'); // hanya jika kamu sempat menambahkannya
            if (Schema::hasColumn('inventory_stocks', 'item_code')) {
                $t->dropColumn('item_code');
            }
        });
    }
};
