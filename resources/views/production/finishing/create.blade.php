@extends('layouts.app')
@section('title', 'Produksi • Finishing (Create)')

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

        .table thead th {
            position: sticky;
            top: 0;
            background: var(--bg, #fff);
            z-index: 1;
        }

        .small-help {
            font-size: 0.75rem;
            color: #6c757d;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Finishing - Buat Dokumen</h1>
                <small class="text-muted">Sumber stok dari WIP-FIN, hasil ke gudang FG.</small>
            </div>
            <a href="{{ route('production.finishing.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('production.finishing.store') }}">
            @csrf

            <input type="hidden" name="from_warehouse_id" value="{{ $fromWarehouse->id }}">
            <input type="hidden" name="to_warehouse_id" value="{{ $toWarehouse->id }}">

            <div class="card p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                            class="form-control form-control-sm @error('date') is-invalid @enderror">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Operator Finishing (opsional)</label>
                        <select name="operator_id"
                            class="form-select form-select-sm @error('operator_id') is-invalid @enderror">
                            <option value="">- Tidak ada -</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}" {{ old('operator_id') == $op->id ? 'selected' : '' }}>
                                    {{ $op->name }} ({{ $op->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                            class="form-control form-control-sm @error('notes') is-invalid @enderror">
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="small-help mt-1">
                            From: <strong>{{ $fromWarehouse->code }}</strong> &rarr;
                            To: <strong>{{ $toWarehouse->code }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Detail Finishing (sumber WIP-FIN)</h2>
                    <span class="small-help">
                        Isi Qty OK / Reject. Qty OK + Reject ≤ Qty Sumber.
                    </span>
                </div>

                <div class="table-responsive" style="max-height: 480px;">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Kode Item</th>
                                <th>Nama</th>
                                <th>LOT</th>
                                <th class="text-end">Stok WIP</th>
                                <th class="text-end">Qty Sumber</th>
                                <th class="text-end">Qty OK</th>
                                <th class="text-end">Qty Reject</th>
                                <th>Unit</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stocks as $idx => $stock)
                                @php
                                    $rowKey = "lines.$idx";
                                @endphp
                                <tr>
                                    <td class="mono">
                                        {{ $idx + 1 }}
                                        <input type="hidden" name="lines[{{ $idx }}][stock_id]"
                                            value="{{ $stock->id }}">
                                    </td>
                                    <td class="mono">{{ $stock->item_code }}</td>
                                    <td>{{ optional($stock->item)->name ?? '-' }}</td>
                                    <td class="mono">{{ optional($stock->lot)->code ?? '-' }}</td>

                                    <td class="mono text-end">
                                        {{ number_format($stock->qty, 2) }}
                                    </td>

                                    <td class="text-end">
                                        <input type="number" step="0.01" name="lines[{{ $idx }}][qty_source]"
                                            class="form-control form-control-sm mono @error($rowKey . '.qty_source') is-invalid @enderror"
                                            value="{{ old("lines.$idx.qty_source", $stock->qty) }}">
                                    </td>

                                    <td class="text-end">
                                        <input type="number" step="0.01" name="lines[{{ $idx }}][qty_ok]"
                                            class="form-control form-control-sm mono @error($rowKey . '.qty_ok') is-invalid @enderror"
                                            value="{{ old("lines.$idx.qty_ok") }}">
                                    </td>

                                    <td class="text-end">
                                        <input type="number" step="0.01" name="lines[{{ $idx }}][qty_reject]"
                                            class="form-control form-control-sm mono @error($rowKey . '.qty_reject') is-invalid @enderror"
                                            value="{{ old("lines.$idx.qty_reject") }}">
                                    </td>

                                    <td>
                                        <input type="text" name="lines[{{ $idx }}][unit]"
                                            class="form-control form-control-sm mono @error($rowKey . '.unit') is-invalid @enderror"
                                            value="{{ old("lines.$idx.unit", $stock->unit ?? 'pcs') }}">
                                    </td>

                                    <td>
                                        <input type="text" name="lines[{{ $idx }}][notes]"
                                            class="form-control form-control-sm @error($rowKey . '.notes') is-invalid @enderror"
                                            value="{{ old("lines.$idx.notes") }}">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-3">
                                        Tidak ada stok di gudang WIP-FIN.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-save"></i> Simpan DRAFT Finishing
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
