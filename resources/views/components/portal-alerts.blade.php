{{-- Contenedor accesible y unificado de notificaciones del portal --}}
<div class="portal-alerts-stack" id="portalAlertsStack" aria-live="polite">
    {{-- Mensaje de Éxito: Desaparición automática tras 6 segundos --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-alert portal-alert-success portal-auto-dismiss"
             role="status"
             data-auto-dismiss="6000"
             aria-live="polite">
            <div class="portal-alert-content">
                <span class="portal-alert-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <span class="portal-alert-text">{{ session('success') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar notificación"></button>
        </div>
    @endif

    {{-- Mensaje de Estatus General (cuando no se haya emitido success explícito) --}}
    @if (session('status') && !session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-alert portal-alert-success portal-auto-dismiss"
             role="status"
             data-auto-dismiss="6000"
             aria-live="polite">
            <div class="portal-alert-content">
                <span class="portal-alert-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <span class="portal-alert-text">{{ session('status') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar notificación"></button>
        </div>
    @endif

    {{-- Mensaje Informativo: Desaparición automática tras 5 segundos --}}
    @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show portal-alert portal-alert-info portal-auto-dismiss"
             role="status"
             data-auto-dismiss="5000"
             aria-live="polite">
            <div class="portal-alert-content">
                <span class="portal-alert-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <span class="portal-alert-text">{{ session('info') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar notificación"></button>
        </div>
    @endif

    {{-- Mensaje de Advertencia: Permanece visible hasta cierre manual --}}
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show portal-alert portal-alert-warning"
             role="alert"
             aria-live="assertive">
            <div class="portal-alert-content">
                <span class="portal-alert-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <span class="portal-alert-text">{{ session('warning') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar advertencia"></button>
        </div>
    @endif

    {{-- Mensaje de Error de Sesión: Permanece visible hasta cierre manual --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show portal-alert portal-alert-danger"
             role="alert"
             aria-live="assertive">
            <div class="portal-alert-content">
                <span class="portal-alert-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <span class="portal-alert-text">{{ session('error') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar error"></button>
        </div>
    @endif

    {{-- Errores de Validación: Permanecen visibles hasta resolución o cierre manual --}}
    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show portal-alert portal-alert-danger"
             role="alert"
             aria-live="assertive">
            <div class="portal-alert-content align-items-start">
                <span class="portal-alert-icon mt-1" aria-hidden="true">
                    <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <div class="portal-alert-text">
                    <strong>Por favor corrige los siguientes detalles:</strong>
                    <ul class="mb-0 mt-1 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar errores"></button>
        </div>
    @endif
</div>

