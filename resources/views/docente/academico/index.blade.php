@extends('layouts.dashboard')

@section('title', 'Mis Asignaturas y Grupos - Bienestar UPEMOR')

@push('styles')
<style>
    .academico-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 16px -2px rgba(109, 40, 217, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .academico-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -2px rgba(109, 40, 217, 0.10);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Encabezado del Módulo -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('docente.dashboard') }}" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver al panel de docente
                </a>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Mis Asignaturas y Grupos</h1>
            <p class="text-muted small mb-0">
                Periodo activo: <strong>{{ $periodoActivo ? $periodoActivo->nombre . ' (' . $periodoActivo->clave . ')' : 'Sin periodo activo definido' }}</strong>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('docente.academico.solicitar') }}" class="btn btn-primary rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-journal-plus" aria-hidden="true"></i>
                <span class="fw-semibold">Solicitar Asignatura</span>
            </a>
            <a href="{{ route('docente.recompensas.index') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2">
                <i class="bi bi-award" aria-hidden="true"></i>
                <span class="fw-semibold">Beneficios</span>
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

    @if (session('info'))
        <div class="alert alert-info rounded-4 border-0 shadow-xs mb-4">
            {{ session('info') }}
        </div>
    @endif

    <!-- PESTAÑAS DE NAVEGACIÓN -->
    <ul class="nav nav-pills mb-4 gap-2" id="docenteAcademicoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="aprobadas-tab" data-bs-toggle="pill" data-bs-target="#aprobadas-pane" type="button" role="tab" aria-controls="aprobadas-pane" aria-selected="true">
                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Asignaturas Aprobadas ({{ $asignaciones->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 position-relative" id="pendientes-tab" data-bs-toggle="pill" data-bs-target="#pendientes-pane" type="button" role="tab" aria-controls="pendientes-pane" aria-selected="false">
                <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Solicitudes Pendientes ({{ $solicitudesPendientes->count() }})
                @if($solicitudesPendientes->count() > 0)
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle">
                        <span class="visually-hidden">Solicitudes pendientes</span>
                    </span>
                @endif
            </button>
        </li>
        @if($solicitudesRechazadas->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="rechazadas-tab" data-bs-toggle="pill" data-bs-target="#rechazadas-pane" type="button" role="tab" aria-controls="rechazadas-pane" aria-selected="false">
                <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Rechazadas ({{ $solicitudesRechazadas->count() }})
            </button>
        </li>
        @endif
    </ul>

    <div class="tab-content" id="docenteAcademicoTabContent">
        <!-- PANE 1: ASIGNATURAS APROBADAS / ACTIVAS (CONSOLIDACIÓN POR ASIGNATURA Y SUBGRUPOS) -->
        <div class="tab-pane fade show active" id="aprobadas-pane" role="tabpanel" aria-labelledby="aprobadas-tab" tabindex="0">
            @if ($asignaciones->isEmpty() && (!isset($asignaturasConsolidadas) || $asignaturasConsolidadas->isEmpty()))
                <div class="academico-card text-center py-5">
                    <div class="p-3 rounded-circle bg-light d-inline-flex mb-3" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                        <i class="bi bi-journal-bookmark fs-2 text-muted" aria-hidden="true"></i>
                    </div>
                    <h2 class="h6 fw-bold text-dark mb-1">No tienes asignaturas activas asignadas en el periodo actual</h2>
                    <p class="text-muted small mb-3">Puedes enviar una solicitud a la Dirección para impartir una materia o consultar tus solicitudes en trámite.</p>
                    <a href="{{ route('docente.academico.solicitar') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Solicitar Asignatura
                    </a>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($asignaturasConsolidadas as $item)
                        @php
                            $materia = $item['asignatura'];
                            $subgrupos = $item['subgrupos'];
                            $asigPrincipal = $subgrupos->first();
                        @endphp
                        <div class="col-md-6 col-lg-6">
                            <div class="academico-card h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <!-- Encabezado de la Tarjeta Consolidada -->
                                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small fw-semibold">
                                                {{ $materia->clave }}
                                            </span>
                                            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                                                {{ $materia->cuatrimestre }}° Cuatrimestre
                                            </span>
                                        </div>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Cátedra Aprobada
                                        </span>
                                    </div>

                                    <h2 class="h5 fw-bold text-dark mb-1">{{ $materia->nombre }}</h2>
                                    <p class="text-muted small mb-3">
                                        Programa: <strong>{{ $materia->programa?->nombre ?? 'UPEMOR' }}</strong> ({{ $materia->programa?->clave ?? 'UPEMOR' }}) &bull; Periodo: {{ $asigPrincipal->periodo?->nombre }}
                                    </p>

                                    <!-- Panel de Subgrupos Atendidos -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="fw-semibold text-dark small">
                                                <i class="bi bi-diagram-3-fill text-primary me-1" aria-hidden="true"></i>Subgrupos Asignados ({{ $subgrupos->count() }}):
                                            </span>
                                            <span class="badge bg-light text-muted border rounded-pill small">
                                                {{ $item['total_recompensas'] }} beneficio(s) activo(s)
                                            </span>
                                        </div>

                                        <div class="list-group rounded-3 border-0 shadow-none gap-2">
                                            @foreach($subgrupos as $sub)
                                                <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light-subtle">
                                                    <div>
                                                        <div class="fw-bold text-dark small">
                                                            Subgrupo {{ $sub->grupo->cuatrimestre }}° "{{ $sub->grupo->nombre }}"
                                                            <span class="badge bg-white text-muted border rounded-pill ms-1 small">Turno: {{ $sub->grupo->turno }}</span>
                                                        </div>
                                                        <div class="text-muted" style="font-size: 0.78rem;">
                                                            {{ $sub->grupo->nombre_completo }}
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1.5">
                                                        <a href="{{ route('docente.academico.grupo', $sub->asignacion_id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 small" title="Ver estudiantes y solicitudes">
                                                            <i class="bi bi-people me-1" aria-hidden="true"></i>Estudiantes
                                                        </a>
                                                        <a href="{{ route('docente.recompensas.create', $sub->asignacion_id) }}" class="btn btn-light btn-sm rounded-pill px-2 py-1 text-muted border small" title="Activar beneficio para este grupo">
                                                            <i class="bi bi-award" aria-hidden="true"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-3 border-top mt-2">
                                    <a href="{{ route('docente.academico.grupo', $asigPrincipal->asignacion_id) }}" class="btn btn-primary btn-sm rounded-pill px-4 w-100 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 shadow-xs">
                                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                                        <span>Gestionar Subgrupos y Estudiantes</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- PANE 2: SOLICITUDES PENDIENTES -->
        <div class="tab-pane fade" id="pendientes-pane" role="tabpanel" aria-labelledby="pendientes-tab" tabindex="0">
            @if ($solicitudesPendientes->isEmpty())
                <div class="academico-card text-center py-5">
                    <i class="bi bi-inbox fs-2 text-muted d-block mb-2" aria-hidden="true"></i>
                    <p class="text-muted small mb-0">No tienes solicitudes pendientes de resolución en este periodo.</p>
                </div>
            @else
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%;">Asignatura</th>
                                    <th style="width: 20%;">Grupo Académico</th>
                                    <th style="width: 20%;">Fecha de Solicitud</th>
                                    <th style="width: 15%;">Estado</th>
                                    <th style="width: 15%;">Justificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($solicitudesPendientes as $sol)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $sol->asignatura?->nombre }}</div>
                                            <div class="small text-muted">Clave: {{ $sol->asignatura?->clave }} &bull; {{ $sol->asignatura?->programa?->clave }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                Grupo {{ $sol->grupo?->nombre_completo }}
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $sol->solicitado_en ? \Carbon\Carbon::parse($sol->solicitado_en)->format('d/m/Y H:i') : ($sol->created_at ? $sol->created_at->format('d/m/Y H:i') : '-') }}
                                        </td>
                                        <td>
                                            <span class="badge badge-estado-pendiente">
                                                <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>En Revisión
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $sol->motivo_solicitud ?: 'Sin observaciones registradas' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- PANE 3: SOLICITUDES RECHAZADAS -->
        @if($solicitudesRechazadas->count() > 0)
        <div class="tab-pane fade" id="rechazadas-pane" role="tabpanel" aria-labelledby="rechazadas-tab" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%;">Asignatura</th>
                                <th style="width: 20%;">Grupo</th>
                                <th style="width: 15%;">Estado</th>
                                <th style="width: 35%;">Observación de Dirección</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($solicitudesRechazadas as $rec)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $rec->asignatura?->nombre }}</div>
                                        <div class="small text-muted">Clave: {{ $rec->asignatura?->clave }}</div>
                                    </td>
                                    <td>Grupo {{ $rec->grupo?->nombre_completo }}</td>
                                    <td>
                                        <span class="badge badge-estado-inactivo">
                                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Rechazada
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $rec->motivo_resolucion ?: 'Sin motivo especificado' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
