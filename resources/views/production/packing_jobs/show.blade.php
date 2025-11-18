@extends('layouts.app')

@section('title', 'Produksi • Packing • ' . $packingJob->code)

@section('content')
    <div class="page-wrap" style="max-width: 1100px; margin-inline: auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">
                Packing {{ $packingJob->code }}
            </h1>

            <div class="d-flex gap-2">
                <a href="{{ route('production.packing_jobs.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>

                @if ($packingJob->isDraft())
                    <form action="{{ route('production.packing_jobs.post', $packingJob) }}" method="POST"
                        onsubmit="return confirm('Posting dokumen ini? Stok akan pindah dari WIP-FIN ke FG.');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-check2-circle"></i> Posting
                        </button>
                    </form>
                @else
                    <span class="badge bg-success align-self-center">SUDAH POSTED</span>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success mb-3">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger mb-3">
                {{ session('error') }}
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Kode</dt>
                    <dd class="col-sm-9">{{ $packingJob->code }}</dd>

                    <dt class="col-sm-3">Tanggal</dt>
                    <dd class="col-sm-9">{{ $packingJob->date?->format('d/m/Y') }}</dd>

                    <dt class="col-sm-3">Gudang</dt>
                    <dd class="col-sm-9">
                        Dari: <strong>{{ $packingJob->fromWarehouse->code ?? '-' }}</strong>
                        &rarr;
                        Ke: <strong>{{ $packingJob->toWarehouse->code ?? '-' }}</strong>
                    </dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">
                        @if ($packingJob->isPosted())
                            <span class="badge bg-success">POSTED</span>
                        @else
                            <span class="badge bg-secondary">DRAFT</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Catatan</dt>
                    <dd class="col-sm-9">{{ $packingJob->notes ?: '-' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Detail Bundle yang Dipacking
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kode Setor Jahit</th>
                                <th>Item</th>
                                <th>LOT</th>
                                <th class="text-end">Qty OK</th>
                                <th class="text-end">Sudah Packing</th>
                                <th class="text-end">Qty Packing (Dokumen Ini)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($packingJob->lines as $line)
                                @php
                                    $sewLine = $line->sewingReturnLine;
                                    $qtyOk = (float) ($sewLine->qty_ok ?? 0);
                                    $packedQty = (float) ($sewLine->packed_qty ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        {{ $sewLine->sewingReturn->code ?? '-' }}<br>
                                        <small class="text-muted">
                                            {{ $sewLine->sewingReturn->date?->format('d/m/Y') }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $line->item_code }}</div>
                                        <div class="small text-muted">{{ $sewLine->item->name ?? '-' }}</div>
                                    </td>
                                    <td class="mono">
                                        {{ $sewLine->lot->code ?? '-' }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($qtyOk, 2) }}
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ number_format($packedQty, 2) }}
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($line->qty, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        Tidak ada detail.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($packingJob->lines->count())
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">TOTAL</th>
                                    <th class="text-end">
                                        {{ number_format($packingJob->total_qty, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
