<div class="header-wrap d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-3">
        <div class="icon-badge d-inline-flex align-items-center justify-content-center">
            <i class="bi bi-scissors" style="font-size:1.15rem;color:#93c5fd"></i>
        </div>

        <div class="min-w-0">
            <div class="crumb text-truncate" style="color:#94a3b8">
                <i class="bi bi-gear-wide-connected me-1"></i>Produksi
                <span class="mx-1">/</span>
                <span class="text-white-50">Vendor Cutting</span>
                <span class="mx-1">/</span>
                <span class="text-white-50">Form</span>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h5 mb-0">
                    Cutting Baru • <span class="mono">{{ $t->code }}</span>
                </h1>
                <span class="badge rounded-pill"
                    style="background:rgba(148,163,184,.10);border:1px solid rgba(148,163,184,.35);color:#e5e7eb;">
                    <i class="bi bi-person-workspace me-1"></i>Operator {{ $t->operator_code }}
                </span>
                <span class="badge rounded-pill"
                    style="background:rgba(56,189,248,.10);border:1px solid rgba(56,189,248,.35);color:#e0f2fe;">
                    <i class="bi bi-send-check me-1"></i>External {{ strtoupper($t->status) }}
                </span>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('production.vendor_cutting.index') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left-short me-1"></i>Kembali
        </a>
    </div>
</div>
