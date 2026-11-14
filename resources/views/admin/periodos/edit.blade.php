@extends('layouts.dashboard')

@section('title', 'Editar Periodo ' . $periodo->clave . ' - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.periodos.index') }}" class="text-decoration-none">Periodos Académicos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar {{ $periodo->clave }}</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Editar Periodo y Parciales</h1>
        <p class="text-muted mb-0">Configura fechas globales y fechas límites para cada corte evaluativo parcial.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h6 class="fw-bold mb-2">Por favor corrige los siguientes errores:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-4">
            <form action="{{ route('admin.periodos.update', $periodo->periodo_id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Clave</label>
                        <input type="text" class="form-control bg-light" value="{{ $periodo->clave }}" readonly>
                    </div>
                    <div class="col-md-8">
                        <label for="nombre" class="form-label fw-semibold">Nombre del Periodo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $periodo->nombre) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="fecha_inicio" class="form-label fw-semibold">Fecha de Inicio <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', $periodo->fecha_inicio?->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_fin" class="form-label fw-semibold">Fecha de Término <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ old('fecha_fin', $periodo->fecha_fin?->toDateString()) }}" required>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" {{ old('activo', $periodo->activo) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="activo">Periodo Vigente / Activo</label>
                        </div>
                    </div>

                    <!-- Configuración de los 3 Parciales -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <h5 class="fw-bold mb-3 text-dark">Cortes de Parciales Reglamentarios</h5>
                        <div class="row g-3">
                            @foreach($periodo->parciales->sortBy('numero_parcial') as $parcial)
                                <div class="col-12 p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-bold text-dark">
                                            <span class="badge bg-primary me-2">{{ $parcial->numero_parcial }}° Parcial</span>
                                            {{ $parcial->nombre }}
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="parcialActivo{{ $parcial->parcial_id }}" name="parciales[{{ $parcial->parcial_id }}][activo]" value="1" {{ $parcial->activo ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="parcialActivo{{ $parcial->parcial_id }}">Parcial en curso</label>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">Fecha de Inicio</label>
                                            <input type="date" name="parciales[{{ $parcial->parcial_id }}][fecha_inicio]" class="form-control form-control-sm" value="{{ $parcial->fecha_inicio?->toDateString() }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">Fecha de Término</label>
                                            <input type="date" name="parciales[{{ $parcial->parcial_id }}][fecha_fin]" class="form-control form-control-sm" value="{{ $parcial->fecha_fin?->toDateString() }}" required>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.periodos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Actualizar Periodo y Parciales</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

