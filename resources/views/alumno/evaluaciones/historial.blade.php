@extends('layouts.dashboard')

@section('title', 'Mi Historial de Evaluaciones - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Encabezado Principal -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('alumno.evaluaciones') }}" class="text-decoration-none text-muted small">
                    &larr; Catálogo de Evaluaciones
                </a>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Mi Historial de Evaluaciones</h1>
            <p class="text-muted small mb-0">
                Consulta tus intentos, fechas de aplicación y resultados de forma estrictamente confidencial.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('alumno.evaluaciones') }}" class="btn btn-portal-primary px-3">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Catálogo de evaluaciones</span>
            </a>
        </div>
    </div>

    <!-- Tarjetas de Resumen Rápido -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <div class="dashboard-section-card p-3 p-md-4 h-100 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase small fw-semibold d-block mb-1">Total de Intentos</span>
                        <span class="h3 fw-bold text-dark mb-0">{{ $totalFinalizados + $totalEnProgreso }}</span>
                    </div>
                    <div class="p-3 rounded-circle bg-primary-subtle text-primary" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="dashboard-section-card p-3 p-md-4 h-100 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase small fw-semibold d-block mb-1">Finalizadas</span>
                        <span class="h3 fw-bold text-success mb-0">{{ $totalFinalizados }}</span>
                    </div>
                    <div class="p-3 rounded-circle bg-success-subtle text-success" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="dashboard-section-card p-3 p-md-4 h-100 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase small fw-semibold d-block mb-1">En Progreso</span>
                        <span class="h3 fw-bold text-warning mb-0">{{ $totalEnProgreso }}</span>
                    </div>
                    <div class="p-3 rounded-circle bg-warning-subtle text-warning" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado del Historial -->
    <div class="dashboard-section-card p-0 shadow-sm overflow-hidden mb-4">
        @if ($intentos->isEmpty())
            <!-- Estado Vacío -->
            <div class="p-5 text-center">
                <div class="mx-auto mb-3 d-inline-flex p-3 rounded-circle" style="background: var(--bienestar-lavender-soft);">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="var(--bienestar-primary)" stroke-width="1.8">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <circle cx="12" cy="14" r="2"/>
                    </svg>
                </div>
                <h2 class="h5 fw-bold text-dark mb-2">Aún no tienes evaluaciones registradas</h2>
                <p class="text-muted small mb-4 mx-auto" style="max-width: 440px;">
                    Cuando comiences a contestar instrumentos de bienestar o hábitos de estudio, tus intentos y resultados quedarán archivados aquí de manera confidencial.
                </p>
                <a href="{{ route('alumno.evaluaciones') }}" class="btn btn-portal-primary px-4">
                    Explorar cuestionarios disponibles &rarr;
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th scope="col" class="ps-4 py-3">Instrumento</th>
                            <th scope="col" class="py-3">Fecha de aplicación</th>
                            <th scope="col" class="py-3">Estado</th>
                            <th scope="col" class="py-3">Resultado</th>
                            <th scope="col" class="py-3">Disponibilidad</th>
                            <th scope="col" class="pe-4 py-3 text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($intentos as $intento)
                            @php
                                $instrumento = $intento->version?->instrumento;
                                $version = $intento->version;
                            @endphp
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <strong class="d-block text-dark">{{ $instrumento->nombre ?? 'Evaluación' }}</strong>
                                            <span class="text-muted small">
                                                {{ $instrumento->codigo ?? 'N/D' }} &bull; v{{ $version->version ?? '1.0' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    @if ($intento->fecha_finalizacion)
                                        <span class="text-dark d-block">
                                            {{ $intento->fecha_finalizacion->translatedFormat('d \d\e M. \d\e Y') }}
                                        </span>
                                        <span class="text-muted small">
                                            {{ $intento->fecha_finalizacion->format('H:i') }} hrs
                                        </span>
                                    @else
                                        <span class="text-muted">Iniciado {{ $intento->fecha_inicio?->diffForHumans() ?? 'recientemente' }}</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if ($intento->estaFinalizado())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                                            Finalizado
                                        </span>
                                    @elseif ($intento->estaEnProgreso())
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small">
                                            En progreso
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border px-2 py-1 rounded-pill small">
                                            {{ $intento->estado }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if ($intento->estaFinalizado())
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-dark">{{ $intento->puntuacion_total ?? 0 }} pts</span>
                                            @if ($intento->nivel_resultado)
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small" style="font-size: 0.76rem;">
                                                    {{ $intento->nivel_resultado }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">Pendiente de finalizar</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if ($intento->estaFinalizado())
                                        @if ($intento->reaplicacion_disponible)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                                                Disponible ahora
                                            </span>
                                        @elseif ($intento->dias_restantes_texto)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">
                                                Disponible en {{ $intento->dias_restantes_texto }}
                                            </span>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">Intento activo</span>
                                    @endif
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    @if ($intento->estaFinalizado())
                                        <a href="{{ route('alumno.evaluaciones.finalizado', $intento->intento_id) }}" class="btn btn-sm btn-outline-primary px-3">
                                            Ver detalle &rarr;
                                        </a>
                                    @elseif ($intento->estaEnProgreso())
                                        <a href="{{ route('alumno.evaluaciones.cuestionario', $intento->intento_id) }}" class="btn btn-sm btn-warning px-3 fw-semibold">
                                            Continuar &rarr;
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($intentos->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $intentos->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Aviso de Confidencialidad Institucional -->
    <div class="alert alert-light border border-primary-subtle rounded-4 p-3 p-md-4">
        <div class="d-flex gap-2 align-items-start small text-muted">
            <span class="text-primary fs-5" aria-hidden="true">🔒</span>
            <div>
                <strong>Privacidad de tus datos:</strong> Las respuestas y registros almacenados en este historial están bajo resguardo institucional de confidencialidad. Únicamente tú tienes acceso a consultar el detalle de tus evaluaciones.
            </div>
        </div>
    </div>
</div>
@endsection

