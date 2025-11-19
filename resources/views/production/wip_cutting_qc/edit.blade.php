@extends('layouts.app')

@section('title', 'QC Cutting • ' . $batch->code)

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

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }

        @media (max-width: 767.98px) {

            /* Di mobile: hanya tampilkan No, Item, Qty, QC, Catatan
                   Kolom Bundle, LOT, Unit disembunyikan */
            .col-mobile-hide {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">

        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.wip_cutting_qc.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="h4 mb-0">QC Cutting – {{ $batch->code }}</h1>
                <div class="help">
                    Stage: {{ $batch->stage }} • Status: {{ $batch->status }}
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger small">
                <strong>Terjadi kesalahan:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Info singkat bahan --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom">
                <div class="fw-semibold">Bahan (LOT) di Batch</div>
            </div>
            <div class="table-responsive" style="max-height: 220px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">LOT</th>
                            <th class="sticky">Item</th>
                            <th class="sticky text-end">Qty Planned</th>
                            <th class="sticky">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batch->materials as $m)
                            <tr>
                                <td class="mono">{{ $m->lot->code ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $m->item->name ?? '-' }}</div>
                                    <div class="help mono">{{ $m->item_code }}</div>
                                </td>
                                <td class="text-end mono">{{ number_format($m->qty_planned, 2) }}</td>
                                <td>{{ $m->unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted small py-3">
                                    Tidak ada data bahan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('production.wip_cutting_qc.update', $batch->id) }}">
            @csrf

            {{-- 🔹 Kirim qty_planned sekali saja ke controller (total semua material) --}}
            @php
                $qtyPlannedTotal = $batch->materials->sum('qty_planned');
            @endphp
            <input type="hidden" name="qty_planned" value="{{ $qtyPlannedTotal }}">

            <div class="card mb-3">
                <div class="p-3 border-bottom d-flex align-items-center">
                    <div>
                        <div class="fw-semibold">QC per Iket / Bundle</div>
                        <div class="help">Pilih OK / Reject untuk setiap iket.</div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 420px;">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                {{-- No (tampil di semua ukuran layar) --}}
                                <th class="sticky text-center" style="width: 1%;">No</th>

                                {{-- Bundle & LOT hanya tampil di desktop/tablet --}}
                                <th class="sticky col-mobile-hide">Bundle</th>
                                <th class="sticky col-mobile-hide">LOT Sumber</th>

                                <th class="sticky">Item</th>
                                <th class="sticky text-end">Qty</th>

                                {{-- Unit hanya untuk desktop --}}
                                <th class="sticky col-mobile-hide">Unit</th>

                                <th class="sticky text-center">QC</th>
                                <th class="sticky">Catatan QC</th>
                            </tr>
                        </thead>

                        {{-- UPDATE QC  --}}
                        <tbody>
                            @forelse ($batch->bundles as $b)
                                @php
                                    $index = $loop->index;
                                    $oldReject = old("bundles.$index.qty_reject", $b->qty_reject ?? 0);
                                    $qtyCut = (int) $b->qty_cut;
                                    $previewOk = max($qtyCut - (int) $oldReject, 0);
                                @endphp
                                <tr>
                                    {{-- No urut, tidak perlu di-controller --}}
                                    <td class="text-center mono">
                                        {{ $loop->iteration }}
                                        <input type="hidden" name="bundles[{{ $index }}][id]"
                                            value="{{ $b->id }}">
                                    </td>

                                    {{-- Bundle: sembunyi di mobile --}}
                                    <td class="col-mobile-hide">
                                        <div class="mono fw-semibold">{{ $b->bundle_code }}</div>
                                        <div class="help">No: {{ $b->bundle_no }}</div>
                                    </td>

                                    {{-- LOT: sembunyi di mobile --}}
                                    <td class="mono col-mobile-hide">{{ $b->lot->code ?? '-' }}</td>

                                    <td>
                                        <div class="fw-semibold">{{ $b->item->name ?? '-' }}</div>
                                        <div class="help mono">{{ $b->item_code }}</div>
                                    </td>

                                    <td class="text-end mono">{{ number_format($b->qty_cut, 0) }}</td>

                                    {{-- Unit: sembunyi di mobile --}}
                                    <td class="col-mobile-hide">{{ $b->unit }}</td>

                                    {{-- input defect / QC --}}
                                    <td class="text-center">
                                        {{-- Desktop: tetap tampil label "Defect / Reject (pcs)" --}}
                                        <div class="small text-muted mb-1 d-none d-md-block">
                                            Defect / Reject (pcs)
                                        </div>

                                        {{-- Mobile: langsung ke input QC (tanpa label di atas) --}}
                                        <input type="number" name="bundles[{{ $index }}][qty_reject]"
                                            class="form-control form-control-sm text-end mono" min="0"
                                            max="{{ $qtyCut }}" value="{{ $oldReject }}">

                                        <div class="help">
                                            OK ≈ {{ $previewOk }} dari {{ $qtyCut }}
                                        </div>
                                    </td>

                                    <td>
                                        <input type="text" name="bundles[{{ $index }}][qc_notes]"
                                            value="{{ old("bundles.$index.qc_notes", $b->notes) }}"
                                            class="form-control form-control-sm">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted small py-3">
                                        Tidak ada data iket. Input dulu di modul Cutting.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-circle"></i>
                        Simpan Hasil QC
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Kalau mobile, fokus langsung ke input QC pertama
            if (window.matchMedia('(max-width: 767.98px)').matches) {
                const firstQcInput = document.querySelector('input[name^="bundles"][name$="[qty_reject]"]');
                if (firstQcInput) {
                    firstQcInput.focus();
                    firstQcInput.select?.();
                }
            }
        });
    </script>
@endpush
