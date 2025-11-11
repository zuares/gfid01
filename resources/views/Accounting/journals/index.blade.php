@extends('layouts.app')
@section('title', 'Accounting • Journals')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
    <div class="container py-3">
        <h3 class="mb-3">Jurnal Umum</h3>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="q" value="{{ $q ?? '' }}"
                    placeholder="Cari kode/ref/memo…">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="range" value="{{ $range ?? '' }}"
                    placeholder="2025-11-01 s/d 2025-11-30">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm m-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 140px">Tanggal</th>
                            <th style="width: 180px">Kode</th>
                            <th>Ref</th>
                            <th>Memo</th>
                            <th style="width: 120px" class="text-end">Total D</th>
                            <th style="width: 120px" class="text-end">Total C</th>
                            <th style="width: 80px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            @php
                                $tot = \DB::table('journal_lines')
                                    ->selectRaw('SUM(debit) d, SUM(credit) c')
                                    ->where('journal_entry_id', $r->id)
                                    ->first();
                            @endphp
                            <tr>
                                <td class="mono">{{ \Illuminate\Support\Carbon::parse($r->date)->format('Y-m-d') }}</td>
                                <td class="mono">{{ $r->code }}</td>
                                <td class="mono">{{ $r->ref_code }}</td>
                                <td>{{ $r->memo }}</td>
                                <td class="text-end mono">{{ $fmt($tot->d ?? 0) }}</td>
                                <td class="text-end mono">{{ $fmt($tot->c ?? 0) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('accounting.journals.show', $r->id) }}"
                                        class="btn btn-sm btn-outline-secondary">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $rows->withQueryString()->links() }}
        </div>
    </div>
@endsection
