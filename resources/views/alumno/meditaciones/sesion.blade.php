@extends('layouts.dashboard')

@section('title', 'Sesión: ' . $meditacion->titulo . ' - Bienestar UPEMOR')

@push('styles')
<style>
    .session-container {
        max-width: 800px;
        margin: 0 auto;
        min-height: 520px;
    }
    .breathing-stage {
        position: relative;
        width: 260px;
        height: 260px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .breathing-ring-outer {
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        border: 2px dashed rgba(124, 58, 237, 0.25);
        animation: spinSlow 30s linear infinite;
    }
    .breathing-circle {
        width: 170px;
        height: 170px;
        border-radius: 50%;
        background: radial-gradient(circle, #a78bfa 0%, #7c3aed 100%);
        box-shadow: 0 0 35px rgba(124, 58, 237, 0.45);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        transition: transform 4s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 4s ease;
    }
    .breathing-circle.inhala {
        transform: scale(1.35);
        box-shadow: 0 0 55px rgba(124, 58, 237, 0.7);
    }
    .breathing-circle.sosten {
        transform: scale(1.35);
        box-shadow: 0 0 45px rgba(56, 189, 248, 0.6);
        background: radial-gradient(circle, #38bdf8 0%, #0284c7 100%);
    }
    .breathing-circle.exhala {
        transform: scale(0.9);
        box-shadow: 0 0 20px rgba(124, 58, 237, 0.25);
    }
    .breathing-circle.pausa {
        transform: scale(0.9);
        box-shadow: 0 0 15px rgba(100, 116, 139, 0.2);
        background: radial-gradient(circle, #94a3b8 0%, #64748b 100%);
    }

    @keyframes spinSlow {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .step-pill-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #e2e8f0;
        transition: all 0.3s ease;
    }
    .step-pill-indicator.active {
        background-color: #7c3aed;
        width: 26px;
        border-radius: 9999px;
    }
    .step-pill-indicator.completed {
        background-color: #10b981;
    }

    @media (prefers-reduced-motion: reduce) {
        .breathing-ring-outer {
            animation: none !important;
        }
        .breathing-circle {
            transition: none !important;
            transform: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Barra Superior -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <a href="{{ route('alumno.meditaciones.show', $meditacion) }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3" id="btnSalir">
            <i class="bi bi-x-circle me-1"></i> Salir de la sesión
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="badge {{ $meditacion->categoria_badge }} rounded-pill px-3 py-2">
                {{ $meditacion->categoria_label }}
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2">
                <i class="bi bi-clock me-1"></i> {{ $meditacion->duracion_minutos }} min
            </span>
        </div>
    </div>

    <!-- Sala Principal de Relajación Guiada -->
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden session-container">
        <!-- Encabezado con degradado suave -->
        <div class="p-4 text-center border-bottom bg-light">
            <h3 class="fw-bold text-slate-800 mb-1" id="meditacionTitulo">{{ $meditacion->titulo }}</h3>
            <p class="text-muted small mb-0">Enfoca tu atención, regula tu respiración y sigue el ritmo visual.</p>
        </div>

        <div class="card-body p-4 p-md-5 text-center">
            @if($meditacion->tipo_contenido === 'VIDEO' && $meditacion->embed_video_url)
                <!-- Vista de Reproductor de Video Guiado (FN.6) -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1.5 fw-bold">
                        <i class="bi bi-gem me-1 text-warning"></i> +{{ $meditacion->puntos }} pts de bienestar al finalizar
                    </span>
                    <a href="{{ $meditacion->media_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1.5" title="Abrir video en YouTube">
                        <i class="bi bi-youtube"></i>
                        <span>Ver en YouTube</span>
                        <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                </div>

                <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-sm mb-4 bg-dark">
                    @if(preg_match('/\.(mp4|webm|ogg)$/i', $meditacion->embed_video_url))
                        <video controls class="w-100 h-100">
                            <source src="{{ $meditacion->embed_video_url }}">
                            Tu navegador no soporta el reproductor de video HTML5.
                        </video>
                    @elseif($meditacion->youtube_video_id)
                        <div id="ytplayer-sesion"></div>
                        <noscript>
                            <iframe src="{{ $meditacion->embed_video_url }}" title="{{ $meditacion->titulo }}" allowfullscreen></iframe>
                        </noscript>
                    @else
                        <iframe src="{{ $meditacion->embed_video_url }}" title="{{ $meditacion->titulo }}" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                    @endif
                </div>

                @if($meditacion->transcripcion)
                    <div class="accordion mb-4 text-start" id="accordionVideoTrans">
                        <div class="accordion-item border rounded-3 overflow-hidden">
                            <h2 class="accordion-header" id="headingVideoTrans">
                                <button class="accordion-button collapsed py-2 px-3 small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVideoTrans" aria-expanded="false" aria-controls="collapseVideoTrans">
                                    <i class="bi bi-file-text me-2"></i> Transcripción / Guía accesible del video
                                </button>
                            </h2>
                            <div id="collapseVideoTrans" class="accordion-collapse collapse" aria-labelledby="headingVideoTrans" data-bs-parent="#accordionVideoTrans">
                                <div class="accordion-body small text-muted" style="white-space: pre-line;">
                                    {{ $meditacion->transcripcion }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @elseif($meditacion->tipo_contenido === 'AUDIO' && $meditacion->media_url)
                <!-- Vista de Audio Guiado / Podcast (FN.6) -->
                <div class="card bg-light border-0 rounded-4 p-4 text-center mb-4 shadow-sm">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 76px; height: 76px;">
                        <i class="bi bi-headphones fs-2"></i>
                    </div>
                    <audio controls class="w-100 mb-3">
                        <source src="{{ $meditacion->media_url }}">
                        Tu navegador no soporta el reproductor de audio HTML5.
                    </audio>
                    <p class="small text-muted mb-0">Adopta una postura cómoda, ajusta el volumen y disfruta de tu práctica auditiva guiada.</p>
                </div>

                @if($meditacion->transcripcion)
                    <div class="accordion mb-4 text-start" id="accordionAudioTrans">
                        <div class="accordion-item border rounded-3 overflow-hidden">
                            <h2 class="accordion-header" id="headingAudioTrans">
                                <button class="accordion-button collapsed py-2 px-3 small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAudioTrans" aria-expanded="false" aria-controls="collapseAudioTrans">
                                    <i class="bi bi-file-text me-2"></i> Transcripción accesible de audio (WCAG AA)
                                </button>
                            </h2>
                            <div id="collapseAudioTrans" class="accordion-collapse collapse" aria-labelledby="headingAudioTrans" data-bs-parent="#accordionAudioTrans">
                                <div class="accordion-body small text-muted" style="white-space: pre-line;">
                                    {{ $meditacion->transcripcion }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @elseif($meditacion->tipo_contenido === 'GUIA_TEXTUAL')
                <!-- Vista de Guía Textual Estructurada (FN.6) -->
                <div class="card border rounded-4 p-4 text-start mb-4 bg-light shadow-sm">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-book-half text-primary me-2"></i> Guía estructurada de relajación</h5>
                    <div class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.7; white-space: pre-line;">
                        {{ $meditacion->transcripcion ?: $meditacion->descripcion }}
                    </div>
                </div>
            @endif

            @if($meditacion->tipo_contenido === 'INTERACTIVO' || count($meditacion->pasos) > 0)
                <!-- Indicador de Pasos -->
                <div class="d-flex justify-content-center align-items-center gap-2 mb-4" id="pasosIndicator">
                    @foreach($meditacion->pasos as $idx => $p)
                        <div class="step-pill-indicator @if($idx === 0) active @endif" data-step="{{ $idx }}" title="Paso {{ $idx + 1 }}: {{ $p['titulo'] }}"></div>
                    @endforeach
                </div>

                <!-- Título del Paso Actual -->
                <div class="mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold" id="pasoEtiqueta">
                        Paso 1 de {{ count($meditacion->pasos) }}
                    </span>
                </div>
                <h4 class="fw-bold text-slate-800 mb-3" id="pasoTitulo">
                    {{ $meditacion->pasos[0]['titulo'] ?? 'Iniciando...' }}
                </h4>

                @if($meditacion->tipo_contenido === 'INTERACTIVO')
                    <!-- Escenario Visual de Respiración / Enfoque -->
                    <div class="my-4 my-md-5">
                        <div class="breathing-stage">
                            <div class="breathing-ring-outer"></div>
                            <div class="breathing-circle inhala" id="visualCircle">
                                <span class="fs-4 fw-bold" id="faseTexto">Inhala</span>
                                <span class="fs-6 opacity-75 fw-normal" id="pasoCountdown">4s</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Indicación textual guía -->
                <div class="bg-light p-3 p-md-4 rounded-4 mx-auto mb-4" style="max-width: 600px;">
                    <p class="text-slate-700 lead mb-0" id="pasoIndicacion" style="font-size: 1.1rem; line-height: 1.6;">
                        {{ $meditacion->pasos[0]['indicacion'] ?? 'Prepárate para comenzar...' }}
                    </p>
                </div>
            @endif

            <!-- Tiempo Total Transcurrido -->
            <div class="text-muted small mb-4">
                <i class="bi bi-stopwatch me-1 text-primary"></i> Tiempo practicado: <span class="fw-bold text-dark" id="tiempoTranscurridoTexto">00:00</span>
            </div>

            <!-- Controles de la Sesión -->
            <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
                @if($meditacion->tipo_contenido === 'INTERACTIVO' || count($meditacion->pasos) > 0)
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2" id="btnAnterior" disabled>
                        <i class="bi bi-chevron-left me-1"></i> Anterior
                    </button>

                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" id="btnPlayPause">
                        <i class="bi bi-pause-fill me-1" id="iconPlayPause"></i> <span id="labelPlayPause">Pausar</span>
                    </button>

                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2" id="btnSiguiente">
                        Siguiente <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                @endif

                <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-semibold ms-md-2" id="btnFinalizar">
                    <i class="bi bi-check-lg me-1"></i> Finalizar y Guardar (+2 pts)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Felicitación / Completado -->
<div class="modal fade" id="completadoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
            <div class="modal-body">
                <div class="rounded-circle bg-success-subtle text-success p-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                    <i class="bi bi-flower1" style="font-size: 2.5rem;"></i>
                </div>
                <h4 class="fw-bold text-slate-800 mb-2">¡Sesión de Calma Completada!</h4>
                <p class="text-muted mb-4">
                    Has dedicado tiempo valioso a cuidar tu salud mental y equilibrio interior. Tu mente y cuerpo te lo agradecen.
                </p>
                <div class="bg-light p-3 rounded-3 mb-4 d-flex justify-content-around">
                    <div>
                        <div class="text-muted small">Duración</div>
                        <div class="fw-bold text-slate-800 fs-5" id="modalDuracion">00:00</div>
                    </div>
                    <div class="border-start"></div>
                    <div>
                        <div class="text-muted small">Estado</div>
                        <div class="fw-bold text-success fs-5"><i class="bi bi-check-circle-fill"></i> Guardada</div>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-primary rounded-pill py-2 fw-semibold">
                        Volver al Catálogo de Meditaciones
                    </a>
                    <a href="{{ route('alumno.meditaciones.historial') }}" class="btn btn-outline-secondary rounded-pill py-2">
                        Ver Mi Historial Privado
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="guardarSesionForm" action="{{ route('alumno.meditaciones.guardar-sesion', $meditacion) }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="duracion_segundos" id="inputDuracionSegundos" value="0">
    <input type="hidden" name="completada" id="inputCompletada" value="1">
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pasos = @json($meditacion->pasos ?? []);
    const hasPasos = Array.isArray(pasos) && pasos.length > 0;

    let currentStepIndex = 0;
    let stepRemainingSeconds = hasPasos ? (parseInt(pasos[0].duracion_segundos) || 4) : 0;
    let totalElapsedSeconds = 0;
    let isPaused = false;
    let timerInterval = null;

    const visualCircle = document.getElementById('visualCircle');
    const faseTexto = document.getElementById('faseTexto');
    const pasoCountdown = document.getElementById('pasoCountdown');
    const pasoTitulo = document.getElementById('pasoTitulo');
    const pasoIndicacion = document.getElementById('pasoIndicacion');
    const pasoEtiqueta = document.getElementById('pasoEtiqueta');
    const tiempoTranscurridoTexto = document.getElementById('tiempoTranscurridoTexto');
    const btnPlayPause = document.getElementById('btnPlayPause');
    const iconPlayPause = document.getElementById('iconPlayPause');
    const labelPlayPause = document.getElementById('labelPlayPause');
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnFinalizar = document.getElementById('btnFinalizar');
    const indicators = document.querySelectorAll('.step-pill-indicator');

    function formatTime(secs) {
        const m = Math.floor(secs / 60).toString().padStart(2, '0');
        const s = (secs % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    }

    function updateStepUI() {
        if (!hasPasos) return;
        const paso = pasos[currentStepIndex];
        if (!paso) return;

        if (pasoTitulo) pasoTitulo.textContent = paso.titulo;
        if (pasoIndicacion) pasoIndicacion.textContent = paso.indicacion;
        if (pasoEtiqueta) pasoEtiqueta.textContent = `Paso ${currentStepIndex + 1} de ${pasos.length}`;
        if (pasoCountdown) pasoCountdown.textContent = `${stepRemainingSeconds}s`;

        if (visualCircle) {
            const fase = (paso.fase || '').toLowerCase();
            visualCircle.className = 'breathing-circle';

            if (fase.includes('inhala')) {
                visualCircle.classList.add('inhala');
                if (faseTexto) faseTexto.textContent = 'Inhala';
            } else if (fase.includes('sosten')) {
                visualCircle.classList.add('sosten');
                if (faseTexto) faseTexto.textContent = 'Sostén';
            } else if (fase.includes('exhala')) {
                visualCircle.classList.add('exhala');
                if (faseTexto) faseTexto.textContent = 'Exhala';
            } else if (fase.includes('pausa')) {
                visualCircle.classList.add('pausa');
                if (faseTexto) faseTexto.textContent = 'Pausa';
            } else {
                visualCircle.classList.add('inhala');
                if (faseTexto) faseTexto.textContent = 'Atención';
            }
        }

        indicators.forEach((ind, idx) => {
            ind.classList.remove('active', 'completed');
            if (idx === currentStepIndex) {
                ind.classList.add('active');
            } else if (idx < currentStepIndex) {
                ind.classList.add('completed');
            }
        });

        if (btnAnterior) btnAnterior.disabled = (currentStepIndex === 0);
        if (btnSiguiente) btnSiguiente.disabled = (currentStepIndex === pasos.length - 1);
    }

    function nextStep() {
        if (currentStepIndex < pasos.length - 1) {
            currentStepIndex++;
            stepRemainingSeconds = parseInt(pasos[currentStepIndex].duracion_segundos) || 4;
            updateStepUI();
        } else {
            // Completó todos los pasos
            finalizarSesion(true);
        }
    }

    function prevStep() {
        if (currentStepIndex > 0) {
            currentStepIndex--;
            stepRemainingSeconds = parseInt(pasos[currentStepIndex].duracion_segundos) || 4;
            updateStepUI();
        }
    }

    function tick() {
        if (isPaused) return;

        totalElapsedSeconds++;
        if (tiempoTranscurridoTexto) tiempoTranscurridoTexto.textContent = formatTime(totalElapsedSeconds);

        if (hasPasos) {
            if (stepRemainingSeconds > 1) {
                stepRemainingSeconds--;
                if (pasoCountdown) pasoCountdown.textContent = `${stepRemainingSeconds}s`;
            } else {
                nextStep();
            }
        }
    }

    function startTimer() {
        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(tick, 1000);
        if (hasPasos) updateStepUI();
    }

    if (btnPlayPause) {
        btnPlayPause.addEventListener('click', function () {
            isPaused = !isPaused;
            if (isPaused) {
                if (iconPlayPause) iconPlayPause.className = 'bi bi-play-fill me-1';
                if (labelPlayPause) labelPlayPause.textContent = 'Reanudar';
            } else {
                if (iconPlayPause) iconPlayPause.className = 'bi bi-pause-fill me-1';
                if (labelPlayPause) labelPlayPause.textContent = 'Pausar';
            }
        });
    }

    if (btnAnterior) btnAnterior.addEventListener('click', prevStep);
    if (btnSiguiente) btnSiguiente.addEventListener('click', nextStep);

    function finalizarSesion(autoCompleted = false) {
        if (timerInterval) clearInterval(timerInterval);

        const duracion = Math.max(totalElapsedSeconds, 1);
        const form = document.getElementById('guardarSesionForm');
        const inputDuracion = document.getElementById('inputDuracionSegundos');
        const inputComp = document.getElementById('inputCompletada');

        inputDuracion.value = duracion;
        inputComp.value = autoCompleted ? '1' : '0';

        // Intentar guardar vía AJAX para feedback fluido
        const url = form.action;
        const token = form.querySelector('input[name="_token"]').value;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                duracion_segundos: duracion,
                completada: autoCompleted ? 1 : 0
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('modalDuracion').textContent = formatTime(duracion);
            const modalEl = document.getElementById('completadoModal');
            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        })
        .catch(err => {
            // Fallback estándar por formulario en caso de desconexión
            form.submit();
        });
    }

    btnFinalizar.addEventListener('click', function () {
        finalizarSesion(true);
    });

    startTimer();
});
</script>
@if($meditacion->tipo_contenido === 'VIDEO' && $meditacion->youtube_video_id)
<script>
    var ytTag = document.createElement('script');
    ytTag.src = "https://www.youtube.com/iframe_api";
    var firstScript = document.getElementsByTagName('script')[0];
    firstScript.parentNode.insertBefore(ytTag, firstScript);

    var ytSesionPlayer;
    function onYouTubeIframeAPIReady() {
        ytSesionPlayer = new YT.Player('ytplayer-sesion', {
            videoId: "{{ $meditacion->youtube_video_id }}",
            playerVars: {
                'enablejsapi': 1,
                'origin': window.location.origin,
                'rel': 0,
                'modestbranding': 1,
                'playsinline': 1
            },
            events: {
                'onStateChange': function(event) {
                    if (event.data === YT.PlayerState.ENDED) {
                        var btn = document.getElementById('btnFinalizar');
                        if (btn) btn.click();
                    }
                }
            }
        });
    }
</script>
@endif
@endpush

