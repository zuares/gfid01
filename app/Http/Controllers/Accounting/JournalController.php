<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    /** Daftar jurnal: filter q (kode/ref/memo) + range tanggal, sort terbaru */
    public function index(Request $r)
    {
        $q = trim((string) $r->get('q', ''));
        $range = $r->get('range'); // "YYYY-MM-DD s/d YYYY-MM-DD"

        $rows = JournalEntry::query()
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                        ->orWhere('ref_code', 'like', "%{$q}%")
                        ->orWhere('memo', 'like', "%{$q}%");
                });
            })
            ->when($range, function ($qq) use ($range) {
                if (preg_match('~^\s*(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})\s*$~', $range, $m)) {
                    $qq->whereBetween('date', [$m[1], $m[2]]);
                }
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('accounting.journals.index', compact('q', 'range', 'rows'));
    }

    /** Detail satu jurnal + baris debet/kredit */
    public function show($id)
    {
        $jr = JournalEntry::with(['lines.account'])->findOrFail($id);

        $totalDebit = $jr->lines->sum('debit');
        $totalCredit = $jr->lines->sum('credit');

        return view('accounting.journals.show', compact('jr', 'totalDebit', 'totalCredit'));
    }

    /** Buku Besar (ledger): filter tanggal & akun
     *  - Jika akun kosong: tampilkan summary saldo semua akun (opening, debit, credit, closing)
     *  - Jika akun dipilih: tampilkan detail baris akun tsb per tanggal
     */
    public function ledger(Request $r)
    {
        $accountId = $r->get('account_id'); // optional
        $range = $r->get('range'); // "YYYY-MM-DD s/d YYYY-MM-DD"
        $dateFrom = $dateTo = null;

        if ($range && preg_match('~^\s*(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})\s*$~', $range, $m)) {
            $dateFrom = $m[1];
            $dateTo = $m[2];
        }

        $accounts = Account::orderBy('code')->get(['id', 'code', 'name', 'type']);

        // Jika pilih 1 akun → detail
        if ($accountId) {
            $acc = $accounts->firstWhere('id', (int) $accountId);
            abort_unless($acc, 404);

            // Opening balance s/d hari sebelum $dateFrom
            $opening = 0.0;
            if ($dateFrom) {
                $sum = JournalLine::query()
                    ->selectRaw('COALESCE(SUM(debit - credit),0) as bal')
                    ->where('account_id', $acc->id)
                    ->whereHas('journalEntry', fn($w) => $w->where('date', '<', $dateFrom))
                    ->value('bal') ?? 0;
                $opening = (float) $sum;
            }

            // Mutasi dalam rentang
            $query = JournalLine::with(['journalEntry:id,date,code,ref_code,memo'])
                ->where('account_id', $acc->id)
                ->when($dateFrom && $dateTo, fn($qq) => $qq->whereHas('journalEntry', fn($w) => $w->whereBetween('date', [$dateFrom, $dateTo])))
                ->when(!$dateFrom || !$dateTo, fn($qq) => $qq->whereHas('journalEntry')) // no filter → all
                ->orderBy(
                    JournalEntry::select('date')->whereColumn('journal_entries.id', 'journal_lines.journal_entry_id')
                )
                ->orderBy('id');

            $lines = $query->get();

            // Running balance
            $running = $opening;
            $rows = $lines->map(function ($x) use (&$running) {
                $running += ((float) $x->debit - (float) $x->credit);
                return [
                    'date' => optional($x->journalEntry?->date)->format('Y-m-d'),
                    'jr_code' => $x->journalEntry?->code,
                    'ref_code' => $x->journalEntry?->ref_code,
                    'memo' => $x->journalEntry?->memo,
                    'debit' => (float) $x->debit,
                    'credit' => (float) $x->credit,
                    'balance' => $running,
                    'note' => $x->note,
                ];
            });

            $totalDebit = $lines->sum('debit');
            $totalCredit = $lines->sum('credit');
            $closing = $opening + ($totalDebit - $totalCredit);

            return view('accounting.ledger.detail', compact(
                'accounts', 'accountId', 'acc', 'range', 'dateFrom', 'dateTo',
                'opening', 'rows', 'totalDebit', 'totalCredit', 'closing'
            ));
        }

        // Jika tidak pilih akun → ringkasan semua akun (opening/mutasi/closing)
        // Untuk performa sederhana: dua query agregat
        $openings = [];
        if ($dateFrom) {
            $openings = JournalLine::query()
                ->select('account_id', DB::raw('COALESCE(SUM(debit - credit),0) as opening'))
                ->whereHas('journalEntry', fn($w) => $w->where('date', '<', $dateFrom))
                ->groupBy('account_id')
                ->pluck('opening', 'account_id')
                ->all();
        }

        $mutasi = JournalLine::query()
            ->select('account_id',
                DB::raw('COALESCE(SUM(debit),0)  as d'),
                DB::raw('COALESCE(SUM(credit),0) as c')
            )
            ->when($dateFrom && $dateTo, fn($qq) => $qq->whereHas('journalEntry', fn($w) => $w->whereBetween('date', [$dateFrom, $dateTo])))
            ->groupBy('account_id')
            ->get();

        $summary = $accounts->map(function ($a) use ($openings, $mutasi) {
            $op = (float) ($openings[$a->id] ?? 0);
            $m = $mutasi->firstWhere('account_id', $a->id);
            $d = (float) ($m->d ?? 0);
            $c = (float) ($m->c ?? 0);
            $cl = $op + ($d - $c);
            return [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'opening' => $op,
                'debit' => $d,
                'credit' => $c,
                'closing' => $cl,
            ];
        });

        return view('accounting.ledger.index', compact('accounts', 'range', 'dateFrom', 'dateTo', 'summary'));
    }
}
