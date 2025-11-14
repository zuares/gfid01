<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpeningBalanceSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::now()->toDateString();

        // ======== 1. Akun dasar (upsert agar tidak double) ========
        $accounts = [
            ['code' => '1101', 'name' => 'Kas', 'type' => 'asset'],
            ['code' => '1102', 'name' => 'Bank', 'type' => 'asset'],
            ['code' => '1201', 'name' => 'Persediaan Bahan', 'type' => 'asset'],
            ['code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'liability'],
            ['code' => '3101', 'name' => 'Modal Pemilik', 'type' => 'equity'],
        ];

        DB::table('accounts')->upsert(
            $accounts,
            ['code'], // unique key
            ['name', 'type', 'updated_at']
        );

        // ======== 2. Jurnal saldo awal (hapus dulu kalau ada kode sama) ========
        $code = 'JRN-' . now()->format('Ymd') . '-001';
        DB::table('journals')->where('ref_code', 'SALDO-AWAL')->delete();

        $journalId = DB::table('journal_entries')->insertGetId([
            'code' => $code,
            'date' => $today,
            'ref_code' => 'SALDO-AWAL',
            'memo' => 'Saldo awal per ' . $today,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ======== 3. Detail debit/kredit ========
        $lines = [
            ['code' => '1101', 'debit' => 10_000_000, 'credit' => 0, 'note' => 'Saldo awal kas'],
            ['code' => '1201', 'debit' => 15_000_000, 'credit' => 0, 'note' => 'Saldo awal persediaan'],
            ['code' => '2101', 'debit' => 0, 'credit' => 5_000_000, 'note' => 'Saldo awal hutang dagang'],
            ['code' => '3101', 'debit' => 0, 'credit' => 20_000_000, 'note' => 'Modal awal'],
        ];

        foreach ($lines as $line) {
            $accountId = DB::table('accounts')->where('code', $line['code'])->value('id');
            if ($accountId) {
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $journalId,
                    'account_id' => $accountId,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'note' => $line['note'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
