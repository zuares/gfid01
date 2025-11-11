@extends('layouts.app')
@section('title', 'Journal • ' . $jr->code)

@push('head')
    <style>
        .table {
            margin: 0
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace
        }

        .tot {
            font-weight: 700
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="m-0">Jurnal: <span class="mono">{{ $jr->code }}</span></h4>
            <a class="btn btn-light" href="{{ route('accounting.journals.index') }}">Kembali</a>
        </div>

        <div class="mb-2 text-muted">
            <div>Tanggal: <span class="mono">{{ optional($jr->date)->format('Y-m-d') }}</span></div>
            <div>Ref: <span class="mono">{{ $jr->ref_code }}</span></div>
            <div>Memo: {{ $jr->memo }}</div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Akun</th>
                            <th style="width:140px" class="text-end">Debet</th>
                            <th style="width:140px" class="text-end">Kredit</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jr->lines as $l)
                            <tr>
                                <td class="mono">{{ $l->account?->code }} — {{ $l->account?->name }}</td>
                                <td class="text-end mono">{{ number_format($l->debit, 2, ',', '.') }}</td>
                                <td class="text-end mono">{{ number_format($l->credit, 2, ',', '.') }}</td>
                                <td>{{ $l->note }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-light">
                            <td class="tot text-end">Total</td>
                            <td class="text-end mono tot">{{ number_format($totalDebit, 2, ',', '.') }}</td>
                            <td class="text-end mono tot">{{ number_format($totalCredit, 2, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
