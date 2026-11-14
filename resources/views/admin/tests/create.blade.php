@extends('layouts.dashboard')

@section('title', 'Registrar Nuevo Test Psicométrico - Administración Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.tests.index') }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Volver al catálogo
            </a>
            <div>
                <h1 class="h3 fw-bold text-slate-800 mb-0">Registrar Nuevo Instrumento Psicométrico</h1>
                <span class="text-muted small">Alta institucional de herramienta de evaluación, reactivos, escalas y baremos</span>
            </div>
        </div>
    </div>

    <!-- A8: Mensaje Institucional Obligatorio al Crear un Test -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="border-left: 5px solid #4f46e5 !important; background: linear-gradient(135deg, #f5f3ff 0%, #eef2ff 100%);">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-primary text-white p-2.5 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 44px; height: 44px;">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-2">Uso responsable de instrumentos de evaluación</h5>
                    <p class="small text-dark mb-2">
                        Los tests psicométricos registrados en Bienestar UPEMOR tienen carácter orientativo, formativo y de detección preventiva.
                    </p>
                    <p class="small text-dark mb-2">
                        Sus resultados no constituyen por sí mismos un diagnóstico clínico o psicológico definitivo, ni sustituyen la valoración de un profesional especializado.
                    </p>
                    <p class="small text-dark mb-1 fw-medium">
                        Todo instrumento que se publique debe contar con sustento metodológico claro, instrucciones comprensibles, escalas consistentes y manejo confidencial de las respuestas.
                    </p>
                    <p class="text-muted mb-0" style="font-size: 0.75rem;">
                        Aviso Institucional de Confidencialidad y Ética Psicométrica: Los instrumentos disponibles son herramientas psicométricas estandarizadas de tamizaje preliminar.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div class="fw-semibold">Por favor corrige los campos señalados antes de continuar:</div>
            </div>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <form action="{{ route('admin.tests.store') }}" method="POST" id="formCrearTest">
        @csrf
        <input type="hidden" name="modo_editor" value="completo">
        <input type="hidden" name="estado_version" id="inputEstadoVersion" value="{{ old('estado_version', 'PUBLICADA') }}">

        <!-- SECCIÓN 1: METADATOS DEL INSTRUMENTO -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                    <i class="bi bi-clipboard2-pulse text-primary me-2"></i>1. Identificación y Parámetros del Instrumento
                </h5>

                <div class="row g-4">
                    <div class="col-md-4">
                        <label for="codigo" class="form-label fw-semibold text-dark small">Código / Clave Única <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3 font-monospace text-uppercase" id="codigo" name="codigo" value="{{ old('codigo') }}" required maxlength="20" placeholder="Ej. BDI-II, DASS-21">
                        <div class="form-text small">Clave alfanumérica única sin espacios.</div>
                    </div>

                    <div class="col-md-5">
                        <label for="nombre" class="form-label fw-semibold text-dark small">Nombre Oficial del Test <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="150" placeholder="Ej. Inventario de Hábitos y Bienestar Académico">
                    </div>

                    <div class="col-md-3">
                        <label for="siglas" class="form-label fw-semibold text-dark small">Siglas Institucionales</label>
                        <input type="text" class="form-control rounded-3 text-uppercase" id="siglas" name="siglas" value="{{ old('siglas') }}" maxlength="20" placeholder="Ej. IHBA-10">
                    </div>

                    <div class="col-md-5">
                        <label for="categoria" class="form-label fw-semibold text-dark small">Categoría Clínica / Bienestar <span class="text-danger">*</span></label>
                        <input list="categoriasList" class="form-control rounded-3" id="categoria" name="categoria" value="{{ old('categoria') }}" required placeholder="Selecciona o escribe una categoría">
                        <datalist id="categoriasList">
                            @foreach($categoriasSugeridas as $sug)
                                <option value="{{ $sug }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label for="dias_espera_reaplicacion" class="form-label fw-semibold text-dark small">Intervalo de Repetición (Días) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3" id="dias_espera_reaplicacion" name="dias_espera_reaplicacion" value="{{ old('dias_espera_reaplicacion', 7) }}" min="0" max="365" required>
                        <div class="d-flex flex-wrap gap-1 mt-1.5">
                            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0 btn-preset-dias" data-dias="0" style="font-size: 0.7rem;">0 (Inmediato)</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 btn-preset-dias" data-dias="1" style="font-size: 0.7rem;">1 día (24h)</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 btn-preset-dias" data-dias="7" style="font-size: 0.7rem;">7 días</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 btn-preset-dias" data-dias="14" style="font-size: 0.7rem;">14 días</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 btn-preset-dias" data-dias="30" style="font-size: 0.7rem;">30 días</button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="anio_publicacion" class="form-label fw-semibold text-dark small">Año de Publicación / Adaptación</label>
                        <input type="number" class="form-control rounded-3" id="anio_publicacion" name="anio_publicacion" value="{{ old('anio_publicacion', date('Y')) }}" min="1900" max="2100">
                    </div>

                    <div class="col-md-12">
                        <label for="autor" class="form-label fw-semibold text-dark small">Autor(es) / Referencia Metodológica</label>
                        <input type="text" class="form-control rounded-3" id="autor" name="autor" value="{{ old('autor') }}" maxlength="150" placeholder="Ej. Comité Institucional de Orientación y Psicopedagogía UPEMOR">
                    </div>

                    <div class="col-md-12">
                        <label for="descripcion" class="form-label fw-semibold text-dark small">Propósito Institucional y Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" id="descripcion" name="descripcion" rows="3" required maxlength="1000" placeholder="Describe brevemente el constructo evaluado y la pertinencia de este instrumento para la comunidad universitaria...">{{ old('descripcion') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: PARÁMETROS DE LA VERSIÓN INICIAL (v1.0) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                    <i class="bi bi-card-checklist text-primary me-2"></i>2. Configuración de la Versión Inicial (v1.0)
                </h5>

                <div class="row g-4">
                    <div class="col-md-4">
                        <label for="marco_temporal" class="form-label fw-semibold text-dark small">Marco Temporal de Referencia</label>
                        <input type="text" class="form-control rounded-3" id="marco_temporal" name="marco_temporal" value="{{ old('marco_temporal', 'Últimas 2 semanas') }}" maxlength="100" placeholder="Ej. Últimas 2 semanas">
                    </div>

                    <div class="col-md-4">
                        <label for="duracion_estimada_min" class="form-label fw-semibold text-dark small">Duración Estimada (Minutos) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3" id="duracion_estimada_min" name="duracion_estimada_min" value="{{ old('duracion_estimada_min', 5) }}" min="1" max="120" required>
                    </div>

                    <div class="col-md-4">
                        <label for="puntos_recompensa" class="form-label fw-semibold text-dark small">Puntos de Recompensa al Completar <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3" id="puntos_recompensa" name="puntos_recompensa" value="{{ old('puntos_recompensa', 15) }}" min="0" max="500" required>
                    </div>

                    <div class="col-md-12">
                        <label for="instrucciones" class="form-label fw-semibold text-dark small">Instrucciones Metodológicas para el Estudiante <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" id="instrucciones" name="instrucciones" rows="3" required maxlength="2000">{{ old('instrucciones', 'Por favor lee atentamente cada una de las siguientes afirmaciones y selecciona la opción que mejor describa cómo te has sentido en las últimas dos semanas. Recuerda que tus respuestas son confidenciales.') }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <div class="d-flex flex-column flex-md-row gap-4 p-3 bg-light rounded-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" @checked(old('activo', true))>
                                <label class="form-check-label fw-semibold text-dark" for="activo">
                                    Habilitar para estudiantes al publicar
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="notificar_alumnos" name="notificar_alumnos" value="1" @checked(old('notificar_alumnos', true))>
                                <label class="form-check-label fw-semibold text-dark" for="notificar_alumnos">
                                    Notificar a los estudiantes activos cuando se publique
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: ESCALA GLOBAL DE RESPUESTA -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-sliders text-primary me-2"></i>3. Escala Global de Respuesta (Para reactivos tipo Escala)
                        </h5>
                        <p class="text-muted small mb-0">Define las opciones y puntajes base que compartirán los reactivos de escala tipo Likert.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnAgregarEscala">
                        <i class="bi bi-plus-lg me-1"></i> Agregar Opción de Escala
                    </button>
                </div>

                <div id="escalasContainer" class="d-flex flex-column gap-2"></div>
            </div>
        </div>

        <!-- SECCIÓN 4: EDITOR VISUAL DE REACTIVOS / PREGUNTAS (A3) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-list-check text-primary me-2"></i>4. Editor de Reactivos y Preguntas del Instrumento
                        </h5>
                        <p class="text-muted small mb-0">
                            Agrega, edita, reordena o elimina reactivos. Soporta escala Likert, opción única, selección múltiple y respuesta abierta (sin puntaje clínico automático).
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold" id="btnAgregarPregunta">
                        <i class="bi bi-plus-circle me-1"></i> Agregar Pregunta
                    </button>
                </div>

                <div id="preguntasContainer" class="d-flex flex-column gap-3"></div>
            </div>
        </div>

        <!-- SECCIÓN 5: BAREMOS E INTERPRETACIÓN -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-bar-chart-steps text-primary me-2"></i>5. Baremos y Niveles de Interpretación Preventiva
                        </h5>
                        <p class="text-muted small mb-0">Configura los rangos de puntuación total y el mensaje orientador para cada categoría.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnAgregarBaremo">
                        <i class="bi bi-plus-lg me-1"></i> Agregar Rango de Baremo
                    </button>
                </div>

                <div id="baremosContainer" class="d-flex flex-column gap-2"></div>
            </div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <a href="{{ route('admin.tests.index') }}" class="btn btn-light rounded-pill px-4">Cancelar</a>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="submit" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" id="btnGuardarBorrador">
                        <i class="bi bi-file-earmark-text me-1"></i> Guardar como Borrador
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" id="btnRegistrarPublicar">
                        <i class="bi bi-check-lg me-1"></i> Registrar e Inicializar Test
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Botones rápidos de intervalo de repetición (0, 1, 7, 14, 30 días)
    const diasInput = document.getElementById('dias_espera_reaplicacion');
    document.querySelectorAll('.btn-preset-dias').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (diasInput) diasInput.value = this.getAttribute('data-dias');
        });
    });

    const escalasContainer = document.getElementById('escalasContainer');
    const preguntasContainer = document.getElementById('preguntasContainer');
    const baremosContainer = document.getElementById('baremosContainer');
    const inputEstadoVersion = document.getElementById('inputEstadoVersion');
    const chkActivo = document.getElementById('activo');

    document.getElementById('btnGuardarBorrador')?.addEventListener('click', function () {
        if (inputEstadoVersion) inputEstadoVersion.value = 'BORRADOR';
        if (chkActivo) chkActivo.checked = false;
    });

    document.getElementById('btnRegistrarPublicar')?.addEventListener('click', function () {
        if (inputEstadoVersion) inputEstadoVersion.value = 'PUBLICADA';
    });

    @php
        $initialEscalasCreate = old('escalas', [
            ['valor' => 0, 'etiqueta' => 'Nunca / Ningún día', 'descripcion' => '0 puntos'],
            ['valor' => 1, 'etiqueta' => 'Varios días', 'descripcion' => '1 punto'],
            ['valor' => 2, 'etiqueta' => 'Más de la mitad de los días', 'descripcion' => '2 puntos'],
            ['valor' => 3, 'etiqueta' => 'Casi todos los días', 'descripcion' => '3 puntos'],
        ]);
        $initialPreguntasCreate = old('preguntas', [
            ['enunciado' => '¿Te has sentido nervioso(a), ansioso(a) o con los nervios de punta?', 'tipo_pregunta' => 'ESCALA', 'es_obligatoria' => 1, 'es_inverso' => 0, 'es_critico' => 0],
        ]);
        $initialBaremosCreate = old('baremos', [
            ['puntuacion_min' => 0, 'puntuacion_max' => 4, 'nivel' => 'Bajo / Adecuado', 'color_hex' => '#10b981', 'mensaje_retroalimentacion' => 'Indicadores favorables de bienestar en esta dimensión.', 'sugiere_orientacion' => 0],
            ['puntuacion_min' => 5, 'puntuacion_max' => 9, 'nivel' => 'Moderado / Preventivo', 'color_hex' => '#f59e0b', 'mensaje_retroalimentacion' => 'Se sugieren pautas preventivas de autocuidado y seguimiento.', 'sugiere_orientacion' => 0],
            ['puntuacion_min' => 10, 'puntuacion_max' => 30, 'nivel' => 'Prioritario / Orientación Sugerida', 'color_hex' => '#ef4444', 'mensaje_retroalimentacion' => 'Se recomienda agendar acompañamiento con el área de orientación.', 'sugiere_orientacion' => 1],
        ]);
    @endphp

    // 1. Escalas Globales
    let escalaIdx = 0;
    const defaultEscalas = @json($initialEscalasCreate);

    function addEscalaRow(data = {}) {
        const i = escalaIdx++;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center escala-row bg-light p-2.5 rounded-3 border';
        row.innerHTML = `
            <div class="col-md-2">
                <label class="form-label small text-muted mb-0">Puntos / Valor</label>
                <input type="number" name="escalas[${i}][valor]" class="form-control form-control-sm rounded-3" value="${data.valor ?? 0}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-0">Etiqueta de Opción</label>
                <input type="text" name="escalas[${i}][etiqueta]" class="form-control form-control-sm rounded-3" value="${data.etiqueta || ''}" placeholder="Ej. Casi siempre" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-0">Descripción opcional</label>
                <input type="text" name="escalas[${i}][descripcion]" class="form-control form-control-sm rounded-3" value="${data.descripcion || ''}" placeholder="Opcional">
            </div>
            <div class="col-md-1 text-end pt-3">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remove-escala" title="Eliminar opción">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        row.querySelector('.btn-remove-escala').addEventListener('click', function () {
            if (escalasContainer.querySelectorAll('.escala-row').length > 2) {
                row.remove();
            }
        });
        escalasContainer.appendChild(row);
    }

    Object.values(defaultEscalas).forEach(e => addEscalaRow(e));
    document.getElementById('btnAgregarEscala')?.addEventListener('click', function () {
        const count = escalasContainer.querySelectorAll('.escala-row').length;
        addEscalaRow({ valor: count, etiqueta: '', descripcion: '' });
    });

    // 2. Reactivos / Preguntas
    let pregIdx = 0;
    const oldPreguntas = @json($initialPreguntasCreate);

    function renumerarPreguntas() {
        preguntasContainer.querySelectorAll('.pregunta-card').forEach(function (card, idx) {
            const badge = card.querySelector('.pregunta-num-badge');
            if (badge) badge.textContent = `Reactivo #${idx + 1}`;
        });
    }

    function addPreguntaCard(data = {}) {
        const idx = pregIdx++;
        const tipoInicial = data.tipo_pregunta || 'ESCALA';
        const esOblig = (data.es_obligatoria === undefined || data.es_obligatoria == 1 || data.es_obligatoria === true);
        const esInverso = (data.es_inverso == 1 || data.es_inverso === true);
        const esCritico = (data.es_critico == 1 || data.es_critico === true);
        const opciones = Array.isArray(data.opciones) ? data.opciones : (Array.isArray(data.opciones_json) ? data.opciones_json : [
            { etiqueta: 'Opción A', valor: 0 },
            { etiqueta: 'Opción B', valor: 1 }
        ]);

        const card = document.createElement('div');
        card.className = 'card border rounded-4 shadow-xs p-3 pregunta-card bg-white';
        card.innerHTML = `
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 pregunta-num-badge">Reactivo</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0.5 btn-move-up" title="Subir reactivo">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0.5 btn-move-down" title="Bajar reactivo">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0.5 btn-remove-pregunta" title="Eliminar reactivo">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold mb-1">Enunciado de la Pregunta / Reactivo <span class="text-danger">*</span></label>
                    <input type="text" name="preguntas[${idx}][enunciado]" class="form-control rounded-3" value="${(data.enunciado || '').replace(/"/g, '&quot;')}" placeholder="Escribe la pregunta clara y comprensible para el estudiante...">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Tipo de Reactivo</label>
                    <select name="preguntas[${idx}][tipo_pregunta]" class="form-select rounded-3 select-tipo-pregunta">
                        <option value="ESCALA" ${tipoInicial === 'ESCALA' ? 'selected' : ''}>Escala de respuesta (Likert)</option>
                        <option value="OPCION_UNICA" ${tipoInicial === 'OPCION_UNICA' ? 'selected' : ''}>Opción única (Radio)</option>
                        <option value="OPCION_MULTIPLE" ${tipoInicial === 'OPCION_MULTIPLE' ? 'selected' : ''}>Selección múltiple (Casillas)</option>
                        <option value="TEXTO_LIBRE" ${tipoInicial === 'TEXTO_LIBRE' ? 'selected' : ''}>Respuesta abierta (Texto libre — 0 pts)</option>
                    </select>
                </div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-4 align-items-center bg-light px-3 py-2 rounded-3">
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="preguntas[${idx}][es_obligatoria]" value="0">
                            <input class="form-check-input" type="checkbox" name="preguntas[${idx}][es_obligatoria]" value="1" id="oblig_${idx}" ${esOblig ? 'checked' : ''}>
                            <label class="form-check-label small fw-semibold" for="oblig_${idx}">Obligatoria</label>
                        </div>
                        <div class="form-check form-switch mb-0 wrap-flag-clinico">
                            <input type="hidden" name="preguntas[${idx}][es_inverso]" value="0">
                            <input class="form-check-input" type="checkbox" name="preguntas[${idx}][es_inverso]" value="1" id="inv_${idx}" ${esInverso ? 'checked' : ''}>
                            <label class="form-check-label small" for="inv_${idx}">Puntaje inverso</label>
                        </div>
                        <div class="form-check form-switch mb-0 wrap-flag-clinico">
                            <input type="hidden" name="preguntas[${idx}][es_critico]" value="0">
                            <input class="form-check-input" type="checkbox" name="preguntas[${idx}][es_critico]" value="1" id="crit_${idx}" ${esCritico ? 'checked' : ''}>
                            <label class="form-check-label small text-danger fw-medium" for="crit_${idx}">Reactivo de alerta crítica</label>
                        </div>
                        <span class="small text-muted aviso-texto-libre d-none">
                            <i class="bi bi-info-circle me-1"></i>Las respuestas abiertas se registran textualmente sin asignar puntaje clínico automático (0 pts).
                        </span>
                    </div>
                </div>
                <div class="col-12 opciones-custom-wrap d-none">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-dark">Opciones específicas de esta pregunta</span>
                            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5 btn-add-opcion-custom" style="font-size: 0.72rem;">
                                + Añadir opción
                            </button>
                        </div>
                        <div class="opciones-custom-list d-flex flex-column gap-2"></div>
                    </div>
                </div>
            </div>
        `;

        const selectTipo = card.querySelector('.select-tipo-pregunta');
        const opcionesWrap = card.querySelector('.opciones-custom-wrap');
        const opcionesList = card.querySelector('.opciones-custom-list');
        const avisoTextoLibre = card.querySelector('.aviso-texto-libre');
        const flagsClinicos = card.querySelectorAll('.wrap-flag-clinico');
        let opCustomIdx = 0;

        function addCustomOptionRow(opData = {}) {
            const oIdx = opCustomIdx++;
            const opRow = document.createElement('div');
            opRow.className = 'row g-2 align-items-center opcion-custom-item';
            opRow.innerHTML = `
                <div class="col-md-8">
                    <input type="text" name="preguntas[${idx}][opciones][${oIdx}][etiqueta]" class="form-control form-control-sm rounded-3" value="${(opData.etiqueta || '').replace(/"/g, '&quot;')}" placeholder="Texto de la opción">
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Pts</span>
                        <input type="number" name="preguntas[${idx}][opciones][${oIdx}][valor]" class="form-control" value="${opData.valor ?? 0}">
                    </div>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-op-custom"><i class="bi bi-x-circle"></i></button>
                </div>
            `;
            opRow.querySelector('.btn-remove-op-custom').addEventListener('click', function () {
                if (opcionesList.querySelectorAll('.opcion-custom-item').length > 2) {
                    opRow.remove();
                }
            });
            opcionesList.appendChild(opRow);
        }

        opciones.forEach(o => addCustomOptionRow(o));
        card.querySelector('.btn-add-opcion-custom').addEventListener('click', function () {
            addCustomOptionRow({ etiqueta: '', valor: 0 });
        });

        function syncTipoUI() {
            const t = selectTipo.value;
            const mostrarOpciones = (t === 'OPCION_UNICA' || t === 'OPCION_MULTIPLE');
            opcionesWrap.classList.toggle('d-none', !mostrarOpciones);
            avisoTextoLibre.classList.toggle('d-none', t !== 'TEXTO_LIBRE');
            flagsClinicos.forEach(el => el.classList.toggle('d-none', t === 'TEXTO_LIBRE'));
        }

        selectTipo.addEventListener('change', syncTipoUI);
        syncTipoUI();

        card.querySelector('.btn-remove-pregunta').addEventListener('click', function () {
            card.remove();
            renumerarPreguntas();
        });

        card.querySelector('.btn-move-up').addEventListener('click', function () {
            const prev = card.previousElementSibling;
            if (prev) {
                preguntasContainer.insertBefore(card, prev);
                renumerarPreguntas();
            }
        });

        card.querySelector('.btn-move-down').addEventListener('click', function () {
            const next = card.nextElementSibling;
            if (next) {
                preguntasContainer.insertBefore(next, card);
                renumerarPreguntas();
            }
        });

        preguntasContainer.appendChild(card);
        renumerarPreguntas();
    }

    Object.values(oldPreguntas).forEach(p => addPreguntaCard(p));
    document.getElementById('btnAgregarPregunta')?.addEventListener('click', function () {
        addPreguntaCard({ enunciado: '', tipo_pregunta: 'ESCALA', es_obligatoria: 1 });
    });

    // 3. Baremos
    let baremoIdx = 0;
    const defaultBaremos = @json($initialBaremosCreate);

    function addBaremoRow(data = {}) {
        const b = baremoIdx++;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center baremo-row bg-light p-2.5 rounded-3 border';
        const sugiere = (data.sugiere_orientacion == 1 || data.sugiere_orientacion === true);
        row.innerHTML = `
            <div class="col-md-1">
                <label class="form-label small text-muted mb-0">Mín</label>
                <input type="number" name="baremos[${b}][puntuacion_min]" class="form-control form-control-sm rounded-3" value="${data.puntuacion_min ?? 0}">
            </div>
            <div class="col-md-1">
                <label class="form-label small text-muted mb-0">Máx</label>
                <input type="number" name="baremos[${b}][puntuacion_max]" class="form-control form-control-sm rounded-3" value="${data.puntuacion_max ?? 10}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Nivel Interpretativo</label>
                <input type="text" name="baremos[${b}][nivel]" class="form-control form-control-sm rounded-3" value="${data.nivel || ''}" placeholder="Ej. Moderado">
            </div>
            <div class="col-md-1">
                <label class="form-label small text-muted mb-0">Color</label>
                <input type="color" name="baremos[${b}][color_hex]" class="form-control form-control-sm form-control-color w-100 rounded-3" value="${data.color_hex || '#6366f1'}">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-0">Retroalimentación al Estudiante</label>
                <input type="text" name="baremos[${b}][mensaje_retroalimentacion]" class="form-control form-control-sm rounded-3" value="${(data.mensaje_retroalimentacion || '').replace(/"/g, '&quot;')}">
            </div>
            <div class="col-md-1 text-center pt-3">
                <div class="form-check d-inline-block" title="Sugerir cita de orientación">
                    <input type="checkbox" class="form-check-input" name="baremos[${b}][sugiere_orientacion]" value="1" ${sugiere ? 'checked' : ''}>
                    <label class="form-check-label small">Cita</label>
                </div>
            </div>
            <div class="col-md-1 text-end pt-3">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remove-baremo" title="Eliminar baremo">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        row.querySelector('.btn-remove-baremo').addEventListener('click', function () {
            if (baremosContainer.querySelectorAll('.baremo-row').length > 1) {
                row.remove();
            }
        });
        baremosContainer.appendChild(row);
    }

    Object.values(defaultBaremos).forEach(b => addBaremoRow(b));
    document.getElementById('btnAgregarBaremo')?.addEventListener('click', function () {
        addBaremoRow({ puntuacion_min: 0, puntuacion_max: 10, nivel: '', color_hex: '#6366f1', mensaje_retroalimentacion: '' });
    });
});
</script>
@endpush
