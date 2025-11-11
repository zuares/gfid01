{{-- resources/views/accounting/ledger.blade.php --}}
@extends('layouts.app')
@section('title', 'Accounting • Buku Besar')

@push('head')
    <style>
        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .table {
            margin: 0
        }

        /* Header tabel yang jelas di dark & light mode */
        .table thead th {
            font-weight: 700;
            letter-spacing: .02em;
            border-bottom-width: 1px;
        }

        /* Light */
        :root[data-bs-theme="light"] .table thead th {
            background: #f8fafc;
            /* lembut tapi kontras */
            color: #0f172a;
            /* slate-900 */
        }

        /* Dark */
        :root[data-bs-theme="dark"] .table thead th {
            background: #0f172a;
            /* card */
            color: #e2e8f0;
            /* slate-200 */
            border-bottom-color: rgba(148, 163, 184, .35);
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <h3 class="mb-3">Buku Besar (Ringkas)</h3>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-12 col-md-4">
                <input type="text" class="form-control" name="range" value="{{ $range ?? '' }}"
                    placeholder="2025-11-01 s/d 2025-11-11">
            </div>
            <div class="col-12 col-md-4">
                <select class="form-select" name="account_id">
                    <option value="">— Pilih akun untuk detail —</option>
                    @foreach ($accounts as $a)
                        <option value="{{ $a->id }}" @selected(($account_id ?? null) == $a->id)>
                            {{ $a->code }} — {{ $a->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-primary w-100">Terapkan</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:140px">Kode</th>
                            <th>Akun</th>
                            <th style="width:160px" class="text-end">Saldo Awal</th>
                            <th style="width:140px" class="text-end">Debet</th>
                            <th style="width:140px" class="text-end">Kredit</th>
                            <th style="width:160px" class="text-end">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary as $s)
                            <tr>
                                <td class="mono">{{ $s['code'] }}</td>
                                <td>{{ $s['name'] }}</td>

                                {{-- gunakan helper numf(..., 0) -> tanpa desimal, gaya Indonesia --}}
                                <td class="text-end mono">{{ numf($s['opening'] ?? 0, 0) }}</td>
                                <td class="text-end mono">{{ numf($s['debit'] ?? 0, 0) }}</td>
                                <td class="text-end mono">{{ numf($s['credit'] ?? 0, 0) }}</td>
                                <td class="text-end mono">{{ numf($s['closing'] ?? 0, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
