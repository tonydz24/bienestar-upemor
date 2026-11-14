@extends('layouts.auth')

@section('title', 'Recuperar contraseña - Bienestar UPEMOR')

@section('content')
    <div class="auth-card">
        <section class="auth-visual" aria-labelledby="auth-recover-title">
            <a class="auth-brand" href="{{ route('login') }}" aria-label="Bienestar UPEMOR, ir al inicio de sesión">
                <span class="brand-mark" aria-hidden="true">♡</span>
                <span>Bienestar <strong>UPEMOR</strong></span>
            </a>

            <div class="auth-copy">
                <p class="auth-eyebrow">Seguridad Institucional</p>
                <h1 id="auth-recover-title">Recupera tu acceso</h1>
                <p>Te ayudamos a restablecer tu contraseña de forma segura y confidencial.</p>
            </div>

            <x-auth.illustration />
        </section>

        <section class="auth-form-panel" aria-labelledby="forgot-title">
            <div class="auth-form-header">
                <h2 id="forgot-title">¿Olvidaste tu contraseña?</h2>
                <span class="auth-help">Paso 1 de 2</span>
            </div>

            <p class="text-muted small mb-4">
                Ingresa el correo institucional asociado a tu cuenta. Te enviaremos un enlace seguro para generar una nueva contraseña.
            </p>

            @if (session('status'))
                <div class="alert alert-success d-flex align-items-start gap-2 shadow-sm rounded-3 p-3 mb-4" role="status">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" class="text-success flex-shrink-0 mt-0.5">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <div>
                        <div class="fw-bold mb-1 text-success">Solicitud procesada con éxito</div>
                        <div class="small text-slate-700" style="line-height: 1.45;">{{ session('status') }}</div>
                    </div>
                </div>
            @endif

            @if ($errors->has('email'))
                <div class="alert alert-danger d-flex align-items-start gap-2 shadow-sm rounded-3 p-3 mb-4" role="alert">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-danger flex-shrink-0 mt-0.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div class="small fw-semibold">{{ $errors->first('email') }}</div>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
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
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary auth-submit w-100">
                    Enviar enlace de recuperación
                </button>
            </form>

            <p class="auth-footer-note text-center mt-4">
                ¿Recordaste tu contraseña? <a href="{{ route('login') }}" class="auth-link">Volver al inicio de sesión</a>
            </p>

            <p class="auth-footer-note">El enlace de recuperación es de un solo uso y expirará en 60 minutos por tu seguridad.</p>
        </section>
    </div>
@endsection

