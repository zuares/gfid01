@extends('layouts.app')
@section('title', 'Inventory • Stocks (Per Item & Gudang)')

@push('head')
    <style>
        :root {
            --in: var(--bs-teal);
            --out: var(--bs-orange);
            --radius: 14px;
        }

        /* ===== Wrap agar identik dengan Purchasing ===== */
        .wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        /* ===== Komponen dasar ikut tema global dari layouts.app ===== */
        .card,
        .kpi-bar,
        .header-wrap {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--card);
            transition: background .2s, color .2s, border-color .2s;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: var(--font-mono, ui-monospace, SFMono-Regular, Menlo, Consolas, monospace);
        }

        .muted {
            color: var(--muted);
        }

        /* ===== KPI BAR ===== */
        .kpi-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: .7rem .9rem;
            height: 100%;
            background: color-mix(in srgb, var(--bs-primary) 4%, var(--card) 96%);
        }

        .kpi-label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .kpi-value {
            font-size: 1.2rem;
            font-weight: 700
        }

        .qty-ok {
            color: var(--bs-teal);
            font-weight: 700
        }

        .qty-low {
            color: var(--bs-blue);
            font-weight: 700
        }

        .qty-zero {
            color: var(--muted);
            font-weight: 700
        }

        .qty-neg {
            color: var(--bs-red);
            font-weight: 700
        }

        /* ===== HEADER FILTER ===== */
        .header-wrap {
            padding: .9rem 1rem;
            background: color-mix(in srgb, var(--bs-primary) 5%, var(--card) 95%)
        }

        #miniFilter .form-control,
        #miniFilter .form-select {
            min-height: 34px;
            border-radius: 10px;
            border-color: var(--line);
            background: var(--bg);
            color: var(--fg);
        }

        .input-ico {
            position: absolute;
            top: 8px;
            left: 10px;
            color: var(--muted)
        }

        .with-ico {
            padding-left: 1.6rem
        }

        .btn-export {
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--card);
            display: flex;
            align-items: center;
            gap: .5rem;
            min-height: 34px;
            color: var(--fg);
            transition: all .15s ease;
        }

        .btn-export:hover {
            background: color-mix(in srgb, var(--bs-primary) 6%, var(--card) 94%)
        }

        .dropdown-menu {
            border-radius: 12px;
            border: 1px solid var(--line);
            background: var(--card);
            color: var(--fg)
        }

        /* ===== TABLE ===== */
        .table-items {
            width: 100%;
            border-collapse: collapse
        }

        .table-items th,
        .table-items td {
            padding: .7rem .85rem;
            border-bottom: 1px solid var(--line);
            vertical-align: middle
        }

        .table-items th {
            background: color-mix(in srgb, var(--bs-primary) 8%, var(--card) 92%);
            color: var(--fg);
            font-weight: 600;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .row-main {
            cursor: pointer;
            transition: background .15s ease
        }

        .row-main:hover {
            background: color-mix(in srgb, var(--bs-primary) 6%, var(--bg) 94%)
        }

        .row-detail td {
            background: color-mix(in srgb, var(--bs-primary) 4%, var(--bg) 96%)
        }

        /* ===== BADGES ===== */
        .pill {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .74rem;
            font-weight: 600;
            line-height: 1;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }

        .pill-ok {
            background: color-mix(in srgb, var(--bs-teal) 15%, transparent 85%);
            color: var(--bs-teal);
            border-color: color-mix(in srgb, var(--bs-teal) 25%, transparent 75%);
        }

        .pill-low {
            background: color-mix(in srgb, var(--bs-blue) 20%, transparent 80%);
            color: var(--bs-blue);
            border-color: color-mix(in srgb, var(--bs-blue) 30%, transparent 70%);
        }

        .pill-warn {
            background: color-mix(in srgb, var(--bs-red) 20%, transparent 80%);
            color: var(--bs-red);
            border-color: color-mix(in srgb, var(--bs-red) 30%, transparent 70%);
        }

        .pill-slow {
            background: color-mix(in srgb, var(--fg) 10%, transparent 90%);
            color: var(--fg);
            border-color: color-mix(in srgb, var(--fg) 25%, transparent 75%);
        }

        /* ===== CHEVRON & SKELETON ===== */
        .chev {
            transition: transform .16s ease;
            color: var(--muted)
        }

        .chev.open {
            transform: rotate(90deg);
            color: var(--bs-primary)
        }

        .skel {
            background: linear-gradient(90deg, rgba(0, 0, 0, .05), rgba(0, 0, 0, .1), rgba(0, 0, 0, .05));
            background-size: 160% 100%;
            animation: shimmer 1.05s infinite linear;
            border-radius: 8px;
            height: 12px;
        }

        @keyframes shimmer {
            0% {
                background-position: 0 0
            }

            100% {
                background-position: 160% 0
            }
        }

        /* ===== Item Code Highlight (ikut mode) ===== */
        .item-code {
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase;
            transition: color .25s ease;
            color: var(--fg);
        }

        @media(max-width:576px) {

            .table-items th,
            .table-items td {
                padding: .55rem .6rem
            }

            .kpi-card {
                padding: .6rem .7rem
            }

            .kpi-value {
                font-size: 1.1rem
            }
        }
    </style>
@endpush

@section('content')
    <div class="wrap py-3"><!-- selaras dengan Purchasing -->
        {{-- KPI BAR --}}
        <div class="kpi-bar mb-3">
            <div class="row g-2 g-md-3">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-label">Total Items</div>
                        <div class="kpi-value mono">{{ number_format($kpi['total_item'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-label">Total Qty</div>
                        <div class="kpi-value mono qty-ok">{{ number_format($kpi['total_qty'] ?? 0, 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-exclamation-triangle-fill"></i> Low</div>
                        <div class="kpi-value qty-low">{{ number_format($kpi['low_count'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-label"><i class="bi bi-x-octagon-fill"></i> Out</div>
                        <div class="kpi-value qty-neg">{{ number_format($kpi['out_count'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- HEADER FILTER --}}
        <div class="header-wrap mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h1 class="h5 m-0">Inventory • Stocks (Per Item & Gudang)</h1>
                    <div class="small muted mt-1">
                        Klik baris item untuk melihat sebaran stok per gudang:
                        <strong>Kontrakan</strong>, <strong>Makloon (CUT-EXT / SEW-EXT)</strong>, dan <strong>Gudang
                            Lain</strong>.
                    </div>
                </div>

                <form id="miniFilter" class="d-flex align-items-center flex-wrap gap-2" method="GET"
                    action="{{ route('inventory.stocks.index') }}" autocomplete="off">

                    {{-- Cari --}}
                    <div class="position-relative" style="min-width:240px;">
                        <i class="bi bi-search input-ico"></i>
                        <input type="text" class="form-control form-control-sm with-ico js-mini" name="q"
                            value="{{ $q ?? '' }}" placeholder="Cari kode / nama item">
                        @if (!empty($q))
                            <button class="btn btn-sm position-absolute top-0 end-0 text-muted" type="button"
                                data-clear="q" style="height:100%; width:34px;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>

                    {{-- Gudang --}}
                    <select class="form-select form-select-sm js-mini-change" name="warehouse" style="width: 220px;">
                        <option value="">Semua Gudang</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(($warehouse ?? null) == $w->id)>
                                {{ $w->code }} — {{ $w->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Urutkan --}}
                    @php $curSort = $sort ?? 'qty_desc'; @endphp
                    <select class="form-select form-select-sm js-mini-change" name="sort" style="width: 170px;">
                        <option value="qty_desc" @selected($curSort === 'qty_desc')>Qty ↓</option>
                        <option value="qty_asc" @selected($curSort === 'qty_asc')>Qty ↑</option>
                        <option value="updated_desc" @selected($curSort === 'updated_desc')>Updated ↓</option>
                        <option value="updated_asc" @selected($curSort === 'updated_asc')>Updated ↑</option>
                        <option value="item_name" @selected($curSort === 'item_name')>Nama Item</option>
                        <option value="item_code" @selected($curSort === 'item_code')>Kode Item</option>
                    </select>

                    {{-- Toggle stok > 0 --}}
                    <div class="form-check form-switch ms-1">
                        <input class="form-check-input js-mini-change" type="checkbox" id="swPositive" name="only_positive"
                            value="on" @checked(!empty($onlyPositive))>
                        <label class="form-check-label small" for="swPositive">Hanya stok &gt; 0</label>
                    </div>

                    {{-- Export --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-export dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i><span class="ms-1">Export</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2"
                                    href="{{ route('inventory.stocks.index', array_merge(request()->query(), ['export' => 'csv'])) }}">
                                    <i class="bi bi-filetype-csv"></i> CSV (Excel)
                                </a>
                            </li>
                        </ul>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card p-0">
            <div class="table-responsive">
                <table class="table-items">
                    <thead>
                        <tr>
                            <th style="width:40px"></th>
                            <th>Item</th>
                            <th class="text-end">Qty</th>
                            <th>Status</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        @forelse ($rows as $r)
                            @php
                                $qty = (float) ($r->total_qty ?? 0);
                                $updatedAt = $r->last_updated ? \Carbon\Carbon::parse($r->last_updated) : null;
                                $updated = $updatedAt ? $updatedAt->format('Y-m-d H:i') : '-';
                                $days = $updatedAt ? $updatedAt->diffInDays() : null;

                                $statusHtml = '';
                                if ($qty < 0) {
                                    $statusHtml =
                                        '<span class="pill pill-warn"><i class="bi bi-bug-fill"></i> Negatif</span>';
                                } elseif ($qty == 0) {
                                    $statusHtml =
                                        '<span class="pill pill-low"><i class="bi bi-exclamation-triangle-fill"></i> Kosong</span>';
                                } elseif ($qty <= 5) {
                                    $statusHtml =
                                        '<span class="pill pill-low"><i class="bi bi-exclamation-triangle-fill"></i> Low</span>';
                                } else {
                                    $statusHtml =
                                        '<span class="pill pill-ok"><i class="bi bi-check2-circle"></i> Tersedia</span>';
                                }

                                $unitMismatch = (int) ($r->units_count ?? 1) > 1;
                                $slow = $days !== null && $days >= 30;
                            @endphp

                            <tr class="row-main" data-item="{{ $r->item_code }}">
                                <td class="text-center"><i class="bi bi-chevron-right chev"></i></td>
                                <td>
                                    <div class="fw-semibold">
                                        <span class="item-code">{{ $r->item_code }}</span>
                                        @if ($unitMismatch)
                                            <span class="pill pill-warn ms-2"><i class="bi bi-slash-circle"></i> Unit
                                                mismatch</span>
                                        @endif
                                        @if ($slow)
                                            <span class="pill pill-slow ms-2"><i class="bi bi-clock-history"></i>
                                                Slow</span>
                                        @endif
                                    </div>
                                    <div class="small muted text-truncate" style="max-width:520px">{{ $r->item_name }}
                                    </div>
                                </td>

                                <td
                                    class="text-end mono {{ $qty < 0 ? 'qty-neg' : ($qty == 0 ? 'qty-zero' : ($qty <= 5 ? 'qty-low' : 'qty-ok')) }}">
                                    {{ number_format($qty, 2, ',', '.') }}
                                </td>
                                <td>{!! $statusHtml !!}</td>
                                <td class="muted small text-nowrap">{{ $updated }}</td>
                            </tr>

                            {{-- Breakdown AJAX --}}
                            <tr class="row-detail d-none" data-detail="{{ $r->item_code }}">
                                <td></td>
                                <td colspan="4">
                                    <div class="py-2">
                                        <div class="skel mb-2" style="width:30%"></div>
                                        <div class="skel mb-1"></div>
                                        <div class="skel mb-1" style="width:80%"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center muted py-4">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-2">{{ $rows->links() }}</div>
        </div>
    </div>

    {{-- JS: filter realtime + breakdown --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('miniFilter');
            if (form) {
                const debounce = (fn, wait) => {
                    let t;
                    return (...a) => {
                        clearTimeout(t);
                        t = setTimeout(() => fn(...a), wait)
                    }
                };
                const deb = debounce(() => form.requestSubmit(), 260);

                form.addEventListener('keydown', e => {
                    if (e.key === 'Enter') e.preventDefault();
                });
                form.querySelectorAll('.js-mini').forEach(el => el.addEventListener('input', deb));
                form.querySelectorAll('.js-mini-change').forEach(el => el.addEventListener('change', () => form
                    .requestSubmit()));
                form.querySelectorAll('[data-clear]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const name = btn.getAttribute('data-clear');
                        const input = form.querySelector(`[name="${name}"]`);
                        if (input) {
                            input.value = '';
                            form.requestSubmit();
                        }
                    });
                });
            }

            const tbody = document.getElementById('itemsBody');
            tbody.addEventListener('click', async (e) => {
                const tr = e.target.closest('.row-main');
                if (!tr) return;
                const item = tr.getAttribute('data-item');
                const detailRow = tbody.querySelector(`.row-detail[data-detail="${item}"]`);
                const icon = tr.querySelector('.chev');
                const isOpen = !detailRow.classList.contains('d-none');

                // close all first
                tbody.querySelectorAll('.row-detail').forEach(x => x.classList.add('d-none'));
                tbody.querySelectorAll('.row-main .chev').forEach(x => x.classList.remove('open'));
                if (isOpen) return;

                icon.classList.add('open');
                detailRow.classList.remove('d-none');
                detailRow.querySelector('td:nth-child(2)').innerHTML = `
            <div class="py-2">
                <div class="skel mb-2" style="width:32%"></div>
                <div class="skel mb-1"></div>
                <div class="skel mb-1" style="width:78%"></div>
            </div>`;

                try {
                    const url = new URL(
                        "{{ route('inventory.stocks.breakdown', ['itemCode' => '__ITEM__']) }}"
                        .replace('__ITEM__', encodeURIComponent(item)),
                        window.location.origin
                    );
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    detailRow.querySelector('td:nth-child(2)').innerHTML = await res.text();
                } catch {
                    detailRow.querySelector('td:nth-child(2)').innerHTML =
                        '<div class="py-2 text-center text-danger small">Gagal memuat rincian.</div>';
                }
            });
        });
    </script>
@endsection
