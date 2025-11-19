@extends('layouts.app')

@section('title', 'Report • Detail Sisa Jahit')

@section('content')
    <div class="page-wrap" style="max-width: 1080px; margin-inline: auto;">

        {{-- HEADER --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="mb-1">
                    Detail Sisa Jahit — {{ $operator->name }}
                </h4>
                <div class="small text-muted">
                    {{ $operator->code ?? 'OP-' . $operator->id }}
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('production.sewing_report.operator_balance') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Rekap
                </a>
                <a href="{{ route('production.sewing_report.operator_detail_export', $operator) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
                    class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Detail
                </a>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body small">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- REKAP PER ITEM --}}
        <div class="card mb-4">
            <div class="card-header small fw-semibold text-uppercase">
                Rekap per Item
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Kode Item</th>
                                <th>Nama Item</th>
                                <th class="text-end">Total Ambil</th>
                                <th class="text-end">Setor OK</th>
                                <th class="text-end">Reject</th>
                                <th class="text-end">Sisa Jahit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $sumAmbil = 0;
                                $sumOk = 0;
                                $sumReject = 0;
                                $sumSisa = 0;
                            @endphp
                            @forelse ($rekapItemLot as $idx => $row)
                                @php
                                    // $row adalah object dengan properti:
                                    // item_code, item_name, total_ambil, total_ok, total_reject, sisa
                                    $sumAmbil += $row->total_ambil;
                                    $sumOk += $row->total_ok;
                                    $sumReject += $row->total_reject;
                                    $sumSisa += $row->sisa;
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td class="mono">{{ $row->item_code }}</td>
                                    <td>{{ $row->item_name }}</td>
                                    <td class="text-end mono">
                                        {{ number_format($row->total_ambil, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-success">
                                        {{ number_format($row->total_ok, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-danger">
                                        {{ number_format($row->total_reject, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono fw-semibold">
                                        {{ number_format($row->sisa, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Tidak ada data rekap per item untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($rekapItemLot->isNotEmpty())
                            <tfoot>
                                <tr class="table-light fw-semibold">
                                    <td colspan="3" class="text-end">TOTAL</td>
                                    <td class="text-end mono">
                                        {{ number_format($sumAmbil, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-success">
                                        {{ number_format($sumOk, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-danger">
                                        {{ number_format($sumReject, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($sumSisa, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- HISTORI AMBIL & SETOR --}}
        <div class="row g-3">
            {{-- HISTORI AMBIL --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header small fw-semibold text-uppercase">
                        Histori Ambil Jahit
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 360px;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Dokumen</th>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($historiAmbil as $row)
                                        <tr>
                                            <td class="small">
                                                {{ \Illuminate\Support\Carbon::parse($row->date)->format('d M Y') }}
                                            </td>
                                            <td class="mono small">{{ $row->doc_code }}</td>
                                            <td class="small">
                                                <div class="mono">{{ $row->item_code }}</div>
                                                <div class="text-muted">{{ $row->item_name }}</div>
                                            </td>
                                            <td class="text-end mono small">
                                                {{ number_format($row->qty, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                Tidak ada histori ambil untuk periode ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- HISTORI SETOR --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header small fw-semibold text-uppercase">
                        Histori Setor Jahit
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 360px;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Dokumen</th>
                                        <th>Item</th>
                                        <th class="text-end">OK</th>
                                        <th class="text-end">Reject</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($historiSetor as $row)
                                        <tr>
                                            <td class="small">
                                                {{ \Illuminate\Support\Carbon::parse($row->date)->format('d M Y') }}
                                            </td>
                                            <td class="mono small">{{ $row->doc_code }}</td>
                                            <td class="small">
                                                <div class="mono">{{ $row->item_code }}</div>
                                                <div class="text-muted">{{ $row->item_name }}</div>
                                            </td>
                                            <td class="text-end mono small text-success">
                                                {{ number_format($row->qty_ok, 2, ',', '.') }}
                                            </td>
                                            <td class="text-end mono small text-danger">
                                                {{ number_format($row->qty_reject, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">
                                                Tidak ada histori setor untuk periode ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
