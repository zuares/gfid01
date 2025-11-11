<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNav">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>

            <div class="small-muted mt-3 mb-1 text-uppercase">Purchasing</div>
            <a class="nav-link {{ request()->routeIs('purchasing.invoices.*') ? 'active' : '' }}"
                href="{{ route('purchasing.invoices.index') }}">
                <i class="bi bi-receipt me-2"></i> Invoices
            </a>
            <a class="nav-link {{ request()->is('suppliers*') ? 'active' : '' }}" href="{{ url('/suppliers') }}">
                <i class="bi bi-people me-2"></i> Suppliers
            </a>

            <div class="small-muted mt-3 mb-1 text-uppercase">Produksi</div>
            <a class="nav-link {{ request()->is('cutting*') ? 'active' : '' }}" href="{{ url('/cutting') }}">
                <i class="bi bi-scissors me-2"></i> Cutting
            </a>
            <a class="nav-link {{ request()->is('sewing*') ? 'active' : '' }}" href="{{ url('/sewing') }}">
                <i class="bi bi-needle me-2"></i> Sewing
            </a>

            <div class="small-muted mt-3 mb-1 text-uppercase">Inventory</div>
            <a class="nav-link {{ request()->is('inventory/mutations*') ? 'active' : '' }}"
                href="{{ url('/inventory/mutations') }}">
                <i class="bi bi-arrow-left-right me-2"></i> Mutasi
            </a>
            <a class="nav-link {{ request()->is('inventory/stocks*') ? 'active' : '' }}"
                href="{{ url('/inventory/stocks') }}">
                <i class="bi bi-box-seam me-2"></i> Stok Barang
            </a>
            <a class="nav-link {{ request()->is('warehouses*') ? 'active' : '' }}" href="{{ url('/warehouses') }}">
                <i class="bi bi-buildings me-2"></i> Gudang
            </a>

            <div class="small-muted mt-3 mb-1 text-uppercase">Payroll</div>
            <a class="nav-link {{ request()->is('payroll/rates*') ? 'active' : '' }}"
                href="{{ url('/payroll/rates') }}">
                <i class="bi bi-cash-coin me-2"></i> Tarif Per Pcs
            </a>
            <a class="nav-link {{ request()->is('payroll/entries*') ? 'active' : '' }}"
                href="{{ url('/payroll/entries') }}">
                <i class="bi bi-person-lines-fill me-2"></i> Data Gaji
            </a>
        </nav>
    </div>
</div>
