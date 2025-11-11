<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('accounts')->updateOrInsert(['code' => '1101'], [
            'name' => 'Persediaan', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('accounts')->updateOrInsert(['code' => '1110'], [
            'name' => 'Kas/Bank', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('accounts')->updateOrInsert(['code' => '2101'], [
            'name' => 'Hutang Dagang', 'type' => 'liability', 'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}
