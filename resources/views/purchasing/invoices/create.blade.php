@extends('layouts.app')
@section('title', 'Pembelian • Invoice Baru')

@push('head')
    <style>
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

        .table thead th {
            position: sticky;
            top: 0;
            background: var(--card)
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Pembelian • Invoice Baru</h5>
            <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <form action="{{ route('purchasing.invoices.store') }}" method="POST" id="form-purchase">
            @csrf

            {{-- HEADER --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Supplier</label>
                            <select class="form-select" name="supplier_id" id="supplier_id" required>
                                <option value="">— Pilih Supplier —</option>
                                @foreach ($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">Tanggal</label>
                            <input type="date" name="date" value="{{ now('Asia/Jakarta')->toDateString() }}"
                                class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">Gudang Tujuan</label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                                <option value="">— Pilih Gudang —</option>
                                @php $warehouses = \App\Models\Warehouse::orderBy('name')->get(['id','name','code']); @endphp
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id', $kontrakanId) == $w->id)>
                                        {{ $w->name }} ({{ $w->code }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="help">Default terpilih: KONTRAKAN.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="note" class="form-control" placeholder="Opsional">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pembayaran</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_cash" id="is_cash"
                                    value="1">
                                <label class="form-check-label" for="is_cash">Tunai (Kas/Bank)</label>
                            </div>
                            <div class="help">Biarkan kosong jika pembelian kredit (Hutang Dagang).</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FILTER ITEM --}}
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label mb-2">Filter Jenis Item</label><br>
                    @php $types=['material'=>'Bahan Baku','pendukung'=>'Bahan Pendukung','finished'=>'Barang Jadi']; @endphp
                    @foreach ($types as $val => $label)
                        <label class="form-check form-check-inline">
                            <input class="form-check-input type-filter" type="radio" name="filter_type"
                                value="{{ $val }}" @checked($filterType === $val)>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                    <div class="help mt-1">Daftar item hanya menampilkan tipe terpilih.</div>
                </div>
            </div>

            {{-- DETAIL --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Detail Pembelian</strong>
                        <button type="button" class="btn btn-primary btn-sm" id="add-line">
                            <i class="bi bi-plus"></i> Tambah Baris
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="table-lines">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th width="12%">Qty</th>
                                    <th width="12%">Unit</th>
                                    <th width="18%">Harga</th>
                                    <th width="18%">Subtotal</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-semibold">Grand Total</td>
                                    <td class="mono" id="grand-total">0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const itemsAll = @json($itemsAll);
            let filterType = @json($filterType);
            const tbody = document.querySelector('#table-lines tbody');
            const totalView = document.querySelector('#grand-total');
            const supplierSel = document.querySelector('#supplier_id');

            // === Formatter: pakai helper JS kamu jika ada, fallback ke locale ===
            const rupiah = (n) => (window.App?.formatRupiah ? window.App.formatRupiah(n) : (Number(n || 0))
                .toLocaleString('id-ID'));
            const parseNum = (v) => (window.App?.parseNumberId ? window.App.parseNumberId(v) :
                (parseFloat(String(v ?? '').replace(/\s+/g, '').replace(/\./g, '').replace(',', '.')) || 0));

            const optionsByType = (type) => {
                const list = itemsAll.filter(i => i.type === type);
                return `<option value="">— Pilih Item (${type}) —</option>` +
                    list.map(i =>
                        `<option value="${i.id}" data-uom="${i.uom}" data-code="${i.code}">${i.code} — ${i.name}</option>`
                    ).join('');
            };

            const calcGrand = () => {
                let t = 0;
                document.querySelectorAll('.line-row').forEach(tr => {
                    const q = parseNum(tr.querySelector('.qty-val').value);
                    const p = parseNum(tr.querySelector('.price-val').value);
                    t += q * p;
                });
                totalView.textContent = rupiah(t);
            };

            // === Ambil last price dari backend
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
                if (js && js.ok && js.data) return js.data; // {unit_cost, unit, date, inv_code}
                return null;
            }

            function addLine() {
                const idx = Date.now();
                const tr = document.createElement('tr');
                tr.classList.add('line-row');
                tr.innerHTML = `
      <td>
        <div class="input-group">
          <select class="form-select item-select" name="lines[${idx}][item_id]" required>
            ${optionsByType(filterType)}
          </select>
          <button type="button" class="btn btn-outline-secondary btn-sm btn-history" title="Harga terakhir">
            <i class="bi bi-clock-history"></i>
          </button>
        </div>
      </td>
      <td>
        <input type="text" class="form-control text-end qty-view" inputmode="decimal" placeholder="0">
        <input type="hidden" name="lines[${idx}][qty]" class="qty-val" value="0">
      </td>
      <td>
        <input type="text" class="form-control unit-input" name="lines[${idx}][unit]" placeholder="unit">
      </td>
      <td>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="text" class="form-control text-end price-view" inputmode="decimal" placeholder="0">
          <input type="hidden" name="lines[${idx}][unit_cost]" class="price-val" value="0">
        </div>
      </td>
      <td class="mono subtotal">0</td>
      <td class="text-end">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del"><i class="bi bi-trash"></i></button>
      </td>
    `;
                tbody.appendChild(tr);
                bindRow(tr);
            }

            function bindRow(tr) {
                const sel = tr.querySelector('.item-select');
                const unit = tr.querySelector('.unit-input');
                const qtyView = tr.querySelector('.qty-view');
                const qtyVal = tr.querySelector('.qty-val');
                const priceView = tr.querySelector('.price-view');
                const priceVal = tr.querySelector('.price-val');
                const subtotal = tr.querySelector('.subtotal');

                const recalc = () => {
                    const q = parseNum(qtyView.value);
                    const p = parseNum(priceView.value);
                    qtyVal.value = q;
                    priceVal.value = p;
                    subtotal.textContent = rupiah(q * p);
                    calcGrand();
                };

                // Auto-isi last price ketika item dipilih (dengan supplier terpilih)
                async function tryAutofillLastPrice() {
                    const supplierId = supplierSel.value;
                    const itemId = sel.value;
                    if (!supplierId || !itemId) return;
                    const last = await fetchLastPrice({
                        itemId,
                        supplierId
                    });
                    if (last) {
                        priceView.value = rupiah(last.unit_cost);
                        priceVal.value = last.unit_cost;
                        if (last.unit && !unit.value) unit.value = last.unit;
                        recalc();
                        tr.classList.add('table-success');
                        setTimeout(() => tr.classList.remove('table-success'), 420);
                    }
                }

                // Default UOM dari master + auto last price
                sel.addEventListener('change', () => {
                    const opt = sel.options[sel.selectedIndex];
                    if (opt && opt.dataset.uom) unit.value = opt.dataset.uom || unit.value;
                    tryAutofillLastPrice();
                });

                // Jika supplier diganti setelah pilih item: coba auto-isi lagi
                supplierSel.addEventListener('change', () => {
                    tryAutofillLastPrice();
                });

                // Tombol hapus baris
                tr.querySelector('.btn-del').addEventListener('click', () => {
                    tr.remove();
                    calcGrand();
                });

                // Tombol history manual
                tr.querySelector('.btn-history').addEventListener('click', async () => {
                    const supplierId = supplierSel.value;
                    const itemId = sel.value;
                    if (!supplierId || !itemId) return alert('Pilih supplier dan item dulu.');
                    const last = await fetchLastPrice({
                        itemId,
                        supplierId
                    });
                    if (last) {
                        priceView.value = rupiah(last.unit_cost);
                        priceVal.value = last.unit_cost;
                        unit.value = last.unit || unit.value;
                        recalc();
                    } else {
                        alert('Belum ada riwayat harga.');
                    }
                });

                // Input guard & recalculation
                const sanitize = (el) => el.value = el.value.replace(/[^0-9.,]/g, '');
                qtyView.addEventListener('input', () => {
                    sanitize(qtyView);
                    recalc();
                });
                priceView.addEventListener('input', () => {
                    sanitize(priceView);
                    recalc();
                });
            }

            // Filter tipe item: refresh semua dropdown aktif
            document.querySelectorAll('.type-filter').forEach(r => {
                r.addEventListener('change', () => {
                    filterType = r.value;
                    document.querySelectorAll('.item-select').forEach(sel => {
                        const current = sel.value;
                        sel.innerHTML = optionsByType(filterType);
                        const match = [...sel.options].some(o => o.value == current);
                        if (!match) {
                            sel.value = '';
                            const row = sel.closest('tr');
                            row.querySelector('.unit-input').value = '';
                            row.querySelector('.price-view').value = '';
                            row.querySelector('.price-val').value = '0';
                            row.querySelector('.subtotal').textContent = '0';
                            calcGrand();
                        }
                    });
                });
            });

            // Add line button + baris awal
            document.getElementById('add-line').addEventListener('click', addLine);
            addLine();
        })();
    </script>
@endpush
