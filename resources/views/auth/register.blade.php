@extends('layouts.auth')

@section('title', 'Crear cuenta - Bienestar UPEMOR')

@section('content')
    <div class="auth-card">
        <section class="auth-visual auth-visual-register" aria-labelledby="register-benefits-title">
            <a class="auth-brand" href="{{ route('login') }}" aria-label="Bienestar UPEMOR, ir al inicio de sesión">
                <span class="brand-mark" aria-hidden="true">♡</span>
                <span>Bienestar <strong>UPEMOR</strong></span>
            </a>

            <div class="auth-copy auth-copy-register">
                <p class="auth-eyebrow">Bienestar emocional</p>
                <h1 id="register-benefits-title">Crea tu cuenta</h1>
                <p>Únete a Bienestar UPEMOR y comienza tu camino hacia una vida emocional más saludable.</p>
            </div>

            <x-auth.illustration />

            <div class="auth-benefits" aria-label="Beneficios de Bienestar UPEMOR">
                <div class="auth-benefit-item">
                    <span class="benefit-icon" aria-hidden="true">✓</span>
                    <span>Evalúa tu bienestar emocional</span>
                </div>
                <div class="auth-benefit-item">
                    <span class="benefit-icon" aria-hidden="true">✓</span>
                    <span>Agenda citas fácilmente</span>
                </div>
                <div class="auth-benefit-item">
                    <span class="benefit-icon" aria-hidden="true">✓</span>
                    <span>Accede a recursos y consejos</span>
                </div>
                <div class="auth-benefit-item">
                    <span class="benefit-icon" aria-hidden="true">✓</span>
                    <span>Privacidad y seguridad</span>
                </div>
            </div>
        </section>

        <section class="auth-form-panel" aria-labelledby="register-title">
            <div class="auth-form-header">
                <h2 id="register-title">Regístrate</h2>
                <a href="{{ route('login') }}" class="auth-help auth-link">¿Ya tienes cuenta? Inicia sesión</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Corrige los siguientes errores:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.store') }}" method="POST" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre completo</label>
                    <input
                        type="text"
                        name="nombre"
                        class="form-control @error('nombre') is-invalid @enderror"
                        id="nombre"
                        value="{{ old('nombre') }}"
                        required
                        autocomplete="given-name"
                        placeholder="Ingresa tu nombre"
                    >
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="apellido_paterno" class="form-label">Apellido paterno</label>
                        <input
                            type="text"
                            name="apellido_paterno"
                            class="form-control @error('apellido_paterno') is-invalid @enderror"
                            id="apellido_paterno"
                            value="{{ old('apellido_paterno') }}"
                            required
                            autocomplete="family-name"
                            placeholder="Apellido paterno"
                        >
                        @error('apellido_paterno')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="apellido_materno" class="form-label">Apellido materno</label>
                        <input
                            type="text"
                            name="apellido_materno"
                            class="form-control @error('apellido_materno') is-invalid @enderror"
                            id="apellido_materno"
                            value="{{ old('apellido_materno') }}"
                            autocomplete="family-name"
                            placeholder="Apellido materno"
                        >
                        @error('apellido_materno')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="email" class="form-label">Correo institucional</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        placeholder="ejemplo@upemor.edu.mx"
                    >
                    <div class="form-text">Utiliza tu correo institucional de la universidad.</div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="matricula" class="form-label">Matrícula</label>
                    <input
                        type="text"
                        name="matricula"
                        class="form-control @error('matricula') is-invalid @enderror"
                        id="matricula"
                        value="{{ old('matricula') }}"
                        required
                        placeholder="Ingresa tu matrícula"
                    >
                    <div class="form-text">Solo números y letras, sin espacios.</div>
                    @error('matricula')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="programa_id" class="form-label">Carrera</label>
                        <select
                            name="programa_id"
                            class="form-select @error('programa_id') is-invalid @enderror"
                            id="programa_id"
                            required
                        >
                            <option value="">Selecciona tu carrera</option>
                            @foreach ($programas as $programa)
                                <option
                                    value="{{ $programa->programa_id }}"
                                    {{ old('programa_id') == $programa->programa_id ? 'selected' : '' }}
                                >
                                    {{ $programa->nombre }} ({{ $programa->clave }})
                                </option>
                            @endforeach
                        </select>
                        @error('programa_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="cuatrimestre" class="form-label">Cuatrimestre</label>
                        <select
                            name="cuatrimestre"
                            class="form-select @error('cuatrimestre') is-invalid @enderror"
                            id="cuatrimestre"
                        >
                            <option value="">No aplica</option>
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('cuatrimestre') == $i ? 'selected' : '' }}>
                                    Cuatrimestre {{ $i }}
                                </option>
                            @endfor
                        </select>
                        @error('cuatrimestre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        required
                        autocomplete="new-password"
                        placeholder="Mínimo 12 caracteres"
                    >
                    <div class="form-text">Usa una contraseña segura con mayúsculas, minúsculas, números y símbolos.</div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        id="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repite tu contraseña"
                    >
                </div>

                <button type="submit" class="btn btn-primary auth-submit w-100">Registrarme</button>
            </form>

            <p class="auth-footer-note">
                Tus datos están protegidos y solo serán utilizados para brindarte una mejor experiencia.
            </p>
        </section>
    </div>
@endsection
