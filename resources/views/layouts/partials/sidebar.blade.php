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
        <a class="nav-link {{ request()->is('cutting*') ? 'active' : '' }}" href="{{ url('/cutting') }}">
            <i class="bi bi-scissors"></i><span>Cutting</span>
        </a>
        <a class="nav-link {{ request()->is('sewing*') ? 'active' : '' }}" href="{{ url('/sewing') }}">
            <i class="bi bi-needle"></i><span>Sewing</span>
        </a>

        {{-- External Transfer --}}
        <a class="nav-link {{ request()->routeIs('production.external.*') ? 'active' : '' }}"
            href="{{ route('production.external.index') }}">
            <i class="bi bi-box-arrow-up-right"></i><span>External Transfer</span>
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

        {{-- Gudang pindah ke Master --}}
        <a class="nav-link {{ request()->routeIs('master.warehouses.*') ? 'active' : '' }}"
            href="{{ route('master.warehouses.index') }}">
            <i class="bi bi-buildings"></i><span>Gudang</span>
        </a>

        {{-- Karyawan, Item, dll → bisa ditambah nanti --}}
        {{-- <a class="nav-link" href="#"><i class="bi bi-person-badge"></i><span>Karyawan</span></a> --}}

        {{-- ===================== --}}
        <div class="section">Payroll</div>
        <a class="nav-link {{ request()->is('payroll/rates*') ? 'active' : '' }}" href="{{ url('/payroll/rates') }}">
            <i class="bi bi-cash-coin"></i><span>Tarif Per Pcs</span>
        </a>
        <a class="nav-link {{ request()->is('payroll/entries*') ? 'active' : '' }}"
            href="{{ url('/payroll/entries') }}">
            <i class="bi bi-person-lines-fill"></i><span>Data Gaji</span>
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
