@extends('layouts.app')

@section('title', 'Payroll • Per PCS')

@push('head')
    <style>
        .payroll-page .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .payroll-page .mono {
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
    <div class="payroll-page container-fluid">
        <div class="row g-3">
            {{-- FORM GENERATE --}}
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h1 class="h5 mb-3">Generate Payroll per PCS</h1>

                        @if (session('success'))
                            <div class="alert alert-success small">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form action="{{ route('payroll.runs.store') }}" method="post">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label small-label d-block">Periode</label>
                                <div class="d-flex gap-2">
                                    <input type="date" name="start_date"
                                        value="{{ old('start_date', now()->startOfWeek()->toDateString()) }}"
                                        class="form-control mono" required>
                                    <span class="mt-1">s/d</span>
                                    <input type="date" name="end_date"
                                        value="{{ old('end_date', now()->endOfWeek()->toDateString()) }}"
                                        class="form-control mono" required>
                                </div>
                                @error('start_date')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @error('end_date')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small-label d-block">Proses</label>
                                <select name="process" class="form-select" required>
                                    <option value="sewing" {{ old('process') === 'sewing' ? 'selected' : '' }}>Sewing
                                    </option>
                                    <option value="cutting" {{ old('process') === 'cutting' ? 'selected' : '' }}>Cutting
                                    </option>
                                    <option value="finishing" {{ old('process') === 'finishing' ? 'selected' : '' }}>
                                        Finishing</option>
                                    <option value="all" {{ old('process') === 'all' ? 'selected' : '' }}>Semua Proses
                                    </option>
                                </select>
                                @error('process')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small-label d-block">Catatan (opsional)</label>
                                <textarea name="notes" rows="2" class="form-control"
                                    placeholder="Contoh: Minggu ke-2, hanya jahit celana jogger">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Generate Payroll
                            </button>

                            <div class="text-muted small mt-2">
                                Sistem akan membaca data <strong>production_batches</strong>
                                sesuai periode & proses, lalu membuat rekap gaji per karyawan.
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- LIST RUN --}}
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6 mb-3">History Payroll per PCS</h2>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Periode</th>
                                        <th>Proses</th>
                                        <th class="text-end">Total</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($runs as $run)
                                        <tr>
                                            <td class="mono">
                                                {{ $run->code }}
                                            </td>
                                            <td class="mono">
                                                {{ $run->start_date->format('d M Y') }}
                                                &mdash;
                                                {{ $run->end_date->format('d M Y') }}
                                            </td>
                                            <td>{{ strtoupper($run->process) }}</td>
                                            <td class="text-end mono">
                                                Rp {{ number_format($run->total_amount, 0, ',', '.') }}
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-subtle">
                                                    {{ strtoupper($run->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('payroll.runs.show', $run->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                Belum ada payroll run yang dibuat.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            {{ $runs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
