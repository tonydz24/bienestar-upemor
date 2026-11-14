@extends('layouts.dashboard')

@section('title', 'Solicitar Cita de Orientación - Bienestar UPEMOR')

@push('styles')
<style>
    .solicitar-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 1.25rem;
        padding: 1.75rem;
        box-shadow: 0 4px 16px -2px rgba(109, 40, 217, 0.05);
    }
    .prof-option-card {
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8fafc;
    }
    .prof-option-card:hover {
        border-color: #a78bfa;
        background: #ffffff;
    }
    .prof-option-card.selected {
        border-color: #7c3aed;
        background: #f5f3ff;
        box-shadow: 0 0 0 1px #7c3aed;
    }
    .slot-pill {
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        padding: 0.6rem 1rem;
        font-weight: 600;
        font-size: 0.9rem;
        background: #ffffff;
        color: #334155;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }
    .slot-pill:hover:not(.disabled) {
        border-color: #7c3aed;
        color: #7c3aed;
        background: #f5f3ff;
    }
    .slot-pill.active {
        background: #7c3aed;
        color: #ffffff;
        border-color: #7c3aed;
        box-shadow: 0 4px 10px rgba(124, 58, 237, 0.25);
    }
    .slot-pill.disabled {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
        cursor: not-allowed;
        text-decoration: line-through;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="mb-4">
        <a href="{{ route('alumno.citas.index') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
            &larr; Volver a Mis Citas
        </a>
        <h1 class="h3 fw-bold text-dark mb-1">Solicitar Cita de Orientación</h1>
        <p class="text-muted small mb-0">Selecciona el orientador, fecha y horario de tu preferencia para tu sesión individual.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-4 border-0 shadow-xs mb-4">
            <h2 class="h6 fw-bold mb-2">Por favor corrige los siguientes detalles:</h2>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('alumno.citas.store') }}" method="POST" id="formSolicitarCita">
        @csrf
        <input type="hidden" name="profesional_id" id="inputProfesionalId" value="{{ old('profesional_id', $profesionalSeleccionadoId ?: ($profesionales->first()?->profesional_id ?? '')) }}">
        <input type="hidden" name="hora_inicio" id="inputHoraInicio" value="{{ old('hora_inicio') }}">

        <div class="row g-4">
            <!-- Columna Izquierda: Orientador y Fecha -->
            <div class="col-lg-7">
                <!-- Paso 1: Orientador -->
                <div class="solicitar-card mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary text-white rounded-circle p-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">1</span>
                        <h2 class="h6 fw-bold text-dark mb-0">Selecciona el profesional u orientador</h2>
                    </div>

                    @if ($profesionales->isEmpty())
                        <div class="alert alert-warning rounded-3 border-0 small mb-0">
                            Actualmente no hay orientadores con agenda disponible. Por favor consulta más tarde.
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach ($profesionales as $prof)
                                <div class="col-12">
                                    <div class="prof-option-card @if((string)old('profesional_id', $profesionalSeleccionadoId) === (string)$prof->profesional_id || (!$profesionalSeleccionadoId && $loop->first && !old('profesional_id'))) selected @endif"
                                         data-prof-id="{{ $prof->profesional_id }}"
                                         data-modalidades="{{ $prof->modalidades_permitidas }}"
                                         data-lugar="{{ $prof->lugar_atencion ?: 'Cubículo institucional' }}"
                                         onclick="seleccionarOrientador(this, {{ $prof->profesional_id }})">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="p-2 rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                                        <circle cx="12" cy="7" r="4"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $prof->nombre_completo }}</div>
                                                    <div class="text-muted small">{{ $prof->cargo }}</div>
                                                    @if ($prof->lugar_atencion)
                                                        <div class="text-muted small mt-1" style="font-size: 0.78rem;">
                                                            📍 {{ $prof->lugar_atencion }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                                    {{ $prof->modalidades_permitidas === 'AMBAS' ? 'Presencial y Virtual' : ($prof->modalidades_permitidas === 'PRESENCIAL' ? 'Solo Presencial' : 'Solo Virtual') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Paso 2: Fecha y Horario -->
                <div class="solicitar-card mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary text-white rounded-circle p-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">2</span>
                        <h2 class="h6 fw-bold text-dark mb-0">Selecciona la fecha y el horario deseado</h2>
                    </div>

                    <div class="mb-4">
                        <label for="inputFecha" class="form-label small fw-semibold text-dark">Fecha de la cita:</label>
                        <input type="date" 
                               name="fecha_cita" 
                               id="inputFecha" 
                               class="form-control rounded-3 py-2" 
                               min="{{ now()->toDateString() }}" 
                               max="{{ now()->addDays(30)->toDateString() }}"
                               value="{{ old('fecha_cita', now()->addDay()->isWeekend() ? now()->addDays(3)->toDateString() : now()->addDay()->toDateString()) }}"
                               onchange="consultarHorarios()">
                        <span class="text-muted small">Citas disponibles de lunes a viernes, hasta con 30 días de anticipación.</span>
                    </div>

                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-semibold text-dark">Horarios disponibles:</span>
                            <span id="labelDiaSeleccionado" class="text-muted small"></span>
                        </div>

                        <!-- Spinner de carga -->
                        <div id="spinnerCargandoHorarios" class="text-center py-4 d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <span class="text-muted small ms-2">Buscando horarios disponibles...</span>
                        </div>

                        <!-- Contenedor de slots dinámicos -->
                        <div id="contenedorSlots" class="d-flex flex-wrap gap-2 py-2">
                            <!-- Inyectado vía JavaScript -->
                        </div>

                        <div id="mensajeSinHorarios" class="alert alert-light border rounded-3 p-3 text-center small text-muted d-none mb-0">
                            El profesional no tiene horarios de atención programados para este día. Por favor elige otra fecha.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Modalidad, Motivo y Confirmación -->
            <div class="col-lg-5">
                <div class="solicitar-card mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary text-white rounded-circle p-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">3</span>
                        <h2 class="h6 fw-bold text-dark mb-0">Modalidad de atención</h2>
                    </div>

                    <div class="mb-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="modalidad" id="modPresencial" value="PRESENCIAL" {{ old('modalidad', 'PRESENCIAL') === 'PRESENCIAL' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary w-100 py-2 rounded-3 text-start small" for="modPresencial">
                                    <div class="fw-bold">🏛 Presencial</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">En cubículo institucional</div>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="modalidad" id="modVirtual" value="VIRTUAL" {{ old('modalidad') === 'VIRTUAL' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary w-100 py-2 rounded-3 text-start small" for="modVirtual">
                                    <div class="fw-bold">💻 Virtual</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Vía videoconferencia</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Motivo opcional -->
                    <div class="mb-4">
                        <label for="motivo_consulta" class="form-label small fw-semibold text-dark">
                            Motivo general de consulta <span class="text-muted fw-normal">(opcional)</span>:
                        </label>
                        <textarea name="motivo_consulta" 
                                  id="motivo_consulta" 
                                  rows="3" 
                                  class="form-control rounded-3 small" 
                                  maxlength="1000"
                                  placeholder="Describe brevemente el tema o inquietud que te gustaría abordar (ej. técnicas de estudio, orientación vocacional, apoyo emocional)...">{{ old('motivo_consulta') }}</textarea>
                        <div class="d-flex justify-content-between text-muted small mt-1">
                            <span>Información estrictamente confidencial.</span>
                            <span id="charCount">0/1000</span>
                        </div>
                    </div>

                    <!-- Resumen antes de enviar -->
                    <div class="p-3 rounded-3 bg-light mb-4">
                        <div class="small fw-semibold text-dark mb-2">Resumen de la cita:</div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Horario seleccionado:</span>
                            <span class="fw-bold text-dark" id="resumenHorario">Ninguno seleccionado</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Duración aproximada:</span>
                            <span class="text-dark">45 minutos</span>
                        </div>
                    </div>

                    <button type="submit" id="btnConfirmarSolicitud" class="btn btn-portal-primary w-100 py-2 fw-semibold rounded-pill" disabled>
                        Confirmar solicitud de cita
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const urlHorarios = "{{ route('alumno.citas.horarios-disponibles') }}";

    function seleccionarOrientador(card, profId) {
        document.querySelectorAll('.prof-option-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        document.getElementById('inputProfesionalId').value = profId;

        // Ajustar modalidades disponibles
        const modalidades = card.dataset.modalidades;
        const radPresencial = document.getElementById('modPresencial');
        const radVirtual = document.getElementById('modVirtual');

        if (modalidades === 'PRESENCIAL') {
            radPresencial.checked = true;
            radPresencial.disabled = false;
            radVirtual.disabled = true;
        } else if (modalidades === 'VIRTUAL') {
            radVirtual.checked = true;
            radVirtual.disabled = false;
            radPresencial.disabled = true;
        } else {
            radPresencial.disabled = false;
            radVirtual.disabled = false;
        }

        consultarHorarios();
    }

    async function consultarHorarios() {
        const profId = document.getElementById('inputProfesionalId').value;
        const fecha = document.getElementById('inputFecha').value;
        const spinner = document.getElementById('spinnerCargandoHorarios');
        const contenedor = document.getElementById('contenedorSlots');
        const mensajeVacio = document.getElementById('mensajeSinHorarios');
        const labelDia = document.getElementById('labelDiaSeleccionado');
        const btnSubmit = document.getElementById('btnConfirmarSolicitud');
        const inputHoraInicio = document.getElementById('inputHoraInicio');
        const resumenHorario = document.getElementById('resumenHorario');

        if (!profId || !fecha) return;

        spinner.classList.remove('d-none');
        contenedor.innerHTML = '';
        mensajeVacio.classList.add('d-none');
        btnSubmit.disabled = true;
        inputHoraInicio.value = '';
        resumenHorario.textContent = 'Ninguno seleccionado';

        try {
            const resp = await fetch(`${urlHorarios}?profesional_id=${profId}&fecha=${fecha}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json();

            spinner.classList.add('d-none');

            if (!data.success || !data.slots || data.slots.length === 0) {
                mensajeVacio.classList.remove('d-none');
                labelDia.textContent = '';
                return;
            }

            labelDia.textContent = data.dia_nombre || '';

            let hayDisponibles = false;

            data.slots.forEach(slot => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `slot-pill ${slot.disponible ? '' : 'disabled'}`;
                btn.textContent = `${slot.hora_inicio} - ${slot.hora_fin}`;
                
                if (slot.disponible) {
                    hayDisponibles = true;
                    btn.onclick = () => {
                        document.querySelectorAll('.slot-pill').forEach(s => s.classList.remove('active'));
                        btn.classList.add('active');
                        inputHoraInicio.value = slot.hora_inicio;
                        resumenHorario.textContent = `${slot.hora_inicio} - ${slot.hora_fin} hrs (${fecha})`;
                        btnSubmit.disabled = false;
                    };
                } else {
                    btn.title = slot.motivo_no_disponible || 'Horario no disponible';
                    btn.disabled = true;
                }

                contenedor.appendChild(btn);
            });

            if (!hayDisponibles) {
                mensajeVacio.textContent = 'Todos los horarios de este orientador ya han sido reservados para la fecha seleccionada. Por favor elige otro día.';
                mensajeVacio.classList.remove('d-none');
            }

        } catch (e) {
            spinner.classList.add('d-none');
            mensajeVacio.textContent = 'Error al consultar horarios. Por favor verifica tu conexión.';
            mensajeVacio.classList.remove('d-none');
        }
    }

    // Contador de caracteres para el motivo
    const txtMotivo = document.getElementById('motivo_consulta');
    const charCounter = document.getElementById('charCount');
    if (txtMotivo && charCounter) {
        txtMotivo.addEventListener('input', () => {
            charCounter.textContent = `${txtMotivo.value.length}/1000`;
        });
    }

    // Inicializar carga de horarios al entrar
    document.addEventListener('DOMContentLoaded', () => {
        consultarHorarios();
    });
</script>
@endpush
@endsection

