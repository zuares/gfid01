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
            ['code' => 'MRF', 'name' => 'Mang Arip', 'role' => 'cutting', 'active' => 1],
            ['code' => 'MYD', 'name' => 'Mang Yadi', 'role' => 'sewing', 'active' => 1],
            ['code' => 'BBI', 'name' => 'Bi Rini', 'role' => 'sewing', 'active' => 1],
            ['code' => 'RDN', 'name' => 'Jang Ridwan', 'role' => 'sewing', 'active' => 1],
        ];

        foreach ($rows as $r) {
            DB::table('employees')->updateOrInsert(['code' => $r['code']], $r + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
