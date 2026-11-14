@extends('layouts.dashboard')

@section('title', 'Resultados de Encuesta — ' . $encuesta->titulo)

@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb y Cabecera --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.encuestas.index') }}" class="text-decoration-none">Encuestas de Satisfacción</a></li>
            <li class="breadcrumb-item active" aria-current="page">Resultados</li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge {{ $encuesta->tipo === 'POST_CITA' ? 'bg-info text-dark' : 'bg-primary' }}">
                    {{ $encuesta->tipo === 'POST_CITA' ? 'Post-Cita de Orientación' : 'Institucional General' }}
                </span>
                <span class="badge {{ $encuesta->estado === 'PUBLICADA' ? 'bg-success' : ($encuesta->estado === 'CERRADA' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                    {{ $encuesta->estado }}
                </span>
                @if($encuesta->es_anonima)
                    <span class="badge bg-light text-muted border">
                        <i class="bi bi-shield-lock me-1"></i>Anónima
                    </span>
                @else
                    <span class="badge bg-primary-subtle text-primary border border-primary">
                        <i class="bi bi-person-check me-1"></i>Identificada
                    </span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">{{ $encuesta->titulo }}</h1>
            @if($encuesta->descripcion)
                <p class="text-muted mb-0 small">{{ $encuesta->descripcion }}</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.encuestas.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al listado
            </a>
            <a href="{{ route('admin.encuestas.edit', $encuesta) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Configuración
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    {{-- Alerta de Umbral de Privacidad para Muestras Pequeñas en Anónimas --}}
    @if($encuesta->es_anonima && $totalRespuestas > 0 && $totalRespuestas < 3)
        <div class="alert alert-warning d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 flex-shrink-0"></i>
            <div>
                <strong>Aviso de Confidencialidad y Muestra Reducida:</strong>
                Esta encuesta cuenta con menos de 3 respuestas registradas ({{ $totalRespuestas }}). Por principios éticos y protección de datos personales de los estudiantes, las interpretaciones estadísticas deben tomarse con reserva para preservar el anonimato de las respuestas.
            </div>
        </div>
    @endif

    {{-- Tarjetas KPI resumen --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Total de Respuestas</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalRespuestas) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-question-circle fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Preguntas Evaluadas</div>
                        <div class="fs-4 fw-bold text-dark">{{ $encuesta->preguntas->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-shield-check fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Tipo de Participación</div>
                        <div class="fs-5 fw-bold text-dark">{{ $encuesta->es_anonima ? 'Anónima' : 'Identificada' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-calendar-event fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Vigencia</div>
                        <div class="small fw-semibold text-dark">
                            @if($encuesta->disponible_desde || $encuesta->disponible_hasta)
                                {{ $encuesta->disponible_desde?->format('d/m/Y') ?? 'Inicio' }} — {{ $encuesta->disponible_hasta?->format('d/m/Y') ?? 'Indefinida' }}
                            @else
                                Permanente
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($totalRespuestas === 0)
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <div class="text-muted mb-3">
                    <i class="bi bi-clipboard-x display-4 text-secondary"></i>
                </div>
                <h4 class="fw-bold text-dark">Aún no hay respuestas registradas</h4>
                <p class="text-muted mb-4 max-w-500 mx-auto">
                    Esta encuesta se encuentra en estado <strong>{{ $encuesta->estado }}</strong>. En cuanto los estudiantes completen sus respuestas, los análisis de satisfacción aparecerán automáticamente aquí.
                </p>
                @if($encuesta->estado === 'BORRADOR')
                    <form action="{{ route('admin.encuestas.toggle-estado', $encuesta) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send-check me-1"></i> Publicar Encuesta Ahora
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @else
        {{-- Estructura de Pestañas: Si es identificada tiene 2 pestañas, si es anónima solo el resumen --}}
        @if(!$encuesta->es_anonima)
            <ul class="nav nav-tabs mb-4" id="resultadosTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="tab-resumen-btn" data-bs-toggle="tab" data-bs-target="#tab-resumen" type="button" role="tab" aria-controls="tab-resumen" aria-selected="true">
                        <i class="bi bi-bar-chart-line me-2"></i>Resumen General y Métricas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="tab-estudiantes-btn" data-bs-toggle="tab" data-bs-target="#tab-estudiantes" type="button" role="tab" aria-controls="tab-estudiantes" aria-selected="false">
                        <i class="bi bi-people me-2"></i>Respuestas por Estudiante
                        <span class="badge bg-primary ms-2">{{ $respuestasIdentificadas->count() }}</span>
                    </button>
                </li>
            </ul>
        @endif

        <div class="tab-content" id="resultadosTabContent">
            {{-- PESTAÑA 1: Resumen General y Análisis por Pregunta --}}
            <div class="tab-pane fade show active" id="tab-resumen" role="tabpanel" aria-labelledby="tab-resumen-btn">
                <div class="row g-4">
                    @foreach($analisisPreguntas as $idx => $item)
                        @php
                            $p = $item['pregunta'];
                        @endphp
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-secondary me-2">Pregunta #{{ $idx + 1 }}</span>
                                        <span class="badge bg-light text-dark border me-2">{{ $p->tipo_pregunta }}</span>
                                        <span class="fw-bold text-dark">{{ $p->texto_pregunta }}</span>
                                    </div>
                                    <div class="text-muted small">
                                        <strong>{{ $item['total_respuestas'] }}</strong> {{ Str::plural('respuesta', $item['total_respuestas']) }}
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    {{-- Caso LIKERT (1 a 5) --}}
                                    @if($p->tipo_pregunta === 'LIKERT')
                                        <div class="row align-items-center mb-4">
                                            <div class="col-md-3 text-center border-end py-2">
                                                <div class="display-5 fw-bold text-primary">{{ number_format($item['promedio'], 2) }}</div>
                                                <div class="text-muted small">Promedio en Escala 1 - 5</div>
                                                <div class="text-warning mt-1">
                                                    @for($s = 1; $s <= 5; $s++)
                                                        <i class="bi {{ $item['promedio'] >= $s ? 'bi-star-fill' : ($item['promedio'] >= $s - 0.5 ? 'bi-star-half' : 'bi-star') }}"></i>
                                                    @endfor
                                                </div>
                                            </div>
                                            <div class="col-md-9 ps-md-4">
                                                @foreach($item['distribucion'] as $dist)
                                                    <div class="mb-2">
                                                        <div class="d-flex justify-content-between small mb-1">
                                                            <span class="fw-medium text-dark">{{ $dist['etiqueta'] }}</span>
                                                            <span class="text-muted">{{ $dist['cantidad'] }} ({{ $dist['porcentaje'] }}%)</span>
                                                        </div>
                                                        <div class="progress" style="height: 12px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $dist['porcentaje'] }}%;" aria-valuenow="{{ $dist['porcentaje'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                    {{-- Caso VALORACIÓN NUMÉRICA (1 a 10) --}}
                                    @elseif($p->tipo_pregunta === 'VALORACION')
                                        <div class="d-flex align-items-center gap-4 py-2">
                                            <div class="text-center p-3 rounded bg-light border" style="min-width: 140px;">
                                                <div class="display-6 fw-bold text-primary">{{ number_format($item['promedio'], 1) }} / 10</div>
                                                <div class="text-muted small">Puntuación Media</div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $item['promedio'] * 10 }}%;" aria-valuenow="{{ $item['promedio'] * 10 }}" aria-valuemin="0" aria-valuemax="100">
                                                        {{ number_format($item['promedio'], 1) }} / 10
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between text-muted small mt-1">
                                                    <span>1 (Deficiente)</span>
                                                    <span>5 (Aceptable)</span>
                                                    <span>10 (Excelente)</span>
                                                </div>
                                            </div>
                                        </div>

                                    {{-- Caso OPCIÓN ÚNICA o MÚLTIPLE --}}
                                    @elseif(in_array($p->tipo_pregunta, ['OPCION_UNICA', 'OPCION_MULTIPLE']))
                                        @if($p->tipo_pregunta === 'OPCION_MULTIPLE')
                                            <div class="alert alert-light border small text-muted mb-3 py-2 px-3 rounded-3">
                                                <i class="bi bi-info-circle text-primary me-1"></i>
                                                <strong>Pregunta de opción múltiple:</strong> Los porcentajes se calculan sobre el total de participantes que respondieron ({{ $item['total_participantes'] ?? $item['total_respuestas'] }} participantes, {{ $item['total_selecciones'] ?? 0 }} selecciones totales). La suma de los porcentajes puede ser mayor al 100% debido a que cada participante pudo marcar varias alternativas.
                                            </div>
                                        @endif

                                        @if(empty($item['distribucion']))
                                            <p class="text-muted fst-italic mb-0">Sin respuestas para esta pregunta.</p>
                                        @else
                                            <div class="row">
                                                @foreach($item['distribucion'] as $dist)
                                                    <div class="col-md-6 mb-3">
                                                        <div class="d-flex justify-content-between small mb-1">
                                                            <span class="fw-medium text-dark">{{ $dist['etiqueta'] }}</span>
                                                            <span class="text-muted">{{ $dist['cantidad'] }} ({{ $dist['porcentaje'] }}%)</span>
                                                        </div>
                                                        <div class="progress" style="height: 10px;">
                                                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ min(100, $dist['porcentaje']) }}%;" aria-valuenow="{{ $dist['porcentaje'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                    {{-- Caso TEXTO LIBRE (Comentarios cualitativos) --}}
                                    @elseif($p->tipo_pregunta === 'TEXTO_LIBRE')
                                        @if(empty($item['textos']))
                                            <p class="text-muted fst-italic mb-0">No se han registrado comentarios escritos para esta pregunta.</p>
                                        @else
                                            <div class="row g-2" style="max-height: 320px; overflow-y: auto;">
                                                @foreach($item['textos'] as $comentario)
                                                    <div class="col-12">
                                                        <div class="p-3 bg-light rounded border-start border-3 border-primary small text-dark">
                                                            <i class="bi bi-chat-left-quote text-primary me-2"></i>
                                                            {{ $comentario }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- PESTAÑA 2: Respuestas por Estudiante (solo visible en encuestas identificadas) --}}
            @if(!$encuesta->es_anonima)
                <div class="tab-pane fade" id="tab-estudiantes" role="tabpanel" aria-labelledby="tab-estudiantes-btn">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0">Listado Nominal de Respuestas Registradas</h6>
                                <span class="small text-muted">Transparencia y trazabilidad del instrumento identificado</span>
                            </div>
                            <span class="badge bg-primary">{{ $respuestasIdentificadas->count() }} Estudiantes</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-3" style="width: 5%;">#</th>
                                        <th style="width: 30%;">Estudiante</th>
                                        <th style="width: 20%;">Matrícula / Carrera</th>
                                        <th style="width: 18%;">Fecha de Respuesta</th>
                                        <th style="width: 15%;">Cita Asociada</th>
                                        <th class="text-end pe-3" style="width: 12%;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($respuestasIdentificadas as $idx => $resp)
                                        @php
                                            $usr = $resp->usuario;
                                            $est = $usr?->estudiante;
                                            $prog = $est?->programaEducativo;
                                        @endphp
                                        <tr>
                                            <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <div class="fw-bold text-dark">
                                                    {{ $usr ? ($usr->nombre . ' ' . $usr->primer_apellido . ' ' . ($usr->segundo_apellido ?? '')) : 'Usuario no disponible' }}
                                                </div>
                                                <div class="small text-muted">{{ $usr?->email ?? 'N/D' }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $est?->matricula ?? 'Sin matrícula' }}</div>
                                                <div class="small text-muted">{{ $prog?->siglas ?? 'UPEMOR' }} — {{ Str::limit($prog?->nombre ?? '', 25) }}</div>
                                            </td>
                                            <td class="small">
                                                <span class="fw-semibold">{{ $resp->fecha_respuesta?->format('d/m/Y') ?? '-' }}</span>
                                                <span class="text-muted">({{ $resp->fecha_respuesta?->format('H:i') ?? '-' }} hrs)</span>
                                            </td>
                                            <td>
                                                @if($resp->cita)
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info">
                                                        Cita #{{ $resp->cita->cita_id }} ({{ $resp->cita->modalidad }})
                                                    </span>
                                                @else
                                                    <span class="text-muted small">N/A</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-3">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modal-resp-{{ $resp->respuesta_id }}">
                                                    <i class="bi bi-eye me-1"></i> Ver Respuestas
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No se han registrado respuestas de estudiantes aún.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Modales accesibles con el detalle de respuestas por estudiante --}}
                    @foreach($respuestasIdentificadas as $resp)
                        @php
                            $usr = $resp->usuario;
                            $detallesMap = $resp->detalles->keyBy('pregunta_id');
                        @endphp
                        <div class="modal fade" id="modal-resp-{{ $resp->respuesta_id }}" tabindex="-1" aria-labelledby="modalLabel-{{ $resp->respuesta_id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header bg-light border-bottom">
                                        <div>
                                            <h5 class="modal-title fw-bold text-dark" id="modalLabel-{{ $resp->respuesta_id }}">
                                                Respuestas de {{ $usr ? ($usr->nombre . ' ' . $usr->primer_apellido) : 'Estudiante' }}
                                            </h5>
                                            <span class="small text-muted">
                                                Registrada el {{ $resp->fecha_respuesta?->format('d/m/Y \a \l\a\s H:i \h\r\s') }} • Folio #{{ $resp->respuesta_id }}
                                            </span>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="d-flex flex-column gap-3">
                                            @foreach($encuesta->preguntas as $pIdx => $pregunta)
                                                @php
                                                    $det = $detallesMap->get($pregunta->pregunta_id);
                                                @endphp
                                                <div class="p-3 bg-light rounded border">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="fw-bold text-dark mb-0">
                                                            <span class="text-primary me-1">#{{ $pIdx + 1 }}</span>
                                                            {{ $pregunta->texto_pregunta }}
                                                        </h6>
                                                        <span class="badge bg-secondary-subtle text-secondary border small">{{ $pregunta->tipo_pregunta }}</span>
                                                    </div>

                                                    <div class="mt-2">
                                                        @if(!$det)
                                                            <span class="text-muted fst-italic small">Sin respuesta registrada (opcional)</span>
                                                        @elseif($pregunta->tipo_pregunta === 'LIKERT')
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="badge bg-primary px-3 py-2 fs-6">Escala: {{ $det->valor_numerico }} / 5</span>
                                                                @php
                                                                    $likertLabel = collect(\App\Models\EncuestaPregunta::opcionesLikertEstandar())->firstWhere('valor', (int)$det->valor_numerico)['etiqueta'] ?? '';
                                                                @endphp
                                                                <span class="fw-semibold text-dark">{{ $likertLabel }}</span>
                                                            </div>
                                                        @elseif($pregunta->tipo_pregunta === 'VALORACION')
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="badge bg-success px-3 py-2 fs-6">{{ $det->valor_numerico }} / 10</span>
                                                                <span class="text-muted small">Puntuación asignada</span>
                                                            </div>
                                                        @elseif($pregunta->tipo_pregunta === 'OPCION_UNICA')
                                                            <div class="fw-semibold text-primary">
                                                                <i class="bi bi-check-circle-fill me-1"></i>{{ $det->texto_respuesta }}
                                                            </div>
                                                        @elseif($pregunta->tipo_pregunta === 'OPCION_MULTIPLE')
                                                            @php
                                                                $opts = is_array($det->opciones_seleccionadas) ? $det->opciones_seleccionadas : json_decode($det->opciones_seleccionadas ?? '[]', true);
                                                            @endphp
                                                            <div class="d-flex flex-wrap gap-1">
                                                                @forelse($opts ?? [] as $o)
                                                                    <span class="badge bg-info text-dark">{{ $o }}</span>
                                                                @empty
                                                                    <span class="text-muted fst-italic small">Ninguna seleccionada</span>
                                                                @endforelse
                                                            </div>
                                                        @elseif($pregunta->tipo_pregunta === 'TEXTO_LIBRE')
                                                            <div class="p-2 bg-white rounded border small text-dark">
                                                                {{ $det->texto_respuesta ?: 'Sin comentarios redactados.' }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light border-top">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
