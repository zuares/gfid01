@extends('layouts.app')
@section('title', 'Produksi • Kirim ke Tukang Cutting')

@push('head')
    <style>
        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .soft {
            border-color: color-mix(in srgb, var(--line) 70%, transparent 30%);
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <h3 class="mb-3">Kirim Bahan Utama ke Tukang Cutting</h3>

        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        <form method="POST" action="{{ route('production.external.send.store') }}" class="card p-3">
            @csrf
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Operator Cutting (Eksternal)</label>
                    <select class="form-select" name="operator_code" required>
                        <option value="">- pilih -</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e->code }}">{{ $e->code }} — {{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Gudang Asal (Bahan)</label>
                    <select class="form-select" name="warehouse_from_id" required>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->code ?? '' }} {{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="note" class="form-control" placeholder="Opsional">
                </div>
            </div>

            <hr>

            <div id="lines" class="vstack gap-2">
                <div class="row g-2 align-items-end line">
                    <div class="col-md-5">
                        <label class="form-label">LOT Bahan Utama</label>
                        <select class="form-select lot-select">
                            <option value="">- pilih -</option>
                            @foreach ($lots as $l)
                                <option value="{{ $l->id }}" data-item-id="{{ $l->item_id }}"
                                    data-item-code="{{ $l->item_code }}" data-item-name="{{ $l->item_name }}"
                                    data-uom="{{ $l->uom }}" data-initial="{{ $l->initial_qty }}">
                                    {{ $l->lot_code }} • {{ $l->item_code }} ({{ $l->item_name }}) • stok:
                                    {{ $l->initial_qty }} {{ $l->uom }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="lines[0][lot_id]" class="lot-id">
                        <input type="hidden" name="lines[0][item_id]" class="item-id">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qty</label>
                        <input type="number" step="0.0001" class="form-control qty" name="lines[0][qty]" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">UOM</label>
                        <input type="text" class="form-control uom" name="lines[0][uom]" placeholder="m/kg">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Info</label>
                        <input type="text" class="form-control info" name="lines[0][note]" placeholder="Opsional">
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" id="addLine">+ Tambah LOT</button>
                <button class="btn btn-primary">Kirim ke Tukang</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            let idx = 0;
            document.querySelector('#addLine').addEventListener('click', () => {
                idx++;
                const row = document.querySelector('.line').cloneNode(true);
                row.querySelectorAll('input,select').forEach(el => {
                    if (el.name?.includes('lines[0]')) {
                        el.name = el.name.replace('lines[0]', `lines[${idx}]`);
                    }
                    if (el.classList.contains('qty') || el.classList.contains('uom') || el.classList.contains(
                            'info')) {
                        el.value = '';
                    }
                    if (el.classList.contains('lot-select')) {
                        el.value = '';
                    }
                    if (el.classList.contains('lot-id') || el.classList.contains('item-id')) {
                        el.value = '';
                    }
                });
                document.querySelector('#lines').appendChild(row);
                bindLotSelect(row);
            });

            function bindLotSelect(scope) {
                (scope || document).querySelectorAll('.lot-select').forEach(sel => {
                    sel.onchange = (e) => {
                        const opt = sel.selectedOptions[0];
                        const wrap = sel.closest('.line');
                        wrap.querySelector('.lot-id').value = opt.value || '';
                        wrap.querySelector('.item-id').value = opt.dataset.itemId || '';
                        wrap.querySelector('.uom').value = opt.dataset.uom || '';
                    };
                });
            }
            bindLotSelect();
        </script>
    @endpush
@endsection
