@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check2-circle me-1"></i>{{ session('ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-1"></i>{{ session('err') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
