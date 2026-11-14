@extends('layouts.dashboard')

@section('title', 'Catálogo y Moderación de Recompensas - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Catálogo Institucional y Propuestas de Recompensas</h1>
            <p class="text-muted mb-0">Gestión de beneficios académicos digitales y moderación de propuestas enviadas por el cuerpo docente.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNuevaRecompensa">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Nuevo Beneficio Institucional</span>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-auto-dismiss mb-4" role="alert" data-auto-dismiss="4000">
            <strong>Éxito:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h6 class="fw-bold mb-2">Por favor corrige los siguientes errores:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- Pestañas de Navegación -->
    <ul class="nav nav-tabs nav-fill mb-4 border-bottom border-light" id="recompensasTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="institucionales-tab" data-bs-toggle="tab" data-bs-target="#institucionales" type="button" role="tab" aria-controls="institucionales" aria-selected="true">
                Catálogo Institucional ({{ $institucionales->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold position-relative" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button" role="tab" aria-controls="pendientes" aria-selected="false">
                Propuestas de Profesores
                @if($propuestasPendientes->isNotEmpty())
                    <span class="badge bg-danger rounded-pill ms-1">{{ $propuestasPendientes->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="resueltas-tab" data-bs-toggle="tab" data-bs-target="#resueltas" type="button" role="tab" aria-controls="resueltas" aria-selected="false">
                Historial de Propuestas
            </button>
        </li>
    </ul>

    <div class="tab-content" id="recompensasTabsContent">
        <!-- Pestaña 1: Catálogo Institucional -->
        <div class="tab-pane fade show active" id="institucionales" role="tabpanel" aria-labelledby="institucionales-tab">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-4">Beneficio</th>
                                <th scope="col">Categoría</th>
                                <th scope="col" class="text-center">Costo Base</th>
                                <th scope="col">Condiciones Generales</th>
                                <th scope="col" class="text-center">Activaciones Activas</th>
                                <th scope="col" class="text-center">Estado</th>
                                <th scope="col" class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($institucionales as $rec)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $rec->nombre }}</div>
                                        <small class="text-muted">{{ $rec->descripcion }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            {{ $rec->categoria }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-dark border px-2 py-1 fw-bold">
                                            🪙 {{ $rec->costo_puntos_base }} pts
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-secondary">{{ $rec->condiciones_generales ?? 'Sin condiciones particulares.' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            {{ $rec->activaciones_count }} grupos
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($rec->activo)
                                            <span class="badge bg-success px-2 py-1">Activo</span>
                                        @else
                                            <span class="badge bg-secondary px-2 py-1">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        {{-- Acción Editar --}}
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-1" data-bs-toggle="modal" data-bs-target="#modalEditarRecompensa{{ $rec->recompensa_id }}" title="Editar beneficio">
                                            <i class="bi bi-pencil me-1"></i> Editar
                                        </button>

                                        {{-- Acción Activar / Desactivar --}}
                                        <form action="{{ route('admin.recompensas.toggle', $rec->recompensa_id) }}" method="POST" class="d-inline me-1">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm rounded-pill px-3 {{ $rec->activo ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $rec->activo ? 'Desactivar beneficio del catálogo' : 'Activar beneficio en catálogo' }}">
                                                <i class="bi {{ $rec->activo ? 'bi-pause-circle' : 'bi-play-circle' }} me-1"></i>
                                                {{ $rec->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>

                                        {{-- Acción Eliminar / Retirar --}}
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2.5" data-bs-toggle="modal" data-bs-target="#modalEliminarRecompensa{{ $rec->recompensa_id }}" title="Eliminar o retirar beneficio">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                        <!-- Modal Editar Recompensa -->
                                        <div class="modal fade" id="modalEditarRecompensa{{ $rec->recompensa_id }}" tabindex="-1" aria-labelledby="modalEditarRecompensaLabel{{ $rec->recompensa_id }}" aria-hidden="true" style="white-space: normal !important;">
                                            <div class="modal-dialog modal-dialog-centered text-start">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <form action="{{ route('admin.recompensas.update', $rec->recompensa_id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold" id="modalEditarRecompensaLabel{{ $rec->recompensa_id }}">Editar Beneficio Institucional</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Nombre del Beneficio <span class="text-danger">*</span></label>
                                                                <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $rec->nombre) }}" maxlength="150" required>
                                                            </div>
                                                            <div class="row g-2 mb-3">
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold small">Categoría <span class="text-danger">*</span></label>
                                                                    <select name="categoria" class="form-select" required>
                                                                        <option value="ACADEMICA" @selected($rec->categoria === 'ACADEMICA')>Académica</option>
                                                                        <option value="TIEMPO" @selected($rec->categoria === 'TIEMPO')>Tiempo / Prórroga</option>
                                                                        <option value="PARTICIPACION" @selected($rec->categoria === 'PARTICIPACION')>Participación</option>
                                                                        <option value="RECONOCIMIENTO" @selected($rec->categoria === 'RECONOCIMIENTO')>Reconocimiento</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold small">Costo Base (pts) <span class="text-danger">*</span></label>
                                                                    <input type="number" name="costo_puntos_base" class="form-control" value="{{ old('costo_puntos_base', $rec->costo_puntos_base) }}" min="1" max="500" required>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Descripción del Beneficio <span class="text-danger">*</span></label>
                                                                <textarea name="descripcion" rows="3" class="form-control" maxlength="1000" required>{{ old('descripcion', $rec->descripcion) }}</textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Condiciones Generales</label>
                                                                <textarea name="condiciones_generales" rows="2" class="form-control" maxlength="1000">{{ old('condiciones_generales', $rec->condiciones_generales) }}</textarea>
                                                            </div>
                                                            <div class="form-check form-switch mb-2">
                                                                <input class="form-check-input" type="checkbox" role="switch" name="activo" id="activoSwitch{{ $rec->recompensa_id }}" value="1" @checked($rec->activo)>
                                                                <label class="form-check-label fw-semibold small" for="activoSwitch{{ $rec->recompensa_id }}">Beneficio activo en catálogo</label>
                                                            </div>
                                                            <div class="alert alert-info border-0 py-2 px-3 small mb-0">
                                                                <i class="bi bi-info-circle me-1"></i> Los cambios aplican a futuros canjes. Los canjes completados conservan su registro histórico inalterado.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar Cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Eliminar Recompensa -->
                                        <div class="modal fade" id="modalEliminarRecompensa{{ $rec->recompensa_id }}" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
                                            <div class="modal-dialog modal-dialog-centered text-start" style="max-width: 500px;">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                                            <span>Eliminación / Retiro de Beneficio</span>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted small mb-2">
                                                            ¿Confirmas la eliminación del beneficio institucional <strong>«{{ $rec->nombre }}»</strong>?
                                                        </p>
                                                        <p class="text-muted small mb-0">
                                                            Si el beneficio cuenta con canjes históricos realizados por estudiantes, el sistema lo desactivará automáticamente del catálogo para proteger la integridad contable de los puntos. Si no tiene canjes, se eliminará definitivamente.
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                        <form action="{{ route('admin.recompensas.destroy', $rec->recompensa_id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold">Confirmar</button>
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
                                        No hay beneficios institucionales en el catálogo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pestaña 2: Propuestas Pendientes -->
        <div class="tab-pane fade" id="pendientes" role="tabpanel" aria-labelledby="pendientes-tab">
            @forelse($propuestasPendientes as $propuesta)
                <div class="card border-0 shadow-sm rounded-4 mb-3 p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                        <div style="max-width: 750px;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning text-dark px-2 py-1">Propuesta Pendiente</span>
                                <span class="badge bg-light text-secondary border">{{ $propuesta->categoria }}</span>
                                <span class="badge bg-warning-subtle text-dark border fw-bold">🪙 {{ $propuesta->costo_puntos_base }} puntos</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">{{ $propuesta->nombre }}</h5>
                            <p class="text-secondary mb-2">{{ $propuesta->descripcion }}</p>
                            <div class="bg-light p-3 rounded-3 border mb-2 small">
                                <div class="fw-semibold text-dark">Justificación del Docente:</div>
                                <div class="text-muted fst-italic">"{{ $propuesta->justificacion_docente }}"</div>
                            </div>
                            <small class="text-muted">
                                Propuesto por: <strong>{{ $propuesta->docenteSolicitante?->nombre }} {{ $propuesta->docenteSolicitante?->apellido_paterno }}</strong> ({{ $propuesta->docenteSolicitante?->email }}) • {{ $propuesta->created_at?->diffForHumans() }}
                            </small>
                        </div>
                        <div class="d-flex flex-md-column gap-2 w-100 w-md-auto">
                            <form action="{{ route('admin.recompensas.aprobar', $propuesta->recompensa_id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Aprobar Beneficio
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#modalRechazar{{ $propuesta->recompensa_id }}">
                                Rechazar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Rechazar -->
                <div class="modal fade" id="modalRechazar{{ $propuesta->recompensa_id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content rounded-4 border-0">
                            <form action="{{ route('admin.recompensas.rechazar', $propuesta->recompensa_id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Rechazar Propuesta Docente</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small">Indica el motivo por el cual no es viable autorizar este beneficio. El profesor podrá consultar la retroalimentación.</p>
                                    <div class="mb-3">
                                        <label for="motivoRechazo{{ $propuesta->recompensa_id }}" class="form-label fw-semibold">Motivo de Rechazo <span class="text-danger">*</span></label>
                                        <textarea name="motivo" id="motivoRechazo{{ $propuesta->recompensa_id }}" rows="3" class="form-control" placeholder="Ej. El beneficio propuesto contraviene las normas de evaluación institucional..." required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                    No hay propuestas docentes pendientes de revisión.
                </div>
            @endforelse
        </div>

        <!-- Pestaña 3: Historial de Propuestas -->
        <div class="tab-pane fade" id="resueltas" role="tabpanel" aria-labelledby="resueltas-tab">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-4">Beneficio</th>
                                <th scope="col">Profesor</th>
                                <th scope="col">Costo</th>
                                <th scope="col">Resolución</th>
                                <th scope="col">Motivo / Notas</th>
                                <th scope="col" class="text-end pe-4">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($propuestasResueltas as $res)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $res->nombre }}</div>
                                        <small class="text-muted">{{ $res->categoria }}</small>
                                    </td>
                                    <td>{{ $res->docenteSolicitante?->nombre }} {{ $res->docenteSolicitante?->apellido_paterno }}</td>
                                    <td>🪙 {{ $res->costo_puntos_base }}</td>
                                    <td>
                                        @if($res->esPropuestaAprobada() || $res->estado_propuesta === 'APROBADA' || $res->estado_propuesta === 'PROPUESTA_APROBADA')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aprobada</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Rechazada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small text-muted">{{ $res->motivo_resolucion_admin ?? 'Aprobada sin observaciones.' }}</div>
                                        @if($res->adminResolutor)
                                            <small class="text-muted" style="font-size: 0.72rem;">Por: {{ $res->adminResolutor->nombre }} {{ $res->adminResolutor->apellido_paterno }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4 small text-muted">
                                        {{ $res->updated_at?->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No hay historial de propuestas moderadas aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($propuestasResueltas->hasPages())
                    <div class="card-footer bg-white border-top border-light py-3 px-4">
                        {{ $propuestasResueltas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Recompensa Institucional -->
<div class="modal fade" id="modalNuevaRecompensa" tabindex="-1" aria-labelledby="modalNuevaRecompensaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <form action="{{ route('admin.recompensas.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalNuevaRecompensaLabel">Nuevo Beneficio Institucional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombreRec" class="form-label fw-semibold">Nombre del Beneficio <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombreRec" class="form-control" placeholder="Ej. Asesoría Personalizada 1 a 1" maxlength="150" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="catRec" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select name="categoria" id="catRec" class="form-select" required>
                                <option value="ACADEMICA">Académica</option>
                                <option value="TIEMPO">Tiempo / Prórroga</option>
                                <option value="PARTICIPACION">Participación</option>
                                <option value="RECONOCIMIENTO">Reconocimiento</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="costoRec" class="form-label fw-semibold">Costo Base (pts) <span class="text-danger">*</span></label>
                            <input type="number" name="costo_puntos_base" id="costoRec" class="form-control" value="50" min="1" max="500" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descRec" class="form-label fw-semibold">Descripción del Beneficio <span class="text-danger">*</span></label>
                        <textarea name="descripcion" id="descRec" rows="2" class="form-control" placeholder="Explica en qué consiste el beneficio..." maxlength="1000" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="condRec" class="form-label fw-semibold">Condiciones Generales</label>
                        <textarea name="condiciones_generales" id="condRec" rows="2" class="form-control" placeholder="Reglas institucionales de aplicación..." maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar en Catálogo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

