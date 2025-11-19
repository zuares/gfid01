<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SewingReportController extends Controller
{
    /**
     * LEVEL 1 + FILTER: Rekap Sisa Jahit per Operator
     */
    public function operatorBalance(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');

        // Semua operator role=sewing (untuk filter & tabel)
        $operators = Employee::where('role', 'sewing')
            ->orderBy('name')
            ->get();

        // --- Query dasar ambil ---
        $ambilQuery = DB::table('sewing_picks as p')
            ->join('sewing_pick_lines as l', 'l.sewing_pick_id', '=', 'p.id');

        if ($dateFrom) {
            $ambilQuery->whereDate('p.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $ambilQuery->whereDate('p.date', '<=', $dateTo);
        }
        if ($operatorId) {
            $ambilQuery->where('p.operator_id', $operatorId);
        }

        $ambil = $ambilQuery
            ->select('p.operator_id', DB::raw('SUM(l.qty) as total_ambil'))
            ->groupBy('p.operator_id')
            ->pluck('total_ambil', 'p.operator_id');

        // --- Query dasar setor (OK + Reject) ---
        $setorQuery = DB::table('sewing_returns as r')
            ->join('sewing_return_lines as l', 'l.sewing_return_id', '=', 'r.id');

        if ($dateFrom) {
            $setorQuery->whereDate('r.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $setorQuery->whereDate('r.date', '<=', $dateTo);
        }
        if ($operatorId) {
            $setorQuery->where('r.operator_id', $operatorId);
        }

        $setor = $setorQuery
            ->select(
                'r.operator_id',
                DB::raw('SUM(l.qty_ok) as total_ok'),
                DB::raw('SUM(l.qty_reject) as total_reject')
            )
            ->groupBy('r.operator_id')
            ->get()
            ->keyBy('operator_id');

        // Compose data akhir
        $rows = $operators->map(function ($op) use ($ambil, $setor) {
            $ambilQty = (float) ($ambil[$op->id] ?? 0);

            $set = $setor[$op->id] ?? null;
            $setorOk = $set ? (float) $set->total_ok : 0;
            $setorReject = $set ? (float) $set->total_reject : 0;

            $sisa = max($ambilQty - ($setorOk + $setorReject), 0);

            return [
                'operator' => $op,
                'ambil' => $ambilQty,
                'setor_ok' => $setorOk,
                'setor_reject' => $setorReject,
                'sisa' => $sisa,
            ];
        });

        return view('production.sewing_report.operator_balance', [
            'rows' => $rows,
            'operators' => $operators,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'operatorId' => $operatorId,
        ]);
    }

    /**
     * LEVEL 2: Detail per Operator (per item/lot + histori)
     */
    public function operatorDetail(Request $request, Employee $operator)
    {
        // pastikan ini operator sewing
        if ($operator->role !== 'sewing') {
            abort(404, 'Operator bukan penjahit (sewing).');
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // ========================
        // 1) REKAP PER ITEM (TANPA LOT)
        // ========================

        // Ambil jahit group by item
        $ambilPerItemQuery = DB::table('sewing_picks as p')
            ->join('sewing_pick_lines as l', 'l.sewing_pick_id', '=', 'p.id')
            ->leftJoin('items as i', 'i.id', '=', 'l.item_id')
            ->where('p.operator_id', $operator->id);

        if ($dateFrom) {
            $ambilPerItemQuery->whereDate('p.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $ambilPerItemQuery->whereDate('p.date', '<=', $dateTo);
        }

        $ambilPerItem = $ambilPerItemQuery
            ->select(
                'l.item_id',
                'l.item_code',
                'i.name as item_name',
                DB::raw('SUM(l.qty) as total_ambil')
            )
            ->groupBy('l.item_id', 'l.item_code', 'i.name')
            ->get();

        // Setor jahit group by item (OK + Reject)
        $setorPerItemQuery = DB::table('sewing_returns as r')
            ->join('sewing_return_lines as l', 'l.sewing_return_id', '=', 'r.id')
            ->where('r.operator_id', $operator->id);

        if ($dateFrom) {
            $setorPerItemQuery->whereDate('r.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $setorPerItemQuery->whereDate('r.date', '<=', $dateTo);
        }

        $setorPerItemRaw = $setorPerItemQuery
            ->select(
                'l.item_id',
                DB::raw('SUM(l.qty_ok) as total_ok'),
                DB::raw('SUM(l.qty_reject) as total_reject')
            )
            ->groupBy('l.item_id')
            ->get();

        // Convert ke map [item_id => [ok, reject]]
        $setorMap = [];
        foreach ($setorPerItemRaw as $row) {
            $setorMap[$row->item_id] = [
                'ok' => (float) $row->total_ok,
                'reject' => (float) $row->total_reject,
            ];
        }

        // Gabung ambil + setor + sisa per item
        $rekapItem = $ambilPerItem->map(function ($row) use ($setorMap) {
            $itemId = $row->item_id;

            $totalAmbil = (float) $row->total_ambil;
            $totalOk = $setorMap[$itemId]['ok'] ?? 0;
            $totalReject = $setorMap[$itemId]['reject'] ?? 0;
            $sisa = max($totalAmbil - ($totalOk + $totalReject), 0);

            return (object) [
                'item_code' => $row->item_code,
                'item_name' => $row->item_name,
                'total_ambil' => $totalAmbil,
                'total_ok' => $totalOk,
                'total_reject' => $totalReject,
                'sisa' => $sisa,
            ];
        })->sortByDesc('sisa')->values();

        // =========================
        // 2) HISTORI AMBIL PER HARI (TANPA LOT)
        // =========================
        $historiAmbilQuery = DB::table('sewing_picks as p')
            ->join('sewing_pick_lines as l', 'l.sewing_pick_id', '=', 'p.id')
            ->leftJoin('items as i', 'i.id', '=', 'l.item_id')
            ->where('p.operator_id', $operator->id);

        if ($dateFrom) {
            $historiAmbilQuery->whereDate('p.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $historiAmbilQuery->whereDate('p.date', '<=', $dateTo);
        }

        $historiAmbil = $historiAmbilQuery
            ->select(
                'p.code as doc_code',
                'p.date',
                'l.item_code',
                'i.name as item_name',
                'l.qty'
            )
            ->orderBy('p.date')
            ->orderBy('p.code')
            ->get();

        // =========================
        // 3) HISTORI SETOR PER HARI (TANPA LOT)
        // =========================
        $historiSetorQuery = DB::table('sewing_returns as r')
            ->join('sewing_return_lines as l', 'l.sewing_return_id', '=', 'r.id')
            ->leftJoin('items as i', 'i.id', '=', 'l.item_id')
            ->where('r.operator_id', $operator->id);

        if ($dateFrom) {
            $historiSetorQuery->whereDate('r.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $historiSetorQuery->whereDate('r.date', '<=', $dateTo);
        }

        $historiSetor = $historiSetorQuery
            ->select(
                'r.code as doc_code',
                'r.date',
                'l.item_code',
                'i.name as item_name',
                'l.qty_ok',
                'l.qty_reject'
            )
            ->orderBy('r.date')
            ->orderBy('r.code')
            ->get();

        return view('production.sewing_report.operator_detail', [
            'operator' => $operator,
            'rekapItemLot' => $rekapItem, // nama variabel view tetap sama biar nggak perlu ubah banyak
            'historiAmbil' => $historiAmbil,
            'historiSetor' => $historiSetor,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * EXPORT LEVEL 1: Rekap Sisa Jahit per Operator (CSV)
     */
    public function exportOperatorBalance(Request $request): StreamedResponse
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');

        // Ambil semua operator sewing
        $operators = Employee::where('role', 'sewing')
            ->orderBy('name')
            ->get();

        // --- Query ambil ---
        $ambilQuery = DB::table('sewing_picks as p')
            ->join('sewing_pick_lines as l', 'l.sewing_pick_id', '=', 'p.id');

        if ($dateFrom) {
            $ambilQuery->whereDate('p.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $ambilQuery->whereDate('p.date', '<=', $dateTo);
        }
        if ($operatorId) {
            $ambilQuery->where('p.operator_id', $operatorId);
        }

        $ambil = $ambilQuery
            ->select('p.operator_id', DB::raw('SUM(l.qty) as total_ambil'))
            ->groupBy('p.operator_id')
            ->pluck('total_ambil', 'p.operator_id');

        // --- Query setor (OK + Reject) ---
        $setorQuery = DB::table('sewing_returns as r')
            ->join('sewing_return_lines as l', 'l.sewing_return_id', '=', 'r.id');

        if ($dateFrom) {
            $setorQuery->whereDate('r.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $setorQuery->whereDate('r.date', '<=', $dateTo);
        }
        if ($operatorId) {
            $setorQuery->where('r.operator_id', $operatorId);
        }

        $setor = $setorQuery
            ->select(
                'r.operator_id',
                DB::raw('SUM(l.qty_ok) as total_ok'),
                DB::raw('SUM(l.qty_reject) as total_reject')
            )
            ->groupBy('r.operator_id')
            ->get()
            ->keyBy('operator_id');

        // Compose rows
        $rows = $operators->map(function ($op) use ($ambil, $setor) {
            $ambilQty = (float) ($ambil[$op->id] ?? 0);

            $set = $setor[$op->id] ?? null;
            $setorOk = $set ? (float) $set->total_ok : 0;
            $setorReject = $set ? (float) $set->total_reject : 0;

            $sisa = max($ambilQty - ($setorOk + $setorReject), 0);

            return [
                'code' => $op->code,
                'name' => $op->name,
                'ambil' => $ambilQty,
                'setor_ok' => $setorOk,
                'setor_reject' => $setorReject,
                'sisa' => $sisa,
            ];
        });

        $fileName = 'sewing-operator-balance-' . Carbon::now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $dateFrom, $dateTo) {
            $handle = fopen('php://output', 'w');

            // Header info
            fputcsv($handle, ['Report Sisa Jahit per Operator']);
            if ($dateFrom || $dateTo) {
                fputcsv($handle, ['Periode', $dateFrom ?: '-', 's/d', $dateTo ?: '-']);
            }
            fputcsv($handle, []); // blank line

            // Header kolom
            fputcsv($handle, [
                'Kode Operator',
                'Nama Operator',
                'Total Ambil',
                'Total Setor OK',
                'Total Reject',
                'Sisa Jahit',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['code'],
                    $row['name'],
                    $row['ambil'],
                    $row['setor_ok'],
                    $row['setor_reject'],
                    $row['sisa'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ]);
    }

    /**
     * EXPORT LEVEL 2: Detail per Operator (per item+lot) ke CSV
     */
    public function exportOperatorDetail(Request $request, Employee $operator): StreamedResponse
    {
        if ($operator->role !== 'sewing') {
            abort(404, 'Operator bukan penjahit (sewing).');
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Ambil per item (tanpa lot)
        $ambilPerItemQuery = DB::table('sewing_picks as p')
            ->join('sewing_pick_lines as l', 'l.sewing_pick_id', '=', 'p.id')
            ->leftJoin('items as i', 'i.id', '=', 'l.item_id')
            ->where('p.operator_id', $operator->id);

        if ($dateFrom) {
            $ambilPerItemQuery->whereDate('p.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $ambilPerItemQuery->whereDate('p.date', '<=', $dateTo);
        }

        $ambilPerItem = $ambilPerItemQuery
            ->select(
                'l.item_id',
                'l.item_code',
                'i.name as item_name',
                DB::raw('SUM(l.qty) as total_ambil')
            )
            ->groupBy('l.item_id', 'l.item_code', 'i.name')
            ->get();

        // Setor per item (tanpa lot)
        $setorPerItemQuery = DB::table('sewing_returns as r')
            ->join('sewing_return_lines as l', 'l.sewing_return_id', '=', 'r.id')
            ->where('r.operator_id', $operator->id);

        if ($dateFrom) {
            $setorPerItemQuery->whereDate('r.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $setorPerItemQuery->whereDate('r.date', '<=', $dateTo);
        }

        $setorPerItemRaw = $setorPerItemQuery
            ->select(
                'l.item_id',
                DB::raw('SUM(l.qty_ok) as total_ok'),
                DB::raw('SUM(l.qty_reject) as total_reject')
            )
            ->groupBy('l.item_id')
            ->get();

        $setorMap = [];
        foreach ($setorPerItemRaw as $row) {
            $setorMap[$row->item_id] = [
                'ok' => (float) $row->total_ok,
                'reject' => (float) $row->total_reject,
            ];
        }

        $rekapItem = $ambilPerItem->map(function ($row) use ($setorMap) {
            $itemId = $row->item_id;
            $totalAmbil = (float) $row->total_ambil;
            $totalOk = $setorMap[$itemId]['ok'] ?? 0;
            $totalReject = $setorMap[$itemId]['reject'] ?? 0;
            $sisa = max($totalAmbil - ($totalOk + $totalReject), 0);

            return [
                'item_code' => $row->item_code,
                'item_name' => $row->item_name,
                'total_ambil' => $totalAmbil,
                'total_ok' => $totalOk,
                'total_reject' => $totalReject,
                'sisa' => $sisa,
            ];
        })->sortByDesc('sisa')->values();

        $fileName = 'sewing-operator-detail-' . $operator->code . '-' . Carbon::now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rekapItem, $operator, $dateFrom, $dateTo) {
            $handle = fopen('php://output', 'w');

            // Header info
            fputcsv($handle, ['Detail Sisa Jahit per Item']);
            fputcsv($handle, ['Operator', $operator->code, $operator->name]);
            if ($dateFrom || $dateTo) {
                fputcsv($handle, ['Periode', $dateFrom ?: '-', 's/d', $dateTo ?: '-']);
            }
            fputcsv($handle, []); // blank line

            // Header kolom
            fputcsv($handle, [
                'Kode Item',
                'Nama Item',
                'Total Ambil',
                'Total Setor OK',
                'Total Reject',
                'Sisa Jahit',
            ]);

            foreach ($rekapItem as $row) {
                fputcsv($handle, [
                    $row['item_code'],
                    $row['item_name'],
                    $row['total_ambil'],
                    $row['total_ok'],
                    $row['total_reject'],
                    $row['sisa'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ]);
    }

}
