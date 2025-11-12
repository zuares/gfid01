@extends('layouts.app')
@section('title', 'Produksi • Kitting (BSJ Set)')

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
        <h3 class="mb-3">Kitting • Satukan Komponen → BSJ Set (Siap Jahit)</h3>

        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif
        @if (session('err'))
            <div class="alert alert-danger">{{ session('err') }}</div>
        @endif

        <form method="POST" action="{{ route('production.kitting.store') }}" class="card p-3">
            @csrf
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batch</label>
                    <select name="batch_code" class="form-select" required>
                        <option value="">- pilih -</option>
                        @foreach ($batches as $b)
                            <option value="{{ $b->code }}">{{ $b->code }} — {{ $b->operator_code }}
                                ({{ $b->status }})</option>
                        @endforeach
                    </select>
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
                <div class="col-md-2">
                    <label class="form-label">Gudang Output</label>
                    <select name="warehouse_output_id" class="form-select" required>
                        <option value="{{ $wipSew->id }}">{{ $wipSew->code }} {{ $wipSew->name }}</option>
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
                    <div class="col-md-3">
                        <label class="form-label">SKU (hasil BSJ Set)</label>
                        <select class="form-select" name="lines[0][product_item_id]" required>
                            <option value="">- pilih -</option>
                            @foreach ($items as $it)
                                <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Target Set</label>
                        <input type="number" class="form-control qty-set" name="lines[0][qty_output_sets]" value="0">
                    </div>

                    <div class="col-md-7">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">LOT Kain Potong (pcs)</label>
                                <select class="form-select lot-select kain" data-type="kain">
                                    <option value="">- pilih -</option>
                                    @foreach ($lots_pcs as $l)
                                        <option value="{{ $l->id }}" data-initial="{{ $l->initial_qty }}"
                                            data-unit="{{ $l->unit }}">
                                            {{ $l->lot_code }} • {{ $l->item_code }} • stok {{ $l->initial_qty }}
                                            {{ $l->unit }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="lines[0][kain_lot_id]" class="kain-lot">
                                <input type="number" class="form-control mt-1" name="lines[0][kain_qty_used]"
                                    placeholder="qty kain pcs yang dipakai">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">LOT Rib (pcs)</label>
                                <select class="form-select lot-select rib" data-type="rib">
                                    <option value="">- pilih -</option>
                                    @foreach ($lots_pcs as $l)
                                        <option value="{{ $l->id }}" data-initial="{{ $l->initial_qty }}"
                                            data-unit="{{ $l->unit }}">
                                            {{ $l->lot_code }} • {{ $l->item_code }} • stok {{ $l->initial_qty }}
                                            {{ $l->unit }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="lines[0][rib_lot_id]" class="rib-lot">
                                <input type="number" class="form-control mt-1" name="lines[0][rib_qty_used]"
                                    placeholder="qty rib pcs">
                            </div>

                            <div class="col-md-6 mt-2">
                                <label class="form-label">LOT Karet (pcs)</label>
                                <select class="form-select lot-select karet" data-type="karet">
                                    <option value="">- pilih -</option>
                                    @foreach ($lots_pcs as $l)
                                        <option value="{{ $l->id }}" data-initial="{{ $l->initial_qty }}"
                                            data-unit="{{ $l->unit }}">
                                            {{ $l->lot_code }} • {{ $l->item_code }} • stok {{ $l->initial_qty }}
                                            {{ $l->unit }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="lines[0][karet_lot_id]" class="karet-lot">
                                <input type="number" class="form-control mt-1" name="lines[0][karet_qty_used]"
                                    placeholder="qty karet pcs">
                            </div>

                            <div class="col-md-6 mt-2">
                                <label class="form-label">LOT Tali (pcs)</label>
                                <select class="form-select lot-select tali" data-type="tali">
                                    <option value="">- pilih -</option>
                                    @foreach ($lots_pcs as $l)
                                        <option value="{{ $l->id }}" data-initial="{{ $l->initial_qty }}"
                                            data-unit="{{ $l->unit }}">
                                            {{ $l->lot_code }} • {{ $l->item_code }} • stok {{ $l->initial_qty }}
                                            {{ $l->unit }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="lines[0][tali_lot_id]" class="tali-lot">
                                <input type="number" class="form-control mt-1" name="lines[0][tali_qty_used]"
                                    placeholder="qty tali pcs">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" id="addLine">+ Tambah SKU</button>
                <button class="btn btn-primary">Post Kitting</button>
            </div>
        </form>

        <div class="card p-3 mt-3">
            <h5 class="mb-2">Riwayat 10 Kitting Terakhir</h5>
            @forelse($history as $h)
                @php $rows = DB::table('kitting_lines')->where('kitting_job_id',$h->id)->get(); @endphp
                <div class="border rounded p-2 mb-2">
                    <div class="fw-bold">{{ $h->code }}</div>
                    <div class="small text-muted">{{ $h->date }} • batch {{ $h->batch_code }} • {{ $h->status }}
                    </div>
                    <ul class="small">
                        @foreach ($rows as $ln)
                            @php
                                $sku = DB::table('items')->find($ln->product_item_id);
                                $kain = $ln->kain_lot_id ? DB::table('lots')->find($ln->kain_lot_id) : null;
                                $rib = $ln->rib_lot_id ? DB::table('lots')->find($ln->rib_lot_id) : null;
                                $karet = $ln->karet_lot_id ? DB::table('lots')->find($ln->karet_lot_id) : null;
                                $tali = $ln->tali_lot_id ? DB::table('lots')->find($ln->tali_lot_id) : null;
                            @endphp
                            <li>
                                {{ $sku?->code }} → {{ $ln->qty_output_sets }} set • LOT out:
                                {{ $ln->lot_output_code }}
                                <br>
                                <span class="text-muted">komponen:
                                    kain={{ $ln->kain_qty_used }} ({{ $kain?->code ?? '—' }}),
                                    rib={{ $ln->rib_qty_used }} ({{ $rib?->code ?? '—' }}),
                                    karet={{ $ln->karet_qty_used }} ({{ $karet?->code ?? '—' }}),
                                    tali={{ $ln->tali_qty_used }} ({{ $tali?->code ?? '—' }})
                                </span>
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

            document.getElementById('addLine').addEventListener('click', () => {
                const wrap = document.getElementById('lines');
                const first = wrap.querySelector('.line');
                const clone = first.cloneNode(true);
                const newIdx = wrap.querySelectorAll('.line').length;

                // reset inputs & rename
                clone.querySelectorAll('input,select').forEach(el => {
                    if (el.name?.includes('lines[0]')) el.name = el.name.replace('lines[0]',
                    `lines[${newIdx}]`);
                    if (el.tagName === 'INPUT') el.value = '';
                    if (el.tagName === 'SELECT') el.value = '';
                });

                // reset hidden lot ids
                ['kain', 'rib', 'karet', 'tali'].forEach(t => {
                    clone.querySelector(`.${t}-lot`).value = '';
                });

                wrap.appendChild(clone);
                bindLotSelect(clone);
            });

            function bindLotSelect(scope) {
                (scope || document).querySelectorAll('.lot-select').forEach(sel => {
                    sel.onchange = () => {
                        const type = sel.dataset.type; // kain/rib/karet/tali
                        const wrap = sel.closest('.line');
                        wrap.querySelector(`.${type}-lot`).value = sel.value || '';
                    };
                });
            }
            bindLotSelect();
        </script>
    @endpush
@endsection
