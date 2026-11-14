@extends('layouts.dashboard')

@section('title', 'Programas Educativos y Planes de Estudio - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Programas Educativos y Planes de Estudio</h1>
            <p class="text-muted mb-0">Gestión de carreras institucionales, planes de estudio oficiales y mallas curriculares de UPEMOR.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.carreras.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-pill px-3 py-2 shadow-xs">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Nuevo Programa</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-auto-dismiss mb-4 rounded-4 border-0 shadow-xs" role="alert" data-auto-dismiss="4000">
            <strong>Éxito:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-4 border-0 shadow-xs" role="alert">
            <strong>Atención:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- Navegación por pestañas: Oficiales vs Históricos -->
    <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 py-2 fw-semibold {{ $tab === 'oficiales' ? 'active shadow-xs' : 'bg-white border text-muted' }}" 
               href="{{ route('admin.carreras.index', ['tab' => 'oficiales']) }}">
                🎓 Programas Oficiales Activos
                <span class="badge {{ $tab === 'oficiales' ? 'bg-white text-primary' : 'bg-light text-secondary border' }} rounded-pill ms-2">{{ $totalOficiales }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 py-2 fw-semibold {{ $tab === 'historicos' ? 'active shadow-xs' : 'bg-white border text-muted' }}" 
               href="{{ route('admin.carreras.index', ['tab' => 'historicos']) }}">
                🏛️ Programas Históricos e Inactivos
                <span class="badge {{ $tab === 'historicos' ? 'bg-white text-primary' : 'bg-light text-secondary border' }} rounded-pill ms-2">{{ $totalHistoricos }}</span>
            </a>
        </li>
    </ul>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom border-light d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-secondary">
                {{ $tab === 'oficiales' ? 'Catálogo Curricular Oficial Vigente' : 'Archivo de Programas Anteriores' }} ({{ $carreras->count() }})
            </span>
            <span class="badge {{ $tab === 'oficiales' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary' }} fw-medium px-3 py-1.5 rounded-pill">
                {{ $tab === 'oficiales' ? '6 Carreras Oficiales' : 'Registros Preservados' }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4">Clave</th>
                        <th scope="col">Nombre Oficial del Programa</th>
                        <th scope="col">Plan Vigente</th>
                        <th scope="col" class="text-center">Asignaturas</th>
                        <th scope="col" class="text-center">Estudiantes</th>
                        <th scope="col" class="text-center">Estado Operativo</th>
                        <th scope="col" class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($carreras as $carrera)
                        <tr>
                            <td class="ps-4 fw-bold text-nowrap">
                                <span class="badge bg-light text-dark border px-2.5 py-1.5 font-monospace fs-6">{{ $carrera->clave }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark fs-6">{{ $carrera->nombre }}</div>
                                @if($carrera->clave_historica || $carrera->nombre_historico)
                                    <div class="text-muted small mt-0.5" style="font-size: 0.78rem;">
                                        @if($carrera->clave_historica && $carrera->clave_historica !== $carrera->clave)
                                            <span class="me-2">Clave anterior: <strong class="font-monospace text-secondary">{{ $carrera->clave_historica }}</strong></span>
                                        @endif
                                        @if($carrera->nombre_historico && $carrera->nombre_historico !== $carrera->nombre)
                                            <span>Denominación previa: <span class="fst-italic text-secondary">{{ $carrera->nombre_historico }}</span></span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($carrera->planVigente)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            {{ $carrera->planVigente->codigo }} (v{{ $carrera->planVigente->version }})
                                        </span>
                                    </div>
                                    <small class="text-muted">{{ $carrera->planVigente->total_cuatrimestres }} cuatrimestres</small>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border px-2 py-1">Sin plan vigente</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border px-2.5 py-1 fw-semibold">
                                    {{ $carrera->asignaturas_count }} materias
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border px-2.5 py-1 fw-semibold">
                                    {{ $carrera->estudiantes_count }} alumnos
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($carrera->estado_operativo === 'EN_LIQUIDACION')
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-1" title="No admite nuevo ingreso; atiende generaciones activas hasta egreso">
                                        ⚠️ En liquidación
                                    </span>
                                @elseif ($carrera->activo)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1" title="Oferta académica oficial vigente">
                                        ✅ Vigente oficial
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border px-3 py-1" title="Programa histórico cerrado para nuevas admisiones">
                                        ⏸ Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4 text-nowrap">
                                <a href="{{ route('admin.planes.index', $carrera->programa_id) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1 d-inline-flex align-items-center" style="min-height: 36px;" title="Ver planes de estudio y materias">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                                    </svg>
                                    Planes ({{ $carrera->planes_count }})
                                </a>
                                <a href="{{ route('admin.carreras.edit', $carrera->programa_id) }}" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 me-1 d-inline-flex align-items-center gap-1 shadow-none" style="min-height: 36px;" title="Editar programa" aria-label="Editar programa {{ $carrera->nombre }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Editar</span>
                                </a>
                                @if (!$carrera->tieneDependenciasActivas())
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-none" style="min-height: 36px;" title="Eliminar programa" aria-label="Eliminar programa {{ $carrera->nombre }}" data-bs-toggle="modal" data-bs-target="#modalEliminarCarrera{{ $carrera->programa_id }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                        </svg>
                                        <span>Eliminar</span>
                                    </button>

                                    <!-- Modal Eliminar Carrera -->
                                    <div class="modal fade" id="modalEliminarCarrera{{ $carrera->programa_id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-danger">Eliminar programa educativo</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <p class="text-muted small mb-0">
                                                        ¿Seguro que deseas eliminar permanentemente el programa <strong>{{ $carrera->nombre }}</strong> ({{ $carrera->clave }})? Esta acción no se puede deshacer.
                                                    </p>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                    <form action="{{ route('admin.carreras.destroy', $carrera->programa_id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No se encontraron programas educativos en esta categoría.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
