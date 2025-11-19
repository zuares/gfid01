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

        /* === CARD ITEM LINES === */
        .line-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: .6rem .75rem;
            margin-bottom: .6rem;
        }

        .line-row-1 {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .line-row-2 {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: .75rem;
            margin-top: .35rem;
            flex-wrap: wrap;
        }

        .line-item-title {
            font-size: .9rem;
        }

        .line-item-title .help {
            font-size: .75rem;
        }

        .line-qty-block {
            text-align: right;
            min-width: 120px;
        }

        .line-qty-block-label,
        .line-price-label,
        .line-subtotal-label {
            font-size: .75rem;
            color: var(--muted);
        }

        .line-qty-unit {
            font-size: .75rem;
            color: var(--muted);
        }

        .line-price-block {
            flex: 1;
            min-width: 130px;
        }

        .line-subtotal-block {
            min-width: 130px;
            text-align: right;
        }

        .line-subtotal-value {
            font-weight: 600;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: .25rem;
        }

        .summary-row-label {
            font-size: .85rem;
            color: var(--muted);
        }

        .summary-row-value {
            font-size: .9rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .header-left {
                flex-direction: column;
                align-items: flex-start;
                gap: .15rem;
            }

            .header-left h5 {
                font-size: 1rem;
            }

            .badge-status {
                font-size: .65rem;
            }

            .kpi-row .card {
                padding: .4rem .35rem;
            }

            .kpi-row .value {
                font-size: .9rem;
            }

            .kpi-row .help {
                font-size: .7rem;
            }

            .line-card {
                padding: .55rem .65rem;
            }

            .line-item-title {
                font-size: .85rem;
            }

            .line-qty-block {
                min-width: 100px;
            }

            .summary-row-label {
                font-size: .8rem;
            }

            .summary-row-value {
                font-size: .9rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container py-3 page-wrap">

        {{-- === HEADER === --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap header-left">
                <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>

                <div class="d-flex align-items-center gap-2 flex-wrap">
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
                    <span
                        class="badge bg-{{ $payBadge }} badge-status text-uppercase">{{ $invoice->payment_status }}</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if ($invoice->status === 'posted')
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

                    {{-- Catatan & Status Pembayaran disembunyikan di mobile --}}
                    <div class="col-md-6 d-none d-md-block">
                        <div class="help">Catatan</div>
                        <div>{{ $invoice->note ?: '—' }}</div>
                    </div>
                    <div class="col-md-6 d-none d-md-block">
                        <div class="help">Status Pembayaran</div>
                        <div class="mono text-uppercase">
                            <span class="badge bg-{{ $payBadge }}">{{ $invoice->payment_status }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- === KPI ANGKA === --}}
        <div class="row g-2 mb-3 kpi-row">
            <div class="col-4 col-md-4">
                <div class="card text-center p-2">
                    <div class="help">Grand Total</div>
                    <div class="value mono fw-semibold">Rp {{ number_format($grand, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-4 col-md-4">
                <div class="card text-center p-2">
                    <div class="help">Dibayar</div>
                    <div class="value mono fw-semibold text-success">Rp {{ number_format($paid, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-4 col-md-4">
                <div class="card text-center p-2">
                    <div class="help">Sisa</div>
                    <div class="value mono fw-semibold" style="color: var(--bs-warning)">
                        Rp {{ number_format($remain, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- === DETAIL ITEM (CARD FULL) === --}}
        <div class="card mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <strong>Detail Pembelian</strong>

                    @if ($invoice->status === 'draft')
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="submit" form="invoice-lines-form" name="next_action" value="preview"
                                class="btn btn-outline-primary">
                                <i class="bi bi-save me-1"></i> Simpan & Preview
                            </button>

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

                    @php $sum = 0; @endphp

                    @forelse($invoice->lines as $ln)
                        @php
                            $sub = (float) $ln->qty * (float) $ln->unit_cost;
                            $sum += $sub;
                        @endphp
                        <div class="line-card" data-line-id="{{ $ln->id }}">
                            {{-- BARIS 1: Item + Qty --}}
                            <div class="line-row-1">
                                <div class="line-item-title mono">
                                    {{ $ln->item_code }}
                                    <div class="help">{{ $ln->item?->name }}</div>
                                </div>

                                <div class="line-qty-block">
                                    <div class="line-qty-block-label">Qty</div>
                                    @if ($invoice->status === 'draft')
                                        <input type="number" step="0.01" min="0"
                                            name="lines[{{ $ln->id }}][qty]"
                                            value="{{ old("lines.$ln->id.qty", $ln->qty) }}"
                                            class="form-control form-control-sm text-end mono js-qty">
                                    @else
                                        <div class="mono">
                                            {{ number_format($ln->qty, 2, ',', '.') }}
                                        </div>
                                    @endif
                                    <div class="line-qty-unit">
                                        {{ $ln->unit }}
                                    </div>
                                </div>
                            </div>

                            {{-- BARIS 2: Harga + Subtotal --}}
                            <div class="line-row-2">
                                <div class="line-price-block">
                                    <div class="line-price-label">Harga</div>
                                    @if ($invoice->status === 'draft')
                                        <input type="number" step="1" min="0"
                                            name="lines[{{ $ln->id }}][unit_cost]"
                                            value="{{ old("lines.$ln->id.unit_cost", $ln->unit_cost) }}"
                                            class="form-control form-control-sm text-end mono js-price">
                                    @else
                                        <div class="mono">
                                            Rp {{ number_format($ln->unit_cost, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </div>

                                <div class="line-subtotal-block">
                                    <div class="line-subtotal-label">Subtotal</div>
                                    <div class="line-subtotal-value mono js-subtotal">
                                        Rp {{ number_format($sub, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center help py-3">
                            Tidak ada detail.
                        </div>
                    @endforelse

                    {{-- RINGKASAN BAWAH --}}
                    <div class="mt-2 pt-2 border-top" style="border-color: var(--line) !important;">
                        <div class="summary-row">
                            <div class="summary-row-label">Subtotal</div>
                            <div class="summary-row-value mono js-subtotal-sum">
                                Rp {{ number_format($sum, 0, ',', '.') }}
                            </div>
                        </div>

                        @if (!is_null($invoice->other_costs))
                            <div class="summary-row">
                                <div class="summary-row-label">Biaya Lain / Ongkir</div>
                                <div class="summary-row-value mono">
                                    Rp {{ number_format((float) $invoice->other_costs, 0, ',', '.') }}
                                </div>
                            </div>
                        @endif

                        <div class="summary-row">
                            <div class="summary-row-label fw-semibold">Grand Total</div>
                            <div class="summary-row-value mono fw-semibold js-grand-total">
                                Rp {{ number_format($grand, 0, ',', '.') }}
                            </div>
                        </div>
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

                form.querySelectorAll('.line-card[data-line-id]').forEach(card => {
                    const qtyInput = card.querySelector('.js-qty');
                    const priceInput = card.querySelector('.js-price');
                    const subEl = card.querySelector('.js-subtotal');

                    if (!qtyInput || !priceInput || !subEl) return;

                    const qty = parseFloat(String(qtyInput.value).replace(',', '.')) || 0;
                    const price = parseFloat(String(priceInput.value).replace(',', '.')) || 0;
                    const sub = qty * price;

                    subtotalSum += sub;
                    subEl.textContent = fmtRupiah(sub);
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
