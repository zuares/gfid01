@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Vendor Cutting - Daftar Bahan Masuk</h4>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Operator</th>
                                <th>Tanggal</th>
                                <th class="text-center">Baris LOT</th>
                                <th>Status</th>
                                <th class="text-end" style="width:150px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $t)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $t->code }}
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $t->operator_code }}
                                        </span>
                                    </td>
                                    <td>{{ $t->date?->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $t->lines_count }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">
                                            SENT
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('production.vendor_cutting.create', $t->id) }}"
                                            class="btn btn-sm btn-primary">
                                            Proses Cutting
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Tidak ada dokumen external (cutting) dengan status <code>sent</code>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($rows->hasPages())
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $rows->total() }}
                        </span>
                        {{ $rows->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
