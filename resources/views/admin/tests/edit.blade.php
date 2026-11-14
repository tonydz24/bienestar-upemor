@extends('layouts.dashboard')

@section('title', 'Editar Test: ' . $test->nombre . ' - Administración Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.tests.show', $test->instrumento_id) }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Volver a la ficha
            </a>
            <div>
                <h1 class="h3 fw-bold text-slate-800 mb-0">Editar Metadatos del Instrumento</h1>
                <span class="text-muted small">{{ $test->codigo }} &bull; {{ $test->nombre }}</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.tests.resultados', $test->instrumento_id) }}" class="btn btn-outline-info rounded-pill btn-sm px-3 fw-semibold">
                <i class="bi bi-bar-chart-line me-1"></i> Ver Resultados
            </a>
            @if(!$puedeEditarReactivos)
                <form action="{{ route('admin.tests.nueva-version', $test->instrumento_id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-pill btn-sm px-3 fw-semibold shadow-xs">
                        <i class="bi bi-layers-half me-1"></i> Crear nueva versión editable
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Banner Metodológico y Deontológico -->
    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
        <div class="fs-4 text-primary flex-shrink-0">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <strong class="d-block text-dark small mb-1">Aviso Institucional de Confidencialidad y Ética Psicométrica</strong>
            <p class="small text-muted mb-0">
                Aviso Institucional: Los instrumentos disponibles son herramientas psicométricas estandarizadas de tamizaje preliminar. No constituyen un diagnóstico definitivo ni reemplazan la valoración personalizada de un especialista en salud mental.
            </p>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div class="fw-semibold">{{ session('status') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div class="fw-semibold">Por favor revisa los errores señalados:</div>
            </div>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- Selector de versión si existen múltiples versiones -->
    @if($test->versiones->count() > 1)
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="small fw-semibold text-dark">
                    <i class="bi bi-clock-history text-primary me-1"></i> Versiones registradas de este instrumento:
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($test->versiones as $verItem)
                        <a href="{{ route('admin.tests.edit', ['test' => $test->instrumento_id, 'version_id' => $verItem->version_id]) }}"
                           class="btn btn-sm rounded-pill px-3 {{ $versionEditable && $versionEditable->version_id === $verItem->version_id ? 'btn-primary' : 'btn-outline-secondary' }}">
                            v{{ $verItem->version }} ({{ $verItem->estado }} &bull; {{ $verItem->intentos_count }} resp.)
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if(!$puedeEditarReactivos && $versionEditable)
        <!-- A4: Aviso de Protección cuando la versión ya tiene respuestas registradas -->
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-lock-fill fs-3 text-warning-emphasis"></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Protección de Respuestas Históricas Activa (Versión v{{ $versionEditable->version }})</h6>
                        <p class="small text-dark mb-0">
                            Esta versión registra <strong>{{ $intentosVersionCount }} aplicación(es) de estudiantes</strong>. Para preservar la validez metodológica y evitar corrupción de datos históricos, no se permite modificar ni eliminar directamente sus preguntas, escalas ni baremos. Puedes actualizar los metadatos generales (como el intervalo de repetición) o generar una <strong>nueva versión editable</strong>.
                        </p>
                    </div>
                </div>
                <form action="{{ route('admin.tests.nueva-version', $test->instrumento_id) }}" method="POST" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-xs">
                        <i class="bi bi-plus-square-dotted me-1"></i> Crear nueva versión
                    </button>
                </form>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.tests.update', $test->instrumento_id) }}" method="POST" id="formEditarTest">
        @csrf
        @method('PUT')
        @if($versionEditable)
            <input type="hidden" name="version_id" value="{{ $versionEditable->version_id }}">
            <input type="hidden" name="estado_version" id="inputEstadoVersionEdit" value="{{ old('estado_version', $versionEditable->estado === 'OBSOLETA' ? 'BORRADOR' : $versionEditable->estado) }}">
        @endif

        <!-- SECCIÓN 1: METADATOS DEL INSTRUMENTO -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                    <i class="bi bi-clipboard2-pulse text-primary me-2"></i>1. Metadatos y Configuración Operativa
                </h5>

                <div class="row g-4">
                    <div class="col-md-8">
                        <label for="nombre" class="form-label fw-semibold text-dark small">Nombre Oficial del Test <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="nombre" name="nombre" value="{{ old('nombre', $test->nombre) }}" required maxlength="150">
                    </div>

                    <div class="col-md-4">
                        <label for="siglas" class="form-label fw-semibold text-dark small">Siglas Institucionales</label>
                        <input type="text" class="form-control rounded-3" id="siglas" name="siglas" value="{{ old('siglas', $test->siglas) }}" maxlength="20">
                    </div>

                    <div class="col-md-5">
                        <label for="categoria" class="form-label fw-semibold text-dark small">Categoría del Instrumento <span class="text-danger">*</span></label>
                        <input list="categoriasList" class="form-control rounded-3" id="categoria" name="categoria" value="{{ old('categoria', $test->categoria) }}" required>
                        <datalist id="categoriasList">
                            @foreach($categoriasSugeridas as $sug)
                                <option value="{{ $sug }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label for="dias_espera_reaplicacion" class="form-label fw-semibold text-dark small">Espera para Reaplicación (Días) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3" id="dias_espera_reaplicacion" name="dias_espera_reaplicacion" value="{{ old('dias_espera_reaplicacion', $test->dias_espera_reaplicacion) }}" min="0" max="365" required>
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
                        <input type="number" class="form-control rounded-3" id="anio_publicacion" name="anio_publicacion" value="{{ old('anio_publicacion', $test->anio_publicacion) }}" min="1900" max="2100">
                    </div>

                    <div class="col-md-12">
                        <label for="autor" class="form-label fw-semibold text-dark small">Autor(es) / Referencia Metodológica</label>
                        <input type="text" class="form-control rounded-3" id="autor" name="autor" value="{{ old('autor', $test->autor) }}" maxlength="150">
                    </div>

                    <div class="col-md-12">
                        <label for="descripcion" class="form-label fw-semibold text-dark small">Descripción y Propósito Institucional <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" id="descripcion" name="descripcion" rows="3" required maxlength="1000">{{ old('descripcion', $test->descripcion) }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check form-switch p-3 bg-light rounded-3">
                            <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="activo" name="activo" value="1" @checked(old('activo', $test->activo))>
                            <label class="form-check-label fw-semibold text-dark" for="activo">
                                Publicar y mantener disponible para la comunidad estudiantil
                            </label>
                            <div class="form-text small ms-0">
                                Al estar activo, los estudiantes podrán ver el test en su catálogo y responder la versión publicada vigente.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($puedeEditarReactivos && $versionEditable)
            <!-- SECCIÓN 2: PARÁMETROS DE LA VERSIÓN EDITABLE (SIN RESPUESTAS) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-layers text-primary me-2"></i>2. Parámetros de la Versión v{{ $versionEditable->version }} (Sin respuestas previas — Editable)
                        </h5>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                            Edición habilitada (0 respuestas registradas)
                        </span>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="marco_temporal" class="form-label fw-semibold text-dark small">Marco Temporal</label>
                            <input type="text" class="form-control rounded-3" id="marco_temporal" name="marco_temporal" value="{{ old('marco_temporal', $versionEditable->marco_temporal) }}" maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label for="duracion_estimada_min" class="form-label fw-semibold text-dark small">Duración Estimada (Minutos)</label>
                            <input type="number" class="form-control rounded-3" id="duracion_estimada_min" name="duracion_estimada_min" value="{{ old('duracion_estimada_min', $versionEditable->duracion_estimada_min) }}" min="1" max="120">
                        </div>
                        <div class="col-md-4">
                            <label for="puntos_recompensa" class="form-label fw-semibold text-dark small">Puntos de Recompensa</label>
                            <input type="number" class="form-control rounded-3" id="puntos_recompensa" name="puntos_recompensa" value="{{ old('puntos_recompensa', $versionEditable->puntos_recompensa) }}" min="0" max="500">
                        </div>
                        <div class="col-md-12">
                            <label for="instrucciones" class="form-label fw-semibold text-dark small">Instrucciones para el Estudiante</label>
                            <textarea class="form-control rounded-3" id="instrucciones" name="instrucciones" rows="3" maxlength="2000">{{ old('instrucciones', $versionEditable->instrucciones) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: ESCALAS DE LA VERSIÓN -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <i class="bi bi-sliders text-primary me-2"></i>3. Escala Global de Respuesta (v{{ $versionEditable->version }})
                            </h5>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnAgregarEscala">
                            <i class="bi bi-plus-lg me-1"></i> Agregar Opción de Escala
                        </button>
                    </div>
                    <div id="escalasContainer" class="d-flex flex-column gap-2"></div>
                </div>
            </div>

            <!-- SECCIÓN 4: REACTIVOS / PREGUNTAS DE LA VERSIÓN -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <i class="bi bi-list-check text-primary me-2"></i>4. Reactivos y Preguntas (v{{ $versionEditable->version }})
                            </h5>
                            <p class="text-muted small mb-0">Puedes agregar, editar, cambiar el tipo de pregunta, reordenar o eliminar reactivos.</p>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold" id="btnAgregarPregunta">
                            <i class="bi bi-plus-circle me-1"></i> Agregar Pregunta
                        </button>
                    </div>
                    <div id="preguntasContainer" class="d-flex flex-column gap-3"></div>
                </div>
            </div>

            <!-- SECCIÓN 5: BAREMOS DE LA VERSIÓN -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <i class="bi bi-bar-chart-steps text-primary me-2"></i>5. Baremos de Interpretación (v{{ $versionEditable->version }})
                            </h5>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnAgregarBaremo">
                            <i class="bi bi-plus-lg me-1"></i> Agregar Baremo
                        </button>
                    </div>
                    <div id="baremosContainer" class="d-flex flex-column gap-2"></div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <a href="{{ route('admin.tests.show', $test->instrumento_id) }}" class="btn btn-outline-secondary rounded-pill px-4">
                    Cancelar
                </a>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($puedeEditarReactivos && $versionEditable)
                        <button type="submit" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" id="btnGuardarComoBorradorEdit">
                            <i class="bi bi-file-earmark-text me-1"></i> Guardar como Borrador
                        </button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm" id="btnPublicarVersionEdit">
                            <i class="bi bi-rocket-takeoff me-1"></i> Guardar y Publicar Versión v{{ $versionEditable->version }}
                        </button>
                    @else
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const diasInput = document.getElementById('dias_espera_reaplicacion');
    document.querySelectorAll('.btn-preset-dias').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (diasInput) diasInput.value = this.getAttribute('data-dias');
        });
    });

    @if($puedeEditarReactivos && $versionEditable)
    const escalasContainer = document.getElementById('escalasContainer');
    const preguntasContainer = document.getElementById('preguntasContainer');
    const baremosContainer = document.getElementById('baremosContainer');
    const inputEstadoVersionEdit = document.getElementById('inputEstadoVersionEdit');
    const chkActivo = document.getElementById('activo');

    document.getElementById('btnGuardarComoBorradorEdit')?.addEventListener('click', function () {
        if (inputEstadoVersionEdit) inputEstadoVersionEdit.value = 'BORRADOR';
    });

    document.getElementById('btnPublicarVersionEdit')?.addEventListener('click', function () {
        if (inputEstadoVersionEdit) inputEstadoVersionEdit.value = 'PUBLICADA';
        if (chkActivo) chkActivo.checked = true;
    });

    @php
        $initialEscalas = old('escalas', $versionEditable->escalas->map(fn($e) => [
            'valor' => $e->valor,
            'etiqueta' => $e->etiqueta,
            'descripcion' => $e->descripcion,
        ])->values()->toArray());
        if (empty($initialEscalas)) {
            $initialEscalas = [
                ['valor' => 0, 'etiqueta' => 'Nunca / Ningún día', 'descripcion' => '0 puntos'],
                ['valor' => 1, 'etiqueta' => 'Varios días', 'descripcion' => '1 punto'],
                ['valor' => 2, 'etiqueta' => 'Más de la mitad de los días', 'descripcion' => '2 puntos'],
                ['valor' => 3, 'etiqueta' => 'Casi todos los días', 'descripcion' => '3 puntos'],
            ];
        }

        $initialPreguntas = old('preguntas', $versionEditable->preguntas->map(fn($p) => [
            'enunciado' => $p->enunciado,
            'tipo_pregunta' => $p->tipo_pregunta ?? 'ESCALA',
            'es_obligatoria' => $p->es_obligatoria ?? true,
            'es_inverso' => $p->es_inverso,
            'es_critico' => $p->es_critico,
            'opciones' => $p->opciones_json ?? [],
        ])->values()->toArray());

        $initialBaremos = old('baremos', $versionEditable->baremos->map(fn($b) => [
            'puntuacion_min' => $b->puntuacion_min,
            'puntuacion_max' => $b->puntuacion_max,
            'nivel' => $b->nivel,
            'color_hex' => $b->color_hex,
            'mensaje_retroalimentacion' => $b->mensaje_retroalimentacion,
            'sugiere_orientacion' => $b->sugiere_orientacion,
        ])->values()->toArray());
    @endphp

    let escalaIdx = 0;
    const defaultEscalas = @json($initialEscalas);

    function addEscalaRow(data = {}) {
        const i = escalaIdx++;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center escala-row bg-light p-2.5 rounded-3 border';
        row.innerHTML = `
            <div class="col-md-2">
                <label class="form-label small text-muted mb-0">Puntos / Valor</label>
                <input type="number" name="escalas[${i}][valor]" class="form-control form-control-sm rounded-3" value="${data.valor ?? 0}">
            </div>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-0">Etiqueta de Opción</label>
                <input type="text" name="escalas[${i}][etiqueta]" class="form-control form-control-sm rounded-3" value="${(data.etiqueta || '').replace(/"/g, '&quot;')}">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-0">Descripción opcional</label>
                <input type="text" name="escalas[${i}][descripcion]" class="form-control form-control-sm rounded-3" value="${(data.descripcion || '').replace(/"/g, '&quot;')}">
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

    let pregIdx = 0;
    const defaultPreguntas = @json($initialPreguntas);

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
        const opciones = Array.isArray(data.opciones) && data.opciones.length > 0 ? data.opciones : [
            { etiqueta: 'Opción A', valor: 0 },
            { etiqueta: 'Opción B', valor: 1 }
        ];

        const card = document.createElement('div');
        card.className = 'card border rounded-4 shadow-xs p-3 pregunta-card bg-white';
        card.innerHTML = `
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 pregunta-num-badge">Reactivo</span>
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
                    <input type="text" name="preguntas[${idx}][enunciado]" class="form-control rounded-3" value="${(data.enunciado || '').replace(/"/g, '&quot;')}">
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
                            <input class="form-check-input" type="checkbox" name="preguntas[${idx}][es_obligatoria]" value="1" id="edit_oblig_${idx}" ${esOblig ? 'checked' : ''}>
                            <label class="form-check-label small fw-semibold" for="edit_oblig_${idx}">Obligatoria</label>
                        </div>
                        <div class="form-check form-switch mb-0 wrap-flag-clinico">
                            <input type="hidden" name="preguntas[${idx}][es_inverso]" value="0">
                            <input class="form-check-input" type="checkbox" name="preguntas[${idx}][es_inverso]" value="1" id="edit_inv_${idx}" ${esInverso ? 'checked' : ''}>
                            <label class="form-check-label small" for="edit_inv_${idx}">Puntaje inverso</label>
                        </div>
                        <div class="form-check form-switch mb-0 wrap-flag-clinico">
                            <input type="hidden" name="preguntas[${idx}][es_critico]" value="0">
                            <input class="form-check-input" type="checkbox" name="preguntas[${idx}][es_critico]" value="1" id="edit_crit_${idx}" ${esCritico ? 'checked' : ''}>
                            <label class="form-check-label small text-danger fw-medium" for="edit_crit_${idx}">Reactivo de alerta crítica</label>
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

    Object.values(defaultPreguntas).forEach(p => addPreguntaCard(p));
    document.getElementById('btnAgregarPregunta')?.addEventListener('click', function () {
        addPreguntaCard({ enunciado: '', tipo_pregunta: 'ESCALA', es_obligatoria: 1 });
    });

    let baremoIdx = 0;
    const defaultBaremos = @json($initialBaremos);

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
                <input type="text" name="baremos[${b}][nivel]" class="form-control form-control-sm rounded-3" value="${(data.nivel || '').replace(/"/g, '&quot;')}">
            </div>
            <div class="col-md-1">
                <label class="form-label small text-muted mb-0">Color</label>
                <input type="color" name="baremos[${b}][color_hex]" class="form-control form-control-sm form-control-color w-100 rounded-3" value="${data.color_hex || '#6366f1'}">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-0">Retroalimentación</label>
                <input type="text" name="baremos[${b}][mensaje_retroalimentacion]" class="form-control form-control-sm rounded-3" value="${(data.mensaje_retroalimentacion || '').replace(/"/g, '&quot;')}">
            </div>
            <div class="col-md-1 text-center pt-3">
                <div class="form-check d-inline-block">
                    <input type="checkbox" class="form-check-input" name="baremos[${b}][sugiere_orientacion]" value="1" ${sugiere ? 'checked' : ''}>
                    <label class="form-check-label small">Cita</label>
                </div>
            </div>
            <div class="col-md-1 text-end pt-3">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remove-baremo">
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
    @endif
});
</script>
@endpush
