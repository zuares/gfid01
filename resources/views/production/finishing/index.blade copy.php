@extends('layouts.app')
@section('title', 'Produksi • Finishing Detail')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
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

        .table-wrap {
            overflow-x: auto;
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

        .badge-status {
            font-size: .75rem;
            border-radius: 999px;
            padding: .15rem .6rem;
        }

        .badge-status-posted {
            background: rgba(34, 197, 94, .1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, .3);
        }

        .stat-card {
            border-radius: 14px;
            border: 1px solid var(--line);
            padding: .75rem 1rem;
            background: var(--card);
        }

        .stat-label {
            font-size: .75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0">
                        Detail Finishing / Packing
                    </h4>
                    <span class="badge-status badge-status-posted">
                        <i class="bi bi-check2-circle me-1"></i> {{ strtoupper($job->status) }}
                    </span>
                </div>
                <div class="small text-muted">
                    Dokumen: <span class="mono">{{ $job->code }}</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('production.finishing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        {{-- SUMMARY STAT --}}
        <div class="row g-2 mb-3">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-label">TANGGAL</div>
                    <div class="stat-value mono">
                        {{ \Illuminate\Support\Carbon::parse($job->date)->format('d M Y') }}
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-label">TOTAL OK</div>
                    <div class="stat-value mono">
                        {{ number_format($totals['ok'], 2, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-label">TOTAL REJECT</div>
                    <div class="stat-value mono">
                        {{ number_format($totals['reject'], 2, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-label">TOTAL DIPROSES</div>
                    <div class="stat-value mono">
                        {{ number_format($totals['all'], 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- INFO HEADER --}}
        <div class="card mb-3">
            <div class="card-body small">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted mb-1">Petugas Finishing</div>
                        @if ($job->employee)
                            <div class="fw-semibold">
                                {{ $job->employee->name }}
                            </div>
                            <div class="mono small">
                                {{ $job->employee->code ?? 'EMP-' . $job->employee->id }}
                            </div>
                        @else
                            <div class="text-muted">Tidak diisi</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted mb-1">Gudang</div>
                        <div class="mono">
                            {{ $job->fromWarehouse?->code }} → {{ $job->toWarehouse?->code }}
                        </div>
                        <div class="small text-muted">
                            {{ $job->fromWarehouse?->name }} → {{ $job->toWarehouse?->name }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted mb-1">Tanggal Posting</div>
                        <div class="mono">
                            {{ $job->posted_at ? $job->posted_at->format('d M Y H:i') : '-' }}
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="text-muted mb-1">Catatan</div>
                        <div>
                            @if ($job->notes)
                                {{ $job->notes }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL TABLE --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold small text-uppercase">
                    Detail Baris Finishing
                </span>
                <small class="text-muted">
                    Menampilkan item WIP dan hasil FG per baris.
                </small>
            </div>

            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Item WIP</th>
                                <th>Item FG</th>
                                <th class="text-end">Qty WIP</th>
                                <th class="text-end">Qty OK</th>
                                <th class="text-end">Qty Reject</th>
                                <th>Unit</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($job->lines as $idx => $line)
                                @php
                                    $itemName = $line->item?->name ?? '-';
                                    $fgName = $line->fgItem?->name ?? '-';
                                @endphp
                                <tr>
                                    <td class="mono">
                                        {{ $idx + 1 }}
                                    </td>

                                    {{-- ITEM WIP --}}
                                    <td>
                                        <div class="fw-semibold mono">
                                            {{ $line->item_code }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $itemName }}
                                        </div>
                                    </td>

                                    {{-- ITEM FG --}}
                                    <td>
                                        <div class="fw-semibold mono">
                                            {{ $line->fg_item_code ?? $line->item_code }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $fgName }}
                                        </div>
                                    </td>

                                    {{-- QTY WIP --}}
                                    <td class="text-end mono">
                                        {{ number_format($line->qty_wip, 2, ',', '.') }}
                                    </td>

                                    {{-- QTY OK --}}
                                    <td class="text-end mono">
                                        {{ number_format($line->qty_ok, 2, ',', '.') }}
                                    </td>

                                    {{-- QTY REJECT --}}
                                    <td class="text-end mono">
                                        {{ number_format($line->qty_reject, 2, ',', '.') }}
                                    </td>

                                    {{-- UNIT --}}
                                    <td class="mono">
                                        {{ $line->unit }}
                                    </td>

                                    {{-- CATATAN --}}
                                    <td>
                                        @if ($line->notes)
                                            {{ $line->notes }}
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Tidak ada detail baris finishing.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
