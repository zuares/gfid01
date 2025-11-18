@extends('layouts.app')

@section('content')
    <div class="container py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold" style="color: var(--text);">
                External Transfer (Kirim Bahan)
            </h3>
            <a href="{{ route('inventory.external_transfers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Buat Transfer
            </a>
        </div>

        {{-- DESKTOP TABLE --}}


        {{-- MOBILE CARD LIST --}}


        {{-- PAGINATION --}}

    </div>
@endsection
