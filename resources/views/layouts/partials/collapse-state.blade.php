{{-- resources/views/layouts/partials/collapse-state.blade.php --}}
<script>
    (function() {
        // Simpan/restore state elemen collapse (kalau nanti dipakai)
        document.querySelectorAll('[data-save-state="collapse"]').forEach(el => {
            const id = el.getAttribute('id');
            if (!id) return;
            const key = 'collapse:' + id;

            try {
                if (localStorage.getItem(key) === 'hide') el.classList.remove('show');
            } catch (e) {}

            el.addEventListener('shown.bs.collapse', () => {
                try {
                    localStorage.setItem(key, 'show');
                } catch (e) {}
            });
            el.addEventListener('hidden.bs.collapse', () => {
                try {
                    localStorage.setItem(key, 'hide');
                } catch (e) {}
            });
        });
    })();
</script>
