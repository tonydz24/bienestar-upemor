@extends('layouts.dashboard')

@section('title', 'Instrucciones: ' . $instrumento->codigo . ' - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <!-- Navegación superior / migas de pan -->
    <div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('alumno.evaluaciones') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <span>Volver al catálogo</span>
            </a>
            <span class="text-muted small">&bull;</span>
            <span class="text-muted small fw-semibold">{{ $instrumento->categoria }}</span>
        </div>
        <div>
            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill small">
                Versión {{ $version->version }} (Vigente)
            </span>
        </div>
    </div>

    <!-- Tarjeta Principal de Instrucciones -->
    <div class="evaluacion-instruction-card mb-4">
        <!-- Encabezado con degradado lavanda sutil -->
        <div class="evaluacion-instruction-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <span class="evaluacion-category-pill mb-2 d-inline-block">{{ $instrumento->categoria }}</span>
                    <h1 class="h3 fw-bold text-dark mb-1">{{ $instrumento->codigo }} &bull; {{ $instrumento->nombre }}</h1>
                    @if ($instrumento->autor)
                        <p class="text-muted small mb-0">
                            <strong>Autor(es):</strong> {{ $instrumento->autor }}
                            @if ($instrumento->anio_publicacion) ({{ $instrumento->anio_publicacion }}) @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4">
            <!-- Fila de Metadatos Clave -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-md-3">
                    <div class="evaluacion-info-pill-box">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#7c3aed" stroke-width="2" class="flex-shrink-0">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <div>
                            <span class="d-block text-muted" style="font-size: 0.74rem;">DURACIÓN</span>
                            <strong class="text-dark small">{{ $version->duracion_estimada_min }} min aprox.</strong>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="evaluacion-info-pill-box">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#7c3aed" stroke-width="2" class="flex-shrink-0">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <div>
                            <span class="d-block text-muted" style="font-size: 0.74rem;">REACTIVOS</span>
                            <strong class="text-dark small">{{ $version->preguntas_count }} preguntas</strong>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="evaluacion-info-pill-box">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#7c3aed" stroke-width="2" class="flex-shrink-0">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <div>
                            <span class="d-block text-muted" style="font-size: 0.74rem;">MARCO TEMPORAL</span>
                            <strong class="text-dark small">{{ $version->marco_temporal ?: 'Últimas 2 semanas' }}</strong>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="evaluacion-info-pill-box">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#7c3aed" stroke-width="2" class="flex-shrink-0">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <div>
                            <span class="d-block text-muted" style="font-size: 0.74rem;">BIENESTAR</span>
                            <strong class="text-dark small">+{{ $version->puntos_recompensa }} puntos</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Propósito del instrumento -->
            <div class="mb-4">
                <h2 class="h6 fw-bold text-dark mb-2">Propósito del instrumento</h2>
                <p class="text-secondary small mb-0" style="line-height: 1.6;">
                    {{ $instrumento->descripcion ?: 'Este instrumento proporciona una medición estandarizada para apoyar tu proceso de autoconocimiento y detección preventiva de factores emocionales.' }}
                </p>
            </div>

            <!-- Instrucciones Oficiales de Llenado -->
            <div class="mb-4">
                <h2 class="h6 fw-bold text-dark mb-2">Instrucciones de respuesta</h2>
                <div class="p-3 rounded-3" style="background: #fbf9ff; border: 1px solid #ede9fe;">
                    <p class="fw-medium text-dark small mb-2">
                        <em>«{{ $version->instrucciones }}»</em>
                    </p>
                    <p class="text-muted small mb-0" style="font-size: 0.82rem;">
                        Lee con detenimiento cada afirmación y selecciona la opción que mejor refleje tu experiencia habitual durante el periodo indicado. No existen respuestas correctas o incorrectas.
                    </p>
                </div>
            </div>

            <!-- Escala de respuesta prevista -->
            @if ($version->escalas->isNotEmpty())
                <div class="mb-4">
                    <h2 class="h6 fw-bold text-dark mb-2">Opciones de respuesta disponibles</h2>
                    <div class="row g-2">
                        @foreach ($version->escalas as $opcion)
                            <div class="col-sm-6 col-md-3">
                                <div class="evaluacion-scale-preview-item">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">{{ $opcion->valor }}</span>
                                    <span class="fw-medium text-dark">{{ $opcion->etiqueta }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($intentoEnProgreso)
                <div class="alert alert-primary border-primary-subtle shadow-sm rounded-4 p-4 mb-4" role="status">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 rounded-circle bg-white text-primary shadow-sm" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="h6 fw-bold mb-1 text-primary">Tienes un intento en progreso</h3>
                                <p class="text-muted small mb-0">Iniciado el {{ $intentoEnProgreso->fecha_inicio->translatedFormat('d \d\e F \d\e Y, H:i') }}. Tus respuestas previas están guardadas.</p>
                            </div>
                        </div>
                        <a href="{{ route('alumno.evaluaciones.cuestionario', $intentoEnProgreso->intento_id) }}" class="btn btn-primary px-4">
                            Reanudar cuestionario &rarr;
                        </a>
                    </div>
                </div>
            @endif

            <!-- Aviso Informativo Provisional y Marco de Privacidad -->
            <div class="alert alert-light border border-primary-subtle shadow-sm rounded-4 p-4 mb-4" role="status">
                <div class="d-flex gap-3 align-items-start">
                    <div class="portal-guidance-icon mt-1" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="h6 fw-bold mb-1 text-dark">Aviso ético, orientador y confidencialidad (Provisional en validación)</h3>
                        <p class="text-muted small mb-2">
                            <strong>Propósito orientador y formativo:</strong> Este instrumento forma parte de un entorno de verificación funcional y bienestar integral. Los cuestionarios disponibles en esta fase no constituyen un diagnóstico clínico ni sustituyen una consulta psicológica profesional.
                        </p>
                        <p class="text-muted small mb-0">
                            <strong>Tratamiento de datos personales:</strong> Las respuestas se resguardan de forma confidencial asociadas a tu cuenta de estudiante para el seguimiento de tu propio proceso de bienestar. Los lineamientos definitivos de custodia y protección de datos están en proceso de ratificación por las áreas correspondientes de UPEMOR.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Consentimiento informado y formulario de inicio -->
            <form method="POST" action="{{ route('alumno.evaluaciones.iniciar', $instrumento->instrumento_id) }}" id="formIniciarEvaluacion" class="border-top pt-4">
                @csrf
                <input type="hidden" name="consentimiento_version" value="v1.0-institucional">

                <!-- Marco de Consentimiento Informado Institucional (FN.3) -->
                <div class="alert alert-primary-subtle border border-primary-subtle rounded-3 p-3 mb-3 small text-dark">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-shield-lock-fill text-primary fs-5 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong class="d-block mb-1 text-primary-emphasis">Consentimiento Informado Institucional</strong>
                            <p class="mb-0 text-muted lh-base">
                                Este instrumento es una herramienta de tamizaje preventivo y formativo de la UPEMOR. Sus resultados no constituyen un diagnóstico médico o psicológico definitivo y están protegidos por el Código Ético del Psicólogo y la normativa de privacidad institucional.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="consentimiento" value="1" id="checkConsentimiento" required>
                    <label class="form-check-label text-dark small fw-semibold" for="checkConsentimiento">
                        He leído y acepto participar voluntariamente.
                    </label>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <a href="{{ route('alumno.evaluaciones') }}" class="btn btn-outline-secondary">
                        Cancelar y volver
                    </a>

                    <button type="submit" class="btn btn-portal-primary px-4" id="btnComenzarEvaluacion" disabled>
                        <span id="btnComenzarTexto">Comenzar evaluación</span>
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkConsentimiento = document.getElementById('checkConsentimiento');
    const btnComenzar = document.getElementById('btnComenzarEvaluacion');
    const btnComenzarTexto = document.getElementById('btnComenzarTexto');
    const formIniciar = document.getElementById('formIniciarEvaluacion');

    checkConsentimiento?.addEventListener('change', function () {
        if (btnComenzar) {
            btnComenzar.disabled = !this.checked;
        }
    });

    // Protección ante doble clic o envíos concurrentes
    formIniciar?.addEventListener('submit', function () {
        if (btnComenzar) {
            btnComenzar.disabled = true;
            if (btnComenzarTexto) {
                btnComenzarTexto.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Iniciando...';
            }
        }
    });
});
</script>
@endpush

