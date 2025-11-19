<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // ============================
        // DEFINE WARNA
        // ============================
        $colors = [
            'BLK', // black
            'NVY', // navy
            'MST', // mustard
            'ABT', // abu tua
            'WHT', // white
        ];

        // ============================
        // FINISHED GOODS (K-Series & J-Series)
        // ============================
        $finishedBase = [
            'K1', 'K2', 'K3', 'K5', 'K7',
            'J3', 'J5', 'J7',
        ];

        $finishedItems = [];

        foreach ($finishedBase as $base) {
            foreach ($colors as $color) {
                $finishedItems[] = [
                    'code' => $base . $color,
                    'name' => "{$base} — {$color}",
                    'unit' => 'pcs',
                    'type' => 'finished',
                ];
            }
        }

        // ============================
        // RAW MATERIAL (multi color)
        // ============================
        $materialMultiColor = [
            'FLC280', // fleece
            'RIB280', // rib
            'KRT4CM', // karet 4cm
        ];

        $materialItems = [];

        foreach ($materialMultiColor as $mat) {
            foreach ($colors as $color) {
                $materialItems[] = [
                    'code' => $mat . $color,
                    'name' => "{$mat} — {$color}",
                    'unit' => 'kg', // default material kg
                    'type' => 'material',
                ];
            }
        }

        // ============================
        // RAW MATERIAL (single color / default)
        // ============================
        $materialFixed = [
            [
                'code' => 'KARETWHT',
                'name' => 'Karet Putih',
                'unit' => 'kg',
                'type' => 'material',
            ],
        ];

        // gabungkan semua
        $all = array_merge(
            $finishedItems,
            $materialItems,
            $materialFixed
        );

        // insert / updateOrCreate
        foreach ($all as $row) {
            Item::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                    'type' => $row['type'],
                ]
            );
        }
    }
}
