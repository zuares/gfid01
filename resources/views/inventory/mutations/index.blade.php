@extends('layouts.app')
@section('title', 'Inventory • Mutations')

@push('head')
    <style>
        :root {
            --radius: 14px;
            --line: color-mix(in srgb, var(--bs-border-color) 78%, var(--bs-body-bg) 22%);
            --head-bg: color-mix(in srgb, var(--bs-primary) 7%, var(--bs-body-bg) 93%);
            --head-fg: color-mix(in srgb, var(--bs-primary-text-emphasis) 60%, var(--bs-body-color) 40%);
            --muted: var(--bs-secondary-color);
            --in: var(--bs-teal);
            --out: var(--bs-orange);
        }

        .wrap {
            max-width: 1100px;
            margin-inline: auto
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden
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
            font-size: 1.1rem
        }

        .kpi-in {
            color: var(--in)
        }

        .kpi-out {
            color: var(--out)
        }

        /* Filter */
        .filter .form-control,
        .filter .form-select {
            border-radius: 10px;
            background: transparent;
            border: 1px solid var(--line)
        }

        /* Chips */
        .chips .btn {
            border-color: var(--line)
        }

        .chips .btn.active {
            background: color-mix(in srgb, var(--bs-primary) 8%, transparent);
            border-color: var(--bs-primary)
        }

        /* Table */
        .table {
            margin: 0
        }

        .table thead th {
            font-weight: 600;
            color: var(--muted);
            background: var(--card);
            position: sticky;
            top: 0;
            z-index: 1;
            border-bottom: 1px solid var(--line);
            text-transform: uppercase;
            font-size: .78rem;
            letter-spacing: .03em
        }

        .table th,
        .table td {
            border: 0;
            vertical-align: middle
        }

        .table tbody tr+tr td {
            border-top: 1px dashed color-mix(in srgb, var(--line) 80%, transparent 20%)
        }

        .table tbody tr:hover {
            background: color-mix(in srgb, var(--bs-primary) 5%, var(--bs-body-bg) 95%)
        }

        tr.row-link {
            cursor: pointer
        }

        tr.row-link:active {
            transform: translateY(1px)
        }

        /* Subtotal badge */
        .sub-badge {
            background: color-mix(in srgb, var(--bs-primary) 10%, var(--bs-body-bg) 90%);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .18rem .55rem;
        }

        /* Qty rapi */
        .qty-cell {
            display: grid;
            grid-template-columns: auto minmax(5.2rem, auto) auto;
            align-items: baseline;
            justify-items: end;
            column-gap: .35rem;
            white-space: nowrap;
            font-variant-numeric: tabular-nums
        }

        .qty-num {
            min-width: 5.2rem;
            text-align: right
        }

        .qty-unit {
            color: var(--muted)
        }

        .qty-in {
            color: var(--in);
            font-weight: 600
        }

        .qty-out {
            color: var(--out);
            font-weight: 600
        }

        .qty-zero {
            color: var(--muted)
        }

        @media(max-width:768px) {
            .hide-sm {
                display: none
            }

            .qty-cell {
                grid-template-columns: auto minmax(4.8rem, auto) auto
            }

            .qty-num {
                min-width: 4.8rem
            }
        }
    </style>
@endpush

