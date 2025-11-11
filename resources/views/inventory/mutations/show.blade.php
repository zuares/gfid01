@extends('layouts.app')
@section('title', 'Inventory • Mutation Detail')

@push('head')
    <style>
        :root {
            --line: color-mix(in srgb, var(--bs-border-color) 78%, var(--bs-body-bg) 22%);
            --head-bg: color-mix(in srgb, var(--bs-primary) 7%, var(--bs-body-bg) 93%);
            --head-fg: color-mix(in srgb, var(--bs-primary-text-emphasis) 60%, var(--bs-body-color) 40%);
            --muted: var(--bs-secondary-color);
            --in: var(--bs-teal);
            --out: var(--bs-orange);
            --card-bg: color-mix(in srgb, var(--bs-body-bg) 96%, var(--bs-primary) 4%);
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--card-bg);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
            overflow: hidden
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-variant-numeric: tabular-nums
        }

        .dim {
            color: var(--muted)
        }

        .section-title {
            background: var(--head-bg);
            color: var(--head-fg);
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: .55rem .85rem;
            border-bottom: 1px solid var(--line);
        }

        .kv {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: .4rem .75rem;
            padding: 1rem 1.25rem
        }

        .kv .k {
            color: var(--muted);
            font-size: .85rem
        }

        .kv .v {
            font-weight: 500
        }

        .pill {
            border-radius: 999px;
            font-size: .82rem;
            padding: .25rem .7rem;
            font-weight: 600;
            border: 1px solid var(--line)
        }

        .pill.in {
            background: color-mix(in srgb, var(--in) 10%, var(--bs-body-bg) 90%);
            color: var(--in)
        }

        .pill.out {
            background: color-mix(in srgb, var(--out) 10%, var(--bs-body-bg) 90%);
            color: var(--out)
        }

        .pill.net {
            background: var(--head-bg);
            color: var(--head-fg)
        }

        .table th,
        .table td {
            vertical-align: middle
        }

        .table thead th {
            background: var(--head-bg);
            color: var(--head-fg);
            font-size: .78rem;
            text-transform: uppercase
        }

        .qty-in {
            color: var(--in)
        }

        .qty-out {
            color: var(--out)
        }

        /* Sticky Back FAB */
        .fab-back {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 10;
            border-radius: 999px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
        }

        /* Next/Prev group */
        .nav-pair .btn {
            border-color: var(--line)
        }

        @media (max-width: 768px) {
            .kv {
                grid-template-columns: 120px 1fr;
                padding: .85rem
            }
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0">Inventory • Mutation Detail</h3>

            <div class="nav-pair d-flex gap-2">
                {{-- PREV --}}
                @if ($prev)
                    <a href="{{ route('inventory.mutations.show', $prev->id) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-chevron-left me-1"></i> Prev
                    </a>
                @else
                    <button class="btn btn-outline-secondary" disabled><i class="bi bi-chevron-left me-1"></i> Prev</button>
                @endif

                {{-- NEXT --}}
                @if ($next)
                    <a href="{{ route('inventory.mutations.show', $next->id) }}" class="btn btn-outline-secondary">
                        Next <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                @else
                    <button class="btn btn-outline-secondary" disabled>Next <i
                            class="bi bi-chevron-right ms-1"></i></button>
                @endif
            </div>
        </div>

        {{-- RINGKASAN --}}
        <div class="card mb-3">
            <div class="section-title">Ringkasan Mutasi</div>
            <div class="p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <div class="fw-semibold mono">{{ $mutation->type }}</div>
                        <div class="dim">{{ optional($mutation->date)->format('d/m/Y') }}</div>
                    </div>

                    @php
                        $qIn = (float) ($mutation->qty_in ?? 0);
                        $qOut = (float) ($mutation->qty_out ?? 0);
                        $net = $qIn - $qOut;
                        $uc = (float) ($mutation->lot->unit_cost ?? 0);
                        $val = $qIn > 0 ? $qIn * $uc : ($qOut > 0 ? -$qOut * $uc : 0);
                    @endphp

                    <div class="d-flex flex-wrap gap-2">
                        <span class="pill in">+ {{ numf($qIn, 2) }}</span>
                        <span class="pill out">− {{ numf($qOut, 2) }}</span>
                        <span class="pill net">{{ $net >= 0 ? '+' : '−' }} {{ numf(abs($net), 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL INFO --}}
        <div class="card mb-3">
            <div class="section-title">Detail Informasi</div>
            <div class="kv">
                <div class="k">Gudang</div>
                <div class="v">{{ $mutation->warehouse?->name ?? '—' }}</div>
                <div class="k">Item</div>
                <div class="v mono">{{ $mutation->lot?->item?->code ?? '—' }}</div>
                <div class="k">LOT</div>
                <div class="v mono">{{ $mutation->lot?->code ?? '—' }}</div>
                <div class="k">Unit</div>
                <div class="v">{{ $mutation->unit ?? ($mutation->lot?->unit ?? 'pcs') }}</div>
                <div class="k">Harga Satuan</div>
                <div class="v mono">{{ idr($uc, 0) }}</div>
                <div class="k">Nilai Total</div>
                <div class="v mono">
                    @if ($val > 0)
                        {{ idr($val, 0) }}
                    @elseif($val < 0)
                        − {{ idr(abs($val), 0) }}
                    @else
                        <span class="dim">Rp 0</span>
                    @endif
                </div>
                <div class="k">Ref</div>
                <div class="v mono">{{ $mutation->ref_code ?? '—' }}</div>
                <div class="k">Catatan</div>
                <div class="v">{{ $mutation->note ?? '—' }}</div>
            </div>
        </div>

        {{-- SUMBER (dinamis: purchase / transfer) --}}
        @if ($purchaseSource || $transferPartner)
            <div class="card mb-3">
                <div class="section-title">Sumber</div>
                <div class="p-3 d-flex flex-column gap-2">
                    @if ($purchaseSource)
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="dim">Pembelian</div>
                            {{-- Ganti route sesuai nama rute show invoice kamu --}}
                            <a class="btn btn-sm btn-outline-primary"
                                href="{{ route('purchasing.invoices.show', $purchaseSource['invoice_id'] ?? null) }}">
                                Lihat Invoice {{ $purchaseSource['invoice_code'] ?? '' }}
                            </a>
                        </div>
                    @endif

                    @if ($transferPartner)
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="dim">Transfer</div>
                            <div class="mono">
                                {{ $transferPartner['from'] ?? '—' }} <span class="dim">→</span>
                                {{ $transferPartner['to'] ?? '—' }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- RIWAYAT LOT --}}
        @isset($lotHistory)
            <div class="card">
                <div class="section-title">Riwayat LOT yang Sama</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th style="width:100px">Tanggal</th>
                                <th style="width:140px">Tipe</th>
                                <th>Gudang</th>
                                <th class="text-end" style="width:150px">Qty (IN / OUT)</th>
                                <th class="text-end" style="width:110px">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lotHistory as $h)
                                @php
                                    $hIn = (float) ($h->qty_in ?? 0);
                                    $hOut = (float) ($h->qty_out ?? 0);
                                    $hNet = $hIn - $hOut;
                                @endphp
                                <tr>
                                    <td class="mono">{{ optional($h->date)->format('d/m/Y') }}</td>
                                    <td><span class="badge rounded-pill text-bg-light mono">{{ $h->type }}</span></td>
                                    <td>{{ $h->warehouse?->name ?? '—' }}</td>
                                    <td class="text-end mono"><span class="qty-in">+ {{ numf($hIn, 2) }}</span> <span
                                            class="dim">/</span> <span class="qty-out">− {{ numf($hOut, 2) }}</span></td>
                                    <td class="text-end mono">{{ $hNet >= 0 ? '+' : '−' }} {{ numf(abs($hNet), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center dim py-3">Tidak ada riwayat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endisset
    </div>

    {{-- Sticky Back FAB --}}
    <a class="btn btn-primary fab-back" href="{{ route('inventory.mutations.index') }}" title="Kembali ke daftar">
        <i class="bi bi-arrow-left"></i>
    </a>
@endsection
