<?php

// database/seeders/EmployeeSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'MRF', 'name' => 'Maruf', 'role' => 'cutting', 'active' => 1],
            ['code' => 'MYD', 'name' => 'Miyadi', 'role' => 'cutting', 'active' => 1],
            ['code' => 'BBI', 'name' => 'Bambang I', 'role' => 'sewing', 'active' => 1],
            ['code' => 'RDN', 'name' => 'Raden', 'role' => 'sewing', 'active' => 1],
        ];

        foreach ($rows as $r) {
            DB::table('employees')->updateOrInsert(['code' => $r['code']], $r + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
