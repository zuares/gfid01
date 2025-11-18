@extends('layouts.app')

@section('title', 'Report • Sisa Jahit per Operator')

@section('content')
    <div class="page-wrap" style="max-width: 1080px; margin-inline: auto;">

        {{-- HEADER --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="mb-1">Report Sisa Jahit per Operator</h4>
                <div class="small text-muted">
                    Rekap total ambil, setor (OK + Reject), dan sisa jahit per penjahit.
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('production.sewing_returns.create') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Setor Jahit
                </a>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body small">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Operator</label>
                        <select name="operator_id" class="form-select form-select-sm">
                            <option value="">- Semua Operator -</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}" @selected($operatorId == $op->id)>
                                    {{ $op->code ?? 'OP-' . $op->id }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="{{ route('production.sewing_report.operator_balance_export', [
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'operator_id' => $operatorId,
                        ]) }}"
                            class="btn btn-success btn-sm">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABEL REKAP --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Operator</th>
                                <th class="text-end">Total Ambil</th>
                                <th class="text-end">Total Setor OK</th>
                                <th class="text-end">Total Reject</th>
                                <th class="text-end">Sisa Jahit</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalAmbilAll = 0;
                                $totalOkAll = 0;
                                $totalRejectAll = 0;
                                $totalSisaAll = 0;
                            @endphp

                            @forelse ($rows as $idx => $row)
                                @php
                                    $totalAmbilAll += $row['ambil'];
                                    $totalOkAll += $row['setor_ok'];
                                    $totalRejectAll += $row['setor_reject'];
                                    $totalSisaAll += $row['sisa'];
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $row['operator']->name }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $row['operator']->code ?? 'OP-' . $row['operator']->id }}
                                        </div>
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($row['ambil'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-success">
                                        {{ number_format($row['setor_ok'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-danger">
                                        {{ number_format($row['setor_reject'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono fw-semibold">
                                        {{ number_format($row['sisa'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('production.sewing_report.operator_detail', $row['operator']->id) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Tidak ada data. Ubah filter tanggal atau operator.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($rows->isNotEmpty())
                            <tfoot>
                                <tr class="fw-semibold table-light">
                                    <td colspan="2" class="text-end">TOTAL</td>
                                    <td class="text-end mono">
                                        {{ number_format($totalAmbilAll, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-success">
                                        {{ number_format($totalOkAll, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono text-danger">
                                        {{ number_format($totalRejectAll, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($totalSisaAll, 2, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
