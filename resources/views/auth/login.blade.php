@extends('layouts.auth')

@section('title', 'Iniciar sesión - Bienestar UPEMOR')

@section('content')
    <div class="auth-card">
        <section class="auth-visual" aria-labelledby="auth-welcome-title">
            <a class="auth-brand" href="{{ route('login') }}" aria-label="Bienestar UPEMOR, ir al inicio de sesión">
                <span class="brand-mark" aria-hidden="true">♡</span>
                <span>Bienestar <strong>UPEMOR</strong></span>
            </a>

            <div class="auth-copy">
                <p class="auth-eyebrow">Bienestar emocional</p>
                <h1 id="auth-welcome-title">Bienvenido de vuelta</h1>
                <p>Inicia sesión para continuar cuidando tu bienestar.</p>
            </div>

            <x-auth.illustration />
        </section>

        <section class="auth-form-panel" aria-labelledby="login-title">
            <div class="auth-form-header">
                <h2 id="login-title">Iniciar Sesión</h2>
                <span class="auth-help">Acceso seguro</span>
            </div>

            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif

            @if ($errors->has('email') || $errors->has('password'))
                <div class="alert alert-danger" role="alert">
                    {{ $errors->first('email') ?: $errors->first('password') }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Correo institucional</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="ejemplo@upemor.edu.mx"
                        @error('email') aria-describedby="email-error" @enderror
                    >
                    @error('email')
                        <div id="email-error" class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <input
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            @error('password') aria-describedby="password-error" @enderror
                        >
                        <button
                            type="button"
                            class="auth-password-toggle"
                            id="togglePassword"
                            aria-label="Mostrar contraseña"
                            aria-controls="password"
                            aria-pressed="false"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5-9.5-5Z" />
                                <circle cx="12" cy="12" r="2.2" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div id="password-error" class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Recordarme</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit w-100">Ingresar al sistema</button>
            </form>

            <p class="auth-footer-note text-center">
                ¿Eres estudiante? <a href="{{ route('register') }}" class="auth-link">Regístrate</a>
            </p>

            <p class="auth-footer-note">Tu información está protegida con medidas de seguridad para cuidar tus datos.</p>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword?.addEventListener('click', () => {
            const isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            togglePassword.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
            togglePassword.setAttribute('aria-pressed', String(isPassword));
        });
    </script>
@endpush