<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // daftar akun yang ingin dipastikan ada/terupdate
        $rows = [
            ['code' => '1101', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'D'],
            ['code' => '1110', 'name' => 'Bank', 'type' => 'asset', 'normal_balance' => 'D'],
            ['code' => '1201', 'name' => 'Persediaan Bahan', 'type' => 'asset', 'normal_balance' => 'D'],
            ['code' => '1202', 'name' => 'Persediaan Barang Jadi', 'type' => 'asset', 'normal_balance' => 'D'],
            ['code' => '1301', 'name' => 'Piutang Usaha', 'type' => 'asset', 'normal_balance' => 'D'],
            ['code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'liability', 'normal_balance' => 'C'],
            ['code' => '2102', 'name' => 'Hutang Gaji', 'type' => 'liability', 'normal_balance' => 'C'],
            ['code' => '3101', 'name' => 'Modal Pemilik', 'type' => 'equity', 'normal_balance' => 'C'],
            ['code' => '3201', 'name' => 'Prive Pemilik', 'type' => 'equity', 'normal_balance' => 'D'],
            ['code' => '4101', 'name' => 'Penjualan', 'type' => 'revenue', 'normal_balance' => 'C'],
            ['code' => '4201', 'name' => 'Pendapatan Lain-lain', 'type' => 'revenue', 'normal_balance' => 'C'],
            ['code' => '5101', 'name' => 'Beban Gaji', 'type' => 'expense', 'normal_balance' => 'D'],
            ['code' => '5102', 'name' => 'Beban Listrik dan Air', 'type' => 'expense', 'normal_balance' => 'D'],
            ['code' => '5103', 'name' => 'Beban Transportasi', 'type' => 'expense', 'normal_balance' => 'D'],
            ['code' => '5104', 'name' => 'Beban Operasional Lainnya', 'type' => 'expense', 'normal_balance' => 'D'],
        ];

        // upsert berdasarkan kolom unik 'code'
        DB::table('accounts')->upsert(
            $rows,
            ['code'], // unique key
            ['name', 'type', 'normal_balance', 'updated_at']// kolom yang di-update
        );
    }
}
