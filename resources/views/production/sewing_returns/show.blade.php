@extends('layouts.app')

@section('title', 'Produksi • Setor Jahit #' . $sewingReturn->code)

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

        .badge-lot {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .75rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .10);
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
                <h4 class="mb-1">
                    Setor Hasil Jahit
                    <span class="mono">#{{ $sewingReturn->code }}</span>
                </h4>
                <div class="small text-muted">
                    Tanggal:
                    {{ $sewingReturn->date?->format('d M Y') ?? '-' }}
                    •
                    Operator:
                    @if ($sewingReturn->operator)
                        {{ $sewingReturn->operator->code ?? 'OP-' . $sewingReturn->operator->id }}
                        — {{ $sewingReturn->operator->name }}
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
                <div class="mt-1">
                    @php
                        $status = $sewingReturn->status ?? 'draft';
                    @endphp
                    <span class="badge bg-{{ $status === 'posted' ? 'success' : 'secondary' }}">
                        {{ strtoupper($status) }}
                    </span>
                    @if ($sewingReturn->posted_at)
                        <span class="small text-muted ms-2">
                            Posted at: {{ $sewingReturn->posted_at->format('d M Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('production.sewing_returns.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>

                @if (($sewingReturn->status ?? 'draft') === 'draft')
                    <form method="post" action="{{ route('production.sewing_returns.post', $sewingReturn->id) }}"
                        onsubmit="return confirm('Posting setor jahit ini? Setelah posting, dokumen tidak bisa diubah.');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check2-circle me-1"></i> Posting
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger small">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- HEADER INFO CARD --}}
        <div class="card mb-3">
            <div class="card-body small">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Kode Dokumen</div>
                        <div class="fw-semibold mono">
                            {{ $sewingReturn->code }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Tanggal Setor</div>
                        <div class="fw-semibold mono">
                            {{ $sewingReturn->date?->format('d M Y') ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Status</div>
                        <div>
                            <span class="badge bg-{{ $status === 'posted' ? 'success' : 'secondary' }}">
                                {{ strtoupper($status) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Operator / Penjahit</div>
                        @if ($sewingReturn->operator)
                            <div class="fw-semibold">
                                {{ $sewingReturn->operator->code ?? 'OP-' . $sewingReturn->operator->id }}
                                — {{ $sewingReturn->operator->name }}
                            </div>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Dari Gudang (Operator)</div>
                        @if ($sewingReturn->fromWarehouse)
                            <div class="fw-semibold mono">
                                {{ $sewingReturn->fromWarehouse->code }}
                            </div>
                            <div class="text-muted">
                                {{ $sewingReturn->fromWarehouse->name }}
                            </div>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Ke Gudang (WIP / Finishing)</div>
                        @if ($sewingReturn->toWarehouse)
                            <div class="fw-semibold mono">
                                {{ $sewingReturn->toWarehouse->code }}
                            </div>
                            <div class="text-muted">
                                {{ $sewingReturn->toWarehouse->name }}
                            </div>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>

                    <div class="col-md-12">
                        <div class="text-muted small mb-1">Catatan Umum</div>
                        <div>
                            {{ $sewingReturn->notes ?: '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SUMMARY TOTALS --}}
        <div class="card mb-3">
            <div class="card-body small d-flex flex-wrap gap-3">
                <div>
                    <div class="text-muted small mb-1">Total OK</div>
                    <div class="fw-semibold mono">
                        {{ number_format($totals['ok'], 2, ',', '.') }}
                    </div>
                </div>
                <div>
                    <div class="text-muted small mb-1">Total Reject</div>
                    <div class="fw-semibold mono text-danger">
                        {{ number_format($totals['reject'], 2, ',', '.') }}
                    </div>
                </div>
                <div>
                    <div class="text-muted small mb-1">Total Semua (OK + Reject)</div>
                    <div class="fw-semibold mono">
                        {{ number_format($totals['all'], 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL TABLE --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold small text-uppercase">
                    Detail Hasil Jahit
                </span>
                <small class="text-muted">
                    Sumber dari dokumen Ambil Jahit yang sudah disetor.
                </small>
            </div>

            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Kode Ambil Jahit</th>
                                <th>Tgl Ambil</th>
                                <th>Item / LOT</th>
                                <th class="text-end">Qty OK</th>
                                <th class="text-end">Qty Reject</th>
                                <th>Unit</th>
                                <th>Catatan Baris</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sewingReturn->lines as $idx => $line)
                                @php
                                    $pick = $line->sewingPickLine->sewingPick ?? null;
                                @endphp
                                <tr>
                                    <td class="mono">
                                        {{ $idx + 1 }}
                                    </td>

                                    {{-- Kode Ambil Jahit --}}
                                    <td class="mono">
                                        @if ($pick)
                                            {{ $pick->code }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Tanggal Ambil --}}
                                    <td class="mono">
                                        @if ($pick && $pick->date)
                                            {{ \Carbon\Carbon::parse($pick->date)->format('d M Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Item + LOT --}}
                                    <td>
                                        <div class="fw-semibold mono">{{ $line->item_code }}</div>
                                        <div class="small text-muted">
                                            LOT:
                                            <span class="badge-lot mono">
                                                #{{ $line->lot_id }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Qty OK --}}
                                    <td class="text-end mono">
                                        {{ number_format($line->qty_ok, 2, ',', '.') }}
                                    </td>

                                    {{-- Qty Reject --}}
                                    <td class="text-end mono text-danger">
                                        {{ number_format($line->qty_reject, 2, ',', '.') }}
                                    </td>

                                    {{-- Unit --}}
                                    <td class="mono">
                                        {{ $line->unit }}
                                    </td>

                                    {{-- Notes --}}
                                    <td>
                                        {{ $line->notes ?: '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Tidak ada detail baris pada dokumen ini.
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
