@extends('layouts.app')
@section('title', 'Produksi • Dashboard')

@push('head')
    <style>
        .page-wrap {
            max-width: 1160px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        thead th {
            background: var(--panel);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table-sm td,
        .table-sm th {
            padding-block: .35rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .stat-label {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 600;
        }

        .badge-pill {
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .75rem;
            border: 1px solid var(--line);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">Dashboard Produksi</h1>
                <div class="help">
                    Ringkasan ambil jahit, setor, sisa per operator & finishing dalam periode tertentu.
                </div>
            </div>
            <div>
                {{-- Link ke report detail (sesuaikan nama route) --}}
                <a href="{{ route('production.sewing_report.operator_balance') }}" class="btn btn-sm btn-outline-secondary">
                    Laporan Sisa Jahit
                </a>
            </div>
        </div>

        {{-- FILTER PERIODE --}}
        <div class="card mb-3 p-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Dari tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Sampai tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-primary btn-sm w-100 mt-2 mt-md-0">
                        Terapkan
                    </button>
                </div>
            </form>
            <div class="help mt-2">
                Periode: <span class="mono">{{ $dateFrom }}</span> s/d <span class="mono">{{ $dateTo }}</span>
            </div>
        </div>

        {{-- RINGKASAN UTAMA --}}
        <div class="row g-3 mb-3">
            {{-- Sewing summary --}}
            <div class="col-md-6">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">Ringkasan Jahit (Sewing)</div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6 mb-2">
                            <div class="stat-label">Total Ambil</div>
                            <div class="stat-value mono">
                                {{ number_format($sewingSummary['total_ambil'], 0) }}
                            </div>
                        </div>
                        <div class="col-6 mb-2">
                            <div class="stat-label">Total Setor OK</div>
                            <div class="stat-value mono">
                                {{ number_format($sewingSummary['total_setor_ok'], 0) }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-label">Total Reject</div>
                            <div class="stat-value mono text-danger">
                                {{ number_format($sewingSummary['total_setor_reject'], 0) }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-label">Sisa Jahit (global)</div>
                            <div class="stat-value mono text-warning">
                                {{ number_format($sewingSummary['total_sisa'], 0) }}
                            </div>
                        </div>
                    </div>
                    <div class="help mt-2">
                        Sisa jahit global = Total ambil - (Setor OK + Reject) dalam periode.
                    </div>
                </div>
            </div>

            {{-- Finishing summary --}}
            <div class="col-md-6">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">Ringkasan Finishing</div>
                        <div class="badge-pill">
                            Draft
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-4 mb-2">
                            <div class="stat-label">Masuk Finishing</div>
                            <div class="stat-value mono">
                                {{ number_format($finishingSummary['total_masuk'], 0) }}
                            </div>
                        </div>
                        <div class="col-4 mb-2">
                            <div class="stat-label">OK Finishing</div>
                            <div class="stat-value mono">
                                {{ number_format($finishingSummary['total_ok'], 0) }}
                            </div>
                        </div>
                        <div class="col-4 mb-2">
                            <div class="stat-label">Reject Finishing</div>
                            <div class="stat-value mono text-danger">
                                {{ number_format($finishingSummary['total_reject'], 0) }}
                            </div>
                        </div>
                    </div>
                    <div class="help mt-2">
                        Data diambil dari <span class="mono">finishing_jobs</span> &amp; <span
                            class="mono">finishing_job_lines</span>.
                    </div>
                </div>
            </div>
        </div>

        {{-- TOP OPERATOR SISA JAHIT --}}
        <div class="card mb-3 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">Top 5 Operator dengan Sisa Jahit Terbesar</div>
                <a href="{{ route('production.sewing_report.operator_balance') }}" class="btn btn-sm btn-outline-primary">
                    Lihat semua operator
                </a>
            </div>
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th class="text-end">Total Ambil</th>
                            <th class="text-end">Setor OK</th>
                            <th class="text-end">Reject</th>
                            <th class="text-end">Sisa</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topOperators as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $row['operator']->code }} — {{ $row['operator']->name }}
                                    </div>
                                </td>
                                <td class="text-end mono">{{ number_format($row['ambil'], 0) }}</td>
                                <td class="text-end mono">{{ number_format($row['ok'], 0) }}</td>
                                <td class="text-end mono text-danger">{{ number_format($row['reject'], 0) }}</td>
                                <td class="text-end mono text-warning">{{ number_format($row['sisa'], 0) }}</td>
                                <td class="text-end">
                                    {{-- Sesuaikan route operator detail --}}
                                    <a href="{{ route('production.sewing_report.operator_detail', $row['operator']) }}"
                                        class="btn btn-xs btn-outline-secondary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    Belum ada data sewing pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- AKTIVITAS HARIAN --}}
        <div class="card mb-3 p-3">
            <div class="fw-semibold mb-2">Aktivitas Harian (Ambil vs Setor)</div>
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-end">Ambil</th>
                            <th class="text-end">Setor OK</th>
                            <th class="text-end">Reject</th>
                            <th class="text-end">Sisa (hari itu)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dailyStats as $d)
                            <tr>
                                <td class="mono">{{ $d['date'] }}</td>
                                <td class="text-end mono">{{ number_format($d['ambil'], 0) }}</td>
                                <td class="text-end mono">{{ number_format($d['ok'], 0) }}</td>
                                <td class="text-end mono text-danger">{{ number_format($d['reject'], 0) }}</td>
                                <td class="text-end mono text-warning">{{ number_format($d['sisa'], 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    Tidak ada aktivitas pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="help mt-2">
                Sisa per hari = Ambil - (Setor OK + Reject) pada tanggal yang sama (tidak akumulatif).
            </div>
        </div>
    </div>
@endsection
