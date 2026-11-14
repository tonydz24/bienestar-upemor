@extends('layouts.dashboard')

@section('title', 'Gestión de Encuestas de Satisfacción - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-slate-800 mb-1">Encuestas de Satisfacción y Calidad</h1>
            <p class="text-muted small mb-0">Monitorea la percepción del estudiantado sobre los servicios de orientación y la plataforma.</p>
        </div>
        <div>
            <a href="{{ route('admin.encuestas.create') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nueva Encuesta
            </a>
        </div>
    </div>

    @include('components.portal-alerts')

    <!-- KPI Cards (Filtros Interactivos) -->
    <div class="row g-3 mb-4">
        {{-- Total Encuestas --}}
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('admin.encuestas.index') }}" class="text-decoration-none d-block h-100" title="Ver todas las encuestas">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 transition-all {{ !request()->filled('estado') ? 'border-start border-4 border-primary' : '' }}" style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Total Encuestas</div>
                            <div class="h3 fw-bold text-slate-800 mb-0">{{ $metricas['total'] }}</div>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary p-3">
                            <i class="bi bi-clipboard2-data fs-4"></i>
                        </div>
                    </div>
                    <div class="text-primary small mt-2 fw-semibold d-flex align-items-center gap-1">
                        <span>Ver catálogo completo</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- Publicadas Activas --}}
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('admin.encuestas.index', ['estado' => 'PUBLICADA']) }}" class="text-decoration-none d-block h-100" title="Filtrar encuestas publicadas">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 transition-all {{ request('estado') === 'PUBLICADA' ? 'border-start border-4 border-success' : '' }}" style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Publicadas Activas</div>
                            <div class="h3 fw-bold text-success mb-0">{{ $metricas['publicadas'] }}</div>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success p-3">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                    <div class="text-success small mt-2 fw-semibold d-flex align-items-center gap-1">
                        <span>Filtrar publicadas</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- Borradores --}}
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('admin.encuestas.index', ['estado' => 'BORRADOR']) }}" class="text-decoration-none d-block h-100" title="Filtrar borradores en preparación">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 transition-all {{ request('estado') === 'BORRADOR' ? 'border-start border-4 border-warning' : '' }}" style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Borradores</div>
                            <div class="h3 fw-bold text-warning mb-0">{{ $metricas['borradores'] }}</div>
                        </div>
                        <div class="rounded-circle bg-warning-subtle text-warning p-3">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </div>
                    </div>
                    <div class="text-warning small mt-2 fw-semibold d-flex align-items-center gap-1">
                        <span>Filtrar borradores</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- Respuestas Recibidas (Informativa / Conteo) --}}
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Respuestas Recibidas</div>
                        <div class="h3 fw-bold text-dark mb-0">{{ number_format($metricas['total_respuestas']) }}</div>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info p-3">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
                <div class="text-muted small mt-2">
                    Participaciones totales registradas
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.encuestas.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 ps-0" placeholder="Buscar por título u objetivo...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="PUBLICADA" @selected(request('estado') === 'PUBLICADA')>Publicadas</option>
                        <option value="BORRADOR" @selected(request('estado') === 'BORRADOR')>Borrador</option>
                        <option value="CERRADA" @selected(request('estado') === 'CERRADA')>Cerradas</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary rounded-pill w-100">Filtrar</button>
                    @if(request()->filled('q') || request()->filled('estado'))
                        <a href="{{ route('admin.encuestas.index') }}" class="btn btn-outline-secondary rounded-pill flex-shrink-0 d-inline-flex align-items-center gap-1 px-3" title="Limpiar filtros">
                            <i class="bi bi-x-lg" aria-hidden="true"></i> <span>Limpiar</span>
                        </a>
                    @endif
                </div>
            </form>

            @if(request()->filled('q') || request()->filled('estado'))
                <div class="d-flex align-items-center gap-2 mt-2 pt-2 border-top small text-muted">
                    <span>Filtros activos:</span>
                    @if(request()->filled('q'))
                        <span class="badge bg-light text-dark border">Búsqueda: "{{ request('q') }}"</span>
                    @endif
                    @if(request()->filled('estado'))
                        <span class="badge bg-light text-dark border">Estado: {{ request('estado') }}</span>
                    @endif
                    <a href="{{ route('admin.encuestas.index') }}" class="text-danger small text-decoration-none ms-1">
                        <i class="bi bi-x-circle me-1"></i>Restablecer
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Tabla de Encuestas -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            @if($encuestas->isEmpty())
                @if($metricas['total'] === 0)
                    {{-- Catálogo Vacío --}}
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-clipboard2-plus fs-1 opacity-50 d-block mb-3 text-primary"></i>
                        <h6 class="fw-bold text-slate-700">Todavía no has creado ninguna encuesta</h6>
                        <p class="small mb-3 max-w-500 mx-auto">
                            Comienza creando el primer instrumento de retroalimentación para recolectar opiniones estudiantiles sobre el servicio de bienestar.
                        </p>
                        <a href="{{ route('admin.encuestas.create') }}" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-plus-lg me-1"></i> Crear Primera Encuesta
                        </a>
                    </div>
                @else
                    {{-- Búsqueda o Filtro sin Resultados --}}
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-search fs-1 opacity-50 d-block mb-3 text-secondary"></i>
                        <h6 class="fw-bold text-slate-700">No encontramos encuestas con los filtros seleccionados</h6>
                        <p class="small mb-3">Intenta cambiar los términos de búsqueda o limpiar los filtros activos.</p>
                        <a href="{{ route('admin.encuestas.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar Filtros
                        </a>
                    </div>
                @endif
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Encuesta</th>
                                <th>Tipo / Modalidad</th>
                                <th>Preguntas</th>
                                <th>Respuestas</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($encuestas as $enc)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-slate-800">{{ $enc->titulo }}</div>
                                        <div class="text-muted small">
                                            {{ Str::limit($enc->descripcion, 90) }} &bull; Creada: {{ $enc->created_at->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($enc->tipo === 'POST_CITA')
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 py-1 small">
                                                <i class="bi bi-calendar2-check me-1"></i> Post-Cita
                                            </span>
                                        @else
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 small">
                                                <i class="bi bi-globe me-1"></i> General
                                            </span>
                                        @endif
                                        <div class="text-muted small mt-1">
                                            {{ $enc->es_anonima ? 'Anónima' : 'Identificada' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-slate-700">{{ $enc->preguntas_count }}</span> preguntas
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-bold fs-6">
                                            {{ $enc->respuestas_count }}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 {{ $enc->estado_badge }} text-white border-0 shadow-none" data-bs-toggle="modal" data-bs-target="#modalCambiarEstado{{ $enc->encuesta_id }}" title="Clic para cambiar el estado de la encuesta">
                                            <i class="bi bi-sliders me-1 small"></i>{{ $enc->estado }}
                                        </button>

                                        <!-- Modal Cambiar Estado Encuesta -->
                                        <div class="modal fade" id="modalCambiarEstado{{ $enc->encuesta_id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <form action="{{ route('admin.encuestas.toggle-estado', $enc) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold text-dark">Modificar estado de encuesta</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <p class="text-muted small mb-3">
                                                                Selecciona el nuevo estado institucional para la encuesta <strong>«{{ $enc->titulo }}»</strong>:
                                                            </p>

                                                            <div class="d-flex flex-column gap-2">
                                                                <label class="p-3 rounded-3 border d-flex align-items-start gap-3 cursor-pointer @if($enc->estado === 'PUBLICADA') bg-success-subtle border-success @endif">
                                                                    <input type="radio" name="estado" value="PUBLICADA" class="form-check-input mt-1" @checked($enc->estado === 'PUBLICADA')>
                                                                    <div>
                                                                        <div class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> PUBLICADA</div>
                                                                        <div class="small text-muted">Visible para los estudiantes y disponible para recibir respuestas dentro de las fechas de vigencia configuradas.</div>
                                                                    </div>
                                                                </label>

                                                                <label class="p-3 rounded-3 border d-flex align-items-start gap-3 cursor-pointer @if($enc->estado === 'BORRADOR') bg-warning-subtle border-warning @endif">
                                                                    <input type="radio" name="estado" value="BORRADOR" class="form-check-input mt-1" @checked($enc->estado === 'BORRADOR')>
                                                                    <div>
                                                                        <div class="fw-bold text-warning-emphasis"><i class="bi bi-pencil-square me-1"></i> BORRADOR</div>
                                                                        <div class="small text-muted">Oculta para los alumnos. Permite reestructurar preguntas si todavía no cuenta con respuestas registradas.</div>
                                                                    </div>
                                                                </label>

                                                                <label class="p-3 rounded-3 border d-flex align-items-start gap-3 cursor-pointer @if($enc->estado === 'CERRADA') bg-danger-subtle border-danger @endif">
                                                                    <input type="radio" name="estado" value="CERRADA" class="form-check-input mt-1" @checked($enc->estado === 'CERRADA')>
                                                                    <div>
                                                                        <div class="fw-bold text-danger"><i class="bi bi-lock-fill me-1"></i> CERRADA</div>
                                                                        <div class="small text-muted">Concluye la recepción de respuestas. Los estudiantes ya no podrán contestarla y los datos quedan congelados para consulta estadística.</div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">Actualizar estado</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        {{-- Botón Resultados --}}
                                        <a href="{{ route('admin.encuestas.resultados', $enc) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1" title="Ver análisis estadístico de resultados">
                                            <i class="bi bi-bar-chart-fill me-1"></i> Resultados
                                        </a>

                                        {{-- Botón Editar --}}
                                        <a href="{{ route('admin.encuestas.edit', $enc) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1 me-1 d-inline-flex align-items-center gap-1 text-dark" title="Editar configuración de encuesta" aria-label="Editar encuesta {{ $enc->titulo }}">
                                            <i class="bi bi-pencil-square text-primary" aria-hidden="true"></i> <span>Editar</span>
                                        </a>

                                        {{-- Botón Eliminar --}}
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1" title="Eliminar encuesta" aria-label="Eliminar encuesta {{ $enc->titulo }}" data-bs-toggle="modal" data-bs-target="#modalEliminarEncuesta{{ $enc->encuesta_id }}">
                                            <i class="bi bi-trash3" aria-hidden="true"></i> <span>Eliminar</span>
                                        </button>

                                        <!-- Modal Confirmar Eliminación Encuesta -->
                                        <!-- Modal Eliminar Encuesta -->
                                        <div class="modal fade" id="modalEliminarEncuesta{{ $enc->encuesta_id }}" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
                                            <div class="modal-dialog modal-dialog-centered" style="white-space: normal !important; max-width: 480px;">
                                                <div class="modal-content rounded-4 border-0 shadow" style="white-space: normal !important; word-break: break-word;">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                                            <span>Eliminar encuesta</span>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <form action="{{ route('admin.encuestas.destroy', $enc) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <div class="modal-body text-start" style="white-space: normal !important; word-break: break-word;">
                                                            @if ($enc->respuestas_count > 0)
                                                                <div class="alert alert-danger border-0 small mb-3">
                                                                    <strong>Advertencia crítica de pérdida de datos:</strong>
                                                                    Esta encuesta contiene <strong>{{ $enc->respuestas_count }} respuesta(s) registrada(s)</strong>.
                                                                    Si continúas, se eliminarán definitivamente la encuesta y todas sus respuestas y estadísticas asociadas. Esta acción no se puede deshacer.
                                                                </div>
                                                                <p class="text-muted small mb-2">
                                                                    Para autorizar la eliminación definitiva de <strong>«{{ $enc->titulo }}»</strong> y sus datos dependientes, escribe la palabra <strong class="text-danger">ELIMINAR</strong> en el siguiente campo:
                                                                </p>
                                                                <div class="mb-2">
                                                                    <input type="text" name="confirmacion_palabra" class="form-control form-control-sm text-uppercase fw-bold" placeholder="Escribe ELIMINAR" required autocomplete="off">
                                                                </div>
                                                            @else
                                                                <p class="text-muted small mb-0">
                                                                    ¿Confirmas que deseas eliminar permanentemente la encuesta <strong>«{{ $enc->titulo }}»</strong>? Esta encuesta no registra respuestas aún, por lo que será removida de inmediato.
                                                                </p>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold">Confirmar Eliminación Definitiva</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $encuestas->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
