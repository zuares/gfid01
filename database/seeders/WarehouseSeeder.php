<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ============================
            //  RAW MATERIAL + LOT
            // ============================
            [
                'code' => 'RAW',
                'name' => 'Raw Material',
                'type' => 'raw_material',
            ],
            [
                'code' => 'LOT',
                'name' => 'LOT Bahan',
                'type' => 'lot_raw',
            ],

            // ============================
            //  WIP INTERNAL
            // ============================
            [
                'code' => 'WIP-CUT',
                'name' => 'WIP Cutting',
                'type' => 'wip_cut',
            ],
            [
                'code' => 'WIP-SEW',
                'name' => 'WIP Sewing',
                'type' => 'wip_sew',
            ],
            [
                'code' => 'WIP-FIN',
                'name' => 'WIP Finishing',
                'type' => 'wip_fin',
            ],

            // ============================
            //  FINISHED GOODS (2 GUDANGMU)
            // ============================
            [
                'code' => 'KONTRAKAN',
                'name' => 'Barang Jadi — Kontrakan',
                'type' => 'fg',
            ],
            [
                'code' => 'RUMAH',
                'name' => 'Barang Jadi — Rumah',
                'type' => 'fg',
            ],

            // ============================
            //  REWORK / REJECT
            // ============================
            [
                'code' => 'REWORK',
                'name' => 'WIP Reject / Rework',
                'type' => 'wip_reject',
            ],
            [
                'code' => 'REJ-CUT',
                'name' => 'Reject Cutting',
                'type' => 'reject_cut',
            ],
            [
                'code' => 'REJ-SEW',
                'name' => 'Reject Sewing',
                'type' => 'reject_sew',
            ],
            [
                'code' => 'REJ-FIN',
                'name' => 'Reject Finishing',
                'type' => 'reject_fin',
            ],

            // ============================
            //  RETURN STORAGE
            // ============================
            [
                'code' => 'RETURN-SEW',
                'name' => 'Return Sewing (Sisa Operator)',
                'type' => 'return_sew',
            ],
        ];

        foreach ($data as $row) {
            Warehouse::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                ]
            );
        }
    }
}
