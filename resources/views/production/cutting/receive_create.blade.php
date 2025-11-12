@extends('layouts.app')
@section('title', 'Produksi • Terima Hasil Cutting (Draft → Post)')

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
        <h3 class="mb-2">Terima Hasil Cutting (Draft → Post)</h3>

        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif
        @if (session('err'))
            <div class="alert alert-danger">{{ session('err') }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <form method="POST" action="{{ route('production.cutting.receive.storeDraft') }}" class="card p-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Batch (per operator)</label>
                            <select name="batch_code" class="form-select" required>
                                <option value="">- pilih -</option>
                                @foreach ($batches as $b)
                                    <option value="{{ $b->code }}">{{ $b->code }} — {{ $b->operator_code }}
                                        ({{ $b->status }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Operator</label>
                            <select name="operator_code" class="form-select" required>
                                <option value="">- pilih -</option>
                                @foreach ($employees as $e)
                                    <option value="{{ $e->code }}">{{ $e->code }} — {{ $e->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Relasi Transfer Eksternal (opsional)</label>
                            <select name="external_transfer_id" class="form-select">
                                <option value="">- pilih -</option>
                                @foreach ($exts as $ex)
                                    <option value="{{ $ex->id }}">{{ $ex->code }} — {{ $ex->batch_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="note" class="form-control" placeholder="Opsional">
                        </div>
                    </div>

                    <hr>
                    <div id="lines" class="vstack gap-2">
                        <div class="row g-2 align-items-end line">
                            <div class="col-md-5">
                                <label class="form-label">SKU (hasil)</label>
                                <select class="form-select" name="lines[0][item_id]" required>
                                    <option value="">- pilih -</option>
                                    @foreach ($items as $it)
                                        <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Iket</label>
                                <input type="number" class="form-control" name="lines[0][bundle_count]" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Pcs/Iket</label>
                                <input type="number" class="form-control" name="lines[0][pcs_per_bundle]" value="20">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Good</label>
                                <input type="number" class="form-control" name="lines[0][good_qty]" value="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Def</label>
                                <input type="number" class="form-control" name="lines[0][defect_qty]" value="0">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-secondary w-100 addLine">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-primary">Simpan Draft</button>
                    </div>
                </form>
            </div>

            <div class="col-lg-5">
                <div class="card p-3">
                    <h5 class="mb-2">Draft Terakhir</h5>
                    @php
                        $drafts = DB::table('cutting_receipts')
                            ->where('status', 'draft')
                            ->orderByDesc('id')
                            ->limit(10)
                            ->get();
                    @endphp
                    @forelse($drafts as $d)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-bold">{{ $d->code }}</div>
                            <div class="small text-muted">{{ $d->batch_code }} • {{ $d->operator_code }} •
                                {{ $d->date }}</div>
                            @php
                                $lines = DB::table('cutting_receipt_lines')->where('cutting_receipt_id', $d->id)->get();
                            @endphp
                            <ul class="small mb-2">
                                @foreach ($lines as $ln)
                                    @php $it = DB::table('items')->find($ln->item_id); @endphp
                                    <li>{{ $it?->code }}: good {{ $ln->good_qty }}, defect {{ $ln->defect_qty }}</li>
                                @endforeach
                            </ul>
                            <form method="POST" action="{{ route('production.cutting.receive.post', $d->id) }}">
                                @csrf
                                <button class="btn btn-success btn-sm">POST</button>
                            </form>
                        </div>
                    @empty
                        <div class="text-muted">Belum ada draft.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('addLine')) {
                    const wrap = document.getElementById('lines');
                    const rows = wrap.querySelectorAll('.line');
                    const idx = rows.length;
                    const clone = rows[0].cloneNode(true);
                    clone.querySelectorAll('input').forEach(inp => {
                        inp.name = inp.name.replace('lines[0]', `lines[${idx}]`);
                        if (inp.type === 'number') inp.value = (inp.name.includes('pcs_per_bundle') ? 20 : 0);
                    });
                    clone.querySelectorAll('select').forEach(sel => {
                        sel.name = sel.name.replace('lines[0]', `lines[${idx}]`);
                        sel.value = '';
                    });
                    wrap.appendChild(clone);
                }
            });
        </script>
    @endpush
@endsection
