@extends('layouts.dashboard')

@section('title', 'Planes de Estudio de ' . $carrera->clave . ' - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.carreras.index') }}" class="text-decoration-none">Programas Educativos</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $carrera->clave }} - Planes</li>
            </ol>
        </nav>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Planes de Estudio: {{ $carrera->nombre }} ({{ $carrera->clave }})</h1>
                <p class="text-muted mb-0">Versiones curriculares oficiales y control de retículas académicas.</p>
            </div>
            <div>
                <a href="{{ route('admin.planes.create', $carrera->programa_id) }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nueva Versión de Plan</span>
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-auto-dismiss mb-4" role="alert" data-auto-dismiss="4000">
            <strong>Éxito:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4">Código / Versión</th>
                        <th scope="col">Nombre del Plan</th>
                        <th scope="col">Año Aprobación</th>
                        <th scope="col">Vigencia</th>
                        <th scope="col" class="text-center">Cuatrimestres</th>
                        <th scope="col" class="text-center">Materias</th>
                        <th scope="col" class="text-center">Estado</th>
                        <th scope="col" class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($carrera->planes as $plan)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark">{{ $plan->codigo }}</span>
                                <span class="badge bg-light text-secondary border ms-1">v{{ $plan->version }}</span>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $plan->nombre }}</div>
                                @if($plan->descripcion)
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 300px;">{{ $plan->descripcion }}</small>
                                @endif
                            </td>
                            <td>{{ $plan->anio_aprobacion }}</td>
                            <td>
                                <span class="text-nowrap">{{ $plan->vigencia_inicio?->format('d/m/Y') ?? 'N/D' }}</span>
                                @if($plan->vigencia_fin)
                                    <span class="text-muted text-nowrap">al {{ $plan->vigencia_fin->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $plan->total_cuatrimestres }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border px-2 py-1 fw-semibold">
                                    {{ $plan->asignaturas->count() }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($plan->es_vigente || $plan->estado === 'VIGENTE')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="Plan aprobado para asignaciones vigentes">
                                        ✅ Vigente oficial
                                    </span>
                                @elseif ($plan->estado === 'BORRADOR')
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1" title="En preparación; no publicado">
                                        📝 Borrador
                                    </span>
                                @elseif ($plan->estado === 'HISTORICO')
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" title="Versión anterior conservada para generaciones previas">
                                        🏛️ Histórico
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1" title="Retirado de uso operativo">
                                        📦 Archivado
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4 text-nowrap">
                                <a href="{{ route('admin.planes.show', $plan->plan_id) }}" class="btn btn-sm btn-primary me-1 rounded-pill px-3">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    Retícula
                                </a>

                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-none" style="min-height: 32px;" title="Eliminar plan de estudios" data-bs-toggle="modal" data-bs-target="#modalEliminarPlan{{ $plan->plan_id }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                    </svg>
                                    <span>Eliminar</span>
                                </button>

                                <!-- Modal Eliminar Plan de Estudios -->
                                <div class="modal fade" id="modalEliminarPlan{{ $plan->plan_id }}" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
                                    <div class="modal-dialog modal-dialog-centered" style="white-space: normal !important; max-width: 500px;">
                                        <div class="modal-content rounded-4 border-0 shadow" style="white-space: normal !important; word-break: break-word;">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold text-danger">Eliminar Plan de Estudios</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                            </div>
                                            <div class="modal-body text-start" style="white-space: normal !important; word-break: break-word;">
                                                <p class="text-muted small mb-2">
                                                    ¿Confirmas que deseas eliminar el plan <strong>{{ $plan->codigo }} v{{ $plan->version }}</strong> ({{ $plan->nombre }})?
                                                </p>
                                                <p class="text-muted small mb-0">
                                                    Esta acción eliminará el plan y sus asignaturas sin historial. Si el plan cuenta con cátedras impartidas o alumnos adscritos históricamente, la eliminación será rechazada para salvaguardar la integridad de los expedientes.
                                                </p>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                <form action="{{ route('admin.planes.destroy', $plan->plan_id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">Confirmar Eliminación</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No hay planes registrados para este programa educativo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Guía Contextual de Estados de Planes -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h2 class="h6 fw-bold text-dark mb-3">ℹ️ Guía Institucional de Estados del Plan de Estudios</h2>
        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="p-3 rounded-3 bg-light h-100 border">
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle mb-2">Borrador</span>
                    <div class="text-muted">Plan en preparación técnica o curricular. Todavía no ha sido oficializado ni permite asignaciones activas de grupos.</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-3 bg-light h-100 border">
                    <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">Vigente oficial</span>
                    <div class="text-muted">Plan curricular formalmente aprobado. Es la versión aplicable a nuevos ingresos y asignaciones de asignaturas activas.</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-3 bg-light h-100 border">
                    <span class="badge bg-secondary-subtle text-secondary border mb-2">Histórico</span>
                    <div class="text-muted">Versión anterior conservada para alumnos de generaciones que iniciaron con esta retícula. Las materias asignadas no sufren alteraciones.</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-3 bg-light h-100 border">
                    <span class="badge bg-light text-muted border mb-2">Archivado</span>
                    <div class="text-muted">Plan totalmente retirado de operación activa. Se conserva únicamente para integridad de expedientes y auditoría histórica.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

