@extends('layouts.app')

@section('content')
    <div class="container-fluid">

        {{-- BREADCRUMB / HEADER ATAS --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb breadcrumb-sm mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('production.sewing_picks.index') }}">Ambil Jahit</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $sewingPick->code }}
                        </li>
                    </ol>
                </nav>

                <h1 class="h4 mb-0">
                    <i class="bi bi-truck me-1"></i> Detail Ambil Jahit
                </h1>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('production.sewing_picks.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        {{-- INFO HEADER DOKUMEN --}}
        <div class="card mb-3">
            <div class="card-body">

                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Kode Dokumen</div>
                        <div class="fw-semibold">{{ $sewingPick->code }}</div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Tanggal</div>
                        <div>{{ $sewingPick->date }}</div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Status</div>
                        @php
                            $status = strtoupper($sewingPick->status);
                            $badge = 'secondary';
                            if ($sewingPick->status === 'posted') {
                                $badge = 'success';
                            }
                            if ($sewingPick->status === 'draft') {
                                $badge = 'warning';
                            }
                        @endphp
                        <span class="badge bg-{{ $badge }}">{{ $status }}</span>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Dibuat Oleh</div>
                        <div>{{ optional($sewingPick->creator)->name ?? '-' }}</div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Dari Gudang</div>
                        @if ($sewingPick->fromWarehouse)
                            <div class="fw-semibold">{{ $sewingPick->fromWarehouse->code }}</div>
                            <div class="small text-muted">{{ $sewingPick->fromWarehouse->name }}</div>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Ke Gudang</div>
                        @if ($sewingPick->toWarehouse)
                            <div class="fw-semibold">{{ $sewingPick->toWarehouse->code }}</div>
                            <div class="small text-muted">{{ $sewingPick->toWarehouse->name }}</div>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Operator / Penjahit</div>
                        @if ($sewingPick->operator)
                            <div class="fw-semibold">{{ $sewingPick->operator->name }}</div>
                            <div class="small text-muted">
                                {{ $sewingPick->operator->code ?? 'ID: ' . $sewingPick->operator->id }}
                            </div>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>
                    <div class="col-12">
                        <div class="text-muted small mb-1">Catatan</div>
                        <div>{{ $sewingPick->notes ?: '-' }}</div>
                    </div>
                </div>

            </div>
        </div>

        {{-- DETAIL BARIS --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    Detail Bundle / Item yang Diambil
                </span>
            </div>

            @php
                $totalQty = $sewingPick->lines->sum('qty');
            @endphp

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Item</th>
                                <th>LOT</th>
                                <th class="text-end">Qty</th>
                                <th>Unit</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sewingPick->lines as $i => $line)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $line->item_code }}</div>
                                        <div class="small text-muted">ID: {{ $line->item_id }}</div>
                                    </td>
                                    <td>#{{ $line->lot_id }}</td>
                                    <td class="text-end">{{ number_format($line->qty, 2) }}</td>
                                    <td>{{ $line->unit }}</td>
                                    <td>{{ $line->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Tidak ada detail baris.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($sewingPick->lines->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">TOTAL</th>
                                    <th class="text-end">{{ number_format($totalQty, 2) }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
