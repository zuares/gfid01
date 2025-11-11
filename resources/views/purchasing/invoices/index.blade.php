@extends('layouts.app')
@section('title', 'Purchasing • Invoices')

@push('head')
    <style>
        .table th,
        .table td {
            vertical-align: middle;
        }

        .badge-status {
            font-size: .75rem;
        }

        .btn-icon {
            padding: .25rem .5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0">Purchasing • Invoices</h3>
            <a class="btn btn-primary" href="{{ route('purchasing.invoices.create') }}">
                <i class="bi bi-plus-lg me-1"></i> Invoice Baru
            </a>
        </div>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-12 col-md-4">
                <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control"
                    placeholder="Cari kode/supplier...">
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- Status --</option>
                    <option value="draft" @selected(($status ?? '') === 'draft')>Draft</option>
                    <option value="posted" @selected(($status ?? '') === 'posted')>Posted</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="supplier" class="form-select">
                    <option value="">-- Semua Supplier --</option>
                    @foreach ($suppliers ?? [] as $s)
                        <option value="{{ $s->id }}" @selected(($supp ?? '') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-outline-secondary w-100">Filter</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:130px">Tanggal</th>
                            <th style="width:160px">Kode</th>
                            <th>Supplier</th>
                            <th>Gudang</th>
                            <th style="width:100px">Status</th>
                            <th style="width:70px" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->date?->format('Y-m-d') }}</td>
                                <td class="mono">{{ $row->code }}</td>
                                <td>{{ $row->supplier?->name }}</td>
                                <td>{{ $row->warehouse?->name }}</td>
                                <td>
                                    <span
                                        class="badge bg-{{ $row->status === 'posted' ? 'success' : 'secondary' }} badge-status">
                                        {{ ucfirst($row->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('purchasing.invoices.show', $row->id) }}"
                                        class="btn btn-sm btn-outline-primary btn-icon" title="Lihat Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (isset($rows))
                <div class="card-body">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
@endsection
