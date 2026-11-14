@extends('layouts.dashboard')

@section('title', 'Asignaciones Docentes y Cátedras - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="bi bi-mortarboard me-2 text-primary" aria-hidden="true"></i>Asignaciones Docentes y Solicitudes de Cátedra
            </h1>
            <p class="text-muted mb-0">Gestión, validación y supervisión institucional de la oferta académica por periodo.</p>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('admin.periodos.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>Periodos Académicos
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5" aria-hidden="true"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5" aria-hidden="true"></i>
            <div>{{ session('warning') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle-fill me-2" aria-hidden="true"></i>
            <strong>Se encontraron errores en la solicitud:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- TARJETAS DE INDICADORES -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Solicitudes Pendientes</span>
                            <h3 class="fw-bold mb-0 text-warning">{{ number_format($totales['pendientes']) }}</h3>
                        </div>
                        <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                            <i class="bi bi-hourglass-split fs-4" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Asignaciones Aprobadas</span>
                            <h3 class="fw-bold mb-0 text-success">{{ number_format($totales['aprobadas']) }}</h3>
                        </div>
                        <div class="bg-success-subtle text-success p-3 rounded-circle">
                            <i class="bi bi-check-circle fs-4" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Solicitudes Rechazadas</span>
                            <h3 class="fw-bold mb-0 text-danger">{{ number_format($totales['rechazadas']) }}</h3>
                        </div>
                        <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                            <i class="bi bi-x-circle fs-4" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.docente-asignaciones.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="periodo_id" class="form-label fw-semibold small text-muted">Periodo Académico</label>
                    <select name="periodo_id" id="periodo_id" class="form-select">
                        @foreach($periodos as $per)
                            <option value="{{ $per->periodo_id }}" {{ (string)$per->periodo_id === (string)$periodoSeleccionadoId ? 'selected' : '' }}>
                                {{ $per->clave }} — {{ $per->nombre }} {{ $per->activo ? '(Activo)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="estado" class="form-label fw-semibold small text-muted">Estado de la Solicitud</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="PENDIENTE" {{ $estadoFiltro === 'PENDIENTE' ? 'selected' : '' }}>Pendientes de Revisión</option>
                        <option value="APROBADA" {{ $estadoFiltro === 'APROBADA' ? 'selected' : '' }}>Aprobadas / Activas</option>
                        <option value="RECHAZADA" {{ $estadoFiltro === 'RECHAZADA' ? 'selected' : '' }}>Rechazadas</option>
                        <option value="TODOS" {{ $estadoFiltro === 'TODOS' ? 'selected' : '' }}>Todas las Asignaciones</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar
                    </button>
                    <a href="{{ route('admin.docente-asignaciones.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE ASIGNACIONES -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Registros de Cátedra ({{ $asignaciones->total() }})</span>
            <span class="badge bg-light text-dark border">Página {{ $asignaciones->currentPage() }} de {{ $asignaciones->lastPage() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width: 22%;">Docente</th>
                        <th scope="col" style="width: 25%;">Asignatura y Carrera</th>
                        <th scope="col" style="width: 15%;">Grupo Asignado</th>
                        <th scope="col" style="width: 12%;">Estado</th>
                        <th scope="col" style="width: 12%;">Fecha Solicitud</th>
                        <th scope="col" style="width: 14%; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asignaciones as $asig)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $asig->docenteUsuario?->nombre }} {{ $asig->docenteUsuario?->apellido_paterno }} {{ $asig->docenteUsuario?->apellido_materno }}</div>
                                <div class="small text-muted">{{ $asig->docenteUsuario?->email }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-primary">{{ $asig->asignatura?->nombre ?? 'Sin Asignatura' }}</div>
                                <div class="small text-muted">
                                    {{ $asig->asignatura?->programa?->clave ?? 'UPEMOR' }} • {{ $asig->asignatura?->cuatrimestre }}° Cuatrimestre
                                </div>
                            </td>
                            <td>
                                @if(($asig->modalidad_alcance ?? 'ESPECIFICO') === 'TODOS')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1">
                                        <i class="bi bi-diagram-3-fill me-1"></i>Apertura General (TODOS)
                                    </span>
                                    <div class="small text-muted font-monospace mt-1">
                                        Subgrupo: {{ $asig->grupo?->nombre_completo ?? 'Todos los grupos' }}
                                    </div>
                                @else
                                    <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                        <i class="bi bi-people me-1 text-secondary" aria-hidden="true"></i>Específico (Sección {{ $asig->grupo?->nombre ?? 'A' }})
                                    </span>
                                    <div class="small text-muted font-monospace mt-1">
                                        {{ $asig->grupo?->nombre_completo ?? 'Grupo Individual' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $estadoActual = $asig->estado ?? ($asig->activo ? 'APROBADA' : 'PENDIENTE');
                                @endphp
                                @if($estadoActual === 'APROBADA')
                                    <span class="badge badge-estado-activo">
                                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Aprobada
                                    </span>
                                @elseif($estadoActual === 'PENDIENTE')
                                    <span class="badge badge-estado-pendiente">
                                        <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Pendiente
                                    </span>
                                @elseif($estadoActual === 'RECHAZADA')
                                    <span class="badge badge-estado-inactivo">
                                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Rechazada
                                    </span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $asig->solicitado_en ? \Carbon\Carbon::parse($asig->solicitado_en)->format('d/m/Y H:i') : ($asig->created_at ? $asig->created_at->format('d/m/Y H:i') : '-') }}
                            </td>
                            <td class="text-end">
                                @if(($asig->estado ?? '') === 'PENDIENTE' || (!$asig->activo && !isset($asig->estado)))
                                    <div class="d-inline-flex gap-1">
                                        <!-- Botón Modal Aprobación -->
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAprobar{{ $asig->asignacion_id }}" title="Aprobar solicitud de cátedra">
                                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Aprobar
                                        </button>

                                        <!-- Botón Modal Rechazo -->
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRechazar{{ $asig->asignacion_id }}" title="Rechazar solicitud">
                                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Rechazar
                                        </button>
                                    </div>

                                    <!-- Modal de Aprobación -->
                                    <div class="modal fade" id="modalAprobar{{ $asig->asignacion_id }}" tabindex="-1" aria-labelledby="modalAprobarLabel{{ $asig->asignacion_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form method="POST" action="{{ route('admin.docente-asignaciones.aprobar', $asig->asignacion_id) }}" class="modal-content text-start">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-success" id="modalAprobarLabel{{ $asig->asignacion_id }}">
                                                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Aprobar Asignación de Cátedra
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @if(($asig->modalidad_alcance ?? 'ESPECIFICO') === 'TODOS')
                                                        <div class="alert alert-info border-0 rounded-3 p-2.5 small mb-3">
                                                            <i class="bi bi-info-circle-fill me-1 text-primary"></i><strong>Solicitud de Alcance General (TODOS):</strong> Esta aprobación autorizará atómicamente la impartición de la cátedra para <strong>todos los grupos del cuatrimestre</strong> cubiertos por la solicitud.
                                                        </div>
                                                        <p class="mb-2">
                                                            ¿Confirmas la aprobación para que el docente <strong>{{ $asig->docenteUsuario?->nombre }} {{ $asig->docenteUsuario?->apellido_paterno }}</strong> imparta la asignatura <strong>{{ $asig->asignatura?->nombre }}</strong> con apertura general para toda la generación?
                                                        </p>
                                                    @else
                                                        <div class="alert alert-light border rounded-3 p-2.5 small mb-3">
                                                            <i class="bi bi-shield-check me-1 text-success"></i><strong>Solicitud por Grupo Específico:</strong> Esta acción aprueba <strong>única y exclusivamente la sección {{ $asig->grupo?->nombre_completo }}</strong>. Las solicitudes para otros grupos conservarán su estado independiente.
                                                        </div>
                                                        <p class="mb-2">
                                                            ¿Confirmas la aprobación para que el docente <strong>{{ $asig->docenteUsuario?->nombre }} {{ $asig->docenteUsuario?->apellido_paterno }}</strong> imparta la asignatura <strong>{{ $asig->asignatura?->nombre }}</strong> en el grupo <strong>{{ $asig->grupo?->nombre_completo }}</strong>?
                                                        </p>
                                                    @endif
                                                    <div class="mb-3">
                                                        <label for="motivo_aprob_{{ $asig->asignacion_id }}" class="form-label fw-semibold small">Observación o nota administrativa <span class="text-muted small">(opcional)</span></label>
                                                        <textarea name="motivo_resolucion" id="motivo_aprob_{{ $asig->asignacion_id }}" rows="2" class="form-control" placeholder="Ej. Aprobado conforme al perfil docente institucional."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-success rounded-pill">
                                                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Confirmar Aprobación
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Modal de Rechazo -->
                                    <div class="modal fade" id="modalRechazar{{ $asig->asignacion_id }}" tabindex="-1" aria-labelledby="modalRechazarLabel{{ $asig->asignacion_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form method="POST" action="{{ route('admin.docente-asignaciones.rechazar', $asig->asignacion_id) }}" class="modal-content text-start">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-danger" id="modalRechazarLabel{{ $asig->asignacion_id }}">
                                                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Rechazar Solicitud de Cátedra
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @if(($asig->modalidad_alcance ?? 'ESPECIFICO') === 'TODOS')
                                                        <div class="alert alert-warning border-0 rounded-3 p-2.5 small mb-3">
                                                            <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i><strong>Rechazo de Alcance General (TODOS):</strong> Se resolverá como rechazada la solicitud global y sus grupos vinculados.
                                                        </div>
                                                    @else
                                                        <div class="alert alert-light border rounded-3 p-2.5 small mb-3">
                                                            <i class="bi bi-info-circle me-1 text-secondary"></i><strong>Rechazo Específico:</strong> Se rechazará únicamente la sección {{ $asig->grupo?->nombre_completo }}, sin alterar las asignaciones de otros grupos.
                                                        </div>
                                                    @endif
                                                    <p class="small text-muted mb-3">
                                                        Indica el motivo técnico o administrativo del rechazo. Esta observación será visible para el docente titular.
                                                    </p>
                                                    <div class="mb-3">
                                                        <label for="motivo_{{ $asig->asignacion_id }}" class="form-label fw-semibold small">Motivo de la resolución <span class="text-danger">*</span></label>
                                                        <textarea name="motivo_resolucion" id="motivo_{{ $asig->asignacion_id }}" rows="3" class="form-control" required placeholder="Ej. El grupo ya cuenta con titular asignado o no cumple con el perfil curricular."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <span class="small text-muted fst-italic">Resuelta</span>
                                    @if($asig->motivo_resolucion)
                                        <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-1" data-bs-toggle="tooltip" title="{{ $asig->motivo_resolucion }}">
                                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-folder-x fs-2 d-block mb-2 text-secondary" aria-hidden="true"></i>
                                No se encontraron asignaciones ni solicitudes con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($asignaciones->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $asignaciones->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
