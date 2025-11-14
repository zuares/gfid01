<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeePieceRate;
use App\Models\Item;
use Illuminate\Database\Seeder;

class EmployeePieceRateSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::whereIn('code', ['MRF', 'MYD', 'BBI'])
            ->get()
            ->keyBy('code');

        $items = Item::whereIn('code', ['K7BLK', 'K5BLK'])
            ->get()
            ->keyBy('code');

        // rate contoh (Rp per pcs)
        $rows = [
            // Cutting umum (semua item)
            [
                'employee_code' => 'MYD',
                'process' => 'sewing',
                'item_code' => 'K7BLK',
                'rate' => 6000.0,
            ],
            // Sewing khusus K5BLK
            [
                'employee_code' => 'BBI',
                'process' => 'sewing',
                'item_code' => 'K7BLK',
                'rate' => 5000.0,
            ],
            // Finishing umum (semua item) untuk MRF
            [
                'employee_code' => 'MRF',
                'process' => 'cutting',
                'item_code' => null,
                'rate' => 800.0,
            ],
        ];

        foreach ($rows as $row) {
            $employee = $employees[$row['employee_code']] ?? null;
            if (!$employee) {
                continue;
            }

            $itemId = null;
            if ($row['item_code']) {
                $itemId = $items[$row['item_code']]->id ?? null;
            }

            EmployeePieceRate::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'process' => $row['process'],
                    'item_id' => $itemId,
                ],
                [
                    'rate_per_piece' => $row['rate'],
                    'effective_from' => now()->subMonth()->toDateString(),
                    'effective_to' => null,
                    'active' => true,
                ]
            );
        }
    }
}
