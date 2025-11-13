@extends('layouts.app')

@section('content')
    <div class="container py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Master Gudang</h4>

            <a href="{{ route('master.warehouses.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Tambah Gudang
            </a>
        </div>

        {{-- Flash messages --}}
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
                                <th style="width: 60px" class="text-center">#</th>
                                <th style="width: 140px">Kode</th>
                                <th>Nama</th>
                                <th style="width: 150px" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $idx => $wh)
                                <tr>
                                    <td class="text-center">
                                        {{ $rows->firstItem() + $idx }}
                                    </td>
                                    <td class="fw-semibold text-uppercase">
                                        {{ $wh->code }}
                                    </td>
                                    <td>{{ $wh->name }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('master.warehouses.edit', $wh) }}"
                                                class="btn btn-outline-secondary">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>

                                            <form action="{{ route('master.warehouses.destroy', $wh) }}" method="POST"
                                                onsubmit="return confirm('Hapus gudang {{ $wh->code }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Belum ada data gudang.
                                        <br>
                                        <a href="{{ route('master.warehouses.create') }}"
                                            class="btn btn-sm btn-primary mt-2">
                                            <i class="bi bi-plus-circle"></i> Tambah pertama
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
                        <div class="small text-muted">
                            Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }}
                            dari {{ $rows->total() }} data
                        </div>
                        <div>
                            {{ $rows->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
