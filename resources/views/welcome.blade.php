<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Produksi • Cutting Baru</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body {
            background: #020617;
            color: #e5e7eb;
        }

        .card {
            background: #020617;
            border: 1px solid #1f2933;
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        }

        .pill-sisa {
            background: rgba(16, 185, 129, .12);
            border: 1px solid rgba(16, 185, 129, .28);
            color: #6ee7b7;
            border-radius: 999px;
            padding: .2rem .55rem;
            font-size: .82rem;
        }

        .bg-secondary-subtle {
            background: rgba(148, 163, 184, .10);
            border: 1px solid rgba(148, 163, 184, .25) !important;
        }

        .required::after {
            content: '*';
            color: #f87171;
            margin-left: 3px
        }

        /* LOT LIST */
        .lots-container {
            display: grid;
            gap: .9rem;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        }

        .lot-card {
            border: 1px solid #1f2933;
            background: #020617;
            border-radius: 14px;
            padding: .9rem .95rem;
            transition: transform .12s ease, border-color .12s ease, box-shadow .12s ease;
        }

        .lot-card:hover {
            transform: translateY(-1px);
            border-color: rgba(148, 163, 184, .35);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .18);
        }

        .lot-headline {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .2px;
            margin-bottom: .35rem;
            line-height: 1.25;
        }

        .lots-help {
            color: #9ca3af;
            font-size: .85rem;
        }

        .header-wrap {
            padding: 0;
            margin: 0 0 1rem;
        }

        .icon-badge {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(59, 130, 246, .12);
            border: 1px solid rgba(59, 130, 246, .28);
        }

        .table thead th.sticky {
            position: sticky;
            top: 0;
            background: #0f172a;
            z-index: 2
        }

        @media (max-width:640px) {

            .col-uom,
            .col-fabric,
            .col-actions {
                display: none !important;
            }

            .lines-table td,
            .lines-table th {
                padding: .5rem .6rem;
            }
        }
    </style>
</head>

