<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoreProductionSeeder extends Seeder
{
    public function run(): void
    {
        // === EMPLOYEES ===
        $employees = [
            ['code' => 'MRF', 'name' => 'Maruf', 'role' => 'cutting', 'active' => 1],
            ['code' => 'MYD', 'name' => 'Maya Dewi', 'role' => 'cutting', 'active' => 1],
            ['code' => 'RDN', 'name' => 'Ridwan', 'role' => 'cutting', 'active' => 1],
            ['code' => 'ADN', 'name' => 'Admin', 'role' => 'admin', 'active' => 1],
            ['code' => 'OWN', 'name' => 'Owner', 'role' => 'owner', 'active' => 1],
        ];

        foreach ($employees as $e) {
            DB::table('employees')->updateOrInsert(['code' => $e['code']], $e);
        }

        // === WAREHOUSES ===
        $warehouses = [
            ['code' => 'WH-BAHAN', 'name' => 'Gudang Bahan', 'is_external' => false],
            ['code' => 'WH-FG', 'name' => 'Gudang Barang Jadi', 'is_external' => false],
            ['code' => 'WIP-CUT', 'name' => 'WIP Cutting', 'is_external' => false],
            ['code' => 'WIP-KIT', 'name' => 'WIP Kitting', 'is_external' => false],
            ['code' => 'EXT-MRF', 'name' => 'Lokasi Eksternal MRF', 'is_external' => true],
            ['code' => 'EXT-MYD', 'name' => 'Lokasi Eksternal MYD', 'is_external' => true],
            ['code' => 'EXT-RDN', 'name' => 'Lokasi Eksternal RDN', 'is_external' => true],
        ];

        foreach ($warehouses as $w) {
            DB::table('warehouses')->updateOrInsert(['code' => $w['code']], $w);
        }

        // === ITEMS ===
        $items = [
            // bahan utama
            ['code' => 'FLC280BLK', 'name' => 'Fleece 280 Black', 'category' => 'bahan', 'uom' => 'm'],
            ['code' => 'RIB280BLK', 'name' => 'Rib Pinggang Hitam', 'category' => 'bahan', 'uom' => 'kg'],
            ['code' => 'RIB280ABT', 'name' => 'Rib Pinggang Abu', 'category' => 'bahan', 'uom' => 'kg'],
            ['code' => 'KARET25', 'name' => 'Karet 2.5cm', 'category' => 'bahan', 'uom' => 'm'],
            ['code' => 'TALI001', 'name' => 'Tali Kur Hitam', 'category' => 'bahan', 'uom' => 'm'],
            // produk jadi / BSJ
            ['code' => 'K7BLK', 'name' => 'Jogger K7 Black', 'category' => 'produk', 'uom' => 'pcs'],
            ['code' => 'K5BLK', 'name' => 'Jogger K5 Black', 'category' => 'produk', 'uom' => 'pcs'],
        ];

        foreach ($items as $it) {
            DB::table('items')->updateOrInsert(['code' => $it['code']], $it);
        }

        // === LOTS (contoh hasil pembelian) ===
        $lots = [
            ['code' => 'LOT-FLC280BLK-20251101-001', 'item_code' => 'FLC280BLK', 'qty' => 650, 'unit' => 'm', 'date' => '2025-11-01'],
            ['code' => 'LOT-RIB280BLK-20251105-001', 'item_code' => 'RIB280BLK', 'qty' => 25, 'unit' => 'kg', 'date' => '2025-11-05'],
            ['code' => 'LOT-RIB280ABT-20251105-002', 'item_code' => 'RIB280ABT', 'qty' => 25, 'unit' => 'kg', 'date' => '2025-11-05'],
            ['code' => 'LOT-KARET25-20251105-003', 'item_code' => 'KARET25', 'qty' => 250, 'unit' => 'm', 'date' => '2025-11-05'],
            ['code' => 'LOT-TALI001-20251105-004', 'item_code' => 'TALI001', 'qty' => 150, 'unit' => 'm', 'date' => '2025-11-05'],
        ];

        foreach ($lots as $lot) {
            $item = DB::table('items')->where('code', $lot['item_code'])->first();
            if ($item) {
                DB::table('lots')->updateOrInsert(
                    ['code' => $lot['code']],
                    [
                        'item_id' => $item->id,
                        'initial_qty' => $lot['qty'],
                        'unit' => $lot['unit'],
                        'date' => $lot['date'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
