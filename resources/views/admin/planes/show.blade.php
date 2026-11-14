@extends('layouts.dashboard')

@section('title', 'Retícula Curricular - ' . $plan->codigo . ' - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.carreras.index') }}" class="text-decoration-none">Programas Educativos</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.planes.index', $plan->programa_id) }}" class="text-decoration-none">{{ $plan->programa->clave }} - Planes</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $plan->codigo }} v{{ $plan->version }}</li>
            </ol>
        </nav>
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">
                    Retícula: {{ $plan->programa->nombre }} ({{ $plan->codigo }} v{{ $plan->version }})
                </h1>
                <p class="text-muted mb-0">Total: {{ $plan->asignaturas->count() }} asignaturas registradas en {{ $plan->total_cuatrimestres }} cuatrimestres.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Formulario Rápido de Estado -->
                <form action="{{ route('admin.planes.estado', $plan->plan_id) }}" method="POST" class="d-flex gap-2 align-items-center">
                    @csrf
                    @method('PATCH')
                    <label for="selectEstado" class="form-label mb-0 fw-semibold text-secondary small">Estado:</label>
                    <select name="estado" id="selectEstado" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="BORRADOR" @selected($plan->estado === 'BORRADOR')>Borrador</option>
                        <option value="VIGENTE" @selected($plan->estado === 'VIGENTE')>Vigente Oficial</option>
                        <option value="HISTORICO" @selected($plan->estado === 'HISTORICO')>Histórico</option>
                        <option value="ARCHIVADO" @selected($plan->estado === 'ARCHIVADO')>Archivado</option>
                    </select>
                </form>

                <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAgregarMateria">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Agregar Asignatura</span>
                </button>

                <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEliminarPlanHeader">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                    </svg>
                    <span>Eliminar Plan</span>
                </button>

                <!-- Modal Eliminar Plan Header -->
                <div class="modal fade" id="modalEliminarPlanHeader" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
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
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-auto-dismiss mb-4" role="alert" data-auto-dismiss="4000">
            <strong>Éxito:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h6 class="fw-bold mb-2">Por favor corrige los siguientes errores:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- Malla Curricular por Cuatrimestres -->
    <div class="row g-4">
        @for ($c = 1; $c <= $plan->total_cuatrimestres; $c++)
            @php
                $materiasCuatri = $asignaturasPorCuatrimestre->get($c, collect());
            @endphp
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom border-light d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-pill px-3 py-1">{{ $c }}° Cuatrimestre</span>
                            @if($c === 6)
                                <span class="badge bg-info-subtle text-info border border-info-subtle small">Cierre TSU</span>
                            @elseif($c === 10)
                                <span class="badge bg-success-subtle text-success border border-success-subtle small">Cierre Licenciatura</span>
                            @endif
                        </div>
                        <span class="text-muted small fw-medium">{{ $materiasCuatri->count() }} materia(s)</span>
                    </div>
                    <div class="card-body p-0">
                        @if($materiasCuatri->isEmpty())
                            <div class="p-4 text-center text-muted small">
                                No hay asignaturas registradas en este cuatrimestre.
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($materiasCuatri as $materia)
                                    <div class="list-group-item p-3 border-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="fw-bold text-dark font-monospace small">{{ $materia->clave }}</span>
                                                @if($materia->es_estadia)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">ESTADÍA</span>
                                                @elseif($materia->es_optativa)
                                                    <span class="badge bg-purple-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">OPTATIVA</span>
                                                @endif
                                                @if(!$materia->activo)
                                                    <span class="badge bg-secondary" style="font-size: 0.7rem;">INACTIVA</span>
                                                @endif
                                            </div>
                                            <div class="fw-semibold text-secondary" style="font-size: 0.95rem;">{{ $materia->nombre }}</div>
                                            <div class="text-muted small mt-1">
                                                <span>{{ $materia->horas_semana ?? '-' }} hrs/sem</span> •
                                                <span>{{ $materia->creditos ?? '-' }} créditos</span> •
                                                <span class="text-lowercase">{{ $materia->tipo }}</span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#modalEditarMateria{{ $materia->asignatura_id }}" title="Editar Asignatura">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v16a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light border text-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarMateria{{ $materia->asignatura_id }}" title="Eliminar Asignatura">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Modal Eliminar Materia -->
                                    <div class="modal fade" id="modalEliminarMateria{{ $materia->asignatura_id }}" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
                                        <div class="modal-dialog modal-dialog-centered" style="white-space: normal !important; max-width: 460px;">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-danger">Eliminar Asignatura</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <p class="text-muted small mb-0">
                                                        ¿Confirmas que deseas eliminar la asignatura <strong>{{ $materia->clave }} - {{ $materia->nombre }}</strong> del plan? Si no cuenta con cátedras impartidas ni inscripciones históricas, será eliminada permanentemente.
                                                    </p>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                    <form action="{{ route('admin.planes.materias.destroy', $materia->asignatura_id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Editar Materia -->
                                    <div class="modal fade" id="modalEditarMateria{{ $materia->asignatura_id }}" tabindex="-1" aria-labelledby="modalEditarMateriaLabel{{ $materia->asignatura_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0">
                                                <form action="{{ route('admin.planes.materias.update', $materia->asignatura_id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold" id="modalEditarMateriaLabel{{ $materia->asignatura_id }}">Editar {{ $materia->clave }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Nombre de la Asignatura</label>
                                                            <input type="text" name="nombre" class="form-control" value="{{ $materia->nombre }}" required>
                                                        </div>
                                                        <div class="row g-2 mb-3">
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Cuatrimestre</label>
                                                                <input type="number" name="cuatrimestre" class="form-control" value="{{ $materia->cuatrimestre }}" min="1" max="12" required>
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Tipo</label>
                                                                <select name="tipo" class="form-select" required>
                                                                    <option value="ORDINARIA" @selected($materia->tipo === 'ORDINARIA')>Ordinaria</option>
                                                                    <option value="OPTATIVA" @selected($materia->tipo === 'OPTATIVA')>Optativa</option>
                                                                    <option value="ESTADIA" @selected($materia->tipo === 'ESTADIA')>Estadía</option>
                                                                    <option value="PROYECTO_INTEGRADOR" @selected($materia->tipo === 'PROYECTO_INTEGRADOR')>Proyecto Integrador</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row g-2 mb-3">
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Horas / Semana</label>
                                                                <input type="number" name="horas_semana" class="form-control" value="{{ $materia->horas_semana }}" min="1" max="40">
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Créditos</label>
                                                                <input type="number" name="creditos" class="form-control" value="{{ $materia->creditos }}" min="1" max="20">
                                                            </div>
                                                        </div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="activoMat{{ $materia->asignatura_id }}" name="activo" value="1" {{ $materia->activo ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-semibold" for="activoMat{{ $materia->asignatura_id }}">Materia Activa</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>

<!-- Modal Agregar Materia -->
<div class="modal fade" id="modalAgregarMateria" tabindex="-1" aria-labelledby="modalAgregarMateriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <form action="{{ route('admin.planes.materias.store', $plan->plan_id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalAgregarMateriaLabel">Agregar Asignatura al Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombreMateria" class="form-label fw-semibold">Nombre de la Asignatura <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombreMateria" class="form-control" placeholder="Ej. Cálculo Diferencial e Integral" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="cuatriMateria" class="form-label fw-semibold">Cuatrimestre <span class="text-danger">*</span></label>
                            <select name="cuatrimestre" id="cuatriMateria" class="form-select" required>
                                @for($i = 1; $i <= $plan->total_cuatrimestres; $i++)
                                    <option value="{{ $i }}">{{ $i }}° Cuatrimestre</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="tipoMateria" class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" id="tipoMateria" class="form-select" required>
                                <option value="ORDINARIA" selected>Ordinaria</option>
                                <option value="OPTATIVA">Optativa</option>
                                <option value="ESTADIA">Estadía</option>
                                <option value="PROYECTO_INTEGRADOR">Proyecto Integrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="claveMateria" class="form-label fw-semibold">Clave (opcional)</label>
                            <input type="text" name="clave" id="claveMateria" class="form-control text-uppercase" placeholder="Autogenerada si vacía">
                        </div>
                        <div class="col-3">
                            <label for="horasMateria" class="form-label fw-semibold">Hrs/Sem</label>
                            <input type="number" name="horas_semana" id="horasMateria" class="form-control" value="5" min="1" max="40">
                        </div>
                        <div class="col-3">
                            <label for="creditosMateria" class="form-label fw-semibold">Créditos</label>
                            <input type="number" name="creditos" id="creditosMateria" class="form-control" value="5" min="1" max="20">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Asignatura</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

