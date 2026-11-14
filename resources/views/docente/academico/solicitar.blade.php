@extends('layouts.dashboard')

@section('title', 'Solicitar Impartición de Cátedra - Bienestar UPEMOR')

@section('content')
<div class="container py-3" style="max-width: 920px;">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="{{ route('docente.academico.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>Volver a mis asignaturas</span>
        </a>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5">
            Periodo Activo: {{ $periodoActivo->nombre }} ({{ $periodoActivo->clave }})
        </span>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary-subtle text-primary p-3 rounded-circle flex-shrink-0">
                    <i class="bi bi-journal-plus fs-3" aria-hidden="true"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Solicitar Impartición de Asignatura</h1>
                    <p class="text-muted small mb-0">
                        Proceso guiado paso a paso para postular la cátedra de una materia ante la Dirección Académica.
                    </p>
                </div>
            </div>

            {{-- Stepper visual --}}
            <div class="d-none d-md-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div class="step-indicator active d-flex align-items-center gap-2" id="step-nav-1">
                    <span class="badge rounded-circle bg-primary text-white p-2" style="width: 26px; height: 26px;">1</span>
                    <span class="small fw-semibold text-dark">Programa</span>
                </div>
                <div class="text-muted"><i class="bi bi-chevron-right small"></i></div>
                <div class="step-indicator d-flex align-items-center gap-2 text-muted" id="step-nav-2">
                    <span class="badge rounded-circle bg-light text-muted border p-2" style="width: 26px; height: 26px;">2</span>
                    <span class="small fw-semibold">Plan</span>
                </div>
                <div class="text-muted"><i class="bi bi-chevron-right small"></i></div>
                <div class="step-indicator d-flex align-items-center gap-2 text-muted" id="step-nav-3">
                    <span class="badge rounded-circle bg-light text-muted border p-2" style="width: 26px; height: 26px;">3</span>
                    <span class="small fw-semibold">Cuatrimestre</span>
                </div>
                <div class="text-muted"><i class="bi bi-chevron-right small"></i></div>
                <div class="step-indicator d-flex align-items-center gap-2 text-muted" id="step-nav-4">
                    <span class="badge rounded-circle bg-light text-muted border p-2" style="width: 26px; height: 26px;">4</span>
                    <span class="small fw-semibold">Asignatura</span>
                </div>
                <div class="text-muted"><i class="bi bi-chevron-right small"></i></div>
                <div class="step-indicator d-flex align-items-center gap-2 text-muted" id="step-nav-6">
                    <span class="badge rounded-circle bg-light text-muted border p-2" style="width: 26px; height: 26px;">5</span>
                    <span class="small fw-semibold">Grupo</span>
                </div>
                <div class="text-muted"><i class="bi bi-chevron-right small"></i></div>
                <div class="step-indicator d-flex align-items-center gap-2 text-muted" id="step-nav-7">
                    <span class="badge rounded-circle bg-light text-muted border p-2" style="width: 26px; height: 26px;">6</span>
                    <span class="small fw-semibold">Resumen</span>
                </div>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2" aria-hidden="true"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-x-circle me-2" aria-hidden="true"></i>
                    <strong>Por favor corrige los campos requeridos:</strong>
                    <ul class="mb-0 mt-1 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('docente.academico.solicitar.store') }}" id="formSolicitudCatedra">
                @csrf
                <input type="hidden" name="periodo_id" value="{{ $periodoActivo->periodo_id }}">

                {{-- Paso 1: Selección de Programa Educativo --}}
                <div class="step-block mb-4" id="paso-1">
                    <label for="select_programa" class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle">1</span>
                        <span>Programa Educativo / Carrera <span class="text-danger">*</span></span>
                    </label>
                    <select id="select_programa" class="form-select form-select-lg rounded-3 shadow-none border" required>
                        <option value="">-- Paso 1: Selecciona la carrera universitaria --</option>
                        @foreach($programas as $prog)
                            <option value="{{ $prog->programa_id }}" data-clave="{{ $prog->clave }}">
                                [{{ $prog->clave }}] {{ $prog->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text small text-muted">Selecciona la facultad o ingeniería a la que pertenece la asignatura.</div>
                </div>

                {{-- Paso 2: Selección de Plan de Estudio --}}
                <div class="step-block mb-4" id="paso-2" style="display: none;">
                    <label for="select_plan" class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle">2</span>
                        <span>Plan de Estudio Vigente <span class="text-danger">*</span></span>
                    </label>
                    <select id="select_plan" class="form-select form-select-lg rounded-3 shadow-none border">
                        <option value="">-- Paso 2: Selecciona el plan de estudios curricular --</option>
                        @foreach($planes as $pl)
                            <option value="{{ $pl->plan_id }}" data-programa-id="{{ $pl->programa_id }}" data-cuatris="{{ $pl->total_cuatrimestres ?? 10 }}">
                                {{ $pl->nombre }} (Versión {{ $pl->version ?? '1.0' }} — Aprobación {{ $pl->anio_aprobacion ?? 2024 }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text small text-muted">Filtra las asignaturas según la versión curricular autorizada.</div>
                </div>

                {{-- Paso 3: Selección de Cuatrimestre --}}
                <div class="step-block mb-4" id="paso-3" style="display: none;">
                    <label for="select_cuatrimestre" class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle">3</span>
                        <span>Cuatrimestre Académico <span class="text-danger">*</span></span>
                    </label>
                    <select id="select_cuatrimestre" class="form-select form-select-lg rounded-3 shadow-none border">
                        <option value="">-- Paso 3: Selecciona el cuatrimestre --</option>
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}">{{ $i }}° Cuatrimestre</option>
                        @endfor
                    </select>
                    <div class="form-text small text-muted">El nivel cuatrimestral correspondiente al avance de la materia.</div>
                </div>

                {{-- Paso 4: Selección de Asignatura Oficial --}}
                <div class="step-block mb-4" id="paso-4" style="display: none;">
                    <label for="asignatura_id" class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle">4</span>
                        <span>Asignatura Curricular <span class="text-danger">*</span></span>
                    </label>
                    <select name="asignatura_id" id="asignatura_id" class="form-select form-select-lg rounded-3 shadow-none border @error('asignatura_id') is-invalid @enderror" required>
                        <option value="">-- Paso 4: Selecciona la asignatura --</option>
                        @foreach($asignaturas as $asig)
                            <option value="{{ $asig->asignatura_id }}"
                                    data-programa-id="{{ $asig->programa_id }}"
                                    data-plan-id="{{ $asig->plan_id }}"
                                    data-cuatrimestre="{{ $asig->cuatrimestre }}"
                                    data-clave="{{ $asig->clave }}"
                                    data-nombre="{{ $asig->nombre }}"
                                    data-tipo="{{ $asig->tipo }}"
                                    data-creditos="{{ $asig->creditos }}"
                                    data-horas="{{ $asig->horas_semana }}"
                                    {{ old('asignatura_id') == $asig->asignatura_id ? 'selected' : '' }}>
                                [{{ $asig->clave }}] {{ $asig->nombre }} ({{ $asig->cuatrimestre }}° Cuatrimestre)
                            </option>
                        @endforeach
                    </select>
                    <div id="no-asignaturas-msg" class="text-danger small mt-1" style="display: none;">
                        <i class="bi bi-info-circle me-1"></i>No se encontraron materias catalogadas para este cuatrimestre y plan.
                    </div>
                </div>

                {{-- Paso 5 & 6: Periodo Activo & Modalidad de Apertura / Grupo Oficial --}}
                <div class="step-block mb-4" id="paso-6" style="display: none;">
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex align-items-center justify-content-between small">
                            <span class="text-muted"><strong>Periodo de Cátedra:</strong> {{ $periodoActivo->nombre }} ({{ $periodoActivo->clave }})</span>
                            <span class="badge bg-success">Activo Institucional</span>
                        </div>
                    </div>

                    <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary rounded-circle">5</span>
                        <span>Alcance y Grupo Académico <span class="text-danger">*</span></span>
                    </label>

                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="modalidad_apertura" id="modalidad_todos" value="todos" checked>
                            <label class="form-check-label fw-semibold text-dark" for="modalidad_todos">
                                <i class="bi bi-collection-fill text-primary me-1"></i> Apertura general para todos los grupos del cuatrimestre <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-1">Recomendado</span>
                            </label>
                            <div class="form-text small text-muted ms-4">
                                Permite impartir la asignatura y recibir alumnos de todos los subgrupos (ej. Grupo A, Grupo B) de esta carrera y cuatrimestre sin necesidad de postulaciones separadas.
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modalidad_apertura" id="modalidad_especifico" value="especifico">
                            <label class="form-check-label fw-semibold text-dark" for="modalidad_especifico">
                                <i class="bi bi-person-badge-fill text-secondary me-1"></i> Asignación delimitada a un grupo específico
                            </label>
                            <div class="form-text small text-muted ms-4">
                                Selecciona puntualmente una sola sección o grupo académico asignado.
                            </div>
                        </div>
                    </div>

                    <div id="contenedor-grupo-especifico" style="display: none;">
                        <label for="grupo_id" class="form-label small fw-semibold text-dark">
                            Selecciona el grupo académico específico:
                        </label>
                        <select name="grupo_id" id="grupo_id" class="form-select form-select-lg rounded-3 shadow-none border @error('grupo_id') is-invalid @enderror">
                            <option value="">-- Selecciona el grupo (ej. 4° A, 4° B) --</option>
                            @foreach($grupos as $grp)
                                <option value="{{ $grp->grupo_id }}"
                                        data-programa-id="{{ $grp->programa_id }}"
                                        data-cuatrimestre="{{ $grp->cuatrimestre }}"
                                        data-nombre="{{ $grp->nombre }}"
                                        data-turno="{{ $grp->turno }}"
                                        {{ old('grupo_id') == $grp->grupo_id ? 'selected' : '' }}>
                                    Grupo {{ $grp->cuatrimestre }}° "{{ $grp->nombre }}" — {{ $grp->programa?->clave ?? 'UPEMOR' }} (Turno: {{ $grp->turno }})
                                </option>
                            @endforeach
                        </select>
                        <div id="no-grupos-msg" class="text-danger small mt-1" style="display: none;">
                            <i class="bi bi-exclamation-triangle me-1"></i>No hay grupos registrados para este cuatrimestre y carrera en el periodo activo. Al elegir apertura general, la Dirección asignará automáticamente la materia al grupo oficial.
                        </div>
                    </div>
                </div>

                {{-- Paso 7: Justificación y Resumen Ejecutivo --}}
                <div class="step-block mb-4" id="paso-7" style="display: none;">
                    <label class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle">6</span>
                        <span>Resumen y Justificación de la Solicitud</span>
                    </label>

                    {{-- Ficha de Resumen Pre-envío --}}
                    <div class="card bg-light border p-3 rounded-4 mb-3">
                        <h6 class="fw-bold text-primary mb-2">Ficha Técnica de la Cátedra Propuesta:</h6>
                        <div class="row g-2 small">
                            <div class="col-sm-6">
                                <span class="text-muted">Carrera:</span> <strong id="resumen-programa">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted">Cuatrimestre:</span> <strong id="resumen-cuatrimestre">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted">Materia:</span> <strong id="resumen-materia">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted">Grupo:</span> <strong id="resumen-grupo">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted">Periodo:</span> <strong>{{ $periodoActivo->nombre }} ({{ $periodoActivo->clave }})</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted">Docente solicitante:</span> <strong>{{ Auth::user()->nombre_completo ?? Auth::user()->nombre }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="motivo_solicitud" class="form-label small fw-semibold text-dark">
                            Comentarios adicionales o disponibilidad horaria <span class="text-muted">(opcional)</span>
                        </label>
                        <textarea name="motivo_solicitud" id="motivo_solicitud" rows="3" class="form-control rounded-3" placeholder="Indica observaciones sobre tu disponibilidad, experiencia previa en la materia o modalidad...">{{ old('motivo_solicitud') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('docente.academico.index') }}" class="btn btn-outline-secondary px-4 rounded-pill">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-semibold" id="btnEnviarSolicitud" disabled>
                        <i class="bi bi-send-fill me-1"></i>Enviar Solicitud a Dirección
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectPrograma = document.getElementById('select_programa');
    const selectPlan = document.getElementById('select_plan');
    const selectCuatri = document.getElementById('select_cuatrimestre');
    const selectAsig = document.getElementById('asignatura_id');
    const selectGrupo = document.getElementById('grupo_id');
    const btnEnviar = document.getElementById('btnEnviarSolicitud');

    const paso2 = document.getElementById('paso-2');
    const paso3 = document.getElementById('paso-3');
    const paso4 = document.getElementById('paso-4');
    const paso6 = document.getElementById('paso-6');
    const paso7 = document.getElementById('paso-7');

    const resumenProg = document.getElementById('resumen-programa');
    const resumenCuatri = document.getElementById('resumen-cuatrimestre');
    const resumenMat = document.getElementById('resumen-materia');
    const resumenGrp = document.getElementById('resumen-grupo');

    const noAsigMsg = document.getElementById('no-asignaturas-msg');
    const noGrpMsg = document.getElementById('no-grupos-msg');

    // Clonar las opciones originales para filtrado limpio
    const todasLasAsignaturas = Array.from(selectAsig.options).slice(1);
    const todosLosPlanes = Array.from(selectPlan.options).slice(1);
    const todosLosGrupos = Array.from(selectGrupo.options).slice(1);

    // Evento 1: Cambio de Programa Educativo
    selectPrograma.addEventListener('change', function () {
        const progId = this.value;
        const progText = this.options[this.selectedIndex]?.text || '';

        // Reset subsiguientes
        selectPlan.value = '';
        selectCuatri.value = '';
        selectAsig.value = '';
        selectGrupo.value = '';
        paso3.style.display = 'none';
        paso4.style.display = 'none';
        paso6.style.display = 'none';
        paso7.style.display = 'none';
        btnEnviar.disabled = true;

        if (!progId) {
            paso2.style.display = 'none';
            return;
        }

        // Filtrar planes de este programa
        selectPlan.innerHTML = '<option value="">-- Paso 2: Selecciona el plan de estudios curricular --</option>';
        let planesDisponibles = 0;
        todosLosPlanes.forEach(opt => {
            if (opt.getAttribute('data-programa-id') === progId) {
                selectPlan.appendChild(opt.cloneNode(true));
                planesDisponibles++;
            }
        });

        paso2.style.display = 'block';

        // Si solo hay un plan, seleccionarlo automáticamente
        if (planesDisponibles === 1) {
            selectPlan.selectedIndex = 1;
            selectPlan.dispatchEvent(new Event('change'));
        }

        resumenProg.textContent = progText;
    });

    // Evento 2: Cambio de Plan de Estudios
    selectPlan.addEventListener('change', function () {
        if (!this.value) {
            paso3.style.display = 'none';
            paso4.style.display = 'none';
            paso6.style.display = 'none';
            paso7.style.display = 'none';
            btnEnviar.disabled = true;
            return;
        }
        paso3.style.display = 'block';
    });

    // Evento 3: Cambio de Cuatrimestre
    selectCuatri.addEventListener('change', function () {
        const cuatriVal = this.value;
        const progId = selectPrograma.value;
        const planId = selectPlan.value;

        selectAsig.value = '';
        selectGrupo.value = '';
        paso6.style.display = 'none';
        paso7.style.display = 'none';
        btnEnviar.disabled = true;

        if (!cuatriVal) {
            paso4.style.display = 'none';
            return;
        }

        resumenCuatri.textContent = cuatriVal + '° Cuatrimestre';

        // Filtrar asignaturas por programa, cuatrimestre (y plan si coincide)
        selectAsig.innerHTML = '<option value="">-- Paso 4: Selecciona la asignatura --</option>';
        let asigCount = 0;

        todasLasAsignaturas.forEach(opt => {
            const optProg = opt.getAttribute('data-programa-id');
            const optCuatri = opt.getAttribute('data-cuatrimestre');
            const optPlan = opt.getAttribute('data-plan-id');

            const coincidePrograma = (optProg === progId);
            const coincideCuatri = (optCuatri === cuatriVal);
            const coincidePlan = (!optPlan || !planId || optPlan === planId);

            if (coincidePrograma && coincideCuatri && coincidePlan) {
                selectAsig.appendChild(opt.cloneNode(true));
                asigCount++;
            }
        });

        // Si el filtro estricto por plan no arrojó resultados, permitir asignaturas de la carrera y cuatrimestre
        if (asigCount === 0) {
            todasLasAsignaturas.forEach(opt => {
                const optProg = opt.getAttribute('data-programa-id');
                const optCuatri = opt.getAttribute('data-cuatrimestre');
                if (optProg === progId && optCuatri === cuatriVal) {
                    selectAsig.appendChild(opt.cloneNode(true));
                    asigCount++;
                }
            });
        }

        noAsigMsg.style.display = asigCount === 0 ? 'block' : 'none';
        paso4.style.display = 'block';

        // Filtrar grupos por programa y cuatrimestre
        selectGrupo.innerHTML = '<option value="">-- Paso 5: Selecciona el grupo académico (ej. 4° A, 4° B) --</option>';
        let grpCount = 0;
        todosLosGrupos.forEach(opt => {
            const optProg = opt.getAttribute('data-programa-id');
            const optCuatri = opt.getAttribute('data-cuatrimestre');
            if (optProg === progId && optCuatri === cuatriVal) {
                selectGrupo.appendChild(opt.cloneNode(true));
                grpCount++;
            }
        });

        noGrpMsg.style.display = grpCount === 0 ? 'block' : 'none';
    });

    const radioModalidadTodos = document.getElementById('modalidad_todos');
    const radioModalidadEspecifico = document.getElementById('modalidad_especifico');
    const contenedorGrupoEspecifico = document.getElementById('contenedor-grupo-especifico');

    function actualizarEstadoGrupo() {
        if (!selectAsig.value) {
            paso6.style.display = 'none';
            paso7.style.display = 'none';
            btnEnviar.disabled = true;
            return;
        }

        paso6.style.display = 'block';

        if (radioModalidadTodos && radioModalidadTodos.checked) {
            if (contenedorGrupoEspecifico) contenedorGrupoEspecifico.style.display = 'none';
            if (selectGrupo) selectGrupo.required = false;
            resumenGrp.textContent = 'Apertura general (Todos los grupos del cuatrimestre)';
            paso7.style.display = 'block';
            btnEnviar.disabled = false;
        } else {
            if (contenedorGrupoEspecifico) contenedorGrupoEspecifico.style.display = 'block';
            if (selectGrupo) selectGrupo.required = true;
            if (selectGrupo && selectGrupo.value) {
                resumenGrp.textContent = selectGrupo.options[selectGrupo.selectedIndex]?.text || '';
                paso7.style.display = 'block';
                btnEnviar.disabled = false;
            } else {
                resumenGrp.textContent = 'Pendiente de seleccionar grupo';
                paso7.style.display = 'none';
                btnEnviar.disabled = true;
            }
        }
    }

    if (radioModalidadTodos) {
        radioModalidadTodos.addEventListener('change', actualizarEstadoGrupo);
    }
    if (radioModalidadEspecifico) {
        radioModalidadEspecifico.addEventListener('change', actualizarEstadoGrupo);
    }

    // Evento 4: Cambio de Asignatura
    selectAsig.addEventListener('change', function () {
        if (!this.value) {
            paso6.style.display = 'none';
            paso7.style.display = 'none';
            btnEnviar.disabled = true;
            return;
        }

        resumenMat.textContent = this.options[this.selectedIndex]?.text || '';
        actualizarEstadoGrupo();
    });

    // Evento 5: Cambio de Grupo
    selectGrupo.addEventListener('change', function () {
        actualizarEstadoGrupo();
    });

    // Control de Doble Envío Concurrente (Debounce / Submit Protection)
    const formCatedra = document.getElementById('formSolicitudCatedra');
    if (formCatedra) {
        formCatedra.addEventListener('submit', function (e) {
            if (btnEnviar) {
                btnEnviar.disabled = true;
                btnEnviar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Registrando Solicitud...';
            }
        });
    }
});
</script>
@endpush
@endsection
