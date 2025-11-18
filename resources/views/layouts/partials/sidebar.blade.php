<aside class="sidebar d-none d-lg-block">
    <nav class="nav flex-column">

        {{-- ===================== --}}
        <div class="section">Dashboard</div>

        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>


        {{-- ===================== --}}
        <div class="section">Purchasing</div>

        @if (auth()->user()->hasRole('owner') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('finance'))
            <a class="nav-link {{ request()->routeIs('purchasing.invoices.*') ? 'active' : '' }}"
                href="{{ route('purchasing.invoices.index') }}">
                <i class="bi bi-receipt"></i><span>Invoices</span>
            </a>

            <a class="nav-link {{ request()->is('suppliers*') ? 'active' : '' }}" href="{{ url('/suppliers') }}">
                <i class="bi bi-people"></i><span>Suppliers</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Produksi</div>

        {{-- External Transfer (admin only) --}}
        @if (auth()->user()->hasRole('admin'))
            <a class="nav-link {{ request()->routeIs('inventory.external_transfers.*') ? 'active' : '' }}"
                href="{{ route('inventory.external_transfers.index') }}">
                <i class="bi bi-box-arrow-up-right"></i><span>External Transfer</span>
            </a>
        @endif

        {{-- Vendor Cutting (cutting/admin) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('cutting'))
            <a class="nav-link {{ request()->routeIs('production.vendor_cutting.*') ? 'active' : '' }}"
                href="{{ route('production.vendor_cutting.index') }}">
                <i class="bi bi-scissors"></i><span>Vendor Cutting</span>
            </a>
        @endif

        {{-- QC Cutting (cutting/admin) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('cutting'))
            <a class="nav-link {{ request()->routeIs('production.wip_cutting_qc.*') ? 'active' : '' }}"
                href="{{ route('production.wip_cutting_qc.index') }}">
                <i class="bi bi-clipboard-check"></i><span>QC Cutting</span>
            </a>
        @endif

        {{-- Ambil Jahit (sewing/admin/owner) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('sewing') || auth()->user()->hasRole('owner'))
            <a class="nav-link {{ request()->routeIs('production.sewing_picks.*') ? 'active' : '' }}"
                href="{{ route('production.sewing_picks.index') }}">
                <i class="bi bi-truck"></i><span>Ambil Jahit</span>
            </a>
        @endif

        {{-- Setor Jahit (sewing/admin/owner) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('sewing') || auth()->user()->hasRole('owner'))
            <a class="nav-link {{ request()->routeIs('production.sewing_returns.*') ? 'active' : '' }}"
                href="{{ route('production.sewing_returns.index') }}">
                <i class="bi bi-box-arrow-in-left"></i><span>Setor Jahit</span>
            </a>
        @endif

        {{-- Finishing (finishing/admin/owner) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('finishing') || auth()->user()->hasRole('owner'))
            <a class="nav-link {{ request()->routeIs('production.finishing.*') ? 'active' : '' }}"
                href="{{ route('production.finishing.index') }}">
                <i class="bi bi-check2-square"></i><span>Finishing</span>
            </a>
        @endif

        {{-- Packing (finishing/admin/owner) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('finishing') || auth()->user()->hasRole('owner'))
            <a class="nav-link {{ request()->routeIs('production.packing_jobs.*') ? 'active' : '' }}"
                href="{{ route('production.packing_jobs.index') }}">
                <i class="bi bi-box-seam"></i><span>Packing</span>
            </a>
        @endif

        {{-- Report Sisa Jahit (sewing/admin/owner) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('sewing') || auth()->user()->hasRole('owner'))
            <a class="nav-link {{ request()->routeIs('production.sewing_report.*') ? 'active' : '' }}"
                href="{{ route('production.sewing_report.operator_balance') }}">
                <i class="bi bi-people"></i><span>Report Sisa Jahit</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Inventory</div>

        @if (auth()->user()->hasRole('admin'))
            <a class="nav-link {{ request()->is('inventory/mutations*') ? 'active' : '' }}"
                href="{{ url('/inventory/mutations') }}">
                <i class="bi bi-arrow-left-right"></i><span>Mutasi</span>
            </a>

            <a class="nav-link {{ request()->is('inventory/stocks*') ? 'active' : '' }}"
                href="{{ url('/inventory/stocks') }}">
                <i class="bi bi-box-seam"></i><span>Stok Barang</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Master Data</div>

        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('owner'))
            <a class="nav-link {{ request()->routeIs('master.warehouses.*') ? 'active' : '' }}"
                href="{{ route('master.warehouses.index') }}">
                <i class="bi bi-buildings"></i><span>Gudang</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Payroll</div>

        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('owner') || auth()->user()->hasRole('finance'))
            <a class="nav-link {{ request()->is('payroll/rates*') ? 'active' : '' }}"
                href="{{ url('/payroll/rates') }}">
                <i class="bi bi-cash-coin"></i><span>Tarif Per Pcs</span>
            </a>

            <a class="nav-link {{ request()->is('payroll/entries*') ? 'active' : '' }}"
                href="{{ url('/payroll/entries') }}">
                <i class="bi bi-person-lines-fill"></i><span>Data Gaji</span>
            </a>

            <a class="nav-link {{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}"
                href="{{ route('payroll.runs.index') }}">
                <i class="bi bi-calculator"></i><span>Payroll per PCS</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Accounting</div>

        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('owner') || auth()->user()->hasRole('finance'))
            <a class="nav-link {{ request()->routeIs('accounting.journals.*') ? 'active' : '' }}"
                href="{{ route('accounting.journals.index') }}">
                <i class="bi bi-journal-text"></i><span>Jurnal</span>
            </a>

            <a class="nav-link {{ request()->routeIs('accounting.ledger') ? 'active' : '' }}"
                href="{{ route('accounting.ledger') }}">
                <i class="bi bi-columns-gap"></i><span>Ledger</span>
            </a>
        @endif

    </nav>
</aside>
