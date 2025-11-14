<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formSection = document.getElementById('vc-form-section');
        const lotSelect = document.getElementById('lotSelect');
        const lotSelectCol = document.getElementById('lotSelectCol');
        const qtyHeader = document.getElementById('qtyUsed');
        const unitHeader = document.getElementById('unitUsed');
        const linesBody = document.getElementById('linesBody');
        const addRowBtn = document.getElementById('addRow');
        const hiddenLines = document.getElementById('vc-hidden-lines');
        const selectedTransferLineId = document.getElementById('selectedTransferLineId');
        const form = document.getElementById('cuttingForm');
        const picSelect = document.getElementById('picSelect');
        const defaultPic = picSelect ? picSelect.dataset.defaultPic : null;

        function showForm() {
            if (formSection && formSection.classList.contains('d-none')) {
                formSection.classList.remove('d-none');
            }
        }

        function hideLotSelect() {
            if (!lotSelectCol) return;
            lotSelectCol.classList.add('d-none');
            lotSelectCol.classList.remove('d-md-block');
        }

        function autoSelectPic() {
            if (!picSelect || !defaultPic) return;
            // Kalau user belum pilih PIC, set otomatis
            if (!picSelect.value) {
                const opt = Array.from(picSelect.options).find(o => o.value === defaultPic);
                if (opt) {
                    picSelect.value = defaultPic;
                }
            }
        }

        // === Ketika pilih LOT dari kartu ===
        document.querySelectorAll('.lot-pick-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const lineId = btn.dataset.lineId;
                const qty = parseFloat(btn.dataset.qty || 0);
                const unit = btn.dataset.unit || 'kg';

                if (!lineId) return;

                showForm();

                if (lotSelect) {
                    lotSelect.value = lineId;
                }

                qtyHeader.value = qty || '';
                unitHeader.value = unit;

                if (selectedTransferLineId) {
                    selectedTransferLineId.value = lineId;
                }

                autoSelectPic();
                hideLotSelect();
                formSection.scrollIntoView({
                    behavior: 'smooth'
                });
                recalcFabricDistribution();
            });
        });

        // === Kalau user ganti LOT dari select manual ===
        if (lotSelect) {
            lotSelect.addEventListener('change', () => {
                const opt = lotSelect.selectedOptions[0];
                if (!opt) return;

                const lineId = opt.dataset.transferId;
                const qty = parseFloat(opt.dataset.qty || 0);
                const unit = opt.dataset.unit || 'kg';

                qtyHeader.value = qty || '';
                unitHeader.value = unit;

                if (selectedTransferLineId) {
                    selectedTransferLineId.value = lineId || '';
                }

                showForm();
                autoSelectPic();
                hideLotSelect();
                recalcFabricDistribution();
            });
        }

        // === Hitung distribusi kain per baris output ===
        function recalcFabricDistribution() {
            const headerKg = parseFloat(qtyHeader.value || 0);
            const qtyInputs = Array.from(linesBody.querySelectorAll('.qty-item'));
            const fabInputs = Array.from(linesBody.querySelectorAll('.fabric-qty'));

            const totalItem = qtyInputs.reduce((sum, el) => {
                const v = parseFloat(el.value || 0);
                return sum + (isNaN(v) ? 0 : v);
            }, 0);

            if (!headerKg || headerKg <= 0 || !totalItem || totalItem <= 0) {
                fabInputs.forEach(f => f.value = 0);
                return;
            }

            qtyInputs.forEach((el, idx) => {
                const q = parseFloat(el.value || 0) || 0;
                let share = (q / totalItem) * headerKg;
                share = Math.round(share * 1000) / 1000;
                fabInputs[idx].value = share;
            });

            const totalKg = fabInputs.reduce((s, f) => s + (parseFloat(f.value || 0) || 0), 0);
            const diff = Math.round((headerKg - totalKg) * 1000) / 1000;
            if (Math.abs(diff) > 0.001 && fabInputs.length > 0) {
                const last = fabInputs[fabInputs.length - 1];
                last.value = Math.max(0, (parseFloat(last.value || 0) || 0) + diff);
            }
        }

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
</td>`;
            linesBody.appendChild(tr);
            wireRow(tr);
            recalcFabricDistribution();
            tr.querySelector('.item-input').focus();
        }

        Array.from(linesBody.querySelectorAll('tr')).forEach(wireRow);

        if (addRowBtn) {
            addRowBtn.addEventListener('click', () => addRow());
        }

        linesBody.addEventListener('keydown', (e) => {
            if (e.target.classList.contains('qty-item') && e.key === 'Enter') {
                e.preventDefault();
                addRow();
            }
        });

        if (form) {
            form.addEventListener('submit', (e) => {
                const lineId = selectedTransferLineId ? selectedTransferLineId.value : null;
                const headerKg = parseFloat(qtyHeader.value || 0);

                if (!lineId) {
                    e.preventDefault();
                    alert('Silakan pilih LOT kain terlebih dahulu.');
                    return;
                }

                if (!headerKg || headerKg <= 0) {
                    e.preventDefault();
                    alert('Qty kain belum terisi dari LOT.');
                    return;
                }

                if (hiddenLines) {
                    hiddenLines.innerHTML = '';
                    hiddenLines.insertAdjacentHTML('beforeend', `
                        <input type="hidden" name="lines[${lineId}][transfer_line_id]" value="${lineId}">
                        <input type="hidden" name="lines[${lineId}][input_qty]" value="${headerKg}">
                    `);
                }
            });
        }
    });
</script>
