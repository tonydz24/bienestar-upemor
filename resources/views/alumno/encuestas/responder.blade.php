@extends('layouts.dashboard')

@section('title', 'Responder Encuesta — ' . $encuesta->titulo)

@section('content')
<div class="container-fluid py-4" style="max-width: 900px;">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('alumno.dashboard') }}" class="text-decoration-none">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('alumno.encuestas.index') }}" class="text-decoration-none">Encuestas</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($encuesta->titulo, 30) }}</li>
        </ol>
    </nav>

    {{-- Encabezado Institucional --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 border-start border-4 border-primary">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge {{ $encuesta->tipo === 'POST_CITA' ? 'bg-info text-dark' : 'bg-primary' }}">
                    {{ $encuesta->tipo === 'POST_CITA' ? 'Encuesta de Satisfacción del Servicio de Citas' : 'Encuesta Institucional' }}
                </span>
                @if($encuesta->es_anonima)
                    <span class="badge bg-success-subtle text-success border border-success">
                        <i class="bi bi-shield-lock-fill me-1"></i> Participación Anónima
                    </span>
                @else
                    <span class="badge bg-primary-subtle text-primary border border-primary">
                        <i class="bi bi-person-check-fill me-1"></i> Participación Identificada
                    </span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-2">{{ $encuesta->titulo }}</h1>
            @if($encuesta->descripcion)
                <p class="text-muted mb-2">{{ $encuesta->descripcion }}</p>
            @endif
            @if($encuesta->objetivo)
                <div class="alert alert-light border small text-muted mb-0">
                    <strong>Objetivo:</strong> {{ $encuesta->objetivo }}
                </div>
            @endif
        </div>
    </div>

    {{-- Aviso Institucional de Privacidad y Transparencia --}}
    @if($encuesta->es_anonima)
        <div class="alert alert-success border-success-subtle shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-shield-lock-fill fs-3 text-success flex-shrink-0"></i>
                <div>
                    <h6 class="fw-bold mb-1 text-success">Participación Confidencial y 100% Anónima</h6>
                    <p class="mb-0 small text-success-emphasis">
                        Tus respuestas en esta encuesta se guardan de forma totalmente disociada de tu cuenta estudiantil. Ningún docente, orientador ni administrador podrá asociar tus respuestas con tu nombre o matrícula. Se utilizarán exclusivamente para análisis estadísticos y mejora continua de Bienestar UPEMOR.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-primary border-primary-subtle shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-info-circle-fill fs-3 text-primary flex-shrink-0"></i>
                <div>
                    <h6 class="fw-bold mb-1 text-primary">Aviso de Privacidad y Transparencia Institucional</h6>
                    <p class="mb-0 small text-primary-emphasis">
                        Tus respuestas se guardarán asociadas a tu cuenta de estudiante. El personal autorizado de orientación psicológica y bienestar institucional podrá consultar quién respondió y las respuestas registradas, exclusivamente para los fines de acompañamiento y seguimiento indicados en esta encuesta.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @include('components.portal-alerts')

    {{-- Formulario de Respuestas --}}
    <form action="{{ route('alumno.encuestas.guardar', $encuesta) }}" method="POST" id="form-encuesta">
        @csrf
        @if(request()->filled('cita_id'))
            <input type="hidden" name="cita_id" value="{{ request('cita_id') }}">
        @endif

        @foreach($encuesta->preguntas as $idx => $pregunta)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="fw-bold text-dark mb-0">
                            <span class="text-primary me-2">#{{ $idx + 1 }}</span>
                            {{ $pregunta->texto_pregunta }}
                        </h5>
                        @if($pregunta->es_obligatoria)
                            <span class="badge bg-danger-subtle text-danger border border-danger small ms-2">Obligatoria</span>
                        @else
                            <span class="badge bg-light text-muted border small ms-2">Opcional</span>
                        @endif
                    </div>

                    {{-- Tipo: LIKERT (1 a 5) --}}
                    @if($pregunta->tipo_pregunta === 'LIKERT')
                        <div class="row g-2 mt-2">
                            @foreach(\App\Models\EncuestaPregunta::opcionesLikertEstandar() as $opt)
                                @php
                                    $optId = "preg_{$pregunta->pregunta_id}_val_{$opt['valor']}";
                                    $isSelected = old("respuestas.{$pregunta->pregunta_id}") == $opt['valor'];
                                @endphp
                                <div class="col-12 col-md">
                                    <input type="radio" 
                                           class="btn-check" 
                                           name="respuestas[{{ $pregunta->pregunta_id }}]" 
                                           id="{{ $optId }}" 
                                           value="{{ $opt['valor'] }}"
                                           {{ $isSelected ? 'checked' : '' }}
                                           {{ $pregunta->es_obligatoria ? 'required' : '' }}>
                                    <label class="btn btn-outline-primary w-100 text-center py-3 d-flex flex-column align-items-center justify-content-center h-100 rounded-3 shadow-none" for="{{ $optId }}">
                                        <div class="fs-5 fw-bold mb-1">{{ $opt['valor'] }}</div>
                                        <span class="small" style="font-size: 0.78rem; line-height: 1.2;">{{ $opt['etiqueta'] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                    {{-- Tipo: VALORACIÓN (1 a 10) --}}
                    @elseif($pregunta->tipo_pregunta === 'VALORACION')
                        <p class="text-muted small mb-2">Selecciona una puntuación del 1 (pésimo/muy bajo) al 10 (excelente/muy alto):</p>
                        <div class="d-flex flex-wrap gap-2 mt-2 justify-content-between">
                            @for($i = 1; $i <= 10; $i++)
                                @php
                                    $valId = "preg_{$pregunta->pregunta_id}_val_{$i}";
                                    $isSelected = old("respuestas.{$pregunta->pregunta_id}") == $i;
                                @endphp
                                <div class="flex-fill text-center">
                                    <input type="radio" 
                                           class="btn-check" 
                                           name="respuestas[{{ $pregunta->pregunta_id }}]" 
                                           id="{{ $valId }}" 
                                           value="{{ $i }}"
                                           {{ $isSelected ? 'checked' : '' }}
                                           {{ $pregunta->es_obligatoria ? 'required' : '' }}>
                                    <label class="btn btn-outline-primary w-100 py-2 fw-bold rounded-3" for="{{ $valId }}">
                                        {{ $i }}
                                    </label>
                                </div>
                            @endfor
                        </div>

                    {{-- Tipo: OPCIÓN ÚNICA --}}
                    @elseif($pregunta->tipo_pregunta === 'OPCION_UNICA')
                        <div class="d-flex flex-column gap-2 mt-2">
                            @foreach($pregunta->opciones ?? [] as $opt)
                                @php
                                    $unicId = "preg_{$pregunta->pregunta_id}_opt_" . Str::slug($opt['etiqueta']);
                                    $isSelected = old("respuestas.{$pregunta->pregunta_id}") === $opt['etiqueta'];
                                @endphp
                                <div class="form-check p-2 rounded bg-light border ps-4">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="respuestas[{{ $pregunta->pregunta_id }}]" 
                                           id="{{ $unicId }}" 
                                           value="{{ $opt['etiqueta'] }}"
                                           {{ $isSelected ? 'checked' : '' }}
                                           {{ $pregunta->es_obligatoria ? 'required' : '' }}>
                                    <label class="form-check-label w-100 text-dark fw-medium" for="{{ $unicId }}">
                                        {{ $opt['etiqueta'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                    {{-- Tipo: OPCIÓN MÚLTIPLE --}}
                    @elseif($pregunta->tipo_pregunta === 'OPCION_MULTIPLE')
                        <div class="d-flex flex-column gap-2 mt-2">
                            @foreach($pregunta->opciones ?? [] as $opt)
                                @php
                                    $multId = "preg_{$pregunta->pregunta_id}_chk_" . Str::slug($opt['etiqueta']);
                                    $oldValues = old("respuestas.{$pregunta->pregunta_id}", []);
                                    $isChecked = is_array($oldValues) && in_array($opt['etiqueta'], $oldValues);
                                @endphp
                                <div class="form-check p-2 rounded bg-light border ps-4">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="respuestas[{{ $pregunta->pregunta_id }}][]" 
                                           id="{{ $multId }}" 
                                           value="{{ $opt['etiqueta'] }}"
                                           {{ $isChecked ? 'checked' : '' }}>
                                    <label class="form-check-label w-100 text-dark fw-medium" for="{{ $multId }}">
                                        {{ $opt['etiqueta'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                    {{-- Tipo: TEXTO LIBRE --}}
                    @elseif($pregunta->tipo_pregunta === 'TEXTO_LIBRE')
                        <div class="mt-2">
                            <textarea class="form-control" 
                                      name="respuestas[{{ $pregunta->pregunta_id }}]" 
                                      rows="3" 
                                      placeholder="Escribe tus comentarios o sugerencias con total libertad..."
                                      {{ $pregunta->es_obligatoria ? 'required' : '' }}>{{ old("respuestas.{$pregunta->pregunta_id}") }}</textarea>
                        </div>
                    @endif

                    @error("respuestas.{$pregunta->pregunta_id}")
                        <div class="text-danger small mt-2">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        @endforeach

        {{-- Acciones del Formulario --}}
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                <a href="{{ route('alumno.encuestas.index') }}" class="btn btn-outline-secondary w-100 w-sm-auto">
                    <i class="bi bi-arrow-left me-1"></i> Cancelar y Volver
                </a>
                <button type="submit" class="btn btn-primary px-5 py-2 w-100 w-sm-auto fw-bold">
                    <i class="bi bi-send-check me-2"></i> Enviar Respuestas
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-encuesta');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    let isSubmitting = false;

    form?.addEventListener('submit', function (e) {
        if (isSubmitting) {
            e.preventDefault();
            return;
        }
        if (!form.checkValidity()) {
            return;
        }
        isSubmitting = true;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enviando respuestas...';
        }
    });
});
</script>
@endpush
@endsection

