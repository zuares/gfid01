@extends('layouts.app')
@section('title', 'Produksi • Cutting Internal (kg/m → pcs)')

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
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <h3 class="mb-3">Cutting Internal Komponen (kg/m → pcs)</h3>

        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif
        @if (session('err'))
            <div class="alert alert-danger">{{ session('err') }}</div>
        @endif

        <form method="POST" action="{{ route('production.cutting.internal.store') }}" class="card p-3">
            @csrf
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Operator (opsional)</label>
                    <select name="operator_code" class="form-select">
                        <option value="">- pilih -</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e->code }}">{{ $e->code }} — {{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gudang Asal (bahan)</label>
                    <select name="warehouse_from_id" class="form-select" required>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->code ?? '' }} {{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gudang Tujuan (hasil pcs)</label>
                    <select name="warehouse_to_id" class="form-select" required>
                        <option value="{{ $whComp->id }}">{{ $whComp->code }} {{ $whComp->name }}</option>
                        @foreach ($warehouses as $w)
                            @if ($w->id !== $whComp->id)
                                <option value="{{ $w->id }}">{{ $w->code ?? '' }} {{ $w->name }}</option>
                            @endif
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
                        <label class="form-label">LOT Bahan (kg/m)</label>
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
                        <label class="form-label">Qty Bahan</label>
                        <input type="number" step="0.0001" class="form-control qty-in" name="lines[0][qty_in]"
                            placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">UOM</label>
                        <input type="text" class="form-control uom-in" name="lines[0][uom_in]" placeholder="kg/m">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hasil (pcs)</label>
                        <input type="number" class="form-control pcs-out" name="lines[0][pcs_output]" placeholder="0">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-secondary w-100 addLine">+</button>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Post Cutting Internal</button>
            </div>
        </form>

        <div class="card p-3 mt-3">
            <h5 class="mb-2">Riwayat 10 Transaksi Terakhir</h5>
            @forelse($last as $h)
                @php
                    $rows = DB::table('cutting_internal_lines')->where('cutting_internal_job_id', $h->id)->get();
                @endphp
                <div class="border rounded p-2 mb-2">
                    <div class="fw-bold">{{ $h->code }}</div>
                    <div class="small text-muted">{{ $h->date }} • {{ $h->operator_code ?? '—' }} •
                        {{ $h->status }}</div>
                    <ul class="small">
                        @foreach ($rows as $ln)
                            @php
                                $it = DB::table('items')->find($ln->item_id);
                                $lotIn = DB::table('lots')->find($ln->lot_id);
                            @endphp
                            <li>
                                {{ $it?->code }} ← {{ $lotIn?->code }}:
                                {{ number_format($ln->qty_in, 4, ',', '.') }} {{ $ln->uom_in }}
                                → {{ $ln->pcs_output }} pcs |
                                LOT out: {{ $ln->lot_output_code }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="text-muted">Belum ada data.</div>
            @endforelse
        </div>
    </div>

    @push('scripts')
        <script>
            let idx = 0;

            function bindLotSelect(scope) {
                (scope || document).querySelectorAll('.lot-select').forEach(sel => {
                    sel.onchange = () => {
                        const opt = sel.selectedOptions[0];
                        const wrap = sel.closest('.line');
                        wrap.querySelector('.lot-id').value = opt?.value || '';
                        wrap.querySelector('.item-id').value = opt?.dataset.itemId || '';
                        wrap.querySelector('.uom-in').value = opt?.dataset.uom || '';
                    };
                });
            }
            bindLotSelect();

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('addLine')) {
                    const wrap = document.getElementById('lines');
                    const rows = wrap.querySelectorAll('.line');
                    const newIdx = rows.length;
                    const clone = rows[0].cloneNode(true);

                    // Reset inputs
                    clone.querySelectorAll('input').forEach(inp => {
                        if (inp.name?.includes('lines[0]')) {
                            inp.name = inp.name.replace('lines[0]', `lines[${newIdx}]`);
                        }
                        if (inp.classList.contains('qty-in') || inp.classList.contains('uom-in') || inp
                            .classList.contains('pcs-out')) {
                            inp.value = '';
                        }
                        if (inp.classList.contains('lot-id') || inp.classList.contains('item-id')) {
                            inp.value = '';
                        }
                    });
                    clone.querySelectorAll('select').forEach(sel => {
                        if (sel.name?.includes('lines[0]')) {
                            sel.name = sel.name.replace('lines[0]', `lines[${newIdx}]`);
                        }
                        sel.value = '';
                    });

                    wrap.appendChild(clone);
                    bindLotSelect(clone);
                }
            });
        </script>
    @endpush
@endsection
