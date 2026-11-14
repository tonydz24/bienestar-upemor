@extends('layouts.dashboard')

@section('title', 'Gestión de Cita - Bienestar UPEMOR')

@push('styles')
<style>
    .show-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 1.25rem;
        padding: 2rem;
        box-shadow: 0 4px 18px -2px rgba(109, 40, 217, 0.06);
    }
    .badge-cita-solicitada {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        font-weight: 600;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
    }
    .badge-cita-confirmada {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        font-weight: 600;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
    }
    .badge-cita-finalizada {
        background-color: #ede9fe;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
        font-weight: 600;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
    }
    .badge-cita-cancelada {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
        font-weight: 600;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="mb-4">
        <a href="{{ route('docente.citas.index') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
            &larr; Volver a la Agenda de Citas
        </a>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Gestión de Cita de Orientación</h1>
                <p class="text-muted small mb-0">Folio: #CIT-{{ str_pad($cita->cita_id, 5, '0', STR_PAD_LEFT) }}</p>
            </div>
            <span class="{{ $cita->estado_badge_class }}">
                {{ $cita->estado_label }}
            </span>
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

    <div class="row g-4">
        <!-- Columna Principal -->
        <div class="col-lg-8">
            <div class="show-card mb-4">
                <!-- Horario y Modalidad -->
                <div class="p-3 rounded-4 bg-primary-subtle text-primary mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-circle bg-white shadow-xs">
                            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 text-dark">
                                {{ \Carbon\Carbon::parse($cita->fecha_cita)->translatedFormat('l, d \d\e F \d\e Y') }}
                            </div>
                            <div class="text-muted small">
                                Horario: <strong>{{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }} hrs</strong>
                            </div>
                        </div>
                    </div>
                    <span class="badge bg-white text-dark border px-3 py-2 rounded-pill fw-semibold">
                        {{ $cita->modalidad === 'PRESENCIAL' ? '🏛 Presencial' : '💻 Virtual' }}
                    </span>
                </div>

                <!-- Lugar o Enlace -->
                <div class="mb-4">
                    <h2 class="h6 fw-bold text-dark mb-2">Lugar o Enlace de Atención:</h2>
                    <div class="p-3 rounded-3 bg-light text-dark">
                        <div class="d-flex align-items-center gap-2">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="text-primary flex-shrink-0">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span>{{ $cita->lugar_o_enlace ?: 'No especificado' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Motivo de consulta registrado por el alumno -->
                <div class="mb-4">
                    <h2 class="h6 fw-bold text-dark mb-2">Motivo expresado por el estudiante:</h2>
                    <div class="p-3 rounded-3 bg-light text-dark small">
                        @if ($cita->motivo_consulta)
                            <div class="fst-italic">"{{ $cita->motivo_consulta }}"</div>
                        @else
                            <span class="text-muted">El estudiante no incluyó notas adicionales al momento de solicitar la cita.</span>
                        @endif
                    </div>
                </div>

                <!-- Si la cita fue cancelada o rechazada, mostrar motivo -->
                @if ($cita->motivo_cancelacion_rechazo)
                    <div class="alert alert-danger rounded-4 p-3 mb-4">
                        <h3 class="h6 fw-bold mb-1">Motivo de resolución / cancelación:</h3>
                        <p class="small mb-0">{{ $cita->motivo_cancelacion_rechazo }}</p>
                    </div>
                @endif

                @if ($cita->tieneReprogramacionPendiente())
                    <!-- Banner de Solicitud de Reprogramación Pendiente -->
                    <div class="alert alert-warning border-2 rounded-4 p-4 mb-4 shadow-xs">
                        <div class="d-flex align-items-start gap-3">
                            <span class="fs-2">📅</span>
                            <div class="w-100">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <h3 class="h6 fw-bold mb-0 text-dark">
                                        Solicitud de Reprogramación Recibida
                                    </h3>
                                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-semibold">Requiere tu Resolución</span>
                                </div>
                                <p class="small mb-2 text-dark">
                                    El estudiante ha solicitado trasladar su cita al <strong>{{ \Carbon\Carbon::parse($cita->reprogramacion_fecha)->translatedFormat('l, d \d\e F \d\e Y') }}</strong> en el horario <strong>{{ substr($cita->reprogramacion_hora_inicio, 0, 5) }} - {{ substr($cita->reprogramacion_hora_fin, 0, 5) }} hrs</strong>.
                                    <br>
                                    <em>Nota de concurrencia: Al aprobar, el sistema re-validará la disponibilidad en tiempo real bajo bloqueo transaccional.</em>
                                </p>
                                @if ($cita->reprogramacion_motivo)
                                    <div class="small bg-white p-2 rounded-3 border mb-3 text-muted">
                                        <strong>Motivo indicado por el estudiante:</strong> "{{ $cita->reprogramacion_motivo }}"
                                    </div>
                                @endif
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <form action="{{ route('docente.citas.reprogramacion.aprobar', $cita->cita_id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-semibold" onclick="return confirm('¿Aprobar y re-validar la reprogramación para el {{ $cita->reprogramacion_fecha }}?');">
                                            ✓ Aprobar Reprogramación
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalRechazarReprogramacion">
                                        ✕ Rechazar con Motivo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($cita->reprogramacion_estado === 'APROBADA')
                    <div class="alert alert-info rounded-4 p-3 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-5">✨</span>
                            <div class="small">
                                <strong>Cita reprogramada previamente:</strong> Originalmente agendada para el {{ $cita->fecha_cita_anterior ? \Carbon\Carbon::parse($cita->fecha_cita_anterior)->format('d/m/Y') : 'previa' }} a las {{ substr($cita->hora_inicio_anterior, 0, 5) }} hrs.
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Barra de Acciones según Estado -->
                <div class="pt-3 border-top">
                    @if ($cita->estado === 'SOLICITADA')
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <span class="small text-muted">¿Qué deseas realizar con esta solicitud?</span>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalRechazar">
                                    Rechazar solicitud
                                </button>
                                <button type="button" class="btn btn-success btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalConfirmar">
                                    Confirmar cita
                                </button>
                            </div>
                        </div>

                    @elseif ($cita->estado === 'CONFIRMADA')
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCancelar">
                                Cancelar cita
                            </button>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalNoAsistio">
                                    No asistió
                                </button>
                                <form action="{{ route('docente.citas.finalizar', $cita->cita_id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-4">
                                        Marcar como completada
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center justify-content-between text-muted small">
                            <span>Esta cita se encuentra en estado definitivo: <strong>{{ $cita->estado_label }}</strong></span>
                            @if ($cita->fecha_resolucion)
                                <span>Resuelta: {{ $cita->fecha_resolucion->translatedFormat('d/m/Y H:i') }} hrs</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @if(Auth::user()->esOrientador())
                <!-- Sección Confidencial: Tamizajes Psicoemocionales (FN.3) -->
                <div class="show-card mb-4 border-primary-subtle" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="h6 fw-bold text-dark mb-0">Tamizajes Psicoemocionales del Estudiante</h3>
                                <p class="text-muted small mb-0">Vista clínica confidencial exclusiva para orientadores con consentimiento informado firmado.</p>
                            </div>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill small">
                            Acompañamiento Profesional
                        </span>
                    </div>

                    @if(isset($evaluacionesEstudiante) && $evaluacionesEstudiante->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="border-0">Instrumento</th>
                                        <th class="border-0 text-center">Puntaje</th>
                                        <th class="border-0">Nivel / Interpretación</th>
                                        <th class="border-0 text-center">Alerta Crítica</th>
                                        <th class="border-0 text-end">Fecha de Aplicación</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @foreach($evaluacionesEstudiante as $eval)
                                        <tr>
                                            <td class="fw-semibold text-dark">
                                                {{ $eval->version?->instrumento?->nombre ?? 'Evaluación Psicológica' }}
                                                <span class="d-block text-muted" style="font-size: 0.72rem;">
                                                    Código: {{ $eval->version?->instrumento?->codigo ?? 'N/D' }} &bull; Versión {{ $eval->version?->version ?? 1 }}
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold text-dark">
                                                {{ $eval->puntuacion_total }} pts
                                            </td>
                                            <td>
                                                <span class="badge @if(str_contains(strtolower($eval->nivel_resultado ?? ''), 'severo') || str_contains(strtolower($eval->nivel_resultado ?? ''), 'alto')) bg-danger-subtle text-danger border border-danger-subtle @elseif(str_contains(strtolower($eval->nivel_resultado ?? ''), 'moderado')) bg-warning-subtle text-warning-emphasis border border-warning-subtle @else bg-success-subtle text-success-emphasis border border-success-subtle @endif rounded-pill px-2 py-1">
                                                    {{ $eval->nivel_resultado ?: 'Completada' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($eval->tiene_alerta_critica)
                                                    <span class="badge bg-danger text-white rounded-pill px-2 py-1 fw-bold" title="Reactivo de riesgo positivo detectado en el instrumento">
                                                        ⚠️ Alerta Activa
                                                    </span>
                                                @else
                                                    <span class="text-muted small">Sin alerta</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-muted">
                                                {{ $eval->fecha_finalizacion ? $eval->fecha_finalizacion->format('d/m/Y H:i') : $eval->created_at->format('d/m/Y') }}
                                                <span class="d-block text-success" style="font-size: 0.70rem;">
                                                    <i class="bi bi-shield-check"></i> Consentimiento firmado
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-3 rounded-3 bg-light text-muted small text-center">
                            El estudiante aún no ha completado tamizajes psicoemocionales o no ha otorgado consentimiento explícito para compartir los resultados en la consulta.
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Columna Lateral: Ficha del Estudiante -->
        <div class="col-lg-4">
            <div class="show-card mb-4">
                <h2 class="h6 fw-bold text-dark mb-3">Información del Estudiante</h2>
                <div class="text-center py-2 mb-3">
                    <div class="p-3 rounded-circle bg-primary-subtle text-primary d-inline-flex mb-2" style="width: 64px; height: 64px; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="fw-bold text-dark fs-6">
                        {{ $cita->estudianteUsuario->nombre }} {{ $cita->estudianteUsuario->apellido_paterno }} {{ $cita->estudianteUsuario->apellido_materno }}
                    </div>
                    <div class="text-muted small">{{ $cita->estudianteUsuario->email }}</div>
                </div>

                <div class="border-top pt-3 text-start small">
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Matrícula:</span>
                        <span class="text-dark fw-semibold">{{ $cita->estudianteUsuario->estudiante?->matricula ?? 'No registrada' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Programa educativo:</span>
                        <span class="text-dark">{{ $cita->estudianteUsuario->estudiante?->programa?->nombre ?? 'UPEMOR' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Cuatrimestre / Grupo:</span>
                        <span class="text-dark">
                            {{ $cita->estudianteUsuario->estudiante?->cuatrimestre ? $cita->estudianteUsuario->estudiante->cuatrimestre . '°' : 'N/D' }} 
                            {{ $cita->estudianteUsuario->estudiante?->grupo ? '"' . $cita->estudianteUsuario->estudiante->grupo . '"' : '' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Recordatorio ético -->
            <div class="p-3 rounded-4 bg-light border text-muted small">
                <div class="fw-semibold text-dark mb-1">Confidencialidad Profesional</div>
                Toda la información tratada en las sesiones de orientación estudiantil es confidencial y de uso exclusivo para el acompañamiento y bienestar del estudiante.
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmar Cita -->
<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('docente.citas.confirmar', $cita->cita_id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Confirmar cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-muted small">
                        Confirma la fecha y asigna o ajusta el cubículo / enlace virtual para que el estudiante pueda acudir puntualmente.
                    </p>
                    <div class="mb-3">
                        <label for="lugar_modal" class="form-label small fw-semibold">Ubicación o enlace:</label>
                        <input type="text" name="lugar_o_enlace" id="lugar_modal" class="form-control rounded-3" value="{{ $cita->lugar_o_enlace }}">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-4">Confirmar cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rechazar Solicitud -->
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-hidden="true">
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
                        Indica el motivo por el cual no es posible atender esta solicitud. El horario se liberará de inmediato.
                    </p>
                    <div class="mb-3">
                        <label for="motivo_rechazo" class="form-label small fw-semibold">Motivo <span class="text-danger">*</span>:</label>
                        <textarea name="motivo" id="motivo_rechazo" rows="2" class="form-control rounded-3" required placeholder="Ej. Solapamiento con reunión institucional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">Rechazar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cancelar Cita Activa -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('docente.citas.cancelar', $cita->cita_id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Cancelar cita programada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-muted small">
                        Explica al estudiante la causa de la cancelación para que pueda reagendar su espacio.
                    </p>
                    <div class="mb-3">
                        <label for="motivo_cancelar" class="form-label small fw-semibold">Motivo de cancelación <span class="text-danger">*</span>:</label>
                        <textarea name="motivo" id="motivo_cancelar" rows="2" class="form-control rounded-3" required placeholder="Ej. Incapacidad médica, imprevisto de fuerza mayor..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">Cancelar cita</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal No Asistió -->
<div class="modal fade" id="modalNoAsistio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('docente.citas.no-asistio', $cita->cita_id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Registrar inasistencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-muted small">
                        ¿Confirmas que el estudiante no acudió a la cita programada? El estado de la cita cambiará a inasistencia y el horario quedará registrado para efectos estadísticos.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-secondary btn-sm rounded-pill px-4">Confirmar inasistencia</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Rechazar Reprogramación -->
<div class="modal fade" id="modalRechazarReprogramacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('docente.citas.reprogramacion.rechazar', $cita->cita_id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Rechazar reprogramación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-muted small">
                        Indica al estudiante el motivo por el cual no es posible aceptar la reprogramación solicitada. La cita original programada se mantendrá intacta.
                    </p>
                    <div class="mb-3">
                        <label for="motivo_rechazo_reprog" class="form-label small fw-semibold">Motivo de rechazo <span class="text-danger">*</span>:</label>
                        <textarea name="motivo" id="motivo_rechazo_reprog" rows="3" class="form-control rounded-3" required placeholder="Ej. El horario solicitado coincide con una reunión académica institucional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">Confirmar rechazo</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

