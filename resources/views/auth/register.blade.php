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
                    <strong>Por favor, revisa y corrige los campos marcados en el formulario.</strong>
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
                        @error('nombre') aria-describedby="nombre-error" @enderror
                    >
                    @error('nombre')
                        <div id="nombre-error" class="invalid-feedback d-block">{{ $message }}</div>
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
                            @error('apellido_paterno') aria-describedby="apellido_paterno-error" @enderror
                        >
                        @error('apellido_paterno')
                            <div id="apellido_paterno-error" class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="apellido_materno" class="form-label">
                            Apellido materno <span class="text-muted small">(opcional)</span>
                        </label>
                        <input
                            type="text"
                            name="apellido_materno"
                            class="form-control @error('apellido_materno') is-invalid @enderror"
                            id="apellido_materno"
                            value="{{ old('apellido_materno') }}"
                            autocomplete="family-name"
                            placeholder="Apellido materno"
                            @error('apellido_materno') aria-describedby="apellido_materno-error" @enderror
                        >
                        @error('apellido_materno')
                            <div id="apellido_materno-error" class="invalid-feedback d-block">{{ $message }}</div>
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
                        @error('email') aria-describedby="email-error" @enderror
                    >
                    <div class="form-text">Utiliza tu correo institucional de la universidad (@upemor.edu.mx).</div>
                    @error('email')
                        <div id="email-error" class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="matricula" class="form-label">Matrícula</label>
                    <input
                        type="text"
                        name="matricula"
                        class="form-control text-uppercase @error('matricula') is-invalid @enderror"
                        id="matricula"
                        value="{{ old('matricula') }}"
                        required
                        maxlength="10"
                        placeholder="ADMO230179"
                        @error('matricula') aria-describedby="matricula-error" @enderror
                    >
                    @error('matricula')
                        <div id="matricula-error" class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="programa_id" class="form-label">Carrera</label>
                    <select
                        name="programa_id"
                        class="form-select @error('programa_id') is-invalid @enderror"
                        id="programa_id"
                        required
                        @error('programa_id') aria-describedby="programa_id-error" @enderror
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
                        <div id="programa_id-error" class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="cuatrimestre" class="form-label">Cuatrimestre</label>
                        <select
                            name="cuatrimestre"
                            class="form-select @error('cuatrimestre') is-invalid @enderror"
                            id="cuatrimestre"
                            required
                            @error('cuatrimestre') aria-describedby="cuatrimestre-error" @enderror
                        >
                            <option value="">Selecciona tu cuatrimestre</option>
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('cuatrimestre') == $i ? 'selected' : '' }}>
                                    Cuatrimestre {{ $i }}
                                </option>
                            @endfor
                        </select>
                        @error('cuatrimestre')
                            <div id="cuatrimestre-error" class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="grupo" class="form-label">Grupo</label>
                        <select
                            name="grupo"
                            class="form-select @error('grupo') is-invalid @enderror"
                            id="grupo"
                            required
                            @error('grupo') aria-describedby="grupo-error" @enderror
                        >
                            <option value="">Selecciona tu grupo</option>
                            @foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $letra)
                                <option value="{{ $letra }}" {{ old('grupo') == $letra ? 'selected' : '' }}>
                                    Grupo {{ $letra }}
                                </option>
                            @endforeach
                        </select>
                        @error('grupo')
                            <div id="grupo-error" class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
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
                            autocomplete="new-password"
                            placeholder="Mínimo 12 caracteres"
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
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5Z" />
                                <circle cx="12" cy="12" r="2.2" />
                            </svg>
                            <svg class="eye-slash d-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                <line x1="2" y1="2" x2="22" y2="22" />
                            </svg>
                        </button>
                    </div>
                    <div class="form-text">Usa una contraseña segura con mayúsculas, minúsculas, números y símbolos.</div>
                    @error('password')
                        <div id="password-error" class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                    <div class="input-group">
                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control @if($errors->has('password_confirmation') || ($errors->has('password') && str_contains((string) $errors->first('password'), 'no coinciden'))) is-invalid @endif"
                            id="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Repite tu contraseña"
                            @if($errors->has('password_confirmation') || ($errors->has('password') && str_contains((string) $errors->first('password'), 'no coinciden'))) aria-describedby="password-confirmation-error" @endif
                        >
                        <button
                            type="button"
                            class="auth-password-toggle"
                            id="togglePasswordConfirm"
                            aria-label="Mostrar confirmación de contraseña"
                            aria-controls="password_confirmation"
                            aria-pressed="false"
                        >
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5Z" />
                                <circle cx="12" cy="12" r="2.2" />
                            </svg>
                            <svg class="eye-slash d-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                <line x1="2" y1="2" x2="22" y2="22" />
                            </svg>
                        </button>
                    </div>
                    @if ($errors->has('password_confirmation'))
                        <div id="password-confirmation-error" class="invalid-feedback d-block">{{ $errors->first('password_confirmation') }}</div>
                    @elseif ($errors->has('password') && str_contains((string) $errors->first('password'), 'no coinciden'))
                        <div id="password-confirmation-error" class="invalid-feedback d-block">Las contraseñas no coinciden.</div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary auth-submit w-100">Registrarme</button>
            </form>

            <p class="auth-footer-note">
                Tus datos están protegidos y solo serán utilizados para brindarte una mejor experiencia.
            </p>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        function setupPasswordToggle(toggleId, inputId, labelText) {
            const toggleBtn = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            if (!toggleBtn || !input) return;

            toggleBtn.addEventListener('click', function () {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                toggleBtn.setAttribute('aria-label', isPassword ? `Ocultar ${labelText}` : `Mostrar ${labelText}`);
                toggleBtn.setAttribute('aria-pressed', String(isPassword));

                const eyeOpen = toggleBtn.querySelector('.eye-open');
                const eyeSlash = toggleBtn.querySelector('.eye-slash');
                if (eyeOpen && eyeSlash) {
                    eyeOpen.classList.toggle('d-none', isPassword);
                    eyeSlash.classList.toggle('d-none', !isPassword);
                }
            });
        }

        setupPasswordToggle('togglePassword', 'password', 'contraseña');
        setupPasswordToggle('togglePasswordConfirm', 'password_confirmation', 'confirmación de contraseña');
    </script>
@endpush
