@extends('layouts.dashboard')

@section('title', 'Resultados de Evaluación - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="dashboard-section-card p-4 p-md-5 shadow-sm">
                <!-- Encabezado de Éxito -->
                <div class="text-center mb-4">
                    <div class="mx-auto mb-3 d-inline-flex p-3 rounded-circle" style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.15) 0%, rgba(99, 102, 241, 0.2) 100%);">
                        <div class="p-3 rounded-circle bg-white shadow-sm text-primary" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill small fw-semibold">
                            Evaluación Finalizada
                        </span>
                    </div>

                    <h1 class="h3 fw-bold text-dark mb-1">¡Evaluación completada con éxito!</h1>
                    <p class="text-muted mb-0">
                        Resultados de evaluación &bull; {{ $instrumento->nombre }} &bull; <span class="fw-semibold">v{{ $version->version }}</span>
                    </p>
                </div>

                <!-- Tarjeta Principal de Puntuación y Escala Visual -->
                <div class="border rounded-4 p-4 p-md-5 mb-4 text-center" style="background: linear-gradient(180deg, rgba(245, 243, 255, 0.6) 0%, rgba(255, 255, 255, 0.9) 100%); border-color: rgba(124, 58, 237, 0.2) !important;">
                    <span class="text-muted text-uppercase tracking-wider small fw-semibold d-block mb-2">
                        Puntuación Obtenida
                    </span>

                    <div class="d-flex align-items-baseline justify-content-center gap-2 mb-2">
                        <span class="display-4 fw-bold text-dark">{{ $intento->puntuacion_total ?? $scoreData['puntuacion_total'] ?? 0 }}</span>
                        <span class="text-muted fs-5">/ {{ $scoreData['puntuacion_maxima'] ?? 15 }} pts</span>
                    </div>

                    @php
                        $colorHex = $scoreData['color_hex'] ?? '#7c3aed';
                        $porcentaje = $scoreData['porcentaje'] ?? 0;
                        $nivel = $intento->nivel_resultado ?? $scoreData['nivel_resultado'] ?? 'COMPLETADO';
                    @endphp

                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4 border" style="background-color: {{ $colorHex }}15; border-color: {{ $colorHex }}40 !important; color: {{ $colorHex }};">
                        <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: {{ $colorHex }};"></span>
                        <strong class="small fw-semibold">{{ $nivel }}</strong>
                    </div>

                    <!-- Escala Visual de Rendimiento -->
                    <div class="mx-auto" style="max-width: 480px;">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Progreso relativo</span>
                            <span class="fw-semibold text-dark">{{ $porcentaje }}%</span>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 999px; background: rgba(0, 0, 0, 0.06);" role="progressbar" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $porcentaje }}%; background-color: {{ $colorHex }}; border-radius: 999px;"></div>
                        </div>
                    </div>

                    <!-- Interpretación comprensible del resultado -->
                    @if (!empty($scoreData['mensaje_retroalimentacion']))
                        <div class="bg-white border rounded-3 p-3 p-md-4 mt-4 mx-auto text-start shadow-xs" style="max-width: 620px;">
                            <div class="d-flex gap-2 align-items-start">
                                <span class="fs-5" aria-hidden="true" style="color: {{ $colorHex }};">✦</span>
                                <div>
                                    <strong class="d-block text-dark small mb-1">Significado de tu resultado:</strong>
                                    <p class="text-muted small mb-0 lh-base">
                                        {{ $scoreData['mensaje_retroalimentacion'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Detalle de Registro y Metadatos -->
                <div class="bg-light border rounded-4 p-4 mb-4">
                    <h2 class="h6 fw-bold text-dark mb-3">Detalle del registro</h2>
                    <div class="row g-3 small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Instrumento:</span>
                            <span class="fw-semibold text-dark">{{ $instrumento->nombre }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Código institucional:</span>
                            <span class="fw-semibold text-dark">{{ $instrumento->codigo }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Fecha y hora de finalización:</span>
                            <span class="fw-semibold text-dark">
                                {{ $intento->fecha_finalizacion ? $intento->fecha_finalizacion->translatedFormat('d \d\e F \d\e Y, H:i') . ' hrs' : 'Reciente' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Estado de integridad:</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                Completado ({{ $scoreData['total_preguntas'] ?? 5 }} reactivos verificados)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Aviso Ético y Confidencialidad Institucional -->
                <div class="alert alert-light border border-primary-subtle rounded-4 p-4 mb-4">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="portal-guidance-icon mt-1 text-primary" aria-hidden="true">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <div>
                            <h3 class="h6 fw-bold mb-1 text-dark">Confidencialidad y Orientación Preventiva</h3>
                            <p class="text-muted small mb-0">
                                Aviso Institucional: Los instrumentos disponibles son herramientas psicométricas estandarizadas de tamizaje preliminar. No constituyen un diagnóstico definitivo ni reemplazan la valoración personalizada de un especialista en salud mental.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botones de Navegación -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2">
                    <a href="{{ route('alumno.evaluaciones.historial') }}" class="btn btn-outline-secondary px-4">
                        <span>Ver mi historial de evaluaciones</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('alumno.evaluaciones') }}" class="btn btn-portal-primary px-4">
                            Volver al catálogo de evaluaciones
                        </a>
                        <a href="{{ route('alumno.dashboard') }}" class="btn btn-outline-secondary px-3">
                            Inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
