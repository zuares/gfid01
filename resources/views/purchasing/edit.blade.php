@extends('layouts.app')
@section('title', 'Purchasing • ' . $invoice->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin: 0 auto;
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
            font-size: .85rem
        }

        .table thead th {
            position: sticky;
            top: 0;
            background: var(--card);
        }

        .badge-status {
            font-size: .75rem;
            letter-spacing: .02em
        }

        .pill {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .25rem .6rem;
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="container py-3 page-wrap">

        {{-- === HEADER === --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <h5 class="mb-0 mono">{{ $invoice->code }}</h5>
                @php
                    $docBadge = $invoice->status === 'posted' ? 'success' : 'secondary';
                    $payBadge = match ($invoice->payment_status) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $docBadge }} badge-status">{{ strtoupper($invoice->status) }}</span>
                <span class="badge bg-{{ $payBadge }} badge-status text-uppercase">{{ $invoice->payment_status }}</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if ($invoice->status === 'posted')
                    {{-- Draft: tidak ada tombol di kanan atas, Post dipindah ke "Simpan & Post" di detail --}}
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                        <i class="bi bi-cash-coin me-1"></i> Tambah Pembayaran
                    </button>
                @endif
            </div>
        </div>

        {{-- === RINGKASAN HEADER === --}}
        @php
            $grand = (float) ($grandColumn ?? ($invoice->grand_total ?? 0));
            $paid = (float) ($paidAmount ?? $invoice->payments->sum('amount'));
            $remain = max(0, $grand - $paid);
        @endphp

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="help">Tanggal</div>
                        <div class="mono">{{ \Carbon\Carbon::parse($invoice->date)->toDateString() }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="help">Supplier</div>
                        <div>{{ $invoice->supplier->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="help">Gudang Tujuan</div>
                        <div>{{ $invoice->warehouse->name ?? '—' }}
                            <span class="help">({{ $invoice->warehouse->code ?? '' }})</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help">Catatan</div>
                        <div>{{ $invoice->note ?: '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="help">Status Pembayaran</div>
                        <div class="mono text-uppercase">
                            <span class="badge bg-{{ $payBadge }}">{{ $invoice->payment_status }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- === KPI ANGKA === --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4">
                <div class="card text-center p-2">
                    <div class="help">Grand Total</div>
                    <div class="value mono fw-semibold">Rp {{ number_format($grand, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card text-center p-2">
                    <div class="help">Dibayar</div>
                    <div class="value mono fw-semibold text-success">Rp {{ number_format($paid, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card text-center p-2">
                    <div class="help">Sisa</div>
                    <div class="value mono fw-semibold" style="color: var(--bs-warning)">
                        Rp {{ number_format($remain, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- === DETAIL ITEM (EDITABLE) === --}}
        <div class="card mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">
                    <strong>Detail Pembelian</strong>

                    @if ($invoice->status === 'draft')
                        <div class="btn-group btn-group-sm" role="group">
                            {{-- Simpan Perubahan & tetap draft (preview) --}}
                            <button type="submit" form="invoice-lines-form" name="next_action" value="preview"
                                class="btn btn-outline-primary">
                                <i class="bi bi-save me-1"></i> Simpan & Preview
                            </button>

                            {{-- Simpan & langsung Post --}}
                            <button type="submit" form="invoice-lines-form" name="next_action" value="post"
                                class="btn btn-primary"
                                onclick="return confirm('Simpan perubahan dan post invoice ini? Stok & jurnal akan dibuat.');">
                                <i class="bi bi-check2-circle me-1"></i> Simpan & Post
                            </button>
                        </div>
                    @else
                        <span class="help">Baris bisa diedit hanya saat status invoice DRAFT.</span>
                    @endif
                </div>

                <form id="invoice-lines-form" method="POST"
                    action="{{ route('purchasing.invoices.lines.update', $invoice) }}">
                    @csrf
                    @method('PUT')

                    <div class="table-responsive mt-2">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th width="12%" class="text-end">Qty</th>
                                    <th width="12%">Unit</th>
                                    <th width="18%" class="text-end">Harga</th>
                                    <th width="18%" class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $sum = 0; @endphp
                                @forelse($invoice->lines as $ln)
                                    @php
                                        $sub = (float) $ln->qty * (float) $ln->unit_cost;
                                        $sum += $sub;
                                    @endphp
                                    <tr data-line-id="{{ $ln->id }}">
                                        <td class="mono">
                                            {{ $ln->item_code }}
                                            <span class="help d-block">{{ $ln->item?->name }}</span>
                                        </td>

                                        {{-- QTY --}}
                                        <td class="text-end">
                                            @if ($invoice->status === 'draft')
                                                <input type="number" step="0.01" min="0"
                                                    name="lines[{{ $ln->id }}][qty]"
                                                    value="{{ old("lines.$ln->id.qty", $ln->qty) }}"
                                                    class="form-control form-control-sm text-end mono js-qty">
                                            @else
                                                <span class="mono">
                                                    {{ number_format($ln->qty, 2, ',', '.') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- UNIT --}}
                                        <td>{{ $ln->unit }}</td>

                                        {{-- HARGA --}}
                                        <td class="text-end">
                                            @if ($invoice->status === 'draft')
                                                <input type="number" step="1" min="0"
                                                    name="lines[{{ $ln->id }}][unit_cost]"
                                                    value="{{ old("lines.$ln->id.unit_cost", $ln->unit_cost) }}"
                                                    class="form-control form-control-sm text-end mono js-price">
                                            @else
                                                <span class="mono">
                                                    Rp {{ number_format($ln->unit_cost, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- SUBTOTAL (AUTO) --}}
                                        <td class="text-end mono js-subtotal">
                                            Rp {{ number_format($sub, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center help py-4">Tidak ada detail.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end">Subtotal</td>
                                    <td class="text-end mono js-subtotal-sum">
                                        Rp {{ number_format($sum, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @if (!is_null($invoice->other_costs))
                                    <tr>
                                        <td colspan="4" class="text-end">Biaya Lain / Ongkir</td>
                                        <td class="text-end mono">
                                            Rp {{ number_format((float) $invoice->other_costs, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="4" class="text-end fw-semibold">Grand Total</td>
                                    <td class="text-end mono fw-semibold js-grand-total">
                                        Rp {{ number_format($grand, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        {{-- === PEMBAYARAN === --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Pembayaran</strong>
                    @if ($invoice->status === 'posted')
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Pembayaran
                        </button>
                    @else
                        <span class="help">Aktif setelah invoice diposting</span>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-secondary">
                                <th>Tanggal</th>
                                <th class="text-end">Jumlah</th>
                                <th>Metode</th>
                                <th>Ref</th>
                                <th>Catatan</th>
                                <th width="6%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->payments as $p)
                                <tr>
                                    <td class="mono">{{ \Carbon\Carbon::parse($p->date)->toDateString() }}</td>
                                    <td class="text-end mono">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                                    <td><span class="pill">{{ strtoupper($p->method) }}</span></td>
                                    <td class="text-muted">{{ $p->ref_no ?: '—' }}</td>
                                    <td class="text-muted">{{ $p->note ?: '—' }}</td>
                                    <td class="text-end">
                                        <form method="POST"
                                            action="{{ route('purchasing.invoices.payments.destroy', [$invoice, $p]) }}"
                                            onsubmit="return confirm('Hapus pembayaran ini? Jurnal akan di-reversal.');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center help py-4">Belum ada pembayaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="d-flex justify-content-end">
            <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    {{-- MODAL PEMBAYARAN --}}
    @include('purchasing.invoices._payment_modal', ['invoice' => $invoice])
@endsection

@push('scripts')
    <script>
        (function() {
            const form = document.getElementById('invoice-lines-form');
            if (!form) return;

            const fmtRupiah = (num) => {
                num = Number(num || 0);
                return 'Rp ' + num.toLocaleString('id-ID', {
                    maximumFractionDigits: 0
                });
            };

            const recalc = () => {
                let subtotalSum = 0;

                form.querySelectorAll('tr[data-line-id]').forEach(tr => {
                    const qtyInput = tr.querySelector('.js-qty');
                    const priceInput = tr.querySelector('.js-price');
                    const subCell = tr.querySelector('.js-subtotal');

                    if (!qtyInput || !priceInput || !subCell) return;

                    const qty = parseFloat(String(qtyInput.value).replace(',', '.')) || 0;
                    const price = parseFloat(String(priceInput.value).replace(',', '.')) || 0;
                    const sub = qty * price;

                    subtotalSum += sub;
                    subCell.textContent = fmtRupiah(sub);
                });

                const subtotalCell = form.querySelector('.js-subtotal-sum');
                if (subtotalCell) {
                    subtotalCell.textContent = fmtRupiah(subtotalSum);
                }

                const otherCosts = {{ (float) ($invoice->other_costs ?? 0) }};
                const grandTotal = subtotalSum + otherCosts;
                const grandCell = form.querySelector('.js-grand-total');
                if (grandCell) {
                    grandCell.textContent = fmtRupiah(grandTotal);
                }
            };

            form.addEventListener('input', function(e) {
                if (e.target.classList.contains('js-qty') || e.target.classList.contains('js-price')) {
                    recalc();
                }
            });
        })();
    </script>
@endpush
