<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6f42c1">
    <title>@yield('title', 'Bienestar UPEMOR')</title>
    @vite(['resources/css/bienestar-bootstrap.css', 'resources/js/bootstrap-app.js'])
</head>
<body class="bienestar-theme auth-page">
    <main class="auth-shell">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
