<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // ===== MATERIAL (Bahan Baku & Pendukung) =====
            [
                'code' => 'FLC280BLK',
                'name' => 'Kain Fleece 280gr Hitam',
                'uom' => 'kg',
                'type' => 'material',
            ],
            [
                'code' => 'RIBBLK',
                'name' => 'Rib Hitam',
                'uom' => 'kg',
                'type' => 'material',
            ],
            [
                'code' => 'KARET30',
                'name' => 'Karet Pinggang 30mm',
                'uom' => 'roll',
                'type' => 'material',
            ],
            [
                'code' => 'TLKURBLK',
                'name' => 'Talikur Hitam',
                'uom' => 'roll',
                'type' => 'material',
            ],

            // ===== FINISHED GOODS =====
            [
                'code' => 'JGRK7BLK',
                'name' => 'Jogger K7 Hitam',
                'uom' => 'pcs',
                'type' => 'finished',
            ],
            [
                'code' => 'SWTK5GRY',
                'name' => 'Sweatshirt K5 Abu',
                'uom' => 'pcs',
                'type' => 'finished',
            ],
            [
                'code' => 'SWTK5BLK',
                'name' => 'Sweatshirt K5 Hitam',
                'uom' => 'pcs',
                'type' => 'finished',
            ],
        ];

        foreach ($items as $item) {
            Item::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
