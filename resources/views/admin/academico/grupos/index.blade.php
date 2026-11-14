@extends('layouts.dashboard')

@section('title', 'Gestión de Grupos Académicos - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>Panel Admin
                </a>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Estructura Académica</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Catálogo de Grupos Académicos</h1>
            <p class="text-muted small mb-0">Administración de secciones, turnos y grupos oficiales para asignaciones docentes e inscripción de estudiantes.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(!empty($gruposNominalesFaltantes) && count($gruposNominalesFaltantes) > 0)
                <form action="{{ route('admin.grupos.sincronizar') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionadoId }}">
                    <button type="submit" class="btn btn-warning rounded-pill px-3 py-2 shadow-sm d-inline-flex align-items-center gap-2" title="Crear automáticamente los grupos de los alumnos que aún no tienen grupo formal (ej. Grupo B)">
                        <i class="bi bi-magic"></i>
                        <span>Sincronizar {{ count($gruposNominalesFaltantes) }} Grupo(s) de Alumnos</span>
                    </button>
                </form>
            @endif
            <button type="button" class="btn btn-primary rounded-pill px-3 py-2 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCrearGrupo">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Nuevo Grupo</span>
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if(!empty($gruposNominalesFaltantes) && count($gruposNominalesFaltantes) > 0)
        <div class="alert alert-warning border-0 rounded-4 shadow-sm p-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-circle-fill fs-3 text-warning"></i>
                <div class="flex-grow-1">
                    <strong class="text-dark">Se detectaron estudiantes con grupos no registrados en el catálogo oficial:</strong>
                    <p class="small text-muted mb-0">
                        Existen alumnos (por ejemplo en <strong>4° Cuatrimestre Grupo B</strong>) asignados en su ficha que requieren la existencia del grupo formal en este periodo para que los docentes puedan ofertarles asignaturas y ellos puedan inscribirse.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.grupos.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Periodo Académico:</label>
                    <select name="periodo_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        @foreach($periodos as $per)
                            <option value="{{ $per->periodo_id }}" {{ (string)$periodoSeleccionadoId === (string)$per->periodo_id ? 'selected' : '' }}>
                                {{ $per->nombre }} ({{ $per->clave }}) {{ $per->activo ? '— Activo' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Programa Educativo:</label>
                    <select name="programa_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        <option value="">Todas las carreras</option>
                        @foreach($programas as $prog)
                            <option value="{{ $prog->programa_id }}" {{ request('programa_id') == $prog->programa_id ? 'selected' : '' }}>
                                [{{ $prog->clave }}] {{ $prog->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Cuatrimestre:</label>
                    <select name="cuatrimestre" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ request('cuatrimestre') == $i ? 'selected' : '' }}>{{ $i }}° Cuatrimestre</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 rounded-3">Filtrar</button>
                    <a href="{{ route('admin.grupos.index', ['periodo_id' => $periodoSeleccionadoId]) }}" class="btn btn-sm btn-outline-secondary rounded-3" title="Limpiar filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de Grupos --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-4">Grupo</th>
                        <th>Programa Educativo</th>
                        <th class="text-center">Cuatrimestre</th>
                        <th class="text-center">Turno</th>
                        <th class="text-center">Alumnos Inscritos</th>
                        <th class="text-center">Materias Asignadas</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grupos as $g)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fs-6 fw-bold">
                                    {{ $g->cuatrimestre }}° "{{ $g->nombre }}"
                                </span>
                            </td>
                            <td>
                                <strong class="text-dark">{{ $g->programa?->nombre ?? 'N/A' }}</strong>
                                <span class="badge bg-light text-muted border ms-1">{{ $g->programa?->clave }}</span>
                            </td>
                            <td class="text-center fw-semibold">{{ $g->cuatrimestre }}°</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $g->turno }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1 rounded-pill">
                                    {{ $g->inscripciones_count }} alumno(s)
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle px-2.5 py-1 rounded-pill">
                                    {{ $g->asignaciones_count }} materia(s)
                                </span>
                            </td>
                            <td class="text-center">
                                @if($g->activo)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">Activo</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <form action="{{ route('admin.grupos.toggle-activo', $g->grupo_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" title="{{ $g->activo ? 'Desactivar grupo' : 'Activar grupo' }}">
                                        <i class="bi {{ $g->activo ? 'bi-toggle-on text-success fs-5' : 'bi-toggle-off text-muted fs-5' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>
                                No se encontraron grupos académicos para los criterios seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($grupos->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $grupos->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal Crear Grupo --}}
<div class="modal fade" id="modalCrearGrupo" tabindex="-1" aria-labelledby="modalCrearGrupoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('admin.grupos.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom bg-light rounded-top-4">
                    <h5 class="modal-title fw-bold" id="modalCrearGrupoLabel">
                        <i class="bi bi-plus-circle-fill text-primary me-2"></i>Registrar Nuevo Grupo Académico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Periodo Académico <span class="text-danger">*</span></label>
                        <select name="periodo_id" class="form-select rounded-3" required>
                            @foreach($periodos as $per)
                                <option value="{{ $per->periodo_id }}" {{ (string)$periodoSeleccionadoId === (string)$per->periodo_id ? 'selected' : '' }}>
                                    {{ $per->nombre }} ({{ $per->clave }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Programa Educativo / Carrera <span class="text-danger">*</span></label>
                        <select name="programa_id" class="form-select rounded-3" required>
                            <option value="">-- Selecciona la carrera --</option>
                            @foreach($programas as $prog)
                                <option value="{{ $prog->programa_id }}" {{ request('programa_id') == $prog->programa_id ? 'selected' : '' }}>
                                    [{{ $prog->clave }}] {{ $prog->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Cuatrimestre <span class="text-danger">*</span></label>
                            <select name="cuatrimestre" class="form-select rounded-3" required>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ (int)request('cuatrimestre', 4) === $i ? 'selected' : '' }}>{{ $i }}° Cuatrimestre</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Identificador / Letra <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control rounded-3 text-uppercase fw-bold" placeholder="Ej. A, B, C" maxlength="10" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Turno <span class="text-danger">*</span></label>
                        <select name="turno" class="form-select rounded-3" required>
                            <option value="MATUTINO">MATUTINO</option>
                            <option value="VESPERTINO">VESPERTINO</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar Grupo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

