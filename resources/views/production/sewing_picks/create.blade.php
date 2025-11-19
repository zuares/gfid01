@extends('layouts.app')
@section('title', 'Produksi • Ambil Jahit')

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

        .badge-lot {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .75rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .10);
        }

        .table-wrap {
            overflow-x: auto;
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
                <h4 class="mb-1">Ambil Jahit</h4>
                <div class="small text-muted">
                    Ambil bundle hasil cutting (qty OK) dari WIP-SEW untuk diberikan ke operator jahit.
                </div>
            </div>
            <div>
                <a href="{{ route('production.sewing_picks.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        {{-- ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger small">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM AMBIL JAHIT --}}
        <form action="{{ route('production.sewing_picks.store') }}" method="post">
            @csrf

            {{-- HEADER DOKUMEN --}}
            <div class="card mb-3">
                <div class="card-body small">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Tanggal Ambil Jahit</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label small">Operator Jahit</label>
                            <select name="operator_id" class="form-select form-select-sm" required>
                                <option value="">- Pilih Operator Jahit -</option>
                                @foreach ($operators as $op)
                                    <option value="{{ $op->id }}" @selected(old('operator_id') == $op->id)>
                                        {{ $op->code ?? 'OP-' . $op->id }} — {{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="help mt-1">
                                Gudang tujuan akan otomatis dibuat dengan kode:
                                <span class="mono">SEW-EXT-[KODE OP]</span>.
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small">Dari Gudang</label>
                            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                                <input type="text" class="form-control form-control-sm mono"
                                    value="{{ $wipWarehouse->code }} — {{ $wipWarehouse->name }}" disabled>
                                <input type="hidden" name="from_warehouse_id" value="{{ $wipWarehouse->id }}">
                            </div>
                            <div class="help mt-1">
                                Selalu dari gudang <span class="mono">WIP-SEW</span> (hasil cutting siap jahit).
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Catatan (opsional)</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm"
                                placeholder="Catatan untuk dokumen ambil jahit...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL BUNDLE --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold small text-uppercase">
                            Bundle Cutting Siap Jahit (status QC OK)
                        </div>
                        <div class="help">
                            Centang bundle yang diambil. Qty ambil otomatis = Qty OK (boleh dikurangi).
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="check-all-picks">
                                    </th>
                                    <th style="width: 40px;">#</th>
                                    <th>Bundle</th>
                                    <th>Item</th>
                                    <th>LOT</th>
                                    <th class="text-end">Qty OK</th>
                                    <th class="text-end">Qty Ambil</th>
                                    <th>Unit</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bundles as $i => $bundle)
                                    @php
                                        $oldLine = old('lines.' . $i, []);
                                        $qtyOk = (float) ($bundle->qty_ok ?? 0);
                                        $lotCode = optional($bundle->lot)->code ?? '—';
                                        $batchCode = optional($bundle->batch)->code ?? null;
                                    @endphp
                                    <tr>
                                        {{-- CHECKBOX --}}
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input pick-checkbox"
                                                name="lines[{{ $i }}][selected]" value="1"
                                                data-index="{{ $i }}" data-qty="{{ $qtyOk }}"
                                                @checked(!empty($oldLine['selected']))>
                                        </td>

                                        <td class="mono">
                                            {{ $i + 1 }}
                                        </td>

                                        {{-- BUNDLE --}}
                                        <td>
                                            <div class="mono">
                                                {{ $bundle->bundle_code ?? 'BND-' . $bundle->id }}
                                            </div>
                                            <div class="small text-muted">
                                                Batch: {{ $batchCode ?? '-' }}
                                            </div>

                                            {{-- Hidden bundle_id --}}
                                            <input type="hidden" name="lines[{{ $i }}][bundle_id]"
                                                value="{{ $bundle->id }}">
                                        </td>

                                        {{-- ITEM --}}
                                        <td>
                                            <div class="mono">{{ $bundle->item_code }}</div>
                                            <div class="small text-muted">
                                                {{ $bundle->item->name ?? '—' }}
                                            </div>

                                            <input type="hidden" name="lines[{{ $i }}][item_id]"
                                                value="{{ $bundle->item_id }}">
                                            <input type="hidden" name="lines[{{ $i }}][item_code]"
                                                value="{{ $bundle->item_code }}">
                                            <input type="hidden" name="lines[{{ $i }}][unit]"
                                                value="{{ $bundle->unit }}">
                                        </td>

                                        {{-- LOT --}}
                                        <td>
                                            <span class="badge-lot mono">
                                                {{ $lotCode }}
                                            </span>
                                        </td>

                                        {{-- QTY OK (tersedia) --}}
                                        <td class="text-end mono text-muted">
                                            {{ number_format($qtyOk, 2, ',', '.') }}
                                        </td>

                                        {{-- QTY AMBIL --}}
                                        <td class="text-end">
                                            <input type="number" name="lines[{{ $i }}][qty]" step="0.01"
                                                min="0" max="{{ $qtyOk }}"
                                                class="form-control form-control-sm text-end mono qty-input"
                                                data-index="{{ $i }}" data-available="{{ $qtyOk }}"
                                                placeholder="0,00" value="{{ $oldLine['qty'] ?? '' }}">
                                        </td>

                                        {{-- UNIT --}}
                                        <td class="mono">
                                            {{ $bundle->unit }}
                                        </td>

                                        {{-- CATATAN LINE --}}
                                        <td>
                                            <input type="text" name="lines[{{ $i }}][notes]"
                                                class="form-control form-control-sm"
                                                placeholder="Catatan baris (opsional)"
                                                value="{{ $oldLine['notes'] ?? '' }}">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            Tidak ada bundle cutting (status QC OK) yang siap dijahit.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="help mt-2">
                        Baris yang <strong>tidak dicentang</strong> akan diabaikan & tidak menyebabkan error.
                        Qty ambil otomatis = Qty OK saat dicentang / Select All, tapi boleh dikurangi.
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="small text-muted">
                    Saat disimpan: stok item K7BLK / dst akan dipindahkan dari <span class="mono">WIP-SEW</span>
                    ke gudang <span class="mono">SEW-EXT-[KODE OP]</span> sesuai bundle yang diambil.
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Ambil Jahit
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {

            function setQtyForRow(idx, qty) {
                const input = document.querySelector('input.qty-input[data-index="' + idx + '"]');
                if (!input) return;
                input.value = qty > 0 ? qty : '';
            }

            // Checkbox per baris
            function onRowCheckboxChange(e) {
                const cb = e.target;
                if (!cb.classList.contains('pick-checkbox')) return;

                const idx = cb.dataset.index;
                const rowQty = parseFloat(cb.dataset.qty || '0');

                if (cb.checked) {
                    setQtyForRow(idx, rowQty);
                } else {
                    setQtyForRow(idx, 0);
                }
            }

            // Checkbox header (select all)
            function onHeaderCheckboxChange(e) {
                const headerCb = e.target;
                if (headerCb.id !== 'check-all-picks') return;

                const checked = headerCb.checked;
                const rowCheckboxes = document.querySelectorAll('.pick-checkbox');

                rowCheckboxes.forEach(cb => {
                    cb.checked = checked;
                    const idx = cb.dataset.index;
                    const rowQty = parseFloat(cb.dataset.qty || '0');
                    if (checked) {
                        setQtyForRow(idx, rowQty);
                    } else {
                        setQtyForRow(idx, 0);
                    }
                });
            }

            document.addEventListener('change', function(e) {
                onRowCheckboxChange(e);
                onHeaderCheckboxChange(e);
            });
        })();
    </script>
@endpush
