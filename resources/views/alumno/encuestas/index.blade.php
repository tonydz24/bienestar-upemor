@extends('layouts.dashboard')

@section('title', 'Encuestas de Satisfacción')

@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb y Cabecera --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('alumno.dashboard') }}" class="text-decoration-none">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Encuestas de Satisfacción</li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Encuestas de Satisfacción y Calidad</h1>
            <p class="text-muted mb-0">Tu opinión nos ayuda a mejorar continuamente los servicios de bienestar, acompañamiento y vida universitaria en UPEMOR.</p>
        </div>
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fs-6">
                <i class="bi bi-shield-lock-fill me-1"></i> Participación Segura y Confidencial
            </span>
        </div>
    </div>

    @include('components.portal-alerts')

    {{-- Banner Informativo Institucional --}}
    <div class="card border-0 bg-primary text-white shadow-sm mb-4" style="background: linear-gradient(135deg, #4A154B 0%, #611f69 100%);">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-9">
                    <h5 class="fw-bold mb-2"><i class="bi bi-chat-heart me-2"></i>La voz estudiantil transforma nuestra comunidad</h5>
                    <p class="mb-0 text-white-50">
                        Las encuestas de satisfacción son instrumentos confidenciales diseñados para evaluar la calidad del servicio de orientación, actividades emocionales e infraestructura de la universidad. Cada respuesta cuenta para tomar decisiones en beneficio de todos.
                    </p>
                </div>
                <div class="col-md-3 text-center d-none d-md-block">
                    <i class="bi bi-clipboard2-check display-3 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Listado de encuestas vigentes --}}
    @if($encuestas->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <div class="text-muted mb-3">
                    <i class="bi bi-patch-check display-4 text-success"></i>
                </div>
                <h4 class="fw-bold text-dark">¡Estás al día con tus opiniones!</h4>
                <p class="text-muted mb-0 max-w-500 mx-auto">
                    No hay encuestas pendientes por responder en este momento. Te notificaremos cuando haya nuevos instrumentos de retroalimentación disponibles.
                </p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($encuestas as $encuesta)
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100 d-flex flex-column {{ $encuesta->estado_alumno === 'RESPONDIDA' ? 'opacity-75' : '' }}">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <span class="badge {{ $encuesta->tipo === 'POST_CITA' ? 'bg-info text-dark' : 'bg-primary' }} text-wrap" style="white-space: normal; line-height: 1.3;">
                                    {{ $encuesta->tipo === 'POST_CITA' ? 'Servicio de Citas' : 'Institucional General' }}
                                </span>
                                @if($encuesta->estado_alumno === 'RESPONDIDA')
                                    <span class="badge bg-success-subtle text-success border border-success text-wrap text-start" style="white-space: normal; line-height: 1.3;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Respondida
                                    </span>
                                @elseif($encuesta->estado_alumno === 'RESPONDIDA_DISPONIBLE')
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info text-wrap text-start" style="white-space: normal; line-height: 1.3; max-width: 100%;">
                                        <i class="bi bi-arrow-repeat me-1"></i> Respondida · Disponible nuevamente
                                    </span>
                                @elseif($encuesta->estado_alumno === 'CERRADA')
                                    <span class="badge bg-danger-subtle text-danger border border-danger text-wrap text-start" style="white-space: normal; line-height: 1.3;">
                                        <i class="bi bi-x-circle-fill me-1"></i> Cerrada
                                    </span>
                                @elseif($encuesta->estado_alumno === 'EXPIRADA')
                                    <span class="badge bg-danger-subtle text-danger border border-danger text-wrap text-start" style="white-space: normal; line-height: 1.3;">
                                        <i class="bi bi-clock-history me-1"></i> Expirada
                                    </span>
                                @elseif($encuesta->estado_alumno === 'PROXIMAMENTE')
                                    <span class="badge bg-secondary-subtle text-primary border border-secondary text-wrap text-start" style="white-space: normal; line-height: 1.3;">
                                        <i class="bi bi-calendar-event me-1"></i> Próximamente
                                    </span>
                                @elseif($encuesta->estado_alumno === 'NO_DISPONIBLE')
                                    <span class="badge bg-secondary-subtle text-secondary border text-wrap text-start" style="white-space: normal; line-height: 1.3;">
                                        <i class="bi bi-calendar-x me-1"></i> No disponible
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success text-wrap text-start" style="white-space: normal; line-height: 1.3;">
                                        <i class="bi bi-pencil-square me-1"></i> Disponible
                                    </span>
                                @endif
                            </div>

                            <h5 class="fw-bold text-dark mb-2">{{ $encuesta->titulo }}</h5>
                            <p class="text-muted small flex-grow-1 mb-3">
                                {{ $encuesta->descripcion ?: 'Ayúdanos completando este breve cuestionario para conocer tu experiencia en la plataforma.' }}
                            </p>

                            <div class="border-top pt-3 mt-auto">
                                <div class="d-flex flex-wrap justify-content-between align-items-center text-muted small mb-3 gap-2">
                                    <span><i class="bi bi-question-square me-1"></i>{{ $encuesta->preguntas_count }} preguntas</span>
                                    <span><i class="bi bi-stopwatch me-1"></i>~{{ max(2, ceil($encuesta->preguntas_count * 0.7)) }} min</span>
                                    @if($encuesta->es_anonima)
                                        <span class="text-success"><i class="bi bi-incognito me-1"></i>Anónima</span>
                                    @endif
                                </div>

                                @if($encuesta->estado_alumno === 'RESPONDIDA')
                                    <button class="btn btn-secondary w-100 disabled" disabled>
                                        <i class="bi bi-check2-all me-1"></i> Encuesta Completada
                                    </button>
                                @elseif($encuesta->estado_alumno === 'RESPONDIDA_DISPONIBLE')
                                    <a href="{{ route('alumno.encuestas.responder', $encuesta) }}" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Responder Nuevamente
                                    </a>
                                @elseif($encuesta->estado_alumno === 'CERRADA')
                                    <button class="btn btn-light border text-muted w-100 disabled" disabled>
                                        <i class="bi bi-lock me-1"></i> Encuesta Cerrada
                                    </button>
                                @elseif($encuesta->estado_alumno === 'EXPIRADA')
                                    <button class="btn btn-light border text-muted w-100 disabled" disabled>
                                        <i class="bi bi-clock-history me-1"></i> Periodo Concluido
                                    </button>
                                @elseif($encuesta->estado_alumno === 'PROXIMAMENTE')
                                    <button class="btn btn-light border text-muted w-100 disabled" disabled>
                                        <i class="bi bi-calendar-event me-1"></i> Inicia {{ $encuesta->disponible_desde?->format('d/m/Y') }}
                                    </button>
                                @elseif($encuesta->estado_alumno === 'NO_DISPONIBLE')
                                    <button class="btn btn-light border text-muted w-100 disabled" disabled>
                                        <i class="bi bi-calendar-x me-1"></i> Fuera de Vigencia
                                    </button>
                                @else
                                    <a href="{{ route('alumno.encuestas.responder', $encuesta) }}" class="btn btn-primary w-100">
                                        <i class="bi bi-pencil-square me-1"></i> Responder Encuesta
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

