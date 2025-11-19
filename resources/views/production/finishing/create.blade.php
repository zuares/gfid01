@extends('layouts.app')
@section('title', 'Produksi • Finishing / Packing')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        thead th {
            background: var(--panel);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table-sm td,
        .table-sm th {
            padding-block: .35rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-lot {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .75rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .10);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">
                    Finishing / Packing
                </h4>
                <div class="small text-muted">
                    Proses barang dari WIP-FIN menjadi stok FG (Finished Goods).
                </div>
            </div>
            <div>
                <a href="{{ route('production.finishing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        {{-- ALERT VALIDATION --}}
        @if ($errors->any())
            <div class="alert alert-danger small">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM FINISHING (POST) --}}
        <form method="post" action="{{ route('production.finishing.store') }}">
            @csrf

            {{-- HEADER FORM --}}
            <div class="card mb-3">
                <div class="card-body small">
                    <div class="row g-3">
                        {{-- TANGGAL --}}
                        <div class="col-md-3">
                            <label for="date" class="form-label small">Tanggal Finishing</label>
                            <input type="date" name="date" id="date" class="form-control form-control-sm"
                                value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        {{-- PEKERJA / OPERATOR FINISHING (OPTIONAL) --}}
                        <div class="col-md-3">
                            <label for="employee_id" class="form-label small">Petugas Finishing / Packing</label>
                            <select name="employee_id" id="employee_id" class="form-select form-select-sm">
                                <option value="">- Pilih (opsional) -</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" @selected(old('employee_id') == $emp->id)>
                                        {{ $emp->code ?? 'EMP-' . $emp->id }} — {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="help mt-1">
                                Bisa dikosongkan jika tidak perlu tracking petugas.
                            </div>
                        </div>

                        {{-- DARI GUDANG (WIP-FIN) --}}
                        <div class="col-md-3">
                            <label class="form-label small">Dari Gudang (WIP-FIN)</label>
                            <input type="text" class="form-control form-control-sm mono"
                                value="{{ $fromWarehouse->code . ' — ' . $fromWarehouse->name }}" disabled>
                            <input type="hidden" name="from_warehouse_id" value="{{ $fromWarehouse->id }}">
                            <div class="help mt-1">
                                Sumber barang finishing (hasil setor jahit OK).
                            </div>
                        </div>

                        {{-- KE GUDANG (FG → KONTRAKAN) --}}
                        <div class="col-md-3">
                            <label class="form-label small">Ke Gudang (KONTRAKAN)</label>
                            <input type="text" class="form-control form-control-sm mono"
                                value="KONTRAKAN — {{ $toWarehouse->name ?? 'Gudang FG' }}" disabled>
                            <input type="hidden" name="to_warehouse_id" value="{{ $toWarehouse->id }}">
                            <div class="help mt-1">
                                Stok barang jadi disimpan di gudang KONTRAKAN.
                            </div>
                        </div>


                        {{-- CATATAN HEADER --}}
                        <div class="col-12">
                            <label for="notes" class="form-label small">Catatan Umum</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control form-control-sm"
                                placeholder="Catatan umum (opsional)">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL TABLE --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small text-uppercase">
                        Detail Finishing / Packing
                    </span>
                    <small class="text-muted">
                        Centang baris yang dipacking. Isi Qty OK & Qty Reject. Qty WIP hanya informasi stok tersedia.
                    </small>
                </div>

                <div class="card-body p-0">
                    <div class="table-wrap">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        Pilih
                                    </th>
                                    <th style="width: 40px;">#</th>
                                    <th>Item (WIP)</th>
                                    <th class="text-end">Qty WIP Tersedia</th>
                                    <th class="text-end">Qty OK (Masuk FG)</th>
                                    <th class="text-end">Qty Reject</th>
                                    <th>Unit</th>
                                    <th>Item FG</th>
                                    <th>Catatan Baris</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stocks as $idx => $stock)
                                    @php
                                        $rowOld = old("lines.$idx", []);
                                        $qtyWip = (float) $stock->qty;
                                        $item = $stock->item;
                                        $itemName = $item?->name ?? '-';
                                    @endphp
                                    <tr>
                                        {{-- CHECKBOX --}}
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input line-checkbox"
                                                name="lines[{{ $idx }}][selected]" value="1"
                                                data-index="{{ $idx }}">
                                        </td>

                                        {{-- INDEX --}}
                                        <td class="mono">
                                            {{ $idx + 1 }}
                                        </td>

                                        {{-- ITEM WIP --}}
                                        <td>
                                            <div class="fw-semibold mono">
                                                {{ $stock->item_code }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $itemName }}
                                            </div>

                                            {{-- HIDDEN FIELD UNTUK SUBMIT --}}
                                            <input type="hidden" name="lines[{{ $idx }}][item_id]"
                                                value="{{ $stock->item_id }}">
                                            <input type="hidden" name="lines[{{ $idx }}][unit]"
                                                value="{{ $stock->unit }}">
                                            <input type="hidden" name="lines[{{ $idx }}][qty_wip]"
                                                value="{{ $qtyWip }}">
                                        </td>

                                        {{-- QTY WIP TERSEDIA --}}
                                        <td class="text-end mono">
                                            {{ number_format($qtyWip, 2, ',', '.') }}
                                        </td>

                                        {{-- QTY OK --}}
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0" max="{{ $qtyWip }}"
                                                name="lines[{{ $idx }}][qty_ok]"
                                                class="form-control form-control-sm text-end mono qty-ok-input"
                                                data-index="{{ $idx }}" value="{{ $rowOld['qty_ok'] ?? '' }}"
                                                placeholder="0,00">
                                        </td>

                                        {{-- QTY REJECT --}}
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0"
                                                max="{{ $qtyWip }}" name="lines[{{ $idx }}][qty_reject]"
                                                class="form-control form-control-sm text-end mono"
                                                value="{{ $rowOld['qty_reject'] ?? '' }}" placeholder="0,00">
                                        </td>

                                        {{-- UNIT --}}
                                        <td class="mono">
                                            {{ $stock->unit }}
                                        </td>

                                        {{-- ITEM FG (sementara sama dengan WIP, kalau mau mapping beda bisa dikembangkan) --}}
                                        <td>
                                            <div class="fw-semibold mono">
                                                {{ $stock->item_code }}
                                            </div>
                                            <div class="small text-muted">
                                                Default: sama dengan item WIP
                                            </div>
                                            <input type="hidden" name="lines[{{ $idx }}][fg_item_id]"
                                                value="{{ $stock->item_id }}">
                                        </td>

                                        {{-- CATATAN BARIS --}}
                                        <td>
                                            <input type="text" name="lines[{{ $idx }}][notes]"
                                                class="form-control form-control-sm" value="{{ $rowOld['notes'] ?? '' }}"
                                                placeholder="Catatan (opsional)">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            Tidak ada stok WIP-FIN yang tersedia untuk dipacking.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Hanya baris yang dicentang dan punya <span class="mono">Qty OK</span> / <span class="mono">Qty
                            Reject</span>
                        yang akan diproses. Baris lain diabaikan.
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('production.finishing.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i> Simpan Finishing & Posting
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            document.addEventListener('change', function(e) {
                // Kalau checkbox baris berubah → fokus ke Qty OK
                if (e.target.classList.contains('line-checkbox')) {
                    const idx = e.target.dataset.index;
                    const qtyInput = document.querySelector('.qty-ok-input[data-index="' + idx + '"]');
                    if (e.target.checked && qtyInput) {
                        qtyInput.focus();
                        qtyInput.select();
                    }
                }
            });
        })();
    </script>
@endpush
