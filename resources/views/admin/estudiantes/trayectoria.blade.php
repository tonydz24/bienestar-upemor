@extends('layouts.dashboard')

@section('title', 'Trayectoria Académica - ' . $usuario->nombre . ' ' . $usuario->apellido_paterno . ' - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0" style="max-width: 1100px;">
    <!-- Breadcrumb y navegación -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-link text-muted p-0 text-decoration-none d-inline-flex align-items-center gap-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>Volver a Usuarios</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.usuarios.edit', $usuario->usuario_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Editar Perfil
            </a>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCambiarPlan">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                    <path d="M16 3h5v5"></path>
                    <path d="M4 20L21 3"></path>
                    <path d="M21 16v5h-5"></path>
                    <path d="M15 15l6 6"></path>
                    <path d="M4 4l5 5"></path>
                </svg>
                Cambiar plan de estudios
            </button>
        </div>
    </div>

    <!-- Alertas del sistema -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <svg width="20" height="20" fill="currentColor" class="bi bi-check-circle-fill text-success flex-shrink-0" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <svg width="20" height="20" fill="currentColor" class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
            </svg>
            <div>
                {{ session('error') ?? 'Se detectaron errores en el formulario:' }}
                @if($errors->any())
                    <ul class="mb-0 mt-1 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 1. Encabezado de Identidad y Expediente -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="border-left: 4px solid #004d35 !important;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 58px; height: 58px; background: linear-gradient(135deg, #004d35 0%, #00875a 100%); font-size: 1.3rem;">
                        {{ strtoupper(substr($usuario->nombre, 0, 1) . substr($usuario->apellido_paterno, 0, 1)) }}
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h4 class="fw-bold text-dark mb-0">{{ $usuario->nombre }} {{ $usuario->apellido_paterno }} {{ $usuario->apellido_materno }}</h4>
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                                {{ $usuario->estado }}
                            </span>
                        </div>
                        <div class="text-muted small mt-1 d-flex align-items-center gap-3 flex-wrap">
                            <span><strong class="text-dark">Matrícula:</strong> <code class="text-primary fw-bold">{{ $estudiante->matricula }}</code></span>
                            <span>&bull;</span>
                            <span><strong class="text-dark">Correo:</strong> {{ $usuario->email }}</span>
                            <span>&bull;</span>
                            <span><strong class="text-dark">Programa:</strong> {{ $estudiante->programa?->nombre ?? 'Sin programa' }} ({{ $estudiante->programa?->clave }})</span>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="small text-muted d-block">Puntos de Bienestar</span>
                    <span class="fs-5 fw-bold font-monospace text-success">{{ number_format($estudiante->saldo_puntos) }} pts</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Tarjetas de Resumen Curricular -->
    <div class="row g-3 mb-4">
        <!-- Tarjeta Plan de Estudios -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-semibold text-uppercase">Plan de Estudios</span>
                    <span class="badge rounded-pill {{ ($estudiante->plan_estudio_asignado?->es_vigente) ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                        {{ $estudiante->plan_estudio_asignado?->estado ?? 'NO ASIGNADO' }}
                    </span>
                </div>
                <div class="fw-bold fs-5 text-dark mb-1">{{ $estudiante->plan_estudio_asignado?->codigo ?? 'Sin código' }}</div>
                <div class="small text-muted text-truncate" title="{{ $estudiante->plan_estudio_asignado?->nombre }}">
                    {{ $estudiante->plan_estudio_asignado?->nombre ?? 'Asignación pendiente' }}
                </div>
                <div class="mt-2 pt-2 border-top small text-muted d-flex justify-content-between">
                    <span>Versión: <strong>{{ $estudiante->plan_estudio_asignado?->version ?? 'Oficial' }}</strong></span>
                    <span>Aprobado: <strong>{{ $estudiante->plan_estudio_asignado?->anio_aprobacion ?? '—' }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Tarjeta Generación / Cohorte -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-semibold text-uppercase">Generación / Cohorte</span>
                    <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle">Ingreso</span>
                </div>
                <div class="fw-bold fs-5 text-dark mb-1">{{ $estudiante->generacion }}</div>
                <div class="small text-muted">
                    Año de ingreso estimado: <strong>{{ $estudiante->resolverAnioIngreso() }}</strong>
                </div>
                <div class="mt-2 pt-2 border-top small text-muted">
                    Fecha registro: {{ $estudiante->created_at?->format('d/m/Y') ?? '—' }}
                </div>
            </div>
        </div>

        <!-- Tarjeta Ubicación Escolar -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-semibold text-uppercase">Ubicación Escolar</span>
                    <span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle">Actual</span>
                </div>
                <div class="fw-bold fs-5 text-dark mb-1">Cuatrimestre {{ $estudiante->cuatrimestre }}°</div>
                <div class="small text-muted">
                    Grupo asignado: <strong class="font-monospace text-dark">{{ $estudiante->grupo ?? 'Sin grupo' }}</strong>
                </div>
                <div class="mt-2 pt-2 border-top small text-muted">
                    Periodo activo: <strong>2026-3</strong>
                </div>
            </div>
        </div>

        <!-- Tarjeta Diagnóstico de Coherencia -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 {{ $diagnosticoPlan['es_ambiguo'] ? 'bg-warning-subtle border border-warning' : 'bg-white' }}">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-semibold text-uppercase">Coherencia de Plan</span>
                    @if($diagnosticoPlan['es_ambiguo'])
                        <span class="badge rounded-pill bg-warning text-dark">Ambigüedad</span>
                    @else
                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Coherente</span>
                    @endif
                </div>
                <div class="small text-dark fw-semibold mb-1" style="font-size: 0.82rem;">
                    {{ $diagnosticoPlan['mensaje'] }}
                </div>
                <div class="mt-auto pt-2 border-top small text-muted" style="font-size: 0.74rem;">
                    Última comprobación: {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Asignaturas Inscritas (Actuales e Históricas) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0">Asignaturas Inscritas y Antecedentes Académicos</h5>
                <p class="small text-muted mb-0">Relación formal de materias cursadas y en curso. Los cambios de plan de estudios preservan intactos todos los antecedentes.</p>
            </div>
            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill">
                {{ $asignaturasInscritas->count() }} Asignaturas registradas
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="ps-4 py-3" style="width: 25%;">Asignatura</th>
                            <th style="width: 15%;">Periodo</th>
                            <th style="width: 15%;">Grupo</th>
                            <th style="width: 25%;">Docente Titular</th>
                            <th style="width: 10%; text-align: center;">Estado</th>
                            <th class="pe-4 py-3 text-end" style="width: 10%;">Fecha Alta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($asignaturasInscritas as $ai)
                            <tr>
                                <td class="ps-4 fw-semibold text-dark">
                                    {{ $ai->asignacion?->asignatura?->nombre ?? 'Asignatura no especificada' }}
                                    <div class="small text-muted font-monospace">{{ $ai->asignacion?->asignatura?->codigo ?? '' }}</div>
                                </td>
                                <td class="small text-muted">
                                    {{ $ai->asignacion?->periodo?->nombre ?? 'Periodo general' }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $ai->asignacion?->grupo?->codigo ?? $ai->grupo_id ?? 'A' }}
                                    </span>
                                </td>
                                <td class="small text-dark">
                                    {{ $ai->asignacion?->docente?->user?->nombre }} {{ $ai->asignacion?->docente?->user?->apellido_paterno }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $badgeEstado = match(strtoupper($ai->estado)) {
                                            'APROBADA', 'ACTIVO' => 'bg-success-subtle text-success border-success-subtle',
                                            'PENDIENTE' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                            'RECHAZADA', 'BAJA' => 'bg-danger-subtle text-danger border-danger-subtle',
                                            default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                        };
                                    @endphp
                                    <span class="badge rounded-pill border px-2 py-1 {{ $badgeEstado }}">
                                        {{ $ai->estado }}
                                    </span>
                                </td>
                                <td class="pe-4 text-end small text-muted">
                                    {{ $ai->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="d-block mx-auto mb-2 text-muted">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    </svg>
                                    No se registran asignaturas docentes inscritas para este estudiante.<br>
                                    <em>La asignación de plan no inscribe automáticamente al estudiante en cátedras.</em>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Historial de Cambios de Plan (Auditoría Administrativa) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold text-dark mb-0">Bitácora de Auditoría de Cambios de Plan</h5>
            <p class="small text-muted mb-0">Registro inmutable de transiciones curriculares, administradores responsables y justificaciones institucionales.</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="ps-4 py-3" style="width: 18%;">Fecha y Hora</th>
                            <th style="width: 18%;">Plan Anterior</th>
                            <th style="width: 18%;">Plan Nuevo</th>
                            <th style="width: 16%;">Autorizado Por</th>
                            <th style="width: 20%;">Motivo / Justificación</th>
                            <th class="pe-4 py-3 text-end" style="width: 10%;">Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historialCambios as $hc)
                            <tr>
                                <td class="ps-4 small text-muted font-monospace">
                                    {{ $hc->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        {{ $hc->planAnterior?->codigo ?? 'Asignación Inicial' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                        {{ $hc->planNuevo?->codigo ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="small text-dark">
                                    {{ $hc->administrador?->nombre }} {{ $hc->administrador?->apellido_paterno }}
                                </td>
                                <td class="small text-muted text-break">
                                    {{ $hc->motivo }}
                                </td>
                                <td class="pe-4 text-end">
                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        {{ $hc->resultado }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    No se han registrado modificaciones administrativas al plan de estudios de este estudiante.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Institucional: Cambiar Plan de Estudios -->
<div class="modal fade" id="modalCambiarPlan" tabindex="-1" aria-labelledby="modalCambiarPlanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 3h5v5"></path>
                            <path d="M4 20L21 3"></path>
                            <path d="M21 16v5h-5"></path>
                            <path d="M15 15l6 6"></path>
                            <path d="M4 4l5 5"></path>
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalCambiarPlanLabel">Cambiar Plan de Estudios Curricular</h5>
                        <div class="small text-muted">Operación sujeta a auditoría institucional y preservación de historial</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('admin.estudiantes.cambiar-plan', $usuario->usuario_id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <!-- Resumen del Alumno y Plan Actual -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="small text-muted d-block mb-1 text-uppercase fw-semibold">Estudiante y Programa:</span>
                                <div class="fw-bold text-dark">{{ $usuario->nombre }} {{ $usuario->apellido_paterno }} {{ $usuario->apellido_materno }}</div>
                                <div class="small text-muted">{{ $estudiante->programa?->nombre }} ({{ $estudiante->matricula }})</div>
                                <div class="small text-primary mt-1">Generación: <strong>{{ $estudiante->generacion }}</strong> &bull; Cohorte: <strong>{{ $estudiante->resolverAnioIngreso() }}</strong></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="small text-muted d-block mb-1 text-uppercase fw-semibold">Plan de Estudios Actual:</span>
                                <div class="fw-bold text-dark">{{ $estudiante->plan_estudio_asignado?->codigo ?? 'Sin Asignación' }}</div>
                                <div class="small text-muted">{{ $estudiante->plan_estudio_asignado?->nombre ?? 'Plan pendiente' }}</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        {{ $estudiante->plan_estudio_asignado?->estado ?? 'VIGENTE' }}
                                    </span>
                                    <span class="small text-muted">Aprobación: {{ $estudiante->plan_estudio_asignado?->anio_aprobacion ?? '2024' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(($planesAlternativos ?? collect())->isEmpty())
                        <!-- Aviso Informativo: Programa con Plan Único -->
                        <div class="alert alert-info border-0 rounded-4 shadow-xs p-3 mb-3">
                            <div class="d-flex gap-2 align-items-start">
                                <svg width="22" height="22" fill="currentColor" class="bi bi-info-circle-fill text-primary flex-shrink-0 mt-0.5" viewBox="0 0 16 16">
                                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                </svg>
                                <div>
                                    <div class="fw-bold text-dark mb-1">Catálogo de Plan Único para este Programa Educativo</div>
                                    <div class="small text-muted">
                                        Actualmente no existen otros planes de estudio registrados o disponibles para el programa <strong>{{ $estudiante->programa?->nombre }}</strong>. El estudiante ya se encuentra adscrito al único plan curricular oficial (<strong>{{ $estudiante->plan_estudio_asignado?->codigo }}</strong>).
                                    </div>
                                    <div class="small text-muted mt-2">
                                        <em>Para registrar nuevos planes curriculares o reformas de plan, diríjase al módulo de Planes de Estudio.</em>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Selector de Plan Propuesto Clasificado -->
                    <div class="mb-3">
                        <label for="modal_plan_id" class="form-label small fw-semibold text-dark">
                            Seleccionar Plan de Estudios Destino <span class="text-danger">*</span>
                        </label>
                        <select name="plan_id" id="modal_plan_id" class="form-select @error('plan_id') is-invalid @enderror" required @disabled(($planesAlternativos ?? collect())->isEmpty())>
                            <option value="">-- Seleccione un plan de estudios alternativo de la carrera --</option>
                            @foreach($planesCarrera as $plan)
                                <option value="{{ $plan->plan_id }}"
                                    data-codigo="{{ $plan->codigo }}"
                                    data-nombre="{{ $plan->nombre }}"
                                    data-estado="{{ $plan->estado }}"
                                    data-clasificacion="{{ $plan->clasificacion }}"
                                    data-anio="{{ $plan->anio_aprobacion }}"
                                    data-advertencias="{{ implode('; ', $plan->evaluacion_compatibilidad['advertencias'] ?? []) }}"
                                    @selected(old('plan_id') == $plan->plan_id)
                                    @disabled($plan->es_actual)>
                                    {{ $plan->codigo }} &mdash; {{ $plan->nombre }} [{{ $plan->clasificacion }}]
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">
                            Se presentan todos los planes registrados para la carrera, clasificando su estatus institucional. El plan actual se encuentra bloqueado para evitar reasignaciones redundantes.
                        </div>
                    </div>

                    <!-- Cuadro Comparativo Dinámico (Plan Actual vs Plan Destino) -->
                    <div id="contenedorComparativoPlan" class="card border rounded-3 p-3 mb-3 bg-light d-none">
                        <span class="small fw-bold text-dark text-uppercase d-block mb-2">Detalle Comparativo de la Transición Curricular</span>
                        <div class="row g-2">
                            <div class="col-12 col-md-6 border-end">
                                <span class="small text-muted d-block">Plan Origen (Actual):</span>
                                <div class="fw-bold text-dark small" id="cmpPlanActualCodigo">{{ $estudiante->plan_estudio_asignado?->codigo }}</div>
                                <div class="small text-muted" id="cmpPlanActualNombre">{{ $estudiante->plan_estudio_asignado?->nombre }}</div>
                                <div class="small text-muted mt-1">Estado: <span class="badge bg-secondary-subtle text-secondary">{{ $estudiante->plan_estudio_asignado?->estado }}</span></div>
                            </div>
                            <div class="col-12 col-md-6 ps-md-3">
                                <span class="small text-muted d-block">Plan Destino (Propuesto):</span>
                                <div class="fw-bold text-primary small" id="cmpPlanDestinoCodigo">—</div>
                                <div class="small text-muted" id="cmpPlanDestinoNombre">—</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-info-subtle text-info-emphasis" id="cmpPlanDestinoEstado">—</span>
                                    <span class="small text-muted" id="cmpPlanDestinoClasificacion">—</span>
                                </div>
                            </div>
                        </div>
                        <div id="cmpAdvertenciasBox" class="alert alert-warning border-0 rounded-3 p-2 small mt-2 mb-0 d-none">
                            <strong>Observación de compatibilidad:</strong> <span id="cmpAdvertenciasTexto"></span>
                        </div>
                    </div>

                    <!-- Motivo Obligatorio del Cambio con Contador -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="modal_motivo" class="form-label small fw-semibold text-dark mb-0">
                                Motivo o Justificación Académica del Cambio <span class="text-danger">*</span>
                            </label>
                            <span class="small text-muted" id="contadorCaracteresMotivo">0 / 10 caracteres mín.</span>
                        </div>
                        <textarea name="motivo" id="modal_motivo" rows="3" class="form-control @error('motivo') is-invalid @enderror" placeholder="Describa detalladamente el motivo académico, dictamen técnico o resolución directiva que autoriza este cambio curricular..." required minlength="10" maxlength="1000" @disabled(($planesAlternativos ?? collect())->isEmpty())>{{ old('motivo') }}</textarea>
                        <div class="form-text small text-muted">Mínimo 10 caracteres. Esta justificación quedará registrada permanentemente en la bitácora inmutable de auditoría.</div>
                    </div>

                    <!-- Advertencia de Protección de Historial -->
                    <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle mb-3">
                        <div class="d-flex gap-2">
                            <svg width="20" height="20" fill="currentColor" class="bi bi-shield-check text-primary flex-shrink-0 mt-0.5" viewBox="0 0 16 16">
                                <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
                                <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                            </svg>
                            <div class="small text-dark">
                                <strong>Garantía de Integridad Académica:</strong> El cambio de plan actualiza la trayectoria curricular futura del estudiante sin destruir asignaturas previas, sin borrar calificaciones históricas y sin modificar saldos de puntos o recompensas.
                            </div>
                        </div>
                    </div>

                    <!-- Confirmación Expresa -->
                    <div class="form-check">
                        <input class="form-check-input @error('confirmacion') is-invalid @enderror" type="checkbox" name="confirmacion" id="modal_confirmacion" value="1" required @disabled(($planesAlternativos ?? collect())->isEmpty())>
                        <label class="form-check-label small fw-semibold text-dark" for="modal_confirmacion">
                            Confirmo que esta operación cuenta con autorización institucional y que he verificado la compatibilidad del plan destino.
                        </label>
                        @error('confirmacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="modal-footer bg-light border-top py-2 px-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    @if(($planesAlternativos ?? collect())->isNotEmpty())
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm" id="btnConfirmarCambioPlan">
                            Confirmar y Guardar Cambio de Plan
                        </button>
                    @else
                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4 disabled" disabled title="No hay planes alternativos para este programa">
                            Sin Planes Alternativos Disponibles
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectPlan = document.getElementById('modal_plan_id');
    const cajaComparativo = document.getElementById('contenedorComparativoPlan');
    const cmpCodigo = document.getElementById('cmpPlanDestinoCodigo');
    const cmpNombre = document.getElementById('cmpPlanDestinoNombre');
    const cmpEstado = document.getElementById('cmpPlanDestinoEstado');
    const cmpClasif = document.getElementById('cmpPlanDestinoClasificacion');
    const cmpAdvBox = document.getElementById('cmpAdvertenciasBox');
    const cmpAdvTexto = document.getElementById('cmpAdvertenciasTexto');

    const textareaMotivo = document.getElementById('modal_motivo');
    const contadorMotivo = document.getElementById('contadorCaracteresMotivo');

    if (textareaMotivo && contadorMotivo) {
        const updateContador = () => {
            const len = textareaMotivo.value.trim().length;
            contadorMotivo.textContent = `${len} / 10 caracteres mín.`;
            if (len >= 10) {
                contadorMotivo.classList.remove('text-muted', 'text-danger');
                contadorMotivo.classList.add('text-success');
            } else {
                contadorMotivo.classList.remove('text-success');
                contadorMotivo.classList.add('text-muted');
            }
        };
        textareaMotivo.addEventListener('input', updateContador);
        updateContador();
    }

    if (selectPlan) {
        selectPlan.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) {
                if (cajaComparativo) cajaComparativo.classList.add('d-none');
                return;
            }

            if (cajaComparativo) {
                cajaComparativo.classList.remove('d-none');
                if (cmpCodigo) cmpCodigo.textContent = opt.dataset.codigo || '—';
                if (cmpNombre) cmpNombre.textContent = opt.dataset.nombre || '—';
                if (cmpEstado) cmpEstado.textContent = opt.dataset.estado || '—';
                if (cmpClasif) cmpClasif.textContent = opt.dataset.clasificacion || '—';

                const adv = opt.dataset.advertencias;
                if (adv && adv.trim() !== '') {
                    if (cmpAdvBox) cmpAdvBox.classList.remove('d-none');
                    if (cmpAdvTexto) cmpAdvTexto.textContent = adv;
                } else {
                    if (cmpAdvBox) cmpAdvBox.classList.add('d-none');
                }
            }
        });

        if (selectPlan.value) {
            selectPlan.dispatchEvent(new Event('change'));
        }
    }
});
</script>
            </form>
        </div>
    </div>
</div>
@endsection

