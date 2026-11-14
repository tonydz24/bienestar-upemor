@extends('layouts.dashboard')

@section('title', $meditacion->titulo . ' - Bienestar UPEMOR')

@push('styles')
<style>
    .step-timeline-item {
        position: relative;
        padding-left: 2.25rem;
        padding-bottom: 1.5rem;
    }
    .step-timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 0.85rem;
        top: 2rem;
        bottom: 0;
        width: 2px;
        background-color: #e2e8f0;
    }
    .step-timeline-badge {
        position: absolute;
        left: 0;
        top: 0.15rem;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        background-color: #7c3aed;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
    }
    .video-reward-toast {
        animation: slideDownIn 0.4s ease-out;
    }
    @keyframes slideDownIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="mb-3">
        <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted p-0">
            <i class="bi bi-arrow-left me-1"></i> Volver al catálogo de meditaciones
        </a>
    </div>

    <!-- Alerta dinámica de finalización y puntos -->
    <div id="alertPuntosWrapper" class="d-none"></div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-trophy-fill fs-5 text-success"></i>
                <div class="fw-semibold">{{ session('status') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Columna Principal -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <!-- Badges superiores -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge {{ $meditacion->categoria_badge }} rounded-pill px-3 py-2 fw-semibold">
                                <i class="bi {{ $meditacion->categoria_icon }} me-1"></i> {{ $meditacion->categoria_label }}
                            </span>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2 fw-bold" title="Puntos de bienestar otorgados al completar">
                                <i class="bi bi-gem text-warning me-1"></i> +{{ $meditacion->puntos }} pts
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2">
                                Nivel: {{ $meditacion->nivel_label }}
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">
                                <i class="bi bi-clock me-1"></i> {{ $meditacion->duracion_minutos }} minutos
                            </span>
                        </div>
                    </div>

                    <h1 class="h2 fw-bold text-slate-800 mb-2">{{ $meditacion->titulo }}</h1>

                    @if($meditacion->autor)
                        <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                <i class="bi bi-person-circle me-1 text-primary"></i> Autor / Canal: <strong>{{ $meditacion->autor }}</strong>
                            </span>
                        </div>
                    @endif

                    <p class="lead text-muted mb-4" style="line-height: 1.6;">
                        {{ $meditacion->descripcion }}
                    </p>

                    @if($meditacion->tipo_contenido === 'VIDEO' && $meditacion->media_url)
                        <!-- ========================================== -->
                        <!-- REPRODUCTOR DE VIDEO GUIADO CON YOUTUBE API -->
                        <!-- ========================================== -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <span class="small text-muted fw-semibold">
                                    <i class="bi bi-play-circle-fill text-danger me-1"></i> Video Guiado Institucional
                                </span>
                                <a href="{{ $meditacion->media_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1.5" title="Abrir video en nueva pestaña de YouTube">
                                    <i class="bi bi-youtube"></i>
                                    <span>Ver en YouTube</span>
                                    <i class="bi bi-box-arrow-up-right small"></i>
                                </a>
                            </div>

                            <!-- Contenedor Responsivo 16:9 con Sombra y Bordes Suaves -->
                            <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-sm border border-light bg-dark mb-3">
                                <div id="ytplayer-container" data-video-id="{{ $meditacion->youtube_video_id }}"></div>
                                <noscript>
                                    <iframe src="{{ $meditacion->embed_video_url }}" title="{{ $meditacion->titulo }}" allowfullscreen></iframe>
                                </noscript>
                            </div>

                            <!-- Barra Informativa de Recompensa Automática -->
                            <div class="alert alert-light border border-primary-subtle rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2 small">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                                    <span>Al concluir la reproducción completa del video se acreditarán <strong>2 puntos de bienestar</strong> en tu perfil.</span>
                                </div>
                                <span id="videoStatusBadge" class="badge bg-secondary-subtle text-secondary-emphasis border rounded-pill px-2.5 py-1">
                                    Listo para reproducir
                                </span>
                            </div>
                        </div>

                        @if($meditacion->transcripcion)
                            <div class="accordion mb-4" id="accordionVideoTrans">
                                <div class="accordion-item border rounded-3 overflow-hidden">
                                    <h2 class="accordion-header" id="headingVideoTrans">
                                        <button class="accordion-button collapsed py-2.5 px-3 small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVideoTrans" aria-expanded="false" aria-controls="collapseVideoTrans">
                                            <i class="bi bi-file-text me-2 text-primary"></i> Transcripción / Guía accesible del video (WCAG AA)
                                        </button>
                                    </h2>
                                    <div id="collapseVideoTrans" class="accordion-collapse collapse" aria-labelledby="headingVideoTrans" data-bs-parent="#accordionVideoTrans">
                                        <div class="accordion-body small text-muted lh-base" style="white-space: pre-line;">
                                            {{ $meditacion->transcripcion }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @else
                        <!-- ========================================== -->
                        <!-- SESIÓN INTERACTIVA / EJERCICIO CON GUÍA    -->
                        <!-- ========================================== -->
                        <div class="p-4 rounded-4 mb-4" style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);">
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                                <div>
                                    <h5 class="fw-bold text-slate-800 mb-1">¿Listo para comenzar?</h5>
                                    <p class="text-muted small mb-0">Encuentra un lugar cómodo, ajusta tu postura y déjate guiar por la visualización.</p>
                                </div>
                                <a href="{{ route('alumno.meditaciones.sesion', $meditacion) }}" class="btn btn-primary btn-lg rounded-pill px-4 py-2 shadow-sm fw-bold">
                                    <i class="bi bi-play-circle-fill me-2"></i> Iniciar Práctica
                                </a>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h4 class="fw-bold text-slate-800 mb-3">Fases de este ejercicio guiado</h4>
                        <p class="text-muted small mb-4">
                            Este ejercicio cuenta con <strong>{{ count($meditacion->pasos) }}</strong> pasos estructurados diseñados para realizarse secuencialmente con temporizador visual interactivo.
                        </p>

                        <div class="step-timeline">
                            @foreach($meditacion->pasos as $paso)
                                <div class="step-timeline-item">
                                    <div class="step-timeline-badge">{{ $paso['paso'] ?? $loop->iteration }}</div>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                                        <h6 class="fw-bold text-slate-800 mb-0">{{ $paso['titulo'] }}</h6>
                                        <span class="badge bg-light text-muted border rounded-pill small">
                                            <i class="bi bi-stopwatch me-1"></i> {{ $paso['duracion_segundos'] }}s
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-0">{{ $paso['indicacion'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Columna Lateral -->
        <div class="col-lg-4">
            <!-- Tarjeta de Autocuidado y Privacidad -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-slate-800 mb-3">
                        <i class="bi bi-shield-check text-success me-2"></i> Privacidad de Bienestar
                    </h5>
                    <p class="text-muted small mb-3">
                        Tus prácticas de relajación y meditación son estrictamente personales. Ningún docente ni usuario externo tiene acceso a tus minutos o registros individuales de calma.
                    </p>
                    <div class="bg-light p-3 rounded-3 text-center">
                        <div class="text-muted small">Has completado este ejercicio</div>
                        <div class="text-primary fs-3 fw-bold" id="vecesCompletadaCounter">{{ $vecesCompletada }}</div>
                        <div class="text-muted small">{{ $vecesCompletada === 1 ? 'vez' : 'veces' }} en total</div>
                    </div>
                </div>
            </div>

            <!-- Recomendaciones -->
            @if($relacionadas->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-slate-800 mb-3">Otras sugerencias de la categoría</h6>
                        <div class="d-flex flex-column gap-3">
                            @foreach($relacionadas as $rel)
                                <div class="p-3 rounded-3 border border-light bg-light d-flex align-items-center justify-content-between">
                                    <div class="overflow-hidden me-2">
                                        <div class="fw-semibold text-slate-800 small text-truncate">{{ $rel->titulo }}</div>
                                        <div class="text-muted small"><i class="bi bi-clock me-1"></i> {{ $rel->duracion_minutos }} min</div>
                                    </div>
                                    <a href="{{ route('alumno.meditaciones.show', $rel) }}" class="btn btn-outline-primary btn-sm rounded-pill">
                                        Ver
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($meditacion->tipo_contenido === 'VIDEO' && $meditacion->media_url)
<script>
    // Carga asíncrona de la YouTube IFrame Player API
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    var ytPlayer;
    var yaRegistrado = false;
    var videoId = "{{ $meditacion->youtube_video_id }}";

    function onYouTubeIframeAPIReady() {
        if (!videoId) return;

        ytPlayer = new YT.Player('ytplayer-container', {
            videoId: videoId,
            playerVars: {
                'enablejsapi': 1,
                'origin': window.location.origin,
                'rel': 0,
                'modestbranding': 1,
                'playsinline': 1
            },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
    }

    function onPlayerReady(event) {
        var statusBadge = document.getElementById('videoStatusBadge');
        if (statusBadge) {
            statusBadge.className = 'badge bg-info-subtle text-info-emphasis border rounded-pill px-2.5 py-1';
            statusBadge.textContent = 'Listo para reproducir';
        }
    }

    function onPlayerStateChange(event) {
        var statusBadge = document.getElementById('videoStatusBadge');

        if (event.data === YT.PlayerState.PLAYING) {
            if (statusBadge) {
                statusBadge.className = 'badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1';
                statusBadge.textContent = 'Reproduciendo video guiado...';
            }
        } else if (event.data === YT.PlayerState.PAUSED) {
            if (statusBadge && !yaRegistrado) {
                statusBadge.className = 'badge bg-warning-subtle text-warning-emphasis border rounded-pill px-2.5 py-1';
                statusBadge.textContent = 'En pausa';
            }
        } else if (event.data === YT.PlayerState.ENDED) {
            // Video finalizado completamente (YT.PlayerState.ENDED === 0)
            if (!yaRegistrado) {
                yaRegistrado = true;
                if (statusBadge) {
                    statusBadge.className = 'badge bg-success-subtle text-success border rounded-pill px-2.5 py-1';
                    statusBadge.textContent = '¡Video finalizado con éxito!';
                }
                completarSesionVideo();
            }
        }
    }

    function completarSesionVideo() {
        var alertWrapper = document.getElementById('alertPuntosWrapper');
        if (alertWrapper) {
            alertWrapper.className = 'alert alert-info border-0 shadow-sm rounded-4 mb-4 video-reward-toast d-flex align-items-center gap-3';
            alertWrapper.innerHTML = `
                <div class="spinner-border spinner-border-sm text-primary flex-shrink-0" role="status">
                    <span class="visually-hidden">Registrando...</span>
                </div>
                <div>
                    <strong>¡Excelente trabajo!</strong> Has completado tu ejercicio guiado. Acreditando puntos de bienestar...
                </div>
            `;
        }

        var duracion = (ytPlayer && typeof ytPlayer.getDuration === 'function') 
            ? Math.round(ytPlayer.getDuration()) 
            : {{ $meditacion->duracion_minutos * 60 }};

        fetch("{{ route('alumno.meditaciones.completar', $meditacion) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                duracion_segundos: duracion
            })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.status === 'success') {
                if (alertWrapper) {
                    alertWrapper.className = 'alert alert-success border-0 shadow-sm rounded-4 mb-4 video-reward-toast d-flex align-items-center justify-content-between flex-wrap gap-2';
                    alertWrapper.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-trophy-fill fs-4 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-success">¡Práctica de Bienestar Completada!</h6>
                                <p class="small text-muted mb-0">${data.message} Nuevo saldo: <strong>${data.nuevo_saldo} pts</strong>.</p>
                            </div>
                        </div>
                        <span class="badge bg-success px-3 py-2 rounded-pill fw-bold">+${data.puntos_otorgados} Puntos</span>
                    `;
                }

                // Actualizar contador visual de veces completada
                var contador = document.getElementById('vecesCompletadaCounter');
                if (contador) {
                    var val = parseInt(contador.textContent) || 0;
                    contador.textContent = val + 1;
                }

                // Actualizar saldo de puntos en la cabecera/navbar
                var topbarPuntos = document.getElementById('topbarPuntosValor');
                if (topbarPuntos && data.nuevo_saldo !== undefined) {
                    topbarPuntos.textContent = data.nuevo_saldo;
                }
                var userBadges = document.querySelectorAll('.user-points-badge');
                userBadges.forEach(function(el) {
                    el.textContent = data.nuevo_saldo;
                });

            } else if (data.status === 'already_completed') {
                if (alertWrapper) {
                    alertWrapper.className = 'alert alert-info border-0 shadow-sm rounded-4 mb-4 video-reward-toast d-flex align-items-center gap-2';
                    alertWrapper.innerHTML = `
                        <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                        <div>
                            <strong>Actividad completada hoy:</strong> ${data.message}
                        </div>
                    `;
                }
            }
        })
        .catch(function(err) {
            console.error('Error al registrar sesión de video:', err);
        });
    }
</script>
@endif
@endpush
