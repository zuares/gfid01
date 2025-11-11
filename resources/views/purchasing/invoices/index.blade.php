@extends('layouts.app')
@section('title', 'Purchasing • Invoices')

@push('head')
    <style>
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace
        }

        .table thead th {
            position: sticky;
            top: 0;
            background: var(--card)
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0">Purchasing • Invoices</h5>
            <a href="{{ route('purchasing.invoices.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Invoice Baru
            </a>
        </div>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-12 col-md-3">
                <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control"
                    placeholder="Cari kode/supplier...">
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    <option value="">— Status —</option>
                    @foreach (['draft' => 'Draft', 'posted' => 'Posted'] as $k => $v)
                        <option value="{{ $k }}" @selected(($status ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="supplier" class="form-select">
                    <option value="">— Semua Supplier —</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(($supp ?? '') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <input type="text" name="range" value="{{ $range ?? '' }}" class="form-control"
                    placeholder="YYYY-MM-DD s/d YYYY-MM-DD">
            </div>
            <div class="col-12 col-md-1 d-grid">
                <button class="btn btn-outline-secondary">Filter</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="14%">Kode</th>
                            <th width="12%">Tanggal</th>
                            <th>Supplier</th>
                            <th width="14%" class="text-end">Total (IDR)</th>
                            <th width="10%">Status</th>
                            <th width="10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
                        @endphp
                        @forelse ($rows as $inv)
                            @php
                                $total = $inv->lines()->selectRaw('sum(qty*unit_cost) as t')->value('t') ?? 0;
                            @endphp
                            <tr>
                                <td class="mono">{{ $inv->code }}</td>
                                <td class="mono">{{ optional($inv->date)->format('Y-m-d') }}</td>
                                <td>{{ $inv->supplier?->name }}</td>
                                <td class="mono text-end">{{ $fmt($total) }}</td>
                                <td>
                                    <span
                                        class="badge {{ $inv->status === 'posted' ? 'bg-success' : 'bg-secondary' }}">{{ strtoupper($inv->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('purchasing.invoices.show', $inv) }}"
                                        class="btn btn-outline-primary btn-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-2">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
@endsection
