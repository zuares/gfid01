@extends('layouts.app')

@section('title', 'Finished Goods • ' . $fg->item_code)

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
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
            font-variant-numeric: tabular-nums;
        }

        .badge-lot {
            background: #1d4ed8;
            color: #fff;
            font-size: .75rem;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .badge-wh {
            background: #475569;
            color: #fff;
            font-size: .75rem;
            padding: 4px 8px;
            border-radius: 6px;
        }

        thead th {
            background: var(--panel);
            position: sticky;
            top: 0;
            z-index: 2;
        }
    </style>
@endpush


@section('content')
    <div class="page-wrap">

        {{-- BACK BUTTON --}}
        <div class="mb-3">
            <a href="{{ route('finishing.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali
            </a>
        </div>

        {{-- HEADER --}}
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h5 mb-1">Finished Goods Detail</h1>
                    <div class="mono text-muted">
                        LOT: {{ $fg->lot->code ?? '-' }}
                    </div>
                </div>

                <div class="d-flex gap-2">
                    {{-- Future actions --}}
                    <a href="#" class="btn btn-sm btn-outline-primary">Mutasi / Transfer</a>
                    {{-- <a href="#" class="btn btn-sm btn-outline-danger">Mutasi OUT</a> --}}
                </div>
            </div>
        </div>

        {{-- FG INFO CARD --}}
        <div class="card mb-4">
            <div class="card-body">

                <h2 class="h6 mb-3">Informasi Barang Jadi</h2>

                <div class="row g-3">

                    <div class="col-md-6">
                        <div class="text-muted small">Item</div>
                        <div class="fw-semibold mono">
                            {{ $fg->item_code }}
                        </div>
                        <div class="text-muted small">
                            {{ $fg->item?->name ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Gudang</div>
                        <div>
                            <span class="badge-wh">{{ $fg->warehouse?->code }}</span>
                        </div>
                        <div class="text-muted small">
                            {{ $fg->warehouse?->name }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small">Jumlah FG</div>
                        <div class="fw-semibold mono fs-5">
                            {{ number_format($fg->qty, 2) }} {{ $fg->unit }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small">Tanggal LOT FG</div>
                        <div class="mono">
                            {{ $fg->lot?->date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small">Batch Finishing</div>
                        <div class="mono fw-semibold">
                            {{ $fg->productionBatch?->code ?? '—' }}
                        </div>
                        @if ($fg->productionBatch)
                            <div class="text-muted small">
                                {{ $fg->productionBatch->date?->format('d M Y') }}
                            </div>
                        @endif
                    </div>

                    <div class="col-md-12">
                        <div class="text-muted small">Catatan</div>
                        <div>
                            {{ $fg->notes ?: '—' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- SOURCE LOT --}}
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Traceability</h2>

                <div class="row g-3">

                    <div class="col-md-6">
                        <div class="text-muted small">LOT Kain Asal</div>
                        <div class="mono fw-semibold">
                            {{ $fg->sourceLot?->code ?? '—' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Proses Cutting/Sewing Asal</div>
                        @if ($fg->productionBatch)
                            <div class="mono fw-semibold">{{ $fg->productionBatch->code }}</div>
                            <div class="text-muted small">
                                {{ $fg->productionBatch->process }} •
                                {{ $fg->productionBatch->date?->format('d M Y') }}
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        {{-- MUTASI LOT SECTION --}}
        <div class="card mb-5">
            <div class="card-body">

                <h2 class="h6 mb-3">Mutasi LOT (Ledger)</h2>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Ref</th>
                                <th class="text-end">IN</th>
                                <th class="text-end">OUT</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($mutations as $m)
                                <tr>
                                    <td class="mono">{{ $m->date }}</td>
                                    <td class="mono">{{ $m->ref_code }}</td>
                                    <td class="text-end mono {{ $m->qty_in > 0 ? 'text-success' : '' }}">
                                        {{ $m->qty_in > 0 ? number_format($m->qty_in, 2) : '-' }}
                                    </td>
                                    <td class="text-end mono {{ $m->qty_out > 0 ? 'text-danger' : '' }}">
                                        {{ $m->qty_out > 0 ? number_format($m->qty_out, 2) : '-' }}
                                    </td>
                                    <td>{{ $m->note }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada mutasi.
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
