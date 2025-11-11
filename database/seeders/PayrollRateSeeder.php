<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollRateSeeder extends Seeder
{
    public function run(): void
    {
        // default rate semua SKU
        DB::table('payroll_rates')->updateOrInsert(
            ['role' => 'cutting', 'item_id' => null],
            ['rate' => 800, 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('payroll_rates')->updateOrInsert(
            ['role' => 'jahit', 'item_id' => null],
            ['rate' => 5000, 'created_at' => now(), 'updated_at' => now()]
        );

        // contoh override khusus K7BLK (opsional)
        // DB::table('payroll_rates')->updateOrInsert(
        //     ['role'=>'jahit','item_id'=>DB::table('items')->where('code','K7BLK')->value('id')],
        //     ['rate'=>5500, 'created_at'=>now(),'updated_at'=>now()]
        // );
    }
}
