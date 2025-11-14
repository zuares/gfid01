@extends('layouts.app')

@section('title', 'Produksi • External Transfer')

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

        .badge-draft {
            background: rgba(148, 163, 184, .12);
            color: #e5e7eb;
        }

        .badge-sent {
            background: rgba(59, 130, 246, .12);
            color: #93c5fd;
        }

        .badge-received {
            background: rgba(34, 197, 94, .12);
            color: #6ee7b7;
        }

        .badge-done {
            background: rgba(16, 185, 129, .12);
            color: #6ee7b7;
        }

        .badge-cancelled {
            background: rgba(248, 113, 113, .12);
            color: #fecaca;
        }

        .chip-process {
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .72rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .10);
        }

        .table thead th {
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
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">External Transfer</h4>
                <div class="text-muted small">
                    Dokumen perpindahan barang (LOT) antar gudang / vendor.
                </div>
            </div>
            <div>
                <a href="{{ route('external-transfers.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Dokumen Baru
                </a>
            </div>
        </div>

        {{-- Filter --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Proses</label>
                        <select name="process" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="cutting" {{ ($process ?? '') === 'cutting' ? 'selected' : '' }}>Cutting</option>
                            <option value="sewing" {{ ($process ?? '') === 'sewing' ? 'selected' : '' }}>Sewing</option>
                            <option value="finishing" {{ ($process ?? '') === 'finishing' ? 'selected' : '' }}>Finishing
                            </option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach (['draft', 'sent', 'received', 'done', 'cancelled'] as $st)
                                <option value="{{ $st }}" {{ ($status ?? '') === $st ? 'selected' : '' }}>
                                    {{ ucfirst($st) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 text-md-end">
                        <button type="submit" class="btn btn-outline-secondary btn-sm me-1">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>
                        <a href="{{ route('external-transfers.index') }}" class="btn btn-outline-dark btn-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-body table-wrap p-0">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3">Dokumen</th>
                            <th>Tanggal</th>
                            <th>Dari → Ke</th>
                            <th>Proses</th>
                            <th class="text-center">LOT</th>
                            <th>Status</th>
                            <th class="pe-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('external-transfers.show', $row) }}"
                                        class="mono text-decoration-none">
                                        {{ $row->code }}
                                    </a>
                                    <div class="small text-muted">
                                        {{ $row->operator_code ?: '—' }}
                                    </div>
                                </td>
                                <td class="mono">
                                    {{ $row->date?->format('d M Y') }}
                                </td>
                                <td>
                                    <div class="small">
                                        {{ $row->fromWarehouse?->code ?? '—' }}
                                        <span class="mx-1">→</span>
                                        {{ $row->toWarehouse?->code ?? '—' }}
                                    </div>
                                    <div class="small text-muted">
                                        {{ $row->fromWarehouse?->name ?? '' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="chip-process mono text-uppercase">
                                        {{ $row->process }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-secondary-subtle">
                                        {{ $row->lines_count }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($row->status) {
                                            'draft' => 'badge-draft',
                                            'sent' => 'badge-sent',
                                            'received' => 'badge-received',
                                            'done' => 'badge-done',
                                            'cancelled' => 'badge-cancelled',
                                            default => 'badge-draft',
                                        };
                                    @endphp
                                    <span class="badge-status {{ $badgeClass }} mono text-uppercase">
                                        {{ $row->status }}
                                    </span>
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('external-transfers.show', $row) }}"
                                            class="btn btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if ($row->status === 'draft')
                                            <a href="{{ route('external-transfers.edit', $row) }}"
                                                class="btn btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('external-transfers.send', $row) }}" method="post"
                                                onsubmit="return confirm('Kirim dokumen ini?')">
                                                @csrf
                                                <button class="btn btn-outline-primary" type="submit">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                            </form>
                                        @elseif($row->status === 'sent')
                                            <form action="{{ route('external-transfers.receive', $row) }}" method="post"
                                                onsubmit="return confirm('Konfirmasi barang sudah diterima?')">
                                                @csrf
                                                <button class="btn btn-outline-success" type="submit">
                                                    <i class="bi bi-box-arrow-in-down"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted small">Belum ada dokumen external transfer.</div>
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
