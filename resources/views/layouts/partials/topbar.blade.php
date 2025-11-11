<header class="topbar">
    <button class="btn btn-outline-secondary me-2 d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav">
        <i class="bi bi-list"></i>
    </button>

    <div class="brand me-2">Greatfit ERP</div>

    <div class="flex-grow-1 d-none d-md-flex">
        <input class="form-control form-control-sm" placeholder="Cari…" aria-label="Search">
    </div>

    <button class="btn btn-outline-secondary" onclick="switchTheme()" title="Dark / Light">
        <i class="bi bi-circle-half"></i>
    </button>

    @auth
        <div class="dropdown ms-2">
            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="dropdown-header small">Signed in as <strong>{{ auth()->user()->email }}</strong></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    @endauth
</header>
