@extends('layouts.app')

@section('title', 'Payroll • ' . $run->code)

@push('head')
    <style>
        .payroll-page .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .payroll-page .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .small-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
        }

        .employee-card {
            border-radius: 12px;
            border: 1px solid var(--line);
            padding: .75rem .9rem;
            margin-bottom: .75rem;
        }

        .table-inner {
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="payroll-page container-fluid">
        <div class="mb-3 d-flex justify-content-between align-items-start">
            <div>
                <a href="{{ route('payroll.runs.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                    &larr; Kembali
                </a>
                <h1 class="h5 mb-0">Payroll per PCS: {{ $run->code }}</h1>
                <div class="text-muted small">
                    Periode:
                    <span class="mono">
                        {{ $run->start_date->format('d M Y') }}
                        &mdash;
                        {{ $run->end_date->format('d M Y') }}
                    </span>
                    • Proses: <strong>{{ strtoupper($run->process) }}</strong>
                </div>
                @if ($run->status === 'posted')
                    <div class="text-muted small">
                        Posted pada: {{ optional($run->posted_at)->format('d M Y H:i') }}
                    </div>
                @endif
            </div>

            <div class="text-end">
                <div class="small-label mb-1">Total Dibayar</div>
                <div class="h5 mono mb-1">
                    Rp {{ number_format($run->total_amount, 0, ',', '.') }}
                </div>
                <span class="badge bg-secondary-subtle mt-1 d-inline-block mb-2">
                    {{ strtoupper($run->status) }}
                </span>

                @if ($run->status === 'draft')
                    <form action="{{ route('payroll.runs.post', $run->id) }}" method="post"
                        onsubmit="return confirm('Post payroll ini? Setelah di-post tidak bisa diubah.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            POST Payroll &amp; Buat Jurnal
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger small">
                {{ session('error') }}
            </div>
        @endif

        @if ($run->notes)
            <div class="alert alert-info small">
                <strong>Catatan:</strong> {{ $run->notes }}
            </div>
        @endif

        {{-- LIST PER KARYAWAN --}}
        @forelse ($linesByEmployee as $employeeId => $lines)
            @php
                /** @var \App\Models\PayrollRunLine $first */
                $first = $lines->first();
                $emp = $first->employee;
                $empName = $emp?->name ?? 'Tidak diketahui';
                $empCode = $emp?->code ?? '-';
                $totalEmployee = $lines->sum('total_payable');
            @endphp

            <div class="employee-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-semibold">
                            {{ $empName }}
                            <span class="text-muted">({{ $empCode }})</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small-label mb-1">Total Dibayar</div>
                        <div class="mono fw-semibold">
                            Rp {{ number_format($totalEmployee, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-inner align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Proses</th>
                                <th>Item</th>
                                <th class="text-end">PCS</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Bonus</th>
                                <th class="text-end">Potongan</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Batch</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $line)
                                <tr>
                                    <td>{{ strtoupper($line->process) }}</td>
                                    <td>
                                        <div class="mono">{{ $line->item?->code ?? '-' }}</div>
                                        <div class="text-muted small">
                                            {{ $line->item?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($line->total_pcs, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        Rp {{ number_format($line->rate_per_piece, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        Rp {{ number_format($line->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-success">
                                        + Rp {{ number_format($line->bonus_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-danger">
                                        - Rp {{ number_format($line->deduction_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end mono fw-semibold">
                                        Rp {{ number_format($line->total_payable, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $line->batch_count }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="text-muted small">
                Belum ada data detail payroll untuk run ini.
            </div>
        @endforelse
    </div>
@endsection
