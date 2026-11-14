@extends('layouts.dashboard')

@section('title', 'Gestión de Meditaciones - Administración Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-slate-800 mb-1">Catálogo de Relajación y Meditación</h1>
            <p class="text-muted small mb-0">Administra los ejercicios guiados de bienestar, técnicas respiratorias y pausas de estudio.</p>
        </div>
        <div>
            <a href="{{ route('admin.meditaciones.create') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Ejercicio Guiado
            </a>
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
            <a href="{{ route('admin.meditaciones.index') }}" class="text-decoration-none text-reset d-block h-100">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 {{ !request()->filled('estado') ? 'border-primary border-2' : '' }} transition-all" style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Total Ejercicios</div>
                            <div class="h3 fw-bold text-slate-800 mb-0">{{ $metricas['total'] }}</div>
                            <span class="badge bg-light text-secondary border rounded-pill mt-2 small">Ver catálogo completo</span>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary p-3">
                            <i class="bi bi-flower1 fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('admin.meditaciones.index', ['estado' => 'publicado']) }}" class="text-decoration-none text-reset d-block h-100">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 {{ request('estado') === 'publicado' ? 'border-success border-2' : '' }} transition-all" style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Publicadas</div>
                            <div class="h3 fw-bold text-success mb-0">{{ $metricas['publicadas'] }}</div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mt-2 small">Filtrar activas</span>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success p-3">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('admin.meditaciones.index', ['estado' => 'borrador']) }}" class="text-decoration-none text-reset d-block h-100">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 {{ request('estado') === 'borrador' ? 'border-warning border-2' : '' }} transition-all" style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Borradores</div>
                            <div class="h3 fw-bold text-warning mb-0">{{ $metricas['borradores'] }}</div>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill mt-2 small">Filtrar borradores</span>
                        </div>
                        <div class="rounded-circle bg-warning-subtle text-warning p-3">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white" style="cursor: default;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Sesiones Practicadas</div>
                        <div class="h3 fw-bold text-indigo mb-0">{{ $metricas['total_sesiones'] }}</div>
                        <span class="badge bg-indigo-subtle text-indigo border rounded-pill mt-2 small">Indicador confidencial</span>
                    </div>
                    <div class="rounded-circle bg-indigo-subtle text-indigo p-3">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.meditaciones.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 ps-0" placeholder="Buscar por título o descripción...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="categoria" class="form-select">
                        <option value="">Todas las categorías</option>
                        @foreach($categorias as $key => $cat)
                            <option value="{{ $key }}" @selected(request('categoria') === $key)>{{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="publicado" @selected(request('estado') === 'publicado')>Publicados</option>
                        <option value="borrador" @selected(request('estado') === 'borrador')>Borradores</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-primary rounded-pill">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Meditaciones -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            @if($meditaciones->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-1 opacity-50 d-block mb-3"></i>
                    <h6 class="fw-bold text-slate-700">No se encontraron ejercicios en este criterio</h6>
                    <p class="small mb-3">Comprueba los filtros aplicados o añade un nuevo ejercicio guiado.</p>
                    <a href="{{ route('admin.meditaciones.create') }}" class="btn btn-primary rounded-pill px-4">
                        Crear Primer Ejercicio
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Ejercicio</th>
                                <th>Categoría</th>
                                <th>Nivel / Duración</th>
                                <th>Impacto (Vistas / Sesiones)</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($meditaciones as $med)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi {{ $med->categoria_icon }} fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-slate-800">{{ $med->titulo }}</div>
                                                <div class="text-muted small">
                                                    {{ count($med->instrucciones ?? []) }} pasos guiados &bull; Creado por: {{ $med->creador?->nombre ?? 'Sistema' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $med->categoria_badge }} rounded-pill px-2 py-1 small">
                                            {{ $med->categoria_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-slate-700">{{ $med->nivel_label }}</div>
                                        <div class="text-muted small"><i class="bi bi-clock me-1"></i> {{ $med->duracion_minutos }} min</div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="text-muted"><i class="bi bi-eye me-1"></i> {{ $med->vistas_count }} visualizaciones</span>
                                        </div>
                                        <div class="small fw-semibold text-primary">
                                            <i class="bi bi-check-circle-fill me-1 text-success"></i> {{ $med->sesiones_count }} sesiones completadas
                                        </div>
                                    </td>
                                    <td>
                                        @if($med->es_publicado)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold">
                                                <i class="bi bi-check-circle-fill me-1"></i> Publicado
                                            </span>
                                        @elseif($med->sesiones_count > 0)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">
                                                <i class="bi bi-archive-fill me-1"></i> Retirado
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle rounded-pill px-3 py-1 fw-semibold">
                                                <i class="bi bi-pencil-square me-1"></i> Borrador
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        {{-- Acción 1: Editar --}}
                                        <a href="{{ route('admin.meditaciones.edit', $med) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-1 d-inline-flex align-items-center justify-content-center" style="min-height: 36px;" aria-label="Editar ejercicio {{ $med->titulo }}">
                                            <i class="bi bi-pencil me-1"></i> Editar
                                        </a>

                                        {{-- Acción 2: Retirar o Volver a publicar / Publicar --}}
                                        @if($med->es_publicado)
                                            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1 shadow-none me-1" style="min-height: 36px;" title="Retirar ejercicio del catálogo institucional" data-bs-toggle="modal" data-bs-target="#modalRetirarMeditacion{{ $med->meditacion_id }}">
                                                <i class="bi bi-archive" aria-hidden="true"></i> <span>Retirar</span>
                                            </button>

                                            <!-- Modal Retirar Meditación -->
                                            <div class="modal fade" id="modalRetirarMeditacion{{ $med->meditacion_id }}" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
                                                <div class="modal-dialog modal-dialog-centered" style="white-space: normal !important; max-width: 500px;">
                                                    <div class="modal-content rounded-4 border-0 shadow" style="white-space: normal !important; word-break: break-word;">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold text-warning-emphasis d-flex align-items-center gap-2">
                                                                <i class="bi bi-archive"></i>
                                                                <span>Retirar ejercicio del catálogo</span>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                        </div>
                                                        <div class="modal-body text-start" style="white-space: normal !important; word-break: break-word;">
                                                            @if($med->sesiones_count > 0)
                                                                <p class="text-muted small mb-2">
                                                                    El ejercicio <strong>{{ $med->titulo }}</strong> cuenta con <strong>{{ $med->sesiones_count }} sesiones completadas</strong> por estudiantes.
                                                                </p>
                                                                <p class="text-muted small mb-0">
                                                                    Para proteger el historial y las estadísticas institucionales de los alumnos, este ejercicio será retirado del catálogo público en lugar de borrarse físicamente. Ningún alumno nuevo podrá iniciarlo, pero sus registros previos se conservarán íntegros.
                                                                </p>
                                                            @else
                                                                <p class="text-muted small mb-0">
                                                                    ¿Deseas retirar el ejercicio <strong>{{ $med->titulo }}</strong> del catálogo público? Los alumnos dejarán de verlo en la plataforma hasta que decidas volver a publicarlo.
                                                                </p>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                            <form action="{{ route('admin.meditaciones.retirar', $med) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="btn btn-warning btn-sm rounded-pill px-4 fw-semibold text-dark">Confirmar Retiro</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1 shadow-none me-1" style="min-height: 36px;" title="{{ $med->sesiones_count > 0 ? 'Volver a publicar ejercicio en el catálogo' : 'Publicar ejercicio en el catálogo' }}" data-bs-toggle="modal" data-bs-target="#modalPublicarMeditacion{{ $med->meditacion_id }}">
                                                <i class="bi {{ $med->sesiones_count > 0 ? 'bi-arrow-counterclockwise' : 'bi-upload' }}" aria-hidden="true"></i>
                                                <span>{{ $med->sesiones_count > 0 ? 'Volver a publicar' : 'Publicar' }}</span>
                                            </button>

                                            <!-- Modal Publicar / Volver a publicar Meditación -->
                                            <div class="modal fade" id="modalPublicarMeditacion{{ $med->meditacion_id }}" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
                                                <div class="modal-dialog modal-dialog-centered" style="white-space: normal !important; max-width: 500px;">
                                                    <div class="modal-content rounded-4 border-0 shadow" style="white-space: normal !important; word-break: break-word;">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold text-success d-flex align-items-center gap-2">
                                                                <i class="bi bi-check-circle"></i>
                                                                <span>{{ $med->sesiones_count > 0 ? 'Volver a publicar ejercicio' : 'Publicar ejercicio en el catálogo' }}</span>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                        </div>
                                                        <div class="modal-body text-start" style="white-space: normal !important; word-break: break-word;">
                                                            <p class="text-muted small mb-2">
                                                                El ejercicio <strong>{{ $med->titulo }}</strong> pasará a estar visible inmediatamente en el catálogo de relajación para todos los estudiantes.
                                                            </p>
                                                            @if($med->sesiones_count > 0)
                                                                <p class="text-muted small mb-0">
                                                                    Se conservarán intactas sus <strong>{{ $med->sesiones_count }} sesiones históricas</strong> registradas previamente.
                                                                </p>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                            <form action="{{ route('admin.meditaciones.publicar', $med) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-semibold">
                                                                    <i class="bi bi-check-lg me-1"></i> Confirmar Publicación
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Acción 3: Eliminar --}}
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-none" style="min-height: 36px;" title="Eliminar ejercicio definitivamente" aria-label="Eliminar ejercicio {{ $med->titulo }}" data-bs-toggle="modal" data-bs-target="#modalEliminarMeditacion{{ $med->meditacion_id }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i> <span>Eliminar</span>
                                        </button>

                                        <!-- Modal Eliminar Meditación -->
                                        <div class="modal fade" id="modalEliminarMeditacion{{ $med->meditacion_id }}" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
                                            <div class="modal-dialog modal-dialog-centered" style="white-space: normal !primary; max-width: 500px;">
                                                <div class="modal-content rounded-4 border-0 shadow" style="white-space: normal !important; word-break: break-word;">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                                                            <i class="bi {{ $med->sesiones_count > 0 ? 'bi-exclamation-triangle-fill' : 'bi-trash-fill' }}"></i>
                                                            <span>{{ $med->sesiones_count > 0 ? 'Eliminación definitiva de ejercicio' : 'Eliminar ejercicio guiado' }}</span>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    @if($med->sesiones_count > 0)
                                                        <form action="{{ route('admin.meditaciones.destroy', $med) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <div class="modal-body text-start" style="white-space: normal !important; word-break: break-word;">
                                                                <div class="alert alert-danger border-0 small mb-3">
                                                                    <strong>Advertencia de eliminación definitiva:</strong>
                                                                    El ejercicio <strong>«{{ $med->titulo }}»</strong> tiene <strong>{{ $med->sesiones_count }} sesión(es) de práctica registradas</strong> por estudiantes.
                                                                    Si continúas, se eliminarán permanentemente el ejercicio y todas sus sesiones vinculadas. Esta acción es estrictamente irreversible.
                                                                </div>
                                                                <p class="text-muted small mb-2">
                                                                    Para confirmar la eliminación definitiva física, escribe la palabra <strong class="text-danger">ELIMINAR</strong> en el siguiente campo:
                                                                </p>
                                                                <div class="mb-2">
                                                                    <input type="text" name="confirmacion_palabra" class="form-control form-control-sm text-uppercase fw-bold" placeholder="Escribe ELIMINAR" required autocomplete="off">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0">
                                                                <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold">Confirmar Eliminación Definitiva</button>
                                                            </div>
                                                        </form>
                                                    @else
                                                        <div class="modal-body text-start" style="white-space: normal !important; word-break: break-word;">
                                                            <p class="text-muted small mb-0">
                                                                ¿Confirmas eliminar definitivamente el ejercicio <strong>{{ $med->titulo }}</strong>? Esta acción no se puede deshacer (no cuenta con sesiones registradas).
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                            <form action="{{ route('admin.meditaciones.destroy', $med) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold">Eliminar definitivamente</button>
                                                            </form>
                                                        </div>
                                                    @endif
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
                    {{ $meditaciones->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

