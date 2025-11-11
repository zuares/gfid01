<style>
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
</style>
