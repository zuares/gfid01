@extends('layouts.app')

@section('title', 'Produksi • Edit External Transfer • ' . $row->code)

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

        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 3px;
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

        .line-actions {
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }
        }

        .btn-icon {
            padding-inline: .45rem;
        }

        .badge-stock {
            border-radius: 999px;
            padding: .05rem .5rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Edit External Transfer</h4>
                <div class="text-muted small">
                    Dokumen: <span class="mono">{{ $row->code }}</span> • Status: <span
                        class="mono">{{ $row->status }}</span>
                </div>
            </div>
            <div>
                <a href="{{ route('external-transfers.show', $row) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

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

        <form action="{{ route('external-transfers.update', $row) }}" method="post">
            @csrf
            @method('put')

            {{-- HEADER --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label small required">Tanggal</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ old('date', $row->date?->toDateString()) }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small required">Dari Gudang</label>
                            <select name="from_warehouse_id" class="form-select form-select-sm">
                                <option value="">Pilih...</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}"
                                        {{ (int) old('from_warehouse_id', $row->from_warehouse_id) === $wh->id ? 'selected' : '' }}>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small required">Ke Gudang / Vendor</label>
                            <select name="to_warehouse_id" class="form-select form-select-sm">
                                <option value="">Pilih...</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}"
                                        {{ (int) old('to_warehouse_id', $row->to_warehouse_id) === $wh->id ? 'selected' : '' }}>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label small required">Proses</label>
                            @php $proc = old('process', $row->process); @endphp
                            <select name="process" class="form-select form-select-sm">
                                <option value="cutting" {{ $proc === 'cutting' ? 'selected' : '' }}>Cutting</option>
                                <option value="sewing" {{ $proc === 'sewing' ? 'selected' : '' }}>Sewing</option>
                                <option value="finishing" {{ $proc === 'finishing' ? 'selected' : '' }}>Finishing</option>
                                <option value="other" {{ $proc === 'other' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label small">Kode Operator / Vendor</label>
                            <input type="text" name="operator_code" class="form-control form-control-sm mono"
                                value="{{ old('operator_code', $row->operator_code) }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Catatan</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm">{{ old('notes', $row->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL LOT --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold small text-uppercase">Detail LOT</div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-line">
                            <i class="bi bi-plus-lg me-1"></i> Tambah LOT
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0" id="lines-table">
                            <thead>
                                <tr>
                                    <th style="min-width: 220px;">LOT</th>
                                    <th style="min-width: 160px;">Item</th>
                                    <th style="min-width: 80px;" class="text-end">Stok</th>
                                    <th style="min-width: 120px;" class="text-end">Qty Kirim</th>
                                    <th style="min-width: 80px;">Satuan</th>
                                    <th style="min-width: 150px;">Catatan</th>
                                    <th style="min-width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldLines = old('lines');
                                    $lines = $oldLines ?? $row->lines->toArray();
                                @endphp

                                @foreach ($lines as $idx => $ln)
                                    @php
                                        $lotId = $oldLines ? $ln['lot_id'] ?? null : $ln['lot_id'] ?? null;
                                        $itemId = $oldLines ? $ln['item_id'] ?? null : $ln['item_id'] ?? null;
                                        $qty = $oldLines
                                            ? $ln['qty'] ?? null
                                            : $ln['qty'] ?? ($row->lines[$idx]->qty ?? null);
                                        $uom = $oldLines
                                            ? $ln['uom'] ?? null
                                            : $ln['uom'] ?? ($row->lines[$idx]->uom ?? null);
                                        $lnNote = $oldLines
                                            ? $ln['notes'] ?? null
                                            : $ln['notes'] ?? ($row->lines[$idx]->notes ?? null);
                                        $lotObj = $row->lines[$idx]->lot ?? null;
                                        $itemObj = $row->lines[$idx]->item ?? null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <select name="lines[{{ $idx }}][lot_id]"
                                                class="form-select form-select-sm line-lot-select"
                                                data-line-index="{{ $idx }}">
                                                <option value="">Pilih LOT...</option>
                                                @foreach ($lots as $lot)
                                                    @php
                                                        $label =
                                                            $lot->lot_code .
                                                            ' — ' .
                                                            $lot->item_code .
                                                            ' ' .
                                                            $lot->item_name;
                                                    @endphp
                                                    <option value="{{ $lot->id }}" data-item-id="{{ $lot->item_id }}"
                                                        data-item-code="{{ $lot->item_code }}"
                                                        data-item-name="{{ $lot->item_name }}"
                                                        data-uom="{{ $lot->uom }}"
                                                        data-stock="{{ $lot->stock_remain }}"
                                                        {{ (int) $lotId === (int) $lot->id ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="mono small line-item-label">
                                                {{ $itemObj?->code ?? '' }} {{ $itemObj?->name ?? '' }}
                                            </div>
                                            <input type="hidden" name="lines[{{ $idx }}][item_id]"
                                                class="line-item-id" value="{{ $itemId }}">
                                        </td>
                                        <td class="text-end">
                                            <span class="badge-stock mono line-stock">0</span>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                name="lines[{{ $idx }}][qty]"
                                                class="form-control form-control-sm text-end mono"
                                                value="{{ $qty }}">
                                        </td>
                                        <td>
                                            <input type="text" name="lines[{{ $idx }}][uom]"
                                                class="form-control form-control-sm text-center mono line-uom"
                                                value="{{ $uom }}">
                                        </td>
                                        <td>
                                            <input type="text" name="lines[{{ $idx }}][notes]"
                                                class="form-control form-control-sm" value="{{ $lnNote }}">
                                        </td>
                                        <td class="line-actions text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-icon btn-remove-line">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="help mt-2">
                        Sesuaikan LOT dan qty kirim jika diperlukan. Dokumen hanya bisa diedit selama status masih draft.
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="small text-muted">
                    Setelah disimpan, status tetap <span class="mono">draft</span> sampai kamu klik tombol
                    <em>Kirim</em>.
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const lots = @json($lots);
            const tbody = document.querySelector('#lines-table tbody');
            const addBtn = document.getElementById('btn-add-line');
            let lineIndex = {{ isset($lines) ? count($lines) : 0 }};

            function buildLotOptions() {
                let html = '<option value="">Pilih LOT...</option>';
                lots.forEach(lot => {
                    const label = `${lot.lot_code} — ${lot.item_code} ${lot.item_name}`;
                    html += `
                        <option value="${lot.id}"
                                data-item-id="${lot.item_id}"
                                data-item-code="${lot.item_code}"
                                data-item-name="${lot.item_name}"
                                data-uom="${lot.uom}"
                                data-stock="${lot.stock_remain}">
                            ${label}
                        </option>
                    `;
                });
                return html;
            }

            function addLineRow() {
                const idx = lineIndex++;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <select name="lines[${idx}][lot_id]"
                                class="form-select form-select-sm line-lot-select"
                                data-line-index="${idx}">
                            ${buildLotOptions()}
                        </select>
                    </td>
                    <td>
                        <div class="mono small line-item-label">—</div>
                        <input type="hidden"
                               name="lines[${idx}][item_id]"
                               class="line-item-id"
                               value="">
                    </td>
                    <td class="text-end">
                        <span class="badge-stock mono line-stock">0</span>
                    </td>
                    <td>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="lines[${idx}][qty]"
                               class="form-control form-control-sm text-end mono"
                               value="">
                    </td>
                    <td>
                        <input type="text"
                               name="lines[${idx}][uom]"
                               class="form-control form-control-sm text-center mono line-uom"
                               value="">
                    </td>
                    <td>
                        <input type="text"
                               name="lines[${idx}][notes]"
                               class="form-control form-control-sm"
                               value="">
                    </td>
                    <td class="line-actions text-center">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-icon btn-remove-line">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            }

            function onLotChange(selectEl) {
                const tr = selectEl.closest('tr');
                const opt = selectEl.selectedOptions[0];
                const itemId = opt?.dataset.itemId || '';
                const itemCode = opt?.dataset.itemCode || '';
                const itemName = opt?.dataset.itemName || '';
                const uom = opt?.dataset.uom || '';
                const stock = opt?.dataset.stock ?? '';

                const itemLabelEl = tr.querySelector('.line-item-label');
                const itemIdInput = tr.querySelector('.line-item-id');
                const uomInput = tr.querySelector('.line-uom');
                const stockBadge = tr.querySelector('.line-stock');

                if (itemId) {
                    itemLabelEl.textContent = `${itemCode} ${itemName}`;
                    itemIdInput.value = itemId;
                    uomInput.value = uom;
                    stockBadge.textContent = stock;
                } else {
                    itemLabelEl.textContent = '—';
                    itemIdInput.value = '';
                    uomInput.value = '';
                    stockBadge.textContent = '0';
                }
            }

            function initEvents() {
                addBtn?.addEventListener('click', () => {
                    addLineRow();
                });

                tbody.addEventListener('change', function(e) {
                    if (e.target.classList.contains('line-lot-select')) {
                        onLotChange(e.target);
                    }
                });

                tbody.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-remove-line')) {
                        const tr = e.target.closest('tr');
                        tr?.remove();
                    }
                });

                // Inisialisasi baris existing
                tbody.querySelectorAll('.line-lot-select').forEach(sel => {
                    onLotChange(sel);
                });

                // Kalau tidak ada baris sama sekali, tambah 1
                if (tbody.children.length === 0) {
                    addLineRow();
                }
            }

            document.addEventListener('DOMContentLoaded', initEvents);
        })();
    </script>
@endpush