<body>
    <div class="container py-4">

        <!-- ================= HEADER ================= -->
        <div class="header-wrap d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-badge d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-scissors" style="font-size:1.15rem;color:#93c5fd"></i>
                </div>

                <div class="min-w-0">
                    <div class="crumb text-truncate" style="color:#94a3b8">
                        <i class="bi bi-gear-wide-connected me-1"></i>Produksi
                        <span class="mx-1">/</span>
                        <span class="text-white-50">Cutting</span>
                        <span class="mx-1">/</span>
                        <span class="text-white-50">Form</span>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h5 mb-0">Cutting Baru</h1>
                        <span class="badge rounded-pill"
                            style="background:rgba(148,163,184,.10);border:1px solid rgba(148,163,184,.35);color:#e5e7eb;">
                            <i class="bi bi-lightning-charge me-1"></i>Draft
                        </span>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="#" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left-short me-1"></i>Kembali
                </a>
            </div>
        </div>

        <div class="mb-3"
            style="height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent)"></div>

        <!-- ================== PILIH LOT KAIN ================== -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-semibold">Pilih LOT Kain</div>
                    <div class="lots-help">Klik salah satu untuk isi form di bawah</div>
                </div>

                <div class="lots-container">
                    <!-- LOT 1 -->
                    <div class="lot-card">
                        <div class="lot-headline text-truncate" title="Fleece Hitam Gramasi 280">
                            Fleece Hitam Gramasi 280
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small text-muted">
                                <span class="mono">LOT-FLC280BLK-20251113-001</span>
                                <span class="badge bg-secondary-subtle text-light border ms-2 mono">FLC280BLK</span>
                            </div>
                            <div class="pill-sisa mono" title="Sisa stok LOT">
                                100,000 kg
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge text-bg-dark">
                                <i class="bi bi-calendar2-week me-1"></i>13/11/2025
                            </span>
                            <span class="badge text-bg-dark">
                                <i class="bi bi-truck me-1"></i>INV
                            </span>
                            <span class="badge text-bg-dark mono">INV-BKU-251113-001</span>
                        </div>

                        <!-- button dengan data-* untuk JS -->
                        <button type="button" class="btn btn-primary w-100 btn-sm lot-pick-btn"
                            data-lot-code="LOT-FLC280BLK-20251113-001" data-lot-qty="100" data-lot-unit="kg">
                            Gunakan LOT Ini
                        </button>
                    </div>

                    <!-- LOT 2 -->
                    <div class="lot-card">
                        <div class="lot-headline text-truncate" title="Fleece Abu 280">
                            Fleece Abu 280
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small text-muted">
                                <span class="mono">LOT-FLC280ABU-20251110-002</span>
                                <span class="badge bg-secondary-subtle text-light border ms-2 mono">FLC280ABU</span>
                            </div>
                            <div class="pill-sisa mono" title="Sisa stok LOT">
                                75,500 kg
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge text-bg-dark">
                                <i class="bi bi-calendar2-week me-1"></i>10/11/2025
                            </span>
                            <span class="badge text-bg-dark">
                                <i class="bi bi-truck me-1"></i>INV
                            </span>
                            <span class="badge text-bg-dark mono">INV-BKU-251110-003</span>
                        </div>

                        <button type="button" class="btn btn-primary w-100 btn-sm lot-pick-btn"
                            data-lot-code="LOT-FLC280ABU-20251110-002" data-lot-qty="75.5" data-lot-unit="kg">
                            Gunakan LOT Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== FORM CUTTING ================== -->
        <form id="cuttingForm" action="#" method="POST" class="card">
            <div class="card-body">
                <div class="row g-3">

                    <!-- PIC -->
                    <div class="col-12 col-md-3">
                        <label class="form-label required">PIC</label>
                        <select name="pic_code" class="form-select" required>
                            <option value="">— Pilih PIC —</option>
                            <option value="MRF">MRF (Maruf)</option>
                            <option value="BBI">BBI (Bebi)</option>
                            <option value="RDN">RDN (Raden)</option>
                        </select>
                    </div>

                    <!-- Tanggal -->
                    <div class="col-12 col-md-2">
                        <label class="form-label required">Tanggal</label>
                        <input type="date" name="date_start" class="form-control" required value="2025-11-13">
                    </div>

                    <!-- LOT select -->
                    <div class="col-md-3 d-none d-md-block">
                        <label class="form-label required">LOT Kain</label>
                        <select name="lot_code" id="lotSelect" class="form-select" required>
                            <option value="">— Pilih LOT —</option>
                            <option value="LOT-FLC280BLK-20251113-001" data-qty="100" data-unit="kg">
                                2025-11-13 • LOT-FLC280BLK-20251113-001 — FLC280BLK • Sisa: 100,000 kg
                            </option>
                            <option value="LOT-FLC280ABU-20251110-002" data-qty="75.5" data-unit="kg">
                                2025-11-10 • LOT-FLC280ABU-20251110-002 — FLC280ABU • Sisa: 75,500 kg
                            </option>
                        </select>
                        <div class="text-muted small mt-1">
                            Hanya LOT dengan stok &gt; 0 yang ditampilkan.
                        </div>
                    </div>

                    <!-- Qty Kain -->
                    <div class="col-md-2 d-none d-md-block">
                        <label class="form-label required">Qty Kain</label>
                        <input type="number" step="0.001" min="0.001" class="form-control mono"
                            id="qtyUsed" readonly placeholder="—">
                        <div class="text-muted small mt-1">Auto dari LOT terpilih</div>
                    </div>

                    <!-- UOM -->
                    <div class="col-md-2 d-none d-md-block">
                        <label class="form-label required">UOM</label>
                        <input type="text" class="form-control" id="unitUsed" readonly placeholder="—">
                    </div>

                    <!-- Catatan -->
                    <div class="col-12 d-none d-md-block">
                        <label class="form-label">Catatan</label>
                        <textarea name="note" rows="2" class="form-control" placeholder="Keterangan tambahan"></textarea>
                    </div>
                </div>

                <hr class="my-4 border-secondary">

                <!-- ======== HASIL CUTTING ======== -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">Hasil Cutting</h6>
                    <button type="button" id="addRow" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle lines-table mb-0">
                        <thead>
                            <tr>
                                <th class="sticky">Barang Jadi (base, mis. K7BLK)</th>
                                <th class="sticky text-end">Qty Item</th>
                                <th class="sticky col-uom">UOM</th>
                                <th class="sticky text-end col-fabric">Qty Bahan (kg)</th>
                                <th class="sticky col-actions" style="width:1%"></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody">
                            <!-- baris awal -->
                            <tr>
                                <td>
                                    <input type="text" class="form-control item-input"
                                        placeholder="Contoh: K7BLK">
                                </td>
                                <td class="text-end">
                                    <input type="number" step="0.001" min="0.001"
                                        class="form-control mono qty-item" placeholder="0.000">
                                </td>
                                <td class="col-uom">
                                    <input type="text" class="form-control uom-input" value="pcs" disabled>
                                </td>
                                <td class="text-end col-fabric">
                                    <input type="number" step="0.001" min="0"
                                        class="form-control mono fabric-qty" value="0" disabled>
                                </td>
                                <td class="col-actions">
                                    <button type="button" class="btn btn-outline-danger btn-sm btnRemove"
                                        title="Hapus">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <small class="text-muted d-block mt-1">
                    • Qty Bahan (kg) akan dibagi otomatis proporsional berdasarkan Qty Item.<br>
                    • Data ini nanti bisa dikirim ke backend untuk dibuat LOT BSJ per SKU.
                </small>
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Simpan Cutting
                </button>
            </div>
        </form>
    </div>

    <!-- ================== JAVASCRIPT INTERAKTIF ================== -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const lotSelect = document.getElementById('lotSelect');
            const qtyHeader = document.getElementById('qtyUsed');
            const unitHeader = document.getElementById('unitUsed');
            const linesBody = document.getElementById('linesBody');
            const addRowBtn = document.getElementById('addRow');

            // === pilih LOT dari kartu → isi select + qty + uom ===
            document.querySelectorAll('.lot-pick-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const code = btn.dataset.lotCode;
                    const qty = parseFloat(btn.dataset.lotQty || 0);
                    const unit = btn.dataset.lotUnit || 'kg';

                    // pilih option LOT di <select>
                    if (lotSelect) {
                        lotSelect.value = code;
                    }

                    // isi header qty & unit
                    qtyHeader.value = qty || '';
                    unitHeader.value = unit;

                    // scroll ke form
                    document.getElementById('cuttingForm').scrollIntoView({
                        behavior: 'smooth'
                    });

                    // update distribusi kain
                    recalcFabricDistribution();
                });
            });

            // === kalau user ganti LOT dari <select>, update header ===
            if (lotSelect) {
                lotSelect.addEventListener('change', () => {
                    const opt = lotSelect.selectedOptions[0];
                    if (!opt) return;
                    const qty = parseFloat(opt.dataset.qty || 0);
                    const unit = opt.dataset.unit || 'kg';

                    qtyHeader.value = qty || '';
                    unitHeader.value = unit;

                    recalcFabricDistribution();
                });
            }

            // === fungsi hitung distribusi kain per baris ===
            function recalcFabricDistribution() {
                const headerKg = parseFloat(qtyHeader.value || 0);
                const qtyInputs = Array.from(linesBody.querySelectorAll('.qty-item'));
                const fabInputs = Array.from(linesBody.querySelectorAll('.fabric-qty'));

                const totalItem = qtyInputs.reduce((sum, el) => {
                    const v = parseFloat(el.value || 0);
                    return sum + (isNaN(v) ? 0 : v);
                }, 0);

                // kalau belum ada qty / header kosong → semua 0
                if (!headerKg || headerKg <= 0 || !totalItem || totalItem <= 0) {
                    fabInputs.forEach(f => f.value = 0);
                    return;
                }

                // bagi proporsional
                qtyInputs.forEach((el, idx) => {
                    const q = parseFloat(el.value || 0) || 0;
                    let share = (q / totalItem) * headerKg;
                    share = Math.round(share * 1000) / 1000; // 3 desimal
                    fabInputs[idx].value = share;
                });

                // koreksi selisih rounding (lempar ke baris terakhir)
                const totalKg = fabInputs.reduce((s, f) => s + (parseFloat(f.value || 0) || 0), 0);
                const diff = Math.round((headerKg - totalKg) * 1000) / 1000;
                if (Math.abs(diff) > 0.001 && fabInputs.length > 0) {
                    const last = fabInputs[fabInputs.length - 1];
                    last.value = Math.max(0, (parseFloat(last.value || 0) || 0) + diff);
                }
            }

            // === helper: wiring satu baris (event qty & tombol hapus) ===
            function wireRow(tr) {
                const qtyInput = tr.querySelector('.qty-item');
                const btnRemove = tr.querySelector('.btnRemove');

                if (qtyInput) {
                    qtyInput.addEventListener('input', recalcFabricDistribution);
                }
                if (btnRemove) {
                    btnRemove.addEventListener('click', () => {
                        tr.remove();
                        recalcFabricDistribution();
                    });
                }
            }

            // === fungsi tambah baris baru ===
            function addRow() {
                const tr = document.createElement('tr');
                tr.innerHTML = `
<td>
  <input type="text" class="form-control item-input" placeholder="Contoh: K7BLK">
</td>
<td class="text-end">
  <input type="number" step="0.001" min="0.001" class="form-control mono qty-item" placeholder="0.000">
</td>
<td class="col-uom">
  <input type="text" class="form-control uom-input" value="pcs" disabled>
</td>
<td class="text-end col-fabric">
  <input type="number" step="0.001" min="0" class="form-control mono fabric-qty" value="0" disabled>
</td>
<td class="col-actions">
  <button type="button" class="btn btn-outline-danger btn-sm btnRemove" title="Hapus">
    <i class="bi bi-x"></i>
  </button>
</td>
`;
                linesBody.appendChild(tr);
                wireRow(tr);
                recalcFabricDistribution();
                tr.querySelector('.item-input').focus();
            }

            // init baris awal
            Array.from(linesBody.querySelectorAll('tr')).forEach(wireRow);

            // tombol tambah baris
            addRowBtn.addEventListener('click', () => {
                addRow();
            });

            // enter di Qty item → tambah baris
            linesBody.addEventListener('keydown', (e) => {
                if (e.target.classList.contains('qty-item') && e.key === 'Enter') {
                    e.preventDefault();
                    addRow();
                }
            });

            // dummy submit (hanya alert)
            document.getElementById('cuttingForm').addEventListener('submit', (e) => {
                e.preventDefault();
                alert('Form cutting tersubmit (demo front-end saja).');
            });
        });
    </script>

</body>

</html>
