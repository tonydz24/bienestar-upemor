@extends('layouts.dashboard')

@section('title', 'Crear Plan de Estudio - ' . $carrera->clave . ' - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.carreras.index') }}" class="text-decoration-none">Programas Educativos</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.planes.index', $carrera->programa_id) }}" class="text-decoration-none">{{ $carrera->clave }} - Planes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nueva Versión</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Nueva Versión de Plan de Estudio</h1>
        <p class="text-muted mb-0">Para la carrera {{ $carrera->nombre }} ({{ $carrera->clave }}).</p>
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

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <form action="{{ route('admin.planes.store', $carrera->programa_id) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="codigo" class="form-label fw-semibold">Código del Plan <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase @error('codigo') is-invalid @enderror" value="{{ old('codigo', $carrera->clave . '-' . date('Y')) }}" placeholder="Ej. {{ $carrera->clave }}-2026" maxlength="30" required>
                    </div>
                    <div class="col-md-6">
                        <label for="version" class="form-label fw-semibold">Versión Curricular <span class="text-danger">*</span></label>
                        <input type="text" name="version" id="version" class="form-control @error('version') is-invalid @enderror" value="{{ old('version', '2.0') }}" placeholder="Ej. 1.0 o 2026-R" maxlength="20" required>
                    </div>

                    <div class="col-12">
                        <label for="nombre" class="form-label fw-semibold">Nombre Descriptivo del Plan <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', 'Plan de Estudios ' . $carrera->nombre . ' ' . date('Y')) }}" maxlength="150" required>
                    </div>

                    <div class="col-md-4">
                        <label for="anio_aprobacion" class="form-label fw-semibold">Año de Aprobación <span class="text-danger">*</span></label>
                        <input type="number" name="anio_aprobacion" id="anio_aprobacion" class="form-control" value="{{ old('anio_aprobacion', date('Y')) }}" min="2000" max="2050" required>
                    </div>
                    <div class="col-md-4">
                        <label for="total_cuatrimestres" class="form-label fw-semibold">Cuatrimestres <span class="text-danger">*</span></label>
                        <input type="number" name="total_cuatrimestres" id="total_cuatrimestres" class="form-control" value="{{ old('total_cuatrimestres', 10) }}" min="1" max="12" required>
                    </div>
                    <div class="col-md-4">
                        <label for="vigencia_inicio" class="form-label fw-semibold">Inicio de Vigencia</label>
                        <input type="date" name="vigencia_inicio" id="vigencia_inicio" class="form-control" value="{{ old('vigencia_inicio', date('Y-m-d')) }}">
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label fw-semibold">Observaciones / Enfoque</label>
                        <textarea name="descripcion" id="descripcion" rows="3" class="form-control" placeholder="Justificación de la actualización curricular...">{{ old('descripcion') }}</textarea>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.planes.index', $carrera->programa_id) }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Crear Plan y Configurar Retícula</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

