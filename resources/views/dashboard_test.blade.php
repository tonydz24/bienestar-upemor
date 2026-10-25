<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal - Bienestar UPEMOR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Bienestar UPEMOR</a>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">Cerrar Sesión</button>
            </form>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="card p-4">
            <h3>Bienvenido(a), {{ Auth::user()->nombre }} {{ Auth::user()->apellido_paterno }}</h3>
            <p><strong>Perfil:</strong> {{ Auth::user()->rol->nombre }}</p>
            <p><strong>Correo:</strong> {{ Auth::user()->email }}</p>
            <div class="alert alert-info">
                Has iniciado sesión correctamente. En las siguientes etapas construiremos los módulos correspondientes a tu perfil.
            </div>
        </div>
    </div>
</body>
</html>