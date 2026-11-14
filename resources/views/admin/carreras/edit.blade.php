@extends('layouts.dashboard')

@section('title', 'Editar Programa Educativo - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.carreras.index') }}" class="text-decoration-none">Programas Educativos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar {{ $carrera->clave }}</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Editar Programa Educativo</h1>
        <p class="text-muted mb-0">Actualiza los datos institucionales de {{ $carrera->nombre }}.</p>
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
            <form action="{{ route('admin.carreras.update', $carrera->programa_id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="clave" class="form-label fw-semibold">Sigla o Clave Oficial <span class="text-danger">*</span></label>
                        <input type="text" name="clave" id="clave" class="form-control text-uppercase @error('clave') is-invalid @enderror" value="{{ old('clave', $carrera->clave) }}" maxlength="20" required>
                    </div>
                    <div class="col-md-8">
                        <label for="nombre" class="form-label fw-semibold">Nombre Completo del Programa <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $carrera->nombre) }}" maxlength="150" required>
                    </div>

                    <div class="col-md-4">
                        <label for="clave_historica" class="form-label fw-semibold">Clave Histórica (opcional)</label>
                        <input type="text" name="clave_historica" id="clave_historica" class="form-control text-uppercase" value="{{ old('clave_historica', $carrera->clave_historica) }}" maxlength="20">
                    </div>
                    <div class="col-md-8">
                        <label for="nombre_historico" class="form-label fw-semibold">Nombre Histórico (opcional)</label>
                        <input type="text" name="nombre_historico" id="nombre_historico" class="form-control" value="{{ old('nombre_historico', $carrera->nombre_historico) }}" maxlength="150">
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label fw-semibold">Descripción o Perfil</label>
                        <textarea name="descripcion" id="descripcion" rows="3" class="form-control">{{ old('descripcion', $carrera->descripcion) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" {{ old('activo', $carrera->activo) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="activo">Programa Activo</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="archivado" name="archivado" value="1" {{ old('archivado', $carrera->archivado) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-warning-emphasis" for="archivado">Marcar como Archivado / En Liquidación</label>
                        </div>
                    </div>

                    @if ($carrera->tieneDependenciasActivas())
                        <div class="col-12">
                            <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 0.875rem;">
                                <strong>Nota institucional:</strong> Este programa cuenta con historial de estudiantes, grupos o asignaturas. Para suspender nuevos ingresos sin afectar la trazabilidad histórica, activa la casilla <em>Archivado</em> en vez de intentar una eliminación física.
                            </div>
                        </div>
                    @endif

                    <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.carreras.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Actualizar Programa</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

