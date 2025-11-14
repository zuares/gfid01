@extends('layouts.app')

@section('title', 'Produksi • Finishing • Siap Finishing')

@push('head')
    <style>
        .finishing-page .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .finishing-page .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }
    </style>
@endpush

@section('content')
    <div class="finishing-page container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Siap Finishing / FG</h1>
                <div class="text-muted small">
                    WIP hasil sewing yang masih tersedia dan siap difinishing / jadi barang jadi.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode Item</th>
                                <th>Nama Item</th>
                                <th class="text-end">Qty WIP Sewing</th>
                                <th>Gudang</th>
                                <th>Batch Sewing Asal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($wips as $wip)
                                <tr>
                                    <td class="mono">
                                        {{ $wip->item_code }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $wip->item?->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($wip->qty, 2) }} pcs
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $wip->warehouse?->code ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $wip->warehouse?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($wip->productionBatch)
                                            <div class="mono">
                                                {{ $wip->productionBatch->code }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ $wip->productionBatch->date?->format('d M Y') }}
                                            </div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('finishing.create', $wip->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Buat Batch Finishing
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada WIP sewing yang siap finishing.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-2">
                    {{ $wips->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
