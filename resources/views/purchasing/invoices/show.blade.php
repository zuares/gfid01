@extends('layouts.app')
@section('title', 'Purchasing • ' . $invoice->code)

@push('head')
    <style>
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace
        }

        .table thead th {
            font-size: .85rem;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase
        }

        .badge {
            border: 1px solid var(--line)
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Detail Pembelian</h1>
            <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- HEADER --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 justify-content-between">
                    <div>
                        <div class="text-muted small">Kode</div>
                        <div class="mono fw-semibold">{{ $invoice->code }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Tanggal</div>
                        <div class="mono">{{ $invoice->date?->format('Y-m-d') }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Supplier</div>
                        <div class="fw-semibold">{{ $invoice->supplier?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Gudang</div>
                        <div class="fw-semibold">{{ $invoice->warehouse?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Status</div>
                        <span
                            class="badge rounded-pill text-bg-{{ $invoice->status === 'posted' ? 'success' : 'secondary' }}">
                            {{ strtoupper($invoice->status) }}
                        </span>
                    </div>
                </div>

                @if ($invoice->note)
                    <hr>
                    <div class="small text-muted mb-1">Catatan</div>
                    <div>{{ $invoice->note }}</div>
                @endif
            </div>
        </div>

        {{-- LINES --}}
        <div class="card mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th>Unit</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $i => $ln)
                                <tr>
                                    <td class="mono">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $ln['item_code'] }}</div>
                                        <div class="small text-muted">{{ $ln['item_name'] ?? '—' }}</div>
                                    </td>
                                    <td class="text-end mono">{{ numf($ln['qty'], 2) }}</td>
                                    <td class="mono">{{ $ln['unit'] }}</td>
                                    <td class="text-end mono">{{ idr($ln['unit_cost'], 2) }}</td>
                                    <td class="text-end mono">{{ idr($ln['subtotal'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada baris.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">Grand Total</th>
                                <th class="text-end mono">{{ idr($grandTotal, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('purchasing.invoices.index') }}">
                <i class="bi bi-list"></i> Daftar
            </a>
        </div>
    </div>
@endsection
