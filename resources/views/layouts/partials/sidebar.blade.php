<aside class="sidebar d-none d-lg-block">
    <nav class="nav flex-column">

        {{-- ===================== --}}
        <div class="section">Dashboard</div>

        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>

        {{-- ===================== --}}
        <div class="section">Purchasing</div>

        <a class="nav-link {{ request()->routeIs('purchasing.invoices.*') ? 'active' : '' }}"
            href="{{ route('purchasing.invoices.index') }}">
            <i class="bi bi-receipt"></i><span>Invoices</span>
        </a>

        <a class="nav-link {{ request()->is('suppliers*') ? 'active' : '' }}" href="{{ url('/suppliers') }}">
            <i class="bi bi-people"></i><span>Suppliers</span>
        </a>

        {{-- ===================== --}}
        <div class="section">Produksi</div>

        {{-- Cutting Internal --}}
        <a class="nav-link {{ request()->is('cutting*') ? 'active' : '' }}" href="{{ url('/cutting') }}">
            <i class="bi bi-scissors"></i><span>Cutting</span>
        </a>

        {{-- Sewing Internal --}}
        <a class="nav-link {{ request()->routeIs('sewing.*') ? 'active' : '' }}" href="{{ route('sewing.index') }}">
            <i class="bi bi-tools"></i><span>Sewing</span>
        </a>

        {{-- Finishing --}}
        <a class="nav-link {{ request()->routeIs('finishing.*') ? 'active' : '' }}"
            href="{{ route('finishing.index') }}">
            <i class="bi bi-check2-square"></i><span>Finishing</span>
        </a>

        {{-- External Transfer --}}
        <a class="nav-link {{ request()->routeIs('external-transfers.*') ? 'active' : '' }}"
            href="{{ route('external-transfers.index') }}">
            <i class="bi bi-box-arrow-up-right"></i><span>External Transfer</span>
        </a>

        {{-- Vendor Cutting --}}
        <a class="nav-link {{ request()->routeIs('vendor-cutting.*') ? 'active' : '' }}"
            href="{{ route('vendor-cutting.index') }}">
            <i class="bi bi-scissors"></i><span>Vendor Cutting</span>
        </a>

        {{-- ===================== --}}
        <div class="section">Inventory</div>

        <a class="nav-link {{ request()->is('inventory/mutations*') ? 'active' : '' }}"
            href="{{ url('/inventory/mutations') }}">
            <i class="bi bi-arrow-left-right"></i><span>Mutasi</span>
        </a>

        <a class="nav-link {{ request()->is('inventory/stocks*') ? 'active' : '' }}"
            href="{{ url('/inventory/stocks') }}">
            <i class="bi bi-box-seam"></i><span>Stok Barang</span>
        </a>

        {{-- ===================== --}}
        <div class="section">Master Data</div>

        <a class="nav-link {{ request()->routeIs('master.warehouses.*') ? 'active' : '' }}"
            href="{{ route('master.warehouses.index') }}">
            <i class="bi bi-buildings"></i><span>Gudang</span>
        </a>

        {{-- ===================== --}}
        <div class="section">Payroll</div>

        {{-- Tarif per pcs --}}
        <a class="nav-link {{ request()->is('payroll/rates*') ? 'active' : '' }}" href="{{ url('/payroll/rates') }}">
            <i class="bi bi-cash-coin"></i><span>Tarif Per Pcs</span>
        </a>

        {{-- Data gaji biasa --}}
        <a class="nav-link {{ request()->is('payroll/entries*') ? 'active' : '' }}"
            href="{{ url('/payroll/entries') }}">
            <i class="bi bi-person-lines-fill"></i><span>Data Gaji</span>
        </a>

        {{-- Payroll Per PCS (menu baru) --}}
        <a class="nav-link {{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}"
            href="{{ route('payroll.runs.index') }}">
            <i class="bi bi-calculator"></i><span>Payroll per PCS</span>
        </a>

        {{-- ===================== --}}
        <div class="section">Accounting</div>

        <a class="nav-link {{ request()->routeIs('accounting.journals.*') ? 'active' : '' }}"
            href="{{ route('accounting.journals.index') }}">
            <i class="bi bi-journal-text"></i><span>Jurnal</span>
        </a>

        <a class="nav-link {{ request()->routeIs('accounting.ledger') ? 'active' : '' }}"
            href="{{ route('accounting.ledger') }}">
            <i class="bi bi-columns-gap"></i><span>Ledger</span>
        </a>

    </nav>
</aside>
