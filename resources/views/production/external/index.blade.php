@extends('layouts.app')

@section('content')
    <div class="container py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">External Transfer (Makloon)</h4>

            <a href="{{ route('production.external.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Buat External Transfer
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('ok'))
            <div class="alert alert-info alert-dismissible fade show">
                {{ session('ok') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Table --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px" class="text-center">#</th>
                                <th>Kode</th>
                                <th>Proses</th>
                                <th>Operator</th>
                                <th>Tanggal</th>
                                <th class="text-center">Baris LOT</th>
                                <th>Status</th>
                                <th style="width: 260px" class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($rows as $idx => $t)
                                <tr>
                                    {{-- NO --}}
                                    <td class="text-center">
                                        {{ $rows->firstItem() + $idx }}
                                    </td>

                                    {{-- KODE --}}
                                    <td class="fw-semibold">
                                        {{ $t->code }}
                                    </td>

                                    {{-- PROSES --}}
                                    <td>
                                        @if ($t->process === 'cutting')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                CUTTING
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                SEWING
                                            </span>
                                        @endif
                                    </td>

                                    {{-- OPERATOR --}}
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $t->operator_code }}
                                        </span>
                                    </td>

                                    {{-- TANGGAL --}}
                                    <td>{{ $t->date?->format('d/m/Y') }}</td>

                                    {{-- JUMLAH LOT --}}
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $t->lines_count }}
                                        </span>
                                    </td>

                                    {{-- STATUS --}}
                                    <td>
                                        @php
                                            $map = [
                                                'draft' => 'bg-secondary-subtle text-secondary',
                                                'sent' => 'bg-info-subtle text-info',
                                                'partially_received' => 'bg-warning-subtle text-warning',
                                                'received' => 'bg-success-subtle text-success',
                                                'posted' => 'bg-dark text-light',
                                                'canceled' => 'bg-danger-subtle text-danger',
                                            ];
                                        @endphp

                                        <span class="badge {{ $map[$t->status] ?? 'bg-light text-dark' }}">
                                            {{ strtoupper(str_replace('_', ' ', $t->status)) }}
                                        </span>
                                    </td>

                                    {{-- AKSI --}}
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">

                                            {{-- TERIMA --}}
                                            @if (in_array($t->status, ['sent', 'partially_received', 'draft']))
                                                <a href="{{ route('production.external.receive.form', $t->id) }}"
                                                    class="btn btn-outline-secondary">
                                                    <i class="bi bi-box-arrow-in-down"></i> Terima
                                                </a>
                                            @endif

                                            {{-- KIRIM --}}
                                            @if ($t->status === 'draft')
                                                <form action="{{ route('production.external.send', $t->id) }}"
                                                    method="POST" onsubmit="return confirm('Kirim stok ke makloon?')">
                                                    @csrf
                                                    <button class="btn btn-outline-primary">
                                                        <i class="bi bi-truck"></i> Kirim
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- POST --}}
                                            @if ($t->status === 'received')
                                                <form action="{{ route('production.external.post', $t->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Post jurnal untuk dokumen ini?')">
                                                    @csrf
                                                    <button class="btn btn-outline-success">
                                                        <i class="bi bi-journal-check"></i> Post
                                                    </button>
                                                </form>
                                            @endif

                                        </div>
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-4 text-center text-muted">
                                        Belum ada data.
                                        <br>
                                        <a href="{{ route('production.external.create') }}"
                                            class="btn btn-sm btn-primary mt-2">
                                            <i class="bi bi-plus"></i> Buat pertama
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            @if ($rows->hasPages())
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $rows->total() }}
                        </span>

                        {{ $rows->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
