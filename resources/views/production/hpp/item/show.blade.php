@extends('layouts.app')
@section('title', 'HPP • Item ' . $item->code)

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
            'raw_material' => 'Raw Material (kain / LOT)',
            'cutting' => 'Cutting',
            'sewing' => 'Sewing',
            'finishing' => 'Finishing',
            'packing' => 'Packing',
        ];
    @endphp

    <div class="page-wrap py-3">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">
                    HPP Item • <span class="mono">{{ $item->code }}</span>
                </h4>
                <div class="small text-muted">
                    Ringkasan biaya per tahap produksi untuk item ini.
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('production.hpp.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke HPP
                </a>
            </div>
        </div>

        {{-- INFO ITEM --}}
        <div class="card mb-3">
            <div class="card-body small">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">Kode Item</div>
                        <div class="fw-semibold mono">{{ $item->code }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Nama Item</div>
                        <div class="fw-semibold">{{ $item->name }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Tipe</div>
                        <div class="fw-semibold text-uppercase">
                            {{ $item->type ?? '-' }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Unit</div>
                        <div class="fw-semibold mono">
                            {{ $summary['unit'] ?? ($item->unit ?? 'pcs') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SUMMARY HPP --}}
        <div class="row g-3 mb-3 small">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Qty Dasar Akumulasi</div>
                        <div class="h5 mono mb-0">
                            {{ number_format($qtyBase, 2, ',', '.') }}
                        </div>
                        <div class="muted mt-1">
                            Total qty yang dipakai sebagai basis pembagian HPP.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Total Biaya Produksi</div>
                        <div class="h5 mono mb-0">
                            {{ number_format($amountTotal, 2, ',', '.') }}
                        </div>
                        <div class="muted mt-1">
                            Gabungan semua tahap: kain, cutting, sewing, finishing, packing.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">HPP per pcs</div>
                        <div class="h5 mono mb-0">
                            {{ number_format($hpp, 2, ',', '.') }}
                        </div>
                        <div class="muted mt-1">
                            Total biaya / {{ $summary['unit'] ?? 'pcs' }} (final).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL PER STAGE --}}
        <div class="card mb-4">
            <div class="card-header small fw-semibold">
                Breakdown Biaya per Tahap
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
                                        Belum ada data biaya untuk item ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- CATATAN --}}
        <div class="muted small">
            Catatan: angka di atas diambil dari tabel <span class="mono">production_costs</span>.
            Kalau ada proses yang belum tercatat (misal finishing / packing), baris tahap tersebut akan nol.
        </div>
    </div>
@endsection
