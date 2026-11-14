@extends('layouts.dashboard')

@section('title', 'Detalle de Intento #' . $numeroIntentoAlumno . ' — ' . $test->codigo)

@section('content')
@php
    $est = $intento->usuario?->estudiante;
    $colorBaremo = $baremoAplicado?->color_hex ?: '#6366f1';
@endphp
<div class="container-fluid py-4">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.tests.index') }}" class="text-decoration-none">Tests Psicométricos</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.tests.resultados', ['test' => $test->instrumento_id, 'version_id' => $intento->version_id]) }}" class="text-decoration-none">Resultados {{ $test->codigo }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Intento #{{ $numeroIntentoAlumno }} (Folio {{ $intento->intento_id }})</li>
        </ol>
    </nav>

    {{-- Cabecera --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge bg-dark font-monospace">Intento #{{ $numeroIntentoAlumno }} de {{ $totalIntentosAlumno }}</span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">{{ $test->codigo }} — v{{ $intento->version?->version }}</span>
                @if($intento->estado === 'FINALIZADO')
                    <span class="badge bg-success">FINALIZADO</span>
                @else
                    <span class="badge bg-warning text-dark">{{ $intento->estado }}</span>
                @endif
                @if($intento->requiere_atencion)
                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sugiere Canalización</span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Consulta Individual de Respuestas</h1>
            <p class="text-muted mb-0 small">
                Instrumento: <strong>{{ $test->nombre }}</strong> ({{ $intento->version?->nombre_version ?? 'Versión ' . $intento->version?->version }})
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tests.resultados', ['test' => $test->instrumento_id, 'version_id' => $intento->version_id]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver a Resultados
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimir Detalle
            </button>
        </div>
    </div>

    {{-- Tarjetas de Identificación del Estudiante y Resultado Global (A7) --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-person-badge text-primary me-2"></i>Datos del Estudiante y Aplicación
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-sm-6">
                            <div class="text-muted">Estudiante</div>
                            <div class="fw-bold text-dark fs-6">
                                {{ $intento->usuario?->nombre }} {{ $intento->usuario?->apellido_paterno }} {{ $intento->usuario?->apellido_materno }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Matrícula Institucional</div>
                            <div class="fw-bold text-dark font-monospace fs-6">{{ $est?->matricula ?? 'S/M' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Programa Educativo</div>
                            <div class="fw-semibold text-dark">{{ $est?->programa?->nombre ?? 'No especificado' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Cuatrimestre y Grupo</div>
                            <div class="fw-semibold text-dark">
                                @if($est?->cuatrimestre || $est?->grupo)
                                    {{ $est?->cuatrimestre }}° Cuatrimestre — Grupo {{ $est?->grupo }}
                                @else
                                    No especificado
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Fecha y Hora de Inicio</div>
                            <div class="fw-semibold text-dark">
                                {{ $intento->fecha_inicio ? \Carbon\Carbon::parse($intento->fecha_inicio)->format('d/m/Y H:i:s') : ($intento->created_at?->format('d/m/Y H:i:s') ?? 'N/D') }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Fecha y Hora de Finalización</div>
                            <div class="fw-semibold text-dark">
                                {{ $intento->fecha_finalizacion ? \Carbon\Carbon::parse($intento->fecha_finalizacion)->format('d/m/Y H:i:s') : 'En progreso' }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Número de Intento</div>
                            <div class="fw-semibold text-dark">Intento #{{ $numeroIntentoAlumno }} de {{ $totalIntentosAlumno }} registrado(s)</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Versión Aplicada</div>
                            <div class="fw-semibold text-dark">v{{ $intento->version?->version }} ({{ $intento->version?->estado }})</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-top: 4px solid {{ $colorBaremo }} !important;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-clipboard2-pulse text-primary me-2"></i>Resultado e Interpretación del Baremo
                    </h6>
                    <span class="badge rounded-pill px-3 py-2" style="background-color: {{ $colorBaremo }}; color: #fff;">
                        {{ $intento->nivel_resultado ?? 'SIN CLASIFICAR' }}
                    </span>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center gap-4 mb-3">
                        <div class="p-3 rounded-3 bg-light border text-center" style="min-width: 130px;">
                            <div class="display-6 fw-bold text-dark mb-0">{{ (int) $intento->puntuacion_total }}</div>
                            <div class="text-muted small">Puntos Totales</div>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">
                                Nivel Interpretativo: {{ $baremoAplicado?->nivel ?? $intento->nivel_resultado ?? 'Completado' }}
                                @if($baremoAplicado)
                                    <span class="text-muted fw-normal small">({{ $baremoAplicado->puntuacion_min }} – {{ $baremoAplicado->puntuacion_max }} pts)</span>
                                @endif
                            </div>
                            <p class="text-muted small mb-2">
                                {{ $baremoAplicado?->interpretacion ?? $intento->interpretacion_resumen ?? 'Aplicación registrada en el historial del estudiante.' }}
                            </p>
                            @if($baremoAplicado?->recomendacion)
                                <div class="p-2 rounded bg-primary bg-opacity-10 text-dark small">
                                    <strong>Recomendación orientada:</strong> {{ $baremoAplicado->recomendacion }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="border-top pt-2 d-flex justify-content-between align-items-center small text-muted">
                        <span>Reactivos contestados: <strong>{{ $detalleReactivos->where('respondida', true)->count() }} / {{ $detalleReactivos->count() }}</strong></span>
                        <span>Puntos de bienestar otorgados: <strong>{{ $intento->puntos_otorgados ?? 0 }} pts</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Desglose Reactivo por Reactivo (A7) --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-list-ol text-primary me-2"></i>Respuestas Registradas por Reactivo
                </h6>
                <span class="small text-muted">Desglose íntegro de enunciados, opciones elegidas o texto libre y puntaje asignado</span>
            </div>
            <span class="badge bg-secondary">{{ $detalleReactivos->count() }} Reactivos</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-3" style="width: 7%;">#</th>
                        <th style="width: 45%;">Reactivo / Enunciado</th>
                        <th style="width: 14%;">Tipo</th>
                        <th style="width: 22%;">Respuesta del Estudiante</th>
                        <th class="text-end pe-3" style="width: 12%;">Puntaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalleReactivos as $reactivo)
                        <tr>
                            <td class="ps-3">
                                <span class="badge bg-dark font-monospace">#{{ $reactivo['numero_reactivo'] }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $reactivo['enunciado'] }}</div>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @if(!$reactivo['es_obligatoria'])
                                        <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">Opcional</span>
                                    @endif
                                    @if($reactivo['es_inverso'])
                                        <span class="badge bg-info-subtle text-info-emphasis border" style="font-size: 0.7rem;">Inverso</span>
                                    @endif
                                    @if($reactivo['es_critico'])
                                        <span class="badge bg-danger-subtle text-danger border" style="font-size: 0.7rem;">Reactivo Crítico</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $reactivo['tipo_pregunta'] }}</span>
                            </td>
                            <td>
                                @if(!$reactivo['respondida'])
                                    <span class="text-muted fst-italic small">Sin respuesta (Omitida)</span>
                                @elseif($reactivo['tipo_pregunta'] === 'TEXTO_LIBRE')
                                    <div class="p-2 rounded bg-light border-start border-3 border-primary small text-dark">
                                        {{ $reactivo['texto_respuesta'] }}
                                    </div>
                                @else
                                    <span class="fw-semibold text-dark">{{ $reactivo['texto_respuesta'] }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @if($reactivo['sin_puntaje_clinico'])
                                    <span class="badge bg-light text-muted border" title="Las preguntas abiertas no suman puntaje clínico automático">0 pts (Abierta)</span>
                                @elseif($reactivo['respondida'])
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                                        {{ $reactivo['puntos_obtenidos'] }} pts
                                    </span>
                                @else
                                    <span class="text-muted small">0 pts</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="4" class="text-end fw-bold text-dark py-3">Puntuación Total Calculada:</td>
                        <td class="text-end pe-3 fw-bold text-primary fs-5 py-3">{{ (int) $intento->puntuacion_total }} pts</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

