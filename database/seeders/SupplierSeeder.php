<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('suppliers')->updateOrInsert(['code' => 'TPL'], [
            'name' => 'Toplis Jaya', 'phone' => '0812xxxx', 'address' => 'Cibolerang', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('suppliers')->updateOrInsert(['code' => 'ORG'], [
            'name' => 'Origami', 'phone' => '0813xxxx', 'address' => 'Cikeueus', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
