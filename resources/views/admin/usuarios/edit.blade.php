@extends('layouts.dashboard')

@section('title', 'Editar Usuario - Bienestar UPEMOR')

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

    @php
        $rolNombre = $usuario->rol?->nombre ?? 'Usuario';
        $esPropiaCuenta = (auth()->id() === $usuario->usuario_id);
        $esDualHistorico = ($usuario->docente && $usuario->citaProfesional);
    @endphp

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <h1 class="h3 fw-bold mb-0" style="color: var(--bienestar-text-primary);">Editar Usuario</h1>
                <span class="badge rounded-pill px-3 py-1" style="background: #ede9fe; color: #5b21b6; font-size: 0.8rem;">
                    {{ $rolNombre }}
                </span>
                @if($esDualHistorico)
                    <span class="badge rounded-pill px-3 py-1 bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.8rem;">
                        Rol Dual (Docente + Orientador)
                    </span>
                @endif
                @if($esPropiaCuenta)
                    <span class="badge rounded-pill bg-light text-dark border" style="font-size: 0.8rem;">Tu Cuenta</span>
                @endif
            </div>
            <p class="text-muted mb-0">Modifica la información personal, credenciales o perfil institucional de {{ $usuario->nombre }} {{ $usuario->apellido_paterno }}.</p>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <strong>Atención:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

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

    {{-- Aviso de cuenta con rol dual histórico --}}
    @if($esDualHistorico)
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-warning bg-warning bg-opacity-10">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-2 rounded-circle bg-warning text-dark bg-opacity-25">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Cuenta Institucional con Rol Dual Histórico</h6>
                        <p class="small text-muted mb-0">
                            Este usuario cuenta simultáneamente con registro académico de docente y registro de orientación clínica de versiones anteriores. En Bienestar UPEMOR se ha adoptado una separación estricta para nuevas altas (Docente puro u Orientador puro). Los datos históricos y citas existentes se preservan de forma íntegra.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.usuarios.update', $usuario->usuario_id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
        @csrf
        @method('PUT')

        <!-- 1. Perfil y Credenciales Generales -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold text-dark mb-1">1. Datos Personales y de Cuenta</h5>
                <p class="small text-muted mb-0">Información básica y credenciales de acceso institucional.</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="nombre" class="form-label small fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $usuario->nombre) }}" required>
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="apellido_paterno" class="form-label small fw-semibold">Apellido Paterno <span class="text-danger">*</span></label>
                        <input type="text" name="apellido_paterno" id="apellido_paterno" class="form-control @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno', $usuario->apellido_paterno) }}" required>
                        @error('apellido_paterno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="apellido_materno" class="form-label small fw-semibold">Apellido Materno</label>
                        <input type="text" name="apellido_materno" id="apellido_materno" class="form-control @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno', $usuario->apellido_materno) }}">
                        @error('apellido_materno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="email" class="form-label small fw-semibold">Correo Electrónico Institucional <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $usuario->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="telefono" class="form-label small fw-semibold">Teléfono de Contacto</label>
                        <input type="text" name="telefono" id="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono', $usuario->telefono) }}">
                        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="estado" class="form-label small fw-semibold">Estado de la Cuenta <span class="text-danger">*</span></label>
                        <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required @disabled($esPropiaCuenta)>
                            <option value="ACTIVO" @selected(old('estado', $usuario->estado) === 'ACTIVO')>ACTIVO (Habilitado)</option>
                            <option value="PENDIENTE" @selected(old('estado', $usuario->estado) === 'PENDIENTE')>PENDIENTE (Por validar)</option>
                            <option value="INACTIVO" @selected(old('estado', $usuario->estado) === 'INACTIVO')>INACTIVO (Suspendido)</option>
                            <option value="BLOQUEADO" @selected(old('estado', $usuario->estado) === 'BLOQUEADO')>BLOQUEADO (Restringido)</option>
                            <option value="RECHAZADO" @selected(old('estado', $usuario->estado) === 'RECHAZADO')>RECHAZADO (Denegado)</option>
                        </select>
                        <div class="form-text small text-muted" style="font-size: 0.74rem;">
                            <strong>ACTIVO:</strong> Operativo. | <strong>INACTIVO:</strong> Pausa administrativa con datos resguardados. | <strong>BLOQUEADO:</strong> Seguridad.
                        </div>
                        @if($esPropiaCuenta)
                            <input type="hidden" name="estado" value="{{ $usuario->estado }}">
                            <div class="form-text small text-muted">No puedes modificar el estado de tu propia cuenta de Administrador.</div>
                        @endif
                        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Rol del Sistema</label>
                        <input type="text" class="form-control bg-light" value="{{ $rolNombre }}" readonly>
                        <div class="form-text small text-muted">El rol primario se preserva para asegurar la integridad de registros académicos.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Actualización Opcional de Contraseña -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold text-dark mb-1">2. Cambio de Contraseña (Opcional)</h5>
                <p class="small text-muted mb-0">Deja estos campos vacíos si deseas conservar la contraseña actual.</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="password" class="form-label small fw-semibold">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Dejar en blanco para no modificar">
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
                        <label for="password_confirmation" class="form-label small fw-semibold">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Repite la nueva contraseña si la modificas">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password_confirmation', this)" title="Mostrar u ocultar contraseña">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Perfil de Alumno (si aplica) -->
        @if ($usuario->estudiante || $rolNombre === 'Alumno')
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-left: 4px solid #6366f1 !important;">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-1">3. Información Académica de Alumno</h5>
                    <p class="small text-muted mb-0">Matrícula, carrera cursada y nivel.</p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="matricula" class="form-label small fw-semibold">Matrícula Estudiantil <span class="text-danger">*</span></label>
                            <input type="text" name="matricula" id="matricula" class="form-control font-monospace @error('matricula') is-invalid @enderror" value="{{ old('matricula', $usuario->estudiante?->matricula) }}" required>
                            @error('matricula') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="programa_id" class="form-label small fw-semibold">Programa Educativo (Carrera) <span class="text-danger">*</span></label>
                            <select name="programa_id" id="programa_id" class="form-select @error('programa_id') is-invalid @enderror" required>
                                @foreach ($programas as $prog)
                                    <option value="{{ $prog->programa_id }}" @selected(old('programa_id', $usuario->estudiante?->programa_id) == $prog->programa_id)>
                                        {{ $prog->clave }} &mdash; {{ $prog->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('programa_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="cuatrimestre" class="form-label small fw-semibold">Cuatrimestre <span class="text-danger">*</span></label>
                            <input type="number" name="cuatrimestre" id="cuatrimestre" min="1" max="12" class="form-control @error('cuatrimestre') is-invalid @enderror" value="{{ old('cuatrimestre', $usuario->estudiante?->cuatrimestre ?? 1) }}" required>
                            @error('cuatrimestre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="grupo" class="form-label small fw-semibold">Grupo</label>
                            <input type="text" name="grupo" id="grupo" maxlength="5" class="form-control font-monospace @error('grupo') is-invalid @enderror" value="{{ old('grupo', $usuario->estudiante?->grupo) }}">
                            @error('grupo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Ficha académica: Plan de Estudio y Generación --}}
                        <div class="col-12 col-md-6">
                            <label for="plan_id" class="form-label small fw-semibold">Plan de Estudio Curricular</label>
                            <select name="plan_id" id="plan_id" class="form-select @error('plan_id') is-invalid @enderror">
                                <option value="">-- Asignación automática por cohorte y vigencia --</option>
                                @if(isset($planesEstudio))
                                    @foreach ($planesEstudio as $plan)
                                        <option value="{{ $plan->plan_id }}" @selected(old('plan_id', $usuario->estudiante?->plan_id) == $plan->plan_id)>
                                            {{ $plan->codigo }} &mdash; {{ $plan->nombre }} ({{ $plan->programa?->clave ?? 'Genérico' }} - {{ $plan->anio_aprobacion }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="form-text small text-muted">
                                Plan resuelto actual: <strong>{{ $usuario->estudiante?->plan_estudio_asignado?->codigo ?? 'Sin plan asignado' }}</strong>
                            </div>
                            @error('plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Generación / Cohorte Académica</label>
                            <input type="text" class="form-control bg-light" value="{{ $usuario->estudiante?->generacion ?? 'Sin generación' }}" readonly>
                            <div class="form-text small text-muted">
                                Calculada según año de matrícula o cuatrimestre cursado.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 rounded-3 bg-light border d-flex flex-column gap-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="small text-muted">Saldo de Puntos de Bienestar:</span>
                                    <span class="fw-bold font-monospace text-dark fs-6">{{ number_format($usuario->estudiante?->saldo_puntos ?? 0) }} pts</span>
                                </div>
                                <div class="small text-muted border-top pt-2">
                                    <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                    <strong>Normativa académica institucional:</strong> La vinculación con el plan de estudio establece el mapa curricular de referencia del estudiante y <em>no lo inscribe de forma automática en asignaturas ni cátedras docentes</em>.
                                </div>
                                <div class="border-top pt-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span class="small text-muted">¿Requiere consultar o gestionar el plan de estudios con trazabilidad completa?</span>
                                    <a href="{{ route('admin.estudiantes.trayectoria', $usuario->usuario_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                            <path d="M16 3h5v5"></path>
                                            <path d="M4 20L21 3"></path>
                                            <path d="M21 16v5h-5"></path>
                                            <path d="M15 15l6 6"></path>
                                            <path d="M4 4l5 5"></path>
                                        </svg>
                                        Ficha de Trayectoria Académica
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 4. Perfil de Docente (si aplica) -->
        @if ($usuario->docente || $rolNombre === 'Docente')
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-left: 4px solid #0284c7 !important;">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-1">3. Perfil Académico Docente</h5>
                    <p class="small text-muted mb-0">Número de empleado y cargos académicos.</p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="numero_empleado" class="form-label small fw-semibold">Número de Empleado <span class="text-danger">*</span></label>
                            <input type="text" name="numero_empleado" id="numero_empleado" class="form-control font-monospace @error('numero_empleado') is-invalid @enderror" value="{{ old('numero_empleado', $usuario->docente?->numero_empleado) }}" required>
                            @error('numero_empleado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="puesto" class="form-label small fw-semibold">Puesto o Categoría</label>
                            <input type="text" name="puesto" id="puesto" class="form-control @error('puesto') is-invalid @enderror" value="{{ old('puesto', $usuario->docente?->puesto) }}">
                            @error('puesto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="especialidad" class="form-label small fw-semibold">Área o Especialidad</label>
                            <input type="text" name="especialidad" id="especialidad" class="form-control @error('especialidad') is-invalid @enderror" value="{{ old('especialidad', $usuario->docente?->especialidad) }}">
                            @error('especialidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="cedula_profesional_doc" class="form-label small fw-semibold">Cédula Profesional</label>
                            <input type="text" name="cedula_profesional" id="cedula_profesional_doc" class="form-control font-monospace @error('cedula_profesional') is-invalid @enderror" value="{{ old('cedula_profesional', $usuario->docente?->cedula_profesional) }}">
                            @error('cedula_profesional') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 5. Perfil de Orientador (si aplica) -->
        @if ($usuario->citaProfesional || $rolNombre === 'Orientador')
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-left: 4px solid #0d9488 !important;">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-1">3. Perfil de Orientador y Citas</h5>
                    <p class="small text-muted mb-0">Configuración de atención estudiantil.</p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="cargo_orientador" class="form-label small fw-semibold">Cargo / Especialidad Clínica <span class="text-danger">*</span></label>
                            <input type="text" name="cargo" id="cargo_orientador" class="form-control @error('cargo') is-invalid @enderror" value="{{ old('cargo', $usuario->citaProfesional?->cargo) }}" required>
                            @error('cargo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="cedula_orientador" class="form-label small fw-semibold">Cédula Profesional</label>
                            <input type="text" name="cedula_profesional" id="cedula_orientador" class="form-control font-monospace @error('cedula_profesional') is-invalid @enderror" value="{{ old('cedula_profesional', $usuario->citaProfesional?->cedula_profesional) }}">
                            @error('cedula_profesional') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="lugar_atencion" class="form-label small fw-semibold">Lugar o Cubículo de Atención</label>
                            <input type="text" name="lugar_atencion" id="lugar_atencion" class="form-control @error('lugar_atencion') is-invalid @enderror" value="{{ old('lugar_atencion', $usuario->citaProfesional?->lugar_atencion) }}">
                            @error('lugar_atencion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="modalidades_permitidas" class="form-label small fw-semibold">Modalidades Permitidas</label>
                            <select name="modalidades_permitidas" id="modalidades_permitidas" class="form-select">
                                <option value="PRESENCIAL" @selected(old('modalidades_permitidas', $usuario->citaProfesional?->modalidades_permitidas) === 'PRESENCIAL')>Presencial únicamente</option>
                                <option value="VIRTUAL" @selected(old('modalidades_permitidas', $usuario->citaProfesional?->modalidades_permitidas) === 'VIRTUAL')>Virtual únicamente</option>
                                <option value="AMBAS" @selected(old('modalidades_permitidas', $usuario->citaProfesional?->modalidades_permitidas) === 'AMBAS')>Presencial y Virtual (Híbrida)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check p-3 rounded-3 bg-light border">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="permite_citas" id="permite_citas" value="1" @checked(old('permite_citas', $usuario->citaProfesional?->permite_citas))>
                                <label class="form-check-label small fw-semibold text-dark" for="permite_citas">
                                    Permitir que los alumnos agenden citas con este orientador
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Banner de Garantía de Integridad -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="text-primary">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                </div>
                <div class="small text-muted">
                    <strong>Integridad garantizada:</strong> La actualización de este perfil preserva estrictamente el historial de evaluaciones psicológicas, bitácoras emocionales, canjes de recompensas y citas agendadas asociadas al usuario.
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
                <span>Guardar Cambios</span>
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
</script>
@endsection
