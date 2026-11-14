@extends('layouts.auth')

@section('title', 'Restablecer contraseña - Bienestar UPEMOR')

@section('content')
    <div class="auth-card">
        <section class="auth-visual" aria-labelledby="auth-reset-title">
            <a class="auth-brand" href="{{ route('login') }}" aria-label="Bienestar UPEMOR, ir al inicio de sesión">
                <span class="brand-mark" aria-hidden="true">♡</span>
                <span>Bienestar <strong>UPEMOR</strong></span>
            </a>

            <div class="auth-copy">
                <p class="auth-eyebrow">Protección de Cuenta</p>
                <h1 id="auth-reset-title">Nueva contraseña</h1>
                <p>Crea una nueva contraseña segura para tu cuenta en Bienestar UPEMOR.</p>
            </div>

            <x-auth.illustration />
        </section>

        <section class="auth-form-panel" aria-labelledby="reset-title">
            <div class="auth-form-header">
                <h2 id="reset-title">Crear Contraseña</h2>
                <span class="auth-help">Paso 2 de 2</span>
            </div>

            <p class="text-muted small mb-3">
                Define tu nueva clave institucional. Debe contener al menos 8 caracteres para mantener segura tu información.
            </p>

            @if ($errors->has('email'))
                <div class="alert alert-danger shadow-sm rounded-3 p-3 mb-4" role="alert">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-danger flex-shrink-0 mt-0.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <div class="small fw-semibold text-danger">{{ $errors->first('email') }}</div>
                    </div>
                    <div class="pt-1">
                        <a href="{{ route('password.request') }}" class="btn btn-outline-danger btn-sm rounded-pill fw-semibold">
                            Solicitar un nuevo enlace de recuperación
                        </a>
                    </div>
                </div>
            @elseif ($errors->any())
                <div class="alert alert-danger shadow-sm rounded-3 py-2 px-3 mb-3" role="alert">
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Correo institucional</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control bg-light @error('email') is-invalid @enderror"
                        id="email"
                        value="{{ old('email', $email) }}"
                        required
                        readonly
                    >
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Nueva contraseña</label>
                    <div class="input-group">
                        <input
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            required
                            autocomplete="new-password"
                            placeholder="Mínimo 8 caracteres"
                        >
                        <button
                            type="button"
                            class="auth-password-toggle btn btn-outline-secondary"
                            id="togglePassword"
                            aria-label="Mostrar contraseña"
                            aria-controls="password"
                            aria-pressed="false"
                        >
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5Z" />
                                <circle cx="12" cy="12" r="2.2" />
                            </svg>
                            <svg class="eye-slash d-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                <line x1="2" y1="2" x2="22" y2="22" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    {{-- Indicador de requisitos en tiempo real --}}
                    <div class="p-2 mt-2 rounded-3 bg-light border small text-muted" id="requisitosContainer" style="font-size: 0.76rem;">
                        <div class="fw-semibold text-dark mb-1">Requisitos de seguridad:</div>
                        <div id="reqMinLength" class="d-flex align-items-center gap-1.5 text-muted">
                            <span class="bullet">&bull;</span> Al menos 8 caracteres
                        </div>
                        <div id="reqAlphaNum" class="d-flex align-items-center gap-1.5 text-muted">
                            <span class="bullet">&bull;</span> Combina letras y números
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label small fw-semibold">Confirmar nueva contraseña</label>
                    <div class="input-group">
                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control"
                            id="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Repite la contraseña"
                        >
                        <button
                            type="button"
                            class="auth-password-toggle btn btn-outline-secondary"
                            id="togglePasswordConfirmation"
                            aria-label="Mostrar confirmación de contraseña"
                            aria-controls="password_confirmation"
                            aria-pressed="false"
                        >
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5Z" />
                                <circle cx="12" cy="12" r="2.2" />
                            </svg>
                            <svg class="eye-slash d-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                <line x1="2" y1="2" x2="22" y2="22" />
                            </svg>
                        </button>
                    </div>
                    {{-- Indicador visual de coincidencia --}}
                    <div id="matchIndicator" class="small mt-1" style="font-size: 0.76rem; display: none;"></div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit w-100 py-2.5 rounded-3 fw-semibold shadow-sm">
                    Restablecer y actualizar contraseña
                </button>
            </form>

            <p class="auth-footer-note text-center mt-4">
                ¿Prefieres no cambiarla? <a href="{{ route('login') }}" class="auth-link">Volver al inicio de sesión</a>
            </p>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        // Configuración de visibilidad para ambos campos de forma independiente
        function setupToggle(toggleId, inputId) {
            const btn = document.querySelector(toggleId);
            const input = document.querySelector(inputId);
            if (!btn || !input) return;

            btn.addEventListener('click', () => {
                const isPass = input.type === 'password';
                input.type = isPass ? 'text' : 'password';
                btn.setAttribute('aria-label', isPass ? 'Ocultar contraseña' : 'Mostrar contraseña');
                btn.setAttribute('aria-pressed', String(isPass));

                const eyeOpen = btn.querySelector('.eye-open');
                const eyeSlash = btn.querySelector('.eye-slash');
                if (eyeOpen && eyeSlash) {
                    eyeOpen.classList.toggle('d-none', isPass);
                    eyeSlash.classList.toggle('d-none', !isPass);
                }
            });
        }

        setupToggle('#togglePassword', '#password');
        setupToggle('#togglePasswordConfirmation', '#password_confirmation');

        // Validación visual interactiva en tiempo real
        const passInput = document.querySelector('#password');
        const confirmInput = document.querySelector('#password_confirmation');
        const reqMin = document.querySelector('#reqMinLength');
        const reqAlphaNum = document.querySelector('#reqAlphaNum');
        const matchInd = document.querySelector('#matchIndicator');

        function validatePasswordStrength() {
            const val = passInput.value;
            const hasMin = val.length >= 8;
            const hasAlpha = /[a-zA-Z]/.test(val);
            const hasNum = /[0-9]/.test(val);

            // Requisito longitud
            if (hasMin) {
                reqMin.className = 'd-flex align-items-center gap-1.5 text-success fw-medium';
                reqMin.innerHTML = '<span class="text-success">&check;</span> Al menos 8 caracteres';
            } else {
                reqMin.className = 'd-flex align-items-center gap-1.5 text-muted';
                reqMin.innerHTML = '<span class="bullet">&bull;</span> Al menos 8 caracteres';
            }

            // Requisito letras y números
            if (hasAlpha && hasNum) {
                reqAlphaNum.className = 'd-flex align-items-center gap-1.5 text-success fw-medium';
                reqAlphaNum.innerHTML = '<span class="text-success">&check;</span> Combina letras y números';
            } else {
                reqAlphaNum.className = 'd-flex align-items-center gap-1.5 text-muted';
                reqAlphaNum.innerHTML = '<span class="bullet">&bull;</span> Combina letras y números';
            }

            checkMatch();
        }

        function checkMatch() {
            const p = passInput.value;
            const c = confirmInput.value;

            if (!c && !p) {
                matchInd.style.display = 'none';
                return;
            }

            matchInd.style.display = 'block';
            if (p === c && p.length > 0) {
                matchInd.className = 'small mt-1 text-success fw-semibold';
                matchInd.innerHTML = '&check; Las contraseñas coinciden perfectamente.';
            } else if (c.length > 0) {
                matchInd.className = 'small mt-1 text-danger';
                matchInd.innerHTML = '&times; Las contraseñas aún no coinciden.';
            } else {
                matchInd.style.display = 'none';
            }
        }

        passInput?.addEventListener('input', validatePasswordStrength);
        confirmInput?.addEventListener('input', checkMatch);
    </script>
@endpush
