@extends('layouts.dashboard')

@section('title', 'Gestión de Subgrupos y Estudiantes - Bienestar UPEMOR')

@push('styles')
<style>
    .grupo-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 16px -2px rgba(109, 40, 217, 0.05);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Encabezado General de la Asignatura -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('docente.academico.index') }}" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a Mis Asignaturas
                </a>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">{{ $asignacion->asignatura->nombre }}</h1>
            <p class="text-muted small mb-0">
                Clave: <strong>{{ $asignacion->asignatura->clave }}</strong> &bull; 
                Programa: <strong>{{ $asignacion->asignatura->programa?->nombre ?? 'UPEMOR' }}</strong> &bull; 
                Periodo: {{ $asignacion->periodo->nombre }} &bull;
                Subgrupos asignados: <strong>{{ isset($datosPorSubgrupo) ? $datosPorSubgrupo->count() : 1 }}</strong>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('docente.recompensas.create', $asignacion->asignacion_id) }}" class="btn btn-primary rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-award" aria-hidden="true"></i>
                <span class="fw-semibold">Activar Beneficio Académico</span>
            </a>
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

    <!-- NAVEGACIÓN POR SUBGRUPOS (PESTAÑAS BOOTSTRAP) -->
    @if(isset($datosPorSubgrupo) && $datosPorSubgrupo->count() > 1)
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-2">
            <div class="d-flex align-items-center justify-content-between px-3 pt-2 pb-1">
                <span class="small fw-bold text-muted text-uppercase">
                    <i class="bi bi-diagram-3-fill me-1 text-primary"></i>Selecciona un Subgrupo para Administrar:
                </span>
            </div>
            <ul class="nav nav-pills gap-2 p-2" id="subgruposTabs" role="tablist">
                @foreach($datosPorSubgrupo as $sub)
                    @php
                        $esActivo = ($sub['asignacion']->asignacion_id === $asignacion->asignacion_id);
                    @endphp
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-4 @if($esActivo) active @endif" id="tab-subgrupo-{{ $sub['asignacion']->asignacion_id }}" data-bs-toggle="pill" data-bs-target="#pane-subgrupo-{{ $sub['asignacion']->asignacion_id }}" type="button" role="tab" aria-selected="{{ $esActivo ? 'true' : 'false' }}">
                            <span class="fw-semibold">Subgrupo {{ $sub['grupo']->cuatrimestre }}° "{{ $sub['grupo']->nombre }}"</span>
                            <span class="badge rounded-pill {{ $esActivo ? 'bg-white text-dark' : 'bg-light text-secondary border' }} ms-1">
                                {{ $sub['inscripcionesGrupo']->count() }} alumnos
                            </span>
                            @if($sub['solicitudesMateria']->count() > 0)
                                <span class="badge bg-warning text-dark rounded-pill ms-1">
                                    {{ $sub['solicitudesMateria']->count() }} pend.
                                </span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- CONTENIDO DE CADA SUBGRUPO -->
    <div class="tab-content" id="subgruposTabContent">
        @php
            $subgruposRender = isset($datosPorSubgrupo) ? $datosPorSubgrupo : collect([[
                'asignacion' => $asignacion,
                'grupo' => $asignacion->grupo,
                'inscripcionesGrupo' => $inscripcionesGrupo,
                'solicitudesMateria' => $solicitudesMateria,
                'inscritosMateria' => $inscritosMateria,
            ]]);
        @endphp

        @foreach($subgruposRender as $sub)
            @php
                $subAsig = $sub['asignacion'];
                $subGrupo = $sub['grupo'];
                $subInscripciones = $sub['inscripcionesGrupo'];
                $subSolicitudes = $sub['solicitudesMateria'];
                $subInscritos = $sub['inscritosMateria'];
                $esActivo = ($subAsig->asignacion_id === $asignacion->asignacion_id);
            @endphp

            <div class="tab-pane fade @if($esActivo) show active @endif" id="pane-subgrupo-{{ $subAsig->asignacion_id }}" role="tabpanel" aria-labelledby="tab-subgrupo-{{ $subAsig->asignacion_id }}" tabindex="0">
                <!-- Barra de Resumen del Subgrupo -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-body p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-people-fill fs-4" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold text-dark mb-0">
                                    Subgrupo {{ $subGrupo->cuatrimestre }}° "{{ $subGrupo->nombre }}"
                                </h2>
                                <span class="text-muted small">
                                    Turno: <strong>{{ $subGrupo->turno }}</strong> &bull; Total Inscritos: <strong>{{ $subInscripciones->count() }} alumnos</strong>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('docente.recompensas.create', $subAsig->asignacion_id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold">
                                <i class="bi bi-award me-1" aria-hidden="true"></i>Activar Beneficio para Grupo {{ $subGrupo->nombre }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 1: ESTUDIANTES CON INSCRIPCIÓN APROBADA EN ESTA ASIGNATURA -->
                <div class="grupo-card p-0 overflow-hidden mb-4 border border-success-subtle shadow-sm">
                    <div class="p-3 bg-success-subtle bg-opacity-25 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h3 class="h6 fw-bold text-success mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-mortarboard-fill text-success" aria-hidden="true"></i>
                                Estudiantes Cursantes con Inscripción Aprobada en {{ $subAsig->asignatura->nombre }}
                            </h3>
                            <span class="small text-muted">
                                Alumnos formalmente inscritos en tu cátedra para el subgrupo {{ $subGrupo->nombre }}.
                            </span>
                        </div>
                        <span class="badge bg-success rounded-pill px-3 py-1.5 fw-bold">
                            {{ $subInscritos->count() }} cursante(s)
                        </span>
                    </div>

                    @if ($subInscritos->isEmpty())
                        <div class="text-center py-4 px-3 bg-white">
                            <i class="bi bi-person-x fs-2 text-muted d-block mb-2" aria-hidden="true"></i>
                            <p class="text-muted small mb-1 fw-medium">No hay estudiantes con inscripción aprobada en esta asignatura todavía.</p>
                            <p class="text-muted small mb-0">Los estudiantes adscritos a este grupo deben enviar su solicitud de inscripción y ser autorizados para cursar la cátedra.</p>
                        </div>
                    @else
                        <div class="table-responsive bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 small text-uppercase text-muted fw-semibold">Estudiante Cursante</th>
                                        <th class="py-3 small text-uppercase text-muted fw-semibold">Matrícula</th>
                                        <th class="py-3 small text-uppercase text-muted fw-semibold">Correo Institucional</th>
                                        <th class="py-3 small text-uppercase text-muted fw-semibold">Programa Educativo</th>
                                        <th class="py-3 small text-uppercase text-muted fw-semibold text-center">Fecha Aprobación</th>
                                        <th class="pe-4 py-3 small text-uppercase text-muted fw-semibold text-center">Estatus Cátedra</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subInscritos as $inscrito)
                                        @php
                                            $u = $inscrito->estudianteUsuario;
                                            $est = $u?->estudiante;
                                            $progClave = $est?->programa?->clave ?? $est?->programa?->siglas ?? 'UPEMOR';
                                        @endphp
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; font-weight: 600; font-size: 0.85rem;">
                                                        {{ strtoupper(substr($u?->nombre ?? 'A', 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold text-dark small">
                                                            {{ $u?->nombre }} {{ $u?->apellido_paterno }} {{ $u?->apellido_materno }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 text-dark small font-monospace fw-medium">
                                                {{ $est?->matricula ?? 'N/D' }}
                                            </td>
                                            <td class="py-3 text-muted small">
                                                {{ $u?->email }}
                                            </td>
                                            <td class="py-3 text-dark small">
                                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill fw-bold">{{ $progClave }}</span>
                                            </td>
                                            <td class="py-3 text-muted small text-center">
                                                {{ $inscrito->resuelto_en ? \Carbon\Carbon::parse($inscrito->resuelto_en)->format('d/m/Y H:i') : ($inscrito->updated_at ? $inscrito->updated_at->format('d/m/Y H:i') : '-') }}
                                            </td>
                                            <td class="pe-4 py-3 text-center">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold">
                                                    <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Inscrito
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- SECCIÓN 2: SOLICITUDES DE INSCRIPCIÓN PENDIENTES DEL SUBGRUPO -->
                @if($subSolicitudes->isNotEmpty())
                    <div class="card border-0 shadow-sm border-start border-warning border-4 rounded-4 mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="h6 fw-bold text-warning mb-0">
                                    <i class="bi bi-person-plus-fill me-1" aria-hidden="true"></i>Solicitudes de Inscripción Pendientes — Subgrupo {{ $subGrupo->nombre }}
                                </h3>
                                <span class="small text-muted">Estudiantes de esta sección que han solicitado cursar tu asignatura.</span>
                            </div>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold">
                                {{ $subSolicitudes->count() }} solicitud(es)
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" style="width: 30%;">Estudiante</th>
                                        <th style="width: 15%;">Matrícula</th>
                                        <th style="width: 20%;">Correo Institucional</th>
                                        <th style="width: 15%;">Fecha Solicitud</th>
                                        <th class="pe-4 text-end" style="width: 20%;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subSolicitudes as $sol)
                                        @php
                                            $u = $sol->estudianteUsuario;
                                            $est = $u?->estudiante;
                                        @endphp
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark">{{ $u?->nombre }} {{ $u?->apellido_paterno }} {{ $u?->apellido_materno }}</div>
                                                <div class="small text-muted">{{ $est?->programa?->clave }} &bull; {{ $est?->cuatrimestre }}° "{{ $est?->grupo }}"</div>
                                            </td>
                                            <td class="font-monospace small fw-medium">{{ $est?->matricula ?? 'N/D' }}</td>
                                            <td class="small text-muted">{{ $u?->email }}</td>
                                            <td class="small text-muted">
                                                {{ $sol->solicitado_en ? \Carbon\Carbon::parse($sol->solicitado_en)->format('d/m/Y H:i') : '-' }}
                                            </td>
                                            <td class="pe-4 text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <!-- Botón Modal Aprobar -->
                                                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAprobarInsc{{ $sol->inscripcion_materia_id }}">
                                                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Aprobar
                                                    </button>

                                                    <!-- Botón Modal Rechazar -->
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalRechazarInsc{{ $sol->inscripcion_materia_id }}">
                                                        <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Rechazar
                                                    </button>
                                                </div>

                                                <!-- Modal de Aprobación de Alumno -->
                                                <div class="modal fade" id="modalAprobarInsc{{ $sol->inscripcion_materia_id }}" tabindex="-1" aria-labelledby="modalAprobarInscLabel{{ $sol->inscripcion_materia_id }}" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <form method="POST" action="{{ route('docente.academico.inscripciones.aprobar', $sol->inscripcion_materia_id) }}" class="modal-content text-start">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title text-success" id="modalAprobarInscLabel{{ $sol->inscripcion_materia_id }}">
                                                                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Aprobar Inscripción de Estudiante
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="mb-2">
                                                                    ¿Confirmas que deseas inscribir formalmente a <strong>{{ $u?->nombre }} {{ $u?->apellido_paterno }}</strong> en la asignatura <strong>{{ $subAsig->asignatura->nombre }}</strong> (Subgrupo {{ $subGrupo->nombre }})?
                                                                </p>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">Mensaje o bienvenida <span class="text-muted small">(opcional)</span></label>
                                                                    <textarea name="motivo_resolucion" rows="2" class="form-control" placeholder="Ej. Bienvenido a la clase."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-success rounded-pill">
                                                                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Confirmar Inscripción
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                                <!-- Modal de Rechazo de Alumno -->
                                                <div class="modal fade" id="modalRechazarInsc{{ $sol->inscripcion_materia_id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <form method="POST" action="{{ route('docente.academico.inscripciones.rechazar', $sol->inscripcion_materia_id) }}" class="modal-content text-start">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title text-danger">Rechazar Solicitud de Inscripción</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="small text-muted mb-3">
                                                                    Indica el motivo del rechazo para el estudiante {{ $u?->nombre }}.
                                                                </p>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">Motivo u observaciones <span class="text-danger">*</span></label>
                                                                    <textarea name="motivo_resolucion" rows="3" class="form-control" required placeholder="Ej. El cupo del grupo está completo o no cumple con prerrequisitos."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-danger rounded-pill">Confirmar Rechazo</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- SECCIÓN 3: PADRÓN OFICIAL DEL GRUPO ESCOLAR (SERVICIOS ESCOLARES) -->
                <div class="grupo-card p-0 overflow-hidden mb-4">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h3 class="h6 fw-bold text-dark mb-0">
                                <i class="bi bi-people-fill me-1 text-primary" aria-hidden="true"></i>Padrón Nominal Oficial de la Sección Escolar — Subgrupo {{ $subGrupo->cuatrimestre }}° "{{ $subGrupo->nombre }}"
                            </h3>
                            <span class="small text-muted">
                                Estudiantes matriculados en esta sección por Servicios Escolares y su estado de inscripción a esta materia.
                            </span>
                        </div>
                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                            {{ $subInscripciones->count() }} estudiante(s) en la sección
                        </span>
                    </div>

                    @if ($subInscripciones->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-person-x fs-2 text-muted d-block mb-2" aria-hidden="true"></i>
                            <p class="text-muted small mb-0">No hay estudiantes adscritos formalmente en este subgrupo escolar.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 ps-4 py-3 small text-uppercase text-muted fw-semibold">Nombre del Estudiante</th>
                                        <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Matrícula</th>
                                        <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Correo Institucional</th>
                                        <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Programa Educativo</th>
                                        <th class="border-0 py-3 small text-uppercase text-muted fw-semibold text-center">Cuatrimestre</th>
                                        <th class="border-0 py-3 small text-uppercase text-muted fw-semibold text-center">Fecha Inscripción</th>
                                        <th class="border-0 py-3 small text-uppercase text-muted fw-semibold text-center">Estatus en esta Asignatura</th>
                                        <th class="border-0 pe-4 py-3 small text-uppercase text-muted fw-semibold text-center">Adscripción Escolar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subInscripciones as $insc)
                                        @php
                                            $u = $insc->estudianteUsuario;
                                            $est = $u?->estudiante;
                                            $progClave = $est?->programa?->clave ?? $est?->programa?->siglas ?? 'UPEMOR';
                                            $progNombre = $est?->programa?->nombre ?? 'Universidad Politécnica del Estado de Morelos';

                                            // Identificar estado del alumno en esta asignatura específica
                                            $estaAprobado = $subInscritos->contains(fn($item) => $item->estudiante_id === ($u?->usuario_id ?? 0));
                                            $tienePendiente = $subSolicitudes->contains(fn($item) => $item->estudiante_id === ($u?->usuario_id ?? 0));
                                        @endphp
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; font-weight: 600; font-size: 0.85rem;">
                                                        {{ strtoupper(substr($u?->nombre ?? 'A', 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold text-dark small">
                                                            {{ $u?->nombre }} {{ $u?->apellido_paterno }} {{ $u?->apellido_materno }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 text-dark small font-monospace fw-medium">
                                                {{ $est?->matricula ?? 'N/D' }}
                                            </td>
                                            <td class="py-3 text-muted small">
                                                {{ $u?->email }}
                                            </td>
                                            <td class="py-3 text-dark small">
                                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill me-1 fw-bold">{{ $progClave }}</span>
                                                <span class="text-muted small" title="{{ $progNombre }}">{{ Str::limit($progNombre, 28) }}</span>
                                            </td>
                                            <td class="py-3 text-dark small text-center">
                                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1">
                                                    {{ $est?->cuatrimestre ? $est->cuatrimestre . '°' : 'N/D' }}
                                                </span>
                                            </td>
                                            <td class="py-3 text-muted small text-center">
                                                {{ $insc->created_at ? $insc->created_at->format('d/m/Y H:i') : '-' }}
                                            </td>
                                            <td class="py-3 text-center">
                                                @if($estaAprobado)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Inscrito
                                                    </span>
                                                @elseif($tienePendiente)
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1">
                                                        <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Solicitud pendiente
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1">
                                                        <i class="bi bi-dash-circle me-1" aria-hidden="true"></i>Sin inscripción
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="pe-4 py-3 text-center">
                                                <span class="badge badge-estado-activo">
                                                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Grupo Activo
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
        @endforeach
    </div>

    <!-- Aviso de Privacidad Estricta -->
    <div class="p-3 p-md-4 rounded-4 bg-light border text-muted small mt-2">
        <div class="d-flex gap-3 align-items-center">
            <span class="fs-4">🛡️</span>
            <div>
                <h3 class="h6 fw-bold text-dark mb-1">Principio de Mínimo Privilegio y Confidencialidad Académica</h3>
                <p class="mb-0">
                    Como docente titular, tienes acceso exclusivo a la información académica necesaria para la impartición de tu materia. Los registros emocionales de la bitácora personal, los cuestionarios psicológicos y los expedientes de orientación están estrictamente aislados y no son accesibles desde este panel.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
