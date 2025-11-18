@extends('layouts.app')
@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.1rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-draft {
            background: #fff3cd;
            color: #856404;
        }

        .status-posted {
            background: #d4edda;
            color: #155724;
        }

        .table thead th {
            position: sticky;
            top: 0;
            background: var(--bg, #fff);
            z-index: 1;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Finishing</h1>
                <small class="text-muted">Daftar dokumen finishing dari WIP-FIN ke FG.</small>
            </div>
            <a href="{{ route('production.finishing.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Tambah Finishing
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success py-2">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Kode</th>
                            <th style="width: 120px;">Tanggal</th>
                            <th>Operator</th>
                            <th>Gudang</th>
                            <th>Status</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobs as $job)
                            <tr>
                                <td class="mono">
                                    <a href="{{ route('production.finishing.show', $job->id) }}">
                                        {{ $job->code }}
                                    </a>
                                </td>
                                <td class="mono">
                                    {{ $job->date instanceof \Carbon\Carbon ? $job->date->format('Y-m-d') : $job->date }}
                                </td>
                                <td>
                                    {{ optional($job->operator)->name ?? '-' }}
                                </td>
                                <td class="small">
                                    <div><strong>From:</strong> {{ optional($job->fromWarehouse)->code ?? '-' }}</div>
                                    <div><strong>To:</strong> {{ optional($job->toWarehouse)->code ?? '-' }}</div>
                                </td>
                                <td>
                                    @php
                                        $status = $job->status;
                                    @endphp
                                    <span class="status-chip {{ $status === 'posted' ? 'status-posted' : 'status-draft' }}">
                                        {{ strtoupper($status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('production.finishing.show', $job->id) }}"
                                        class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    Belum ada dokumen finishing.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
@endsection
