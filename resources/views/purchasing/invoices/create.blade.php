@extends('layouts.app')
@section('title', 'Pembelian • Invoice Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"
        }

        .help {
            color: var(--muted);
            font-size: .85rem
        }

        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 3px
        }

        thead th {
            background: var(--card);
            position: sticky;
            top: 0;
            z-index: 1
        }

        /* Autocomplete */
        .ac-wrap {
            position: relative
        }

        .ac-input.form-control {
            padding-right: 2.25rem
        }

        .btn-inline {
            position: absolute;
            right: .45rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2
        }

        .ac-menu {
            position: absolute;
            inset-inline: 0;
            top: 100%;
            z-index: 30;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            margin-top: 4px;
            max-height: 300px;
            overflow: auto;
            box-shadow: 0 10px 34px rgba(0, 0, 0, .14)
        }

        .ac-item {
            padding: .55rem .7rem;
            display: grid;
            grid-template-columns: 112px 1fr auto;
            gap: .55rem;
            cursor: pointer;
            align-items: center
        }

        .ac-item:hover,
        .ac-item.active {
            background: color-mix(in srgb, var(--bs-primary) 10%, transparent)
        }

        .ac-code {
            font-weight: 700
        }

        .ac-unit {
            font-size: .8rem;
            color: var(--muted)
        }

        .ac-empty {
            padding: .55rem .7rem;
            color: var(--muted);
            font-size: .9rem
        }

        #table-lines td {
            vertical-align: middle
        }

        /* Unit chip (desktop) */
        .unit-chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .25rem .6rem;
            font-size: .9rem;
            background: color-mix(in srgb, var(--bs-primary) 6%, transparent)
        }

        /* Footer total */
        tfoot .totals {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            align-items: center
        }

        tfoot .totals .label {
            color: var(--muted)
        }

        tfoot .totals .value {
            min-width: 140px;
            text-align: right
        }

        /* Minimal nav hint (DISABLED) */
        .nav-hint {
            display: none;
        }

        /* Layout kolom utk flex di mobile */
        #table-lines tbody tr .col-item,
        #table-lines tbody tr .col-qty,
        #table-lines tbody tr .col-price,
        #table-lines tbody tr .col-subtotal,
        #table-lines tbody tr .col-actions {
            /* placeholder class */
        }

        @media (max-width: 767.98px) {

            /* Sembunyikan header di mobile */
            #table-lines thead {
                display: none;
            }

            #table-lines tbody tr {
                display: flex;
                flex-wrap: wrap;
                border-bottom: 1px dashed color-mix(in srgb, var(--line) 80%, transparent 20%);
                margin-bottom: .65rem;
                padding-bottom: .45rem;
            }

            #table-lines tbody td {
                border: 0 !important;
                padding: .15rem .15rem;
            }

            .col-item {
                order: 1;
                flex: 0 0 60%;
            }

            .col-qty {
                order: 1;
                flex: 0 0 30%;
            }

            .col-actions {
                order: 1;
                flex: 0 0 10%;
                text-align: right;
                align-self: flex-start;
            }

            .col-price {
                order: 2;
                flex: 0 0 60%;
                margin-top: .25rem;
            }

            .col-subtotal {
                order: 2;
                flex: 0 0 40%;
                margin-top: .25rem;
                text-align: right;
            }

            .subtotal-desktop {
                display: none !important;
            }

            .subtotal-mobile {
                display: block;
                font-size: .9rem;
            }

            .col-unit {
                display: none !important;
            }

            #table-lines .form-control {
                font-size: .85rem;
                padding-block: .25rem;
            }

            #table-lines .input-group-text {
                padding-inline: .4rem;
                font-size: .8rem;
            }

            .btn-del {
                padding: .15rem .3rem;
                font-size: .75rem;
            }
        }

        @media (min-width: 768px) {
            .subtotal-mobile {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container py-3 page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Pembelian • Invoice Baru</h5>
            <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Periksa input:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('purchasing.invoices.store') }}" method="POST" id="form-purchase" autocomplete="off">
            @csrf
            <input type="hidden" name="_idem"
                value="{{ old('_idem', $defaults['_idem'] ?? 'IDEM-' . now()->format('YmdHis')) }}">

            {{-- HEADER --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Jenis Item</label>
                            @php
                                $types = [
                                    '' => '— Semua —',
                                    'material' => 'Bahan Baku',
                                    'pendukung' => 'Bahan Pendukung',
                                    'finished' => 'Barang Jadi',
                                ];
                            @endphp
                            <select id="filter_type" class="form-select">
                                @foreach ($types as $val => $label)
                                    <option value="{{ $val }}" @selected(old('filter_type', $filterType) === $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label required">Supplier</label>
                            <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id"
                                id="supplier_id" required>
                                <option value="">— Pilih Supplier —</option>
                                @foreach ($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @php
                            $warehouses = \App\Models\Warehouse::orderBy('name')->get(['id', 'name', 'code']);
                        @endphp

                        <div class="col-6 col-md-3">
                            <label class="form-label required">Gudang</label>
                            <select id="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
                                <option value="">— Pilih Gudang —</option>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id', $kontrakanId) == $w->id)>
                                        {{ $w->name }} ({{ $w->code }})
                                    </option>
                                @endforeach
                            </select>
                            {{-- hidden yang benar-benar dikirim ke server --}}
                            <input type="hidden" name="warehouse_id" id="warehouse_id_hidden"
                                value="{{ old('warehouse_id', $kontrakanId) }}">
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label required">Tanggal</label>
                            <input type="date" name="date" id="date"
                                value="{{ old('date', now('Asia/Jakarta')->toDateString()) }}"
                                class="form-control @error('date') is-invalid @enderror" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Detail Pembelian</strong>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="add-5">+5</button>
                            <button type="button" class="btn btn-primary btn-sm" id="add-line">
                                <i class="bi bi-plus"></i> Tambah Baris
                            </button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="table align-middle" id="table-lines">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="width:12%">Qty</th>
                                    <th style="width:12%" class="col-unit">Unit</th>
                                    <th style="width:18%">Harga</th>
                                    <th style="width:16%">Subtotal</th>
                                    <th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6">
                                        <div class="totals">
                                            <div class="label">Biaya Lain</div>
                                            <div class="input-group" style="max-width:220px">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control text-end" id="other_costs_view"
                                                    inputmode="decimal" placeholder="0"
                                                    value="{{ old('other_costs', 0) }}">
                                                <input type="hidden" name="other_costs" id="other_costs"
                                                    value="{{ old('other_costs', 0) }}">
                                            </div>
                                            <div class="label">Grand Total</div>
                                            <div class="value mono" id="grand-total">0</div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- nav-hint disembunyikan --}}
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-success" id="btn-submit">Simpan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const itemsAll = @json($itemsAll); // item: id, code, name, unit, type
            const filterSel = document.getElementById('filter_type');
            const supplierSel = document.getElementById('supplier_id');
            const warehouseSel = document.getElementById('warehouse_id');
            const warehouseHidden = document.getElementById('warehouse_id_hidden');
            const warehouses = @json($warehouses);
            const rawWarehouse = warehouses.find(w => w.code === 'RAW') || null;

            const tbody = document.querySelector('#table-lines tbody');
            const totalView = document.getElementById('grand-total');
            const ocView = document.getElementById('other_costs_view');
            const ocHidden = document.getElementById('other_costs');
            const btnAdd = document.getElementById('add-line');
            const btnAdd5 = document.getElementById('add-5');
            const form = document.getElementById('form-purchase');
            const btnSubmit = document.getElementById('btn-submit');

            const rupiah = (n) => (window.App?.formatRupiah ?
                window.App.formatRupiah(n) :
                (Number(n || 0)).toLocaleString('id-ID'));

            const parseNum = (v) => (window.App?.parseNumberId ?
                window.App.parseNumberId(v) :
                (parseFloat(String(v ?? '')
                    .replace(/\s+/g, '')
                    .replace(/\./g, '')
                    .replace(',', '.')) || 0));

            const sanitize = (el) => el.value = el.value.replace(/[^0-9.,]/g, '');

            const getFilteredItems = () => {
                const t = filterSel.value;
                return t ? itemsAll.filter(i => i.type === t) : itemsAll;
            };

            // ==== Lock gudang ke RAW kalau jenis material ====
            function syncWarehouseHidden() {
                if (warehouseHidden && warehouseSel) {
                    warehouseHidden.value = warehouseSel.value || '';
                }
            }

            function updateWarehouseByType() {
                if (!warehouseSel) return;
                const t = filterSel.value;
                if (t === 'material' && rawWarehouse) {
                    warehouseSel.value = String(rawWarehouse.id);
                    syncWarehouseHidden();
                    warehouseSel.disabled = true;
                } else {
                    warehouseSel.disabled = false;
                }
            }

            if (warehouseSel) {
                warehouseSel.addEventListener('change', syncWarehouseHidden);
                syncWarehouseHidden();
            }

            // ==== AJAX last price ====
            async function fetchLastPrice({
                itemId,
                supplierId
            }) {
                if (!itemId || !supplierId) return null;
                const url = new URL(`{{ route('purchasing.invoices.ajax.last_price') }}`, window.location.origin);
                url.searchParams.set('supplier_id', supplierId);
                url.searchParams.set('item_id', itemId);
                const res = await fetch(url);
                if (!res.ok) return null;
                const js = await res.json().catch(() => null);
                if (js && js.success && js.data) return js.data;
                return null;
            }

            // ==== Hitung total dari nilai HIDDEN ====
            const calcLines = () => {
                let t = 0;
                document.querySelectorAll('.line-row').forEach(tr => {
                    const q = Number(tr.querySelector('.qty-val').value || 0);
                    const p = Number(tr.querySelector('.price-val').value || 0);
                    t += q * p;
                });
                return t;
            };

            function updateTotals() {
                const oc = parseNum(ocView.value);
                const gt = Math.max(0, calcLines() + oc);
                totalView.textContent = rupiah(gt);
            }

            // ==== Tambah baris ====
            function addLine(prefill = null) {
                const idx = Date.now() + Math.floor(Math.random() * 999);
                const tr = document.createElement('tr');
                tr.classList.add('line-row');
                tr.innerHTML =
                    `
<td class="col-item">
  <div class="ac-wrap">
    <input type="text" class="form-control ac-input" placeholder="Ketik kode/nama">
    <button type="button" class="btn btn-outline-secondary btn-sm btn-inline btn-history" title="Harga terakhir">
      <i class="bi bi-clock-history"></i>
    </button>
    <div class="ac-menu d-none"></div>
  </div>
  <input type="hidden" class="item-id" name="lines[${idx}][item_id]">
</td>
<td class="col-qty">
  <input type="text" class="form-control text-end qty-view" inputmode="decimal" placeholder="0">
  <input type="hidden" name="lines[${idx}][qty]" class="qty-val" value="0">
</td>
<td class="col-unit d-none d-md-table-cell">
  <span class="unit-chip">
    <i class="bi bi-box"></i>
    <span class="unit-text">—</span>
  </span>
  <input type="hidden" class="unit-hidden" name="lines[${idx}][unit]" value="">
</td>
<td class="col-price">
  <div class="input-group">
    <span class="input-group-text">Rp</span>
    <input type="text" class="form-control text-end price-view" inputmode="decimal" placeholder="0">
    <input type="hidden" name="lines[${idx}][unit_cost]" class="price-val" value="0">
  </div>
</td>
<td class="mono col-subtotal">
  <span class="subtotal-desktop">0</span>
  <span class="subtotal-mobile mono">0</span>
</td>
<td class="text-end col-actions">
  <button type="button" class="btn btn-outline-danger btn-sm btn-del">
    <i class="bi bi-trash"></i>
  </button>
</td>`;
                tbody.appendChild(tr);
                bindRow(tr, prefill);
                setTimeout(() => tr.querySelector('.ac-input')?.focus(), 0);
                return tr;
            }

            function bindRow(tr, prefill) {
                const acInput = tr.querySelector('.ac-input');
                const acMenu = tr.querySelector('.ac-menu');
                const itemId = tr.querySelector('.item-id');
                const btnHistory = tr.querySelector('.btn-history');

                const unitText = tr.querySelector('.unit-text');
                const unitHidden = tr.querySelector('.unit-hidden');

                const qtyView = tr.querySelector('.qty-view');
                const qtyVal = tr.querySelector('.qty-val');
                const priceView = tr.querySelector('.price-view');
                const priceVal = tr.querySelector('.price-val');
                const subDesktop = tr.querySelector('.subtotal-desktop');
                const subMobile = tr.querySelector('.subtotal-mobile');

                const recalc = () => {
                    const q = parseNum(qtyView.value);
                    const p = parseNum(priceView.value);
                    qtyVal.value = q;
                    priceVal.value = p;
                    const s = q * p;
                    if (subDesktop) subDesktop.textContent = rupiah(s);
                    if (subMobile) subMobile.textContent = rupiah(s);
                    updateTotals();
                };

                let activeIndex = -1;
                let currentList = [];

                function scrollIntoViewIfNeeded(container, child) {
                    const cTop = container.scrollTop;
                    const cBottom = cTop + container.clientHeight;
                    const eTop = child.offsetTop;
                    const eBottom = eTop + child.offsetHeight;
                    if (eTop < cTop) container.scrollTop = eTop;
                    else if (eBottom > cBottom) container.scrollTop = eBottom - container.clientHeight;
                }

                function renderMenu(list) {
                    if (!list.length) {
                        acMenu.innerHTML = `<div class="ac-empty">Tidak ada hasil…</div>`;
                        acMenu.classList.remove('d-none');
                        activeIndex = -1;
                        return;
                    }
                    acMenu.innerHTML = list.slice(0, 300).map((it, i) => `
                <div class="ac-item ${i===activeIndex?'active':''}" data-id="${it.id}">
                    <div class="ac-code mono">${it.code}</div>
                    <div class="ac-name">${it.name}</div>
                    <div class="ac-unit">${it.unit || ''}</div>
                </div>
            `).join('');
                    acMenu.classList.remove('d-none');
                    if (activeIndex >= 0) {
                        const el = acMenu.querySelectorAll('.ac-item')[activeIndex];
                        if (el) scrollIntoViewIfNeeded(acMenu, el);
                    }
                }

                function openFullList() {
                    currentList = getFilteredItems();
                    activeIndex = currentList.length ? 0 : -1;
                    renderMenu(currentList);
                }

                function filterList(q) {
                    q = q.trim().toLowerCase();
                    const src = getFilteredItems();
                    currentList = !q ? src : src.filter(it =>
                        it.code.toLowerCase().includes(q) ||
                        it.name.toLowerCase().includes(q)
                    );
                    activeIndex = currentList.length ? 0 : -1;
                    renderMenu(currentList);
                }

                function moveActive(delta) {
                    if (!currentList.length) return;
                    activeIndex = Math.max(0, Math.min(currentList.length - 1, activeIndex + delta));
                    renderMenu(currentList);
                }

                function applyUnitText(u) {
                    const unit = u || '—';
                    if (unitText) unitText.textContent = unit;
                    if (unitHidden) unitHidden.value = unit;
                }

                function pickItem(it) {
                    itemId.value = it.id;
                    // INPUT cuma tampilkan kode (FLC280BLK)
                    acInput.value = it.code;
                    applyUnitText(it.unit);
                    acMenu.classList.add('d-none');

                    const supplierId = supplierSel.value;
                    if (supplierId) {
                        fetchLastPrice({
                            itemId: it.id,
                            supplierId
                        }).then(last => {
                            if (!last) return;
                            priceView.value = rupiah(last.unit_cost);
                            priceVal.value = last.unit_cost;
                            if (last.unit) {
                                applyUnitText(last.unit);
                            }
                            // highlight sebentar, tanpa teks "Terakhir: ..."
                            tr.classList.add('table-success');
                            setTimeout(() => tr.classList.remove('table-success'), 420);
                            recalc();
                        });
                    }
                    setTimeout(() => qtyView.focus(), 0);
                }

                // ==== Keyboard di ITEM ====
                acInput.addEventListener('keydown', (e) => {
                    const isOpen = !acMenu.classList.contains('d-none');
                    if (isOpen && e.key === 'ArrowDown') {
                        e.preventDefault();
                        moveActive(+1);
                        return;
                    }
                    if (isOpen && e.key === 'ArrowUp') {
                        e.preventDefault();
                        moveActive(-1);
                        return;
                    }
                    if (e.key === 'Enter' && !e.shiftKey) {
                        if (isOpen && activeIndex >= 0 && currentList[activeIndex]) {
                            e.preventDefault();
                            pickItem(currentList[activeIndex]);
                        }
                    }
                    if (e.key === 'Tab') {
                        if (isOpen && activeIndex >= 0 && currentList[activeIndex]) {
                            e.preventDefault();
                            pickItem(currentList[activeIndex]);
                        }
                    }
                    if (e.key === 'Escape') {
                        acMenu.classList.add('d-none');
                    }
                });
                acInput.addEventListener('input', () => filterList(acInput.value));
                acInput.addEventListener('focus', () => {
                    if (acInput.value === '') filterList('');
                });
                acMenu.addEventListener('mousemove', (e) => {
                    const el = e.target.closest('.ac-item');
                    if (!el) return;
                    [...acMenu.querySelectorAll('.ac-item')].forEach(x => x.classList.remove('active'));
                    el.classList.add('active');
                    const id = Number(el.dataset.id);
                    const idx = currentList.findIndex(x => x.id === id);
                    if (idx >= 0) activeIndex = idx;
                });
                acMenu.addEventListener('click', (e) => {
                    const el = e.target.closest('.ac-item');
                    if (!el) return;
                    const id = Number(el.dataset.id);
                    const it = currentList.find(x => x.id === id);
                    if (it) pickItem(it);
                });
                document.addEventListener('click', (e) => {
                    if (!tr.contains(e.target)) acMenu.classList.add('d-none');
                });

                // Harga terakhir tombol
                btnHistory.addEventListener('click', async () => {
                    const id = Number(itemId.value || 0);
                    const supplierId = supplierSel.value;
                    if (!id || !supplierId) {
                        alert('Pilih supplier & item dulu.');
                        return;
                    }
                    const last = await fetchLastPrice({
                        itemId: id,
                        supplierId
                    });
                    if (last) {
                        priceView.value = rupiah(last.unit_cost);
                        priceVal.value = last.unit_cost;
                        if (last.unit) {
                            applyUnitText(last.unit);
                        }
                        tr.classList.add('table-success');
                        setTimeout(() => tr.classList.remove('table-success'), 420);
                        recalc();
                    } else {
                        alert('Belum ada riwayat harga.');
                    }
                });

                // Qty & Price
                qtyView.addEventListener('input', () => {
                    sanitize(qtyView);
                    recalc();
                });
                priceView.addEventListener('input', () => {
                    sanitize(priceView);
                    recalc();
                });

                // Hapus baris
                tr.querySelector('.btn-del').addEventListener('click', () => {
                    tr.remove();
                    updateTotals();
                });

                // Prefill (kalau old input)
                if (prefill && prefill.item_id) {
                    const found = itemsAll.find(x => x.id == prefill.item_id);
                    if (found) {
                        itemId.value = found.id;
                        acInput.value = found.code; // cuma kode
                        applyUnitText(found.unit);
                    }
                    if (prefill.qty != null) {
                        qtyView.value = String(prefill.qty);
                    }
                    if (prefill.unit) {
                        applyUnitText(prefill.unit);
                    }
                    if (prefill.unit_cost != null) {
                        priceView.value = rupiah(prefill.unit_cost);
                        priceVal.value = prefill.unit_cost;
                    }
                    recalc();
                }
            }

            btnAdd.addEventListener('click', () => addLine());
            btnAdd5.addEventListener('click', () => {
                for (let i = 0; i < 5; i++) addLine();
            });
            if (!tbody.querySelector('.line-row')) addLine();

            // ==== Global shortcuts sederhana (tanpa hint) ====
            let addLock = false;

            function requestAddLine() {
                if (addLock) return;
                addLock = true;
                const newTr = addLine();
                newTr.querySelector('.ac-input')?.focus();
                setTimeout(() => addLock = false, 250);
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.shiftKey) {
                    if (document.activeElement?.closest('button')) return;
                    e.preventDefault();
                    requestAddLine();
                    return;
                }
                if (e.key === 'Backspace' && e.shiftKey) {
                    const row = document.activeElement?.closest('.line-row');
                    if (row) {
                        e.preventDefault();
                        const prev = row.previousElementSibling?.querySelector('.ac-input') ||
                            row.previousElementSibling?.querySelector('input,select');
                        row.remove();
                        if (prev) prev.focus();
                        updateTotals();
                    }
                }
            });

            // Biaya lain
            ocView.addEventListener('input', () => {
                sanitize(ocView);
                ocHidden.value = String(parseNum(ocView.value));
                updateTotals();
            });

            // Submit guard
            form.addEventListener('submit', (e) => {
                const rows = [...document.querySelectorAll('.line-row')];
                if (rows.length === 0) {
                    e.preventDefault();
                    return alert('Minimal 1 baris pembelian.');
                }

                const gt = calcLines() + parseNum(ocView.value);
                if (gt <= 0) {
                    e.preventDefault();
                    return alert('Grand total belum valid.');
                }

                btnSubmit.disabled = true;
                btnSubmit.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
                syncWarehouseHidden();
            });

            // ==== Fokus & behaviour awal ====
            updateWarehouseByType();
            filterSel.addEventListener('change', updateWarehouseByType);

            if (supplierSel) {
                supplierSel.focus();
                supplierSel.addEventListener('change', () => {
                    const firstAc = document.querySelector('#table-lines tbody .line-row .ac-input');
                    if (firstAc) firstAc.focus();
                });
            }

            if (supplierSel && supplierSel.value) {
                const firstAc = document.querySelector('#table-lines tbody .line-row .ac-input');
                if (firstAc) firstAc.focus();
            }

            updateTotals();
        })();
    </script>
@endpush
