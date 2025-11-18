@extends('layouts.app')
@section('title', 'Produksi • Ambil Jahit')

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

        .badge-status {
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .75rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                overflow-x: auto;
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
                    Ambil Jahit
                </h4>
                <div class="small text-muted">
                    Daftar dokumen ambil jahit dari WIP-SEW ke gudang operator per hari / per operator.
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('production.sewing_picks.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Buat Ambil Jahit
                </a>
            </div>
        </div>

        {{-- FLASH MESSAGE --}}
        @if (session('success'))
            <div class="alert alert-success py-2 small">
                {{ session('success') }}
            </div>
        @endif

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body small">
                <form method="get">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">Operator Jahit</label>
                            <select name="operator_id" class="form-select form-select-sm">
                                <option value="">- Semua Operator -</option>
                                @foreach ($operators as $op)
                                    <option value="{{ $op->id }}" @selected($operatorId == $op->id)>
                                        {{ $op->code ?? 'OP-' . $op->id }} — {{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="{{ $dateFrom }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="{{ $dateTo }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-filter me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Tanggal</th>
                                <th>Kode</th>
                                <th>Operator</th>
                                <th>Dari Gudang</th>
                                <th>Ke Gudang</th>
                                <th class="text-end">Total Qty</th>
                                <th class="text-end">Status</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($picks as $i => $pick)
                                @php
                                    $rowNumber = $picks->firstItem() + $i;
                                    $totalQty = $pick->lines->sum('qty');
                                    $status = strtoupper($pick->status);
                                    $badgeClass = 'secondary';
                                    if ($pick->status === 'posted') {
                                        $badgeClass = 'success';
                                    } elseif ($pick->status === 'draft') {
                                        $badgeClass = 'warning';
                                    }
                                @endphp
                                <tr>
                                    <td class="mono">
                                        {{ $rowNumber }}
                                    </td>
                                    <td class="mono">
                                        {{ \Illuminate\Support\Carbon::parse($pick->date)->format('d M Y') }}
                                    </td>
                                    <td class="mono">
                                        {{ $pick->code }}
                                    </td>
                                    <td>
                                        @if ($pick->operator)
                                            <div class="mono">
                                                {{ $pick->operator->code ?? 'OP-' . $pick->operator->id }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $pick->operator->name }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($pick->fromWarehouse)
                                            <div class="mono">{{ $pick->fromWarehouse->code }}</div>
                                            <div class="small text-muted">
                                                {{ $pick->fromWarehouse->name }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($pick->toWarehouse)
                                            <div class="mono">{{ $pick->toWarehouse->code }}</div>
                                            <div class="small text-muted">
                                                {{ $pick->toWarehouse->name }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($totalQty, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $badgeClass }} badge-status">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('production.sewing_picks.show', $pick) }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Belum ada dokumen ambil jahit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($picks instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-between align-items-center small">
                        <div>
                            Menampilkan
                            <span class="mono">
                                {{ $picks->firstItem() }}–{{ $picks->lastItem() }}
                            </span>
                            dari
                            <span class="mono">{{ $picks->total() }}</span>
                            dokumen.
                        </div>
                        <div>
                            {{ $picks->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
