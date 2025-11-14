<style>
    .vendor-cutting-page .mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    }

    .vendor-cutting-page .lots-container {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }

    .vendor-cutting-page .lot-card {
        border-radius: .9rem;
        border: 1px solid var(--line, #1f2933);
        background: var(--panel, #020617);
        padding: .85rem .9rem;
        transition: transform .12s ease, border-color .12s ease, box-shadow .12s ease;
    }

    .vendor-cutting-page .lot-card:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--brand, #3b82f6) 70%, var(--line, #1f2933) 30%);
        box-shadow: 0 6px 18px rgba(0, 0, 0, .18);
    }

    .vendor-cutting-page .lot-headline {
        font-size: .98rem;
        font-weight: 600;
        margin-bottom: .25rem;
    }

    .vendor-cutting-page .pill-sisa {
        background: rgba(16, 185, 129, .10);
        border: 1px solid rgba(16, 185, 129, .35);
        color: #6ee7b7;
        border-radius: 999px;
        padding: .15rem .55rem;
        font-size: .78rem;
    }

    .vendor-cutting-page .lots-help {
        color: var(--muted, #9ca3af);
        font-size: .85rem;
    }

    .vendor-cutting-page .cutting-row.table-active {
        background: color-mix(in srgb, var(--panel, #020617) 60%, var(--brand-soft, #1e293b) 40%);
    }
</style>
