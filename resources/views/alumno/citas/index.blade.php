@extends('layouts.dashboard')

@section('title', 'Citas de Orientación - Bienestar UPEMOR')

@push('styles')
<style>
    .cita-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.16);
        border-radius: 1.25rem;
        padding: 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 16px -2px rgba(109, 40, 217, 0.05);
    }
    .cita-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px -4px rgba(109, 40, 217, 0.12);
    }
    .badge-cita-solicitada {
        background-color: #fef3c7 !important;
        color: #78350f !important;
        border: 1px solid #fde68a !important;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .badge-cita-confirmada {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .badge-cita-finalizada {
        background-color: #ede9fe !important;
        color: #4c1d95 !important;
        border: 1px solid #ddd6fe !important;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .badge-cita-cancelada {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #fecaca !important;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .badge-modalidad-presencial {
        background: #f1f5f9 !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
        font-weight: 600;
        font-size: 0.78rem;
    }
    .badge-modalidad-virtual {
        background: #e0f2fe !important;
        color: #0c4a6e !important;
        border: 1px solid #7dd3fc !important;
        font-weight: 600;
        font-size: 0.78rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Encabezado del Módulo -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('alumno.dashboard') }}" class="text-decoration-none text-muted small">
                    &larr; Volver al panel principal
                </a>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Citas de Orientación y Acompañamiento</h1>
            <p class="text-muted small mb-0">
                Agenda sesiones individuales y confidenciales con los orientadores de Bienestar UPEMOR para tu bienestar integral.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('alumno.citas.create') }}" class="btn btn-portal-primary px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span class="fw-semibold">Solicitar nueva cita</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-xs border-0 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-success flex-shrink-0">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-xs border-0 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-danger flex-shrink-0">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- SECCIÓN 1: Citas Activas / Próximas -->
    <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Tus próximas citas programadas</span>
            </h2>
            <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                {{ $citasProximas->count() }} activa(s)
            </span>
        </div>

        @if ($citasProximas->isEmpty())
            <div class="cita-card text-center py-5">
                <div class="p-3 rounded-circle bg-primary-subtle text-primary d-inline-flex mb-3" style="width: 64px; height: 64px; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <h3 class="h6 fw-bold text-dark mb-1">No tienes citas activas programadas</h3>
                <p class="text-muted small mb-3 max-w-md mx-auto" style="max-width: 460px;">
                    Si necesitas orientación académica, apoyo vocacional o un espacio de escucha para tu bienestar, puedes reservar una sesión con nuestros profesionales.
                </p>
                <a href="{{ route('alumno.citas.create') }}" class="btn btn-portal-primary btn-sm px-4 rounded-pill">
                    Solicitar una cita ahora
                </a>
            </div>
        @else
            <div class="row g-3">
                @foreach ($citasProximas as $cita)
                    <div class="col-md-6 col-lg-6">
                        <div class="cita-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="{{ $cita->estado_badge_class }}">
                                            {{ $cita->estado_label }}
                                        </span>
                                        @if ($cita->tieneReprogramacionPendiente())
                                            <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small">
                                                Reprogramación pendiente
                                            </span>
                                        @endif
                                    </div>
                                    <span class="badge {{ $cita->modalidad === 'PRESENCIAL' ? 'badge-modalidad-presencial' : 'badge-modalidad-virtual' }} rounded-pill px-2 py-1">
                                        {{ $cita->modalidad === 'PRESENCIAL' ? '🏛 Presencial' : '💻 Virtual' }}
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="p-2 rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="h6 fw-bold text-dark mb-0">{{ $cita->profesional->nombre_completo }}</h3>
                                        <p class="text-muted small mb-0">{{ $cita->profesional->cargo }}</p>
                                    </div>
                                </div>

                                <div class="p-3 rounded-3 bg-light mb-3">
                                    <div class="d-flex align-items-center gap-2 text-dark fw-medium small mb-1">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                        </svg>
                                        <span>{{ \Carbon\Carbon::parse($cita->fecha_cita)->translatedFormat('l, d \d\e F \d\e Y') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-muted small">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                        <span>{{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }} hrs</span>
                                    </div>
                                    @if ($cita->lugar_o_enlace)
                                        <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span class="text-truncate">{{ $cita->lugar_o_enlace }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between gap-2 pt-2 border-top">
                                <a href="{{ route('alumno.citas.show', $cita->cita_id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    Ver detalles
                                </a>

                                @if ($cita->puedeSerCanceladaPorAlumno())
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cancelModal-{{ $cita->cita_id }}">
                                        Cancelar cita
                                    </button>

                                    <!-- Modal de confirmación de cancelación -->
                                    <div class="modal fade" id="cancelModal-{{ $cita->cita_id }}" tabindex="-1" aria-labelledby="cancelModalLabel-{{ $cita->cita_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="{{ route('alumno.citas.cancelar', $cita->cita_id) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold" id="cancelModalLabel-{{ $cita->cita_id }}">¿Deseas cancelar esta cita?</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <p class="text-muted small">
                                                            Al cancelar, tu lugar quedará liberado inmediatamente para que otro estudiante pueda aprovecharlo.
                                                        </p>
                                                        <div class="mb-3">
                                                            <label for="motivo-{{ $cita->cita_id }}" class="form-label small fw-semibold">Motivo de cancelación (opcional):</label>
                                                            <textarea name="motivo" id="motivo-{{ $cita->cita_id }}" rows="2" class="form-control form-control-sm rounded-3" placeholder="Ej. Cruce de horario con un examen, imprevisto personal..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">Confirmar cancelación</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- SECCIÓN 2: Historial de Citas Previas -->
    <div class="mb-5">
        <h2 class="h5 fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Historial de citas anteriores</span>
        </h2>

        @if ($citasHistorial->isEmpty())
            <div class="cita-card text-center py-4">
                <p class="text-muted small mb-0">Aún no cuentas con registros en tu historial de citas previas.</p>
            </div>
        @else
            <div class="cita-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-4 py-3 small text-uppercase text-muted fw-semibold">Fecha y Hora</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Orientador</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Modalidad</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Estado</th>
                                <th class="border-0 pe-4 py-3 small text-uppercase text-muted fw-semibold text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($citasHistorial as $hist)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold text-dark small">
                                            {{ \Carbon\Carbon::parse($hist->fecha_cita)->translatedFormat('d M, Y') }}
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.78rem;">
                                            {{ substr($hist->hora_inicio, 0, 5) }} - {{ substr($hist->hora_fin, 0, 5) }} hrs
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="fw-medium text-dark small">{{ $hist->profesional->nombre_completo }}</div>
                                        <div class="text-muted small" style="font-size: 0.78rem;">{{ $hist->profesional->cargo }}</div>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge {{ $hist->modalidad === 'PRESENCIAL' ? 'badge-modalidad-presencial' : 'badge-modalidad-virtual' }} rounded-pill px-2 py-1">
                                            {{ $hist->modalidad }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <span class="{{ $hist->estado_badge_class }}">
                                            {{ $hist->estado_label }}
                                        </span>
                                        @if ($hist->motivo_cancelacion_rechazo)
                                            <div class="text-muted mt-1 small" style="font-size: 0.75rem; max-width: 220px;" title="{{ $hist->motivo_cancelacion_rechazo }}">
                                                <em>{{ Str::limit($hist->motivo_cancelacion_rechazo, 45) }}</em>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="pe-4 py-3 text-end">
                                        <a href="{{ route('alumno.citas.show', $hist->cita_id) }}" class="btn btn-light btn-sm rounded-pill px-3" style="font-size: 0.8rem;">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($citasHistorial->hasPages())
                    <div class="p-3 border-top">
                        {{ $citasHistorial->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Banner de confidencialidad institucional -->
    <div class="p-3 p-md-4 rounded-4 bg-primary-subtle border border-primary-subtle">
        <div class="d-flex gap-3 align-items-center">
            <div class="p-2 rounded-circle bg-white text-primary flex-shrink-0" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <div>
                <h4 class="h6 fw-bold text-dark mb-1">Acompañamiento seguro y confidencial</h4>
                <p class="text-muted small mb-0">
                    Tu privacidad es un principio rector de Bienestar UPEMOR. Las citas y orientaciones no forman parte de tu historial académico, ni son visibles para profesores u otros estudiantes.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

