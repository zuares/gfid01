@extends('layouts.app')
@section('title', 'Accounting • Journal Detail')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0">Detail Jurnal</h3>
            <a href="{{ route('accounting.journals.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="text-muted small">Tanggal</div>
                        <div class="mono">{{ \Illuminate\Support\Carbon::parse($jr->date)->format('Y-m-d') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Kode</div>
                        <div class="mono">{{ $jr->code }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Ref</div>
                        <div class="mono">{{ $jr->ref_code }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Memo</div>
                        <div>{{ $jr->memo }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm m-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 140px">Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Catatan</th>
                            <th style="width: 140px" class="text-end">Debit</th>
                            <th style="width: 140px" class="text-end">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $l)
                            <tr>
                                <td class="mono">{{ $l->account_code }}</td>
                                <td>{{ $l->account_name }}</td>
                                <td class="text-muted">{{ $l->note }}</td>
                                <td class="text-end mono">{{ $fmt($l->debit) }}</td>
                                <td class="text-end mono">{{ $fmt($l->credit) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Total</td>
                            <td class="text-end mono">{{ $fmt($totalDebit) }}</td>
                            <td class="text-end mono">{{ $fmt($totalCredit) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
