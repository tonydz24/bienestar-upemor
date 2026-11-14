@extends('layouts.dashboard')

@section('title', 'Portal Docente - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Encabezado Académico Personalizado -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary, #262238);">
                ¡Bienvenido/a, {{ $docente->nombre }}! <span aria-hidden="true">👨‍🏫</span>
            </h1>
            <p class="text-muted mb-0">
                Consulta tus asignaturas, administra los beneficios académicos y da seguimiento a tus grupos.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('docente.recompensas.index') }}" class="btn btn-portal-primary px-3 py-2 d-inline-flex align-items-center gap-2 rounded-pill shadow-xs">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Gestión de Recompensas</span>
            </a>
            <a href="{{ route('docente.academico.index') }}" class="btn btn-light border px-3 py-2 d-inline-flex align-items-center gap-2 rounded-pill">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                </svg>
                <span>Mis Asignaturas</span>
            </a>
        </div>
    </div>

    <!-- Principio de confidencialidad y ética institucional -->
    <div class="alert alert-light border border-primary-subtle shadow-xs rounded-4 p-3 mb-4 d-flex align-items-center gap-3" role="status">
        <span class="fs-4">🔒</span>
        <div class="small text-muted">
            <strong class="text-dark">Principio de confidencialidad y consentimiento explícito:</strong>
            El rol académico docente <strong>no otorga acceso automático</strong> a bitácoras privadas ni resultados clínicos de los estudiantes de acuerdo a la normativa institucional de UPEMOR.
        </div>
    </div>

    @if (($solicitudesInscripcionPendientes ?? 0) > 0)
        <div class="alert alert-warning border border-warning-subtle shadow-xs rounded-4 p-3 mb-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-person-lines-fill fs-3 text-warning"></i>
                <div>
                    <strong class="text-dark">Solicitudes de inscripción pendientes:</strong>
                    <div class="small text-muted">Tienes {{ $solicitudesInscripcionPendientes }} solicitud(es) de alumnos esperando tu confirmación en tus grupos de clase.</div>
                </div>
            </div>
            <a href="{{ route('docente.academico.index') }}" class="btn btn-warning text-dark btn-sm rounded-pill px-3 fw-semibold text-nowrap">
                Revisar grupos &rarr;
            </a>
        </div>
    @endif

    <!-- Indicadores Numéricos Superiores (Navegables a Vistas y Filtros Reales) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('docente.academico.index') }}" class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 text-decoration-none portal-stat-card transition-all" title="Ver catálogo de asignaturas asignadas">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="d-block text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Asignaturas</span>
                    <span class="text-primary small">&rarr;</span>
                </div>
                <span class="fs-3 fw-bold text-dark mt-1">{{ $totalAsignaturas }}</span>
                <span class="small text-muted" style="font-size: 0.75rem;">Materia(s) activa(s)</span>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('docente.academico.index') }}" class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 text-decoration-none portal-stat-card transition-all" title="Consultar grupos atendidos">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="d-block text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Grupos</span>
                    <span class="text-primary small">&rarr;</span>
                </div>
                <span class="fs-3 fw-bold text-dark mt-1">{{ $totalGrupos }}</span>
                <span class="small text-muted" style="font-size: 0.75rem;">Grupo(s) asignado(s)</span>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('docente.academico.index') }}" class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 text-decoration-none portal-stat-card transition-all" title="Ver estudiantes autorizados por grupo">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="d-block text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Estudiantes</span>
                    <span class="text-primary small">&rarr;</span>
                </div>
                <span class="fs-3 fw-bold text-dark mt-1">{{ $totalEstudiantesUnicos }}</span>
                <span class="small text-muted" style="font-size: 0.75rem;">Alumnos únicos</span>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('docente.recompensas.index', ['tab' => 'activadas']) }}" class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 text-decoration-none portal-stat-card transition-all" title="Gestionar recompensas habilitadas">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="d-block text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Beneficios</span>
                    <span class="text-primary small">&rarr;</span>
                </div>
                <span class="fs-3 fw-bold text-dark mt-1">{{ $totalRecompensasHabilitadas }}</span>
                <span class="small text-muted" style="font-size: 0.75rem;">Activas en grupos</span>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('docente.recompensas.index', ['tab' => 'solicitudes']) }}" class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 text-decoration-none portal-stat-card transition-all @if($beneficiosPendientesValidar > 0) border border-warning @endif" title="Validar canjes pendientes de alumnos">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="d-block text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Por Validar</span>
                    <span class="@if($beneficiosPendientesValidar > 0) text-warning @else text-primary @endif small">&rarr;</span>
                </div>
                <span class="fs-3 fw-bold @if($beneficiosPendientesValidar > 0) text-warning-emphasis @else text-dark @endif mt-1">
                    {{ $beneficiosPendientesValidar }}
                </span>
                <span class="small text-muted" style="font-size: 0.75rem;">Cupones de alumnos</span>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('docente.recompensas.index', ['tab' => 'propuestas']) }}" class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 text-decoration-none portal-stat-card transition-all" title="Ver estado de propuestas enviadas">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="d-block text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Propuestas</span>
                    <span class="text-primary small">&rarr;</span>
                </div>
                <span class="fs-3 fw-bold text-dark mt-1">{{ $propuestasPendientes }}</span>
                <span class="small text-muted" style="font-size: 0.75rem;">En revisión admin</span>
            </a>
        </div>
    </div>

    <!-- Sección Principal: Mis Asignaturas y Grupos -->
    <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="color: #7c3aed;">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                </svg>
                <span>Mis Asignaturas y Cargas Académicas</span>
            </h2>
            <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                {{ $asignaciones->count() }} asignación(es) total(es)
            </span>
        </div>

        @if ($asignaciones->isEmpty())
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <div class="p-3 rounded-circle bg-light d-inline-flex mb-3 mx-auto" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    </svg>
                </div>
                <h3 class="h6 fw-bold text-dark mb-1">Aún no cuentas con asignaciones académicas registradas</h3>
                <p class="text-muted small mb-0 max-w-md mx-auto" style="max-width: 460px;">
                    La coordinación académica de tu programa educativo vinculará las asignaturas, cuatrimestres y grupos correspondientes para este periodo.
                </p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($asignaciones as $asig)
                    @php
                        $inscritos = $conteoAlumnosPorGrupo[$asig->grupo_id] ?? 0;
                        $recompensasCount = $asig->recompensasActivadas->count();
                    @endphp
                    <div class="col-md-6 col-lg-6 col-xl-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white d-flex flex-column justify-content-between" style="border-left: 4px solid #7c3aed !important;">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small fw-semibold">
                                        {{ $asig->asignatura->clave }}
                                    </span>
                                    <span class="badge {{ $asig->periodo?->estado_calendario_badge ?? 'bg-light text-muted border' }} rounded-pill px-3 py-1 small">
                                        {{ $asig->periodo?->estado_calendario_label ?? $asig->periodo?->clave }}
                                    </span>
                                </div>

                                <h3 class="h5 fw-bold text-dark mb-1">{{ $asig->asignatura->nombre }}</h3>
                                <p class="text-muted small mb-3">
                                    {{ $asig->asignatura->programa?->nombre ?? 'Universidad Politécnica del Estado de Morelos' }}
                                </p>

                                <div class="p-3 rounded-3 bg-light border small mb-3">
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>Grupo y Nivel:</span>
                                        <span class="fw-semibold text-dark">
                                            {{ $asig->asignatura->cuatrimestre }}.º cuatrimestre &bull; Grupo {{ $asig->grupo->nombre }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>Turno:</span>
                                        <span class="text-dark">{{ ucfirst(strtolower($asig->grupo->turno ?? 'Matutino')) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>Estudiantes inscritos:</span>
                                        <span class="badge bg-white text-dark border px-2 py-1 fw-bold">
                                            {{ $inscritos }} alumno(s)
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted">
                                        <span>Beneficios activos:</span>
                                        <span class="badge @if($recompensasCount > 0) bg-success-subtle text-success border border-success-subtle @else bg-light text-muted border @endif px-2 py-1">
                                            {{ $recompensasCount }} habilitado(s)
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <a href="{{ route('docente.academico.grupo', $asig->asignacion_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    Ver alumnos
                                </a>
                                <a href="{{ route('docente.recompensas.create', $asig->asignacion_id) }}" class="btn btn-sm btn-primary rounded-pill px-3" style="background-color: #7c3aed; border-color: #7c3aed;">
                                    + Habilitar beneficio
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Sección Inferior: Actividad Reciente Relevante -->
    <div class="row g-4">
        <!-- Beneficios por Validar o Canjes Recientes -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h6 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="text-warning">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <span>Cupones de beneficios recientes</span>
                    </h2>
                    <a href="{{ route('docente.recompensas.index', ['tab' => 'solicitudes']) }}" class="small text-decoration-none fw-semibold" style="color: #7c3aed;">
                        Ver todos &rarr;
                    </a>
                </div>

                @if ($canjesRecientes->isEmpty())
                    <p class="text-muted small py-3 mb-0 text-center">No hay canjes recientes registrados en tus asignaturas.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-3">Estudiante</th>
                                    <th class="border-0">Beneficio y Materia</th>
                                    <th class="border-0 text-center">Código</th>
                                    <th class="border-0 text-end pe-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($canjesRecientes as $canje)
                                    <tr>
                                        <td class="ps-3 py-2 fw-semibold text-dark">
                                            {{ $canje->estudianteUsuario->nombre }} {{ $canje->estudianteUsuario->apellido_paterno }}
                                        </td>
                                        <td class="py-2">
                                            <div class="fw-semibold text-dark">{{ $canje->activacion->recompensaCatalogo->nombre }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $canje->activacion->asignacion->asignatura->nombre }} &bull; Grupo {{ $canje->activacion->asignacion->grupo->nombre }}</div>
                                        </td>
                                        <td class="py-2 text-center">
                                            <code class="fw-bold" style="color: #6d28d9;">{{ $canje->codigo_beneficio ?? 'S/C' }}</code>
                                        </td>
                                        <td class="pe-3 py-2 text-end">
                                            <span class="{{ $canje->estado_badge_class }}">
                                                {{ $canje->estado_label }}
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

        <!-- Propuestas de Beneficios del Docente -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h6 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <span>Mis propuestas resueltas</span>
                    </h2>
                    <a href="{{ route('docente.recompensas.index', ['tab' => 'propuestas']) }}" class="small text-decoration-none fw-semibold" style="color: #7c3aed;">
                        Gestionar &rarr;
                    </a>
                </div>

                @if ($propuestasResueltas->isEmpty())
                    <p class="text-muted small py-3 mb-0 text-center">No hay propuestas resueltas recientemente por administración.</p>
                @else
                    <div class="d-flex flex-column gap-2.5">
                        @foreach ($propuestasResueltas as $prop)
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <span class="fw-bold text-dark small">{{ $prop->nombre }}</span>
                                    @if ($prop->esPropuestaAprobada())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">Aprobada</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small">Rechazada</span>
                                    @endif
                                </div>
                                <p class="text-muted mb-1" style="font-size: 0.76rem;">{{ Str::limit($prop->descripcion, 75) }}</p>
                                @if ($prop->motivo_resolucion_admin)
                                    <div class="text-muted fst-italic" style="font-size: 0.72rem;">
                                        Nota de admin: "{{ $prop->motivo_resolucion_admin }}"
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
