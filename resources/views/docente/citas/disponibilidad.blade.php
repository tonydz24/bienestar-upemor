@extends('layouts.dashboard')

@section('title', 'Configurar Horarios de Atención - Bienestar UPEMOR')

@push('styles')
<style>
    .disp-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 1.25rem;
        padding: 1.75rem;
        box-shadow: 0 4px 16px -2px rgba(109, 40, 217, 0.05);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="mb-4">
        <a href="{{ route('docente.citas.index') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
            &larr; Volver a la Agenda de Citas
        </a>
        <h1 class="h3 fw-bold text-dark mb-1">Configuración de Horarios de Atención</h1>
        <p class="text-muted small mb-0">
            Define tus bloques semanales disponibles para que los estudiantes de UPEMOR puedan agendar citas de orientación.
        </p>
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

    @if (session('alerta_solapamiento'))
        <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4 mb-4" role="alert">
            <div class="d-flex gap-3 align-items-start">
                <span class="fs-4">⚠️</span>
                <div>
                    <h3 class="h6 fw-bold mb-1 text-dark">Conflicto de Horarios Superpuestos</h3>
                    <p class="small mb-3 text-muted">
                        {{ session('alerta_solapamiento')['mensaje'] }}
                    </p>
                    <form action="{{ route('docente.citas.disponibilidad.store') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="dia_semana" value="{{ session('alerta_solapamiento')['dia_semana'] }}">
                        <input type="hidden" name="hora_inicio" value="{{ session('alerta_solapamiento')['hora_inicio'] }}">
                        <input type="hidden" name="hora_fin" value="{{ session('alerta_solapamiento')['hora_fin'] }}">
                        <input type="hidden" name="duracion_cita_min" value="{{ session('alerta_solapamiento')['duracion_cita_min'] }}">
                        <input type="hidden" name="modalidad" value="{{ session('alerta_solapamiento')['modalidad'] }}">
                        <input type="hidden" name="confirmar_reemplazo" value="1">
                        <input type="hidden" name="reemplazar_id" value="{{ session('alerta_solapamiento')['reemplazar_id'] }}">
                        <button type="submit" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark">
                            Sí, reemplazar horario anterior
                        </button>
                        <a href="{{ route('docente.citas.disponibilidad') }}" class="btn btn-light btn-sm rounded-pill px-3 ms-2">
                            Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger rounded-4 border-0 shadow-xs mb-4">
            <h2 class="h6 fw-bold mb-2">Por favor revisa los siguientes errores:</h2>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- Formulario para agregar nuevo horario -->
        <div class="col-lg-5">
            <div class="disp-card mb-4">
                <h2 class="h6 fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>Agregar bloque de atención</span>
                </h2>

                <form action="{{ route('docente.citas.disponibilidad.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="dia_semana" class="form-label small fw-semibold text-dark">Día de la semana:</label>
                        <select name="dia_semana" id="dia_semana" class="form-select rounded-3 form-select-sm" required>
                            <option value="">-- Selecciona el día --</option>
                            <option value="1" {{ old('dia_semana') == '1' ? 'selected' : '' }}>Lunes</option>
                            <option value="2" {{ old('dia_semana') == '2' ? 'selected' : '' }}>Martes</option>
                            <option value="3" {{ old('dia_semana') == '3' ? 'selected' : '' }}>Miércoles</option>
                            <option value="4" {{ old('dia_semana') == '4' ? 'selected' : '' }}>Jueves</option>
                            <option value="5" {{ old('dia_semana') == '5' ? 'selected' : '' }}>Viernes</option>
                            <option value="6" {{ old('dia_semana') == '6' ? 'selected' : '' }}>Sábado</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="hora_inicio" class="form-label small fw-semibold text-dark">Hora inicio:</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" class="form-control rounded-3 form-control-sm" value="{{ old('hora_inicio', '09:00') }}" required>
                        </div>
                        <div class="col-6">
                            <label for="hora_fin" class="form-label small fw-semibold text-dark">Hora fin:</label>
                            <input type="time" name="hora_fin" id="hora_fin" class="form-control rounded-3 form-control-sm" value="{{ old('hora_fin', '13:00') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="duracion_cita_min" class="form-label small fw-semibold text-dark">Duración por cita:</label>
                        <select name="duracion_cita_min" id="duracion_cita_min" class="form-select rounded-3 form-select-sm" required>
                            <option value="30" {{ old('duracion_cita_min') == '30' ? 'selected' : '' }}>30 minutos</option>
                            <option value="45" {{ old('duracion_cita_min', '45') == '45' ? 'selected' : '' }}>45 minutos (Recomendado)</option>
                            <option value="60" {{ old('duracion_cita_min') == '60' ? 'selected' : '' }}>60 minutos</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="modalidad" class="form-label small fw-semibold text-dark">Modalidad permitida:</label>
                        <select name="modalidad" id="modalidad" class="form-select rounded-3 form-select-sm" required>
                            <option value="AMBAS" {{ old('modalidad', 'AMBAS') === 'AMBAS' ? 'selected' : '' }}>Ambas (Presencial o Virtual)</option>
                            <option value="PRESENCIAL" {{ old('modalidad') === 'PRESENCIAL' ? 'selected' : '' }}>Solo Presencial</option>
                            <option value="VIRTUAL" {{ old('modalidad') === 'VIRTUAL' ? 'selected' : '' }}>Solo Virtual</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-portal-primary w-100 rounded-pill py-2 fw-semibold">
                        Guardar horario de atención
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de horarios configurados agrupados por día -->
        <div class="col-lg-7">
            <div class="disp-card">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div>
                        <h2 class="h6 fw-bold text-dark mb-0">Horarios semanales activos</h2>
                        <span class="text-muted small">Agrupados por día de atención</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 small fw-bold">
                        {{ $disponibilidades->count() }} bloque(s) activo(s)
                    </span>
                </div>

                @if ($disponibilidades->isEmpty())
                    <div class="text-center py-5">
                        <p class="text-muted small mb-0">Aún no has configurado ningún bloque de disponibilidad horaria.</p>
                        <p class="text-muted small">Agrega al menos un bloque para que los alumnos puedan agendar citas contigo.</p>
                    </div>
                @else
                    @php
                        $porDia = $disponibilidades->groupBy('dia_semana')->sortKeys();
                    @endphp

                    <div class="d-flex flex-column gap-3">
                        @foreach ($porDia as $diaNum => $bloques)
                            @php
                                $primerBloque = $bloques->first();
                                $nombreDia = $primerBloque->nombre_dia;
                            @endphp
                            <div class="border rounded-4 p-3 bg-light-subtle">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h3 class="h6 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-1" style="font-size: 0.75rem;">
                                            {{ $nombreDia }}
                                        </span>
                                        <span class="text-muted small fw-normal">
                                            ({{ $bloques->count() }} {{ $bloques->count() === 1 ? 'bloque' : 'bloques' }})
                                        </span>
                                    </h3>
                                </div>

                                <div class="list-group list-group-flush rounded-3 border">
                                    @foreach ($bloques as $d)
                                        <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3 bg-white">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="fw-semibold text-dark small">
                                                    🕐 {{ substr($d->hora_inicio, 0, 5) }} – {{ substr($d->hora_fin, 0, 5) }} hrs
                                                </div>
                                                <div class="text-muted small">
                                                    ({{ $d->duracion_cita_min }} min)
                                                </div>
                                                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                                                    {{ $d->modalidad }}
                                                </span>
                                            </div>
                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalEliminarDisp{{ $d->disponibilidad_id }}">
                                                Eliminar
                                            </button>

                                            <!-- Modal Confirmar Eliminación Bloque -->
                                            <div class="modal fade" id="modalEliminarDisp{{ $d->disponibilidad_id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-4 border-0 shadow">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold text-danger">Eliminar bloque de horario</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <p class="text-muted small mb-0">
                                                                ¿Confirmas que deseas eliminar el bloque de <strong>{{ substr($d->hora_inicio, 0, 5) }} a {{ substr($d->hora_fin, 0, 5) }} hrs</strong>? Si cuenta con citas activas vinculadas, el sistema protegerá la agenda del estudiante y rechazará la acción.
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                            <form action="{{ route('docente.citas.disponibilidad.destroy', $d->disponibilidad_id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">Eliminar bloque</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
            </div>
        </div>
    </div>
</div>
@endsection

