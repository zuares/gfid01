<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('warehouses')->updateOrInsert(['code' => 'KONTRAKAN'], [
            'name' => 'Gudang Kontrakan', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('warehouses')->updateOrInsert(['code' => 'RUMAH'], [
            'name' => 'Gudang Rumah', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('items')->updateOrInsert(['code' => 'FLC280BLK'], [
            'name' => 'Fleece 280 Black', 'uom' => 'kg', 'type' => 'material',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('items')->updateOrInsert(['code' => 'K7BLK'], [
            'name' => 'Jogger K7 Black', 'uom' => 'pcs', 'type' => 'finished',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
