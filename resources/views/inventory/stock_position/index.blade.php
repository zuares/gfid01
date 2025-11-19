@extends('layouts.app')
@section('title', 'Inventory • Posisi Stok')

@push('head')
    <style>
        .page-wrap {
            max-width: 1200px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .card-header {
            border-bottom: 1px solid var(--line);
            background: transparent;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
            font-variant-numeric: tabular-nums;
        }

        .help {
            color: var(--muted);
            font-size: .82rem;
        }

        .pill-filter {
            border-radius: 999px;
            border: 1px solid var(--line);
            padding: 4px 10px;
            font-size: .78rem;
            background: var(--panel);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .pill-filter.active {
            background: rgba(59, 130, 246, .12);
            border-color: rgba(59, 130, 246, .7);
            color: #60a5fa;
        }

        .pill-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
        }

        .pill-dot.raw {
            background: #fbbf24;
        }

        .pill-dot.wip-sew {
            background: #fb7185;
        }

        .pill-dot.wip-fin {
            background: #22c55e;
        }

        .pill-dot.fg {
            background: #38bdf8;
        }

        .pill-dot.external {
            background: #a855f7;
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
            padding-block: .45rem;
        }

        .badge-cat {
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .75rem;
            border: 1px solid var(--line);
        }

        .badge-cat.raw {
            background: rgba(250, 204, 21, .08);
            color: #facc15;
        }

        .badge-cat.wip-sew {
            background: rgba(248, 113, 113, .08);
            color: #fb7185;
        }

        .badge-cat.wip-fin {
            background: rgba(34, 197, 94, .08);
            color: #22c55e;
        }

        .badge-cat.fg {
            background: rgba(56, 189, 248, .08);
            color: #38bdf8;
        }

        .badge-cat.external {
            background: rgba(168, 85, 247, .08);
            color: #a855f7;
        }

        .badge-cat.other {
            background: rgba(148, 163, 184, .08);
            color: #94a3b8;
        }

        .search-input-wrap {
            position: relative;
        }

        .search-input-wrap .search-icon {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            font-size: .9rem;
            color: var(--muted);
        }

        .search-input-wrap input {
            padding-left: 1.7rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .filters-row>div {
                margin-bottom: .5rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="mb-1">Posisi Stok per Gudang</h4>
                <div class="small text-muted">
                    Lihat stok berjalan di gudang RAW, WIP Sewing, WIP Finishing, FG, dan gudang penjahit.
                </div>
            </div>
        </div>

        {{-- FILTER CARD --}}
        <div class="card mb-3">
            <div class="card-header py-2 border-0">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small fw-semibold text-uppercase text-muted">
                        Filter
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        {{-- Kategori cepat sebagai pill (ubah value di input hidden + submit) --}}
                        @php
                            $type = $warehouseType;
                        @endphp

                        <button type="button" class="pill-filter {{ $type === 'raw' ? 'active' : '' }}"
                            onclick="applyCategory('raw')">
                            <span class="pill-dot raw"></span> RAW
                        </button>

                        <button type="button" class="pill-filter {{ $type === 'wip_sewing' ? 'active' : '' }}"
                            onclick="applyCategory('wip_sewing')">
                            <span class="pill-dot wip-sew"></span> WIP Sewing
                        </button>

                        <button type="button" class="pill-filter {{ $type === 'wip_fin' ? 'active' : '' }}"
                            onclick="applyCategory('wip_fin')">
                            <span class="pill-dot wip-fin"></span> WIP Finishing
                        </button>

                        <button type="button" class="pill-filter {{ $type === 'fg' ? 'active' : '' }}"
                            onclick="applyCategory('fg')">
                            <span class="pill-dot fg"></span> FG
                        </button>

                        <button type="button" class="pill-filter {{ $type === 'external_sew' ? 'active' : '' }}"
                            onclick="applyCategory('external_sew')">
                            <span class="pill-dot external"></span> Gudang Jahit
                        </button>

                        <button type="button" class="pill-filter {{ $type === null || $type === '' ? 'active' : '' }}"
                            onclick="applyCategory('')">
                            <i class="bi bi-sliders"></i> Semua
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body small pt-2 pb-2">
                <form method="get" id="stockFilterForm">
                    <input type="hidden" name="warehouse_type" id="warehouse_type" value="{{ $warehouseType }}">

                    <div class="row g-2 align-items-end filters-row">
                        {{-- Gudang --}}
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Gudang</label>
                            <select name="warehouse_id" class="form-select form-select-sm">
                                <option value="">Semua gudang</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected($warehouseId == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Item Code --}}
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Kode Item</label>
                            <input type="text" name="item_code" class="form-control form-control-sm mono"
                                value="{{ $itemCode }}" placeholder="K7BLK / FLC280BLK">
                        </div>

                        {{-- Search general --}}
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Cari (Item / Gudang)</label>
                            <div class="search-input-wrap">
                                <span class="search-icon">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="q" class="form-control form-control-sm"
                                    value="{{ $q }}" placeholder="Nama item, kode gudang, ...">
                            </div>
                        </div>

                        {{-- Tombol --}}
                        <div class="col-md-2 d-flex gap-2 justify-content-md-end">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                <i class="bi bi-filter me-1"></i> Terapkan
                            </button>
                            <a href="{{ route('inventory.stock_position.index') }}"
                                class="btn btn-outline-secondary btn-sm flex-fill">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-footer py-2">
                <div class="d-flex justify-content-between align-items-center small text-muted flex-wrap gap-2">
                    <div>
                        Menampilkan stok dengan qty ≠ 0.
                    </div>
                    <div>
                        Halaman {{ $stocks->currentPage() }} dari {{ $stocks->lastPage() }} •
                        Total baris: {{ $stocks->total() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL HASIL --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Kategori</th>
                                <th>Gudang</th>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th style="width: 70px;">Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stocks as $row)
                                @php
                                    $code = $row->wh_code ?? '';
                                    $catKey = 'other';

                                    if (str_starts_with($code, 'RAW')) {
                                        $catKey = 'raw';
                                    } elseif (str_starts_with($code, 'WIP-SEW')) {
                                        $catKey = 'wip-sew';
                                    } elseif (str_starts_with($code, 'WIP-FIN')) {
                                        $catKey = 'wip-fin';
                                    } elseif (str_starts_with($code, 'FG')) {
                                        $catKey = 'fg';
                                    } elseif (str_starts_with($code, 'SEW-EXT-')) {
                                        $catKey = 'external';
                                    }

                                    $catLabel = match ($catKey) {
                                        'raw' => 'Raw Material',
                                        'wip-sew' => 'WIP Sewing',
                                        'wip-fin' => 'WIP Finishing',
                                        'fg' => 'Finished Goods',
                                        'external' => 'Gudang Jahit',
                                        default => 'Lainnya',
                                    };
                                @endphp

                                <tr>
                                    <td>
                                        <span class="badge-cat {{ $catKey }}">
                                            {{ $catLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="mono fw-semibold">
                                            {{ $row->wh_code }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $row->wh_name }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="mono fw-semibold">
                                            {{ $row->item_code }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $row->item_name }}
                                        </div>
                                    </td>

                                    <td class="text-end mono fw-semibold">
                                        {{ number_format($row->qty, 2, ',', '.') }}
                                    </td>

                                    <td class="mono">
                                        {{ $row->unit }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Tidak ada data stok yang cocok dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($stocks->hasPages())
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-between align-items-center small">
                        <div class="text-muted">
                            Menampilkan {{ $stocks->firstItem() }}–{{ $stocks->lastItem() }} dari
                            {{ $stocks->total() }} baris.
                        </div>
                        <div>
                            {{ $stocks->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function applyCategory(type) {
            const input = document.getElementById('warehouse_type');
            input.value = type || '';
            document.getElementById('stockFilterForm').submit();
        }
    </script>
@endpush
