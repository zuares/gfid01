<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    /** Daftar jurnal (index) */
    public function index(Request $r)
    {
        $q = trim((string) $r->get('q', ''));
        $range = $r->get('range'); // "YYYY-MM-DD s/d YYYY-MM-DD"

        $rows = DB::table('journal_entries as je')
            ->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
                $w->where('je.code', 'like', "%{$q}%")
                    ->orWhere('je.ref_code', 'like', "%{$q}%")
                    ->orWhere('je.memo', 'like', "%{$q}%");
            }))
            ->when($range, function ($qq) use ($range) {
                if (preg_match('~^\s*(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})\s*$~', $range, $m)) {
                    $qq->whereBetween('je.date', [$m[1], $m[2]]);
                }
            })
            ->select('je.id', 'je.code', 'je.date', 'je.ref_code', 'je.memo')
            ->orderByDesc('je.date')
            ->orderByDesc('je.id')
            ->paginate(20);

        return view('accounting.journals.index', compact('rows', 'q', 'range'));
    }

    /** Detail 1 jurnal (show) */
    public function show(int $id)
    {
        $jr = DB::table('journal_entries')->where('id', $id)->first();
        abort_if(!$jr, 404);

        $lines = DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_entry_id', $id)
            ->orderBy('jl.id')
            ->get(['a.code as account_code', 'a.name as account_name', 'jl.debit', 'jl.credit', 'jl.note']);

        // total
        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');

        return view('accounting.journals.show', compact('jr', 'lines', 'totalDebit', 'totalCredit'));
    }

    public function ledger(Request $r)
    {
        $accountId = (int) $r->get('account_id');
        $range = $r->get('range');

        $dateFrom = $dateTo = null;
        if ($range && preg_match('~^\s*(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})\s*$~', $range, $m)) {
            $dateFrom = $m[1];
            $dateTo = $m[2];
        }

        // ambil akun untuk dropdown (kolom normal_balance bisa tidak ada di beberapa env)
        $accounts = DB::table('accounts')->orderBy('code')->get(['id', 'code', 'name']);

        // query ledger
        $q = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->when($accountId, fn($qq) => $qq->where('a.id', $accountId))
            ->when($dateFrom && $dateTo, fn($qq) => $qq->whereBetween('je.date', [$dateFrom, $dateTo]))
            ->orderBy('a.code')->orderBy('je.date')->orderBy('jl.id')
            ->get(['a.id as account_id', 'a.code', 'a.name', 'je.date', 'je.code as jcode', 'je.ref_code', 'jl.debit', 'jl.credit', 'jl.note']);

        // fallback normal balance per kode (jika kolom tidak ada)
        $normal = [
            // ASSETS
            '1101' => 'D', '1110' => 'D', '1201' => 'D', '1202' => 'D', '1301' => 'D',
            // LIABILITIES
            '2101' => 'C', '2102' => 'C',
            // EQUITY
            '3101' => 'C', '3201' => 'D',
            // REVENUE
            '4101' => 'C', '4201' => 'C',
            // EXPENSES
            '5101' => 'D', '5102' => 'D', '5103' => 'D', '5104' => 'D',
        ];

        $grouped = $q->groupBy('account_id')->map(function ($rows) use ($normal) {
            $code = $rows->first()->code ?? '';
            $nb = $normal[$code] ?? 'D';
            $balance = 0.0;
            $items = [];
            foreach ($rows as $r) {
                $delta = ($nb === 'D') ? ($r->debit - $r->credit) : ($r->credit - $r->debit);
                $balance += $delta;
                $items[] = [
                    'date' => $r->date,
                    'jcode' => $r->jcode,
                    'ref' => $r->ref_code,
                    'debit' => (float) $r->debit,
                    'credit' => (float) $r->credit,
                    'note' => $r->note,
                    'balance' => $balance,
                    'code' => $r->code,
                    'name' => $r->name,
                ];
            }
            return $items;
        });

        return view('accounting.ledger.index', [
            'accounts' => $accounts,
            'grouped' => $grouped,
            'accountId' => $accountId,
            'range' => $range,
        ]);
    }

}