@section('content')
    <div class="wrap py-3">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-0">Inventory • Mutations</h5>
                <div class="muted small">Jejak mutasi per hari, fokus ke qty IN/OUT (tanpa nilai uang).</div>
            </div>
            <a class="btn btn-ghost btn-sm" href="{{ route('inventory.mutations.index') }}">Reset</a>
        </div>

        {{-- KPI BAR --}}
        @php
            $numf = fn($v, $d = 2) => number_format((float) $v, $d, ',', '.');
            $tIn = (float) ($totalIn ?? 0);
            $tOut = (float) ($totalOut ?? 0);
            $tNet = $tIn - $tOut;
        @endphp
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4">
                <div class="card kpi">
                    <div class="label">Total IN</div>
                    <div class="value mono kpi-in">{{ $numf($tIn, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card kpi">
                    <div class="label">Total OUT</div>
                    <div class="value mono kpi-out">{{ $numf($tOut, 2) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card kpi">
                    <div class="label">Net</div>
                    <div class="value mono">{{ $tNet >= 0 ? '+' : '−' }} {{ $numf(abs($tNet), 2) }}</div>
                </div>
            </div>
        </div>

        {{-- QUICK FILTER CHIPS --}}
        <div class="d-flex flex-wrap gap-2 mb-3 chips">
            @php
                $baseParams = [
                    'q' => request('q'),
                    'warehouse' => request('warehouse'),
                    'date_from' => request('date_from'),
                    'date_to' => request('date_to'),
                ];
                $chip = fn($p) => array_filter($p, fn($v) => $v !== null && $v !== '');

                // Ambil type dari controller (distinct dari DB)
                $types = collect($availableTypes ?? [])
                    ->filter()
                    ->values()
                    ->all();

                // Fallback kalau belum ada data sama sekali
                if (empty($types)) {
                    $types = [
                        'PURCHASE_IN',
                        'TRANSFER_OUT',
                        'TRANSFER_IN',
                        'CUTTING_USE',
                        'PRODUCTION_IN',
                        'ADJUSTMENT',
                        'SALE_OUT',
                    ];
                }
            @endphp

            <a class="btn btn-sm btn-outline-secondary {{ request('type') ? '' : 'active' }}"
                href="{{ route('inventory.mutations.index', $chip($baseParams)) }}">Semua Tipe</a>

            @foreach ($types as $t)
                <a class="btn btn-sm btn-outline-secondary {{ request('type') === $t ? 'active' : '' }}"
                    href="{{ route('inventory.mutations.index', $chip($baseParams + ['type' => $t])) }}">
                    {{ $t }}
                </a>
            @endforeach
        </div>

        {{-- FILTER BAR --}}
        <form method="GET" action="{{ route('inventory.mutations.index') }}" class="card soft p-3 mb-3 filter"
            id="mutFilter">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                        placeholder="Cari ref / item…">
                </div>
                <div class="col-6 col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Tipe</option>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="warehouse" class="form-select">
                        <option value="">Gudang</option>
                        @foreach ($warehouses ?? collect() as $w)
                            <option value="{{ $w->id }}" @selected((string) request('warehouse') === (string) $w->id)>
                                {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
            </div>
        </form>

        {{-- TABLE --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:110px">Tanggal</th>
                            <th style="width:110px">Tipe</th>
                            <th style="width:160px">Item</th>
                            <th style="width:160px">Gudang</th>
                            <th class="text-end" style="width:140px">Qty IN</th>
                            <th class="text-end" style="width:140px">Qty OUT</th>
                            <th class="text-end" style="width:120px">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grouped = $grouped ?? []; @endphp

                        @forelse($grouped as $dateKey => $grp)
                            @php
                                $items = $grp['items'] ?? [];
                                $sumIn = (float) ($grp['sum_in'] ?? 0);
                                $sumOut = (float) ($grp['sum_out'] ?? 0);
                                $netDay = $sumIn - $sumOut;
                            @endphp

                            {{-- SUBTOTAL HARI --}}
                            <tr>
                                <td class="py-2">
                                    <span class="sub-badge mono">{{ $dateKey }}</span>
                                </td>
                                <td class="py-2 muted">Subtotal</td>
                                <td class="py-2 muted">—</td>
                                <td class="py-2 muted">—</td>
                                <td class="py-2 text-end mono">
                                    {{ $sumIn > 0 ? $numf($sumIn, 2) : '0,00' }}
                                </td>
                                <td class="py-2 text-end mono">
                                    {{ $sumOut > 0 ? $numf($sumOut, 2) : '0,00' }}
                                </td>
                                <td class="py-2 text-end mono">
                                    {{ $netDay >= 0 ? '+' : '−' }} {{ $numf(abs($netDay), 2) }}
                                </td>
                            </tr>

                            {{-- ITEM ROWS --}}
                            @foreach ($items as $row)
                                @php
                                    $qIn = (float) ($row->qty_in ?? 0);
                                    $qOut = (float) ($row->qty_out ?? 0);
                                    $net = $qIn - $qOut;

                                    $itemCode = $row->item_code;
                                    $wh = ($warehouses ?? collect())->firstWhere('id', (int) $row->warehouse_id);
                                    $whLabel = $wh ? $wh->code . ' — ' . $wh->name : '-';

                                    $href = route('inventory.mutations.show', $row->id ?? 0);
                                @endphp
                                <tr class="row-link" data-href="{{ $href }}">
                                    {{-- Jam --}}
                                    <td class="mono muted">
                                        {{ \Carbon\Carbon::parse($row->date)->format('d M Y H:i') }}
                                    </td>

                                    {{-- Tipe --}}
                                    <td>
                                        <span class="badge bg-light text-dark mono">
                                            {{ $row->type ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- Item --}}
                                    <td class="mono">
                                        {{ $itemCode ?? '—' }}
                                    </td>

                                    {{-- Gudang --}}
                                    <td>
                                        <span class="muted small">{{ $whLabel }}</span>
                                    </td>

                                    {{-- Qty IN --}}
                                    <td class="text-end">
                                        <div class="qty-cell mono">
                                            <span class="qty-num qty-in">
                                                {{ $qIn > 0 ? $numf($qIn, 2) : '0,00' }}
                                            </span>
                                            <span class="qty-unit">{{ $row->unit ?? '' }}</span>
                                        </div>
                                    </td>

                                    {{-- Qty OUT --}}
                                    <td class="text-end">
                                        <div class="qty-cell mono">
                                            <span class="qty-num qty-out">
                                                {{ $qOut > 0 ? $numf($qOut, 2) : '0,00' }}
                                            </span>
                                            <span class="qty-unit">{{ $row->unit ?? '' }}</span>
                                        </div>
                                    </td>

                                    {{-- Net --}}
                                    <td class="text-end mono">
                                        {{ $net >= 0 ? '+' : '−' }} {{ $numf(abs($net), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="text-center muted py-4">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (!empty($rows))
                <div class="p-2">{{ $rows->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            // Row click → show page
            document.querySelectorAll('tr.row-link').forEach(tr => {
                tr.addEventListener('click', () => {
                    const href = tr.getAttribute('data-href');
                    if (href) window.location.href = href;
                });
            });

            // Filter auto-apply (debounce 500ms)
            const form = document.getElementById('mutFilter');
            if (!form) return;

            let timer = null;
            const debounce = (fn, wait = 500) => {
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), wait);
                }
            };
            const submitFiltered = () => {
                const url = new URL(window.location.href);
                const fd = new FormData(form);
                url.search = '';
                for (const [k, v] of fd.entries()) {
                    if (v !== '') url.searchParams.set(k, v);
                }
                window.history.replaceState({}, '', url);
                form.submit();
            };
            const debounced = debounce(submitFiltered, 500);

            form.querySelectorAll('select, input[type="date"]').forEach(el => {
                el.addEventListener('change', submitFiltered);
            });
            form.querySelectorAll('input[type="text"], input[type="search"]').forEach(el => {
                el.addEventListener('input', debounced);
                el.addEventListener('change', submitFiltered);
            });

            // ESC untuk clear field teks aktif
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
