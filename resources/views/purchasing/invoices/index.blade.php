@extends('layouts.app')
@section('title', 'Purchasing • Invoices')

@push('head')
    <style>
        :root {
            --radius: 14px;
        }

        .wrap {
            max-width: 1100px;
            margin-inline: auto
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
        }

        .card.table-card {
            /* shadow khusus kartu tabel, halus tapi tetap “angkat” */
            box-shadow: 0 10px 24px rgba(0, 0, 0, .18);
        }

        .soft {
            border-color: color-mix(in srgb, var(--line) 70%, transparent 30%);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .muted {
            color: var(--muted);
        }

        .btn-ghost {
            border: 1px solid var(--line);
            background: transparent;
        }

        .btn-ghost:hover {
            background: color-mix(in srgb, var(--card) 85%, var(--line) 15%);
        }

        /* HEADER STICKY (MOBILE) */
        .page-header {
            margin-bottom: 0.75rem;
            border-radius: var(--radius);
            background: color-mix(in srgb, var(--card) 92%, var(--line) 8%);
        }

        @media (max-width: 767.98px) {
            .page-header {
                position: sticky;
                top: 0;
                z-index: 30;
                padding-block: .5rem;
                /* lebih terang & shadow minimal supaya tidak terlalu gelap di dark mode */
                background: color-mix(in srgb, var(--card) 88%, rgba(255, 255, 255, .08) 12%);
                border-radius: 0 0 var(--radius) var(--radius);
                box-shadow: 0 4px 10px rgba(0, 0, 0, .18);
            }

            .page-header h5 {
                font-size: 1rem;
            }

            .page-header .muted {
                font-size: .8rem;
            }

            .page-header .btn {
                padding-inline: .6rem;
            }
        }

        /* KPI */
        .kpi {
            padding: .9rem 1rem;
        }

        .kpi .label {
            font-size: .82rem;
            color: var(--muted);
            letter-spacing: .02em;
        }

        .kpi .value {
            font-weight: 600;
            font-size: 1.15rem;
        }

        /* KPI: sembunyikan di mobile */
        @media (max-width: 767.98px) {
            .kpi-row {
                display: none !important;
            }
        }

        /* Filter */
        .filter {
            border-radius: var(--radius);
        }

        .filter .form-control,
        .filter .form-select {
            border-radius: 10px;
            background: transparent;
            border: 1px solid var(--line);
        }

        @media (max-width: 767.98px) {
            .filter {
                padding: .75rem;
                margin-bottom: 0.75rem;
            }

            .filter .row>[class^="col-"],
            .filter .row>[class*=" col-"] {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .filter .form-control,
            .filter .form-select {
                font-size: .85rem;
                padding: .35rem .55rem;
            }
        }

        /* Table minimal */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            font-weight: 600;
            color: var(--muted);
            background: color-mix(in srgb, var(--card) 88%, var(--line) 12%);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        /* cell transparan, warna diatur di tr */
        .table th,
        .table td {
            border: 0;
            background: transparent;
        }

        .table tbody tr+tr td {
            border-top: 1px dashed color-mix(in srgb, var(--line) 80%, transparent 20%);
        }

        /* WARNA BARIS: beda dari background + eye catching */
        .table tbody tr.invoice-row {
            cursor: pointer;
            transition: background-color .15s ease, box-shadow .15s ease, transform .12s ease;
            background: color-mix(in srgb, var(--card) 85%, var(--line) 15%);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
        }

        .table tbody tr.invoice-row:nth-child(even) {
            background: color-mix(in srgb, var(--card) 80%, var(--line) 20%);
        }

        .table tbody tr.invoice-row:hover {
            background: color-mix(in srgb, var(--card) 75%, var(--line) 25%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .20);
        }

        .badge {
            border-radius: 999px;
            font-size: .72rem;
            padding: .18rem .6rem;
        }

        .row-gap {
            row-gap: .5rem;
        }

        /* Status pill */
        .status-pill {
            border-radius: 999px;
            font-size: .72rem;
            padding: .14rem .55rem;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border: 1px solid color-mix(in srgb, var(--line) 80%, transparent 20%);
            background: color-mix(in srgb, var(--card) 85%, var(--line) 15%);
            white-space: nowrap;
        }

        .status-pill span.label {
            letter-spacing: .04em;
            font-size: .7rem;
            text-transform: uppercase;
            opacity: .8;
        }

        .status-dot {
            width: .55rem;
            height: .55rem;
            border-radius: 999px;
        }

        .status-dot.doc-draft {
            background-color: #ffc107;
        }

        .status-dot.doc-posted {
            background-color: #28a745;
        }

        .status-dot.pay-unpaid {
            background-color: #dc3545;
        }

        .status-dot.pay-partial {
            background-color: #ffc107;
        }

        .status-dot.pay-paid {
            background-color: #28a745;
        }

        /* TOTAL text */
        .total-cell {
            font-size: .9rem;
        }

        .total-cell .total-mobile {
            font-size: .8rem;
        }

        @media (max-width: 767.98px) {
            .total-cell {
                font-size: .8rem;
            }
        }

        /* table padding di mobile */
        @media (max-width: 767.98px) {

            .table td,
            .table th {
                padding-block: .4rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="wrap py-3">
        {{-- Header (sticky di mobile) --}}
        <div class="d-flex align-items-center justify-content-between page-header px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <div>
                    <h5 class="mb-0">Purchasing • Invoices</h5>
                    <div class="muted small">Ringkasan faktur pembelian dan status pembayarannya</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-ghost btn-sm" href="{{ route('purchasing.invoices.index') }}">Reset</a>
                <a href="{{ route('purchasing.invoices.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Invoice Baru
                </a>
            </div>
        </div>

        {{-- KPI (desktop saja) --}}
        <div class="row row-gap g-2 mb-3 kpi-row d-none d-md-flex">
            <div class="col-6 col-md-3">
                <div class="card kpi">
                    <div class="label">Jumlah Faktur</div>
                    <div class="value mono">{{ number_format($stats['count'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card kpi">
                    <div class="label">Total</div>
                    <div class="value mono">Rp {{ number_format($stats['total'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card kpi">
                    <div class="label">Dibayar</div>
                    <div class="value mono">Rp {{ number_format($stats['paid'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card kpi">
                    <div class="label">Sisa</div>
                    <div class="value mono">Rp {{ number_format($stats['remain'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" class="card soft p-3 mb-3 filter" id="filterForm">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control"
                        placeholder="Cari kode / supplier">
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Dokumen</option>
                        @foreach (['draft' => 'Draft', 'posted' => 'Posted'] as $k => $v)
                            <option value="{{ $k }}" @selected(($status ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="payment" class="form-select">
                        <option value="">Status Bayar</option>
                        @foreach (['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'] as $k => $v)
                            <option value="{{ $k }}" @selected(($pay ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <select name="supplier" class="form-select">
                        <option value="">Semua Supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(($supp ?? '') == $s->id)>
                                {{ $s->code ?? $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <input type="text" name="range" value="{{ $range ?? '' }}" class="form-control"
                        placeholder="YYYY-MM-DD s/d YYYY-MM-DD">
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="card table-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            {{-- Kode: desktop saja --}}
                            <th class="d-none d-md-table-cell" style="width:16%">Kode</th>

                            {{-- Tanggal: mobile DD-MM, desktop YYYY-MM-DD --}}
                            <th style="width:12%">Tanggal</th>

                            {{-- Supplier: mobile & desktop = nama supplier --}}
                            <th>Supplier</th>

                            {{-- Total --}}
                            <th class="text-end" style="width:14%">Total</th>

                            {{-- Dibayar: desktop saja --}}
                            <th class="text-end d-none d-md-table-cell" style="width:14%">Dibayar</th>

                            {{-- Sisa: desktop saja --}}
                            <th class="text-end d-none d-md-table-cell" style="width:14%">Sisa</th>

                            <th style="width:16%">Status</th>
                            <th style="width:8%" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $fmt = fn($n)=>number_format((float)$n,0,',','.'); @endphp
                        @forelse ($rows as $inv)
                            @php
                                $total =
                                    $inv->grand_total ??
                                    $inv->lines()->selectRaw('COALESCE(SUM(qty*unit_cost),0) t')->value('t');
                                $paid = method_exists($inv, 'payments')
                                    ? (float) $inv->payments()->sum('amount')
                                    : (float) ($inv->paid_amount ?? 0);
                                $remainRow = max(0, (float) $total - $paid);

                                $payStatus =
                                    $inv->payment_status ??
                                    ($remainRow <= 0.00001 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'));

                                $payLabel =
                                    [
                                        'paid' => 'Lunas',
                                        'partial' => 'Sebagian',
                                        'unpaid' => 'Belum Bayar',
                                    ][$payStatus] ?? strtoupper($payStatus);
                            @endphp
                            <tr class="invoice-row" data-href="{{ route('purchasing.invoices.show', $inv) }}">
                                {{-- Kode: desktop-only --}}
                                <td class="mono d-none d-md-table-cell">
                                    {{ $inv->code }}
                                </td>

                                {{-- Tanggal: desktop = YYYY-MM-DD, mobile = DD-MM --}}
                                <td class="mono">
                                    <span class="d-none d-md-inline">
                                        {{ \Illuminate\Support\Carbon::parse($inv->date)->toDateString() }}
                                    </span>
                                    <span class="d-inline d-md-none">
                                        {{ \Illuminate\Support\Carbon::parse($inv->date)->format('d-m') }}
                                    </span>
                                </td>

                                {{-- Supplier: pakai nama saja (mobile & desktop) --}}
                                <td>
                                    <span>
                                        {{ $inv->supplier?->name ?? '—' }}
                                    </span>

                                    @if (!empty($inv->note))
                                        <div class="small muted d-none d-md-block">
                                            {{ \Illuminate\Support\Str::limit($inv->note, 64) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Total --}}
                                <td class="mono text-end total-cell">
                                    {{-- Desktop: pakai Rp --}}
                                    <span class="d-none d-md-inline">
                                        Rp {{ $fmt($total) }}
                                    </span>
                                    {{-- Mobile: hanya angka, lebih kecil --}}
                                    <span class="d-inline d-md-none total-mobile">
                                        {{ $fmt($total) }}
                                    </span>
                                </td>

                                {{-- Dibayar: desktop-only --}}
                                <td class="mono text-end d-none d-md-table-cell">
                                    Rp {{ $fmt($paid) }}
                                </td>

                                {{-- Sisa: desktop-only --}}
                                <td class="mono text-end d-none d-md-table-cell">
                                    Rp {{ $fmt($remainRow) }}
                                </td>

                                {{-- Status visual --}}
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        {{-- Dokumen --}}
                                        <span class="status-pill">
                                            <span
                                                class="status-dot {{ $inv->status === 'posted' ? 'doc-posted' : 'doc-draft' }}"></span>
                                            <span class="label">DOC</span>
                                            <span>{{ $inv->status === 'posted' ? 'Posted' : 'Draft' }}</span>
                                        </span>

                                        {{-- Pembayaran --}}
                                        <span class="status-pill">
                                            <span
                                                class="status-dot
                                                {{ $payStatus === 'paid' ? 'pay-paid' : ($payStatus === 'partial' ? 'pay-partial' : 'pay-unpaid') }}">
                                            </span>
                                            <span class="label">PAY</span>
                                            <span>{{ $payLabel }}</span>
                                        </span>
                                    </div>
                                </td>

                                {{-- Aksi --}}
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        {{-- Detail: hanya di desktop (mobile pakai klik baris) --}}
                                        <a href="{{ route('purchasing.invoices.show', $inv) }}"
                                            class="btn btn-ghost d-none d-md-inline-flex" title="Lihat Detail"
                                            aria-label="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        {{-- Edit detail (kalau masih draft) --}}
                                        @if ($inv->status === 'draft')
                                            <a href="{{ route('purchasing.invoices.lines.edit', $inv) }}"
                                                class="btn btn-outline-primary" title="Edit Detail"
                                                aria-label="Edit Detail">
                                                {{-- Desktop: icon saja --}}
                                                <span class="d-none d-md-inline">
                                                    <i class="bi bi-pencil-square"></i>
                                                </span>
                                                {{-- Mobile: icon kecil + teks bisa dibaca --}}
                                                <span class="d-inline d-md-none">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center muted py-4">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-2">
                {{ $rows->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('filterForm');
            if (!form) return;

            // Debounce helper
            let timer = null;
            const debounce = (fn, wait = 500) => (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), wait);
            };

            // Submit with rebuilt query (reset page param)
            const submitFiltered = () => {
                const url = new URL(window.location.href);
                const fd = new FormData(form);
                url.search = '';
                for (const [k, v] of fd.entries()) {
                    if (v !== '') url.searchParams.set(k, v);
                }
                window.history.replaceState({}, '', url); // reflect query instantly
                form.submit();
            };

            const debounced = debounce(submitFiltered, 500);

            // Selects → submit segera pada change
            form.querySelectorAll('select').forEach(el => {
                el.addEventListener('change', submitFiltered);
            });

            // Inputs teks → debounce
            form.querySelectorAll('input[type="text"], input[type="search"]').forEach(el => {
                el.addEventListener('input', debounced);
                el.addEventListener('change', submitFiltered);
            });

            // Range (rentang tanggal) → ikut debounce
            const rangeEl = form.querySelector('input[name="range"]');
            if (rangeEl) {
                rangeEl.addEventListener('input', debounced);
            }

            // UX: ESC untuk clear cepat field aktif & apply
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && document.activeElement instanceof HTMLInputElement) {
                    const el = document.activeElement;
                    if (el.form === form && (el.type === 'text' || el.type === 'search')) {
                        el.value = '';
                        debounced();
                    }
                }
            });

            // === Klik baris -> ke halaman detail (kecuali klik tombol / link) ===
            document.querySelectorAll('tr.invoice-row').forEach(row => {
                row.addEventListener('click', (e) => {
                    // Jika klik di dalam tombol / link, jangan navigate
                    const interactive = e.target.closest('a, button, input, label, select');
                    if (interactive) return;

                    const href = row.dataset.href;
                    if (href) {
                        window.location.href = href;
                    }
                });
            });
        })();
    </script>
@endpush
