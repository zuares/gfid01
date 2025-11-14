{{-- resources/views/production/sewing/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing • Siap Dijahit')

@push('head')
    <style>
        .sewing-page {
            max-width: 1080px;
            margin-inline: auto;
        }

        .sewing-page .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .sewing-page .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .badge-stage {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .75rem;
            border: 1px solid var(--line);
        }

        .badge-stage-cutting {
            background: rgba(59, 130, 246, .08);
        }

        .table-sticky thead th {
            position: sticky;
            top: 0;
            background: var(--panel);
            z-index: 1;
        }

        @media (max-width: 767.98px) {
            .table-responsive {
                font-size: .875rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="sewing-page page-wrap">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">Sewing • Siap Dijahit</h1>
                <div class="text-muted small">
                    Daftar stok WIP hasil cutting yang masih tersedia dan siap diproses jahit.
                </div>
            </div>
        </div>

        {{-- Flash message --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show small" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Main card --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 table-sticky">
                        <thead>
                            <tr>
                                <th>Kode Item</th>
                                <th>Nama Item</th>
                                <th class="text-end">Qty WIP (pcs)</th>
                                <th>Gudang</th>
                                <th>Batch Cutting Asal</th>
                                <th>Stage</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($wips as $wip)
                                <tr>
                                    {{-- Kode item --}}
                                    <td class="mono">
                                        {{ $wip->item_code }}
                                    </td>

                                    {{-- Nama item --}}
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $wip->item?->name ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            Item ID: {{ $wip->item_id }}
                                        </div>
                                    </td>

                                    {{-- Qty WIP --}}
                                    <td class="text-end mono">
                                        {{ number_format($wip->qty, 2) }}
                                    </td>

                                    {{-- Gudang --}}
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $wip->warehouse?->code ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $wip->warehouse?->name ?? '' }}
                                        </div>
                                    </td>

                                    {{-- Batch Cutting Asal --}}
                                    <td>
                                        @if ($wip->productionBatch)
                                            <div class="mono fw-semibold">
                                                {{ $wip->productionBatch->code }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ optional($wip->productionBatch->date)->format('d M Y') }}
                                            </div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>

                                    {{-- Stage --}}
                                    <td>
                                        @php
                                            $stageClass = $wip->stage === 'cutting' ? 'badge-stage-cutting' : '';
                                        @endphp
                                        <span class="badge-stage {{ $stageClass }}">
                                            {{ strtoupper($wip->stage) }}
                                        </span>
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('sewing.create', $wip->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Buat Batch Jahit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada stok WIP hasil cutting yang siap dijahit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="p-2">
                    {{ $wips->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
