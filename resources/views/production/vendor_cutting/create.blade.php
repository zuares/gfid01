@extends('layouts.app')
@section('title', 'Produksi • Vendor Cutting • ' . $t->code)

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

        .line-actions {
            white-space: nowrap;
        }

        .btn-icon {
            padding-inline: .45rem;
        }

        .badge-lot {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .75rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .10);
        }

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Proses Cutting</h4>
                <div class="small text-muted">
                    Dokumen: <span class="mono">{{ $t->code }}</span> • Status: <span
                        class="mono">{{ $t->status }}</span>
                </div>
            </div>
            <div>
                <a href="{{ route('vendor-cutting.index') }}" class="btn btn-outline-secondary btn-sm">
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

        {{-- INFO KIRIMAN --}}
        <div class="card mb-3">
            <div class="card-body small">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted mb-1">Tanggal Dokumen</div>
                        <div class="mono">{{ $t->date?->format('d M Y') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted mb-1">Operator / Vendor</div>
                        <div class="mono">{{ $t->operator_code ?: '—' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted mb-1">Gudang Tujuan (Lokasi Cutting)</div>
                        <div class="fw-semibold">
                            {{ $t->toWarehouse?->code ?? '—' }}
                        </div>
                        <div class="text-muted">
                            {{ $t->toWarehouse?->name ?? '' }}
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="text-muted mb-1">LOT Kain</div>
                        <div class="table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>LOT</th>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th>Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($t->lines as $ln)
                                        <tr>
                                            <td class="mono">
                                                {{ $ln->lot?->code ?? '—' }}
                                            </td>
                                            <td>
                                                <div class="mono">
                                                    {{ $ln->item?->code ?? '—' }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ $ln->item?->name ?? '' }}
                                                </div>
                                            </td>
                                            <td class="text-end mono">
                                                {{ number_format($ln->qty, 2, ',', '.') }}
                                            </td>
                                            <td class="mono">
                                                {{ $ln->uom }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-end">Total</th>
                                        <th class="text-end mono">
                                            {{ number_format($inputQty, 2, ',', '.') }}
                                        </th>
                                        <th class="mono">{{ $inputUom }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="help mt-1">
                            Total kain yang diterima vendor untuk dokumen ini.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FORM HASIL CUTTING --}}
        <form action="{{ route('vendor-cutting.store', $t) }}" method="post">
            @csrf

            {{-- HIDDEN: supaya validasi controller terpenuhi --}}
            <input type="hidden" name="input_qty" value="{{ old('input_qty', $inputQty) }}">
            <input type="hidden" name="input_uom" value="{{ old('input_uom', $inputUom) }}">

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Tanggal Proses Cutting</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ old('date', now()->toDateString()) }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Operator (opsional)</label>
                            <input type="text" name="operator_code" class="form-control form-control-sm mono"
                                value="{{ old('operator_code', $t->operator_code) }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Waste (rusak)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" name="waste_qty"
                                    class="form-control mono text-end" value="{{ old('waste_qty', 0) }}">
                                <span class="input-group-text mono">{{ $inputUom }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Sisa Kain Bagus</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" name="remain_qty"
                                    class="form-control mono text-end" value="{{ old('remain_qty', 0) }}">
                                <span class="input-group-text mono">{{ $inputUom }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Catatan</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Catatan hasil cutting...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold small text-uppercase">
                            Hasil Cutting (Barang Setengah Jadi)
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-output-line">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0" id="outputs-table">
                            <thead>
                                <tr>
                                    <th style="min-width: 220px;">Item Hasil</th>
                                    <th style="min-width: 120px;" class="text-end">Qty (pcs)</th>
                                    <th style="min-width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldResults = old('results', [['item_code' => null, 'qty' => null]]);
                                @endphp
                                @foreach ($oldResults as $idx => $out)
                                    <tr>
                                        <td>
                                            <select name="results[{{ $idx }}][item_code]"
                                                class="form-select form-select-sm" required>
                                                <option value="">Pilih item...</option>
                                                @foreach ($finishedItems as $fi)
                                                    <option value="{{ $fi->code }}"
                                                        {{ ($out['item_code'] ?? '') === $fi->code ? 'selected' : '' }}>
                                                        {{ $fi->code }} — {{ $fi->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="1" min="1"
                                                name="results[{{ $idx }}][qty]"
                                                class="form-control form-control-sm text-end mono"
                                                value="{{ $out['qty'] ?? '' }}">
                                        </td>
                                        <td class="line-actions text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-icon btn-remove-output-line">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="help mt-2">
                        Isi barang hasil cutting (misal: K7BLK, K5BLK) beserta jumlah pcs.
                        Minimal satu baris harus diisi. Total pcs akan dijumlahkan otomatis di server.
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="small text-muted">
                    Saat disimpan: bahan dianggap sudah diterima (jika sebelumnya status masih <span
                        class="mono">sent</span>) dan dokumen akan dianggap selesai cutting.
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        Simpan Hasil Cutting
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const finishedItems = @json($finishedItems);
            const tbody = document.querySelector('#outputs-table tbody');
            const addBtn = document.getElementById('btn-add-output-line');
            let outIndex = {{ count($oldResults ?? []) }};

            function buildItemOptions() {
                let html = '<option value="">Pilih item...</option>';
                finishedItems.forEach(fi => {
                    html += `<option value="${fi.code}">${fi.code} — ${fi.name}</option>`;
                });
                return html;
            }

            function addOutputRow() {
                const idx = outIndex++;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <select name="results[${idx}][item_code]"
                                class="form-select form-select-sm" required>
                            ${buildItemOptions()}
                        </select>
                    </td>
                    <td>
                        <input type="number"
                               step="1"
                               min="1"
                               name="results[${idx}][qty]"
                               class="form-control form-control-sm text-end mono"
                               value="">
                    </td>
                    <td class="line-actions text-center">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-icon btn-remove-output-line">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            }

            function initEvents() {
                addBtn?.addEventListener('click', () => {
                    addOutputRow();
                });

                tbody.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-remove-output-line')) {
                        const tr = e.target.closest('tr');
                        tr?.remove();
                    }
                });

                // Kalau tidak ada baris sama sekali, tambah 1 baris default
                if (tbody.children.length === 0) {
                    addOutputRow();
                }
            }

            document.addEventListener('DOMContentLoaded', initEvents);
        })();
    </script>
@endpush
