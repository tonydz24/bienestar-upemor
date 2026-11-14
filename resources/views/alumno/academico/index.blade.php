@extends('layouts.dashboard')

@section('title', 'Mi Portal Académico - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Encabezado -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('alumno.dashboard') }}" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver al Inicio
                </a>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="bi bi-mortarboard-fill me-2 text-primary" aria-hidden="true"></i>Mi Portal Académico e Inscripciones
            </h1>
            <p class="text-muted small mb-0">
                Periodo activo: <strong>{{ $periodoActivo ? $periodoActivo->nombre . ' (' . $periodoActivo->clave . ')' : 'Sin periodo activo definido' }}</strong>
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5" aria-hidden="true"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5" aria-hidden="true"></i>
            <div>{{ session('warning') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-x-circle-fill me-2 fs-5" aria-hidden="true"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2 fs-5" aria-hidden="true"></i>
            <div>{{ session('info') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- TARJETA: MI GRUPO ACADÉMICO -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="bi bi-people-fill fs-3" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Adscripción Oficial</span>
                        @if($miGrupo)
                            <h2 class="h4 fw-bold text-dark mb-0">
                                Grupo {{ $miGrupo->cuatrimestre }}° "{{ $miGrupo->nombre }}" — {{ $miGrupo->programa?->nombre ?? 'UPEMOR' }}
                            </h2>
                            <p class="text-muted small mb-0">
                                Turno: <strong>{{ $miGrupo->turno }}</strong> &bull; Clave de Carrera: <strong>{{ $miGrupo->programa?->clave }}</strong>
                                @if(!empty($estudiante))
                                    &bull; Generación: <strong>{{ $estudiante->generacion }}</strong> &bull; Matrícula: <strong>{{ $estudiante->matricula }}</strong>
                                @endif
                            </p>
                        @else
                            <h2 class="h5 fw-bold text-danger mb-0">Sin grupo asignado para este periodo</h2>
                            <p class="text-muted small mb-0">Comunícate con Servicios Escolares o Coordinación de Carrera para formalizar tu grupo.</p>
                        @endif
                    </div>
                </div>

                @if($miGrupo)
                    <div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Grupo Autorizado: Sección "{{ $miGrupo->nombre }}"
                        </span>
                    </div>
                @endif
            </div>

            <div class="alert alert-light border rounded-3 mt-3 mb-0 small text-muted">
                <i class="bi bi-info-circle me-1 text-primary" aria-hidden="true"></i>
                <strong>Políticas de asignación por grupo:</strong> Por normativa institucional, las solicitudes de inscripción están delimitadas estrictamente al grupo al que perteneces (ej. Grupo A vs Grupo B). No se admiten inscripciones cruzadas entre secciones para salvaguardar los cupos y la trazabilidad académica.
            </div>
        </div>
    </div>

    <!-- SECCIÓN: MIS MATERIAS INSCRITAS (APROBADAS) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold text-dark mb-0">
                <i class="bi bi-journal-check me-1 text-success" aria-hidden="true"></i>Mis Materias Inscritas ({{ $materiasInscritas->count() }})
            </h2>
            <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">Periodo Actual</span>
        </div>
        <div class="card-body p-0">
            @if($materiasInscritas->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-journal-x fs-2 text-muted d-block mb-2" aria-hidden="true"></i>
                    <p class="text-muted small mb-0">Aún no cuentas con materias inscritas aprobadas en este periodo.</p>
                    <p class="text-muted small">Consulta la oferta disponible abajo para solicitar tu inscripción.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 35%;">Materia / Asignatura</th>
                                <th style="width: 30%;">Docente Titular</th>
                                <th style="width: 15%; text-align: center;">Grupo</th>
                                <th class="pe-4 text-center" style="width: 20%;">Estado Académico</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($materiasInscritas as $materia)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $materia->asignatura?->nombre }}</div>
                                        <div class="small text-muted">Clave: {{ $materia->asignatura?->clave }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $materia->asignacion?->docenteUsuario?->nombre }} {{ $materia->asignacion?->docenteUsuario?->apellido_paterno }}</div>
                                        <div class="small text-muted">{{ $materia->asignacion?->docenteUsuario?->email }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            Grupo {{ $materia->grupo?->nombre_completo }}
                                        </span>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <span class="badge badge-estado-activo">
                                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Inscrito / Cursando
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- SECCIÓN: MIS SOLICITUDES EN PROCESO -->
    @if($solicitudesPendientes->isNotEmpty() || $solicitudesOtras->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold text-dark mb-0">
                    <i class="bi bi-clock-history me-1 text-warning" aria-hidden="true"></i>Historial de Solicitudes de Inscripción
                </h2>
                <span class="badge bg-warning-subtle text-warning border px-3 py-1 rounded-pill small">
                    {{ $solicitudesPendientes->count() }} pendiente(s)
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 30%;">Asignatura</th>
                            <th style="width: 25%;">Docente</th>
                            <th style="width: 15%;">Fecha Solicitud</th>
                            <th style="width: 15%; text-align: center;">Estado</th>
                            <th class="pe-4 text-end" style="width: 15%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($solicitudesPendientes as $sol)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $sol->asignatura?->nombre }}</div>
                                    <div class="small text-muted">Grupo {{ $sol->grupo?->nombre_completo }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sol->asignacion?->docenteUsuario?->nombre }} {{ $sol->asignacion?->docenteUsuario?->apellido_paterno }}</div>
                                </td>
                                <td class="small text-muted">
                                    {{ $sol->solicitado_en ? \Carbon\Carbon::parse($sol->solicitado_en)->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-estado-pendiente">
                                        <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Pendiente
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCancelarSol{{ $sol->inscripcion_materia_id }}">
                                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancelar
                                    </button>

                                    <!-- Modal de Confirmación de Cancelación -->
                                    <div class="modal fade" id="modalCancelarSol{{ $sol->inscripcion_materia_id }}" tabindex="-1" aria-labelledby="modalCancelarSolLabel{{ $sol->inscripcion_materia_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-start">
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-danger" id="modalCancelarSolLabel{{ $sol->inscripcion_materia_id }}">
                                                        <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Cancelar Solicitud de Inscripción
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="mb-0">
                                                        ¿Confirmas que deseas cancelar tu solicitud de inscripción a la materia <strong>{{ $sol->asignatura?->nombre }}</strong>? Esta acción no se puede deshacer.
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Mantener Solicitud</button>
                                                    <form method="POST" action="{{ route('alumno.academico.cancelar', $sol->inscripcion_materia_id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger rounded-pill">
                                                            <i class="bi bi-trash me-1" aria-hidden="true"></i>Sí, Cancelar Solicitud
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        @foreach($solicitudesOtras as $otra)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $otra->asignatura?->nombre }}</div>
                                    <div class="small text-muted">Grupo {{ $otra->grupo?->nombre_completo }}</div>
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $otra->asignacion?->docenteUsuario?->nombre }} {{ $otra->asignacion?->docenteUsuario?->apellido_paterno }}</div>
                                </td>
                                <td class="small text-muted">
                                    {{ $otra->solicitado_en ? \Carbon\Carbon::parse($otra->solicitado_en)->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $otra->estado === 'RECHAZADA' ? 'badge-estado-inactivo' : 'bg-secondary-subtle text-secondary border' }}">
                                        {{ $otra->estado }}
                                    </span>
                                </td>
                                <td class="pe-4 text-end small text-muted">
                                    {{ $otra->motivo_resolucion ?: 'Sin observaciones' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- SECCIÓN: OFERTA ACADÉMICA DISPONIBLE PARA SU GRUPO -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 fw-bold text-dark mb-0">
                    <i class="bi bi-collection-play me-1 text-primary" aria-hidden="true"></i>Oferta Académica Disponible para tu Grupo
                </h2>
                <span class="small text-muted">Materias autorizadas para tu carrera y cuatrimestre, adscritas a tu sección oficial.</span>
            </div>
            @if($miGrupo)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small fw-semibold">
                    Sección "{{ $miGrupo->nombre }}"
                </span>
            @endif
        </div>
        <div class="card-body">
            @if(isset($tablaInscripcionesDisponible) && !$tablaInscripcionesDisponible)
                <div class="alert alert-info border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill fs-5 text-primary flex-shrink-0" aria-hidden="true"></i>
                    <div class="small">
                        <strong>Periodo de registro en preparación:</strong> La oferta curricular autorizada se encuentra publicada para tu consulta. El módulo de recepción de solicitudes se habilitará formalmente en los próximos días conforme al calendario escolar.
                    </div>
                </div>
            @endif

            @if(!$miGrupo)
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-exclamation-circle fs-3 text-warning d-block mb-2" aria-hidden="true"></i>
                    No es posible consultar la oferta sin un grupo asignado formalmente.
                </div>
            @elseif(($ofertaAgrupada ?? collect())->isEmpty() && $ofertaDisponible->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-all fs-2 text-success d-block mb-2" aria-hidden="true"></i>
                    <p class="mb-0 fw-semibold text-dark">No hay materias pendientes por solicitar en tu grupo.</p>
                    <p class="small text-muted mb-0">Has solicitado o estás inscrito en todas las asignaturas ofertadas actualmente para tu sección.</p>
                </div>
            @else
                {{-- OFERTA AGRUPADA PRIMERO POR ASIGNATURA (Requisito Oficial Incremento 18) --}}
                <div class="row g-3">
                    @php
                        $catalogoRender = ($ofertaAgrupada && $ofertaAgrupada->isNotEmpty())
                            ? $ofertaAgrupada
                            : $ofertaDisponible->groupBy('asignatura_id')->map(function ($items) {
                                return [
                                    'asignatura' => $items->first()->asignatura,
                                    'docentes_oferta' => $items,
                                    'total_docentes' => $items->count(),
                                ];
                            })->values();
                    @endphp

                    @foreach($catalogoRender as $item)
                        @php
                            $asig = $item['asignatura'];
                            $docentesOferta = $item['docentes_oferta'];
                        @endphp
                        <div class="col-12 col-lg-6">
                            <div class="card h-100 border rounded-4 p-3 shadow-xs d-flex flex-column justify-content-between bg-white">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-3 py-1 small fw-semibold">
                                            {{ $asig?->clave ?? 'UPEMOR' }}
                                        </span>
                                        <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                            {{ $asig?->cuatrimestre }}° Cuatrimestre &bull; {{ $asig?->programa?->clave }}
                                        </span>
                                    </div>

                                    <h3 class="h6 fw-bold text-dark mb-1">{{ $asig?->nombre }}</h3>
                                    <p class="text-muted small mb-3">
                                        Grupo elegible: <strong>{{ $miGrupo->nombre_completo ?? ("Grupo " . $miGrupo->nombre) }}</strong>
                                    </p>

                                    {{-- Docentes disponibles para esta asignatura --}}
                                    <div class="d-flex flex-column gap-2 mb-3">
                                        @foreach($docentesOferta as $oferta)
                                            <div class="p-2.5 rounded-3 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                <div>
                                                    <div class="small text-muted">Docente titular:</div>
                                                    <div class="fw-semibold text-dark small">
                                                        <i class="bi bi-person-badge me-1 text-primary" aria-hidden="true"></i>
                                                        {{ $oferta->docenteUsuario?->nombre }} {{ $oferta->docenteUsuario?->apellido_paterno }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalInscribir{{ $oferta->asignacion_id }}" @if(isset($tablaInscripcionesDisponible) && !$tablaInscripcionesDisponible) disabled title="En preparación administrativa" @endif>
                                                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Solicitar
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Modal para Confirmar Solicitud de Inscripción -->
                                            <div class="modal fade" id="modalInscribir{{ $oferta->asignacion_id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <form method="POST" action="{{ route('alumno.academico.solicitar') }}" class="modal-content text-start" onsubmit="const btn = this.querySelector('.btn-submit-solicitud'); if(btn){ btn.disabled = true; btn.innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span>Procesando...'; }">
                                                        @csrf
                                                        <input type="hidden" name="asignacion_id" value="{{ $oferta->asignacion_id }}">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title text-primary">
                                                                <i class="bi bi-mortarboard me-1" aria-hidden="true"></i>Confirmar Solicitud de Materia
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-2">
                                                                ¿Confirmas la solicitud de inscripción para la asignatura <strong>{{ $asig?->nombre }}</strong> en tu grupo oficial <strong>{{ $miGrupo->nombre_completo ?? $miGrupo->nombre }}</strong>?
                                                            </p>
                                                            <p class="small text-muted mb-3">
                                                                Docente titular: <strong>{{ $oferta->docenteUsuario?->nombre }} {{ $oferta->docenteUsuario?->apellido_paterno }}</strong>. La solicitud se remitirá para su validación académica.
                                                            </p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Comentario para el docente <span class="text-muted small">(opcional)</span></label>
                                                                <textarea name="motivo_solicitud" rows="2" class="form-control" placeholder="Ej. Solicitud de registro al cuatrimestre regular..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary rounded-pill btn-submit-solicitud">
                                                                <i class="bi bi-send me-1" aria-hidden="true"></i>Confirmar Solicitud
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if(isset($materiasSinOferta) && $materiasSinOferta->isNotEmpty())
        @if(request()->boolean('ver_sin_oferta'))
            <!-- SECCIÓN: MATERIAS DEL PLAN SIN OFERTA DOCENTE (FN.6 / SECCIÓN 6.3) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light" id="seccion-sin-oferta">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 fw-bold text-muted mb-0">
                        <i class="bi bi-clock-history me-1 text-secondary" aria-hidden="true"></i>Materias de tu plan todavía sin oferta disponible ({{ $materiasSinOferta->count() }})
                    </h2>
                    <div>
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill small me-2">Informativo</span>
                        <a href="{{ route('alumno.academico.index') }}" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;">Ocultar</a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <p class="small text-muted mb-3">
                        Las siguientes asignaturas forman parte del mapa curricular de tu cuatrimestre ({{ $miGrupo?->cuatrimestre }}°), pero aún no cuentan con un docente autorizado o asignación abierta para tu grupo en este periodo operativo.
                    </p>
                    <div class="row g-2">
                        @foreach($materiasSinOferta as $sinOferta)
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="p-2 border rounded-3 bg-white d-flex align-items-center justify-content-between">
                                    <div class="text-truncate me-2">
                                        <span class="fw-semibold small text-dark d-block text-truncate">{{ $sinOferta->nombre }}</span>
                                        <span class="text-muted" style="font-size: 0.75rem;">Clave: {{ $sinOferta->clave }} &bull; {{ $sinOferta->horas_semana ?? 4 }} hrs/sem</span>
                                    </div>
                                    <span class="badge bg-light text-muted border text-uppercase" style="font-size: 0.65rem;">Sin oferta</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="text-center mb-4">
                <a href="{{ route('alumno.academico.index', ['ver_sin_oferta' => 1]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Consultar {{ $materiasSinOferta->count() }} materias de tu plan sin oferta docente en este periodo
                </a>
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form[action="{{ route('alumno.academico.solicitar') }}"]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const btn = form.querySelector('.btn-submit-solicitud');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Enviando...';
                }
            });
        });
    });
</script>
@endpush

