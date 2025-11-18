@extends('layouts.app')

@section('title', 'Produksi • Packing')

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Packing Jobs</h1>

            <a href="{{ route('production.packing_jobs.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Buat Packing
            </a>
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

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Dari</th>
                                <th>Ke</th>
                                <th>Status</th>
                                <th class="text-end">Total Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($packingJobs as $job)
                                <tr>
                                    <td>
                                        <a href="{{ route('production.packing_jobs.show', $job) }}">
                                            {{ $job->code }}
                                        </a>
                                    </td>
                                    <td>{{ $job->date?->format('d/m/Y') }}</td>
                                    <td>{{ $job->fromWarehouse->code ?? '-' }}</td>
                                    <td>{{ $job->toWarehouse->code ?? '-' }}</td>
                                    <td>
                                        @if ($job->status === 'posted')
                                            <span class="badge bg-success">POSTED</span>
                                        @else
                                            <span class="badge bg-secondary">DRAFT</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($job->total_qty, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        Belum ada dokumen packing.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($packingJobs instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="p-2">
                        {{ $packingJobs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
