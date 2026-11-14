@extends('layouts.dashboard')

@section('title', 'Gestión de Tests Psicométricos - Administración Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-slate-800 mb-1">Gestión de Tests e Instrumentos Psicométricos</h1>
            <p class="text-muted small mb-0">Supervisión, estado de publicación y parámetros baremados de las herramientas de tamizaje clínico y bienestar.</p>
        </div>
        <div>
            <a href="{{ route('admin.tests.create') }}" class="btn btn-primary rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2 shadow-xs">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span class="fw-semibold">Registrar Nuevo Test</span>
            </a>
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

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Total de Instrumentos</div>
                        <div class="h3 fw-bold text-slate-800 mb-0">{{ $metricas['total'] }}</div>
                        <span class="badge bg-light text-secondary border rounded-pill mt-2 small">Estandarizados</span>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary p-3">
                        <i class="bi bi-clipboard2-pulse fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Publicados / Activos</div>
                        <div class="h3 fw-bold text-success mb-0">{{ $metricas['activos'] }}</div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mt-2 small">Disponibles</span>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success p-3">
                        <i class="bi bi-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Inactivos / Pausa</div>
                        <div class="h3 fw-bold text-secondary mb-0">{{ $metricas['inactivos'] }}</div>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill mt-2 small">En revisión</span>
                    </div>
                    <div class="rounded-circle bg-secondary-subtle text-secondary p-3">
                        <i class="bi bi-pause-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Aplicaciones Totales</div>
                        <div class="h3 fw-bold text-primary mb-0">{{ $metricas['total_intentos'] }}</div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill mt-2 small">Respuestas alumno</span>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary p-3">
                        <i class="bi bi-person-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.tests.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control bg-light border-0" placeholder="Buscar por código, nombre o sigla...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="categoria" class="form-select bg-light border-0">
                        <option value="">Todas las categorías</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat }}" @selected(request('categoria') == $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select bg-light border-0">
                        <option value="">Todos los estados</option>
                        <option value="activo" @selected(request('estado') == 'activo')>Activos</option>
                        <option value="inactivo" @selected(request('estado') == 'inactivo')>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill w-100 fw-semibold">Filtrar</button>
                    @if(request()->anyFilled(['q', 'categoria', 'estado']))
                        <a href="{{ route('admin.tests.index') }}" class="btn btn-outline-secondary rounded-pill px-3" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Tests -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Código / Instrumento</th>
                        <th>Categoría</th>
                        <th class="text-center">Versión Vigente</th>
                        <th class="text-center">Reactivos</th>
                        <th class="text-center">Respuestas</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tests as $test)
                        @php
                            $vVigente = $test->versionVigente ?? $test->versiones->first();
                            $numReactivos = $vVigente ? $vVigente->preguntas()->count() : 0;
                            $numBaremos = $vVigente ? $vVigente->baremos()->count() : 0;
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 bg-purple-subtle text-purple-emphasis p-2 text-center" style="min-width: 44px;">
                                        <strong class="d-block small">{{ $test->siglas ?: substr($test->codigo, 0, 4) }}</strong>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.tests.show', $test->instrumento_id) }}" class="fw-bold text-dark text-decoration-none">
                                            {{ $test->nombre }}
                                        </a>
                                        <div class="text-muted small">
                                            <code>{{ $test->codigo }}</code> &bull; Reaplicación:
                                            @if((int)$test->dias_espera_reaplicacion === 0)
                                                <span class="text-primary fw-semibold">Inmediata (0 días)</span>
                                            @elseif((int)$test->dias_espera_reaplicacion === 1)
                                                <span>24 horas (1 día)</span>
                                            @else
                                                <span>{{ $test->dias_espera_reaplicacion }} días</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill">{{ $test->categoria }}</span>
                            </td>
                            <td class="text-center">
                                @if($vVigente)
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill">
                                        v{{ $vVigente->version }} ({{ $vVigente->estado }})
                                    </span>
                                    @if($test->versiones->count() > 1)
                                        <div class="text-muted" style="font-size: 0.68rem;">{{ $test->versiones->count() }} versiones</div>
                                    @endif
                                @else
                                    <span class="text-muted small">Sin versión</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-dark">{{ $numReactivos }}</span>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $numBaremos }} baremos</div>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.tests.resultados', $test->instrumento_id) }}" class="badge bg-light text-primary border rounded-pill fw-bold text-decoration-none px-2.5 py-1" title="Ver resultados y estadísticas">
                                    {{ $test->intentos_count }}
                                </a>
                            </td>
                            <td class="text-center">
                                @if($test->retirado ?? false)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-archive-fill me-1"></i> Retirado
                                    </span>
                                @elseif($test->activo)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> Publicado
                                    </span>
                                @elseif($vVigente && $vVigente->estado === 'BORRADOR')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-pencil-square me-1"></i> Borrador
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-pause-circle-fill me-1"></i> Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                                    <a href="{{ route('admin.tests.show', $test->instrumento_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" title="Ver ficha técnica">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.tests.resultados', $test->instrumento_id) }}" class="btn btn-sm btn-outline-info rounded-pill px-2 py-1" title="Resultados y estadísticas">
                                        <i class="bi bi-bar-chart-line"></i>
                                    </a>
                                    <a href="{{ route('admin.tests.edit', $test->instrumento_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" title="Editar instrumento y reactivos">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.tests.duplicar', $test->instrumento_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-2 py-1" title="Duplicar como borrador (sin respuestas)">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.tests.toggle-estado', $test->instrumento_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $test->activo ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill px-2 py-1"
                                                title="{{ $test->activo ? 'Pausar disponibilidad' : 'Habilitar para alumnos' }}">
                                            <i class="bi {{ $test->activo ? 'bi-pause' : 'bi-play' }}"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="Eliminar instrumento definitivamente" data-bs-toggle="modal" data-bs-target="#modalEliminarTest{{ $test->instrumento_id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                    <!-- Modal Eliminar Instrumento -->
                                    <div class="modal fade" id="modalEliminarTest{{ $test->instrumento_id }}" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
                                        <div class="modal-dialog modal-dialog-centered" style="white-space: normal !important; max-width: 480px;">
                                            <div class="modal-content rounded-4 border-0 shadow" style="white-space: normal !important; word-break: break-word;">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                                        <span>Eliminar instrumento</span>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <form action="{{ route('admin.tests.destroy', $test->instrumento_id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-body text-start" style="white-space: normal !important; word-break: break-word;">
                                                        @if(($test->intentos_count ?? 0) > 0)
                                                            <div class="alert alert-danger border-0 small mb-3">
                                                                <strong>Advertencia crítica de información sensible:</strong>
                                                                Este instrumento psicométrico registra <strong>{{ $test->intentos_count }} intento(s) histórico(s)</strong> completados por alumnos.
                                                                Si continúas, se eliminarán permanentemente el instrumento, sus preguntas, opciones y todas las respuestas asociadas. Esta acción es estrictamente irreversible.
                                                            </div>
                                                            <p class="text-muted small mb-2">
                                                                Para confirmar la eliminación definitiva física de <strong>«{{ $test->nombre }}»</strong>, escribe la palabra <strong class="text-danger">ELIMINAR</strong> en el siguiente campo:
                                                            </p>
                                                            <div class="mb-2">
                                                                <input type="text" name="confirmacion_palabra" class="form-control form-control-sm text-uppercase fw-bold" placeholder="Escribe ELIMINAR" required autocomplete="off">
                                                            </div>
                                                        @else
                                                            <p class="text-muted small mb-0">
                                                                ¿Confirmas eliminar definitivamente el instrumento <strong>«{{ $test->nombre }}»</strong> ({{ $test->codigo }})? Esta acción no se puede deshacer (no cuenta con intentos registrados).
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
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard2-x fs-1 d-block mb-2 text-muted"></i>
                                No se encontraron instrumentos que coincidan con la búsqueda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tests->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $tests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

