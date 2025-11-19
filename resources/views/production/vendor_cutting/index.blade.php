@extends('layouts.app')

@section('title', 'Cutting • Kiriman Bahan')

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

        .muted {
            color: var(--muted);
        }

        .tag-soft {
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
        }

        .mobile-card-header {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
        }

        .mobile-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .75rem;
            font-size: .8rem;
        }

        .mobile-card-meta span {
            white-space: nowrap;
        }

        .mobile-item-summary {
            font-size: .85rem;
        }

        .mobile-item-summary .label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .mobile-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h1 class="h4 mb-0">Kiriman Bahan ke Cutting</h1>

            <form method="GET" class="d-flex gap-2 w-100 w-md-auto">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                    placeholder="Cari kode transfer…">
                <button class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info small">{{ session('info') }}</div>
        @endif

        <div class="card">

            {{-- DESKTOP / TABLET: TABEL BIASA --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Dari</th>
                            <th>Ke</th>
                            <th>Status</th>
                            <th>Batch</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $t)
                            <tr>
                                <td class="mono">{{ $t->code }}</td>
                                <td>{{ $t->date?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $t->fromWarehouse->name ?? '-' }}</div>
                                    <div class="text-muted small mono">{{ $t->fromWarehouse->code ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $t->toWarehouse->name ?? '-' }}</div>
                                    <div class="text-muted small mono">{{ $t->toWarehouse->code ?? '' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ strtoupper($t->status) }}</span>
                                </td>
                                <td>
                                    @if ($t->productionBatch)
                                        <div class="mono">{{ $t->productionBatch->code }}</div>
                                    @else
                                        <span class="text-muted small">Belum ada</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($t->productionBatch)
                                        {{-- Kalau sudah punya batch → tombol Lihat Batch --}}
                                        <a href="{{ route('production.vendor_cutting.batches.show', $t->productionBatch->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Lihat Batch
                                        </a>
                                    @else
                                        {{-- Kalau belum → tombol Terima & Buat Batch --}}
                                        <a href="{{ route('production.vendor_cutting.receive.form', $t->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="bi bi-scissors"></i> Terima & Buat Batch
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small py-4">
                                    Belum ada kiriman bahan untuk cutting.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE: CARD MINIMALIS DENGAN RINGKASAN ITEM --}}
            <div class="d-block d-md-none">
                @forelse ($transfers as $t)
                    @php
                        // Asumsi: relasi detail item = $t->lines (silakan sesuaikan kalau beda)
                        $lines = $t->lines ?? collect();
                        $firstLine = $lines->first();
                        $otherCount = max($lines->count() - 1, 0);
                    @endphp

                    <div class="border-bottom px-3 py-2">
                        {{-- HEADER: KODE + STATUS --}}
                        <div class="mobile-card-header mb-1">
                            <div>
                                <div class="mono fw-semibold">{{ $t->code }}</div>
                                <div class="small muted">
                                    {{ $t->date?->format('d/m/Y') ?? '-' }}
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="tag-soft mono">
                                    {{ strtoupper($t->status) }}
                                </span>
                            </div>
                        </div>

                        {{-- META: DARI / KE / BATCH --}}
                        <div class="mobile-card-meta mb-2 muted">
                            <span>
                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                {{ $t->fromWarehouse->code ?? '-' }}
                            </span>
                            <span>
                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                {{ $t->toWarehouse->code ?? '-' }}
                            </span>
                            <span>
                                <i class="bi bi-grid-3x3-gap me-1"></i>
                                {{ $t->productionBatch->code ?? 'Belum ada batch' }}
                            </span>
                        </div>

                        {{-- RINGKASAN ITEM DIKIRIM (BAHAN + WARNA) --}}
                        <div class="mobile-item-summary mb-2">
                            <div class="label text-uppercase text-muted mb-1">Ringkasan bahan</div>
                            @if ($firstLine)
                                <div class="mono">
                                    {{-- contoh: FLC280BLK — Fleece 280 Hitam --}}
                                    {{ $firstLine->item_code ?? '' }}
                                    @if (!empty($firstLine->item_name))
                                        — {{ $firstLine->item_name }}
                                    @endif
                                </div>
                                @if ($otherCount > 0)
                                    <div class="small text-muted">
                                        +{{ $otherCount }} item lain
                                    </div>
                                @endif
                            @else
                                <div class="small text-muted">
                                    Belum ada detail bahan di dokumen ini.
                                </div>
                            @endif
                        </div>

                        {{-- AKSI --}}
                        <div class="mobile-actions">
                            @if ($t->productionBatch)
                                <a href="{{ route('production.vendor_cutting.batches.show', $t->productionBatch->id) }}"
                                    class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-eye"></i> Lihat Batch
                                </a>
                            @else
                                <a href="{{ route('production.vendor_cutting.receive.form', $t->id) }}"
                                    class="btn btn-sm btn-primary flex-fill">
                                    <i class="bi bi-scissors"></i> Terima & Buat Batch
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-3 py-4 text-center text-muted small">
                        Belum ada kiriman bahan untuk cutting.
                    </div>
                @endforelse
            </div>

            <div class="card-footer py-2">
                {{ $transfers->withQueryString()->links() }}
            </div>
        </div>

    </div>
@endsection
