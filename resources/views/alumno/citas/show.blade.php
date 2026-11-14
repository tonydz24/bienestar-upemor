@extends('layouts.dashboard')

@section('title', 'Detalle de Cita de Orientación - Bienestar UPEMOR')

@push('styles')
<style>
    .detalle-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.16);
        border-radius: 1.25rem;
        padding: 2rem;
        box-shadow: 0 4px 18px -2px rgba(109, 40, 217, 0.06);
    }
    .badge-cita-solicitada {
        background-color: #fef3c7 !important;
        color: #78350f !important;
        border: 1px solid #fde68a !important;
        font-weight: 700;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
    }
    .badge-cita-confirmada {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
        font-weight: 700;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
    }
    .badge-cita-finalizada {
        background-color: #ede9fe !important;
        color: #4c1d95 !important;
        border: 1px solid #ddd6fe !important;
        font-weight: 700;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
    }
    .badge-cita-cancelada {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #fecaca !important;
        font-weight: 700;
        padding: 0.4rem 0.85rem;
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
    <div class="mb-4">
        <a href="{{ route('alumno.citas.index') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
            &larr; Volver a Mis Citas
        </a>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Detalle de la Cita de Orientación</h1>
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

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="detalle-card mb-4">
                <!-- Información Principal de Fecha y Horario -->
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

                <!-- Estado y Notificaciones Contextuales -->
                @if ($cita->tieneReprogramacionPendiente())
                    <div class="alert alert-warning border-0 rounded-4 p-3 mb-4 shadow-xs">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="fs-4">📅</span>
                            <div class="w-100">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                                    <h3 class="h6 fw-bold mb-0 text-dark">Solicitud de Reprogramación en Proceso</h3>
                                    <span class="badge bg-warning text-dark px-2 py-1 rounded-pill small">Pendiente de Aprobación</span>
                                </div>
                                <p class="small mb-2 text-dark">
                                    Has solicitado cambiar esta sesión para el <strong>{{ \Carbon\Carbon::parse($cita->reprogramacion_fecha)->translatedFormat('l, d \d\e F \d\e Y') }}</strong> en horario <strong>{{ substr($cita->reprogramacion_hora_inicio, 0, 5) }} - {{ substr($cita->reprogramacion_hora_fin, 0, 5) }} hrs</strong>.
                                    Tu cita actual sigue reservada hasta que el orientador valide y apruebe la nueva fecha.
                                </p>
                                @if ($cita->reprogramacion_motivo)
                                    <div class="small bg-white p-2 rounded-3 border mb-2 text-muted">
                                        <strong>Motivo indicado:</strong> {{ $cita->reprogramacion_motivo }}
                                    </div>
                                @endif
                                <form action="{{ route('alumno.citas.cancelar-reprogramacion', $cita->cita_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="return confirm('¿Deseas desistir de la solicitud de cambio de fecha? Tu cita original se mantendrá intacta.');">
                                        Desistir de la solicitud de cambio
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @elseif ($cita->reprogramacion_estado === 'RECHAZADA')
                    <div class="alert alert-secondary border-0 rounded-4 p-3 mb-4">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="fs-4">ℹ</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Solicitud de cambio no aprobada</h3>
                                <p class="small mb-1">El orientador no pudo autorizar la reprogramación solicitada. Tu cita original sigue confirmada para la fecha programada.</p>
                                @if($cita->reprogramacion_respuesta_motivo)
                                    <p class="small text-muted mb-0"><strong>Motivo del orientador:</strong> {{ $cita->reprogramacion_respuesta_motivo }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($cita->reprogramacion_estado === 'APROBADA')
                    <div class="alert alert-info border-0 rounded-4 p-3 mb-4">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="fs-4">✨</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Cita reprogramada exitosamente</h3>
                                <p class="small mb-0">
                                    Esta cita fue reprogramada con autorización del orientador. Fecha anterior: {{ $cita->fecha_cita_anterior ? \Carbon\Carbon::parse($cita->fecha_cita_anterior)->format('d/m/Y') : 'previa' }} ({{ substr($cita->hora_inicio_anterior, 0, 5) }} hrs).
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($cita->estado === 'SOLICITADA')
                    <div class="alert alert-warning border-0 rounded-4 p-3 mb-4">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="fs-4">⏳</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Solicitud en revisión</h3>
                                <p class="small mb-0">
                                    Tu solicitud fue enviada el {{ $cita->fecha_solicitud->translatedFormat('d M Y, H:i') }} hrs. El orientador revisará su disponibilidad para confirmar el espacio. Puedes cancelar esta solicitud en cualquier momento antes de la fecha programada.
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif ($cita->estado === 'CONFIRMADA')
                    <div class="alert alert-success border-0 rounded-4 p-3 mb-4">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="fs-4">✅</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Cita confirmada</h3>
                                <p class="small mb-0">
                                    El orientador ha confirmado tu sesión. Por favor acude con 5 minutos de anticipación al lugar indicado o ingresa al enlace correspondiente.
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif (in_array($cita->estado, ['RECHAZADA', 'CANCELADA_ALUMNO', 'CANCELADA_PROFESIONAL', 'NO_ASISTIO']))
                    <div class="alert alert-danger border-0 rounded-4 p-3 mb-4">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="fs-4">ℹ</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Estado: {{ $cita->estado_label }}</h3>
                                @if ($cita->motivo_cancelacion_rechazo)
                                    <p class="small mb-0">
                                        <strong>Motivo registrado:</strong> {{ $cita->motivo_cancelacion_rechazo }}
                                    </p>
                                @endif
                                @if ($cita->fecha_resolucion)
                                    <p class="small text-muted mt-1 mb-0">
                                        Fecha de resolución: {{ $cita->fecha_resolucion->translatedFormat('d M Y, H:i') }} hrs.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($cita->estado === 'FINALIZADA')
                    <div class="alert alert-info border-0 rounded-4 p-3 mb-4">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="fs-4">🎉</span>
                            <div class="w-100">
                                <h3 class="h6 fw-bold mb-1">Sesión completada</h3>
                                <p class="small mb-2">
                                    Esta cita fue concluida satisfactoriamente. Esperamos que el acompañamiento haya sido de utilidad para tu desarrollo integral.
                                </p>
                                <a href="{{ route('alumno.encuestas.post-cita', $cita) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-star me-1"></i> Evaluar Atención de Orientación
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Ubicación o Enlace -->
                <div class="mb-4">
                    <h2 class="h6 fw-bold text-dark mb-2">Lugar o Enlace de Acceso:</h2>
                    <div class="p-3 rounded-3 bg-light text-dark">
                        @if ($cita->lugar_o_enlace)
                            <div class="d-flex align-items-center gap-2">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="text-primary flex-shrink-0">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span>{{ $cita->lugar_o_enlace }}</span>
                            </div>
                        @else
                            <span class="text-muted small">Por definir al momento de la confirmación.</span>
                        @endif
                    </div>
                </div>

                <!-- Motivo de consulta registrado por el alumno -->
                @if ($cita->motivo_consulta)
                    <div class="mb-4">
                        <h2 class="h6 fw-bold text-dark mb-2">Tu nota / motivo de orientación:</h2>
                        <div class="p-3 rounded-3 bg-light text-dark small fst-italic">
                            "{{ $cita->motivo_consulta }}"
                        </div>
                    </div>
                @endif

                <!-- Acciones -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top">
                    <a href="{{ route('alumno.citas.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
                        &larr; Volver al listado
                    </a>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if ($cita->puedeSolicitarReprogramacion())
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reprogramarModal">
                                <i class="bi bi-calendar2-range me-1"></i> Solicitar reprogramación
                            </button>
                        @endif

                        @if ($cita->puedeSerCanceladaPorAlumno())
                            <button type="button" class="btn btn-danger btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                Cancelar esta cita
                            </button>
                        @endif
                    </div>

                    @if ($cita->puedeSolicitarReprogramacion())
                        <!-- Modal de Reprogramación -->
                        <div class="modal fade" id="reprogramarModal" tabindex="-1" aria-labelledby="reprogramarModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0 shadow">
                                    <form action="{{ route('alumno.citas.reprogramar', $cita->cita_id) }}" method="POST" id="formReprogramarCita">
                                        @csrf
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-bold" id="reprogramarModalLabel">Solicitar cambio de horario</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <p class="text-muted small mb-3">
                                                Selecciona una nueva fecha y horario disponible con <strong>{{ $cita->profesional->nombre_completo }}</strong>. Tu cita actual permanecerá activa hasta que el orientador valide y confirme el cambio.
                                            </p>
                                            
                                            <div class="mb-3">
                                                <label for="inputNuevaFecha" class="form-label small fw-semibold">Nueva Fecha deseada:</label>
                                                <input type="date" name="nueva_fecha" id="inputNuevaFecha" class="form-control form-control-sm rounded-3" min="{{ now()->toDateString() }}" value="{{ now()->addDay()->toDateString() }}" required onchange="consultarHorariosReprog()">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold d-block">Horarios Disponibles:</label>
                                                <div id="reprogSpinner" class="text-center py-2 d-none">
                                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                    <span class="small text-muted ms-2">Buscando horarios...</span>
                                                </div>
                                                <div id="reprogSlotsContenedor" class="d-flex flex-wrap gap-2"></div>
                                                <div id="reprogMensajeVacio" class="small text-danger mt-2 d-none">No hay horarios libres en esa fecha. Por favor elige otro día.</div>
                                                <input type="hidden" name="nueva_hora_inicio" id="inputNuevaHoraInicio" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="reprog_motivo" class="form-label small fw-semibold">Motivo del cambio (opcional):</label>
                                                <textarea name="motivo" id="reprog_motivo" rows="2" class="form-control form-control-sm rounded-3" placeholder="Ej. Cruce de examen departamental..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4" id="btnSubmitReprog" disabled>Enviar Solicitud</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <script>
                            async function consultarHorariosReprog() {
                                const profId = {{ $cita->profesional_id }};
                                const fecha = document.getElementById('inputNuevaFecha').value;
                                const spinner = document.getElementById('reprogSpinner');
                                const contenedor = document.getElementById('reprogSlotsContenedor');
                                const mensajeVacio = document.getElementById('reprogMensajeVacio');
                                const inputHora = document.getElementById('inputNuevaHoraInicio');
                                const btnSubmit = document.getElementById('btnSubmitReprog');

                                if (!fecha) return;

                                spinner.classList.remove('d-none');
                                contenedor.innerHTML = '';
                                mensajeVacio.classList.add('d-none');
                                btnSubmit.disabled = true;
                                inputHora.value = '';

                                try {
                                    const resp = await fetch(`{{ route('alumno.citas.horarios-disponibles') }}?profesional_id=${profId}&fecha=${fecha}`, {
                                        headers: { 'Accept': 'application/json' }
                                    });
                                    const data = await resp.json();
                                    spinner.classList.add('d-none');

                                    if (!data.success || !data.slots || data.slots.length === 0) {
                                        mensajeVacio.classList.remove('d-none');
                                        return;
                                    }

                                    let libres = 0;
                                    data.slots.forEach(slot => {
                                        const btn = document.createElement('button');
                                        btn.type = 'button';
                                        btn.className = `btn btn-sm ${slot.disponible ? 'btn-outline-primary' : 'btn-outline-secondary disabled'}`;
                                        btn.textContent = `${slot.hora_inicio} - ${slot.hora_fin}`;
                                        if (slot.disponible) {
                                            libres++;
                                            btn.onclick = () => {
                                                contenedor.querySelectorAll('button').forEach(b => b.classList.remove('active', 'btn-primary'));
                                                btn.classList.add('active', 'btn-primary');
                                                btn.classList.remove('btn-outline-primary');
                                                inputHora.value = slot.hora_inicio;
                                                btnSubmit.disabled = false;
                                            };
                                        } else {
                                            btn.disabled = true;
                                        }
                                        contenedor.appendChild(btn);
                                    });

                                    if (libres === 0) {
                                        mensajeVacio.classList.remove('d-none');
                                    }
                                } catch (e) {
                                    spinner.classList.add('d-none');
                                    mensajeVacio.textContent = 'Error al consultar horarios disponibles.';
                                    mensajeVacio.classList.remove('d-none');
                                }
                            }
                            document.addEventListener('DOMContentLoaded', () => {
                                const modalReprog = document.getElementById('reprogramarModal');
                                if (modalReprog) {
                                    modalReprog.addEventListener('shown.bs.modal', () => {
                                        consultarHorariosReprog();
                                    });
                                }
                            });
                        </script>
                    @endif

                    @if ($cita->puedeSerCanceladaPorAlumno())
                        <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0 shadow">
                                    <form action="{{ route('alumno.citas.cancelar', $cita->cita_id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-bold" id="cancelModalLabel">¿Deseas cancelar esta cita?</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <p class="text-muted small">
                                                Al confirmar la cancelación, el horario quedará inmediatamente disponible para otros compañeros.
                                            </p>
                                            <div class="mb-3">
                                                <label for="motivo_cancelacion" class="form-label small fw-semibold">Motivo (opcional):</label>
                                                <textarea name="motivo" id="motivo_cancelacion" rows="2" class="form-control form-control-sm rounded-3" placeholder="Ej. Cambio de horario escolar..."></textarea>
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

        <!-- Columna Lateral: Datos del Orientador -->
        <div class="col-lg-4">
            <div class="detalle-card">
                <h2 class="h6 fw-bold text-dark mb-3">Orientador asignado</h2>
                <div class="text-center py-2 mb-3">
                    <div class="p-3 rounded-circle bg-primary-subtle text-primary d-inline-flex mb-2" style="width: 64px; height: 64px; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="fw-bold text-dark">{{ $cita->profesional->nombre_completo }}</div>
                    <div class="text-muted small">{{ $cita->profesional->cargo }}</div>
                </div>

                <div class="border-top pt-3 text-start small">
                    @if ($cita->profesional->cedula_profesional)
                        <div class="d-flex justify-content-between py-1 text-muted">
                            <span>Cédula:</span>
                            <span class="text-dark">{{ $cita->profesional->cedula_profesional }}</span>
                        </div>
                    @endif
                    @if ($cita->profesional->lugar_atencion)
                        <div class="d-flex justify-content-between py-1 text-muted">
                            <span>Ubicación habitual:</span>
                            <span class="text-dark">{{ $cita->profesional->lugar_atencion }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Modalidades:</span>
                        <span class="text-dark">{{ $cita->profesional->modalidades_permitidas }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

