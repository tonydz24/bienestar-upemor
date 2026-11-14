@extends('layouts.dashboard')

@section('title', 'Gestión de Usuarios - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado institucional -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Gestión Institucional de Usuarios</h1>
            <p class="text-muted mb-0">Administración integral de cuentas de alumnos, docentes, orientadores y personal de administración.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 rounded-3" data-bs-toggle="collapse" data-bs-target="#guiaEstadosCollapse" aria-expanded="false" aria-controls="guiaEstadosCollapse">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span>Guía de Estados</span>
            </button>
            <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm rounded-3">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Nuevo Usuario</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-auto-dismiss mb-4 shadow-sm" role="alert" data-auto-dismiss="5000">
            <strong>Éxito:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <strong>Atención:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    {{-- Notificación destacada de contraseña restablecida por el administrador --}}
    @if (session('success_password_reset'))
        @php $resetData = session('success_password_reset'); @endphp
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-warning" style="background: #fffbeb;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-2 rounded-circle bg-warning bg-opacity-25 text-warning-emphasis">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold text-dark mb-1">Contraseña restablecida con éxito para {{ $resetData['nombre'] }}</h6>
                        <p class="small text-muted mb-2">Proporciona esta nueva credencial temporal de forma confidencial al usuario:</p>
                        <div class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded-3 bg-white border border-warning-subtle font-monospace text-dark fw-bold">
                            <span>{{ $resetData['password'] }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary p-1 px-2" onclick="navigator.clipboard.writeText('{{ $resetData['password'] }}'); this.textContent = '✓ ¡Copiada!'; setTimeout(() => this.textContent = 'Copiar', 2000);" title="Copiar contraseña">
                                Copiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Guía explicativa colapsable de los 5 estados de cuenta --}}
    <div class="collapse mb-4" id="guiaEstadosCollapse">
        <div class="card border-0 shadow-sm rounded-4 bg-light">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>Criterios Institucionales para Estados de Cuenta</span>
                </h6>
                <div class="row g-3">
                    <div class="col-12 col-md-4 col-xl-2">
                        <div class="p-3 bg-white rounded-3 border h-100">
                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1 mb-2">ACTIVO</span>
                            <p class="small text-muted mb-0" style="font-size: 0.78rem;">
                                Cuenta plenamente operativa. Permite inicio de sesión regular, acceso a evaluaciones, bitácora emocional, citas o portal docente según su rol.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2">
                        <div class="p-3 bg-white rounded-3 border h-100">
                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 mb-2">PENDIENTE</span>
                            <p class="small text-muted mb-0" style="font-size: 0.78rem;">
                                Registro creado o auto-registrado en espera de validación institucional o asignación de grupo/perfil antes de habilitar su acceso.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2">
                        <div class="p-3 bg-white rounded-3 border h-100">
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2 py-1 mb-2">INACTIVO</span>
                            <p class="small text-muted mb-0" style="font-size: 0.78rem;">
                                Suspensión administrativa temporal (egreso, baja temporal, fin de ciclo). Se preserva íntegramente todo su expediente académico y clínico.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2">
                        <div class="p-3 bg-white rounded-3 border h-100">
                            <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle px-2 py-1 mb-2">BLOQUEADO</span>
                            <p class="small text-muted mb-0" style="font-size: 0.78rem;">
                                Bloqueo preventivo de seguridad debido a anomalías de acceso, múltiples contraseñas fallidas o instrucción directa de administración.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2">
                        <div class="p-3 bg-white rounded-3 border h-100">
                            <span class="badge rounded-pill bg-dark-subtle text-dark-emphasis border border-dark-subtle px-2 py-1 mb-2">RECHAZADO</span>
                            <p class="small text-muted mb-0" style="font-size: 0.78rem;">
                                Solicitud de registro denegada por inconsistencia de datos o incumplimiento de requisitos institucionales.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2">
                        <div class="p-3 bg-white rounded-3 border h-100 border-primary-subtle">
                            <div class="fw-semibold text-primary mb-1 small" style="font-size: 0.8rem;">Trazabilidad Clínica</div>
                            <p class="small text-muted mb-0" style="font-size: 0.75rem;">
                                Las cuentas con registros clínicos o académicos no se pueden eliminar físicamente; deben cambiarse a INACTIVO o BLOQUEADO.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de métricas rápidas con función interactiva de filtrado -->
    @php
        $esTotalActivo = ($rolFiltro === 'TODOS' && $estadoFiltro === 'TODOS');
        $esAlumnoActivo = ($rolFiltro === 'Alumno');
        $esDocenteActivo = ($rolFiltro === 'Docente');
        $esOrientadorActivo = ($rolFiltro === 'Orientador');
        $esAdminActivo = ($rolFiltro === 'Administrador');
        $esPendienteActivo = ($estadoFiltro === 'PENDIENTE');
    @endphp
    <div class="row g-3 mb-4">
        <!-- 1. Total Cuentas -->
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('admin.usuarios.index', ['rol' => 'TODOS', 'estado' => 'TODOS']) }}"
               class="card border-0 shadow-sm rounded-4 h-100 p-3 text-decoration-none portal-stat-card {{ $esTotalActivo ? 'active-filter' : '' }}"
               title="Ver todas las cuentas registradas">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="text-muted small fw-semibold">Total Cuentas</span>
                    @if($esTotalActivo)
                        <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size: 0.65rem;">Filtro Activo</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalUsuarios) }}</div>
                <div class="small text-muted mt-auto" style="font-size: 0.72rem;">Registradas en base &rarr;</div>
            </a>
        </div>

        <!-- 2. Alumnos -->
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('admin.usuarios.index', ['rol' => 'Alumno', 'estado' => $estadoFiltro !== 'TODOS' ? $estadoFiltro : 'TODOS']) }}"
               class="card border-0 shadow-sm rounded-4 h-100 p-3 text-decoration-none portal-stat-card {{ $esAlumnoActivo ? 'active-filter' : '' }}"
               style="border-left: 4px solid #6366f1 !important;"
               title="Filtrar alumnos">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="text-muted small fw-semibold">Alumnos</span>
                    @if($esAlumnoActivo)
                        <span class="badge rounded-pill px-2 py-1 text-white" style="background: #6366f1; font-size: 0.65rem;">Filtro Activo</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalAlumnos) }}</div>
                <div class="small text-muted mt-auto" style="font-size: 0.72rem;">Estudiantes &rarr;</div>
            </a>
        </div>

        <!-- 3. Docentes -->
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('admin.usuarios.index', ['rol' => 'Docente', 'estado' => $estadoFiltro !== 'TODOS' ? $estadoFiltro : 'TODOS']) }}"
               class="card border-0 shadow-sm rounded-4 h-100 p-3 text-decoration-none portal-stat-card {{ $esDocenteActivo ? 'active-filter' : '' }}"
               style="border-left: 4px solid #0284c7 !important;"
               title="Filtrar docentes">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="text-muted small fw-semibold">Docentes</span>
                    @if($esDocenteActivo)
                        <span class="badge rounded-pill px-2 py-1 text-white" style="background: #0284c7; font-size: 0.65rem;">Filtro Activo</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalDocentes) }}</div>
                <div class="small text-muted mt-auto" style="font-size: 0.72rem;">Académicos &rarr;</div>
            </a>
        </div>

        <!-- 4. Orientadores -->
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('admin.usuarios.index', ['rol' => 'Orientador', 'estado' => $estadoFiltro !== 'TODOS' ? $estadoFiltro : 'TODOS']) }}"
               class="card border-0 shadow-sm rounded-4 h-100 p-3 text-decoration-none portal-stat-card {{ $esOrientadorActivo ? 'active-filter' : '' }}"
               style="border-left: 4px solid #0d9488 !important;"
               title="Filtrar orientadores">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="text-muted small fw-semibold">Orientadores</span>
                    @if($esOrientadorActivo)
                        <span class="badge rounded-pill px-2 py-1 text-white" style="background: #0d9488; font-size: 0.65rem;">Filtro Activo</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalOrientadores) }}</div>
                <div class="small text-muted mt-auto" style="font-size: 0.72rem;">Acompañamiento &rarr;</div>
            </a>
        </div>

        <!-- 5. Administradores -->
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('admin.usuarios.index', ['rol' => 'Administrador', 'estado' => $estadoFiltro !== 'TODOS' ? $estadoFiltro : 'TODOS']) }}"
               class="card border-0 shadow-sm rounded-4 h-100 p-3 text-decoration-none portal-stat-card {{ $esAdminActivo ? 'active-filter' : '' }}"
               style="border-left: 4px solid #8b5cf6 !important;"
               title="Filtrar administradores">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="text-muted small fw-semibold">Administradores</span>
                    @if($esAdminActivo)
                        <span class="badge rounded-pill px-2 py-1 text-white" style="background: #8b5cf6; font-size: 0.65rem;">Filtro Activo</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalAdministradores) }}</div>
                <div class="small text-muted mt-auto" style="font-size: 0.72rem;">Gestión global &rarr;</div>
            </a>
        </div>

        <!-- 6. Pendientes -->
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('admin.usuarios.index', ['estado' => 'PENDIENTE', 'rol' => $rolFiltro !== 'TODOS' ? $rolFiltro : 'TODOS']) }}"
               class="card border-0 shadow-sm rounded-4 h-100 p-3 text-decoration-none portal-stat-card {{ $esPendienteActivo ? 'active-filter' : '' }}"
               style="border-left: 4px solid #f59e0b !important;"
               title="Filtrar cuentas pendientes de validación">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="text-muted small fw-semibold">Pendientes</span>
                    @if($esPendienteActivo)
                        <span class="badge rounded-pill px-2 py-1 text-white" style="background: #f59e0b; font-size: 0.65rem;">Filtro Activo</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalPendientes) }}</div>
                <div class="small text-muted mt-auto" style="font-size: 0.72rem;">Por validar &rarr;</div>
            </a>
        </div>
    </div>

    <!-- Barra de búsqueda y filtros -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.usuarios.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" name="buscar" class="form-control bg-light border-start-0" placeholder="Buscar por nombre, correo, matrícula o empleado..." value="{{ $buscar }}">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <select name="rol" class="form-select bg-light">
                        <option value="TODOS" @selected($rolFiltro === 'TODOS')>Todos los Roles</option>
                        <option value="Alumno" @selected($rolFiltro === 'Alumno')>Alumnos</option>
                        <option value="Docente" @selected($rolFiltro === 'Docente')>Docentes</option>
                        <option value="Orientador" @selected($rolFiltro === 'Orientador')>Orientadores</option>
                        <option value="Administrador" @selected($rolFiltro === 'Administrador')>Administradores</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <select name="estado" class="form-select bg-light">
                        <option value="TODOS" @selected($estadoFiltro === 'TODOS')>Todos los Estados</option>
                        <option value="ACTIVO" @selected($estadoFiltro === 'ACTIVO')>ACTIVO</option>
                        <option value="PENDIENTE" @selected($estadoFiltro === 'PENDIENTE')>PENDIENTE</option>
                        <option value="INACTIVO" @selected($estadoFiltro === 'INACTIVO')>INACTIVO</option>
                        <option value="BLOQUEADO" @selected($estadoFiltro === 'BLOQUEADO')>BLOQUEADO</option>
                        <option value="RECHAZADO" @selected($estadoFiltro === 'RECHAZADO')>RECHAZADO</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 rounded-3">
                        Filtrar
                    </button>
                    @if ($buscar !== '' || $rolFiltro !== 'TODOS' || $estadoFiltro !== 'TODOS')
                        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
                            &times;
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4" style="width: 50px;">#</th>
                        <th scope="col">Usuario e Información</th>
                        <th scope="col">Rol Institucional</th>
                        <th scope="col">Identificador / Perfil</th>
                        <th scope="col" class="text-center">Estado</th>
                        <th scope="col">Último Acceso</th>
                        <th scope="col" class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $u)
                        @php
                            $rolNombre = $u->rol?->nombre ?? 'Sin Rol';
                            $iniciales = strtoupper(substr($u->nombre, 0, 1) . substr($u->apellido_paterno, 0, 1));
                            $esPropio = (auth()->id() === $u->usuario_id);
                            $admId = 'ADM-' . str_pad($u->usuario_id, 4, '0', STR_PAD_LEFT);
                            $oriId = 'ORI-' . str_pad($u->citaProfesional?->profesional_id ?? $u->usuario_id, 4, '0', STR_PAD_LEFT);
                        @endphp
                        <tr>
                            <td class="ps-4 text-muted small font-monospace">{{ $u->usuario_id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                                         style="width: 38px; height: 38px; min-width: 38px; font-size: 0.85rem;
                                         @if($rolNombre === 'Administrador') background: #7c3aed;
                                         @elseif($rolNombre === 'Docente') background: #0284c7;
                                         @elseif($rolNombre === 'Orientador') background: #0d9488;
                                         @else background: #6366f1; @endif">
                                        {{ $iniciales }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            {{ $u->nombre }} {{ $u->apellido_paterno }} {{ $u->apellido_materno }}
                                            @if($esPropio)
                                                <span class="badge rounded-pill bg-light text-dark border ms-1" style="font-size: 0.68rem;">Tú</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted">{{ $u->email }}</div>
                                        @if($u->telefono)
                                            <div class="small text-muted" style="font-size: 0.72rem;">Tel: {{ $u->telefono }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($rolNombre === 'Administrador')
                                    <span class="badge rounded-pill px-3 py-1" style="background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe;">
                                        Administrador
                                    </span>
                                @elseif($rolNombre === 'Docente')
                                    <span class="badge rounded-pill px-3 py-1" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                        Docente
                                    </span>
                                    @if($u->citaProfesional?->activo)
                                        <span class="badge rounded-pill px-2 py-1 ms-1" style="background: #ccfbf1; color: #0f766e; font-size: 0.68rem;" title="Cuenta con perfil de orientación asignado">
                                            + Orientador
                                        </span>
                                    @endif
                                @elseif($rolNombre === 'Orientador')
                                    <span class="badge rounded-pill px-3 py-1" style="background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4;">
                                        Orientador Clínico
                                    </span>
                                @elseif($rolNombre === 'Alumno')
                                    <span class="badge rounded-pill px-3 py-1" style="background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;">
                                        Alumno
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-secondary px-3 py-1">{{ $rolNombre }}</span>
                                @endif
                            </td>
                            <td>
                                @if($u->estudiante)
                                    <div class="fw-bold font-monospace text-dark small">{{ $u->estudiante->matricula }}</div>
                                    <div class="small text-muted text-truncate" style="max-width: 210px;">
                                        {{ $u->estudiante->programa?->clave ?? 'S/P' }} &bull; {{ $u->estudiante->cuatrimestre }}°{{ $u->estudiante->grupo ? ' "' . $u->estudiante->grupo . '"' : '' }} &bull; {{ $u->estudiante->plan_estudio_asignado?->codigo ?? 'Auto' }}
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.70rem;">Gen: {{ $u->estudiante->generacion }}</div>
                                @elseif($u->docente)
                                    <div class="fw-bold font-monospace text-dark small">No. {{ $u->docente->numero_empleado }}</div>
                                    <div class="small text-muted text-truncate" style="max-width: 180px;">
                                        {{ $u->docente->especialidad ?? $u->docente->puesto }}
                                    </div>
                                @elseif($rolNombre === 'Orientador' || $u->citaProfesional)
                                    <div class="fw-bold font-monospace text-dark small">{{ $oriId }}</div>
                                    <div class="small text-dark fw-semibold text-truncate" style="max-width: 180px;">{{ $u->citaProfesional?->cargo ?? 'Orientación y Bienestar' }}</div>
                                    @if($u->citaProfesional?->cedula_profesional)
                                        <div class="small text-muted font-monospace" style="font-size: 0.72rem;">Cédula: {{ $u->citaProfesional->cedula_profesional }}</div>
                                    @endif
                                @elseif($rolNombre === 'Administrador')
                                    <div class="fw-bold font-monospace text-dark small">{{ $admId }}</div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Administración Central</div>
                                @else
                                    <span class="text-muted small">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($u->estado === 'ACTIVO')
                                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-1">
                                        ACTIVO
                                    </span>
                                @elseif($u->estado === 'PENDIENTE')
                                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1">
                                        PENDIENTE
                                    </span>
                                @elseif($u->estado === 'BLOQUEADO')
                                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle px-3 py-1">
                                        BLOQUEADO
                                    </span>
                                @elseif($u->estado === 'INACTIVO')
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-3 py-1">
                                        INACTIVO
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-dark-subtle text-dark-emphasis border border-dark-subtle px-3 py-1">
                                        {{ $u->estado }}
                                    </span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                @if($u->ultimo_acceso)
                                    <span title="{{ $u->ultimo_acceso }}">{{ \Carbon\Carbon::parse($u->ultimo_acceso)->diffForHumans() }}</span>
                                @else
                                    <span class="text-muted" style="font-size: 0.75rem;">Sin registros</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-3 px-3 py-1 d-inline-flex align-items-center justify-content-center" style="min-height: 36px;" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Opciones de usuario {{ $u->nombre }}">
                                        Opciones
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-1">
                                        <li>
                                            <a class="dropdown-item rounded-2 small py-1.5 d-flex align-items-center gap-2" href="{{ route('admin.usuarios.edit', $u->usuario_id) }}">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                <span>Editar perfil</span>
                                            </a>
                                        </li>

                                        @if($u->esAlumno() || $u->estudiante)
                                            <li>
                                                <a class="dropdown-item rounded-2 small py-1.5 d-flex align-items-center gap-2 text-primary" href="{{ route('admin.estudiantes.trayectoria', $u->usuario_id) }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M16 3h5v5"></path>
                                                        <path d="M4 20L21 3"></path>
                                                        <path d="M21 16v5h-5"></path>
                                                        <path d="M15 15l6 6"></path>
                                                        <path d="M4 4l5 5"></path>
                                                    </svg>
                                                    <span>Trayectoria académica</span>
                                                </a>
                                            </li>
                                        @endif

                                        <li><hr class="dropdown-divider my-1"></li>

                                        {{-- Cambios de estado protegidos por modales Bootstrap 5 --}}
                                        @if(!$esPropio)
                                            @if($u->estado !== 'ACTIVO')
                                                <li>
                                                    <form action="{{ route('admin.usuarios.estado', $u->usuario_id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="ACTIVO">
                                                        <button type="submit" class="dropdown-item rounded-2 small py-1.5 text-success d-flex align-items-center gap-2">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <polyline points="20 6 9 17 4 12"></polyline>
                                                            </svg>
                                                            <span>Activar cuenta</span>
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif

                                            @if($u->estado !== 'INACTIVO')
                                                <li>
                                                    <button type="button" class="dropdown-item rounded-2 small py-1.5 text-secondary d-flex align-items-center gap-2"
                                                            data-bs-toggle="modal" data-bs-target="#modalDesactivar{{ $u->usuario_id }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                                        </svg>
                                                        <span>Desactivar cuenta...</span>
                                                    </button>
                                                </li>
                                            @endif

                                            @if($u->estado !== 'BLOQUEADO')
                                                <li>
                                                    <button type="button" class="dropdown-item rounded-2 small py-1.5 text-danger d-flex align-items-center gap-2"
                                                            data-bs-toggle="modal" data-bs-target="#modalBloquear{{ $u->usuario_id }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                                                        </svg>
                                                        <span>Bloquear cuenta...</span>
                                                    </button>
                                                </li>
                                            @endif
                                        @endif

                                        <li><hr class="dropdown-divider my-1"></li>

                                        <li>
                                            <button type="button" class="dropdown-item rounded-2 small py-1.5 text-primary d-flex align-items-center gap-2"
                                                    data-bs-toggle="modal" data-bs-target="#modalResetPass{{ $u->usuario_id }}">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                                                </svg>
                                                <span>Restablecer contraseña...</span>
                                            </button>
                                        </li>

                                        @if(!$esPropio)
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button type="button" class="dropdown-item rounded-2 small py-1.5 text-danger d-flex align-items-center gap-2"
                                                        data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $u->usuario_id }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                    <span>Eliminar cuenta...</span>
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                </div>

                                {{-- MODAL 1: Desactivar Cuenta --}}
                                @if(!$esPropio && $u->estado !== 'INACTIVO')
                                    <div class="modal fade text-start" id="modalDesactivar{{ $u->usuario_id }}" tabindex="-1" aria-labelledby="modalLabelDesactivar{{ $u->usuario_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="{{ route('admin.usuarios.estado', $u->usuario_id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="estado" value="INACTIVO">
                                                    <div class="modal-header border-bottom-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-dark" id="modalLabelDesactivar{{ $u->usuario_id }}">Desactivar Cuenta</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted small mb-2">
                                                            ¿Estás seguro de desactivar la cuenta institucional de <strong>{{ $u->nombre }} {{ $u->apellido_paterno }}</strong> ({{ $u->email }})?
                                                        </p>
                                                        <div class="alert alert-light border small text-muted mb-0">
                                                            <strong>Garantía institucional:</strong> El usuario no podrá iniciar sesión mientras permanezca inactivo. Su historial clínico, evaluaciones, asignaciones y notas se mantendrán intactos.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top-0 pt-0">
                                                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-secondary rounded-3">Confirmar Desactivación</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- MODAL 2: Bloquear Cuenta --}}
                                @if(!$esPropio && $u->estado !== 'BLOQUEADO')
                                    <div class="modal fade text-start" id="modalBloquear{{ $u->usuario_id }}" tabindex="-1" aria-labelledby="modalLabelBloquear{{ $u->usuario_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="{{ route('admin.usuarios.estado', $u->usuario_id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="estado" value="BLOQUEADO">
                                                    <div class="modal-header border-bottom-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-danger" id="modalLabelBloquear{{ $u->usuario_id }}">Bloqueo Preventivo de Seguridad</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted small mb-2">
                                                            ¿Deseas bloquear preventivamente el acceso de <strong>{{ $u->nombre }} {{ $u->apellido_paterno }}</strong>?
                                                        </p>
                                                        <div class="alert alert-danger alert-dismissible bg-danger-subtle border-danger-subtle text-danger-emphasis small mb-0">
                                                            La cuenta será restringida de inmediato ante cualquier intento de acceso. Solo un Administrador podrá desbloquearla.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top-0 pt-0">
                                                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-danger rounded-3">Confirmar Bloqueo</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- MODAL 3: Eliminar Cuenta (Safe Delete) --}}
                                @if(!$esPropio)
                                    <div class="modal fade text-start" id="modalEliminar{{ $u->usuario_id }}" tabindex="-1" aria-labelledby="modalLabelEliminar{{ $u->usuario_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="{{ route('admin.usuarios.destroy', $u->usuario_id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-header border-bottom-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-danger" id="modalLabelEliminar{{ $u->usuario_id }}">Eliminación de Cuenta</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted small mb-2">
                                                            Estás solicitando la eliminación de la cuenta de <strong>{{ $u->nombre }} {{ $u->apellido_paterno }}</strong> ({{ $u->email }}).
                                                        </p>
                                                        <div class="alert alert-warning border small text-dark mb-0">
                                                            <strong>Política de Integridad:</strong> Solo se permite la eliminación física de cuentas que <u>no tengan dependencias históricas</u> (evaluaciones psicológicas, bitácoras emocionales, citas, asignaciones docentes o canjes de recompensas). Si existen antecedentes, el sistema rechazará la acción y te sugerirá cambiar el estado a <strong>INACTIVO</strong>.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top-0 pt-0">
                                                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-danger rounded-3">Eliminar de Forma Segura</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- MODAL 4: Restablecer Contraseña --}}
                                <div class="modal fade text-start" id="modalResetPass{{ $u->usuario_id }}" tabindex="-1" aria-labelledby="modalLabelPass{{ $u->usuario_id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form action="{{ route('admin.usuarios.reset-password', $u->usuario_id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                                @csrf
                                                <div class="modal-header border-bottom-0 pb-0">
                                                    <h5 class="modal-title fw-bold" id="modalLabelPass{{ $u->usuario_id }}">Restablecer Contraseña</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-3">
                                                        Estás por generar una nueva credencial para <strong>{{ $u->nombre }} {{ $u->apellido_paterno }}</strong> ({{ $u->email }}).
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Contraseña personalizada (opcional):</label>
                                                        <input type="password" name="custom_password" class="form-control" placeholder="Dejar vacío para generar clave aleatoria segura">
                                                        <div class="form-text small" style="font-size: 0.75rem;">Si la dejas vacía, el sistema creará una contraseña temporal aleatoria.</div>
                                                    </div>
                                                    @if($u->estado === 'BLOQUEADO')
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" name="desbloquear_cuenta" value="1" id="desbloquear{{ $u->usuario_id }}" checked>
                                                            <label class="form-check-label small" for="desbloquear{{ $u->usuario_id }}">
                                                                Desbloquear cuenta automáticamente a estado ACTIVO
                                                            </label>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer border-top-0 pt-0">
                                                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary rounded-3">Confirmar y Restablecer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-2 text-muted">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="8" y1="12" x2="16" y2="12"></line>
                                </svg>
                                <p class="mb-0 fw-semibold">No se encontraron usuarios con los criterios de búsqueda seleccionados.</p>
                                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-link mt-1">Limpiar filtros</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($usuarios->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Mostrando {{ $usuarios->firstItem() }} a {{ $usuarios->lastItem() }} de {{ $usuarios->total() }} usuarios
                    </div>
                    <div>
                        {{ $usuarios->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
.portal-stat-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    cursor: pointer;
}
.portal-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}
.portal-stat-card.active-filter {
    box-shadow: 0 0 0 2px #3b82f6 !important;
    background-color: #f8fafc;
}
</style>
@endsection
