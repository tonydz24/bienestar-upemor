@extends('layouts.dashboard')

@section('title', 'Gestión de Recompensas - Bienestar UPEMOR')

@push('styles')
<style>
    .recompensa-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 16px -2px rgba(109, 40, 217, 0.05);
    }
    .codigo-cupon {
        background: #f5f3ff;
        color: #6d28d9;
        font-family: monospace;
        font-weight: 700;
        font-size: 1.05rem;
        padding: 0.35rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px dashed #c4b5fd;
        display: inline-block;
        letter-spacing: 0.05em;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Encabezado -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('docente.dashboard') }}" class="text-decoration-none text-muted small">
                    &larr; Volver al panel de docente
                </a>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Programa de Recompensas e Incentivos</h1>
            <p class="text-muted small mb-0">
                Valida beneficios digitales presentados por estudiantes y gestiona incentivos académicos para tus grupos.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalProponerBeneficio">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span class="fw-semibold">Proponer incentivo</span>
            </button>
            <a href="{{ route('docente.academico.index') }}" class="btn btn-primary rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                </svg>
                <span class="fw-semibold">Habilitar en materias</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-xs border-0 mb-4 portal-auto-dismiss" role="alert" data-auto-dismiss="4000">
            <div class="d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-success flex-shrink-0">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-xs border-0 mb-4" role="alert">
            <div class="d-flex align-items-start gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-danger flex-shrink-0 mt-1">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <div>
                    <strong class="d-block mb-1">Por favor revisa la información ingresada:</strong>
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- Navegación por pestañas profesionales -->
    <ul class="nav nav-pills gap-2 mb-4">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 @if($tab === 'solicitudes') active @endif" href="{{ route('docente.recompensas.index', ['tab' => 'solicitudes']) }}">
                Beneficios por validar
                @if ($solicitudes->count() > 0)
                    <span class="badge bg-warning text-dark rounded-pill ms-2">{{ $solicitudes->count() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 @if($tab === 'activadas') active @endif" href="{{ route('docente.recompensas.index', ['tab' => 'activadas']) }}">
                Habilitados en mis grupos
                <span class="badge bg-light text-muted border rounded-pill ms-2">{{ $activadas->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 @if($tab === 'propuestas') active @endif" href="{{ route('docente.recompensas.index', ['tab' => 'propuestas']) }}">
                Mis propuestas personalizadas
                <span class="badge bg-light text-muted border rounded-pill ms-2">{{ $misPropuestas->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 @if($tab === 'historial') active @endif" href="{{ route('docente.recompensas.index', ['tab' => 'historial']) }}">
                Historial de canjes
            </a>
        </li>
    </ul>

    <!-- Contenido Pestaña 1: Beneficios por Validar -->
    @if ($tab === 'solicitudes')
        @if ($solicitudes->isEmpty())
            <div class="recompensa-card text-center py-5">
                <div class="p-3 rounded-circle bg-light d-inline-flex mb-3" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </div>
                <h2 class="h6 fw-bold text-dark mb-1">No hay beneficios digitales pendientes por validar</h2>
                <p class="text-muted small mb-0">Cuando un estudiante canjee un beneficio y te presente su cupón en clase, aparecerá aquí para que confirmes su aplicación.</p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($solicitudes as $canje)
                    <div class="col-md-6 col-lg-6">
                        <div class="recompensa-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <span class="codigo-cupon">
                                        {{ $canje->codigo_beneficio ?? 'BEN-PENDIENTE' }}
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small fw-semibold">
                                        🪙 {{ $canje->puntos_canjeados }} pts
                                    </span>
                                </div>

                                <h3 class="h5 fw-bold text-dark mb-1">{{ $canje->activacion->recompensaCatalogo->nombre }}</h3>
                                <p class="text-muted small mb-3">
                                    Materia: <strong>{{ $canje->activacion->asignacion->asignatura->nombre }}</strong> &bull; 
                                    Grupo: {{ $canje->activacion->asignacion->grupo->cuatrimestre }}° "{{ $canje->activacion->asignacion->grupo->nombre }}"
                                </p>

                                <div class="p-3 rounded-3 bg-light mb-3 small border">
                                    <div class="fw-semibold text-dark mb-1">
                                        Estudiante: {{ $canje->estudianteUsuario->nombre }} {{ $canje->estudianteUsuario->apellido_paterno }} {{ $canje->estudianteUsuario->apellido_materno }}
                                    </div>
                                    <div class="text-muted mb-2">
                                        Matrícula: {{ $canje->estudianteUsuario->estudiante?->matricula ?? 'N/D' }} &bull; 
                                        Canjeado el: {{ ($canje->fecha_canje ?? $canje->fecha_solicitud)?->format('d/m/Y H:i') }} hrs
                                    </div>
                                    @if ($canje->motivo_solicitud)
                                        <div class="text-muted fst-italic">
                                            "{{ $canje->motivo_solicitud }}"
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalRechazarCanje-{{ $canje->canje_id }}">
                                    Rechazar / Anular
                                </button>
                                <button type="button" class="btn btn-success btn-sm rounded-pill px-4 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalUtilizarCanje-{{ $canje->canje_id }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span>Marcar como Utilizado</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Confirmar Uso de Canje (Accesible Bootstrap 5) -->
                    <div class="modal fade" id="modalUtilizarCanje-{{ $canje->canje_id }}" tabindex="-1" aria-labelledby="modalUtilizarLabel-{{ $canje->canje_id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('docente.recompensas.utilizar', $canje->canje_id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header border-bottom-0 pb-0">
                                        <h5 class="modal-title fw-bold text-dark" id="modalUtilizarLabel-{{ $canje->canje_id }}">Confirmar Uso de Beneficio</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <p class="text-muted small mb-3">
                                            ¿Confirmas que el estudiante <strong>{{ $canje->estudianteUsuario?->nombre }} {{ $canje->estudianteUsuario?->apellido_paterno }}</strong> ha presentado y hecho efectivo su cupón en clase?
                                        </p>
                                        <div class="p-3 rounded-3 bg-light d-flex align-items-center justify-content-between mb-2">
                                            <span class="small text-muted">Cupón presentado:</span>
                                            <span class="codigo-cupon">{{ $canje->codigo_cupon }}</span>
                                        </div>
                                        <div class="alert alert-info py-2 px-3 small rounded-3 mb-0" style="font-size: 0.78rem;">
                                            Esta acción marcará el cupón como <strong>UTILIZADO</strong> y concluirá el ciclo del beneficio.
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-success rounded-pill px-4">
                                            Sí, marcar utilizado
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Rechazar Canje -->
                    <div class="modal fade" id="modalRechazarCanje-{{ $canje->canje_id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('docente.recompensas.rechazar', $canje->canje_id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Anular canje de beneficio</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <p class="text-muted small">
                                            Al anular el cupón, los <strong>{{ $canje->puntos_canjeados }} puntos</strong> se reembolsarán de inmediato al estudiante y el cupo en tu materia se restablecerá.
                                        </p>
                                        <div class="mb-3">
                                            <label for="motivo-canje-{{ $canje->canje_id }}" class="form-label small fw-semibold">Motivo de la anulación <span class="text-danger">*</span>:</label>
                                            <textarea name="motivo" id="motivo-canje-{{ $canje->canje_id }}" rows="2" class="form-control rounded-3 form-control-sm" required placeholder="Ej. El beneficio no aplica en periodo de exámenes departamentales..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">Confirmar anulación</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    <!-- Contenido Pestaña 2: Beneficios Habilitados Agrupados por Asignación Docente -->
    @elseif ($tab === 'activadas')
        @if ($asignacionesDocente->isEmpty())
            <div class="recompensa-card text-center py-5">
                <p class="text-muted small mb-3">Aún no cuentas con asignaturas registradas para este periodo.</p>
                <a href="{{ route('docente.academico.index') }}" class="btn btn-primary btn-sm rounded-pill px-4">
                    Ir a mis asignaturas
                </a>
            </div>
        @else
            <div class="d-flex flex-column gap-4">
                @foreach ($asignacionesDocente as $asig)
                    @php
                        $recompensasDeEstaAsig = $asig->recompensasActivadas;
                    @endphp
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white" style="border-left: 4px solid #7c3aed !important;">
                        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <div>
                                <h3 class="h5 fw-bold text-dark mb-0">{{ $asig->asignatura->nombre }}</h3>
                                <div class="text-muted small">
                                    {{ $asig->asignatura->programa?->nombre ?? 'UPEMOR' }} &bull;
                                    <strong>{{ $asig->asignatura->cuatrimestre }}.º cuatrimestre &bull; Grupo {{ $asig->grupo->nombre }}</strong> &bull;
                                    Turno {{ ucfirst(strtolower($asig->grupo->turno ?? 'Matutino')) }} &bull;
                                    <span class="badge {{ $asig->periodo?->estado_calendario_badge ?? 'bg-light text-muted border' }} py-1 px-2">
                                        {{ $asig->periodo?->estado_calendario_label ?? $asig->periodo?->clave }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-purple-subtle text-purple border rounded-pill px-3 py-1.5 small" style="background: #ede9fe; color: #5b21b6;">
                                    {{ $recompensasDeEstaAsig->count() }} beneficio(s) habilitado(s)
                                </span>
                                <a href="{{ route('docente.recompensas.create', $asig->asignacion_id) }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" style="background-color: #7c3aed; border-color: #7c3aed;">
                                    + Habilitar en esta materia
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            @if ($recompensasDeEstaAsig->isEmpty())
                                <div class="text-center py-4 bg-light rounded-4">
                                    <p class="text-muted small mb-2">No has activado beneficios académicos para los estudiantes de este grupo.</p>
                                    <a href="{{ route('docente.recompensas.create', $asig->asignacion_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Habilitar primer beneficio
                                    </a>
                                </div>
                            @else
                                <div class="row g-3">
                                    @foreach ($recompensasDeEstaAsig as $act)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="recompensa-card h-100 d-flex flex-column justify-content-between border">
                                                <div>
                                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                                        <span class="badge {{ $act->estado_visual_badge }} rounded-pill px-3 py-1 small">
                                                            {{ $act->estado_visual_label }}
                                                        </span>
                                                        <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                                            Cupo: {{ $act->cupo_disponible }} / {{ $act->cupo_maximo }}
                                                        </span>
                                                    </div>

                                                    @if($act->estaDeshabilitadaPorAdmin())
                                                        <div class="alert alert-danger py-1 px-2 small mb-2 rounded-2 d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                                            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                                            <span>Beneficio pausado por la Administración Institucional.</span>
                                                        </div>
                                                    @endif

                                                    <h4 class="h6 fw-bold text-dark mb-1">{{ $act->recompensaCatalogo->nombre }}</h4>
                                                    <p class="text-muted small mb-2">{{ Str::limit($act->recompensaCatalogo->descripcion, 90) }}</p>

                                                    <div class="p-3 rounded-3 bg-light small mb-3 border">
                                                        <div class="d-flex justify-content-between text-muted mb-1">
                                                            <span>Costo fijado:</span>
                                                            <span class="fw-bold text-dark">🪙 {{ $act->costo_puntos }} pts</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-muted mb-1">
                                                            <span>Canjes ejercidos:</span>
                                                            <span class="text-dark fw-semibold">{{ $act->canjes->count() }} alumno(s)</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-muted">
                                                            <span>Vigencia:</span>
                                                            <span class="text-dark">{{ \Carbon\Carbon::parse($act->vigencia_fin)->format('d/m/Y') }}</span>
                                                        </div>
                                                    </div>

                                                    @if ($act->condiciones_docente)
                                                        <div class="small text-muted fst-italic mb-3" style="font-size: 0.78rem;">
                                                            Nota: "{{ $act->condiciones_docente }}"
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                                                    <a href="{{ route('docente.recompensas.edit', $act->activacion_id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                        Editar
                                                    </a>

                                                    @if ($act->estado === 'ACTIVA')
                                                        <form action="{{ route('docente.recompensas.estado', $act->activacion_id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="estado" value="SUSPENDIDA">
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="Pausar canjes temporales">
                                                                Pausar
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('docente.recompensas.estado', $act->activacion_id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="estado" value="ACTIVA">
                                                            <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3" title="Reanudar canjes para alumnos">
                                                                Reanudar
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    <!-- Contenido Pestaña 3: Mis Propuestas Personalizadas -->
    @elseif ($tab === 'propuestas')
        @if ($misPropuestas->isEmpty())
            <div class="recompensa-card text-center py-5">
                <p class="text-muted small mb-3">Aún no has propuesto incentivos personalizados para aprobación de la administración.</p>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalProponerBeneficio">
                    Crear mi primera propuesta
                </button>
            </div>
        @else
            <div class="row g-3">
                @foreach ($misPropuestas as $prop)
                    <div class="col-md-6">
                        <div class="recompensa-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-light text-secondary border">{{ $prop->categoria }}</span>
                                @if($prop->esPropuestaPendiente() || $prop->estado_propuesta === 'PENDIENTE')
                                    <span class="badge bg-warning text-dark">Pendiente de revisión</span>
                                @elseif($prop->esPropuestaAprobada() || $prop->estado_propuesta === 'APROBADA')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Aprobada (Exclusiva)</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rechazada</span>
                                @endif
                            </div>
                            <h4 class="h5 fw-bold text-dark mb-1">{{ $prop->nombre }}</h4>
                            <p class="text-muted small mb-2">{{ $prop->descripcion }}</p>
                            <div class="p-3 bg-light rounded-3 small mb-2">
                                <div class="fw-semibold text-dark">Justificación:</div>
                                <div class="text-muted fst-italic">"{{ $prop->justificacion_docente }}"</div>
                                @if($prop->motivo_resolucion_admin)
                                    <div class="fw-semibold text-danger mt-2">Respuesta administrativa:</div>
                                    <div class="text-danger small">{{ $prop->motivo_resolucion_admin }}</div>
                                @endif
                            </div>
                            <div class="text-muted small">
                                Costo base: <strong>🪙 {{ $prop->costo_puntos_base }} pts</strong> • Enviada: {{ $prop->created_at?->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    <!-- Contenido Pestaña 4: Historial -->
    @else
        @if ($historial->isEmpty())
            <div class="recompensa-card text-center py-5">
                <p class="text-muted small mb-0">No hay registros en el historial de canjes.</p>
            </div>
        @else
            <div class="recompensa-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-4 py-3 small text-uppercase text-muted fw-semibold">Estudiante</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Beneficio</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Cupón</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Materia / Grupo</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Puntos</th>
                                <th class="border-0 pe-4 py-3 small text-uppercase text-muted fw-semibold text-end">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($historial as $h)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold text-dark small">
                                            {{ $h->estudianteUsuario->nombre }} {{ $h->estudianteUsuario->apellido_paterno }}
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.78rem;">
                                            {{ $h->estudianteUsuario->estudiante?->matricula ?? 'N/D' }}
                                        </div>
                                    </td>
                                    <td class="py-3 text-dark small fw-medium">
                                        {{ $h->activacion->recompensaCatalogo->nombre }}
                                    </td>
                                    <td class="py-3 font-monospace small fw-bold text-purple">
                                        {{ $h->codigo_beneficio ?? '-' }}
                                    </td>
                                    <td class="py-3">
                                        <div class="fw-medium text-dark small">
                                            {{ $h->activacion->asignacion->asignatura->nombre }}
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.78rem;">
                                            {{ $h->activacion->asignacion->grupo->cuatrimestre }}° cuatrimestre &bull; Grupo {{ $h->activacion->asignacion->grupo->nombre }}
                                            <span class="text-black-50 ms-1" title="Clave institucional de la asignatura">({{ $h->activacion->asignacion->asignatura->clave }})</span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-dark small fw-semibold">
                                        🪙 {{ $h->puntos_canjeados }}
                                    </td>
                                    <td class="pe-4 py-3 text-end">
                                        <span class="{{ $h->estado_badge_class }}">
                                            {{ $h->estado_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($historial->hasPages())
                    <div class="p-3 border-top">
                        {{ $historial->links() }}
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>

<!-- Modal Proponer Beneficio Personalizado -->
<div class="modal fade" id="modalProponerBeneficio" tabindex="-1" aria-labelledby="modalProponerBeneficioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('docente.recompensas.proponer') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalProponerBeneficioLabel">Proponer Nuevo Beneficio Personalizado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">La propuesta será evaluada por el área administrativa antes de estar disponible en tus asignaturas.</p>
                    <div class="mb-3">
                        <label for="propNombre" class="form-label fw-semibold">Nombre del Beneficio <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="propNombre" class="form-control" placeholder="Ej. Acceso anticipado a material de estudio" maxlength="150" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="propCategoria" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select name="categoria" id="propCategoria" class="form-select" required>
                                <option value="ACADEMICA" {{ old('categoria') === 'ACADEMICA' ? 'selected' : '' }}>Académica</option>
                                <option value="TIEMPO" {{ old('categoria') === 'TIEMPO' ? 'selected' : '' }}>Tiempo / Prórroga</option>
                                <option value="PARTICIPACION" {{ old('categoria') === 'PARTICIPACION' ? 'selected' : '' }}>Participación</option>
                                <option value="RECONOCIMIENTO" {{ old('categoria') === 'RECONOCIMIENTO' ? 'selected' : '' }}>Reconocimiento</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="propCosto" class="form-label fw-semibold">Costo Sugerido (pts) <span class="text-danger">*</span></label>
                            <input type="number" name="costo_puntos" id="propCosto" class="form-control" value="{{ old('costo_puntos', 50) }}" min="1" max="500" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="propDesc" class="form-label fw-semibold">Descripción del Beneficio <span class="text-danger">*</span></label>
                        <textarea name="descripcion" id="propDesc" rows="2" class="form-control" placeholder="Explica detalladamente qué recibirá el alumno..." required>{{ old('descripcion') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="propJust" class="form-label fw-semibold">Justificación Docente <span class="text-danger">*</span></label>
                        <textarea name="justificacion" id="propJust" rows="2" class="form-control" placeholder="¿Cómo fomenta el aprendizaje o la participación de tus estudiantes?..." required>{{ old('justificacion') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="propCond" class="form-label fw-semibold">Condiciones Particulares (opcional)</label>
                        <textarea name="condiciones" id="propCond" rows="2" class="form-control" placeholder="Reglas o restricciones de aplicación...">{{ old('condiciones') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Enviar Propuesta</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
