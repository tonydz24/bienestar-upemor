@extends('layouts.dashboard')

@section('title', 'Ficha Técnica: ' . $test->nombre . ' - Administración Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.tests.index') }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Volver al listado
            </a>
            <div>
                <h1 class="h3 fw-bold text-slate-800 mb-0">{{ $test->nombre }}</h1>
                <span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle rounded-pill me-2">
                    {{ $test->codigo }}
                </span>
                <span class="text-muted small">{{ $test->categoria }}</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.tests.resultados', $test->instrumento_id) }}" class="btn btn-info text-white rounded-pill px-3 fw-semibold shadow-xs">
                <i class="bi bi-bar-chart-line me-1"></i> Resultados y Estadísticas
            </a>
            <a href="{{ route('admin.tests.edit', ['test' => $test->instrumento_id, 'version_id' => $versionActual?->version_id]) }}" class="btn btn-primary rounded-pill px-3 fw-semibold">
                <i class="bi bi-pencil me-1"></i> Editar Metadatos / Reactivos
            </a>
            <form action="{{ route('admin.tests.nueva-version', $test->instrumento_id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary rounded-pill px-3 fw-semibold" title="Clonar versión actual en una nueva versión borrador editable">
                    <i class="bi bi-layers-half me-1"></i> Nueva Versión
                </button>
            </form>
            <form action="{{ route('admin.tests.duplicar', $test->instrumento_id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-dark rounded-pill px-3 fw-semibold" title="Duplicar test sin copiar respuestas">
                    <i class="bi bi-copy me-1"></i> Duplicar
                </button>
            </form>
            <form action="{{ route('admin.tests.toggle-estado', $test->instrumento_id) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn {{ $test->activo ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill px-3 fw-semibold">
                    <i class="bi {{ $test->activo ? 'bi-pause' : 'bi-play' }} me-1"></i>
                    {{ $test->activo ? 'Pausar Test' : 'Activar Test' }}
                </button>
            </form>
            @if(!($test->retirado ?? false))
                <form action="{{ route('admin.tests.retirar', $test->instrumento_id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Retirar este instrumento de circulación conservando su historial?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold" title="Retirar de circulación conservando respuestas">
                        <i class="bi bi-archive me-1"></i> Retirar
                    </button>
                </form>
            @endif
            @if(($totalIntentosInstrumento ?? 0) === 0)
                <form action="{{ route('admin.tests.destroy', $test->instrumento_id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar definitivamente este instrumento sin respuestas registradas?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3 fw-semibold" title="Eliminar instrumento (0 respuestas)">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Banner Metodológico y Deontológico -->
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

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div class="fw-semibold">{{ session('status') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Columna Izquierda: Información General e Historial de Versiones -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3">Información General</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <span class="text-muted small d-block">Código institucional</span>
                            <strong>{{ $test->codigo }}</strong>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small d-block">Siglas</span>
                            <strong>{{ $test->siglas ?: 'N/A' }}</strong>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small d-block">Categoría</span>
                            <span class="badge bg-light text-dark border">{{ $test->categoria }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small d-block">Autor(es) / Referencia</span>
                            <span>{{ $test->autor ?: 'Comité Institucional' }} ({{ $test->anio_publicacion ?: '2026' }})</span>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small d-block">Periodo de Reaplicación</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                @if((int)$test->dias_espera_reaplicacion === 0)
                                    Repetición inmediata permitida (0 días)
                                @elseif((int)$test->dias_espera_reaplicacion === 1)
                                    Cada 24 horas (1 día)
                                @else
                                    Cada {{ $test->dias_espera_reaplicacion }} días
                                @endif
                            </span>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small d-block">Estado Operativo</span>
                            @if($test->retirado ?? false)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="bi bi-archive me-1"></i> Retirado de circulación
                                </span>
                            @elseif($test->activo)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i> Publicado y Activo
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="bi bi-pause-circle me-1"></i> Inactivo / En Pausa
                                </span>
                            @endif
                        </li>
                        <li>
                            <span class="text-muted small d-block">Total de Evaluaciones Finalizadas (Versión Mostrada)</span>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <span class="h4 fw-bold text-primary mb-0">{{ $totalRespuestas }}</span>
                                <a href="{{ route('admin.tests.resultados', ['test' => $test->instrumento_id, 'version_id' => $versionActual?->version_id]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    Analizar resultados &rarr;
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-2">Descripción Institucional</h5>
                    <p class="text-muted small mb-0 lh-base">
                        {{ $test->descripcion }}
                    </p>
                </div>
            </div>

            <!-- Historial de Versiones (A2, A4) -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-layers me-1 text-primary"></i>Historial de Versiones</h6>
                    <span class="badge bg-light text-secondary border rounded-pill">{{ $test->versiones->count() }}</span>
                </div>
                <div class="card-body p-3 pt-1">
                    <div class="list-group list-group-flush">
                        @foreach($test->versiones as $ver)
                            <div class="list-group-item px-2 py-2.5 d-flex align-items-center justify-content-between {{ $versionActual && $versionActual->version_id === $ver->version_id ? 'bg-light rounded-3' : '' }}">
                                <div>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <strong class="small text-dark">v{{ $ver->version }}</strong>
                                        <span class="badge rounded-pill {{ $ver->estado === 'PUBLICADA' ? 'bg-success-subtle text-success' : ($ver->estado === 'BORRADOR' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary') }}" style="font-size: 0.65rem;">
                                            {{ $ver->estado }}
                                        </span>
                                        @if($ver->es_vigente)
                                            <span class="badge rounded-pill bg-primary text-white" style="font-size: 0.62rem;">Vigente</span>
                                        @endif
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        {{ $ver->preguntas_count ?? $ver->preguntas->count() }} reactivos &bull; {{ $ver->intentos_count ?? 0 }} intentos
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ route('admin.tests.show', ['test' => $test->instrumento_id, 'version_id' => $ver->version_id]) }}"
                                       class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                        Ver
                                    </a>
                                    @if($ver->estado === 'BORRADOR')
                                        <form action="{{ route('admin.tests.publicar-version', $test->instrumento_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="version_id" value="{{ $ver->version_id }}">
                                            <button type="submit" class="btn btn-xs btn-success rounded-pill px-2 py-0.5" style="font-size: 0.72rem;" title="Publicar esta versión">
                                                Publicar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Versión Seleccionada, Escalas, Baremos y Reactivos -->
        <div class="col-lg-8">
            @if($versionActual)
                <!-- Versión y Reglas -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">Versión Vigente: v{{ $versionActual->version }}</h5>
                                <p class="text-muted small mb-0">{{ $versionActual->nombre_version ?: 'Versión estandarizada para estudiantes' }}</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $versionActual->estado === 'PUBLICADA' ? 'bg-success-subtle text-success border-success-subtle' : ($versionActual->estado === 'BORRADOR' ? 'bg-warning-subtle text-warning-emphasis border-warning-subtle' : 'bg-secondary-subtle text-secondary border-secondary-subtle') }} border rounded-pill px-3 py-1">
                                    Estado: {{ $versionActual->estado }}
                                </span>
                                @if($versionActual->estado === 'BORRADOR')
                                    <form action="{{ route('admin.tests.publicar-version', $test->instrumento_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="version_id" value="{{ $versionActual->version_id }}">
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">
                                            <i class="bi bi-rocket-takeoff me-1"></i> Publicar Versión v{{ $versionActual->version }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <span class="text-muted small d-block fw-semibold mb-1">Instrucciones al Estudiante:</span>
                            <p class="small text-dark mb-0 italic"><em>«{{ $versionActual->instrucciones }}»</em></p>
                        </div>
                        <div class="row g-3 small">
                            <div class="col-sm-4">
                                <span class="text-muted d-block">Marco temporal:</span>
                                <strong>{{ $versionActual->marco_temporal ?: 'Últimas 2 semanas' }}</strong>
                            </div>
                            <div class="col-sm-4">
                                <span class="text-muted d-block">Duración estimada:</span>
                                <strong>{{ $versionActual->duracion_estimada_min }} minutos</strong>
                            </div>
                            <div class="col-sm-4">
                                <span class="text-muted d-block">Puntos otorgados:</span>
                                <strong>+{{ $versionActual->puntos_recompensa }} pts</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Escala Global de Opciones -->
                @if($versionActual->escalas->isNotEmpty())
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                            <h5 class="fw-bold text-dark mb-0">Escala de Opciones de Respuesta ({{ $versionActual->escalas->count() }})</h5>
                        </div>
                        <div class="card-body p-4 pt-2">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($versionActual->escalas as $esc)
                                    <div class="border rounded-3 px-3 py-2 bg-light d-inline-flex align-items-center gap-2">
                                        <span class="badge bg-primary rounded-pill">{{ $esc->valor }} pts</span>
                                        <span class="small fw-semibold text-dark">{{ $esc->etiqueta }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Baremos de Interpretación -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                        <h5 class="fw-bold text-dark mb-0">Baremos de Interpretación Clínica / Preventiva</h5>
                        <p class="text-muted small mb-0">Puntos de corte normativos configurados para este instrumento.</p>
                    </div>
                    <div class="card-body p-4 pt-2">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Rango</th>
                                        <th>Nivel / Severidad</th>
                                        <th>Mensaje de Retroalimentación</th>
                                        <th class="text-center">Sugerencia Orientación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($versionActual->baremos as $baremo)
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ $baremo->puntuacion_min }} - {{ $baremo->puntuacion_max }} pts
                                                </span>
                                            </td>
                                            <td>
                                                <span class="d-inline-flex align-items-center gap-1.5 fw-semibold" style="color: {{ $baremo->color_hex }};">
                                                    <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: {{ $baremo->color_hex }};"></span>
                                                    {{ $baremo->nivel }}
                                                </span>
                                            </td>
                                            <td class="small text-muted" style="max-width: 320px;">
                                                {{ $baremo->mensaje_retroalimentacion }}
                                            </td>
                                            <td class="text-center">
                                                @if($baremo->sugiere_orientacion)
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                        Sí (Preventivo)
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-muted py-3 text-center small">No hay baremos registrados para esta versión.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Lista de Reactivos / Preguntas -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h5 class="fw-bold text-dark mb-0">Reactivos del Instrumento ({{ $versionActual->preguntas->count() }})</h5>
                            @if(($totalIntentosVersion ?? 0) === 0)
                                <a href="{{ route('admin.tests.edit', ['test' => $test->instrumento_id, 'version_id' => $versionActual->version_id]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-pencil-square me-1"></i> Editar reactivos
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-4 pt-2">
                        <div class="list-group list-group-flush">
                            @forelse($versionActual->preguntas as $p)
                                @php
                                    $tipoP = strtoupper((string) ($p->tipo_pregunta ?: 'ESCALA'));
                                    $obligP = (bool) ($p->es_obligatoria ?? true);
                                    $opcionesP = is_array($p->opciones_json) ? $p->opciones_json : [];
                                @endphp
                                <div class="list-group-item px-0 py-3 border-bottom">
                                    <div class="d-flex align-items-start gap-3">
                                        <span class="badge bg-light text-dark border rounded-circle p-2 fw-bold" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                            {{ $p->numero_reactivo ?? $p->orden }}
                                        </span>
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-wrap align-items-center gap-1.5 mb-1">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.68rem;">
                                                    {{ $tipoP }}
                                                </span>
                                                <span class="badge {{ $obligP ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary' }} border rounded-pill" style="font-size: 0.68rem;">
                                                    {{ $obligP ? 'Obligatoria' : 'Opcional' }}
                                                </span>
                                                @if($p->es_inverso)
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill" style="font-size: 0.68rem;">
                                                        Puntaje Inverso
                                                    </span>
                                                @endif
                                                @if($p->es_critico)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" style="font-size: 0.68rem;">
                                                        Reactivo Crítico
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="small text-dark mb-1 fw-semibold">{{ $p->enunciado }}</p>
                                            @if($p->descripcion_ayuda)
                                                <small class="text-muted d-block mb-1">{{ $p->descripcion_ayuda }}</small>
                                            @endif
                                            @if(!empty($opcionesP))
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    @foreach($opcionesP as $opItem)
                                                        <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                            {{ $opItem['etiqueta'] ?? '' }} ({{ $opItem['valor'] ?? 0 }} pts)
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted py-3 text-center small">No hay reactivos registrados en esta versión.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary border-0 rounded-4 p-4 text-center">
                    <i class="bi bi-exclamation-circle fs-2 d-block mb-2 text-muted"></i>
                    No se encontró una versión activa para este instrumento.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
