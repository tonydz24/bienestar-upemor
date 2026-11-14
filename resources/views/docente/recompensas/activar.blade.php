@extends('layouts.dashboard')

@section('title', 'Habilitar Recompensa - Bienestar UPEMOR')

@push('styles')
<style>
    .form-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 1.25rem;
        padding: 2rem;
        box-shadow: 0 4px 18px -2px rgba(109, 40, 217, 0.06);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="mb-4">
        <a href="{{ route('docente.academico.index') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
            &larr; Volver a Mis Asignaturas
        </a>
        <h1 class="h3 fw-bold text-dark mb-1">Habilitar Recompensa o Beneficio Académico</h1>
        <p class="text-muted small mb-0">
            Asignatura: <strong>{{ $asignacion->asignatura->nombre }}</strong> &bull; Grupo: <strong>{{ $asignacion->grupo->cuatrimestre }}° "{{ $asignacion->grupo->nombre }}"</strong>
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-4 border-0 shadow-xs mb-4">
            <h2 class="h6 fw-bold mb-2">Por favor corrige los siguientes campos:</h2>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="form-card">
                <form action="{{ route('docente.recompensas.store', $asignacion->asignacion_id) }}" method="POST">
                    @csrf

                    <!-- Selección del beneficio institucional -->
                    <div class="mb-4">
                        <label for="recompensa_id" class="form-label small fw-semibold text-dark">Beneficio del catálogo institucional:</label>
                        <select name="recompensa_id" id="recompensa_id" class="form-select rounded-3 py-2" required>
                            <option value="">-- Selecciona un beneficio institucional --</option>
                            @foreach ($catalogo as $cat)
                                @php
                                    $yaHabilitada = isset($habilitadasIds[$cat->recompensa_id]);
                                @endphp
                                <option value="{{ $cat->recompensa_id }}" 
                                    {{ old('recompensa_id') == $cat->recompensa_id ? 'selected' : '' }} 
                                    data-costo="{{ $cat->costo_puntos_base }}"
                                    data-activa="{{ $yaHabilitada ? '1' : '0' }}"
                                    data-activacion-id="{{ $yaHabilitada ? $habilitadasIds[$cat->recompensa_id] : '' }}">
                                    [{{ $cat->categoria }}] {{ $cat->nombre }} (Costo base: {{ $cat->costo_puntos_base }} pts) {{ $yaHabilitada ? '⚠️ (Ya habilitado)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-muted small d-block mt-1" style="font-size: 0.78rem;">
                            Solo se muestran beneficios autorizados en el catálogo de Bienestar UPEMOR.
                        </span>
                        <div id="alertaYaHabilitada" class="alert alert-warning border-0 rounded-3 mt-2 py-2 px-3 small d-none">
                            <span>Este beneficio ya se encuentra habilitado en esta materia y grupo.</span>
                            <a id="btnIrEditar" href="#" class="fw-bold ms-2 text-dark text-decoration-underline">Editar configuración existente &rarr;</a>
                        </div>
                    </div>

                    <!-- Cupo y Costo de Puntos -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="cupo_maximo" class="form-label small fw-semibold text-dark">Cupo máximo disponible:</label>
                            <input type="number" name="cupo_maximo" id="cupo_maximo" class="form-control rounded-3 py-2" min="1" max="50" value="{{ old('cupo_maximo', 5) }}" required>
                            <span class="text-muted small" style="font-size: 0.75rem;">Número máximo de canjes permitidos en el grupo.</span>
                        </div>
                        <div class="col-md-6">
                            <label for="costo_puntos" class="form-label small fw-semibold text-dark">Costo en puntos:</label>
                            <input type="number" name="costo_puntos" id="costo_puntos" class="form-control rounded-3 py-2" min="0" max="500" value="{{ old('costo_puntos', 10) }}" required>
                            <span class="text-muted small" style="font-size: 0.75rem;">Puntos requeridos por el alumno para solicitar el canje.</span>
                        </div>
                    </div>

                    <!-- Vigencia Inicio y Fin -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="vigencia_inicio" class="form-label small fw-semibold text-dark">Fecha de inicio de vigencia:</label>
                            <input type="date" name="vigencia_inicio" id="vigencia_inicio" class="form-control rounded-3 py-2" value="{{ old('vigencia_inicio', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="vigencia_fin" class="form-label small fw-semibold text-dark">Fecha límite de vigencia:</label>
                            <input type="date" name="vigencia_fin" id="vigencia_fin" class="form-control rounded-3 py-2" value="{{ old('vigencia_fin', now()->addMonths(2)->toDateString()) }}" required>
                        </div>
                    </div>

                    <!-- Condiciones específicas del docente -->
                    <div class="mb-4">
                        <label for="condiciones_docente" class="form-label small fw-semibold text-dark">Condiciones o acuerdos específicos del docente (opcional):</label>
                        <textarea name="condiciones_docente" id="condiciones_docente" rows="3" class="form-control rounded-3 small" placeholder="Ej. Aplica únicamente en entregas de prácticas de laboratorio regulares; no acumulable con otras prórrogas.">{{ old('condiciones_docente') }}</textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="{{ route('docente.academico.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-portal-primary rounded-pill px-4 py-2 fw-semibold">
                            Habilitar recompensa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card">
                <h2 class="h6 fw-bold text-dark mb-2">Acerca del Programa de Incentivos</h2>
                <p class="text-muted small mb-3">
                    Las recompensas permiten motivar la participación positiva, la constancia y el cuidado personal de los estudiantes en un marco estrictamente académico.
                </p>
                <div class="p-3 rounded-3 bg-light text-muted small">
                    <div class="fw-semibold text-dark mb-1">Importante:</div>
                    Los beneficios nunca sustituyen los criterios formales de acreditación ni alteran arbitrariamente actas oficiales de calificación.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('recompensa_id');
        const costoInput = document.getElementById('costo_puntos');
        const alertYa = document.getElementById('alertaYaHabilitada');
        const btnEdit = document.getElementById('btnIrEditar');

        function actualizarSeleccion() {
            const opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) {
                alertYa.classList.add('d-none');
                return;
            }

            if (opt.dataset.costo && (!costoInput.value || costoInput.value == 10)) {
                costoInput.value = opt.dataset.costo;
            }

            if (opt.dataset.activa === '1') {
                alertYa.classList.remove('d-none');
                btnEdit.href = '/docente/recompensas/' + opt.dataset.activacionId + '/editar';
            } else {
                alertYa.classList.add('d-none');
            }
        }

        select.addEventListener('change', actualizarSeleccion);
        actualizarSeleccion();
    });
</script>
@endpush

