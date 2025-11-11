{{-- Breakdown stok per gudang (ikut theme global) --}}
@php
    $fmtQty = fn($v) => number_format((float) $v, 2, ',', '.');
    $tone = function ($v) {
        if ($v < 0) {
            return 'qty-neg';
        }
        if ($v == 0) {
            return 'qty-zero';
        }
        if ($v <= 5) {
            return 'qty-low';
        }
        return 'qty-ok';
    };
@endphp

<style>
    :root {
        /* turunan dari layout */
        --bdk-bg: var(--card);
        --bdk-ink: var(--fg);
        --bdk-line: var(--line);
        --bdk-muted: var(--muted);
        --bdk-head-bg: color-mix(in srgb, var(--brand) 8%, var(--bdk-bg) 92%);
        --bdk-head-fg: color-mix(in srgb, var(--fg) 80%, var(--brand) 20%);
        --bdk-hover: color-mix(in srgb, var(--brand) 6%, var(--bdk-bg) 94%);
        --bdk-tint: color-mix(in srgb, var(--brand) 5%, var(--bdk-bg) 95%);

        /* tone qty adaptif */
        --qty-ok: color-mix(in srgb, #0ea5e9 75%, var(--bdk-ink) 25%);
        --qty-low: color-mix(in srgb, #1d4ed8 75%, var(--bdk-ink) 25%);
        --qty-zero: color-mix(in srgb, var(--bdk-ink) 75%, #94a3b8 25%);
        --qty-neg: color-mix(in srgb, #ef4444 85%, var(--bdk-ink) 15%);
    }

    .bdk {
        border: 1px solid var(--bdk-line);
        border-radius: 12px;
        overflow: hidden;
        background: var(--bdk-bg);
        box-shadow: 0 1px 0 color-mix(in srgb, var(--bdk-ink) 6%, transparent 94%),
            0 8px 24px color-mix(in srgb, var(--bdk-ink) 4%, transparent 96%);
    }

    .bdk-hd {
        padding: .75rem 1rem;
        border-bottom: 1px solid var(--bdk-line);
        background: var(--bdk-head-bg);
    }

    .bdk-ttl {
        font-weight: 700;
        letter-spacing: .01em;
        font-size: .95rem;
        color: var(--bdk-head-fg);
    }

    .bdk-table {
        width: 100%;
        border-collapse: collapse
    }

    .bdk-table th,
    .bdk-table td {
        padding: .65rem .9rem;
        border-bottom: 1px solid var(--bdk-line);
        vertical-align: middle;
        color: var(--bdk-ink);
        background: var(--bdk-bg);
    }

    .bdk-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        background: var(--bdk-tint);
        color: var(--bdk-head-fg);
    }

    .bdk-table tbody tr:hover {
        background: var(--bdk-hover);
        transition: background .15s ease
    }

    .mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, Menlo, Consolas, monospace
    }

    /* Tone qty */
    .qty-ok {
        color: var(--qty-ok);
        font-weight: 700
    }

    .qty-low {
        color: var(--qty-low);
        font-weight: 700
    }

    .qty-zero {
        color: var(--qty-zero);
        font-weight: 700
    }

    .qty-neg {
        color: var(--qty-neg);
        font-weight: 700
    }

    .badge-kosong {
        border: 1px solid var(--bdk-ink);
        color: var(--bdk-ink);
        background: transparent;
        border-radius: 999px;
        padding: .18rem .55rem;
        font-size: .72rem;
        font-weight: 700;
        margin-left: .4rem
    }

    .btn-detail {
        font-size: .75rem;
        padding: .28rem .65rem;
        border: 1px solid var(--bdk-ink);
        border-radius: 999px;
        color: var(--bdk-ink);
        background: transparent;
        text-decoration: none;
        transition: all .15s ease
    }

    .btn-detail:hover {
        background: color-mix(in srgb, var(--brand) 10%, transparent 90%)
    }

    @media (max-width:576px) {
        .bdk-hd {
            padding: .6rem .8rem
        }

        .bdk-table th,
        .bdk-table td {
            padding: .5rem .7rem
        }

        .bdk-ttl {
            font-size: .9rem
        }

        .btn-detail {
            padding: .22rem .5rem;
            font-size: .7rem
        }
    }
</style>

<div class="row g-3">

    {{-- Kontrakan --}}
    @if ($kontrakan->count())
        <div class="col-12">
            <div class="bdk">
                <div class="bdk-hd">
                    <div class="bdk-ttl">Kontrakan</div>
                </div>
                <div class="table-responsive">
                    <table class="bdk-table">
                        <thead>
                            <tr>
                                <th>Gudang</th>
                                <th class="text-end">Qty</th>
                                <th>Unit</th>
                                <th class="text-nowrap">Updated</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kontrakan as $r)
                                @php
                                    $updated = $r->last_updated
                                        ? \Carbon\Carbon::parse($r->last_updated)->format('Y-m-d H:i')
                                        : '-';
                                @endphp
                                <tr>
                                    <td class="fw-semibold text-nowrap">{{ $r->wh_code }} — {{ $r->wh_name }}</td>
                                    <td class="text-end mono {{ $tone($r->qty) }}">
                                        {{ $fmtQty($r->qty) }}
                                        @if ((float) $r->qty === 0.0)
                                            <span class="badge-kosong">KOSONG</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $r->unit }}</td>
                                    <td class="small text-nowrap">{{ $updated }}</td>
                                    <td class="text-end">
                                        <a class="btn-detail"
                                            aria-label="Lihat mutasi item {{ $itemCode }} di gudang {{ $r->wh_name }}"
                                            href="{{ route('inventory.mutations.index', ['item_code' => $itemCode, 'warehouse' => $r->wh_id]) }}">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Gudang Lain --}}
    <div class="col-12">
        <div class="bdk">
            <div class="bdk-hd">
                <div class="bdk-ttl">Gudang Lain</div>
            </div>
            <div class="table-responsive">
                <table class="bdk-table mb-0">
                    <thead>
                        <tr>
                            <th>Gudang</th>
                            <th class="text-end">Qty</th>
                            <th>Unit</th>
                            <th class="text-nowrap">Updated</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($others as $r)
                            @php
                                $updated = $r->last_updated
                                    ? \Carbon\Carbon::parse($r->last_updated)->format('Y-m-d H:i')
                                    : '-';
                            @endphp
                            <tr>
                                <td class="fw-semibold text-nowrap">{{ $r->wh_code }} — {{ $r->wh_name }}</td>
                                <td class="text-end mono {{ $tone($r->qty) }}">
                                    {{ $fmtQty($r->qty) }}
                                    @if ((float) $r->qty === 0.0)
                                        <span class="badge-kosong">KOSONG</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $r->unit }}</td>
                                <td class="small text-nowrap">{{ $updated }}</td>
                                <td class="text-end">
                                    <a class="btn-detail"
                                        aria-label="Lihat mutasi item {{ $itemCode }} di gudang {{ $r->wh_name }}"
                                        href="{{ route('inventory.mutations.index', ['item_code' => $itemCode, 'warehouse' => $r->wh_id]) }}">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3">
                                    <span class="badge-kosong">KOSONG</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
