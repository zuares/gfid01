@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Vendor Cutting - Detail Bahan Dikirim</h4>
                <div class="text-muted small">
                    Dokumen: <strong>{{ $t->code }}</strong>
                    &middot; Operator: <strong>{{ $t->operator_code }}</strong>
                    &middot; Tanggal: <strong>{{ $t->date?->format('d/m/Y') }}</strong>
                </div>
            </div>

            <a href="{{ route('production.vendor_cutting.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali
            </a>
        </div>

        {{-- Alert jika ada flash --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- CARD DETAIL HEADER + BUTTON KONFIRM --}}
        <div class="card mb-3">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="mb-1">
                        <span class="badge bg-info-subtle text-info">STATUS: SENT</span>
                    </div>
                    <div class="small text-muted">
                        Bahan kain sudah dikirim ke vendor.
                        Kamu bisa:
                        <ul class="mb-0">
                            <li>Konfirmasi bahan diterima (1 klik)</li>
                            <li>atau langsung isi hasil cutting detail di form manual.</li>
                        </ul>
                    </div>
                </div>

                <div class="text-md-end">
                    {{-- Tombol 1-klik konfirmasi --}}
                    <form action="{{ route('production.vendor_cutting.confirm_all', $t->id) }}" method="POST"
                        onsubmit="return confirm('Konfirmasi bahwa SEMUA bahan di dokumen ini sudah diterima vendor dan buat job cutting otomatis?');">
                        @csrf
                        <button type="submit" class="btn btn-success mb-2 w-100">
                            <i class="bi bi-check2-circle"></i>
                            Konfirmasi Bahan Diterima
                        </button>
                    </form>


                </div>
            </div>
        </div>

        {{-- TABEL LOT KAIN --}}
        <div class="card">
            <div class="card-header py-2">
                <strong>LOT Kain yang Dikirim</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>LOT Kain</th>
                                <th>Item</th>
                                <th class="text-end" style="width:140px">Qty Kirim</th>
                                <th>UOM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lines as $l)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $l->lot_code }}
                                    </td>
                                    <td>
                                        {{ $l->item_code }} — {{ $l->item_name }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($l->qty, 2) }}
                                    </td>
                                    <td class="text-uppercase">
                                        {{ $l->unit }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Tidak ada baris LOT di dokumen ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
