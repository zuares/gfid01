@extends('layouts.app')
@section('title', 'Produksi • Setor Jahit')

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
                    Setor Hasil Jahit
                </h4>
                <div class="small text-muted">
                    Pilih operator, lalu isi hasil jahit (OK & Reject) berdasarkan dokumen Ambil Jahit.
                </div>
            </div>
            <div>
                <a href="{{ route('production.sewing_returns.index') }}" class="btn btn-outline-secondary btn-sm">
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

        {{-- FILTER OPERATOR (GET) --}}
        <div class="card mb-3">
            <div class="card-body small">
                <form method="get">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label for="operator_filter" class="form-label small">Operator / Penjahit</label>
                            <select name="operator_id" id="operator_filter" class="form-select form-select-sm">
                                <option value="">- Pilih Operator Jahit -</option>
                                @foreach ($operators as $op)
                                    <option value="{{ $op->id }}" @selected($operatorId == $op->id)>
                                        {{ $op->code ?? 'OP-' . $op->id }} — {{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-search me-1"></i> Tampilkan Ambil Jahit
                            </button>
                        </div>
                        <div class="col-md-4">
                            @if ($operator)
                                <div class="alert alert-info py-2 px-3 mb-0">
                                    Menampilkan ambil jahit untuk:
                                    <strong>{{ $operator->code ?? 'OP-' . $operator->id }} — {{ $operator->name }}</strong>
                                </div>
                            @else
                                <div class="help mb-0">
                                    Pilih operator lalu klik <strong>Tampilkan</strong> untuk melihat daftar ambil jahit.
                                </div>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- FORM SETOR JAHIT (POST) --}}
        <form method="post" action="{{ route('production.sewing_returns.store') }}">
            @csrf

            {{-- HEADER FORM --}}
            <div class="card mb-3">
                <div class="card-body small">
                    <div class="row g-3">
                        {{-- TANGGAL --}}
                        <div class="col-md-3">
                            <label for="date" class="form-label small">Tanggal Setor</label>
                            <input type="date" name="date" id="date" class="form-control form-control-sm"
                                value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        {{-- OPERATOR (read-only + hidden) --}}
                        <div class="col-md-3">
                            <label class="form-label small">Operator / Penjahit</label>
                            <input type="text" class="form-control form-control-sm"
                                value="{{ $operator ? ($operator->code ?? 'OP-' . $operator->id) . ' — ' . $operator->name : 'Pilih operator di atas' }}"
                                disabled>
                            <input type="hidden" name="operator_id" value="{{ $operatorId }}">
                            <div class="help mt-1">
                                Operator mengikuti pilihan di filter atas.
                            </div>
                        </div>

                        {{-- DARI GUDANG (Operator) --}}
                        <div class="col-md-3">
                            <label class="form-label small">Dari Gudang (Operator)</label>
                            <input type="text" class="form-control form-control-sm mono"
                                value="{{ $fromWarehouse ? $fromWarehouse->code . ' — ' . $fromWarehouse->name : 'Otomatis mengikuti gudang tujuan Ambil Jahit' }}"
                                disabled>
                            {{-- input hidden ini sudah tidak dipakai controller, hanya untuk display --}}
                            <input type="hidden" name="from_warehouse_id" value="{{ $fromWarehouse?->id }}">
                            <div class="help mt-1">
                                Mengikuti <span class="mono">to_warehouse</span> pada dokumen Ambil Jahit operator ini.
                            </div>
                        </div>

                        {{-- KE GUDANG (WIP-FIN) --}}
                        <div class="col-md-3">
                            <label class="form-label small">Ke Gudang (WIP Finishing)</label>
                            <input type="text" class="form-control form-control-sm mono"
                                value="{{ $toWarehouse->code . ' — ' . $toWarehouse->name }}" disabled>
                            {{-- sama, hidden ini hanya informasi, controller tidak membacanya --}}
                            <input type="hidden" name="to_warehouse_id" value="{{ $toWarehouse->id }}">
                            <div class="help mt-1">
                                Otomatis <span class="mono">WIP-FIN</span>.
                            </div>
                        </div>

                        {{-- CATATAN HEADER --}}
                        <div class="col-12">
                            <label for="notes" class="form-label small">Catatan Umum</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control form-control-sm"
                                placeholder="Catatan umum (opsional)">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- BIAYA SEWING --}}
                    <div class="row g-3 align-items-end">
                        {{-- TOTAL QTY OK (auto, display saja) --}}
                        <div class="col-md-3">
                            <label class="form-label small">Total Qty OK (dokumen ini)</label>
                            <div class="form-control form-control-sm mono bg-light-subtle">
                                <span id="totalOkDisplay">0,00</span> pcs
                            </div>
                            <div class="help mt-1">
                                Otomatis dijumlah dari kolom <span class="mono">Qty OK</span> baris yang dicentang.
                            </div>
                        </div>

                        {{-- SEWING RATE --}}
                        <div class="col-md-3">
                            <label for="sewing_rate" class="form-label small">Tarif Jahit per pcs</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text mono">@</span>
                                <input type="number" step="0.01" min="0" name="sewing_rate" id="sewing_rate"
                                    class="form-control form-control-sm mono text-end" value="{{ old('sewing_rate') }}"
                                    placeholder="0,00">
                            </div>
                            <div class="help mt-1">
                                Opsional. Jika diisi &amp; <span class="mono">Total Qty OK</span> &gt; 0,
                                maka <strong>Total Upah Sewing</strong> dihitung otomatis.
                            </div>
                        </div>

                        {{-- TOTAL SEWING FEE --}}
                        <div class="col-md-3">
                            <label for="sewing_fee" class="form-label small">Total Upah Sewing</label>
                            <input type="number" step="0.01" min="0" name="sewing_fee" id="sewing_fee"
                                class="form-control form-control-sm mono text-end" value="{{ old('sewing_fee') }}"
                                placeholder="0,00">
                            <div class="help mt-1">
                                Bisa diisi manual. Kalau kosong, akan diisi dari
                                <span class="mono">rate × Qty OK</span>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL TABLE --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small text-uppercase">
                        Detail Hasil Jahit
                    </span>
                    <small class="text-muted">
                        Centang baris yang disetor. Qty OK diisi manual (fokus otomatis ke kolom Qty OK saat dicentang).
                    </small>
                </div>

                <div class="card-body p-0">
                    <div class="table-wrap">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        Ambil
                                    </th>
                                    <th style="width: 40px;">#</th>
                                    <th>Kode Ambil Jahit</th>
                                    <th>Item / LOT</th>
                                    <th class="text-end">Qty Sisa di Penjahit</th>
                                    <th class="text-end">Qty OK (Setor)</th>
                                    <th class="text-end">Qty Reject</th>
                                    <th>Unit</th>
                                    <th>Catatan Baris</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pickLines as $idx => $line)
                                    @php
                                        $rowOld = old("lines.$idx", []);

                                        $picked = (float) $line->qty;
                                        $returned = (float) ($line->total_ok ?? 0) + (float) ($line->total_reject ?? 0);
                                        $remain = max(0, $picked - $returned);

                                        $stock = $line->stock ?? null; // kalau tidak eager load, akan lazy
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

                                        {{-- KODE PICK --}}
                                        <td>
                                            @if ($line->sewingPick)
                                                <div class="fw-semibold mono">
                                                    {{ $line->sewingPick->code }}
                                                </div>
                                                <div class="small text-muted">
                                                    Tgl:
                                                    {{ \Illuminate\Support\Carbon::parse($line->sewingPick->date)->format('d M Y') }}
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif

                                            <input type="hidden" name="lines[{{ $idx }}][sewing_pick_line_id]"
                                                value="{{ $line->id }}">
                                        </td>

                                        {{-- ITEM + LOT --}}
                                        <td>
                                            <div class="fw-semibold mono">{{ $line->item_code }}</div>
                                            <div class="small text-muted">
                                                LOT:
                                                @if ($stock && $stock->lot_id)
                                                    <span class="badge-lot mono">
                                                        #{{ $stock->lot_id }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">tanpa LOT</span>
                                                @endif
                                            </div>

                                            {{-- field ini tidak dipakai di controller, tapi tetap dikirim kalau suatu saat perlu --}}
                                            <input type="hidden" name="lines[{{ $idx }}][item_id]"
                                                value="{{ $line->item_id }}">
                                            <input type="hidden" name="lines[{{ $idx }}][item_code]"
                                                value="{{ $line->item_code }}">
                                            <input type="hidden" name="lines[{{ $idx }}][unit]"
                                                value="{{ $line->unit }}">
                                        </td>

                                        {{-- QTY SISA DI PENJAHIT --}}
                                        <td class="text-end mono">
                                            {{ number_format($remain, 2, ',', '.') }}
                                            <input type="hidden" name="lines[{{ $idx }}][max_remain]"
                                                value="{{ $remain }}">
                                        </td>

                                        {{-- QTY OK (manual, fokus kalau dicentang) --}}
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0"
                                                max="{{ $remain }}" name="lines[{{ $idx }}][qty_ok]"
                                                class="form-control form-control-sm text-end mono qty-ok-input"
                                                data-index="{{ $idx }}" value="{{ $rowOld['qty_ok'] ?? '' }}"
                                                placeholder="0,00">
                                        </td>

                                        {{-- QTY REJECT (manual) --}}
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0"
                                                max="{{ $remain }}" name="lines[{{ $idx }}][qty_reject]"
                                                class="form-control form-control-sm text-end mono"
                                                value="{{ $rowOld['qty_reject'] ?? '' }}" placeholder="0,00">
                                        </td>

                                        {{-- UNIT --}}
                                        <td class="mono">
                                            {{ $line->unit }}
                                        </td>

                                        {{-- NOTES --}}
                                        <td>
                                            <input type="text" name="lines[{{ $idx }}][notes]"
                                                class="form-control form-control-sm" value="{{ $rowOld['notes'] ?? '' }}"
                                                placeholder="Catatan (opsional)">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            @if ($operator)
                                                Tidak ada data ambil jahit yang masih punya sisa untuk operator ini.
                                            @else
                                                Pilih operator terlebih dahulu untuk melihat data ambil jahit.
                                            @endif
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
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i> Simpan Setoran Jahit
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
            let sewingFeeManual = false; // kalau user sudah edit total fee, jangan dioverride otomatis

            function parseNumber(val) {
                if (!val) return 0;
                return parseFloat(val) || 0;
            }

            function formatIdNumber(num) {
                // tampil rapi ala Indonesia, tapi simple aja
                try {
                    return num.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } catch (e) {
                    return num.toFixed(2);
                }
            }

            function recalcTotalOk() {
                const qtyInputs = document.querySelectorAll('.qty-ok-input');
                let totalOk = 0;

                qtyInputs.forEach(function(input) {
                    const idx = input.dataset.index;
                    const checkbox = document.querySelector('.line-checkbox[data-index="' + idx + '"]');
                    if (!checkbox || !checkbox.checked) {
                        return;
                    }
                    const val = parseNumber(input.value);
                    if (val > 0) {
                        totalOk += val;
                    }
                });

                const span = document.getElementById('totalOkDisplay');
                if (span) {
                    span.textContent = formatIdNumber(totalOk);
                    span.dataset.totalOk = totalOk; // simpan mentahnya
                }

                recalcSewingFee(); // setiap total OK berubah, coba hitung fee (kalau belum manual)
            }

            function recalcSewingFee() {
                if (sewingFeeManual) {
                    return; // user sudah edit manual, jangan diubah lagi
                }

                const span = document.getElementById('totalOkDisplay');
                const totalOk = span ? parseNumber(span.dataset.totalOk) : 0;

                const rateInput = document.getElementById('sewing_rate');
                const feeInput = document.getElementById('sewing_fee');

                if (!rateInput || !feeInput) return;

                const rate = parseNumber(rateInput.value);
                if (rate <= 0 || totalOk <= 0) {
                    // kalau tidak ada data cukup, biarkan kosong
                    return;
                }

                const fee = rate * totalOk;
                feeInput.value = fee.toFixed(2);
            }

            document.addEventListener('change', function(e) {
                // Kalau checkbox baris berubah
                if (e.target.classList.contains('line-checkbox')) {
                    const idx = e.target.dataset.index;
                    const qtyInput = document.querySelector('.qty-ok-input[data-index="' + idx + '"]');
                    if (e.target.checked && qtyInput) {
                        qtyInput.focus();
                        qtyInput.select();
                    }
                    recalcTotalOk();
                }

                // Kalau Qty OK berubah → hitung ulang total OK
                if (e.target.classList.contains('qty-ok-input')) {
                    recalcTotalOk();
                }

                // Kalau rate berubah → kita anggap boleh override fee (kecuali user sudah manual)
                if (e.target.id === 'sewing_rate') {
                    // reset flag manual supaya fee bisa dihitung ulang dari rate baru
                    sewingFeeManual = false;
                    recalcSewingFee();
                }
            });

            document.addEventListener('input', function(e) {
                // perubahan qty OK real-time (kalau mau)
                if (e.target.classList.contains('qty-ok-input')) {
                    recalcTotalOk();
                }

                // kalau user mengetik di fee → anggap manual
                if (e.target.id === 'sewing_fee') {
                    sewingFeeManual = true;
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                // inisialisasi: hitung total OK awal (kalau ada old input)
                recalcTotalOk();

                // kalau sudah ada old('sewing_fee') → tandai manual
                var feeInput = document.getElementById('sewing_fee');
                if (feeInput && feeInput.value) {
                    sewingFeeManual = true;
                }
            });
        })();
    </script>
@endpush
