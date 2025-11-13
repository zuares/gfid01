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
            border-radius: var(--radius)
        }

        .soft {
            border-color: color-mix(in srgb, var(--line) 70%, transparent 30%)
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace
        }

        .muted {
            color: var(--muted)
        }

        .btn-ghost {
            border: 1px solid var(--line);
            background: transparent
        }

        /* KPI */
        .kpi {
            padding: .9rem 1rem
        }

        .kpi .label {
            font-size: .82rem;
            color: var(--muted);
            letter-spacing: .02em
        }

        .kpi .value {
            font-weight: 600;
            font-size: 1.15rem
        }

        /* Filter */
        .filter .form-control,
        .filter .form-select {
            border-radius: 10px;
            background: transparent;
            border: 1px solid var(--line)
        }

        /* Table minimal */
        .table {
            margin: ;

        }

        .table thead th {
            font-weight: 600;
            color: var(--muted);
            background: var(--card);
            position: sticky;
            top: 0;
            z-index: 1
        }

        .table th,
        .table td {
            border: 0;
            background: var(--card);
        }

        .table tbody tr+tr td {
            border-top: 1px dashed color-mix(in srgb, var(--line) 80%, transparent 20%);
        }



        .table tbody tr {
            transition: background-color .15s ease, box-shadow .15s ease;
        }




        .badge {
            border-radius: 999px;
            font-size: .72rem;
            padding: .18rem .6rem
        }

        .row-gap {
            row-gap: .5rem
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
            /* warning-ish */
        }

        .status-dot.doc-posted {
            background-color: #28a745;
            /* success-ish */
        }

        .status-dot.pay-unpaid {
            background-color: #dc3545;
            /* danger-ish */
        }

        .status-dot.pay-partial {
            background-color: #ffc107;
            /* warning-ish */
        }

        .status-dot.pay-paid {
            background-color: #28a745;
            /* success-ish */
        }
    </style>
@endpush

@section('content')
    <div class="wrap py-3">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
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

        {{-- KPI --}}
        <div class="row row-gap g-2 mb-3">
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

        {{-- Filter (auto-apply, no submit button) --}}
        <form method="GET" class="card soft p-3 mb-3 filter" id="filterForm">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control"
                        placeholder="Cari kode/supplier">
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
                            <option value="{{ $s->id }}" @selected(($supp ?? '') == $s->id)>{{ $s->name }}</option>
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
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:16%">Kode</th>
                            <th style="width:12%">Tanggal</th>
                            <th>Supplier</th>
                            <th class="text-end" style="width:14%">Total</th>
                            <th class="text-end" style="width:14%">Dibayar</th>
                            <th class="text-end" style="width:14%">Sisa</th>
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
                                $payBadge =
                                    ['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'secondary'][
                                        $payStatus
                                    ] ?? 'secondary';
                                $docBadge = $inv->status === 'posted' ? 'success' : 'secondary';

                                $payLabel =
                                    [
                                        'paid' => 'Lunas',
                                        'partial' => 'Sebagian',
                                        'unpaid' => 'Belum Bayar',
                                    ][$payStatus] ?? strtoupper($payStatus);
                            @endphp
                            <tr>
                                {{-- Kode + chip kecil --}}
                                <td class="mono">
                                    {{ $inv->code }}
                                    <div class="small mt-1">

                                    </div>
                                </td>

                                {{-- Tanggal --}}
                                <td class="mono">
                                    {{ \Illuminate\Support\Carbon::parse($inv->date)->toDateString() }}
                                </td>

                                {{-- Supplier + note singkat --}}
                                <td>
                                    {{ $inv->supplier?->name ?? '—' }}
                                    @if (!empty($inv->note))
                                        <div class="small muted">
                                            {{ \Illuminate\Support\Str::limit($inv->note, 64) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Angka --}}
                                <td class="mono text-end">Rp {{ $fmt($total) }}</td>
                                <td class="mono text-end">Rp {{ $fmt($paid) }}</td>
                                <td class="mono text-end">Rp {{ $fmt($remainRow) }}</td>

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

                                {{-- Aksi pakai icon --}}
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        {{-- Detail --}}
                                        <a href="{{ route('purchasing.invoices.show', $inv) }}" class="btn btn-ghost"
                                            title="Lihat Detail" aria-label="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        {{-- Edit detail (kalau masih draft) --}}
                                        @if ($inv->status === 'draft')
                                            <a href="{{ route('purchasing.invoices.lines.edit', $inv) }}"
                                                class="btn btn-outline-primary" title="Edit Detail"
                                                aria-label="Edit Detail">
                                                <i class="bi bi-pencil-square"></i>
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
        })();
    </script>
@endpush
