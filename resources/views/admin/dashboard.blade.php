@extends('layouts.dashboard')

@section('title', 'Panel Administrativo - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-2 py-2">
    {{-- Encabezado de Bienvenida --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="portal-greeting-title mb-1">¡Bienvenido, {{ $admin->nombre }}! <span aria-hidden="true">👋</span></h1>
            <p class="portal-greeting-sub mb-0">Panel de control y administración integral de la plataforma Bienestar UPEMOR.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reportes.index') }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill d-inline-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Reportes Institucionales
            </a>
            <a href="{{ route('admin.respaldos.index') }}" class="btn btn-primary btn-sm px-3 rounded-pill d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-database me-1"></i> Respaldos DB
            </a>
        </div>
    </div>

    @include('components.portal-alerts')

    {{-- Fila 1: Indicadores Operativos Clave (Tarjetas de Acción con Cursor y Hover Sutil) --}}
    <div class="row g-3 g-xl-4 mb-4">
        {{-- 1. Solicitudes Pendientes --}}
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.solicitudes.index') }}" class="text-decoration-none d-block h-100" aria-label="Ver {{ $metricas['solicitudes_pendientes'] }} solicitudes pendientes de alumnos">
                <div class="metric-summary-card h-100 transition-all border-0 shadow-sm" style="cursor: pointer;">
                    <div class="metric-card-top">
                        <div class="metric-icon-circle metric-circle-orange" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div>
                            <span class="metric-label">Solicitudes Pendientes</span>
                            <div class="metric-value metric-value-orange">{{ $metricas['solicitudes_pendientes'] }}</div>
                        </div>
                    </div>
                    <div class="metric-link text-primary mt-2">
                        <span>Revisar solicitudes</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- 2. Alumnos Activos --}}
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.usuarios.index', ['rol_id' => 3, 'estado' => 'ACTIVO']) }}" class="text-decoration-none d-block h-100" aria-label="Ver {{ $metricas['alumnos_activos'] }} alumnos activos">
                <div class="metric-summary-card h-100 transition-all border-0 shadow-sm" style="cursor: pointer;">
                    <div class="metric-card-top">
                        <div class="metric-icon-circle metric-circle-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div>
                            <span class="metric-label">Alumnos activos</span>
                            <div class="metric-value metric-value-green">{{ $metricas['alumnos_activos'] }}</div>
                        </div>
                    </div>
                    <div class="metric-link text-primary mt-2">
                        <span>Ver directorio estudiantil</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- 3. Docentes Activos --}}
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.usuarios.index', ['rol_id' => 2, 'estado' => 'ACTIVO']) }}" class="text-decoration-none d-block h-100" aria-label="Ver {{ $metricas['docentes_activos'] }} docentes activos">
                <div class="metric-summary-card h-100 transition-all border-0 shadow-sm" style="cursor: pointer;">
                    <div class="metric-card-top">
                        <div class="metric-icon-circle metric-circle-purple" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div>
                            <span class="metric-label">Docentes Activos</span>
                            <div class="metric-value metric-value-purple">{{ $metricas['docentes_activos'] }}</div>
                        </div>
                    </div>
                    <div class="metric-link text-primary mt-2">
                        <span>Gestionar docentes</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- 4. Encuestas Publicadas --}}
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.encuestas.index', ['estado' => 'PUBLICADA']) }}" class="text-decoration-none d-block h-100" aria-label="Ver {{ $metricas['encuestas_publicadas'] }} encuestas publicadas">
                <div class="metric-summary-card h-100 transition-all border-0 shadow-sm" style="cursor: pointer;">
                    <div class="metric-card-top">
                        <div class="metric-icon-circle metric-circle-blue" aria-hidden="true">
                            <i class="bi bi-patch-question fs-4"></i>
                        </div>
                        <div>
                            <span class="metric-label">Encuestas Publicadas</span>
                            <div class="metric-value text-primary">{{ $metricas['encuestas_publicadas'] }}</div>
                        </div>
                    </div>
                    <div class="metric-link text-primary mt-2">
                        <span>Ver encuestas vigentes</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Fila 2: Indicadores Secundarios / Contextuales --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.carreras.index') }}" class="card border-0 shadow-sm p-3 bg-white text-decoration-none h-100 transition-all rounded-4" style="cursor: pointer;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Programas</span>
                    <i class="bi bi-mortarboard text-primary"></i>
                </div>
                <div class="fs-4 fw-bold text-dark">{{ $metricas['carreras_activas'] }}</div>
                <span class="text-muted small" style="font-size: 0.75rem;">Carreras e ingenierías</span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="{{ route('admin.periodos.index') }}" class="card border-0 shadow-sm p-3 bg-white text-decoration-none h-100 transition-all rounded-4" style="cursor: pointer;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Periodo Vigente</span>
                    <i class="bi bi-calendar-event text-success"></i>
                </div>
                <div class="fs-6 fw-bold text-dark text-truncate">{{ $metricas['periodo_vigente']?->clave ?? 'Sin periodo activo' }}</div>
                <span class="text-muted small" style="font-size: 0.75rem;">{{ $metricas['periodo_vigente']?->nombre ?? 'Configurar en periodos' }}</span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="{{ route('admin.recompensas.catalogo') }}" class="card border-0 shadow-sm p-3 bg-white text-decoration-none h-100 transition-all rounded-4" style="cursor: pointer;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Recompensas</span>
                    <i class="bi bi-gift text-warning"></i>
                </div>
                <div class="fs-4 fw-bold text-dark">{{ $metricas['recompensas_activas'] }}</div>
                <span class="text-muted small" style="font-size: 0.75rem;">Premios en catálogo</span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="{{ route('admin.respaldos.index') }}" class="card border-0 shadow-sm p-3 bg-white text-decoration-none h-100 transition-all rounded-4" style="cursor: pointer;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Respaldos BD</span>
                    <i class="bi bi-shield-check text-info"></i>
                </div>
                <div class="fs-4 fw-bold text-dark">{{ $metricas['total_respaldos'] }}</div>
                <span class="text-muted small" style="font-size: 0.75rem;">Copias de seguridad</span>
            </a>
        </div>
    </div>

    {{-- Sección de Operación Institucional Directa --}}
    <div class="row g-4 mb-4">
        {{-- Columna Izquierda: Atención Prioritaria de Solicitudes --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-person-check-fill text-primary"></i>
                        <span>Solicitudes de Ingreso Estudiantil</span>
                    </h2>
                    <a href="{{ route('admin.solicitudes.index') }}" class="small text-primary text-decoration-none fw-semibold">
                        Ver todas &rarr;
                    </a>
                </div>

                <p class="text-muted small mb-3">
                    Revisa las solicitudes de nuevo registro de los estudiantes. Verifica la matrícula institucional, carrera asignada y confirma el alta de cuenta.
                </p>

                <div class="d-flex align-items-center gap-3 text-muted small mb-3">
                    <span>Estado solicitudes: <strong class="text-warning">Pendientes ({{ $metricas['solicitudes_pendientes'] }})</strong></span>
                    <span>&bull;</span>
                    <span><a href="{{ route('admin.solicitudes.index') }}" class="text-decoration-none text-muted">Rechazadas ({{ $metricas['solicitudes_rechazadas'] }})</a></span>
                </div>

                @if($metricas['solicitudes_pendientes'] > 0)
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-4 bg-warning bg-opacity-10 border border-warning-subtle mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-warning text-dark p-2 rounded-circle" aria-hidden="true">
                                <i class="bi bi-hourglass-split fs-5"></i>
                            </span>
                            <div>
                                <div class="fw-bold text-dark">Solicitudes pendientes de revisión</div>
                                <div class="text-muted small">{{ $metricas['solicitudes_pendientes'] }} alumno(s) esperando autorización de acceso</div>
                            </div>
                        </div>
                        <a href="{{ route('admin.solicitudes.index') }}" class="btn btn-warning text-dark btn-sm px-3 fw-bold rounded-pill">
                            Atender Ahora
                        </a>
                    </div>
                @else
                    <div class="p-3 rounded-4 bg-success bg-opacity-10 border border-success-subtle d-flex align-items-center gap-3 mb-3">
                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        <div>
                            <div class="fw-bold text-dark">Bandeja de solicitudes al día</div>
                            <div class="text-muted small">No hay solicitudes de alumnos pendientes en este momento.</div>
                        </div>
                    </div>
                @endif

                @if($metricas['solicitudes_catedra_pendientes'] > 0)
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-4 bg-primary bg-opacity-10 border border-primary-subtle mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-primary text-white p-2 rounded-circle" aria-hidden="true">
                                <i class="bi bi-person-workspace fs-5"></i>
                            </span>
                            <div>
                                <div class="fw-bold text-dark">Cátedras docentes por autorizar</div>
                                <div class="text-muted small">{{ $metricas['solicitudes_catedra_pendientes'] }} solicitud(es) de asignación docente esperando resolución</div>
                            </div>
                        </div>
                        <a href="{{ route('admin.docente-asignaciones.index') }}" class="btn btn-primary btn-sm px-3 fw-bold rounded-pill">
                            Revisar
                        </a>
                    </div>
                @endif

                <div class="row g-2 mt-auto pt-2">
                    <div class="col-sm-6">
                        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-outline-secondary btn-sm w-100 rounded-3 text-start py-2">
                            <i class="bi bi-person-plus me-1 text-primary"></i> Alta manual de usuario
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary btn-sm w-100 rounded-3 text-start py-2">
                            <i class="bi bi-people me-1 text-primary"></i> Directorio institucional
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna Derecha: Accesos a Módulos Administrativos Activos --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-grid-fill text-primary"></i>
                        <span>Módulos de Gestión</span>
                    </h2>
                    <span class="badge bg-light text-muted border px-2 py-1 rounded-pill small">Operativos</span>
                </div>

                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('admin.docente-asignaciones.index') }}" class="p-3 rounded-3 bg-light text-decoration-none d-flex align-items-center justify-content-between transition-all hover-shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-person-workspace fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small d-flex align-items-center gap-2">
                                    <span>Cátedras Docentes</span>
                                    @if($metricas['solicitudes_catedra_pendientes'] > 0)
                                        <span class="badge bg-warning text-dark rounded-pill">{{ $metricas['solicitudes_catedra_pendientes'] }} pendiente{{ $metricas['solicitudes_catedra_pendientes'] > 1 ? 's' : '' }}</span>
                                    @endif
                                </div>
                                <div class="text-muted small" style="font-size: 0.78rem;">Asignación de asignaturas y grupos a profesores</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <a href="{{ route('admin.encuestas.index') }}" class="p-3 rounded-3 bg-light text-decoration-none d-flex align-items-center justify-content-between transition-all hover-shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-patch-question fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small">Encuestas de Satisfacción</div>
                                <div class="text-muted small" style="font-size: 0.78rem;">Instrumentos de calidad y post-cita</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <a href="{{ route('admin.reportes.index') }}" class="p-3 rounded-3 bg-light text-decoration-none d-flex align-items-center justify-content-between transition-all hover-shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 bg-success bg-opacity-10 text-success">
                                <i class="bi bi-file-earmark-bar-graph fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small">Reportes Institucionales</div>
                                <div class="text-muted small" style="font-size: 0.78rem;">6 reportes oficiales con PDF y Excel</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <a href="{{ route('admin.respaldos.index') }}" class="p-3 rounded-3 bg-light text-decoration-none d-flex align-items-center justify-content-between transition-all hover-shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 bg-info bg-opacity-10 text-info">
                                <i class="bi bi-database-down fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small">Respaldos de Base de Datos</div>
                                <div class="text-muted small" style="font-size: 0.78rem;">Generación segura y descarga SQL</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <a href="{{ route('admin.carreras.index') }}" class="p-3 rounded-3 bg-light text-decoration-none d-flex align-items-center justify-content-between transition-all hover-shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 bg-secondary bg-opacity-10 text-secondary">
                                <i class="bi bi-mortarboard fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small">Carreras y Planes de Estudio</div>
                                <div class="text-muted small" style="font-size: 0.78rem;">Planes vigentes y asignaturas</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
