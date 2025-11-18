@extends('layouts.app')

@section('title', 'Produksi • Packing • Buat')

@section('content')
    <div class="page-wrap" style="max-width: 1100px; margin-inline: auto;">
        <h1 class="h4 mb-3">Buat Packing dari Hasil Jahit</h1>

        @if (session('error'))
            <div class="alert alert-danger mb-3">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('production.packing_jobs.store') }}" method="POST">
            @csrf

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Dari Gudang</label>
                            <input type="text" class="form-control"
                                value="{{ $fromWarehouse->code }} - {{ $fromWarehouse->name }}" disabled>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ke Gudang</label>
                            <input type="text" class="form-control"
                                value="{{ $toWarehouse->code }} - {{ $toWarehouse->name }}" disabled>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Pilih Bundle Hasil Jahit yang Akan Dipacking</span>
                    <small class="text-muted">
                        Sumber: WIP-FIN (dari sewing_return_lines yang sudah POSTED)
                    </small>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 480px; overflow: auto;">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 32px;">
                                        {{-- ceklis manual per baris --}}
                                    </th>
                                    <th>Kode Setor Jahit</th>
                                    <th>Item</th>
                                    <th>LOT</th>
                                    <th class="text-end">Qty OK</th>
                                    <th class="text-end">Sudah Packing</th>
                                    <th class="text-end">Sisa</th>
                                    <th class="text-end" style="width: 140px;">Qty Packing</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bundleLines as $idx => $line)
                                    @php
                                        $qtyOk = (float) ($line->qty_ok ?? 0);
                                        $packedQty = (float) ($line->packed_qty ?? 0);
                                        $remaining = $qtyOk - $packedQty;
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input"
                                                name="lines[{{ $idx }}][selected]" value="1">
                                        </td>
                                        <td>
                                            {{ $line->sewingReturn->code ?? '-' }}<br>
                                            <small class="text-muted">
                                                {{ $line->sewingReturn->date?->format('d/m/Y') }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $line->item_code }}</div>
                                            <div class="small text-muted">{{ $line->item->name ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="mono">
                                                {{ $line->lot->code ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($qtyOk, 2) }}
                                        </td>
                                        <td class="text-end text-muted">
                                            {{ number_format($packedQty, 2) }}
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format($remaining, 2) }}
                                        </td>
                                        <td>
                                            <input type="hidden" name="lines[{{ $idx }}][line_id]"
                                                value="{{ $line->id }}">
                                            <input type="number" step="0.01" min="0" max="{{ $remaining }}"
                                                name="lines[{{ $idx }}][qty_pack]"
                                                class="form-control form-control-sm text-end" value="">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">
                                            Tidak ada bundle hasil jahit yang siap dipacking (sisa = 0).
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Hanya baris yang dicentang dan Qty Packing &gt; 0 yang akan dibuat sebagai detail dokumen.
                    </small>
                    <button type="submit" class="btn btn-primary">
                        Simpan Draft Packing
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
