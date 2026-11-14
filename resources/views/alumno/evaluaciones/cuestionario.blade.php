@extends('layouts.dashboard')

@section('title', 'Cuestionario: ' . $instrumento->nombre . ' - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-2 py-md-4">
    <!-- Encabezado superior de navegación -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('alumno.evaluaciones') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" title="Volver al catálogo">
                    <span aria-hidden="true">&larr;</span>
                    <span>Catálogo</span>
                </a>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                    {{ $instrumento->categoria }}
                </span>
                <span class="badge bg-light text-muted border rounded-pill">
                    v{{ $version->version }}
                </span>
            </div>
            <h1 class="h4 fw-bold text-dark mb-0">{{ $instrumento->nombre }}</h1>
        </div>

        <div class="d-flex align-items-center gap-2 bg-light border px-3 py-2 rounded-pill small text-muted">
            <span class="portal-pulse-indicator" aria-hidden="true"></span>
            <span>Intento confidencial &bull; Autoguardado activo</span>
        </div>
    </div>

    <!-- Barra de progreso superior -->
    <div class="dashboard-section-card mb-4 p-4 shadow-sm">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <span class="small fw-semibold text-dark" id="indicadorPasoTexto">
                Pregunta <span id="numPasoDisplay">{{ $primerPasoPendiente + 1 }}</span> de {{ $totalPreguntas }}
            </span>
            <span class="small fw-bold text-primary" id="porcentajeTexto">
                {{ $totalPreguntas > 0 ? round(($totalRespondidas / $totalPreguntas) * 100) : 0 }}% completado
            </span>
        </div>

        <div class="progress" style="height: 10px; border-radius: 999px; background-color: #ede9fe;">
            <div
                class="progress-bar"
                id="barraProgreso"
                role="progressbar"
                style="width: {{ $totalPreguntas > 0 ? round(($totalRespondidas / $totalPreguntas) * 100) : 0 }}%; background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); transition: width 0.35s ease;"
                aria-valuenow="{{ $totalPreguntas > 0 ? round(($totalRespondidas / $totalPreguntas) * 100) : 0 }}"
                aria-valuemin="0"
                aria-valuemax="100">
            </div>
        </div>

        <!-- Navegador de reactivos (píldoras de paso) -->
        <div class="d-flex flex-wrap gap-2 mt-3 pt-2 border-top" id="navegadorPilas">
            @foreach ($preguntas as $idx => $preg)
                @php
                    $estaRespondida = isset($respuestasGuardadas[$preg->pregunta_id]);
                    $esActiva = ($idx === $primerPasoPendiente);
                @endphp
                <button
                    type="button"
                    class="btn btn-sm evaluacion-step-dot {{ $estaRespondida ? 'is-answered' : '' }} {{ $esActiva ? 'is-current' : '' }}"
                    id="dot-{{ $idx }}"
                    data-target-step="{{ $idx }}"
                    title="Reactivo {{ $preg->numero_reactivo }}{{ $estaRespondida ? ' (Respondido)' : ' (Pendiente)' }}"
                    aria-label="Ir al reactivo {{ $preg->numero_reactivo }}">
                    {{ $preg->numero_reactivo }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Contenedor interactivo de pasos (una pregunta por paso) -->
    <div class="dashboard-section-card p-4 p-md-5 shadow-sm" id="cuestionarioContainer">
        @foreach ($preguntas as $index => $pregunta)
            @php
                $esPasoInicial = ($index === $primerPasoPendiente);
                $opcionSeleccionadaId = $respuestasGuardadas[$pregunta->pregunta_id] ?? null;
                $detalleResp = $respuestasDetalle[$pregunta->pregunta_id] ?? ['escala_id' => null, 'respuesta_texto' => null, 'opciones_seleccionadas' => []];
                $tipoPregunta = strtoupper((string) ($pregunta->tipo_pregunta ?: 'ESCALA'));
                $esObligatoria = $pregunta->es_obligatoria ?? true;
                $opcionesPregunta = is_array($pregunta->opciones_json) ? $pregunta->opciones_json : [];
            @endphp
            <div
                class="cuestionario-step {{ $esPasoInicial ? '' : 'd-none' }}"
                id="step-card-{{ $index }}"
                data-step-index="{{ $index }}"
                data-pregunta-id="{{ $pregunta->pregunta_id }}"
                data-tipo-pregunta="{{ $tipoPregunta }}"
                data-es-obligatoria="{{ $esObligatoria ? '1' : '0' }}">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill small fw-semibold">
                            Reactivo {{ $pregunta->numero_reactivo }} de {{ $totalPreguntas }}
                        </span>
                        @if (!$esObligatoria)
                            <span class="badge bg-light text-secondary border rounded-pill small">Opcional</span>
                        @endif
                    </div>
                    <span class="small text-muted autosave-label" id="autosave-label-{{ $pregunta->pregunta_id }}">
                        {{ $opcionSeleccionadaId !== null ? '✓ Respuesta guardada' : ($tipoPregunta === 'TEXTO_LIBRE' ? 'Escribe tu respuesta' : 'Selecciona una opción') }}
                    </span>
                </div>

                <h2 class="h4 fw-bold text-dark mb-3 py-2 lh-base">
                    {{ $pregunta->enunciado }}
                </h2>
                @if (!empty($pregunta->descripcion_ayuda))
                    <p class="text-muted small mb-3">{{ $pregunta->descripcion_ayuda }}</p>
                @endif

                @if ($tipoPregunta === 'TEXTO_LIBRE')
                    <div class="mb-4">
                        <textarea
                            class="form-control rounded-4 p-3 texto-libre-input"
                            id="texto_libre_{{ $pregunta->pregunta_id }}"
                            rows="4"
                            maxlength="2000"
                            data-pregunta-id="{{ $pregunta->pregunta_id }}"
                            placeholder="Escribe aquí tu reflexión o respuesta abierta...">{{ $detalleResp['respuesta_texto'] ?? '' }}</textarea>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="text-muted small"><i class="bi bi-shield-lock me-1"></i>Respuesta abierta formativa (sin puntuación clínica automática).</span>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-guardar-texto-libre" data-pregunta-id="{{ $pregunta->pregunta_id }}">
                                Guardar respuesta
                            </button>
                        </div>
                    </div>
                @elseif ($tipoPregunta === 'OPCION_MULTIPLE' && !empty($opcionesPregunta))
                    <p class="small text-muted mb-3"><i class="bi bi-check2-square me-1"></i>Puedes seleccionar una o varias opciones:</p>
                    <div class="row g-3 mb-4">
                        @foreach ($opcionesPregunta as $opIdx => $opItem)
                            @php
                                $etiquetaOp = (string) ($opItem['etiqueta'] ?? $opItem['texto'] ?? ('Opción ' . ($opIdx + 1)));
                                $seleccionadasArr = is_array($detalleResp['opciones_seleccionadas']) ? $detalleResp['opciones_seleccionadas'] : [];
                                $isChecked = in_array($etiquetaOp, $seleccionadasArr, true);
                            @endphp
                            <div class="col-12 col-md-6">
                                <label class="evaluacion-radio-card {{ $isChecked ? 'is-selected' : '' }}"
                                       for="p{{ $pregunta->pregunta_id }}_chk{{ $opIdx }}">
                                    <input
                                        type="checkbox"
                                        class="d-none checkbox-opcion-input"
                                        name="pregunta_multi_{{ $pregunta->pregunta_id }}[]"
                                        id="p{{ $pregunta->pregunta_id }}_chk{{ $opIdx }}"
                                        value="{{ $etiquetaOp }}"
                                        data-pregunta-id="{{ $pregunta->pregunta_id }}"
                                        {{ $isChecked ? 'checked' : '' }}>
                                    <div class="radio-card-check" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-bold text-dark opcion-titulo">{{ $etiquetaOp }}</span>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @elseif ($tipoPregunta === 'OPCION_UNICA' && !empty($opcionesPregunta))
                    <div class="row g-3 mb-4">
                        @foreach ($opcionesPregunta as $opIdx => $opItem)
                            @php
                                $etiquetaOp = (string) ($opItem['etiqueta'] ?? $opItem['texto'] ?? ('Opción ' . ($opIdx + 1)));
                                $seleccionadasArr = is_array($detalleResp['opciones_seleccionadas']) ? $detalleResp['opciones_seleccionadas'] : [];
                                $isSelected = in_array($etiquetaOp, $seleccionadasArr, true) || ($detalleResp['respuesta_texto'] === $etiquetaOp);
                            @endphp
                            <div class="col-12 col-md-6">
                                <label class="evaluacion-radio-card {{ $isSelected ? 'is-selected' : '' }}"
                                       for="p{{ $pregunta->pregunta_id }}_uopt{{ $opIdx }}">
                                    <input
                                        type="radio"
                                        class="d-none radio-unica-custom-input"
                                        name="pregunta_{{ $pregunta->pregunta_id }}"
                                        id="p{{ $pregunta->pregunta_id }}_uopt{{ $opIdx }}"
                                        value="{{ $etiquetaOp }}"
                                        data-pregunta-id="{{ $pregunta->pregunta_id }}"
                                        {{ $isSelected ? 'checked' : '' }}>
                                    <div class="radio-card-check" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-bold text-dark opcion-titulo">{{ $etiquetaOp }}</span>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Opciones de escala tipo radio-card -->
                    <div class="row g-3 mb-4">
                        @foreach ($escalas as $opcion)
                            @php
                                $isSelected = ($detalleResp['escala_id'] === $opcion->escala_id || $opcionSeleccionadaId === $opcion->escala_id);
                            @endphp
                            <div class="col-12 col-md-6">
                                <label
                                    class="evaluacion-radio-card {{ $isSelected ? 'is-selected' : '' }}"
                                    for="p{{ $pregunta->pregunta_id }}_opt{{ $opcion->escala_id }}">
                                    <input
                                        type="radio"
                                        class="d-none radio-opcion-input"
                                        name="pregunta_{{ $pregunta->pregunta_id }}"
                                        id="p{{ $pregunta->pregunta_id }}_opt{{ $opcion->escala_id }}"
                                        value="{{ $opcion->escala_id }}"
                                        data-pregunta-id="{{ $pregunta->pregunta_id }}"
                                        {{ $isSelected ? 'checked' : '' }}>

                                    <div class="radio-card-check" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-dark opcion-titulo">{{ $opcion->etiqueta }}</span>
                                        </div>
                                        @if ($opcion->descripcion)
                                            <p class="text-muted small mb-0 mt-1">{{ $opcion->descripcion }}</p>
                                        @endif
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <!-- Controles de Navegación Inferior -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-4 border-top">
            <button
                type="button"
                class="btn btn-outline-secondary px-4"
                id="btnAnterior"
                {{ $primerPasoPendiente === 0 ? 'disabled' : '' }}>
                &larr; Anterior
            </button>

            <div class="d-flex align-items-center gap-2">
                <button
                    type="button"
                    class="btn btn-portal-primary px-4 {{ $primerPasoPendiente === ($totalPreguntas - 1) ? 'd-none' : '' }}"
                    id="btnSiguiente">
                    <span>Siguiente</span>
                    <span aria-hidden="true">&rarr;</span>
                </button>

                <button
                    type="button"
                    class="btn btn-success px-4 {{ $primerPasoPendiente === ($totalPreguntas - 1) ? '' : 'd-none' }}"
                    id="btnFinalizar"
                    data-bs-toggle="modal"
                    data-bs-target="#modalConfirmarFinalizacion">
                    <span>Finalizar evaluación</span>
                    <span aria-hidden="true">&check;</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Finalizar la Evaluación -->
<div class="modal fade" id="modalConfirmarFinalizacion" tabindex="-1" aria-labelledby="modalFinalizarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4" style="background: var(--bienestar-lavender-soft);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-circle bg-white text-primary shadow-sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="modal-title h6 fw-bold mb-0 text-dark" id="modalFinalizarLabel">Confirmar finalización de la evaluación</h2>
                        <span class="text-muted small">Cuestionario: {{ $instrumento->siglas ?? $instrumento->codigo }}</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalAvisoCompleto">
                    <p class="mb-3 text-dark">
                        Has respondido todas las preguntas de este cuestionario (<strong id="modalConteoPreguntas">{{ $totalPreguntas }} de {{ $totalPreguntas }}</strong>).
                    </p>
                    <div class="alert alert-light border border-primary-subtle rounded-3 p-3 mb-0" id="alertaRegistroDefinitivo">
                        <div class="d-flex gap-2 align-items-start">
                            <span class="text-primary fs-5" aria-hidden="true">🔒</span>
                            <div class="small text-muted">
                                <strong>Registro definitivo:</strong> Una vez finalizada, tus respuestas quedarán almacenadas de manera confidencial en tu expediente. No podrás realizar modificaciones posteriores en este intento.
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info border-info-subtle rounded-3 p-3 mb-0 d-none" id="alertaGuardandoEnServidor">
                        <div class="d-flex align-items-center gap-2 text-primary small">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <span>Guardando respuestas en el servidor, por favor espera...</span>
                        </div>
                    </div>
                    <div class="alert alert-danger border-danger-subtle rounded-3 p-3 mb-0 d-none" id="alertaErroresPendientes">
                        <div class="d-flex align-items-start gap-2 text-danger small">
                            <span class="fs-5" aria-hidden="true">⚠</span>
                            <div>
                                <strong>Respuestas con error de guardado:</strong> Una o más respuestas no se pudieron registrar en el servidor por un fallo de conexión. Cierra este diálogo, revisa las preguntas marcadas y presiona <em>Reintentar</em> antes de finalizar.
                            </div>
                        </div>
                    </div>
                </div>

                <div id="modalAvisoIncompleto" class="d-none">
                    <div class="alert alert-warning border-warning-subtle rounded-3 p-3 mb-0">
                        <div class="d-flex gap-2 align-items-start">
                            <span class="text-warning fs-5" aria-hidden="true">⚠</span>
                            <div class="small text-dark">
                                <strong>Preguntas pendientes:</strong> Aún tienes preguntas obligatorias sin contestar (<span id="modalFaltantesConteo"></span>). Debes responder todos los reactivos antes de poder finalizar formalmente.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Continuar revisando
                </button>
                <form method="POST" action="{{ route('alumno.evaluaciones.finalizar', $intento->intento_id) }}" id="formFinalizarIntento">
                    @csrf
                    <button type="submit" class="btn btn-success px-4" id="btnConfirmarEnvio">
                        Sí, finalizar evaluación
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const totalPreguntas = {{ $totalPreguntas }};
    let pasoActual = {{ $primerPasoPendiente }};
    const respuestasMap = @json($respuestasGuardadas);
    const intentoId = {{ $intento->intento_id }};
    const csrfToken = '{{ csrf_token() }}';
    const urlGuardarRespuesta = '{{ route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id) }}';

    // Mapeo estable de ID de pregunta a índice de paso (inmune a navegación rápida)
    const preguntaIdToStepIndex = {};
    const preguntasObligatoriasIds = [];
    document.querySelectorAll('.cuestionario-step').forEach(function (card) {
        const stepIdx = parseInt(card.dataset.stepIndex, 10);
        const pregId = parseInt(card.dataset.preguntaId, 10);
        preguntaIdToStepIndex[pregId] = stepIdx;
        if (card.dataset.esObligatoria !== '0') {
            preguntasObligatoriasIds.push(pregId);
        }
    });

    // Control de peticiones de autoguardado en vuelo, errores y timestamps para evitar condiciones de carrera
    const guardandoPreguntas = new Set();
    const preguntasConError = new Set();
    const ultimaPeticionPorPregunta = new Map();

    const barraProgreso = document.getElementById('barraProgreso');
    const porcentajeTexto = document.getElementById('porcentajeTexto');
    const numPasoDisplay = document.getElementById('numPasoDisplay');
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnFinalizar = document.getElementById('btnFinalizar');
    const btnConfirmarEnvio = document.getElementById('btnConfirmarEnvio');
    const modalAvisoCompleto = document.getElementById('modalAvisoCompleto');
    const modalAvisoIncompleto = document.getElementById('modalAvisoIncompleto');
    const modalConfirmarModalEl = document.getElementById('modalConfirmarFinalizacion');
    const formFinalizar = document.getElementById('formFinalizarIntento');

    // 1. Mostrar un paso específico
    function mostrarPaso(nuevoPaso) {
        if (nuevoPaso < 0 || nuevoPaso >= totalPreguntas) return;

        // Ocultar paso actual
        const pasoActualEl = document.getElementById(`step-card-${pasoActual}`);
        if (pasoActualEl) pasoActualEl.classList.add('d-none');

        // Quitar indicador de paso actual en dots
        const dotAnterior = document.getElementById(`dot-${pasoActual}`);
        if (dotAnterior) dotAnterior.classList.remove('is-current');

        // Mostrar nuevo paso
        pasoActual = nuevoPaso;
        const nuevoPasoEl = document.getElementById(`step-card-${pasoActual}`);
        if (nuevoPasoEl) nuevoPasoEl.classList.remove('d-none');

        // Actualizar indicador de paso actual en dots
        const dotNuevo = document.getElementById(`dot-${pasoActual}`);
        if (dotNuevo) dotNuevo.classList.add('is-current');

        // Actualizar contador numérico
        if (numPasoDisplay) numPasoDisplay.textContent = (pasoActual + 1);

        // Actualizar visibilidad de botones Anterior / Siguiente / Finalizar
        if (btnAnterior) btnAnterior.disabled = (pasoActual === 0);

        const esUltimoPaso = (pasoActual === totalPreguntas - 1);
        if (btnSiguiente) {
            btnSiguiente.classList.toggle('d-none', esUltimoPaso);
        }
        if (btnFinalizar) {
            btnFinalizar.classList.toggle('d-none', !esUltimoPaso);
        }

        actualizarEstadoFinalizar();
    }

    // 2. Actualizar barra de progreso y estado general
    function actualizarProgreso() {
        const respondidasCount = Object.keys(respuestasMap).length;
        const porcentaje = totalPreguntas > 0 ? Math.round((respondidasCount / totalPreguntas) * 100) : 0;

        if (barraProgreso) {
            barraProgreso.style.width = `${porcentaje}%`;
            barraProgreso.setAttribute('aria-valuenow', porcentaje);
        }
        if (porcentajeTexto) {
            porcentajeTexto.textContent = `${porcentaje}% completado`;
        }

        actualizarEstadoFinalizar();
    }

    // 3. Sincronizar estado visual de los avisos del modal de finalización
    function contarObligatoriasFaltantes() {
        return preguntasObligatoriasIds.filter(function(id) {
            return respuestasMap[id] === undefined || respuestasMap[id] === null || respuestasMap[id] === '';
        }).length;
    }

    function actualizarEstadoFinalizar() {
        const respondidasCount = Object.keys(respuestasMap).length;
        const faltantesObligatorias = contarObligatoriasFaltantes();
        const todasRespondidas = (faltantesObligatorias === 0 && totalPreguntas > 0);
        const hayGuardadosEnVuelo = (guardandoPreguntas.size > 0);
        const hayErrores = (preguntasConError.size > 0);

        const alertaRegistroDefinitivo = document.getElementById('alertaRegistroDefinitivo');
        const alertaGuardandoEnServidor = document.getElementById('alertaGuardandoEnServidor');
        const alertaErroresPendientes = document.getElementById('alertaErroresPendientes');
        const modalFaltantesConteo = document.getElementById('modalFaltantesConteo');
        const modalConteoPreguntas = document.getElementById('modalConteoPreguntas');

        if (modalConteoPreguntas) {
            modalConteoPreguntas.textContent = `${respondidasCount} de ${totalPreguntas}`;
        }

        if (modalAvisoCompleto && modalAvisoIncompleto) {
            if (todasRespondidas) {
                modalAvisoCompleto.classList.remove('d-none');
                modalAvisoIncompleto.classList.add('d-none');

                if (hayErrores) {
                    alertaErroresPendientes?.classList.remove('d-none');
                    alertaGuardandoEnServidor?.classList.add('d-none');
                    alertaRegistroDefinitivo?.classList.add('d-none');
                } else if (hayGuardadosEnVuelo) {
                    alertaGuardandoEnServidor?.classList.remove('d-none');
                    alertaErroresPendientes?.classList.add('d-none');
                    alertaRegistroDefinitivo?.classList.add('d-none');
                } else {
                    alertaRegistroDefinitivo?.classList.remove('d-none');
                    alertaGuardandoEnServidor?.classList.add('d-none');
                    alertaErroresPendientes?.classList.add('d-none');
                }
            } else {
                modalAvisoCompleto.classList.add('d-none');
                modalAvisoIncompleto.classList.remove('d-none');

                const faltantes = Math.max(0, faltantesObligatorias);
                if (modalFaltantesConteo) {
                    modalFaltantesConteo.textContent = `${faltantes} ${faltantes === 1 ? 'pregunta pendiente' : 'preguntas pendientes'}`;
                }
            }
        }

        // El botón para confirmar la finalización solo se activa si todo está respondido,
        // no hay peticiones en vuelo y no hay errores de guardado pendientes
        if (btnConfirmarEnvio) {
            btnConfirmarEnvio.disabled = (!todasRespondidas || hayGuardadosEnVuelo || hayErrores);
        }
    }

    // 4. Función centralizada y segura de envío de respuesta (soporta ESCALA, OPCION_UNICA, OPCION_MULTIPLE, TEXTO_LIBRE)
    async function enviarRespuesta(preguntaId, escalaId, pasoOrigen, extraPayload = null, autoAdvance = true) {
        const labelStatus = document.getElementById(`autosave-label-${preguntaId}`);
        if (labelStatus) {
            labelStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...';
        }

        const peticionTimestamp = Date.now();
        ultimaPeticionPorPregunta.set(preguntaId, peticionTimestamp);
        guardandoPreguntas.add(preguntaId);
        preguntasConError.delete(preguntaId);
        actualizarEstadoFinalizar();

        const payload = Object.assign({
            pregunta_id: preguntaId,
            escala_id: escalaId,
            cliente_timestamp: peticionTimestamp,
        }, extraPayload || {});

        try {
            const response = await fetch(urlGuardarRespuesta, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            // Si se disparó otra petición para esta misma pregunta mientras se esperaba respuesta, descartar
            if (ultimaPeticionPorPregunta.get(preguntaId) !== peticionTimestamp) {
                return;
            }

            if (response.ok && data.success) {
                if (data.stale) {
                    return;
                }

                // Registrar confirmación estricta desde el servidor
                respuestasMap[preguntaId] = escalaId || data.respuesta_texto || true;
                preguntasConError.delete(preguntaId);

                if (labelStatus) {
                    labelStatus.innerHTML = '<span class="text-success fw-medium">✓ Guardado en servidor</span>';
                }

                // Actualizar el dot exacto del reactivo mediante mapeo seguro
                const stepIdx = preguntaIdToStepIndex[preguntaId];
                if (stepIdx !== undefined) {
                    const dotTarget = document.getElementById(`dot-${stepIdx}`);
                    if (dotTarget) dotTarget.classList.add('is-answered');
                }

                actualizarProgreso();

                // Autoavance suave solo si el usuario sigue en el mismo reactivo
                if (autoAdvance && pasoActual === pasoOrigen && pasoActual < totalPreguntas - 1) {
                    setTimeout(function () {
                        if (pasoActual === pasoOrigen) {
                            mostrarPaso(pasoActual + 1);
                        }
                    }, 260);
                }
            } else {
                preguntasConError.add(preguntaId);
                if (labelStatus) {
                    labelStatus.innerHTML = '<span class="text-danger small fw-medium">⚠ Error al guardar.</span> <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 fw-bold text-decoration-underline btn-reintentar" data-pregunta-id="' + preguntaId + '" data-escala-id="' + (escalaId || '') + '">Reintentar</button>';
                }
            }
        } catch (err) {
            if (ultimaPeticionPorPregunta.get(preguntaId) === peticionTimestamp) {
                preguntasConError.add(preguntaId);
                if (labelStatus) {
                    labelStatus.innerHTML = '<span class="text-danger small fw-medium">⚠ Error de conexión.</span> <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 fw-bold text-decoration-underline btn-reintentar" data-pregunta-id="' + preguntaId + '" data-escala-id="' + (escalaId || '') + '">Reintentar</button>';
                }
            }
        } finally {
            if (ultimaPeticionPorPregunta.get(preguntaId) === peticionTimestamp) {
                guardandoPreguntas.delete(preguntaId);
            }
            actualizarEstadoFinalizar();
        }
    }

    // 5. Manejo de selección de opción con guardado asíncrono
    document.querySelectorAll('.radio-opcion-input').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const preguntaId = parseInt(this.dataset.preguntaId, 10);
            const escalaId = parseInt(this.value, 10);
            const pasoOrigen = pasoActual;

            const contenedorPaso = document.getElementById(`step-card-${preguntaIdToStepIndex[preguntaId] ?? pasoActual}`);
            if (contenedorPaso) {
                contenedorPaso.querySelectorAll('.evaluacion-radio-card').forEach(function (card) {
                    card.classList.remove('is-selected');
                });
                const cardSeleccionada = this.closest('.evaluacion-radio-card');
                if (cardSeleccionada) cardSeleccionada.classList.add('is-selected');
            }

            enviarRespuesta(preguntaId, escalaId, pasoOrigen);
        });
    });

    // 5b. Opción única personalizada (OPCION_UNICA)
    document.querySelectorAll('.radio-unica-custom-input').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const preguntaId = parseInt(this.dataset.preguntaId, 10);
            const valorTexto = this.value;
            const pasoOrigen = pasoActual;

            const contenedorPaso = document.getElementById(`step-card-${preguntaIdToStepIndex[preguntaId] ?? pasoActual}`);
            if (contenedorPaso) {
                contenedorPaso.querySelectorAll('.evaluacion-radio-card').forEach(function (card) {
                    card.classList.remove('is-selected');
                });
                const cardSeleccionada = this.closest('.evaluacion-radio-card');
                if (cardSeleccionada) cardSeleccionada.classList.add('is-selected');
            }

            enviarRespuesta(preguntaId, null, pasoOrigen, {
                respuesta_texto: valorTexto,
                opciones_seleccionadas: [valorTexto]
            }, true);
        });
    });

    // 5c. Selección múltiple (OPCION_MULTIPLE)
    document.querySelectorAll('.checkbox-opcion-input').forEach(function (chk) {
        chk.addEventListener('change', function () {
            const preguntaId = parseInt(this.dataset.preguntaId, 10);
            const pasoOrigen = pasoActual;
            const contenedorPaso = document.getElementById(`step-card-${preguntaIdToStepIndex[preguntaId] ?? pasoActual}`);
            const seleccionadas = [];

            if (contenedorPaso) {
                contenedorPaso.querySelectorAll('.checkbox-opcion-input').forEach(function (inputEl) {
                    const card = inputEl.closest('.evaluacion-radio-card');
                    if (inputEl.checked) {
                        if (card) card.classList.add('is-selected');
                        seleccionadas.push(inputEl.value);
                    } else {
                        if (card) card.classList.remove('is-selected');
                    }
                });
            }

            if (seleccionadas.length > 0) {
                enviarRespuesta(preguntaId, null, pasoOrigen, {
                    opciones_seleccionadas: seleccionadas,
                    respuesta_texto: seleccionadas.join(', ')
                }, false);
            }
        });
    });

    // 5d. Respuesta abierta (TEXTO_LIBRE)
    document.querySelectorAll('.btn-guardar-texto-libre').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const preguntaId = parseInt(this.dataset.preguntaId, 10);
            const textarea = document.getElementById(`texto_libre_${preguntaId}`);
            const texto = textarea ? textarea.value.trim() : '';
            if (!texto) return;

            enviarRespuesta(preguntaId, null, pasoActual, {
                respuesta_texto: texto
            }, true);
        });
    });

    // Delegación de eventos para el botón de reintento
    document.addEventListener('click', function (e) {
        const btnReintentar = e.target.closest('.btn-reintentar');
        if (btnReintentar) {
            const preguntaId = parseInt(btnReintentar.dataset.preguntaId, 10);
            const escalaId = parseInt(btnReintentar.dataset.escalaId, 10);
            enviarRespuesta(preguntaId, escalaId, pasoActual);
        }
    });

    // 6. Botones Anterior y Siguiente
    btnAnterior?.addEventListener('click', function () {
        if (pasoActual > 0) {
            mostrarPaso(pasoActual - 1);
        }
    });

    btnSiguiente?.addEventListener('click', function () {
        if (pasoActual < totalPreguntas - 1) {
            mostrarPaso(pasoActual + 1);
        }
    });

    // 7. Clicks en el navegador de puntos (dots)
    document.querySelectorAll('.evaluacion-step-dot').forEach(function (dot) {
        dot.addEventListener('click', function () {
            const targetStep = parseInt(this.dataset.targetStep, 10);
            mostrarPaso(targetStep);
        });
    });

    // 8. Sincronización del modal al abrirse
    modalConfirmarModalEl?.addEventListener('show.bs.modal', function () {
        actualizarEstadoFinalizar();
    });

    // 9. Navegación accesible por teclado (Flechas izquierda y derecha)
    document.addEventListener('keydown', function (e) {
        if (['input', 'textarea', 'select'].includes(document.activeElement?.tagName?.toLowerCase())) {
            return;
        }

        if (e.key === 'ArrowRight' && pasoActual < totalPreguntas - 1) {
            mostrarPaso(pasoActual + 1);
        } else if (e.key === 'ArrowLeft' && pasoActual > 0) {
            mostrarPaso(pasoActual - 1);
        }
    });

    // 10. Protección ante doble clic o envío con errores o solicitudes pendientes
    formFinalizar?.addEventListener('submit', function (e) {
        if (guardandoPreguntas.size > 0) {
            e.preventDefault();
            const el = document.getElementById('alertaGuardandoEnServidor');
            if (el) el.classList.remove('d-none');
            return;
        }

        if (preguntasConError.size > 0) {
            e.preventDefault();
            const el = document.getElementById('alertaErroresPendientes');
            if (el) el.classList.remove('d-none');
            return;
        }

        if (contarObligatoriasFaltantes() > 0) {
            e.preventDefault();
            const el = document.getElementById('modalAvisoIncompleto');
            if (el) el.classList.remove('d-none');
            return;
        }

        if (btnConfirmarEnvio) {
            btnConfirmarEnvio.disabled = true;
            btnConfirmarEnvio.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Finalizando...';
        }
    });

    // Inicializar estado al cargar
    actualizarProgreso();
});
</script>
@endpush

