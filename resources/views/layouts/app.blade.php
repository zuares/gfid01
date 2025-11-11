{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="id" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ERP • App')</title>

    {{-- Vendor --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Styles --}}
    @include('layouts.partials.styles')

    {{-- Set tema awal (auto from system, fallback dark) --}}
    <script>
        (function() {
            try {
                const chosen = localStorage.getItem('theme');
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-bs-theme', chosen || (prefersDark ? 'dark' : 'light'));
            } catch (e) {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            }
        })();
    </script>

    @stack('head')
</head>

<body class="with-topbar">
    @include('layouts.partials.topbar')
    @include('layouts.partials.sidebar')
    @include('layouts.partials.offcanvas')

    <main class="content-wrap">
        @include('layouts.partials.alerts')
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Theme switcher --}}
    <script>
        (function() {
            window.switchTheme = function() {
                const cur = document.documentElement.getAttribute('data-bs-theme') || 'dark';
                const next = cur === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                try {
                    localStorage.setItem('theme', next);
                } catch (e) {}
            };
        })();
    </script>

    @stack('scripts')
</body>

</html>
