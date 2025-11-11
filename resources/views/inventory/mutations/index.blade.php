@extends('layouts.app')
@section('title', 'Inventory • Mutations')

@push('head')
    <style>
        :root {
            --line: color-mix(in srgb, var(--bs-border-color) 78%, var(--bs-body-bg) 22%);
            --head-bg: color-mix(in srgb, var(--bs-primary) 7%, var(--bs-body-bg) 93%);
            --head-fg: color-mix(in srgb, var(--bs-primary-text-emphasis) 60%, var(--bs-body-color) 40%);
            --muted: var(--bs-secondary-color);
            --in: var(--bs-teal);
            --out: var(--bs-orange);
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace
        }

        .dim {
            color: var(--muted)
        }

        .table {
            margin: 0
        }

        .table th,
        .table td {
            vertical-align: middle
        }

        .table thead th {
            background: var(--head-bg);
            color: var(--head-fg);
            text-transform: uppercase;
            font-size: .78rem;
            letter-spacing: .03em;
            border-bottom: 1px solid var(--line)
        }

        .table tbody td {
            border-color: var(--line);
            padding: .55rem .70rem
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

        .kpi-card {
            border: 1px solid var(--line);
            border-radius: 12px
        }

        .kpi-title {
            font-size: .8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .03em
        }

        .kpi-value {
            font-size: 1.05rem
        }

        .kpi-in {
            color: var(--in)
        }

        .kpi-out {
            color: var(--out)
        }

        .chips .btn {
            border-color: var(--line)
        }

        .sub-badge {
            background: color-mix(in srgb, var(--bs-primary) 10%, var(--bs-body-bg) 90%);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .18rem .55rem;
        }

        /* ===== Qty rapi: sign | number | unit ===== */
        .qty-cell {
            display: grid;
            grid-template-columns: auto minmax(5.2rem, auto) auto;
            /* sign | angka | unit */
            align-items: baseline;
            justify-items: end;
            column-gap: .35rem;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .qty-sign {
            width: 1ch;
            text-align: right
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

        .val-num {
            min-width: 6.2rem;
            display: inline-block;
            text-align: right
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
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0">Inventory • Mutations</h3>
        </div>

        {{-- KPI BAR --}}
        @php
            $numf = function ($v, $d = 2) {
                return function_exists('numf') ? numf($v, $d) : number_format((float) $v, $d, ',', '.');
            };
            $idr = function ($v) {
                return function_exists('idr') ? idr($v, 0) : 'Rp ' . number_format((float) $v, 0, ',', '.');
            };
            $tIn = (float) ($totalIn ?? 0);
            $tOut = (float) ($totalOut ?? 0);
            $tNet = $tIn - $tOut;
        @endphp
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="kpi-card p-3">
                    <div class="kpi-title">Total IN</div>
                    <div class="kpi-value mono kpi-in">+ {{ $numf($tIn, 2) }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="kpi-card p-3">
                    <div class="kpi-title">Total OUT</div>
                    <div class="kpi-value mono kpi-out">− {{ $numf($tOut, 2) }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="kpi-card p-3">
                    <div class="kpi-title">Net</div>
                    <div class="kpi-value mono">{{ $tNet >= 0 ? '+' : '−' }} {{ $numf(abs($tNet), 2) }}</div>
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
                $types = [
                    'PURCHASE_IN',
                    'TRANSFER_OUT',
                    'TRANSFER_IN',
                    'CUTTING_USE',
                    'PRODUCTION_IN',
                    'ADJUSTMENT',
                    'SALE_OUT',
                ];
            @endphp

            <a class="btn btn-sm btn-outline-secondary {{ request('type') ? '' : 'active' }}"
                href="{{ route('inventory.mutations.index', $chip($baseParams)) }}">Semua Tipe</a>

            @foreach ($types as $t)
                <a class="btn btn-sm btn-outline-secondary {{ request('type') === $t ? 'active' : '' }}"
                    href="{{ route('inventory.mutations.index', $chip($baseParams + ['type' => $t])) }}">{{ $t }}</a>
            @endforeach
        </div>

        {{-- FILTER BAR --}}
        <form method="GET" action="{{ route('inventory.mutations.index') }}" class="row g-2 mb-3" id="mutFilter">
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
        </form>

        {{-- TABLE --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:110px">Tanggal</th>
                            <th style="width:110px">Tipe</th>
                            <th style="width:140px">Item</th>
                            <th class="text-end" style="width:120px">Harga</th>
                            <th class="text-end" style="width:180px">Qty</th>
                            <th class="text-end" style="width:110px">Net</th>
                            <th class="text-end" style="width:150px">Nilai</th>
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

                                $sumValIn = 0.0;
                                $sumValOut = 0.0;
                                foreach ($items as $gRow) {
                                    $uc = (float) ($gRow->unit_cost ?? ($gRow->lot->unit_cost ?? 0));
                                    $sumValIn += ((float) ($gRow->qty_in ?? 0)) * $uc;
                                    $sumValOut += ((float) ($gRow->qty_out ?? 0)) * $uc;
                                }
                                $sumValNet = $sumValIn - $sumValOut;
                            @endphp

                            {{-- SUBTOTAL --}}
                            <tr>
                                <td class="py-2"><span class="sub-badge mono">{{ $dateKey }}</span></td>
                                <td class="py-2 dim">Subtotal</td>
                                <td class="py-2 dim">—</td>
                                <td class="py-2 text-end dim">—</td>
                                <td class="py-2 text-end">
                                    <div class="qty-cell mono">
                                        <span class="qty-sign">&nbsp;</span>
                                        <span class="qty-num">
                                            @if ($sumIn > 0)
                                                <span class="qty-in">+ {{ $numf($sumIn, 2) }}</span>
                                            @endif
                                            @if ($sumOut > 0)
                                                <span class="qty-out ms-2">− {{ $numf($sumOut, 2) }}</span>
                                            @endif
                                            @if ($sumIn == 0 && $sumOut == 0)
                                                <span class="qty-zero">0,00</span>
                                            @endif
                                        </span>
                                        <span class="qty-unit"></span>
                                    </div>
                                </td>
                                <td class="py-2 text-end mono">{{ $netDay >= 0 ? '+' : '−' }} {{ $numf(abs($netDay), 2) }}
                                </td>
                                <td class="py-2 text-end mono"><span class="val-num">{{ $sumValNet >= 0 ? '+' : '−' }}
                                        {{ $idr(abs($sumValNet)) }}</span></td>
                            </tr>

                            {{-- ROWS --}}
                            @foreach ($items as $row)
                                @php
                                    $qIn = (float) ($row->qty_in ?? 0);
                                    $qOut = (float) ($row->qty_out ?? 0);
                                    $net = $qIn - $qOut;

                                    $uc = (float) ($row->unit_cost ?? ($row->lot->unit_cost ?? 0));
                                    $val = $qIn > 0 ? $qIn * $uc : ($qOut > 0 ? -$qOut * $uc : 0);

                                    // tampilkan satu sign dominan agar tidak “lompat”: OUT prioritas
                                    $sign = $qOut > 0 ? '−' : ($qIn > 0 ? '+' : ' ');
                                    $num = $qOut > 0 ? $qOut : ($qIn > 0 ? $qIn : 0);
                                    $tone = $qOut > 0 ? 'qty-out' : ($qIn > 0 ? 'qty-in' : 'qty-zero');

                                    $itemCode = $row->item_code ?? ($row->lot->item->code ?? '—');
                                    $href = route('inventory.mutations.show', $row->id);
                                @endphp
                                <tr class="row-link" data-href="{{ $href }}">
                                    <td class="mono dim">{{ \Carbon\Carbon::parse($row->date)->format('H:i') }}</td>
                                    <td><span class="badge rounded-pill text-bg-light mono">{{ $row->type ?? '—' }}</span>
                                    </td>
                                    <td class="mono">{{ $itemCode }}</td>
                                    <td class="text-end mono">{{ $idr($uc) }}</td>

                                    {{-- QTY (sign | number | unit) --}}
                                    <td class="text-end">
                                        <div class="qty-cell mono">
                                            <span class="qty-sign {{ $tone }}">{{ $sign }}</span>
                                            <span class="qty-num {{ $tone }}">{{ $numf($num, 2) }}</span>
                                            <span class="qty-unit">{{ $row->unit ?? '' }}</span>
                                        </div>
                                    </td>

                                    <td class="text-end mono">{{ $net >= 0 ? '+' : '−' }} {{ $numf(abs($net), 2) }}</td>

                                    <td class="text-end mono">
                                        @if ($val > 0)
                                            <span class="val-num">{{ $idr($val) }}</span>
                                        @elseif ($val < 0)
                                            <span class="val-num">− {{ $idr(abs($val)) }}</span>
                                        @else
                                            <span class="val-num dim">Rp 0</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="text-center dim py-4">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (!empty($rows))
                <div class="card-body">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Auto-submit filter & row click --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('mutFilter');
            if (form) {
                // autosubmit untuk select & tanggal
                form.querySelectorAll('select[name], input[type="date"][name]').forEach(el => {
                    el.addEventListener('change', () => form.requestSubmit());
                });
                // input teks hanya submit saat Enter
                const q = form.querySelector('input[name="q"]');
                if (q) q.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        form.requestSubmit();
                    }
                });
            }

            // baris klik → ke halaman show
            document.querySelectorAll('tr.row-link').forEach(tr => {
                tr.addEventListener('click', () => {
                    const href = tr.getAttribute('data-href');
                    if (href) window.location.href = href;
                });
            });
        });
    </script>
@endsection
