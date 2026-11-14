<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6f42c1">
    <title>@yield('title', 'Administración - Bienestar UPEMOR')</title>
    <!-- Bootstrap Icons (Local institucional con fallback a CDN) -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" onerror="this.onerror=null;this.href='{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}';">
    @vite(['resources/css/bienestar-bootstrap.css', 'resources/js/bootstrap-app.js'])
</head>
<body class="bienestar-theme admin-page">
    <header class="admin-topbar">
        <div class="admin-topbar-inner">
            <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                <span class="brand-mark" aria-hidden="true">♡</span>
                <span>Bienestar <strong>UPEMOR</strong></span>
            </a>

            <nav class="admin-nav" aria-label="Administración">
                <a href="{{ route('admin.solicitudes.index') }}"
                   @if (request()->routeIs('admin.solicitudes.*')) aria-current="page" @endif>
                    Solicitudes de alumnos
                </a>
            </nav>

            <form action="{{ route('logout') }}" method="POST" class="ms-auto">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <main class="admin-content">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
