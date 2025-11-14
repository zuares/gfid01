{{-- ================== KARTU LOT DARI EXTERNAL TRANSFER ================== --}}
<div id="vc-lot-cards" class="vc-card mb-4 p-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold">Pilih LOT Kain</div>
            <div class="lots-help">Klik salah satu LOT untuk mulai isi form cutting</div>
        </div>

        <div class="lots-container">
            @foreach ($lines as $l)
                <div class="lot-card" data-line-id="{{ $l->id }}" data-lot-code="{{ $l->lot_code }}"
                    data-item-name="{{ $l->item_name }}" data-qty="{{ $l->qty }}" data-unit="{{ $l->unit }}">
                    <div class="lot-headline text-truncate" title="{{ $l->item_name }}">
                        {{ $l->item_name }}
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="small text-muted">
                            <span class="mono">{{ $l->lot_code }}</span>
                            <span class="badge bg-secondary-subtle text-light border ms-2 mono">
                                {{ $l->item_code }}
                            </span>
                        </div>
                        <div class="pill-sisa mono" title="Qty kirim LOT">
                            {{ number_format($l->qty, 3) }} {{ $l->unit }}
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-dark">
                            <i class="bi bi-calendar2-week me-1"></i>{{ $t->date?->format('d/m/Y') }}
                        </span>
                        <span class="badge text-bg-dark">
                            <i class="bi bi-truck me-1"></i>EXT
                        </span>
                        <span class="badge text-bg-dark mono">{{ $t->code }}</span>
                    </div>

                    <button type="button" class="btn btn-primary w-100 btn-sm lot-pick-btn"
                        data-line-id="{{ $l->id }}" data-qty="{{ $l->qty }}"
                        data-unit="{{ $l->unit }}">
                        Gunakan LOT Ini
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</div>
