<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use Carbon\Carbon; // 🔴 tambahkan ini
use Illuminate\Support\Facades\DB;

class JournalService
{

    public function createJournal($date, $source, $ref, array $lines, $notes = null)
    {
        return DB::transaction(function () use ($date, $source, $ref, $lines, $notes) {

            // === GENERATE KODE JURNAL ===
            $prefix = 'JRN';
            $today = now()->format('ymd');

            $countToday = JournalEntry::whereDate('created_at', now()->toDateString())->count();
            $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

            $code = $prefix . '-' . $today . '-' . $seq;

            // === INSERT HEADER ===
            $entry = JournalEntry::create([
                'code' => $code, // ← WAJIB ADA
                'date' => $date,
                'description' => trim(($source ?? '') . ' ' . ($ref ?? '') . ' ' . ($notes ?? '')),
            ]);

            // === INSERT DETAIL ===
            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_code'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'note' => $line['note'] ?? null,
                ]);
            }

            return $entry;
        });
    }
    /**
     * === PEMBELIAN (KOMPATIBILITAS VERSI LAMA) ===
     * Dr 1201 Persediaan | Cr 1101 Kas (jika $cash = true) ATAU Cr 2101 Hutang (jika $cash = false)
     *
     * Rekomendasi baru: panggil postPurchaseSplit() langsung dari controller.
     */
    public function postPurchase(string $refCode, string $date, float $amount, bool $cash = false, ?string $memo = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $cashPaid = $cash ? $amount : 0.0;
        $payableRemain = max(0.0, $amount - $cashPaid);

        $this->postPurchaseSplit(
            refCode: $refCode,
            date: $date,
            inventoryAmount: $amount,
            cashPaid: $cashPaid,
            payableRemain: $payableRemain,
            cashAccountNote: $cash ? 'CASH' : null,
            memo: $memo
        );
    }

    /**
     * === PEMBELIAN (SKEMA BARU – DISARANKAN) ===
     * Dr 1201 Persediaan (inventoryAmount)
     * Cr 1101/1102 Kas/Bank (cashPaid, jika >0)
     * Cr 2101 Hutang Dagang (payableRemain, jika >0)
     */
    public function postPurchaseSplit(
        string $refCode,
        string $date,
        float $inventoryAmount,
        float $cashPaid,
        float $payableRemain,
        ?string $cashAccountNote = null,
        ?string $memo = null
    ): void {
        if ($inventoryAmount <= 0) {
            return;
        }

        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->toDateString();

        // === UBAH DI SINI: JRN-YYMMDD- ===
        $prefix = 'JRN-' . $dateObj->format('ymd') . '-';

        // Akun
        $accPersediaan = DB::table('accounts')->where('code', '1201')->first();
        $accCash = DB::table('accounts')->where('code', '1101')->first();
        $accBank = DB::table('accounts')->where('code', '1102')->first();
        $accAP = DB::table('accounts')->where('code', '2101')->first();

        if (!$accPersediaan || !$accAP) {
            throw new \RuntimeException('Akun 1201/2101 belum ada. Seed AccountSeeder dulu.');
        }

        $creditCashAccountId = $accBank->id ?? $accCash->id ?? null;

        $autoMemo = sprintf(
            'Pembelian %s sebesar Rp %s (bayar: Rp %s, sisa: Rp %s)',
            $refCode,
            number_format($inventoryAmount, 0, ',', '.'),
            number_format(max(0, $cashPaid), 0, ',', '.'),
            number_format(max(0, $payableRemain), 0, ',', '.')
        );
        $memo = $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : $autoMemo;

        $lines = [];

        // Dr Persediaan
        $lines[] = [
            'account_id' => $accPersediaan->id,
            'debit' => $inventoryAmount,
            'credit' => 0,
            'note' => 'Persediaan bertambah dari pembelian',
        ];

        // Cr Kas/Bank jika bayar
        if ($cashPaid > 0) {
            if (!$creditCashAccountId) {
                throw new \RuntimeException('Akun kas/bank (1101/1102) belum tersedia.');
            }
            $note = 'Kas/Bank keluar untuk pembelian';
            if ($cashAccountNote) {
                $note .= " ({$cashAccountNote})";
            }

            $lines[] = [
                'account_id' => $creditCashAccountId,
                'debit' => 0,
                'credit' => $cashPaid,
                'note' => $note,
            ];
        }

        // Cr Hutang jika sisa
        if ($payableRemain > 0) {
            $lines[] = [
                'account_id' => $accAP->id,
                'debit' => 0,
                'credit' => $payableRemain,
                'note' => 'Hutang timbul dari pembelian (sisa)',
            ];
        }

        $this->postBalanced($prefix, $dateStr, $refCode, $memo, $lines);
    }

    /** === PEMBAYARAN HUTANG PEMBELIAN (DP/Termin) === */
    public function postPaymentPurchase(string $refCode, string $date, float $amount, string $method = 'cash', ?string $memo = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->toDateString();
        // === UBAH DI SINI: JRN-YYMMDD- ===
        $prefix = 'JRN-' . $dateObj->format('ymd') . '-';

        $accAP = DB::table('accounts')->where('code', '2101')->first();
        $accCash = DB::table('accounts')->where('code', '1101')->first();
        $accBank = DB::table('accounts')->where('code', '1102')->first();

        if (!$accAP) {
            throw new \RuntimeException('Akun 2101 (Hutang Dagang) belum ada.');
        }

        $method = strtolower($method);
        $creditAccountId = match ($method) {
            'bank', 'transfer' => ($accBank?->id ?? $accCash?->id),
            'cash', 'other' => $accCash?->id,
            default => $accCash?->id,
        };

        if (!$creditAccountId) {
            throw new \RuntimeException('Akun kas/bank (1101/1102) belum tersedia.');
        }

        $autoMemo = sprintf('Pembayaran pembelian %s sebesar Rp %s', $refCode, number_format($amount, 0, ',', '.'));
        $memo = $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : $autoMemo;

        $lines = [
            ['account_id' => $accAP->id, 'debit' => $amount, 'credit' => 0, 'note' => 'Pelunasan/DP hutang pembelian'],
            ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $amount, 'note' => 'Kas/Bank keluar untuk pembayaran pembelian'],
        ];

        $this->postBalanced($prefix, $dateStr, $refCode, $memo, $lines);
    }

    /** === REVERSAL PEMBAYARAN === */
    public function reversePaymentPurchase(string $refCode, string $date, float $amount, string $method = 'cash', ?string $memo = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->toDateString();
        // === UBAH DI SINI: JRN-YYMMDD- ===
        $prefix = 'JRN-' . $dateObj->format('ymd') . '-';

        $accAP = DB::table('accounts')->where('code', '2101')->first();
        $accCash = DB::table('accounts')->where('code', '1101')->first();
        $accBank = DB::table('accounts')->where('code', '1102')->first();

        if (!$accAP) {
            throw new \RuntimeException('Akun 2101 (Hutang Dagang) belum ada.');
        }

        $method = strtolower($method);
        $debitAccountId = match ($method) {
            'bank', 'transfer' => ($accBank?->id ?? $accCash?->id),
            'cash', 'other' => $accCash?->id,
            default => $accCash?->id,
        };

        if (!$debitAccountId) {
            throw new \RuntimeException('Akun kas/bank (1101/1102) belum tersedia.');
        }

        $autoMemo = sprintf('Reversal pembayaran pembelian %s sebesar Rp %s', $refCode, number_format($amount, 0, ',', '.'));
        $memo = $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : $autoMemo;

        $lines = [
            ['account_id' => $debitAccountId, 'debit' => $amount, 'credit' => 0, 'note' => 'Reversal pembayaran pembelian (kas/bank kembali)'],
            ['account_id' => $accAP->id, 'debit' => 0, 'credit' => $amount, 'note' => 'Reversal: hutang bertambah kembali'],
        ];

        $this->postBalanced($prefix, $dateStr, $refCode, $memo, $lines);
    }

    /**
     * Helper: Insert journal entry + lines & guard balance.
     * Menghasilkan kode JRN-YYMMDD-£££
     */
    protected function postBalanced(string $prefix, string $dateStr, string $refCode, ?string $memo, array $lines): void
    {
        DB::transaction(function () use ($prefix, $dateStr, $refCode, $memo, $lines) {
            $seq = $this->nextSeq($prefix);
            // 3 digit sesuai £££
            $jrCode = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

            $jrId = DB::table('journal_entries')->insertGetId([
                'code' => $jrCode,
                'date' => $dateStr,
                'ref_code' => $refCode,
                'memo' => $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = [];
            $totDr = 0.0;
            $totCr = 0.0;

            foreach ($lines as $l) {
                $dr = (float) ($l['debit'] ?? 0);
                $cr = (float) ($l['credit'] ?? 0);
                $rows[] = [
                    'journal_entry_id' => $jrId,
                    'account_id' => $l['account_id'],
                    'debit' => $dr,
                    'credit' => $cr,
                    'note' => $l['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $totDr += $dr;
                $totCr += $cr;
            }

            if (round($totDr - $totCr, 2) !== 0.00) {
                throw new \RuntimeException("Jurnal tidak balance: {$jrCode}");
            }

            DB::table('journal_lines')->insert($rows);
        });
    }

    /**
     * Penomoran sequence JRN-YYMMDD-£££
     */
    protected function nextSeq(string $prefix): int
    {
        $max = DB::table('journal_entries')
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTR(code, ?) AS INTEGER)) AS maxnum", [strlen($prefix) + 1])
            ->value('maxnum');

        return ((int) $max) + 1;
    }
}
