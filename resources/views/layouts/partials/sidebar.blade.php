<aside class="sidebar desktop-fixed d-none d-lg-flex flex-column">
    <div class="p-3">
        <div class="brand mb-2">ERP • App</div>
        <div class="text-muted small">Navigasi</div>
    </div>

    <div class="px-2 pb-3 scroll-area" tabindex="0">
        @php
            /** @var \App\Models\User $user */
            $user = auth()->user();

            $isDashboard = request()->routeIs('dashboard');

            // GROUP FLAG
            $isPurchasing = request()->routeIs('purchasing.*') || request()->is('suppliers*');

            $isProdCutting =
                request()->routeIs('inventory.external_transfers.*') ||
                request()->routeIs('production.vendor_cutting.*') ||
                request()->routeIs('production.wip_cutting_qc.*');

            $isProdJahit =
                request()->routeIs('production.sewing_picks.*') ||
                request()->routeIs('production.sewing_returns.*') ||
                request()->routeIs('production.sewing_report.*');

            $isProdFinishing =
                request()->routeIs('production.finishing.*') || request()->routeIs('production.packing_jobs.*');

            $isProduction = $isProdCutting || $isProdJahit || $isProdFinishing;

            $isInventory =
                request()->routeIs('inventory.*') ||
                request()->routeIs('inventory.stock_position.*') ||
                request()->is('inventory/*');

            $isMaster = request()->routeIs('master.warehouses.*');

            $isPayroll = request()->is('payroll/*') || request()->routeIs('payroll.runs.*');

            $isAccounting = request()->routeIs('accounting.journals.*') || request()->routeIs('accounting.ledger');
        @endphp

        <nav class="nav flex-column" role="navigation" aria-label="Sidebar">

            {{-- ===================== --}}
            {{-- HOME / DASHBOARD      --}}
            {{-- ===================== --}}
            <a href="{{ route('dashboard') }}" class="nav-link mx-1 mb-1 {{ $isDashboard ? 'active' : '' }}">
                <i class="bi bi-house-door me-2"></i>
                <span>Dashboard</span>
            </a>


            {{-- ===================== --}}
            {{-- PURCHASING           --}}
            {{-- ===================== --}}
            @if ($user->hasRole('owner') || $user->hasRole('admin') || $user->hasRole('finance'))
                <div class="nav-group-title mt-2">Purchasing</div>

                <a class="nav-link mx-1 mb-1 {{ $isPurchasing ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    href="#navPurchasing" aria-expanded="{{ $isPurchasing ? 'true' : 'false' }}">
                    <i class="bi bi-bag me-2"></i> Purchasing
                    <i class="bi bi-chevron-down chev"></i>
                </a>

                <div id="navPurchasing" class="collapse {{ $isPurchasing ? 'show' : '' }}" data-persist-collapse
                    style="visibility: visible;">
                    <a class="nav-link mx-2 mb-1 {{ request()->routeIs('purchasing.invoices.*') ? 'active' : '' }}"
                        href="{{ route('purchasing.invoices.index') }}">
                        <i class="bi bi-receipt me-2"></i>
                        <span>Invoices</span>
                    </a>
                    <a class="nav-link mx-2 mb-1 {{ request()->is('suppliers*') ? 'active' : '' }}"
                        href="{{ url('/suppliers') }}">
                        <i class="bi bi-people me-2"></i>
                        <span>Suppliers</span>
                    </a>
                </div>
            @endif


            {{-- ===================== --}}
            {{-- PRODUKSI (CUT/JAHIT/FINISH) --}}
            {{-- ===================== --}}
            @if (
                $user->hasRole('admin') ||
                    $user->hasRole('cutting') ||
                    $user->hasRole('sewing') ||
                    $user->hasRole('finishing') ||
                    $user->hasRole('owner'))
                <div class="nav-group-title mt-2">Produksi</div>

                {{-- MAIN PRODUKSI TOGGLER --}}
                <a class="nav-link mx-1 mb-1 {{ $isProduction ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    href="#navProduksi" aria-expanded="{{ $isProduction ? 'true' : 'false' }}">
                    <i class="bi bi-cpu me-2"></i> Produksi
                    <i class="bi bi-chevron-down chev"></i>
                </a>

                <div id="navProduksi" class="collapse {{ $isProduction ? 'show' : '' }}" data-persist-collapse
                    style="visibility: visible;">

                    {{-- ========== CUTTING FLOW (External + Vendor + QC) ========== --}}
                    @if ($user->hasRole('admin') || $user->hasRole('cutting') || $user->hasRole('owner'))
                        <a class="nav-link mx-2 mb-1 {{ $isProdCutting ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                            href="#navCutting" aria-expanded="{{ $isProdCutting ? 'true' : 'false' }}">
                            <i class="bi bi-scissors me-2"></i> Cutting
                            <i class="bi bi-chevron-down chev"></i>
                        </a>
                        <div id="navCutting" class="collapse {{ $isProdCutting ? 'show' : '' }}" data-persist-collapse
                            style="visibility: visible;">

                            {{-- External Transfer (admin only) --}}
                            @if ($user->hasRole('admin'))
                                <a href="{{ route('inventory.external_transfers.index') }}"
                                    class="nav-link mx-3 mb-1 {{ request()->routeIs('inventory.external_transfers.index') ? 'active' : '' }}">
                                    <i class="bi bi-box-arrow-up-right me-2"></i>
                                    <span>Daftar Transfer</span>
                                </a>
                                <a href="{{ route('inventory.external_transfers.create', ['type' => 'cutting']) }}"
                                    class="nav-link mx-3 mb-1 {{ request()->routeIs('inventory.external_transfers.create') ? 'active' : '' }}">
                                    <i class="bi bi-plus-square me-2"></i>
                                    <span>Buat Transfer Cutting</span>
                                </a>
                            @endif

                            {{-- Vendor Cutting --}}
                            @if ($user->hasRole('admin') || $user->hasRole('cutting'))
                                <a href="{{ route('production.vendor_cutting.index') }}"
                                    class="nav-link mx-3 mb-1 {{ request()->routeIs('production.vendor_cutting.*') ? 'active' : '' }}">
                                    <i class="bi bi-list-ul me-2"></i>
                                    <span>Vendor Cutting</span>
                                </a>

                                {{-- QC Cutting --}}
                                <a href="{{ route('production.wip_cutting_qc.index') }}"
                                    class="nav-link mx-3 mb-1 {{ request()->routeIs('production.wip_cutting_qc.*') ? 'active' : '' }}">
                                    <i class="bi bi-clipboard-check me-2"></i>
                                    <span>QC Cutting</span>
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- ========== JAHIT FLOW (Ambil / Setor / Report) ========== --}}
                    @if ($user->hasRole('admin') || $user->hasRole('sewing') || $user->hasRole('owner'))
                        <a class="nav-link mx-2 mb-1 {{ $isProdJahit ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                            href="#navJahit" aria-expanded="{{ $isProdJahit ? 'true' : 'false' }}">
                            <i class="bi bi-threads-fill me-2"></i> Jahit
                            <i class="bi bi-chevron-down chev"></i>
                        </a>
                        <div id="navJahit" class="collapse {{ $isProdJahit ? 'show' : '' }}" data-persist-collapse
                            style="visibility: visible;">

                            {{-- Ambil Jahit --}}
                            <a href="{{ route('production.sewing_picks.index') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.sewing_picks.index') ? 'active' : '' }}">
                                <i class="bi bi-truck me-2"></i>
                                <span>Daftar Ambil Jahit</span>
                            </a>
                            <a href="{{ route('production.sewing_picks.create') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.sewing_picks.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-square me-2"></i>
                                <span>Buat Dokumen Ambil</span>
                            </a>

                            {{-- Setor Jahit --}}
                            <a href="{{ route('production.sewing_returns.index') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.sewing_returns.index') ? 'active' : '' }}">
                                <i class="bi bi-box-arrow-in-left me-2"></i>
                                <span>Daftar Setor Jahit</span>
                            </a>
                            <a href="{{ route('production.sewing_returns.create') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.sewing_returns.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-square me-2"></i>
                                <span>Input Setor Jahit</span>
                            </a>

                            {{-- Report Sisa Jahit --}}
                            <a href="{{ route('production.sewing_report.operator_balance') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.sewing_report.*') ? 'active' : '' }}">
                                <i class="bi bi-people me-2"></i>
                                <span>Report Sisa Jahit</span>
                            </a>
                        </div>
                    @endif

                    {{-- ========== FINISHING & PACKING ========== --}}
                    @if ($user->hasRole('admin') || $user->hasRole('finishing') || $user->hasRole('owner'))
                        <a class="nav-link mx-2 mb-1 {{ $isProdFinishing ? '' : 'collapsed' }}"
                            data-bs-toggle="collapse" href="#navFinishing"
                            aria-expanded="{{ $isProdFinishing ? 'true' : 'false' }}">
                            <i class="bi bi-check2-square me-2"></i> Finishing
                            <i class="bi bi-chevron-down chev"></i>
                        </a>
                        <div id="navFinishing" class="collapse {{ $isProdFinishing ? 'show' : '' }}"
                            data-persist-collapse style="visibility: visible;">

                            <a href="{{ route('production.finishing.index') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.finishing.index') ? 'active' : '' }}">
                                <i class="bi bi-list-ul me-2"></i>
                                <span>Daftar Finishing</span>
                            </a>
                            <a href="{{ route('production.finishing.create') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.finishing.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-square me-2"></i>
                                <span>Input Finishing</span>
                            </a>

                            <a href="{{ route('production.packing_jobs.index') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.packing_jobs.index') ? 'active' : '' }}">
                                <i class="bi bi-box-seam me-2"></i>
                                <span>Daftar Packing</span>
                            </a>
                            <a href="{{ route('production.packing_jobs.create') }}"
                                class="nav-link mx-3 mb-1 {{ request()->routeIs('production.packing_jobs.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-square me-2"></i>
                                <span>Buat Packing</span>
                            </a>
                        </div>
                    @endif
                </div>
            @endif


            {{-- ===================== --}}
            {{-- INVENTORY            --}}
            {{-- ===================== --}}
            @if ($user->hasRole('admin'))
                <div class="nav-group-title mt-2">Inventory</div>

                <a class="nav-link mx-1 mb-1 {{ $isInventory ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    href="#navInventory" aria-expanded="{{ $isInventory ? 'true' : 'false' }}">
                    <i class="bi bi-boxes me-2"></i> Inventory
                    <i class="bi bi-chevron-down chev"></i>
                </a>

                <div id="navInventory" class="collapse {{ $isInventory ? 'show' : '' }}" data-persist-collapse
                    style="visibility: visible;">
                    <a href="{{ route('inventory.mutations.index') }}"
                        class="nav-link mx-2 mb-1 {{ request()->routeIs('inventory.mutations.index') || request()->is('inventory/mutations*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right me-2"></i>
                        <span>Mutasi</span>
                    </a>
                    <a href="{{ route('inventory.stocks.index') }}"
                        class="nav-link mx-2 mb-1 {{ request()->routeIs('inventory.stocks.index') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-data me-2"></i>
                        <span>Stok Barang</span>
                    </a>
                    <a href="{{ route('inventory.stock_position.index') }}"
                        class="nav-link mx-2 mb-1 {{ request()->routeIs('inventory.stock_position.index') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-data me-2"></i>
                        <span>Report Posisi Stok</span>
                    </a>
                </div>
            @endif


            {{-- ===================== --}}
            {{-- MASTER DATA          --}}
            {{-- ===================== --}}
            @if ($user->hasRole('admin') || $user->hasRole('owner'))
                <div class="nav-group-title mt-2">Master Data</div>

                <a class="nav-link mx-1 mb-1 {{ $isMaster ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    href="#navMaster" aria-expanded="{{ $isMaster ? 'true' : 'false' }}">
                    <i class="bi bi-grid-3x3-gap me-2"></i> Master Data
                    <i class="bi bi-chevron-down chev"></i>
                </a>

                <div id="navMaster" class="collapse {{ $isMaster ? 'show' : '' }}" data-persist-collapse
                    style="visibility: visible;">
                    <a href="{{ route('master.warehouses.index') }}"
                        class="nav-link mx-2 mb-1 {{ request()->routeIs('master.warehouses.*') ? 'active' : '' }}">
                        <i class="bi bi-buildings me-2"></i>
                        <span>Gudang</span>
                    </a>
                </div>
            @endif


            {{-- ===================== --}}
            {{-- PAYROLL              --}}
            {{-- ===================== --}}
            @if ($user->hasRole('admin') || $user->hasRole('owner') || $user->hasRole('finance'))
                <div class="nav-group-title mt-2">Payroll</div>

                <a class="nav-link mx-1 mb-1 {{ $isPayroll ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    href="#navPayroll" aria-expanded="{{ $isPayroll ? 'true' : 'false' }}">
                    <i class="bi bi-cash-coin me-2"></i> Payroll
                    <i class="bi bi-chevron-down chev"></i>
                </a>

                <div id="navPayroll" class="collapse {{ $isPayroll ? 'show' : '' }}" data-persist-collapse
                    style="visibility: visible;">
                    <a href="{{ url('/payroll/rates') }}"
                        class="nav-link mx-2 mb-1 {{ request()->is('payroll/rates*') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin me-2"></i>
                        <span>Tarif Per Pcs</span>
                    </a>
                    <a href="{{ url('/payroll/entries') }}"
                        class="nav-link mx-2 mb-1 {{ request()->is('payroll/entries*') ? 'active' : '' }}">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        <span>Data Gaji</span>
                    </a>
                    <a href="{{ route('payroll.runs.index') }}"
                        class="nav-link mx-2 mb-1 {{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}">
                        <i class="bi bi-calculator me-2"></i>
                        <span>Payroll per PCS</span>
                    </a>
                </div>
            @endif


            {{-- ===================== --}}
            {{-- ACCOUNTING           --}}
            {{-- ===================== --}}
            @if ($user->hasRole('admin') || $user->hasRole('owner') || $user->hasRole('finance'))
                <div class="nav-group-title mt-2">Accounting</div>

                <a class="nav-link mx-1 mb-1 {{ $isAccounting ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    href="#navAccounting" aria-expanded="{{ $isAccounting ? 'true' : 'false' }}">
                    <i class="bi bi-journal-text me-2"></i> Accounting
                    <i class="bi bi-chevron-down chev"></i>
                </a>

                <div id="navAccounting" class="collapse {{ $isAccounting ? 'show' : '' }}" data-persist-collapse
                    style="visibility: visible;">
                    <a href="{{ route('accounting.journals.index') }}"
                        class="nav-link mx-2 mb-1 {{ request()->routeIs('accounting.journals.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i>
                        <span>Jurnal</span>
                    </a>
                    <a href="{{ route('accounting.ledger') }}"
                        class="nav-link mx-2 mb-1 {{ request()->routeIs('accounting.ledger') ? 'active' : '' }}">
                        <i class="bi bi-columns-gap me-2"></i>
                        <span>Ledger</span>
                    </a>
                </div>
            @endif

        </nav>
    </div>
</aside>
