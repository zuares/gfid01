<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('suppliers')->updateOrInsert(['code' => 'SUPP-001'], [
            'name' => 'CV Tekstil Makmur', 'phone' => '0812xxxx', 'address' => 'Bandung', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('suppliers')->updateOrInsert(['code' => 'SUPP-002'], [
            'name' => 'UD Bahan Pendukung', 'phone' => '0813xxxx', 'address' => 'Jakarta', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
