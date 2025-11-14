<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Services\JournalService;
use App\Services\PayrollPerPieceService;
use Illuminate\Http\Request;

class PayrollPerPieceController extends Controller
{
    /**
     * Halaman index:
     * - form generate payroll
     * - list payroll_runs terbaru
     */
    public function index()
    {
        $runs = PayrollRun::orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('payroll.runs.index', compact('runs'));
    }

    /**
     * Proses generate payroll_run baru.
     */
    public function store(Request $request, PayrollPerPieceService $service)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'process' => ['required', 'in:cutting,sewing,finishing,all'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // panggil service
        $run = $service->generateRun(
            $data['start_date'],
            $data['end_date'],
            $data['process'],
            $data['notes'] ?? null
        );

        return redirect()
            ->route('payroll.runs.show', $run->id)
            ->with('success', 'Payroll per pcs berhasil dibuat: ' . $run->code);
    }

    /**
     * Detail 1 payroll_run: rekap per karyawan.
     */
    public function show(PayrollRun $payrollRun)
    {
        $payrollRun->load(['lines.employee', 'lines.item']);

        // group by employee untuk tampilan rapih
        $linesByEmployee = $payrollRun->lines
            ->groupBy('employee_id');

        return view('payroll.runs.show', [
            'run' => $payrollRun,
            'linesByEmployee' => $linesByEmployee,
        ]);
    }

    public function post(PayrollRun $payrollRun, JournalService $journals)
    {
        if ($payrollRun->status !== 'draft') {
            return back()->with('error', 'Payroll ini sudah tidak bisa di-post (status bukan draft).');
        }

        if ($payrollRun->lines()->count() === 0) {
            return back()->with('error', 'Payroll ini tidak punya detail, tidak bisa di-post.');
        }

        // Pakai tanggal akhir periode sebagai tanggal jurnal
        $date = $payrollRun->end_date ?? now()->toDateString();

        // Nilai total payroll = total_payable semua karyawan
        $total = $payrollRun->lines()->sum('total_payable');

        if ($total <= 0) {
            return back()->with('error', 'Total payroll = 0, tidak bisa di-post.');
        }

        // 🔹 Bikin jurnal akuntansi
        // NOTE: SESUAIKAN dengan interface JournalService kamu.
        // Contoh generic:
        $journals->createJournal(
            date: $date,
            source: 'PAYROLL',
            ref: $payrollRun->code,
            lines: [
                // Dr Beban Gaji (5101)
                [
                    'account_code' => '5101',
                    'debit' => $total,
                    'credit' => 0,
                    'description' => 'Beban gaji per pcs ' . $payrollRun->code,
                ],
                // Cr Hutang Gaji (2105)
                [
                    'account_code' => '2105',
                    'debit' => 0,
                    'credit' => $total,
                    'description' => 'Hutang gaji per pcs ' . $payrollRun->code,
                ],
            ],
            notes: 'Payroll per pcs ' . $payrollRun->code
        );

        // 🔹 Update status payroll
        $payrollRun->status = 'posted';
        $payrollRun->posted_at = now();
        $payrollRun->posted_by = auth()->id();
        $payrollRun->total_amount = $total; // pastikan sinkron dengan total_payable
        $payrollRun->save();

        return redirect()
            ->route('payroll.runs.show', $payrollRun->id)
            ->with('success', 'Payroll berhasil di-post & jurnal dibuat.');
    }
}
