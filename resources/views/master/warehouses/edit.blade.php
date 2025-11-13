@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Edit Gudang</h4>

            <a href="{{ route('master.warehouses.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('master.warehouses.update', $warehouse) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">

                        {{-- KODE --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Gudang</label>
                            <input type="text" name="code" value="{{ old('code', $warehouse->code) }}"
                                class="form-control @error('code') is-invalid @enderror" style="text-transform: uppercase;">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">
                                    Pastikan kode tetap unik (dipakai di referensi stok & mutasi).
                                </div>
                            @enderror
                        </div>

                        {{-- NAMA --}}
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Gudang</label>
                            <input type="text" name="name" value="{{ old('name', $warehouse->name) }}"
                                class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
