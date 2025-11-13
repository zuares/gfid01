{{-- resources/views/production/external/create.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h4 class="mb-3">Buat External Transfer (Makloon)</h4>

        <form method="POST" action="{{ route('production.external.store') }}">
            @csrf

            {{-- === HEADER INFO === --}}
            <div class="card mb-4">
                <div class="card-body row g-3">

                    {{-- PROSES --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Proses</label>
                        <select name="process" class="form-select" id="process-select" required>
                            <option value="cutting">Cutting</option>
                            <option value="sewing">Sewing</option>
                        </select>
                    </div>

                    {{-- OPERATOR --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Operator (Makloon)</label>
                        <select name="operator_code" class="form-select" id="operator-select" required>
                            @forelse ($employees as $e)
                                <option value="{{ $e->code }}">{{ $e->code }} — {{ $e->name }}</option>
                            @empty
                                <option disabled>⚠️ Belum ada data karyawan — jalankan seeder</option>
                            @endforelse
                        </select>
                    </div>

                    {{-- TANGGAL (hanya tampilan, controller pakai now()) --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}"
                            required>
                    </div>
                    {{-- GUDANG ASAL --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Gudang Asal</label>

                        <select name="from_warehouse_id" id="from-warehouse-select" class="form-select" required>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(($fromWarehouseId ?? '') == $w->id)>
                                    {{ $w->code }} — {{ $w->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="form-text">
                            Mengubah gudang asal akan memfilter daftar LOT di bawah.
                        </div>
                    </div>



                    {{-- GUDANG TUJUAN (READONLY) --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Gudang Tujuan (Eksternal)</label>

                        {{-- tampil readonly --}}
                        <input type="text" id="to-warehouse-display" class="form-control mb-1" disabled>

                        {{-- hidden untuk dikirim ke controller --}}
                        <input type="hidden" name="to_warehouse_code" id="to-warehouse-code">

                        <div class="form-text">
                            Otomatis: <code>CUT-EXT-EMPCODE</code>.<br>
                        </div>
                    </div>

                    {{-- CATATAN --}}
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Catatan</label>
                        <input type="text" name="note" class="form-control" placeholder="Catatan tambahan (opsional)">
                    </div>
                </div>
            </div>

            {{-- === TABLE LOTS === --}}
            <h6 class="fw-semibold mb-2">Pilih LOT yang akan dikirim</h6>

            <div class="table-responsive border rounded">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px" class="text-center"></th>
                            <th>LOT</th>
                            <th>Item</th>
                            <th>UOM</th>
                            <th class="text-end" style="width:140px">Qty LOT</th>
                            <th style="width:140px">Qty Kirim</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @if ($lots->isEmpty())
                            {{-- === TIDAK ADA LOT === --}}
                            <tr>
                                <td colspan="7" class="text-center py-4">

                                    <div class="d-flex flex-column justify-content-center align-items-center py-2">

                                        <div class="text-warning" style="font-size:2rem;">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        </div>

                                        <div class="fw-semibold mt-2">
                                            Tidak ada LOT tersedia di gudang asal ini
                                        </div>

                                        <div class="text-muted small mt-1">
                                            Pilih gudang asal lain atau lakukan mutasi stok ke gudang ini.
                                        </div>

                                    </div>

                                </td>
                            </tr>
                        @else
                            {{-- === LOT ADA === --}}
                            @foreach ($lots as $l)
                                <tr>
                                    {{-- CHECKBOX --}}
                                    <td class="text-center align-middle">
                                        <input type="checkbox" class="form-check-input row-check"
                                            data-row="{{ $l->id }}">
                                    </td>

                                    {{-- LOT CODE --}}
                                    <td class="align-middle fw-semibold">
                                        {{ $l->lot_code }}
                                    </td>

                                    {{-- ITEM --}}
                                    <td class="align-middle">
                                        {{ $l->item_name }}
                                    </td>

                                    {{-- UOM --}}
                                    <td class="align-middle text-uppercase">
                                        {{ $l->uom }}
                                    </td>

                                    {{-- QTY LOT --}}
                                    @php
                                        $qtyDisplay = rtrim(
                                            rtrim(number_format($l->initial_qty, 4, '.', ''), '0'),
                                            '.',
                                        );
                                        $isZero = (float) $l->initial_qty <= 0;
                                    @endphp

                                    <td class="align-middle text-end {{ $isZero ? 'text-danger fw-bold' : '' }}">
                                        {{ $qtyDisplay }}
                                        @if ($isZero)
                                            <span class="badge bg-danger-subtle text-danger ms-1">Kosong</span>
                                        @endif
                                    </td>

                                    {{-- QTY KIRIM --}}
                                    <td class="align-middle">
                                        <input type="number" step="0.0001" class="form-control form-control-sm qty-input"
                                            data-row="{{ $l->id }}" data-available="{{ $l->initial_qty }}"
                                            placeholder="{{ $isZero ? 'Tidak bisa' : '0' }}"
                                            {{ $isZero ? 'disabled' : '' }}>
                                    </td>

                                    {{-- CATATAN --}}
                                    <td class="align-middle">
                                        <input type="text" class="form-control form-control-sm note-input"
                                            data-row="{{ $l->id }}" placeholder="Keterangan"
                                            {{ $isZero ? 'disabled' : '' }}>
                                    </td>

                                    {{-- Hidden --}}
                                    <input type="hidden" name="lines[{{ $l->id }}][lot_id]"
                                        value="{{ $l->id }}" disabled>
                                    <input type="hidden" name="lines[{{ $l->id }}][item_id]"
                                        value="{{ $l->item_id ?? $l->id }}" disabled>
                                    <input type="hidden" name="lines[{{ $l->id }}][qty]"
                                        class="qty-hidden-{{ $l->id }}" disabled>
                                    <input type="hidden" name="lines[{{ $l->id }}][unit]"
                                        value="{{ $l->uom }}" disabled>
                                    <input type="hidden" name="lines[{{ $l->id }}][note]"
                                        class="note-hidden-{{ $l->id }}" disabled>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- === SUBMIT BUTTON === --}}
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save"></i> Simpan (Draft)
                </button>
            </div>
        </form>
    </div>
    <script>
        // === Auto set gudang tujuan: PROSES-EXT-EMPCODE ===
        const processSelect = document.getElementById('process-select');
        const operatorSelect = document.getElementById('operator-select');
        const toWhDisplay = document.getElementById('to-warehouse-display');
        const toWhHidden = document.getElementById('to-warehouse-code');

        function syncWarehouse() {
            if (!processSelect || !operatorSelect || !toWhDisplay || !toWhHidden) return;

            const emp = operatorSelect.value;
            const proc = processSelect.value;

            if (!emp || !proc) return;

            const prefix = proc === 'sewing' ? 'SEW' : 'CUT';
            const code = `${prefix}-EXT-${emp}`;

            toWhDisplay.value = code;
            toWhHidden.value = code;
        }

        if (processSelect && operatorSelect) {
            processSelect.addEventListener('change', syncWarehouse);
            operatorSelect.addEventListener('change', syncWarehouse);
            window.addEventListener('DOMContentLoaded', syncWarehouse);
        }

        // === Ganti gudang asal → reload halaman dengan query ?from_warehouse_id= ===
        const fromWhSelect = document.getElementById('from-warehouse-select');
        if (fromWhSelect) {
            fromWhSelect.addEventListener('change', function(e) {
                const whId = e.target.value;
                const url = new URL("{{ route('production.external.create') }}", window.location.origin);
                const current = new URL(window.location.href);

                // pertahankan query yang lain kalau ada (misal nanti nambah filter)
                current.searchParams.forEach((value, key) => {
                    if (key !== 'from_warehouse_id') {
                        url.searchParams.set(key, value);
                    }
                });

                url.searchParams.set('from_warehouse_id', whId);
                window.location = url.toString();
            });
        }

        // === Checkbox: aktifkan / matikan hidden inputs per baris + auto isi qty ===
        document.querySelectorAll('.row-check').forEach(cb => {
            const id = cb.dataset.row;

            const toggleHidden = (on) => {
                document.querySelectorAll([
                    `input[name="lines[${id}][lot_id]"]`,
                    `input[name="lines[${id}][item_id]"]`,
                    `input[name="lines[${id}][qty]"]`,
                    `input[name="lines[${id}][unit]"]`,
                    `input[name="lines[${id}][note]"]`
                ].join(',')).forEach(inp => inp.disabled = !on);
            };

            cb.addEventListener('change', e => {
                const checked = e.target.checked;
                toggleHidden(checked);

                const qtyInput = document.querySelector(`.qty-input[data-row="${id}"]`);
                const qtyHidden = document.querySelector(`.qty-hidden-${id}`);
                const noteInput = document.querySelector(`.note-input[data-row="${id}"]`);
                const noteHidden = document.querySelector(`.note-hidden-${id}`);

                if (checked) {
                    if (qtyInput && qtyHidden) {
                        const available = qtyInput.dataset.available;
                        if (typeof available !== 'undefined') {
                            qtyInput.value = available;
                            qtyHidden.value = available;
                        }
                    }
                } else {
                    if (qtyInput) qtyInput.value = '';
                    if (qtyHidden) qtyHidden.value = '';
                    if (noteInput) noteInput.value = '';
                    if (noteHidden) noteHidden.value = '';
                }
            });
        });

        // === Sinkron qty input → hidden field (kalau user mau edit manual) ===
        document.querySelectorAll('.qty-input').forEach(inp => {
            inp.addEventListener('input', e => {
                const id = e.target.dataset.row;
                const value = e.target.value;
                const hidden = document.querySelector(`.qty-hidden-${id}`);
                if (hidden) hidden.value = value;
            });
        });

        // === Sinkron note input → hidden field ===
        document.querySelectorAll('.note-input').forEach(inp => {
            inp.addEventListener('input', e => {
                const id = e.target.dataset.row;
                const value = e.target.value;
                const hidden = document.querySelector(`.note-hidden-${id}`);
                if (hidden) hidden.value = value;
            });
        });
    </script>
@endsection
