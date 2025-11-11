@extends('layouts.app')
@section('title', 'Accounting • Buku Besar')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
    <div class="container py-3">
        <h3 class="mb-3">Buku Besar</h3>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <select name="account_id" class="form-select">
                    <option value="">— Semua Akun —</option>
                    @foreach ($accounts as $a)
                        <option value="{{ $a->id }}" @selected($accountId == $a->id)>{{ $a->code }} —
                            {{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="range" value="{{ $range ?? '' }}"
                    placeholder="2025-11-01 s/d 2025-11-30">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Terapkan</button>
            </div>
        </form>

        @forelse($grouped as $accountId => $rows)
            @php
                $akun = $rows[0]['code'] . ' — ' . $rows[0]['name'];
            @endphp
            <div class="card mb-3">
                <div class="card-header fw-semibold">{{ $akun }}</div>
                <div class="table-responsive">
                    <table class="table table-sm m-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 120px">Tanggal</th>
                                <th style="width: 160px">Kode Jurnal</th>
                                <th style="width: 160px">Ref</th>
                                <th>Catatan</th>
                                <th style="width: 120px" class="text-end">Debit</th>
                                <th style="width: 120px" class="text-end">Kredit</th>
                                <th style="width: 140px" class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td class="mono">{{ \Illuminate\Support\Carbon::parse($r['date'])->format('Y-m-d') }}
                                    </td>
                                    <td class="mono">{{ $r['jcode'] }}</td>
                                    <td class="mono">{{ $r['ref'] }}</td>
                                    <td class="text-muted">{{ $r['note'] }}</td>
                                    <td class="text-end mono">{{ $fmt($r['debit']) }}</td>
                                    <td class="text-end mono">{{ $fmt($r['credit']) }}</td>
                                    <td class="text-end mono fw-semibold">{{ $fmt($r['balance']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="text-muted">Tidak ada data untuk filter ini.</div>
        @endforelse
    </div>
@endsection
