@extends('layouts.dashboard')

@section('title', 'Gestión de Citas de Orientación - Bienestar UPEMOR')

@push('styles')
<style>
    .gestion-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.16);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 16px -2px rgba(109, 40, 217, 0.05);
    }
    .badge-cita-solicitada {
        background-color: #fef3c7 !important;
        color: #78350f !important;
        border: 1px solid #fde68a !important;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
    }
    .badge-cita-confirmada {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
    }
    .badge-cita-finalizada {
        background-color: #ede9fe !important;
        color: #4c1d95 !important;
        border: 1px solid #ddd6fe !important;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
    }
    .badge-cita-cancelada {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #fecaca !important;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
    }
    .badge-modalidad-presencial {
        background: #f1f5f9 !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
        font-weight: 600;
    }
    .badge-modalidad-virtual {
        background: #e0f2fe !important;
        color: #0c4a6e !important;
        border: 1px solid #7dd3fc !important;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Encabezado del Módulo Orientador -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('docente.dashboard') }}" class="text-decoration-none text-muted small">
                    &larr; Volver al panel de docente
                </a>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Agenda de Citas de Orientación</h1>
            <p class="text-muted small mb-0">
                Orientador: <strong>{{ $profesional->nombre_completo }}</strong> &bull; {{ $profesional->cargo }}
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('docente.citas.disponibilidad') }}" class="btn btn-outline-primary rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span class="fw-semibold">Configurar mi horario semanal</span>
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

    <!-- Navegación por pestañas -->
    <ul class="nav nav-pills gap-2 mb-4">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 @if($tab === 'solicitadas') active @endif" href="{{ route('docente.citas.index', ['tab' => 'solicitadas']) }}">
                Solicitudes pendientes
                @if ($solicitadas->count() > 0)
                    <span class="badge bg-warning text-dark rounded-pill ms-2">{{ $solicitadas->count() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 @if($tab === 'confirmadas') active @endif" href="{{ route('docente.citas.index', ['tab' => 'confirmadas']) }}">
                Citas confirmadas
                @if ($confirmadas->count() > 0)
                    <span class="badge bg-success rounded-pill ms-2">{{ $confirmadas->count() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 @if($tab === 'historial') active @endif" href="{{ route('docente.citas.index', ['tab' => 'historial']) }}">
                Historial general
            </a>
        </li>
    </ul>

    <!-- Contenido de las pestañas -->
    @if ($tab === 'solicitadas')
        <!-- PESTAÑA: SOLICITUDES PENDIENTES -->
        @if ($solicitadas->isEmpty())
            <div class="gestion-card text-center py-5">
                <div class="p-3 rounded-circle bg-light d-inline-flex mb-3" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h3 class="h6 fw-bold text-dark mb-1">No hay solicitudes pendientes</h3>
                <p class="text-muted small mb-0">Todas las solicitudes de los alumnos han sido resueltas oportunamente.</p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($solicitadas as $cita)
                    <div class="col-md-6 col-xl-6">
                        <div class="gestion-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="{{ $cita->estado_badge_class }}">
                                            {{ $cita->estado_label }}
                                        </span>
                                        @if ($cita->tieneReprogramacionPendiente())
                                            <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small">
                                                Reprogramación solicitada
                                            </span>
                                        @endif
                                    </div>
                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
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
                                        <div class="fw-bold text-dark">
                                            {{ $cita->estudianteUsuario->nombre }} {{ $cita->estudianteUsuario->apellido_paterno }} {{ $cita->estudianteUsuario->apellido_materno }}
                                        </div>
                                        <div class="text-muted small">
                                            Matrícula: {{ $cita->estudianteUsuario->estudiante?->matricula ?? 'S/M' }} &bull; 
                                            {{ $cita->estudianteUsuario->estudiante?->programa?->nombre ?? 'UPEMOR' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 rounded-3 bg-light mb-3 small">
                                    <div class="d-flex align-items-center gap-2 fw-semibold text-dark mb-1">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                        </svg>
                                        <span>{{ \Carbon\Carbon::parse($cita->fecha_cita)->translatedFormat('l, d \d\e F \d\e Y') }}</span>
                                    </div>
                                    <div class="text-muted">
                                        Horario: {{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }} hrs
                                    </div>
                                    @if ($cita->motivo_consulta)
                                        <div class="mt-2 text-muted fst-italic">
                                            "{{ Str::limit($cita->motivo_consulta, 120) }}"
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between gap-2 pt-3 border-top">
                                <a href="{{ route('docente.citas.show', $cita->cita_id) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                    Ver detalle
                                </a>

                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalRechazar-{{ $cita->cita_id }}">
                                        Rechazar
                                    </button>
                                    <button type="button" class="btn btn-portal-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalConfirmar-{{ $cita->cita_id }}">
                                        Confirmar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Confirmar -->
                    <div class="modal fade" id="modalConfirmar-{{ $cita->cita_id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('docente.citas.confirmar', $cita->cita_id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Confirmar cita con {{ $cita->estudianteUsuario->nombre }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <p class="text-muted small">
                                            Fecha: <strong>{{ \Carbon\Carbon::parse($cita->fecha_cita)->translatedFormat('d M, Y') }}</strong> &bull; {{ substr($cita->hora_inicio, 0, 5) }} hrs.
                                        </p>
                                        <div class="mb-3">
                                            <label for="lugar-{{ $cita->cita_id }}" class="form-label small fw-semibold">Cubículo o enlace de atención:</label>
                                            <input type="text" name="lugar_o_enlace" id="lugar-{{ $cita->cita_id }}" class="form-control rounded-3 form-control-sm" value="{{ $cita->lugar_o_enlace }}">
                                            <span class="text-muted small" style="font-size: 0.75rem;">Visible para el alumno en su panel de citas.</span>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">Confirmar cita</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Rechazar -->
                    <div class="modal fade" id="modalRechazar-{{ $cita->cita_id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('docente.citas.rechazar', $cita->cita_id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Rechazar solicitud de cita</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <p class="text-muted small">
                                            Indica al estudiante el motivo por el cual no es posible aceptar esta cita para que pueda seleccionar otra fecha.
                                        </p>
                                        <div class="mb-3">
                                            <label for="motivo-rechazo-{{ $cita->cita_id }}" class="form-label small fw-semibold">Motivo del rechazo <span class="text-danger">*</span>:</label>
                                            <textarea name="motivo" id="motivo-rechazo-{{ $cita->cita_id }}" rows="2" class="form-control rounded-3 form-control-sm" required placeholder="Ej. Compromiso institucional imprevisto, favor de agendar otro día..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">Rechazar solicitud</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @elseif ($tab === 'confirmadas')
        <!-- PESTAÑA: CONFIRMADAS PRÓXIMAS -->
        @if ($confirmadas->isEmpty())
            <div class="gestion-card text-center py-5">
                <div class="p-3 rounded-circle bg-light d-inline-flex mb-3" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <h3 class="h6 fw-bold text-dark mb-1">No tienes citas confirmadas próximas</h3>
                <p class="text-muted small mb-0">Las citas confirmadas aparecerán aquí organizadas por fecha y hora.</p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($confirmadas as $cita)
                    <div class="col-md-6 col-xl-6">
                        <div class="gestion-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="{{ $cita->estado_badge_class }}">
                                            {{ $cita->estado_label }}
                                        </span>
                                        @if ($cita->tieneReprogramacionPendiente())
                                            <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small">
                                                Reprogramación solicitada
                                            </span>
                                        @endif
                                    </div>
                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                        {{ $cita->modalidad === 'PRESENCIAL' ? '🏛 Presencial' : '💻 Virtual' }}
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="p-2 rounded-circle bg-success-subtle text-success flex-shrink-0" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            {{ $cita->estudianteUsuario->nombre }} {{ $cita->estudianteUsuario->apellido_paterno }} {{ $cita->estudianteUsuario->apellido_materno }}
                                        </div>
                                        <div class="text-muted small">
                                            Matrícula: {{ $cita->estudianteUsuario->estudiante?->matricula ?? 'S/M' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 rounded-3 bg-light mb-3 small">
                                    <div class="d-flex align-items-center gap-2 fw-semibold text-dark mb-1">
                                        <span>📅 {{ \Carbon\Carbon::parse($cita->fecha_cita)->translatedFormat('l, d \d\e F \d\e Y') }}</span>
                                    </div>
                                    <div class="text-muted">
                                        ⏰ {{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }} hrs
                                    </div>
                                    <div class="text-muted mt-1">
                                        📍 {{ $cita->lugar_o_enlace ?: 'Lugar no especificado' }}
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between gap-2 pt-3 border-top">
                                <a href="{{ route('docente.citas.show', $cita->cita_id) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                    Ver gestión
                                </a>

                                <form action="{{ route('docente.citas.finalizar', $cita->cita_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">
                                        Marcar completada
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @else
        <!-- PESTAÑA: HISTORIAL -->
        @if ($historial->isEmpty())
            <div class="gestion-card text-center py-5">
                <p class="text-muted small mb-0">No hay registros de citas en el historial.</p>
            </div>
        @else
            <div class="gestion-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-4 py-3 small text-uppercase text-muted fw-semibold">Estudiante</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Fecha y Hora</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Modalidad</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Estado</th>
                                <th class="border-0 pe-4 py-3 small text-uppercase text-muted fw-semibold text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($historial as $h)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold text-dark small">
                                            {{ $h->estudianteUsuario->nombre }} {{ $h->estudianteUsuario->apellido_paterno }}
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.78rem;">
                                            {{ $h->estudianteUsuario->estudiante?->matricula ?? 'S/M' }}
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="small text-dark fw-medium">
                                            {{ \Carbon\Carbon::parse($h->fecha_cita)->translatedFormat('d M, Y') }}
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.78rem;">
                                            {{ substr($h->hora_inicio, 0, 5) }} - {{ substr($h->hora_fin, 0, 5) }} hrs
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                            {{ $h->modalidad }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <span class="{{ $h->estado_badge_class }}">
                                            {{ $h->estado_label }}
                                        </span>
                                    </td>
                                    <td class="pe-4 py-3 text-end">
                                        <a href="{{ route('docente.citas.show', $h->cita_id) }}" class="btn btn-light btn-sm rounded-pill px-3" style="font-size: 0.8rem;">
                                            Detalle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($historial->hasPages())
                    <div class="p-3 border-top">
                        {{ $historial->links() }}
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection

