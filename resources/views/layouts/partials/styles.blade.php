<style>
    :root {
        /* === Warna dasar tema (dark-friendly & soft contrast) === */
        --bg: color-mix(in srgb, #0d1117 8%, #ffffff 92%);
        --fg: color-mix(in srgb, #000 90%, #fff 10%);
        --card: color-mix(in srgb, var(--bg) 96%, var(--bs-primary) 4%);
        --line: color-mix(in srgb, var(--bs-border-color) 78%, var(--bg) 22%);
        --muted: color-mix(in srgb, var(--fg) 60%, var(--bg) 40%);

        /* === Aksen sistem === */
        --in: var(--bs-teal);
        --out: var(--bs-orange);
        --accent: var(--bs-primary);

        /* === Tipografi === */
        --radius: 14px;
        --font-sans: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        --font-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    body {
        background: var(--bg);
        color: var(--fg);
        font-family: var(--font-sans);
        transition: background .2s, color .2s;
    }

    .card {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        transition: background .2s, border-color .2s;
    }

    .btn-ghost {
        border: 1px solid var(--line);
        background: transparent;
        color: var(--fg);
    }

    .muted {
        color: var(--muted);
    }

    .mono {
        font-family: var(--font-mono);
        font-variant-numeric: tabular-nums;
    }

    :root {
        --bg: #fff;
        --fg: #0f172a;
        --muted: #64748b;
        --card: #fff;
        --line: rgba(15, 23, 42, .08);
        --brand: #0ea5e9;
    }

    :root[data-bs-theme="dark"] {
        --bg: #0b1220;
        --fg: #e2e8f0;
        --muted: #94a3b8;
        --card: #0f172a;
        --line: rgba(148, 163, 184, .18);
        --brand: #22d3ee;
    }

    html,
    body {
        height: 100%
    }

    body {
        background: var(--bg);
        color: var(--fg);
        -webkit-font-smoothing: antialiased
    }

    a {
        color: var(--brand)
    }

    a:hover {
        opacity: .92
    }

    .with-topbar {
        padding-top: 56px
    }

    .content-wrap {
        padding: 20px;
        margin-left: 240px
    }

    @media (max-width:991.98px) {
        .content-wrap {
            margin-left: 0
        }
    }

    .topbar {
        position: fixed;
        inset: 0 0 auto 0;
        height: 56px;
        background: var(--card);
        border-bottom: 1px solid var(--line);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
        z-index: 1030
    }

    .topbar .brand {
        font-weight: 600
    }

    .topbar .btn {
        border-radius: 10px
    }

    .sidebar {
        position: fixed;
        top: 56px;
        left: 0;
        width: 240px;
        height: calc(100% - 56px);
        border-right: 1px solid var(--line);
        background: var(--card);
        padding: 12px 8px;
        overflow-y: auto;
        z-index: 1029
    }

    .sidebar .section {
        color: var(--muted);
        font-size: .75rem;
        letter-spacing: .06em;
        margin: 10px 10px 6px
    }

    .sidebar .nav-link {
        display: flex;
        align-items: center;
        gap: .6rem;
        color: var(--fg);
        border-radius: 8px;
        padding: .5rem .65rem
    }

    .sidebar .nav-link i {
        font-size: 1rem;
        opacity: .9;
        min-width: 20px;
        text-align: center
    }

    .sidebar .nav-link.active {
        background: rgba(14, 165, 233, .15);
        border: 1px solid rgba(14, 165, 233, .25);
        color: var(--brand);
        font-weight: 500
    }

    .card {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px
    }

    .table th,
    .table td {
        vertical-align: middle
    }

    .small-muted {
        color: var(--muted);
        font-size: .85rem
    }

    .mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace
    }

    .table tbody tr:hover {
        background: color-mix(in srgb, var(--card) 65%, var(--line) 35%);
        box-shadow: inset 0 0 0 9999px rgba(255, 255, 255, 0.02);
    }

    .table tbody tr:hover td {
        background: transparent !important;
    }

    .table tbody tr:hover {
        border-radius: 8px;
    }

    .btn-ghost:hover i,
    .btn-outline-primary:hover i {
        transform: scale(1.15);
        transition: transform .15s ease;
    }

    .table tbody tr:hover {
        background: color-mix(in srgb, var(--card) 55%, var(--line) 45%);
        box-shadow: inset 0 0 0 9999px rgba(255, 255, 255, 0.04);
        transform: scale(1.002);
    }
</style>
