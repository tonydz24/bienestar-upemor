@extends('layouts.dashboard')

@section('title', 'Evaluaciones disponibles - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado del Módulo -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="portal-greeting-title">Evaluaciones disponibles</h1>
                <p class="portal-greeting-sub mb-0">Cuestionarios e instrumentos psicométricos estandarizados para el autoconocimiento y la prevención.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('alumno.evaluaciones.historial') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Mi historial</span>
                    @if (!empty($totalHistorial) && $totalHistorial > 0)
                        <span class="badge bg-primary text-white rounded-pill ms-1" style="font-size: 0.72rem;">{{ $totalHistorial }}</span>
                    @endif
                </a>
                <a href="{{ route('alumno.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    &larr; Volver al inicio
                </a>
            </div>
        </div>
    </div>

    <!-- Banner Metodológico y Deontológico Institucional -->
    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
        <div class="fs-4 text-primary flex-shrink-0">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <strong class="d-block text-dark small mb-1">Aviso Institucional de Confidencialidad y Ética Psicométrica</strong>
            <p class="small text-muted mb-0">
                Aviso Institucional: Los instrumentos disponibles son herramientas psicométricas estandarizadas de tamizaje preliminar. No constituyen un diagnóstico definitivo ni reemplazan la valoración personalizada de un especialista en salud mental.
            </p>
        </div>
    </div>

    @if (! ($tablasDisponibles ?? true))
        <!-- Mensaje de estado: Tablas en preparación técnica -->
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4" role="status">
            <div class="d-flex gap-3 align-items-start">
                <span class="fs-4 text-primary" aria-hidden="true">ℹ</span>
                <div>
                    <h2 class="h6 fw-bold mb-1">Módulo en etapa de preparación metodológica</h2>
                    <p class="small mb-0">
                        Los instrumentos clínicos mostrados a continuación han sido preseleccionados para la comunidad estudiantil. La aplicación interactiva de reactivos y el almacenamiento seguro de respuestas se habilitarán próximamente bajo estrictos protocolos de consentimiento y confidencialidad.
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            @foreach ($evaluacionesFallback as $eval)
                <div class="col-md-6 col-xl-6">
                    <div class="evaluacion-card">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                            <span class="evaluacion-category-pill">{{ $eval['categoria'] }}</span>
                            <span class="badge bg-light text-muted border px-2 py-1 rounded-pill small">Próximamente</span>
                        </div>

                        <h2 class="h5 fw-bold text-dark mb-1">{{ $eval['codigo'] }} &bull; {{ $eval['nombre'] }}</h2>
                        <p class="text-muted small mb-3">{{ $eval['descripcion'] }}</p>

                        <div class="d-flex align-items-center gap-3 text-muted small mb-4">
                            <span class="evaluacion-meta-badge">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>{{ $eval['duracion'] }}</span>
                            </span>
                            <span>&bull;</span>
                            <span class="evaluacion-meta-badge">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                </svg>
                                <span>{{ $eval['preguntas'] }} reactivos</span>
                            </span>
                        </div>

                        <div class="mt-auto">
                            <button type="button" class="btn btn-portal-primary w-100 disabled" aria-disabled="true" title="El cuestionario estará disponible en una próxima actualización">
                                Comenzar evaluación (Próximamente)
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Marco Ético y Metodológico Institucional (Incremento 13) -->
        <div class="alert alert-light border border-primary-subtle shadow-sm rounded-4 p-4 mb-4" role="status">
            <div class="d-flex gap-3 align-items-start">
                <div class="portal-guidance-icon mt-1 text-primary" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="h6 fw-bold mb-1 text-dark">Marco Ético y Metodológico de Evaluaciones Institucionales</h2>
                    <p class="text-muted small mb-0">
                        En apego al <strong>Código Ético del Psicólogo</strong> y a la normativa de la <strong>UPEMOR</strong>, los instrumentos clínicos estandarizados (<em>GAD-7, PHQ-9, PSS-10 y WHO-5</em>) y orientadores cuentan con reactivos psicométricos y baremos calibrados con fines de tamizaje y orientación preventiva universitaria. Toda participación requiere consentimiento informado voluntario. Nuevos módulos en preparación metodológica institucional se integrarán de forma continua (Próximamente).
                    </p>
                </div>
            </div>
        </div>

        @if ($instrumentos->isEmpty())
            <div class="dashboard-section-card text-center py-5">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#cbd5e1" stroke-width="1.5" class="mb-3 mx-auto" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <h3 class="h6 fw-bold text-dark mb-1">No hay evaluaciones registradas en este momento</h3>
                <p class="text-muted small mb-0">Vuelve más tarde para consultar nuevas herramientas de bienestar estudiantil.</p>
            </div>
        @else
            <div class="row g-4">
                @foreach ($instrumentos as $inst)
                    @php
                        $catClass = match(strtolower($inst->categoria)) {
                            'ansiedad' => 'evaluacion-category-ansiedad',
                            'estado de ánimo', 'animo' => 'evaluacion-category-animo',
                            'estrés', 'estres' => 'evaluacion-category-estres',
                            default => 'evaluacion-category-bienestar',
                        };
                    @endphp

                    <div class="col-md-6 col-xl-6">
                        <div class="evaluacion-card">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <span class="evaluacion-category-pill {{ $catClass }}">{{ $inst->categoria }}</span>

                                @if ($inst->estado_disponibilidad === 'EN_PROGRESO')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small">
                                        En progreso
                                    </span>
                                @elseif ($inst->estado_disponibilidad === 'DISPONIBLE')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                                        Disponible
                                    </span>
                                @elseif ($inst->estado_disponibilidad === 'COMPLETADA_RECIENTE')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small" title="Completada recientemente">
                                        Disponible en {{ $inst->dias_restantes }} {{ $inst->dias_restantes === 1 ? 'día' : 'días' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2 py-1 rounded-pill small" title="Estructura técnica y baremos listos; en proceso de revisión colegiada por el comité institucional">
                                        En preparación (Próximamente) &bull; Listo para revisión
                                    </span>
                                @endif
                            </div>

                            <h2 class="h5 fw-bold text-dark mb-1">
                                {{ $inst->codigo }} &bull; {{ $inst->nombre }}
                            </h2>

                            <p class="text-muted small mb-3">
                                {{ $inst->descripcion }}
                            </p>

                            <div class="d-flex flex-wrap align-items-center gap-3 text-muted small mb-4">
                                <span class="evaluacion-meta-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span>{{ $inst->duracion_texto }}</span>
                                </span>
                                <span>&bull;</span>
                                <span class="evaluacion-meta-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    </svg>
                                    <span>{{ $inst->preguntas_count }} reactivos</span>
                                </span>
                                @if ($inst->version_mostrada)
                                    <span>&bull;</span>
                                    <span class="text-muted" style="font-size: 0.78rem;">v{{ $inst->version_mostrada->version }}</span>
                                @endif
                            </div>

                            <div class="mt-auto">
                                @if ($inst->estado_disponibilidad === 'EN_PROGRESO')
                                    <a href="{{ route('alumno.evaluaciones.cuestionario', $inst->intento_activo_id) }}" class="btn btn-warning w-100 fw-semibold">
                                        Continuar cuestionario &rarr;
                                    </a>
                                @elseif ($inst->estado_disponibilidad === 'DISPONIBLE')
                                    <a href="{{ route('alumno.evaluaciones.instrucciones', $inst->instrumento_id) }}" class="btn btn-portal-primary w-100">
                                        Ver instrucciones y comenzar &rarr;
                                    </a>
                                @elseif ($inst->estado_disponibilidad === 'COMPLETADA_RECIENTE')
                                    <button type="button" class="btn btn-outline-secondary w-100 disabled" aria-disabled="true" title="Completaste esta prueba recientemente. Podrás realizarla de nuevo en {{ $inst->dias_restantes }} {{ $inst->dias_restantes === 1 ? 'día' : 'días' }}.">
                                        Completada (Disponible en {{ $inst->dias_restantes }} {{ $inst->dias_restantes === 1 ? 'día' : 'días' }})
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-secondary w-100 disabled" aria-disabled="true" title="Estructura técnica configurada. En espera de aprobación formal colegiada del comité de psicología UPEMOR.">
                                        <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Próximamente &bull; Listo técnicamente para revisión
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection

