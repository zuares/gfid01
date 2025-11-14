<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Item;
use App\Models\ProductionBatch;
use Illuminate\Database\Seeder;

class SampleProductionBatchesSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::whereIn('code', ['MRF', 'MYD', 'BBI'])
            ->get()
            ->keyBy('code');

        $items = Item::whereIn('code', ['K7BLK', 'K5BLK'])
            ->get()
            ->keyBy('code');

        if ($employees->isEmpty() || $items->isEmpty()) {
            // pastikan ItemSeeder & EmployeeSeeder sudah dijalankan
            return;
        }

        // ==== SAMPLE CUTTING ====
        // MYD memotong kain jadi K7BLK (300 pcs) dan K5BLK (150 pcs)
        $cuttingDate = now()->subDays(7)->toDateString();
        $cuttingBatch = ProductionBatch::firstOrCreate(
            ['code' => 'BCH-CUT-EXAMPLE-001'],
            [
                'date' => $cuttingDate,
                'process' => 'cutting',
                'status' => 'done',
                'external_transfer_id' => null,
                'lot_id' => null,
                'from_warehouse_id' => null,
                'to_warehouse_id' => null,
                'operator_code' => 'MYD',
                'input_qty' => 10.5,
                'input_uom' => 'kg',
                'output_total_pcs' => 450,
                'output_items_json' => [
                    'K7BLK' => 300,
                    'K5BLK' => 150,
                ],
                'waste_qty' => 0.5,
                'remain_qty' => 1.0,
                'notes' => 'Sample cutting batch',
                'created_by' => null,
                'updated_by' => null,
            ]
        );

        // ==== SAMPLE SEWING ====
        // MRF menjahit 250 pcs K7BLK dari batch cutting
        $sewingDate1 = now()->subDays(5)->toDateString();
        $sewingBatch1 = ProductionBatch::firstOrCreate(
            ['code' => 'BCH-SEW-EXAMPLE-001'],
            [
                'date' => $sewingDate1,
                'process' => 'sewing',
                'status' => 'done',
                'external_transfer_id' => null,
                'lot_id' => null,
                'from_warehouse_id' => null,
                'to_warehouse_id' => null,
                'operator_code' => 'MRF',
                'input_qty' => 250,
                'input_uom' => 'pcs',
                'output_total_pcs' => 240,
                'output_items_json' => [
                    'K7BLK' => 240,
                ],
                'waste_qty' => 10,
                'remain_qty' => 10,
                'notes' => 'Sample sewing batch MRF',
                'created_by' => null,
                'updated_by' => null,
            ]
        );

        // BBI menjahit 120 pcs K5BLK
        $sewingDate2 = now()->subDays(4)->toDateString();
        $sewingBatch2 = ProductionBatch::firstOrCreate(
            ['code' => 'BCH-SEW-EXAMPLE-002'],
            [
                'date' => $sewingDate2,
                'process' => 'sewing',
                'status' => 'done',
                'external_transfer_id' => null,
                'lot_id' => null,
                'from_warehouse_id' => null,
                'to_warehouse_id' => null,
                'operator_code' => 'BBI',
                'input_qty' => 120,
                'input_uom' => 'pcs',
                'output_total_pcs' => 118,
                'output_items_json' => [
                    'K7BLK' => 118,
                ],
                'waste_qty' => 2,
                'remain_qty' => 0,
                'notes' => 'Sample sewing batch BBI',
                'created_by' => null,
                'updated_by' => null,
            ]
        );

        // ==== SAMPLE FINISHING ====
        // MRF finishing 200 pcs K7BLK (dari hasil sewing)
        $finishingDate = now()->subDays(2)->toDateString();
        $finishingBatch = ProductionBatch::firstOrCreate(
            ['code' => 'BCH-FIN-EXAMPLE-001'],
            [
                'date' => $finishingDate,
                'process' => 'finishing',
                'status' => 'done',
                'external_transfer_id' => null,
                'lot_id' => null,
                'from_warehouse_id' => null,
                'to_warehouse_id' => null,
                'operator_code' => 'MRF',
                'input_qty' => 200,
                'input_uom' => 'pcs',
                'output_total_pcs' => 195,
                'output_items_json' => [
                    'K7BLK' => 195,
                ],
                'waste_qty' => 5,
                'remain_qty' => 0,
                'notes' => 'Sample finishing batch',
                'created_by' => null,
                'updated_by' => null,
            ]
        );
    }
}
