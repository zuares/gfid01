@extends('layouts.app')
@section('title', 'Buku Besar • Detail')

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
        <h3 class="mb-3">Buku Besar – {{ $acc->code }} — {{ $acc->name }}</h3>

        <form method="GET" class="row g-2 mb-3" action="{{ route('accounting.ledger') }}">
            <div class="col-12 col-md-4">
                <input type="text" class="form-control" name="range" value="{{ $range }}"
                    placeholder="2025-11-01 s/d 2025-11-11">
            </div>
            <div class="col-12 col-md-4">
                <select class="form-select" name="account_id">
                    @foreach ($accounts as $a)
                        <option value="{{ $a->id }}" {{ $a->id == $accountId ? 'selected' : '' }}>
                            {{ $a->code }} — {{ $a->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-primary w-100">Terapkan</button>
            </div>
            <div class="col-12 col-md-2">
                <a class="btn btn-light w-100" href="{{ route('accounting.ledger') }}">Ringkas</a>
            </div>
        </form>

        <div class="mb-2">
            <span class="text-muted">Periode:</span>
            <span class="mono">{{ $dateFrom ?: '—' }} s/d {{ $dateTo ?: '—' }}</span>
        </div>

        <div class="card mb-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:120px">Tanggal</th>
                            <th style="width:160px">Jurnal</th>
                            <th style="width:160px">Ref</th>
                            <th>Memo</th>
                            <th style="width:140px" class="text-end">Debet</th>
                            <th style="width:140px" class="text-end">Kredit</th>
                            <th style="width:160px" class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-secondary">
                            <td colspan="6"><em>Saldo awal</em></td>
                            <td class="text-end mono">{{ number_format($opening, 2, ',', '.') }}</td>
                        </tr>
                        @forelse($rows as $r)
                            <tr>
                                <td class="mono">{{ $r['date'] }}</td>
                                <td class="mono">{{ $r['jr_code'] }}</td>
                                <td class="mono">{{ $r['ref_code'] }}</td>
                                <td>{{ $r['memo'] }}</td>
                                <td class="text-end mono">{{ number_format($r['debit'], 2, ',', '.') }}</td>
                                <td class="text-end mono">{{ number_format($r['credit'], 2, ',', '.') }}</td>
                                <td class="text-end mono">{{ number_format($r['balance'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada transaksi pada periode ini</td>
                            </tr>
                        @endforelse
                        <tr class="table-light">
                            <td colspan="4" class="tot text-end">Total Periode</td>
                            <td class="text-end mono tot">{{ number_format($totalDebit, 2, ',', '.') }}</td>
                            <td class="text-end mono tot">{{ number_format($totalCredit, 2, ',', '.') }}</td>
                            <td class="text-end mono tot">{{ number_format($closing, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
