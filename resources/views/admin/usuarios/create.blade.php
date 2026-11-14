@extends('layouts.dashboard')

@section('title', 'Nuevo Usuario Institucional - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <!-- Breadcrumb -->
    <div class="mb-3">
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-link text-muted p-0 text-decoration-none d-inline-flex align-items-center gap-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>Volver a la lista de usuarios</span>
        </a>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Alta Institucional de Usuario</h1>
            <p class="text-muted mb-0">Crea directamente una cuenta para un alumno, docente, orientador o administrador.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <div class="fw-bold mb-1">Por favor verifica los siguientes campos:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <form action="{{ route('admin.usuarios.store') }}" method="POST" id="formCrearUsuario" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
        @csrf

        <!-- 1. Perfil y Credenciales Generales -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold text-dark mb-1">1. Datos Personales y de Acceso</h5>
                <p class="small text-muted mb-0">Información básica y credenciales de autenticación del usuario.</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="nombre" class="form-label small fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="apellido_paterno" class="form-label small fw-semibold">Apellido Paterno <span class="text-danger">*</span></label>
                        <input type="text" name="apellido_paterno" id="apellido_paterno" class="form-control @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno') }}" required>
                        @error('apellido_paterno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="apellido_materno" class="form-label small fw-semibold">Apellido Materno <span class="text-danger">*</span></label>
                        <input type="text" name="apellido_materno" id="apellido_materno" class="form-control @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno') }}" required>
                        @error('apellido_materno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="email" class="form-label small fw-semibold">Correo Electrónico Institucional <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" placeholder="usuario@upemor.edu.mx" value="{{ old('email') }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="telefono" class="form-label small fw-semibold">Teléfono de Contacto</label>
                        <input type="text" name="telefono" id="telefono" class="form-control @error('telefono') is-invalid @enderror" placeholder="7771234567" value="{{ old('telefono') }}">
                        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="password" class="form-label small fw-semibold">Contraseña Inicial <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Mínimo 8 caracteres" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password', this)" title="Mostrar u ocultar contraseña">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="password_confirmation" class="form-label small fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Repite la contraseña" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password_confirmation', this)" title="Mostrar u ocultar contraseña">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="rol_id" class="form-label small fw-semibold">Rol Asignado <span class="text-danger">*</span></label>
                        <select name="rol_id" id="rol_id" class="form-select @error('rol_id') is-invalid @enderror" required onchange="actualizarSeccionesRol()">
                            <option value="">-- Selecciona el rol --</option>
                            @foreach ($roles as $r)
                                <option value="{{ $r->rol_id }}" data-nombre="{{ $r->nombre }}" @selected(old('rol_id') == $r->rol_id)>
                                    {{ $r->nombre }} &mdash; {{ $r->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        @error('rol_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="estado" class="form-label small fw-semibold">Estado de la Cuenta <span class="text-danger">*</span></label>
                        <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required>
                            <option value="ACTIVO" @selected(old('estado', 'ACTIVO') === 'ACTIVO')>ACTIVO (Habilitado inmediatamente)</option>
                            <option value="PENDIENTE" @selected(old('estado') === 'PENDIENTE')>PENDIENTE (Requiere validación posterior)</option>
                            <option value="INACTIVO" @selected(old('estado') === 'INACTIVO')>INACTIVO (Deshabilitado)</option>
                            <option value="BLOQUEADO" @selected(old('estado') === 'BLOQUEADO')>BLOQUEADO (Acceso restringido)</option>
                        </select>
                        <div class="form-text small text-muted" style="font-size: 0.74rem;">
                            <strong>ACTIVO:</strong> Acceso inmediato. | <strong>PENDIENTE:</strong> Por validar. | <strong>INACTIVO:</strong> Suspendido. | <strong>BLOQUEADO:</strong> Restringido.
                        </div>
                        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Información Especializada de Alumno (Condicional) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 seccion-rol" id="seccionAlumno" style="display: none; border-left: 4px solid #6366f1 !important;">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold text-dark mb-1">2. Información Académica del Alumno</h5>
                <p class="small text-muted mb-0">Matrícula, carrera cursada y nivel cuatrimestral.</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="matricula" class="form-label small fw-semibold">Matrícula Estudiantil <span class="text-danger">*</span></label>
                        <input type="text" name="matricula" id="matricula" class="form-control font-monospace @error('matricula') is-invalid @enderror" placeholder="UP260000" value="{{ old('matricula') }}">
                        @error('matricula') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="programa_id" class="form-label small fw-semibold">Programa Educativo (Carrera) <span class="text-danger">*</span></label>
                        <select name="programa_id" id="programa_id" class="form-select @error('programa_id') is-invalid @enderror">
                            <option value="">-- Selecciona carrera --</option>
                            @foreach ($programas as $prog)
                                <option value="{{ $prog->programa_id }}" @selected(old('programa_id') == $prog->programa_id)>
                                    {{ $prog->clave }} &mdash; {{ $prog->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('programa_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="cuatrimestre" class="form-label small fw-semibold">Cuatrimestre <span class="text-danger">*</span></label>
                        <input type="number" name="cuatrimestre" id="cuatrimestre" min="1" max="12" class="form-control @error('cuatrimestre') is-invalid @enderror" value="{{ old('cuatrimestre', 1) }}">
                        @error('cuatrimestre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="grupo" class="form-label small fw-semibold">Grupo</label>
                        <input type="text" name="grupo" id="grupo" maxlength="5" class="form-control font-monospace @error('grupo') is-invalid @enderror" placeholder="Ej. A, B, C" value="{{ old('grupo') }}">
                        @error('grupo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Información Especializada de Docente Puro (Condicional) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 seccion-rol" id="seccionDocente" style="display: none; border-left: 4px solid #0284c7 !important;">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold text-dark mb-1">2. Perfil Académico Docente</h5>
                <p class="small text-muted mb-0">Número de empleado, área de especialidad y cargos académicos (separado estrictamente de orientación).</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="numero_empleado" class="form-label small fw-semibold">Número de Empleado <span class="text-danger">*</span></label>
                        <input type="text" name="numero_empleado" id="numero_empleado" class="form-control font-monospace @error('numero_empleado') is-invalid @enderror" placeholder="DOC-2026-001" value="{{ old('numero_empleado') }}">
                        @error('numero_empleado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="puesto" class="form-label small fw-semibold">Puesto o Categoría</label>
                        <input type="text" name="puesto" id="puesto" class="form-control @error('puesto') is-invalid @enderror" placeholder="Profesor de Asignatura / PTC" value="{{ old('puesto') }}">
                        @error('puesto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="especialidad" class="form-label small fw-semibold">Área o Especialidad</label>
                        <input type="text" name="especialidad" id="especialidad" class="form-control @error('especialidad') is-invalid @enderror" placeholder="Desarrollo de Software, Matemáticas, Redes..." value="{{ old('especialidad') }}">
                        @error('especialidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="cedula_profesional_doc" class="form-label small fw-semibold">Cédula Profesional</label>
                        <input type="text" name="cedula_profesional" id="cedula_profesional_doc" class="form-control font-monospace @error('cedula_profesional') is-invalid @enderror" placeholder="12345678" value="{{ old('cedula_profesional') }}">
                        @error('cedula_profesional') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Información Especializada de Orientador (Condicional) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 seccion-rol" id="seccionOrientador" style="display: none; border-left: 4px solid #0d9488 !important;">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold text-dark mb-1">2. Perfil de Orientación Psicológica y Citas</h5>
                <p class="small text-muted mb-0">Configuración clínica, lugar de atención y agenda de citas.</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="cargo_orientador" class="form-label small fw-semibold">Cargo / Especialidad Clínica <span class="text-danger">*</span></label>
                        <input type="text" name="cargo" id="cargo_orientador" class="form-control @error('cargo') is-invalid @enderror" placeholder="Psicólogo Educativo / Orientador Estudiantil" value="{{ old('cargo', 'Psicólogo / Orientador Estudiantil') }}">
                        @error('cargo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="cedula_orientador" class="form-label small fw-semibold">Cédula Profesional</label>
                        <input type="text" name="cedula_profesional" id="cedula_orientador" class="form-control font-monospace @error('cedula_profesional') is-invalid @enderror" placeholder="87654321" value="{{ old('cedula_profesional') }}">
                        @error('cedula_profesional') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="lugar_atencion" class="form-label small fw-semibold">Lugar o Cubículo de Atención</label>
                        <input type="text" name="lugar_atencion" id="lugar_atencion" class="form-control @error('lugar_atencion') is-invalid @enderror" placeholder="Cubículo 3, Edificio de Bienestar Estudiantil" value="{{ old('lugar_atencion') }}">
                        @error('lugar_atencion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="modalidades_permitidas" class="form-label small fw-semibold">Modalidades Permitidas</label>
                        <select name="modalidades_permitidas" id="modalidades_permitidas" class="form-select">
                            <option value="PRESENCIAL" @selected(old('modalidades_permitidas', 'PRESENCIAL') === 'PRESENCIAL')>Presencial únicamente</option>
                            <option value="VIRTUAL" @selected(old('modalidades_permitidas') === 'VIRTUAL')>Virtual únicamente</option>
                            <option value="AMBAS" @selected(old('modalidades_permitidas') === 'AMBAS')>Presencial y Virtual (Híbrida)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check p-3 rounded-3 bg-light border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="permite_citas" id="permite_citas" value="1" @checked(old('permite_citas', 1))>
                            <label class="form-check-label small fw-semibold text-dark" for="permite_citas">
                                Permitir que los alumnos agenden citas con este orientador
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Nota informativa para Administrador -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 seccion-rol" id="seccionAdministrador" style="display: none; border-left: 4px solid #8b5cf6 !important; background: #faf5ff;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded-circle bg-purple text-white" style="background: #8b5cf6;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Cuenta con Privilegios de Administrador</h6>
                        <p class="small text-muted mb-0">
                            Este usuario tendrá acceso total a la gestión institucional, creación de periodos, administración de carreras, planes curriculares, catálogo de recompensas y gestión de usuarios.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary px-4 rounded-3">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary px-4 rounded-3 d-inline-flex align-items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Guardar Usuario</span>
            </button>
        </div>
    </form>
</div>

<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>`;
        } else {
            input.type = 'password';
            btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>`;
        }
    }

    function actualizarSeccionesRol() {
        const select = document.getElementById('rol_id');
        const selectedOption = select.options[select.selectedIndex];
        const rolNombre = selectedOption ? selectedOption.getAttribute('data-nombre') : '';

        // Ocultar todas las secciones dinámicas
        document.getElementById('seccionAlumno').style.display = 'none';
        document.getElementById('seccionDocente').style.display = 'none';
        document.getElementById('seccionOrientador').style.display = 'none';
        document.getElementById('seccionAdministrador').style.display = 'none';

        // Desactivar atributo required de campos condicionales ocultos para evitar bloqueos del navegador
        setRequired('matricula', false);
        setRequired('programa_id', false);
        setRequired('cuatrimestre', false);
        setRequired('numero_empleado', false);
        setRequired('cargo_orientador', false);

        if (rolNombre === 'Alumno') {
            document.getElementById('seccionAlumno').style.display = 'block';
            setRequired('matricula', true);
            setRequired('programa_id', true);
            setRequired('cuatrimestre', true);
        } else if (rolNombre === 'Docente') {
            document.getElementById('seccionDocente').style.display = 'block';
            setRequired('numero_empleado', true);
        } else if (rolNombre === 'Orientador') {
            document.getElementById('seccionOrientador').style.display = 'block';
            setRequired('cargo_orientador', true);
        } else if (rolNombre === 'Administrador') {
            document.getElementById('seccionAdministrador').style.display = 'block';
        }
    }

    function setRequired(id, isRequired) {
        const el = document.getElementById(id);
        if (el) {
            el.required = isRequired;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        actualizarSeccionesRol();
    });
</script>
@endsection
