@extends('layouts.dashboard')

@section('title', 'Editar Configuración de Beneficio - Bienestar UPEMOR')

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
        <a href="{{ route('docente.recompensas.index', ['tab' => 'activadas']) }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
            &larr; Volver a Recompensas Habilitadas
        </a>
        <h1 class="h3 fw-bold text-dark mb-1">Editar Configuración del Beneficio</h1>
        <p class="text-muted small mb-0">
            Beneficio: <strong>{{ $activacion->recompensaCatalogo->nombre }}</strong> &bull; 
            Asignatura: <strong>{{ $activacion->asignacion->asignatura->nombre }}</strong> &bull; 
            Grupo: <strong>{{ $activacion->asignacion->grupo->cuatrimestre }}° "{{ $activacion->asignacion->grupo->nombre }}"</strong>
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
                <form action="{{ route('docente.recompensas.update', $activacion->activacion_id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Estado del beneficio -->
                    <div class="mb-4">
                        <label for="estado" class="form-label small fw-semibold text-dark">Estado operativo del beneficio en este grupo:</label>
                        <select name="estado" id="estado" class="form-select rounded-3 py-2" required>
                            <option value="ACTIVA" {{ old('estado', $activacion->estado) === 'ACTIVA' ? 'selected' : '' }}>
                                ✅ Activo (Disponible para canjes de estudiantes)
                            </option>
                            <option value="SUSPENDIDA" {{ old('estado', $activacion->estado) === 'SUSPENDIDA' ? 'selected' : '' }}>
                                ⏸ Pausado temporalmente (No disponible para nuevos canjes)
                            </option>
                            <option value="FINALIZADA" {{ old('estado', $activacion->estado) === 'FINALIZADA' ? 'selected' : '' }}>
                                ⏹ Concluido / Archivado (Historial preservado)
                            </option>
                        </select>
                    </div>

                    <!-- Cupo y Costo de Puntos -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="cupo_maximo" class="form-label small fw-semibold text-dark">Cupo total asignado:</label>
                            <input type="number" name="cupo_maximo" id="cupo_maximo" class="form-control rounded-3 py-2" min="{{ $cuponesConsumidos }}" max="100" value="{{ old('cupo_maximo', $activacion->cupo_maximo) }}" required>
                            <span class="text-muted small" style="font-size: 0.75rem;">
                                Cupones ya canjeados por alumnos: <strong>{{ $cuponesConsumidos }}</strong> (el cupo no puede ser menor a este valor).
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label for="costo_puntos" class="form-label small fw-semibold text-dark">Costo en puntos:</label>
                            <input type="number" name="costo_puntos" id="costo_puntos" class="form-control rounded-3 py-2" min="0" max="500" value="{{ old('costo_puntos', $activacion->costo_puntos) }}" required>
                            <span class="text-muted small" style="font-size: 0.75rem;">Puntos requeridos para futuros canjes.</span>
                        </div>
                    </div>

                    <!-- Vigencia Inicio y Fin -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="vigencia_inicio" class="form-label small fw-semibold text-dark">Fecha de inicio:</label>
                            <input type="date" name="vigencia_inicio" id="vigencia_inicio" class="form-control rounded-3 py-2" value="{{ old('vigencia_inicio', $activacion->vigencia_inicio?->toDateString()) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="vigencia_fin" class="form-label small fw-semibold text-dark">Fecha límite:</label>
                            <input type="date" name="vigencia_fin" id="vigencia_fin" class="form-control rounded-3 py-2" value="{{ old('vigencia_fin', $activacion->vigencia_fin?->toDateString()) }}" required>
                        </div>
                    </div>

                    <!-- Condiciones específicas del docente -->
                    <div class="mb-4">
                        <label for="condiciones_docente" class="form-label small fw-semibold text-dark">Condiciones o notas específicas (opcional):</label>
                        <textarea name="condiciones_docente" id="condiciones_docente" rows="3" class="form-control rounded-3 small">{{ old('condiciones_docente', $activacion->condiciones_docente) }}</textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="{{ route('docente.recompensas.index', ['tab' => 'activadas']) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-portal-primary rounded-pill px-4 py-2 fw-semibold">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card bg-light">
                <h3 class="h6 fw-bold text-dark mb-2">📋 Resumen del Beneficio</h3>
                <p class="text-muted small mb-3">{{ $activacion->recompensaCatalogo->descripcion }}</p>
                
                <div class="small text-muted mb-2">
                    Categoría: <span class="badge bg-primary-subtle text-primary">{{ $activacion->recompensaCatalogo->categoria }}</span>
                </div>
                <div class="small text-muted mb-2">
                    Cupos disponibles actuales: <strong class="text-dark">{{ $activacion->cupo_disponible }}</strong>
                </div>
                <div class="small text-muted mb-2">
                    Cupones canjeados: <strong class="text-dark">{{ $cuponesConsumidos }}</strong>
                </div>

                <div class="alert alert-info border-0 rounded-3 mt-3 py-2 small mb-0">
                    💡 Modificar esta configuración aplicará únicamente a nuevos canjes. Los cupones ya adquiridos por los estudiantes conservan su validez original.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

