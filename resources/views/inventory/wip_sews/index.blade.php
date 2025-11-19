@extends('layouts.app')
@section('title', 'Inventory • WIP Sewing')

@push('head')
    <style>
        :root {
            --radius: 14px;
            --line: color-mix(in srgb, var(--bs-border-color) 78%, var(--bs-body-bg) 22%);
            --muted: var(--bs-secondary-color);
        }

        .wrap {
            max-width: 900px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .soft {
            border-color: color-mix(in srgb, var(--line) 70%, transparent 30%);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .muted {
            color: var(--muted);
        }

        .table {
            margin: 0;
        }

        .table thead th {
            font-weight: 600;
            color: var(--muted);
            background: var(--card);
            position: sticky;
            top: 0;
            z-index: 1;
            border-bottom: 1px solid var(--line);
            text-transform: uppercase;
            font-size: .78rem;
            letter-spacing: .03em;
        }

        .table th,
        .table td {
            border: 0;
            vertical-align: middle;
        }

        .table tbody tr+tr td {
            border-top: 1px dashed color-mix(in srgb, var(--line) 80%, transparent 20%);
        }
    </style>
@endpush

@section('content')
    <div class="wrap py-3">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-0">Inventory • WIP Sewing</h5>
                <div class="muted small">
                    Stok WIP siap jahit di gudang WIP-SEW (per item, tanpa LOT).
                </div>
                @if ($warehouse)
                    <div class="muted small">
                        Gudang: <span class="fw-semibold">{{ $warehouse->code }} — {{ $warehouse->name }}</span>
                    </div>
                @else
                    <div class="text-danger small">
                        Gudang WIP-SEW belum dibuat. Akan otomatis dibuat saat QC Cutting pertama kali jalan.
                    </div>
                @endif
            </div>

            <form method="GET" action="{{ route('inventory.wip_sew.index') }}" class="d-flex gap-2">
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm"
                    placeholder="Cari item code / nama…">
                <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="card soft">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Item Code</th>
                            <th>Nama Item</th>
                            <th style="width: 150px;" class="text-end">Qty</th>
                            <th style="width: 80px;">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $numf = fn($v, $d = 2) => number_format((float) $v, $d, ',', '.'); @endphp

                        @forelse ($rows as $row)
                            <tr>
                                <td class="mono">{{ $row->item_code }}</td>
                                <td>{{ $row->item_name }}</td>
                                <td class="text-end mono">{{ $numf($row->qty, 2) }}</td>
                                <td>{{ $row->unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center muted py-4">
                                    Belum ada stok WIP di gudang WIP-SEW.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
