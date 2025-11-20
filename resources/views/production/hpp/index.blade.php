@extends('layouts.app')
@section('title', 'Produksi • HPP Final')

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
    <div class="page-wrap py-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">HPP Final per Item</h4>
                <div class="small text-muted">
                    Ringkasan HPP = Raw Material (LOT) + Cutting (LOT) + Sewing + Finishing/Packing.
                </div>
            </div>

            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body small">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Cari Item</label>
                        <input type="text" name="q" value="{{ $q }}"
                            class="form-control form-control-sm" placeholder="Kode / nama item…">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Jenis Item</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="finished" @selected($type === 'finished')>Barang Jadi (finished)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search me-1"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABEL HPP --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th class="text-end">Qty Dasar</th>
                                <th class="text-end">HPP / pcs</th>
                                <th class="text-end">Raw</th>
                                <th class="text-end">Cutting</th>
                                <th class="text-end">Sewing</th>
                                <th class="text-end">Finishing</th>
                                <th class="text-end">Packing</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $idx => $item)
                                @php
                                    $sum = $summaries[$item->id] ?? null;
                                    $stages = $sum['stages'] ?? [];

                                    $qtyBase = (float) ($sum['total_qty_base'] ?? 0);
                                    $hpp = (float) ($sum['cost_per_unit'] ?? 0);

                                    $cpuRaw = (float) ($stages['raw_material']['cost_per_unit'] ?? 0);
                                    $cpuCut = (float) ($stages['cutting']['cost_per_unit'] ?? 0);
                                    $cpuSew = (float) ($stages['sewing']['cost_per_unit'] ?? 0);
                                    $cpuFin = (float) ($stages['finishing']['cost_per_unit'] ?? 0);
                                    $cpuPack = (float) ($stages['packing']['cost_per_unit'] ?? 0);
                                @endphp
                                <tr>
                                    <td class="mono">
                                        {{ $items->firstItem() + $idx }}
                                    </td>
                                    <td class="mono">
                                        {{ $item->code }}
                                    </td>
                                    <td>
                                        {{ $item->name }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($qtyBase, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono fw-semibold">
                                        {{ number_format($hpp, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $cpuRaw > 0 ? number_format($cpuRaw, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $cpuCut > 0 ? number_format($cpuCut, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $cpuSew > 0 ? number_format($cpuSew, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $cpuFin > 0 ? number_format($cpuFin, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $cpuPack > 0 ? number_format($cpuPack, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('production.hpp.items.show', $item->id) }}"
                                            class="btn btn-outline-secondary btn-xs btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        Tidak ada item yang ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($items->hasPages())
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-between align-items-center small">
                        <div class="muted">
                            Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }}
                            dari {{ $items->total() }} item
                        </div>
                        <div>
                            {{ $items->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
