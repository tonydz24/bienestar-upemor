@extends('layouts.dashboard')

@section('title', 'Periodos Académicos - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Periodos Académicos y Parciales</h1>
            <p class="text-muted mb-0">Gestión de cuatrimestres institucionales, cortes de evaluación parcial y promociones.</p>
        </div>
        <div>
            <a href="{{ route('admin.periodos.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Nuevo Periodo</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-auto-dismiss mb-4" role="alert" data-auto-dismiss="4000">
            <strong>Éxito:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <strong>Atención:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4">Clave</th>
                        <th scope="col">Nombre del Periodo</th>
                        <th scope="col">Vigencia</th>
                        <th scope="col">Cortes de Parciales</th>
                        <th scope="col" class="text-center">Alumnos</th>
                        <th scope="col" class="text-center">Grupos</th>
                        <th scope="col" class="text-center">Estado de Calendario</th>
                        <th scope="col" class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periodos as $periodo)
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-dark">{{ $periodo->clave }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $periodo->nombre }}</div>
                                <div class="small text-muted">
                                    {{ $periodo->activo ? 'Registro administrativo: Habilitado' : 'Registro administrativo: Deshabilitado' }}
                                </div>
                            </td>
                            <td>
                                <span class="text-nowrap">{{ $periodo->fecha_inicio?->format('d/m/Y') }}</span>
                                <span class="text-muted text-nowrap">al {{ $periodo->fecha_fin?->format('d/m/Y') }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @php $parcialActual = $periodo->parcialActual(); @endphp
                                    @forelse($periodo->parciales as $parcial)
                                        @php $esActual = ($parcialActual && $parcialActual->parcial_id === $parcial->parcial_id); @endphp
                                        <div class="d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                            <span class="badge @if($esActual) bg-success text-white fw-bold @elseif($parcial->activo) bg-primary-subtle text-primary border @else bg-light text-secondary border @endif py-0 px-1">
                                                {{ $parcial->numero_parcial }}° @if($esActual) (Actual) @endif
                                            </span>
                                            <span class="text-muted">{{ $parcial->fecha_inicio?->format('d/m') }} - {{ $parcial->fecha_fin?->format('d/m') }}</span>
                                        </div>
                                    @empty
                                        <span class="text-muted small">Sin parciales</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border px-2 py-1 fw-semibold">
                                    {{ $periodo->inscripciones_count }} inscritos
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border px-2 py-1 fw-semibold">
                                    {{ $periodo->grupos_count }} grupos
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span class="badge {{ $periodo->estado_calendario_badge }} px-2 py-1">
                                        {{ $periodo->estado_calendario_label }}
                                    </span>
                                    @if ($periodo->activo && $periodo->estado_calendario === \App\Models\PeriodoAcademico::ESTADO_CONCLUIDO)
                                        <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;" title="El periodo tiene activo=true en base de datos, pero sus fechas reglamentarias ya concluyeron">
                                            Vencido administrativamente
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end pe-4 text-nowrap">
                                <a href="{{ route('admin.periodos.promociones', $periodo->periodo_id) }}" class="btn btn-sm btn-outline-info me-1" title="Gestionar promoción administrativa">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                        <polyline points="17 11 12 6 7 11"></polyline>
                                        <polyline points="17 18 12 13 7 18"></polyline>
                                    </svg>
                                    Promociones
                                </a>
                                <a href="{{ route('admin.periodos.edit', $periodo->periodo_id) }}" class="btn btn-sm btn-light border me-1" title="Editar periodo y parciales">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.periodos.toggle-activo', $periodo->periodo_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $periodo->activo ? 'btn-outline-secondary' : 'btn-outline-success' }}" title="{{ $periodo->activo ? 'Archivar / Desactivar periodo' : 'Activar periodo' }}">
                                        {{ $periodo->activo ? 'Archivar' : 'Activar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No hay periodos académicos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

