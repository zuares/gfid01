@extends('layouts.app')

@section('title', 'Produksi • Finishing • Batch Baru')

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

        .small-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="finishing-page container-fluid">
        <div class="mb-3">
            <a href="{{ route('finishing.index') }}" class="btn btn-sm btn-outline-secondary">
                &larr; Kembali
            </a>
        </div>

        <div class="row g-3">
            {{-- LEFT: INFO WIP SEWING --}}
            <div class="col-12 col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="small-label mb-2">WIP Sewing</div>

                        <div class="mb-2">
                            <div class="small-label">Item</div>
                            <div class="fw-semibold">
                                {{ $wip->item_code }}
                            </div>
                            <div class="text-muted small">
                                {{ $wip->item?->name ?? '-' }}
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="small-label">Qty Tersedia</div>
                            <div class="mono fw-semibold">
                                {{ number_format($wip->qty, 2) }} pcs
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="small-label">Gudang / Lokasi</div>
                            <div class="fw-semibold">
                                {{ $wip->warehouse?->code ?? '-' }}
                            </div>
                            <div class="text-muted small">
                                {{ $wip->warehouse?->name ?? '' }}
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="small-label">Batch Sewing Asal</div>
                            @if ($wip->productionBatch)
                                <div class="mono fw-semibold">
                                    {{ $wip->productionBatch->code }}
                                </div>
                                <div class="text-muted small">
                                    {{ $wip->productionBatch->date?->format('d M Y') }}
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </div>

                        <div class="mb-2">
                            <div class="small-label">LOT Asal Kain</div>
                            <div class="mono">
                                {{ $wip->sourceLot?->code ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: FORM BATCH FINISHING --}}
            <div class="col-12 col-lg-8">
                <form action="{{ route('finishing.store', $wip->id) }}" method="post">
                    @csrf

                    <div class="card">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Batch Finishing Baru</h2>

                            @if ($errors->any())
                                <div class="alert alert-danger small">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row g-3 mb-3">
                                <div class="col-6 col-md-4">
                                    <label class="form-label small-label d-block">Tanggal</label>
                                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                                        class="form-control mono" required>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small-label d-block">Operator Finishing</label>
                                    <input type="text" name="operator_code" value="{{ old('operator_code') }}"
                                        class="form-control mono" placeholder="Kode/operator">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6 col-md-4">
                                    <label class="form-label small-label d-block">Qty difinishing</label>
                                    <input type="number" name="qty_to_finish"
                                        value="{{ old('qty_to_finish', (int) $wip->qty) }}" class="form-control mono"
                                        min="1" max="{{ (int) $wip->qty }}" step="1" required>
                                    <div class="text-muted small mt-1">
                                        Maksimal: {{ number_format($wip->qty, 0) }} pcs
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small-label d-block">Qty FG (OK)</label>
                                    <input type="number" name="fg_qty" value="{{ old('fg_qty', (int) $wip->qty) }}"
                                        class="form-control mono" min="1" step="1" required>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small-label d-block">Reject (pcs)</label>
                                    <input type="number" name="reject_qty" value="{{ old('reject_qty', 0) }}"
                                        class="form-control mono" min="0" step="1">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small-label d-block">Catatan</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Batch Finishing
                                </button>
                            </div>

                            <div class="text-muted small mt-2">
                                Setelah disimpan:
                                <ul class="mb-0">
                                    <li>Stok WIP sewing akan berkurang.</li>
                                    <li>Dibuat batch <strong>finishing</strong> (production_batches).</li>
                                    <li>Dibuat stok <strong>Finished Goods</strong> pada tabel <code>finished_goods</code>.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
