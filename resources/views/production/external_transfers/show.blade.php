@extends('layouts.app')

@section('title', 'Produksi • External Transfer • ' . $row->code)

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

        .badge-stock {
            border-radius: 999px;
            padding: .05rem .5rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
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
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="mb-1 mono">{{ $row->code }}</h4>
                <div class="small text-muted">
                    External Transfer • {{ ucfirst($row->process) }}
                </div>
            </div>
            <div class="text-end">
                <a href="{{ route('external-transfers.index') }}" class="btn btn-outline-secondary btn-sm mb-1">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>

                {{-- Aksi tergantung status --}}
                @if ($row->status === 'draft')
                    <a href="{{ route('external-transfers.edit', $row) }}" class="btn btn-outline-secondary btn-sm mb-1">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>

                    <form action="{{ route('external-transfers.send', $row) }}" method="post" class="d-inline"
                        onsubmit="return confirm('Kirim dokumen ini?')">
                        @csrf
                        <button class="btn btn-primary btn-sm mb-1" type="submit">
                            <i class="bi bi-send me-1"></i> Kirim
                        </button>
                    </form>

                    <form action="{{ route('external-transfers.destroy', $row) }}" method="post" class="d-inline"
                        onsubmit="return confirm('Hapus dokumen draft ini?')">
                        @csrf
                        @method('delete')
                        <button class="btn btn-outline-danger btn-sm mb-1" type="submit">
                            <i class="bi bi-trash me-1"></i> Hapus
                        </button>
                    </form>
                @elseif($row->status === 'sent')
                    <form action="{{ route('external-transfers.receive', $row) }}" method="post" class="d-inline"
                        onsubmit="return confirm('Konfirmasi barang sudah diterima?')">
                        @csrf
                        <button class="btn btn-success btn-sm mb-1" type="submit">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Terima
                        </button>
                    </form>
                @elseif(in_array($row->status, ['received', 'sent']))
                    <form action="{{ route('external-transfers.done', $row) }}" method="post" class="d-inline"
                        onsubmit="return confirm('Tandai dokumen ini selesai?')">
                        @csrf
                        <button class="btn btn-outline-success btn-sm mb-1" type="submit">
                            <i class="bi bi-check2-circle me-1"></i> Selesai
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- HEADER --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 small">
                    <div class="col-6 col-md-3">
                        <div class="text-muted mb-1">Tanggal</div>
                        <div class="mono">
                            {{ $row->date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="text-muted mb-1">Status</div>
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
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="text-muted mb-1">Proses</div>
                        <span class="chip-process mono text-uppercase">
                            {{ $row->process }}
                        </span>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="text-muted mb-1">Operator / Vendor</div>
                        <div class="mono">
                            {{ $row->operator_code ?: '—' }}
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="text-muted mb-1">Dari Gudang</div>
                        <div class="fw-semibold">
                            {{ $row->fromWarehouse?->code ?? '—' }}
                        </div>
                        <div class="text-muted">
                            {{ $row->fromWarehouse?->name ?? '' }}
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="text-muted mb-1">Ke Gudang / Vendor</div>
                        <div class="fw-semibold">
                            {{ $row->toWarehouse?->code ?? '—' }}
                        </div>
                        <div class="text-muted">
                            {{ $row->toWarehouse?->name ?? '' }}
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="text-muted mb-1">Catatan</div>
                        <div>{{ $row->notes ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL LOT --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="fw-semibold small text-uppercase mb-2">
                    Detail LOT ({{ $row->lines->count() }})
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>LOT</th>
                                <th>Item</th>
                                <th class="text-end">Qty Kirim</th>
                                <th>Satuan</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($row->lines as $ln)
                                <tr>
                                    <td class="mono">
                                        {{ $ln->lot?->code ?? '—' }}
                                    </td>
                                    <td>
                                        <div class="mono">
                                            {{ $ln->item?->code ?? '—' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $ln->item?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($ln->qty, 2, ',', '.') }}
                                    </td>
                                    <td class="mono">
                                        {{ $ln->uom }}
                                    </td>
                                    <td class="small">
                                        {{ $ln->notes ?: '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 small text-muted">
                                        Tidak ada detail LOT.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <div class="mb-5 small text-muted">
            Dibuat pada: {{ $row->created_at?->format('d M Y H:i') ?? '—' }} •
            Diperbarui: {{ $row->updated_at?->format('d M Y H:i') ?? '—' }}
        </div>
    </div>
@endsection
