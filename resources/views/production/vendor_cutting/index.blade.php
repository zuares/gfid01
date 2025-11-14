@extends('layouts.app')
@section('title', 'Produksi • Vendor Cutting')

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

        .badge-status {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .75rem;
            border: 1px solid var(--line);
        }

        .badge-sent {
            background: rgba(59, 130, 246, .12);
            color: #93c5fd;
        }

        .badge-received {
            background: rgba(34, 197, 94, .12);
            color: #6ee7b7;
        }

        .chip-process {
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .72rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .10);
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

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap vendor-cutting-page">
        <h4 class="mb-1">Vendor Cutting</h4>
        <div class="text-muted small mb-3">
            Daftar kiriman kain (external transfer) untuk diproses cutting oleh vendor / operator.
        </div>

        <div class="card">
            <div class="card-body table-wrap p-0">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3">Dokumen</th>
                            <th>Tanggal</th>
                            <th>Dari → Ke</th>
                            <th>LOT</th>
                            <th>Status</th>
                            <th class="pe-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="ps-3">
                                    <div class="mono">{{ $row->code }}</div>
                                    <div class="small text-muted">
                                        {{ $row->operator_code ?: '—' }}
                                    </div>
                                </td>
                                <td class="mono">
                                    {{ $row->date?->format('d M Y') }}
                                </td>
                                <td>
                                    <div class="small">
                                        {{ $row->fromWarehouse?->code ?? '—' }} → {{ $row->toWarehouse?->code ?? '—' }}
                                    </div>
                                    <div class="small text-muted">
                                        {{ $row->toWarehouse?->name ?? '' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-secondary-subtle">
                                        {{ $row->lines_count }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = $row->status === 'sent' ? 'badge-sent' : 'badge-received';
                                    @endphp
                                    <span class="badge-status {{ $badgeClass }} mono text-uppercase">
                                        {{ $row->status }}
                                    </span>
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('vendor-cutting.create', $row) }}" class="btn btn-sm btn-primary">
                                        Proses Cutting
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 small text-muted">
                                    Belum ada dokumen cutting yang siap diproses.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rows->hasPages())
                <div class="card-footer py-2">
                    <div class="px-3">
                        {{ $rows->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
