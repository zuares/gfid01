@extends('layouts.app')
@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        thead th {
            background: var(--panel);
            position: sticky;
            top: 0;
            z-index: 3;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- ====== HEADER ====== --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-semibold">Finishing</h5>
            <a href="{{ route('production.finishing.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Baru
            </a>
        </div>

        {{-- ====== FILTER ====== --}}
        <form method="GET" class="card p-3 mb-4">
            <div class="row g-2">

                {{-- Operator finishing (opsional) --}}
                <div class="col-md-4">
                    <label class="form-label small">Operator</label>
                    <select name="employee_id" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date from --}}
                <div class="col-md-3">
                    <label class="form-label small">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>

                {{-- Date to --}}
                <div class="col-md-3">
                    <label class="form-label small">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-secondary w-100 btn-sm">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>

            </div>
        </form>

        {{-- ====== TABLE ====== --}}
        <div class="card p-0">
            <div class="table-responsive" style="max-height: calc(100vh - 260px);">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Tanggal</th>
                            <th>Kode</th>
                            <th>Operator</th>
                            <th>Dari</th>
                            <th>Ke</th>
                            <th class="text-end" style="width: 80px;">Qty OK</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobs as $job)
                            <tr>
                                <td class="mono">{{ $job->date }}</td>
                                <td class="mono">{{ $job->code }}</td>
                                <td>{{ $job->employee?->name ?? '-' }}</td>
                                <td>{{ $job->fromWarehouse?->code ?? '-' }}</td>
                                <td>{{ $job->toWarehouse?->code ?? '-' }}</td>

                                {{-- Hitung total OK dari lines --}}
                                <td class="text-end">
                                    <span class="mono">
                                        {{ number_format($job->lines->sum('qty_ok'), 0) }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('production.finishing.show', $job->id) }}"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tidak ada data finishing.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
@endsection
