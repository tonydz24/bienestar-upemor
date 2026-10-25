<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Bienestar UPEMOR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-login { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .btn-upemor { background-color: #6f42c1; color: white; font-weight: 600; }
        .btn-upemor:hover { background-color: #59359a; color: white; }
        .header-logo { color: #6f42c1; font-weight: bold; }
        .input-group-text { background: transparent; cursor: pointer; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <h2 class="header-logo">Bienestar UPEMOR</h2>
                    <p class="text-muted">Plataforma para el Bienestar Emocional Universitario</p>
                </div>

                {{-- Alerta de éxito al cerrar sesión --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Alerta general de error de credenciales (FF.1) --}}
                @if ($errors->has('email') || $errors->has('password'))
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ $errors->first('email') ?: $errors->first('password') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card card-login p-4">
                    <h4 class="mb-4 text-center">Iniciar Sesión</h4>

                    <form action="{{ route('login.post') }}" method="POST">
                        @csrf

                        {{-- Campo Correo --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Institucional</label>
                            <input type="email" name="email" class="form-control" id="email" value="{{ old('email') }}" required autofocus placeholder="ejemplo@upemor.edu.mx">
                        </div>

                        {{-- Campo Contraseña con Toggle Ojo --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" id="password" required placeholder="••••••••">
                                <span class="input-group-text" id="togglePassword">
                                    <i class="bi bi-eye-slash" id="eyeIcon"></i>
                                </span>
                            </div>
                        </div>

                        {{-- Recordarme --}}
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Recordarme</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-upemor w-100 py-2 mb-3">Ingresar al Sistema</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para alternar la visibilidad de la contraseña
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>