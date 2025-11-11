<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function postPurchase(string $refCode, string $date, float $amount, bool $cash = false, ?string $memo = null): void
    {
        if ($amount <= 0) {
            return;
        }

        // generate kode jurnal sederhana: JRN-YYYYMMDD-###
        $prefix = 'JRN-' . date('Ymd', strtotime($date)) . '-';
        $seq = $this->nextSeq($prefix);
        $jrCode = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        $accPersediaan = Account::where('code', '1101')->firstOrFail();
        $accCash = Account::where('code', '1110')->first(); // boleh null
        $accAP = Account::where('code', '2101')->firstOrFail();

        DB::transaction(function () use ($jrCode, $date, $refCode, $amount, $cash, $memo, $accPersediaan, $accCash, $accAP) {
            $jr = JournalEntry::create([
                'code' => $jrCode,
                'date' => $date,
                'ref_code' => $refCode,
                'memo' => $memo,
            ]);

            // Dr Persediaan
            JournalLine::create([
                'journal_entry_id' => $jr->id,
                'account_id' => $accPersediaan->id,
                'debit' => $amount,
                'credit' => 0,
                'note' => 'Pembelian barang/ bahan',
            ]);

            if ($cash && $accCash) {
                // Cr Kas/Bank
                JournalLine::create([
                    'journal_entry_id' => $jr->id,
                    'account_id' => $accCash->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'note' => 'Pembelian tunai',
                ]);
            } else {
                // Cr Hutang Dagang
                JournalLine::create([
                    'journal_entry_id' => $jr->id,
                    'account_id' => $accAP->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'note' => 'Pembelian kredit',
                ]);
            }
        });
    }

    protected function nextSeq(string $prefix): int
    {
        $max = DB::table('journal_entries')
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("max(substr(code, length(?) + 1, 10)) as suffix", [$prefix])
            ->value('suffix');

        return ((int) $max) + 1;
    }
}
