@extends('layouts.app')
@section('title', 'Accounting • Journals')

@push('head')
    <style>
        .table {
            margin: 0
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <h3 class="mb-3">Daftar Jurnal</h3>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-12 col-md-4">
                <input type="text" class="form-control" name="q" value="{{ $q }}"
                    placeholder="Cari kode/ref/memo...">
            </div>
            <div class="col-12 col-md-4">
                <input type="text" class="form-control" name="range" value="{{ $range }}"
                    placeholder="2025-11-01 s/d 2025-11-11">
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:140px">Tanggal</th>
                            <th style="width:170px">Kode</th>
                            <th>Ref</th>
                            <th>Memo</th>
                            <th style="width:80px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td class="mono">
                                    {{ $r->date ? $r->date->format('Y-m-d') : '-' }}
                                </td>

                                <td class="mono">{{ $r->code }}</td>
                                <td class="mono">{{ $r->ref_code }}</td>
                                <td>{{ $r->memo }}</td>
                                <td><a class="btn btn-sm btn-outline-secondary"
                                        href="{{ route('accounting.journals.show', $r->id) }}">Lihat</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-2">
                {{ $rows->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
