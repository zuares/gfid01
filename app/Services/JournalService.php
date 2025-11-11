<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function postPurchase(string $refCode, string $date, float $amount, bool $cash = false, ?string $memo = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->toDateString();
        $prefix = 'JRN-' . $dateObj->format('Ymd') . '-';

        // === Mapping akun sesuai seeder:
        // 1201 Persediaan Bahan (Debit), 1101 Kas (Kredit jika tunai), 2101 Hutang Dagang (Kredit jika kredit)
        $accPersediaan = \DB::table('accounts')->where('code', '1201')->first(); // Persediaan Bahan
        $accCash = \DB::table('accounts')->where('code', '1101')->first(); // Kas
        $accAP = \DB::table('accounts')->where('code', '2101')->first(); // Hutang Dagang

        if (!$accPersediaan || !$accAP) {
            throw new \RuntimeException('Akun 1201/2101 belum ada. Seed AccountSeeder dulu.');
        }
        $useCash = $cash && $accCash;

        $autoMemo = sprintf(
            'Pembelian %s %s sebesar Rp %s',
            $useCash ? 'tunai' : 'kredit',
            $refCode,
            number_format($amount, 0, ',', '.')
        );
        $memo = $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : $autoMemo;

        DB::transaction(function () use ($prefix, $dateStr, $refCode, $amount, $memo, $accPersediaan, $accCash, $accAP, $useCash) {
            $seq = $this->nextSeq($prefix);
            $jrCode = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

            $jrId = DB::table('journal_entries')->insertGetId([
                'code' => $jrCode,
                'date' => $dateStr,
                'ref_code' => $refCode,
                'memo' => $memo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Dr 1201 Persediaan Bahan
            DB::table('journal_lines')->insert([
                'journal_entry_id' => $jrId,
                'account_id' => $accPersediaan->id,
                'debit' => $amount,
                'credit' => 0,
                'note' => 'Persediaan bertambah dari pembelian',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($useCash) {
                // Cr 1101 Kas
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jrId,
                    'account_id' => $accCash->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'note' => 'Kas keluar untuk pembelian',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Cr 2101 Hutang Dagang
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jrId,
                    'account_id' => $accAP->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'note' => 'Hutang timbul dari pembelian',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // guard balance
            $tot = DB::table('journal_lines')->selectRaw('SUM(debit) td, SUM(credit) tc')->where('journal_entry_id', $jrId)->first();
            if (round((float) $tot->td, 2) !== round((float) $tot->tc, 2)) {
                throw new \RuntimeException('Jurnal tidak balance: ' . $jrCode);
            }
        });
    }

    protected function nextSeq(string $prefix): int
    {
        $max = DB::table('journal_entries')
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTR(code, ?) AS INTEGER)) AS maxnum", [strlen($prefix) + 1])
            ->value('maxnum');
        return ((int) $max) + 1;
    }
}
