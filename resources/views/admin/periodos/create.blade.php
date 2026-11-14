@extends('layouts.dashboard')

@section('title', 'Nuevo Periodo Académico - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.periodos.index') }}" class="text-decoration-none">Periodos Académicos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nuevo Periodo</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Crear Periodo Académico</h1>
        <p class="text-muted mb-0">Al registrar un periodo, se generarán automáticamente sus tres parciales reglamentarios.</p>
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
            <form action="{{ route('admin.periodos.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="clave" class="form-label fw-semibold">Clave del Periodo <span class="text-danger">*</span></label>
                        <input type="text" name="clave" id="clave" class="form-control text-uppercase @error('clave') is-invalid @enderror" value="{{ old('clave') }}" placeholder="Ej. 2026-1" maxlength="20" required>
                        <div class="form-text">Nomenclatura institucional (Año-Número).</div>
                    </div>
                    <div class="col-md-7">
                        <label for="nombre" class="form-label fw-semibold">Nombre Descriptivo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" placeholder="Ej. Enero - Abril 2026" maxlength="100" required>
                    </div>

                    <div class="col-md-6">
                        <label for="fecha_inicio" class="form-label fw-semibold">Fecha de Inicio <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror" value="{{ old('fecha_inicio') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_fin" class="form-label fw-semibold">Fecha de Término <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror" value="{{ old('fecha_fin') }}" required>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" {{ old('activo', '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="activo">Periodo Vigente / Activo</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info py-2 px-3 small mb-0">
                            <strong>Nota de automatización:</strong> El sistema calculará e insertará automáticamente los 3 parciales dividiendo el rango de fechas en 3 periodos iguales, los cuales podrás afinar posteriormente.
                        </div>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.periodos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Guardar Periodo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

