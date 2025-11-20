@extends('layouts.app')
@section('title', 'HPP • LOT ' . $lot->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
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

        .muted {
            color: var(--muted);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $stages = $summary['stages'] ?? [];
        $qtyBase = (float) ($summary['total_qty_base'] ?? 0);
        $amountTotal = (float) ($summary['total_amount'] ?? 0);
        $hpp = (float) ($summary['cost_per_unit'] ?? 0);

        $stageLabels = [
            'raw_material' => 'Raw Material (kain LOT ini)',
            'cutting' => 'Cutting (hasil dari LOT ini)',
        ];
    @endphp

    <div class="page-wrap py-3">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">
                    HPP LOT • <span class="mono">{{ $lot->code }}</span>
                </h4>
                <div class="small text-muted">
                    Biaya kain & cutting yang melekat ke LOT ini.
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('production.hpp.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke HPP
                </a>
            </div>
        </div>

        {{-- INFO LOT --}}
        <div class="card mb-3">
            <div class="card-body small">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">Kode LOT</div>
                        <div class="fw-semibold mono">{{ $lot->code }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Item Kain</div>
                        <div class="fw-semibold">
                            @if ($lot->item)
                                <span class="mono">{{ $lot->item->code }}</span> — {{ $lot->item->name }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Unit LOT</div>
                        <div class="fw-semibold mono">
                            {{ $lot->unit ?? ($summary['unit'] ?? 'kg') }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Tgl LOT</div>
                        <div class="fw-semibold mono">
                            {{ optional($lot->date)->format('d-m-Y') ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SUMMARY HPP LOT --}}
        <div class="row g-3 mb-3 small">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Qty Dasar</div>
                        <div class="h5 mono mb-0">
                            {{ number_format($qtyBase, 2, ',', '.') }}
                        </div>
                        <div class="muted mt-1">
                            Biasanya = total pcs hasil cutting yang diambil dari LOT ini.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Total Biaya LOT</div>
                        <div class="h5 mono mb-0">
                            {{ number_format($amountTotal, 2, ',', '.') }}
                        </div>
                        <div class="muted mt-1">
                            Biaya kain + biaya cutting yang dialokasikan ke LOT ini.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Biaya / pcs (dari LOT ini)</div>
                        <div class="h5 mono mb-0">
                            {{ number_format($hpp, 2, ',', '.') }}
                        </div>
                        <div class="muted mt-1">
                            Cost per pcs hasil (dasar HPP WIP Cutting / Sewing).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL PER STAGE (LOT) --}}
        <div class="card mb-3">
            <div class="card-header small fw-semibold">
                Breakdown Biaya LOT per Tahap
            </div>
            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Tahap</th>
                                <th class="text-end">Qty Dasar</th>
                                <th class="text-end">Total Biaya</th>
                                <th class="text-end">Cost / Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $row = 1; @endphp
                            @forelse ($stageLabels as $key => $label)
                                @php
                                    $st = $stages[$key] ?? null;
                                    $qty = (float) ($st['qty_base'] ?? 0);
                                    $amount = (float) ($st['amount'] ?? 0);
                                    $cpu = (float) ($st['cost_per_unit'] ?? 0);
                                @endphp
                                <tr @class(['text-muted' => $qty <= 0 && $amount <= 0])>
                                    <td class="mono">{{ $row++ }}</td>
                                    <td>{{ $label }}</td>
                                    <td class="text-end mono">
                                        {{ $qty > 0 ? number_format($qty, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $amount > 0 ? number_format($amount, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $cpu > 0 ? number_format($cpu, 2, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada data biaya untuk LOT ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- STOK LOT PER GUDANG --}}
        <div class="card mb-4">
            <div class="card-header small fw-semibold">
                Posisi Stok LOT per Gudang
            </div>
            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Gudang</th>
                                <th class="text-end">Qty</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stocks as $idx => $s)
                                <tr>
                                    <td class="mono">{{ $idx + 1 }}</td>
                                    <td>
                                        @if ($s->warehouse)
                                            <span class="mono">{{ $s->warehouse->code }}</span> —
                                            {{ $s->warehouse->name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($s->qty, 2, ',', '.') }}
                                    </td>
                                    <td class="mono">
                                        {{ $s->unit }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada stok LOT ini di gudang manapun.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="muted small">
            Stok di atas diambil dari <span class="mono">inventory_stocks</span> (update via
            <span class="mono">InventoryService</span>).
        </div>
    </div>
@endsection
