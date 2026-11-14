@extends('layouts.dashboard')

@section('title', 'Mi Historial de Prácticas de Bienestar - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="mb-3">
        <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted p-0">
            <i class="bi bi-arrow-left me-1"></i> Volver al catálogo de meditaciones
        </a>
    </div>

    <!-- Encabezado -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-slate-800 mb-1">Mi Historial de Relajación y Meditación</h1>
            <p class="text-muted small mb-0">Registro cronológico de tus sesiones de autocuidado y regulación emocional.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold">
                <i class="bi bi-flower1 me-1"></i> Explorar Más Prácticas
            </a>
        </div>
    </div>

    <!-- Garantía de Confidencialidad Institucional -->
    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 d-flex align-items-start gap-3 p-3 p-md-4" style="background-color: #f0fdf4; border-left: 4px solid #16a34a !important;">
        <i class="bi bi-shield-check text-success fs-3 flex-shrink-0"></i>
        <div>
            <h6 class="fw-bold text-success mb-1">Privacidad y Confidencialidad Garantizada</h6>
            <p class="text-muted small mb-0">
                Tus sesiones de relajación y minutos invertidos son estrictamente confidenciales. Esta información es de tu uso personal y <strong>no se comparte</strong> con docentes, tutores ni personal académico.
            </p>
        </div>
    </div>

    <!-- KPIs Personales -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Sesiones Completadas</div>
                        <div class="h3 fw-bold text-slate-800 mb-0">{{ $totalSesiones }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Minutos Acumulados</div>
                        <div class="h3 fw-bold text-slate-800 mb-0">{{ $minutosTotales }} min</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-heart-pulse fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Compromiso con la Calma</div>
                        <div class="h6 fw-bold text-slate-800 mb-0">Constancia de Autocuidado</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de Sesiones -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom p-3 p-md-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold text-slate-800 mb-0">Registro Cronológico</h5>
            <span class="badge bg-light text-muted border rounded-pill">{{ $sesiones->total() }} registros</span>
        </div>
        <div class="card-body p-0">
            @if($sesiones->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-clock fs-1 opacity-50 d-block mb-3"></i>
                    <h6 class="fw-bold text-slate-700">Aún no has registrado sesiones de relajación</h6>
                    <p class="small mb-3">Dedica unos minutos hoy mismo a probar un ejercicio de respiración o descanso.</p>
                    <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-primary rounded-pill px-4">
                        Comenzar Ahora
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Fecha y Hora</th>
                                <th>Ejercicio</th>
                                <th>Categoría</th>
                                <th>Duración Practicada</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sesiones as $sesion)
                                <tr>
                                    <td class="ps-4 text-nowrap">
                                        <div class="fw-semibold text-slate-800">{{ $sesion->fecha_sesion->translatedFormat('d M Y') }}</div>
                                        <div class="text-muted small">{{ $sesion->fecha_sesion->format('H:i') }} hrs</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-slate-800">{{ $sesion->meditacion?->titulo ?? 'Ejercicio retirado' }}</div>
                                        <div class="text-muted small">{{ $sesion->meditacion?->nivel_label ?? 'General' }}</div>
                                    </td>
                                    <td>
                                        @if($sesion->meditacion)
                                            <span class="badge {{ $sesion->meditacion->categoria_badge }} rounded-pill px-2 py-1 small">
                                                {{ $sesion->meditacion->categoria_label }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary rounded-pill">General</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-slate-700">
                                            {{ floor($sesion->duracion_segundos / 60) }}m {{ $sesion->duracion_segundos % 60 }}s
                                        </span>
                                    </td>
                                    <td>
                                        @if($sesion->completada)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                                <i class="bi bi-check-circle me-1"></i> Completada
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1 small">
                                                <i class="bi bi-dash-circle me-1"></i> Parcial
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        @if($sesion->meditacion)
                                            <a href="{{ route('alumno.meditaciones.sesion', $sesion->meditacion) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                Repetir
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $sesiones->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

