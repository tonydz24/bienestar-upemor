@extends('layouts.dashboard')

@section('title', 'Resultados y Estadísticas — ' . $test->nombre)

@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.tests.index') }}" class="text-decoration-none">Tests Psicométricos</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.tests.show', $test->instrumento_id) }}" class="text-decoration-none">{{ $test->codigo }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Resultados y Estadísticas</li>
        </ol>
    </nav>

    {{-- Cabecera --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge bg-dark font-monospace">{{ $test->codigo }}</span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">{{ $test->categoria }}</span>
                @if($versionSeleccionada)
                    <span class="badge bg-indigo-subtle text-dark border">
                        <i class="bi bi-layers me-1"></i>Versión analizada: v{{ $versionSeleccionada->version }} ({{ $versionSeleccionada->estado }})
                    </span>
                @endif
                @if($test->retirado ?? false)
                    <span class="badge bg-danger">Retirado</span>
                @elseif($test->activo)
                    <span class="badge bg-success">Activo</span>
                @else
                    <span class="badge bg-secondary">Pausado / Borrador</span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">{{ $test->nombre }}</h1>
            <p class="text-muted mb-0 small">
                Análisis estadístico por versión, distribución de niveles interpretativos, frecuencias por reactivo y consulta nominal de intentos individuales.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.tests.show', $test->instrumento_id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Ficha del Test
            </a>
            <a href="{{ route('admin.tests.edit', ['test' => $test->instrumento_id, 'version_id' => $versionSeleccionada?->version_id]) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i> Configurar / Versiones
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    {{-- Filtros por Versión, Fechas, Programa, Grupo y Búsqueda de Texto Libre --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.tests.resultados', $test->instrumento_id) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="version_id" class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-git me-1"></i>Versión del Instrumento
                    </label>
                    <select name="version_id" id="version_id" class="form-select form-select-sm">
                        @foreach($test->versiones as $ver)
                            <option value="{{ $ver->version_id }}" @selected($versionSeleccionada && $versionSeleccionada->version_id === $ver->version_id)>
                                v{{ $ver->version }} — {{ $ver->estado }} ({{ $ver->intentos_count ?? 0 }} intentos)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="programa_id" class="form-label small fw-bold text-secondary mb-1">Programa Educativo</label>
                    <select name="programa_id" id="programa_id" class="form-select form-select-sm">
                        <option value="">Todos los programas</option>
                        @foreach($programas as $prog)
                            <option value="{{ $prog->programa_id }}" @selected((string) $programaId === (string) $prog->programa_id)>
                                {{ $prog->clave ?? $prog->nombre }} — {{ Str::limit($prog->nombre, 24) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="cuatrimestre" class="form-label small fw-bold text-secondary mb-1">Cuatrimestre</label>
                    <select name="cuatrimestre" id="cuatrimestre" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @for($c = 1; $c <= 10; $c++)
                            <option value="{{ $c }}" @selected((string) ($cuatrimestre ?? '') === (string) $c)>{{ $c }}.º</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="grupo_id" class="form-label small fw-bold text-secondary mb-1">Grupo Académico</label>
                    <select name="grupo_id" id="grupo_id" class="form-select form-select-sm">
                        <option value="">Todos los grupos</option>
                        @foreach($grupos as $grp)
                            <option value="{{ $grp->grupo_id }}" @selected((string) $grupoId === (string) $grp->grupo_id)>
                                {{ $grp->cuatrimestre }}.º {{ $grp->nombre }} — {{ $grp->programa->clave ?? $grp->programa->nombre ?? 'UPEMOR' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fecha_inicio" class="form-label small fw-bold text-secondary mb-1">Desde</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ $fechaInicio }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label for="fecha_fin" class="form-label small fw-bold text-secondary mb-1">Hasta</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" value="{{ $fechaFin }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrar
                    </button>
                </div>

                @if($fechaInicio || $fechaFin || $programaId || ($cuatrimestre ?? false) || $grupoId || $qAbierta)
                    <div class="col-12 mt-2 d-flex justify-content-between align-items-center border-top pt-2">
                        <span class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>Mostrando estadísticas segmentadas exclusivamente para la versión <strong>v{{ $versionSeleccionada?->version }}</strong> sin mezclar versiones incompatibles.
                        </span>
                        <a href="{{ route('admin.tests.resultados', ['test' => $test->instrumento_id, 'version_id' => $versionSeleccionada?->version_id]) }}" class="btn btn-sm btn-link text-decoration-none p-0">
                            Limpiar filtros adicionales
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Alerta de Muestra Pequeña (< 3 alumnos únicos) --}}
    @if($muestraPequena)
        <div class="alert alert-warning d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-shield-exclamation fs-4 me-3 flex-shrink-0"></i>
            <div>
                <strong>Aviso de Muestra Reducida y Confidencialidad ({{ $alumnosUnicos }} {{ Str::plural('estudiante', $alumnosUnicos) }}):</strong>
                Esta selección registra menos de 3 alumnos únicos participantes. Interpreta los porcentajes agregados con cautela estadística y resguarda la confidencialidad de la información sensible conforme a la normativa institucional.
            </div>
        </div>
    @endif

    {{-- Tarjetas KPI Resumen (A6) --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-person-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Alumnos Únicos</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($alumnosUnicos) }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Participantes distintos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-clipboard2-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Intentos Completados</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalIntentosFinalizados) }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            @if($intentosEnProgresoCount > 0)
                                + {{ $intentosEnProgresoCount }} en progreso
                            @else
                                Aplicaciones finalizadas
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-list-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Respuestas Registradas</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalRespuestasRegistradas) }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Reactivos contestados</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-speedometer2 fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Puntaje Promedio</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($promedioPuntaje, 1) }} <span class="fs-6 fw-normal text-muted">pts</span></div>
                        <div class="text-muted" style="font-size: 0.75rem;">Rango: {{ $minPuntaje }} – {{ $maxPuntaje }} pts</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-secondary bg-opacity-10 text-secondary me-3">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Última Respuesta</div>
                        <div class="fw-bold text-dark">
                            @if($ultimaRespuestaFecha)
                                {{ \Carbon\Carbon::parse($ultimaRespuestaFecha)->format('d/m/Y H:i') }}
                            @else
                                Sin registros
                            @endif
                        </div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            Reaplicación:
                            @if((int) $test->dias_espera_reaplicacion === 0)
                                Inmediata (0 días)
                            @elseif((int) $test->dias_espera_reaplicacion === 1)
                                24 horas (1 día)
                            @else
                                {{ $test->dias_espera_reaplicacion }} días
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Distribución por Categoría Interpretativa / Baremo --}}
    @if(!empty($distribucionNiveles))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-pie-chart-fill text-primary me-2"></i>Distribución por Nivel Interpretativo (Baremos v{{ $versionSeleccionada?->version }})
                    </h6>
                    <span class="small text-muted">Clasificación de los {{ $totalIntentosFinalizados }} intentos completados según el rango de puntuación obtenido</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($distribucionNiveles as $nivelItem)
                        <div class="col-md-6 col-lg-3">
                            <div class="p-3 rounded-3 border h-100" style="border-left: 4px solid {{ $nivelItem['color_hex'] }} !important;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="fw-bold text-dark d-block">{{ $nivelItem['nivel'] }}</span>
                                        <span class="text-muted small">{{ $nivelItem['rango'] }}</span>
                                    </div>
                                    <span class="badge rounded-pill" style="background-color: {{ $nivelItem['color_hex'] }}; color: #fff;">
                                        {{ $nivelItem['porcentaje'] }}%
                                    </span>
                                </div>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ min(100, $nivelItem['porcentaje']) }}%; background-color: {{ $nivelItem['color_hex'] }};"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span class="fw-semibold text-dark">{{ $nivelItem['conteo'] }} {{ Str::plural('intento', $nivelItem['conteo']) }}</span>
                                    @if($nivelItem['sugiere_orientacion'])
                                        <span class="text-danger small fw-semibold"><i class="bi bi-heart-pulse me-1"></i>Sugiere canalización</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Navegación por Pestañas: Estadísticas por Reactivo vs Intentos Individuales --}}
    <ul class="nav nav-tabs mb-4" id="testResultadosTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="tab-estadisticas-btn" data-bs-toggle="tab" data-bs-target="#tab-estadisticas" type="button" role="tab" aria-controls="tab-estadisticas" aria-selected="true">
                <i class="bi bi-bar-chart-line me-2"></i>Estadísticas por Reactivo
                <span class="badge bg-secondary ms-1">{{ count($estadisticasPreguntas) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-intentos-btn" data-bs-toggle="tab" data-bs-target="#tab-intentos" type="button" role="tab" aria-controls="tab-intentos" aria-selected="false">
                <i class="bi bi-people me-2"></i>Intentos y Respuestas Individuales
                <span class="badge bg-primary ms-1">{{ $intentosPaginados->total() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="testResultadosTabContent">
        {{-- PESTAÑA 1: Estadísticas por Reactivo --}}
        <div class="tab-pane fade show active" id="tab-estadisticas" role="tabpanel" aria-labelledby="tab-estadisticas-btn">
            @if(empty($estadisticasPreguntas))
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-clipboard-x display-5 text-muted mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">Esta versión no tiene reactivos configurados</h5>
                        <p class="text-muted mb-0">Configura preguntas en el editor del instrumento para visualizar sus métricas.</p>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach($estadisticasPreguntas as $item)
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                    <div>
                                        <span class="badge bg-dark me-2">Reactivo #{{ $item['numero_reactivo'] }}</span>
                                        <span class="badge bg-light text-dark border me-1">{{ $item['tipo_pregunta'] }}</span>
                                        @if($item['es_obligatoria'])
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">Obligatoria</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border me-1">Opcional</span>
                                        @endif
                                        @if($item['es_inverso'])
                                            <span class="badge bg-info-subtle text-info-emphasis border me-1">Puntaje Inverso</span>
                                        @endif
                                        @if($item['es_critico'])
                                            <span class="badge bg-danger-subtle text-danger border me-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Alerta Crítica</span>
                                        @endif
                                        <div class="fw-bold text-dark mt-2">{{ $item['enunciado'] }}</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 text-muted small flex-shrink-0">
                                        <span><strong class="text-success">{{ $item['total_validas'] }}</strong> válidas</span>
                                        <span><strong class="text-secondary">{{ $item['total_omitidas'] }}</strong> omitidas</span>
                                        @if($item['tipo_pregunta'] !== 'TEXTO_LIBRE')
                                            <span>Promedio: <strong class="text-primary">{{ number_format($item['promedio_puntos'], 2) }} pts</strong></span>
                                        @else
                                            <span class="badge bg-light text-muted border">0 pts (Cualitativa)</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="card-body p-4">
                                    @if($item['tipo_pregunta'] === 'TEXTO_LIBRE')
                                        <div class="alert alert-light border small text-muted mb-3 py-2 px-3">
                                            <i class="bi bi-info-circle text-primary me-1"></i>
                                            <strong>Pregunta abierta complementaria:</strong> Las respuestas abiertas son descriptivas y no se interpretan clínicamente ni suman puntaje automático al baremo del instrumento.
                                        </div>

                                        <form method="GET" action="{{ route('admin.tests.resultados', $test->instrumento_id) }}" class="row g-2 mb-3">
                                            <input type="hidden" name="version_id" value="{{ $versionSeleccionada?->version_id }}">
                                            <input type="hidden" name="programa_id" value="{{ $programaId }}">
                                            <input type="hidden" name="grupo_id" value="{{ $grupoId }}">
                                            <input type="hidden" name="fecha_inicio" value="{{ $fechaInicio }}">
                                            <input type="hidden" name="fecha_fin" value="{{ $fechaFin }}">
                                            <div class="col-sm-8 col-md-5">
                                                <input type="text" name="q_abierta" value="{{ $qAbierta }}" class="form-control form-control-sm" placeholder="Buscar palabra clave en respuestas abiertas...">
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-search me-1"></i>Buscar texto
                                                </button>
                                            </div>
                                        </form>

                                        @if(empty($item['respuestas_abiertas']))
                                            <p class="text-muted fst-italic mb-0 small">No hay respuestas abiertas registradas para este reactivo con los filtros actuales.</p>
                                        @else
                                            <div class="row g-2" style="max-height: 340px; overflow-y: auto;">
                                                @foreach($item['respuestas_abiertas'] as $respTexto)
                                                    <div class="col-12">
                                                        <div class="p-3 bg-light rounded border-start border-3 border-primary small d-flex justify-content-between align-items-start gap-3">
                                                            <div>
                                                                <div class="text-dark mb-1">
                                                                    <i class="bi bi-chat-left-quote text-primary me-2"></i>{{ $respTexto['texto'] }}
                                                                </div>
                                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                                    <strong>{{ $respTexto['alumno_nombre'] ?: 'Estudiante' }}</strong> ({{ $respTexto['matricula'] }})
                                                                    — {{ $respTexto['fecha'] ? \Carbon\Carbon::parse($respTexto['fecha'])->format('d/m/Y H:i') : '' }}
                                                                </div>
                                                            </div>
                                                            <a href="{{ route('admin.tests.intentos.show', [$test->instrumento_id, $respTexto['intento_id']]) }}" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                                                Ver intento
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        @if($item['suma_puede_superar_100'])
                                            <div class="alert alert-light border small text-muted mb-3 py-2 px-3">
                                                <i class="bi bi-check2-square text-primary me-1"></i>
                                                <strong>Selección múltiple:</strong> Cada porcentaje se calcula sobre los {{ $item['total_validas'] }} intentos que respondieron este reactivo. La suma de los porcentajes puede superar el 100% porque el estudiante puede marcar más de una opción.
                                            </div>
                                        @endif

                                        @if(empty($item['opciones']))
                                            <p class="text-muted fst-italic mb-0 small">No hay opciones configuradas para este reactivo.</p>
                                        @else
                                            <div class="row g-3">
                                                @foreach($item['opciones'] as $opStat)
                                                    <div class="col-md-6">
                                                        <div class="d-flex justify-content-between small mb-1">
                                                            <span class="fw-medium text-dark">
                                                                {{ $opStat['etiqueta'] }}
                                                                <span class="text-muted">({{ $opStat['valor'] }} pts)</span>
                                                            </span>
                                                            <span class="text-muted fw-semibold">
                                                                {{ $opStat['frecuencia'] }} ({{ $opStat['porcentaje'] }}%)
                                                            </span>
                                                        </div>
                                                        <div class="progress" style="height: 10px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, $opStat['porcentaje']) }}%;" aria-valuenow="{{ $opStat['porcentaje'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- PESTAÑA 2: Intentos y Respuestas Individuales (A7) --}}
        <div class="tab-pane fade" id="tab-intentos" role="tabpanel" aria-labelledby="tab-intentos-btn">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Historial de Aplicaciones e Intentos Registrados</h6>
                        <span class="small text-muted">Acceso administrativo exclusivo al detalle de respuestas por intento y estudiante</span>
                    </div>
                    <span class="badge bg-primary">{{ $intentosPaginados->total() }} {{ Str::plural('registro', $intentosPaginados->total()) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-3">Intento</th>
                                <th>Estudiante</th>
                                <th>Programa / Grupo</th>
                                <th>Versión</th>
                                <th>Fecha de Aplicación</th>
                                <th>Estado</th>
                                <th>Puntaje</th>
                                <th>Nivel / Interpretación</th>
                                <th class="text-end pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($intentosPaginados as $intento)
                                @php
                                    $numIntento = $mapaNumeroIntento[$intento->intento_id] ?? 1;
                                    $est = $intento->usuario?->estudiante;
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-dark font-monospace">Intento #{{ $numIntento }}</span>
                                        <div class="text-muted" style="font-size: 0.7rem;">ID: {{ $intento->intento_id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $intento->usuario?->nombre }} {{ $intento->usuario?->apellido_paterno }} {{ $intento->usuario?->apellido_materno }}
                                        </div>
                                        <div class="text-muted small font-monospace">
                                            Matrícula: {{ $est?->matricula ?? 'S/M' }}
                                        </div>
                                    </td>
                                    <td class="small">
                                        <div class="fw-medium text-dark">{{ $est?->programa?->clave ?? $est?->programa?->nombre ?? 'N/D' }}</div>
                                        @if($est?->cuatrimestre || $est?->grupo)
                                            <div class="text-muted">{{ $est?->cuatrimestre }}° {{ $est?->grupo }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">v{{ $versionSeleccionada?->version ?? '1.0' }}</span>
                                    </td>
                                    <td class="small">
                                        @if($intento->fecha_finalizacion)
                                            <div class="text-dark">{{ \Carbon\Carbon::parse($intento->fecha_finalizacion)->format('d/m/Y H:i') }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Finalizado</div>
                                        @else
                                            <div class="text-dark">{{ $intento->created_at?->format('d/m/Y H:i') }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Iniciado</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($intento->estado === 'FINALIZADO')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">FINALIZADO</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $intento->estado }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($intento->estado === 'FINALIZADO')
                                            <span class="fw-bold text-dark">{{ $intento->puntuacion_total }} pts</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($intento->nivel_resultado)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $intento->nivel_resultado }}</span>
                                        @else
                                            <span class="text-muted small">Sin clasificar</span>
                                        @endif
                                        @if($intento->requiere_atencion)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" title="Sugiere canalización">
                                                <i class="bi bi-exclamation-circle-fill"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('admin.tests.intentos.show', [$test->instrumento_id, $intento->intento_id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>Ver respuestas individuales
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No se encontraron intentos registrados para esta versión con los criterios seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($intentosPaginados->hasPages())
                    <div class="card-footer bg-white border-top py-3">
                        {{ $intentosPaginados->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
