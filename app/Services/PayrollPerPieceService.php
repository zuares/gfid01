<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeePieceRate;
use App\Models\Item;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use App\Models\ProductionBatch;
use Illuminate\Support\Facades\DB;

class PayrollPerPieceService
{
    /**
     * Membuat satu payroll run per pcs dari data production_batches.
     *
     * @param  string  $startDate  format Y-m-d
     * @param  string  $endDate    format Y-m-d
     * @param  string  $process    'cutting' | 'sewing' | 'finishing' | 'all'
     * @param  string|null $notes
     * @return PayrollRun
     */
    public function generateRun(string $startDate, string $endDate, string $process = 'sewing', ?string $notes = null): PayrollRun
    {
        return DB::transaction(function () use ($startDate, $endDate, $process, $notes) {

            // 1) Generate kode payroll run
            $prefix = 'PR';
            $now = now();
            $countToday = PayrollRun::whereDate('created_at', $now->toDateString())->count();
            $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . $now->format('ymd') . '-' . $seq;

            // 2) Buat header terlebih dahulu
            $run = PayrollRun::create([
                'code' => $code,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'process' => $process,
                'status' => 'draft',
                'total_amount' => 0,
                'notes' => $notes,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // 3) Ambil semua batch produksi di rentang tanggal & proses
            $batchQuery = ProductionBatch::query()
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotNull('operator_code');

            if ($process !== 'all') {
                $batchQuery->where('process', $process);
            }

            $batches = $batchQuery->get();

            if ($batches->isEmpty()) {
                return $run; // tidak ada data, payroll kosong
            }

            // Ambil semua employee berdasar operator_code
            $operatorCodes = $batches->pluck('operator_code')->filter()->unique()->values()->all();

            $employees = Employee::whereIn('code', $operatorCodes)
                ->get()
                ->keyBy('code'); // operator_code -> Employee

            // 4) Flatten data ke bentuk: (employee_id, process, item_code, total_pcs, details[])
            $agg = []; // key: employee_id|process|item_code

            foreach ($batches as $batch) {
                $employee = $employees[$batch->operator_code] ?? null;
                if (!$employee) {
                    continue; // operator_code tidak terdaftar, skip
                }

                $items = $batch->output_items_json ?? [];
                if (!is_array($items)) {
                    continue;
                }

                foreach ($items as $itemCode => $qty) {
                    $qty = (int) $qty;
                    if ($qty <= 0) {
                        continue;
                    }

                    $key = $employee->id . '|' . $batch->process . '|' . $itemCode;

                    if (!isset($agg[$key])) {
                        $agg[$key] = [
                            'employee_id' => $employee->id,
                            'process' => $batch->process,
                            'item_code' => $itemCode,
                            'total_pcs' => 0,
                            'batches' => [],
                        ];
                    }

                    $agg[$key]['total_pcs'] += $qty;
                    $agg[$key]['batches'][] = [
                        'batch_code' => $batch->code,
                        'date' => $batch->date?->format('Y-m-d'),
                        'pcs' => $qty,
                    ];
                }
            }

            if (empty($agg)) {
                return $run;
            }

            // 5) Ambil rate per pcs
            $employeeIds = collect($agg)->pluck('employee_id')->unique()->values()->all();

            $rates = EmployeePieceRate::active()
                ->whereIn('employee_id', $employeeIds)
                ->when($process !== 'all', fn($q) => $q->where('process', $process))
                ->get();

            // map rate: employee_id|process|item_id / null
            $rateMap = [];
            foreach ($rates as $rate) {
                $key = $rate->employee_id . '|' . $rate->process . '|' . ($rate->item_id ?? 'null');
                $rateMap[$key] = $rate->rate_per_piece;
            }

            // map item_code -> item_id
            $itemCodes = collect($agg)->pluck('item_code')->unique()->values()->all();
            $itemMap = Item::whereIn('code', $itemCodes)
                ->pluck('id', 'code'); // code -> id

            // 6) Ambil total kasbon (unpaid) per employee
            $loanByEmployee = EmployeeLoan::whereIn('employee_id', $employeeIds)
                ->where('status', 'unpaid')
                ->selectRaw('employee_id, SUM(amount) as total_loan')
                ->groupBy('employee_id')
                ->pluck('total_loan', 'employee_id'); // employee_id -> total_loan

            // 7) Insert PayrollRunLine + apply bonus & kasbon
            $totalRunAmount = 0;

            foreach ($agg as $row) {
                $employeeId = $row['employee_id'];
                $proc = $row['process'];
                $itemCode = $row['item_code'];
                $totalPcs = (int) $row['total_pcs'];
                $batchList = $row['batches'];

                $itemId = $itemMap[$itemCode] ?? null;

                // Cari rate
                $rateKeySpecific = $employeeId . '|' . $proc . '|' . ($itemId ?? 'null');
                $rateKeyGeneral = $employeeId . '|' . $proc . '|null';

                $rate = 0;
                if (isset($rateMap[$rateKeySpecific])) {
                    $rate = $rateMap[$rateKeySpecific];
                } elseif (isset($rateMap[$rateKeyGeneral])) {
                    $rate = $rateMap[$rateKeyGeneral];
                }

                // Gaji kotor (sebelum bonus/potongan)
                $amount = $totalPcs * $rate;

                // ==== LOGIC BONUS SEDERHANA ====
                // Contoh rule: kalau totalPcs > 1000, bonus 25.000
                $bonus = 0;
                if ($totalPcs > 1000) {
                    $bonus = 25000;
                }

                // ==== LOGIC KASBON (POTONGAN) ====
                $totalLoanUnpaid = (float) ($loanByEmployee[$employeeId] ?? 0);
                // potongan maksimal = gaji + bonus (jangan minus)
                $maxForDeduction = $amount + $bonus;
                $deduction = min($totalLoanUnpaid, $maxForDeduction);

                // ==== TOTAL YANG HARUS DIBAYAR KE KARYAWAN ====
                $totalPayable = $amount + $bonus - $deduction;

                // Akumulasi ke header
                $totalRunAmount += $totalPayable;

                // Simpan line
                PayrollRunLine::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employeeId,
                    'process' => $proc,
                    'item_id' => $itemId,
                    'total_pcs' => $totalPcs,
                    'rate_per_piece' => $rate,
                    'amount' => $amount,
                    'bonus_amount' => $bonus,
                    'deduction_amount' => $deduction,
                    'total_payable' => $totalPayable,
                    'batch_count' => count($batchList),
                    'details_json' => $batchList,
                ]);
            }

            // 8) Update total_amount di header pakai totalPayable semua karyawan
            $run->total_amount = $totalRunAmount;
            $run->save();

            // Catatan:
            // - kalau mau sekalian mark kasbon "paid", sebaiknya dilakukan
            //   di step "POST payroll", supaya tidak langsung lunas kalau run masih draft.

            return $run;
        });
    }
}
