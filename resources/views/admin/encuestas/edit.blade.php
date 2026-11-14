@extends('layouts.dashboard')

@section('title', 'Editar: ' . $encuesta->titulo . ' - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="mb-3">
        <a href="{{ route('admin.encuestas.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted p-0">
            <i class="bi bi-arrow-left me-1"></i> Volver a la lista de encuestas
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-4">
            <h4 class="fw-bold text-slate-800 mb-1">Editar Encuesta: {{ $encuesta->titulo }}</h4>
            <p class="text-muted small mb-0">Actualiza las configuraciones de aplicación y vigencia.</p>
        </div>

        <div class="card-body p-4 p-md-5">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 rounded-4 mb-4" role="alert">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <h6 class="fw-bold mb-0">Por favor corrige los errores indicados:</h6>
                    </div>
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if ($tieneRespuestas)
                <div class="alert alert-info border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                    <div class="small">
                        Esta encuesta ya cuenta con respuestas registradas. Por integridad estadística, las preguntas no pueden ser eliminadas ni reestructuradas, pero puedes modificar el título, objetivo, vigencia y estado.
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.encuestas.update', $encuesta) }}" method="POST">
                @csrf
                @method('PUT')

                <h5 class="fw-bold text-slate-800 mb-3 border-bottom pb-2">1. Configuración General</h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label for="titulo" class="form-label fw-semibold">Título de la Encuesta <span class="text-danger">*</span></label>
                        <input type="text" name="titulo" id="titulo" class="form-control rounded-3" value="{{ old('titulo', $encuesta->titulo) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label for="tipo" class="form-label fw-semibold">Tipo de Aplicación <span class="text-danger">*</span></label>
                        <select name="tipo" id="tipo" class="form-select rounded-3" required>
                            <option value="GENERAL" @selected(old('tipo', $encuesta->tipo) === 'GENERAL')>General institucional</option>
                            <option value="POST_CITA" @selected(old('tipo', $encuesta->tipo) === 'POST_CITA')>Posterior a cita de orientación</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="disponible_desde" class="form-label fw-semibold">Disponible Desde</label>
                        <input type="date" name="disponible_desde" id="disponible_desde" class="form-control rounded-3" value="{{ old('disponible_desde', $encuesta->disponible_desde?->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label for="disponible_hasta" class="form-label fw-semibold">Disponible Hasta</label>
                        <input type="date" name="disponible_hasta" id="disponible_hasta" class="form-control rounded-3" value="{{ old('disponible_hasta', $encuesta->disponible_hasta?->format('Y-m-d')) }}">
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label fw-semibold">Descripción para el Estudiante</label>
                        <textarea name="descripcion" id="descripcion" rows="2" class="form-control rounded-3">{{ old('descripcion', $encuesta->descripcion) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label for="objetivo" class="form-label fw-semibold">Objetivo Institucional</label>
                        <textarea name="objetivo" id="objetivo" rows="2" class="form-control rounded-3">{{ old('objetivo', $encuesta->objetivo) }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label for="estado" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                        <select name="estado" id="estado" class="form-select rounded-3" required>
                            <option value="BORRADOR" @selected(old('estado', $encuesta->estado) === 'BORRADOR')>Borrador (no visible)</option>
                            <option value="PUBLICADA" @selected(old('estado', $encuesta->estado) === 'PUBLICADA')>Publicada (activa)</option>
                            <option value="CERRADA" @selected(old('estado', $encuesta->estado) === 'CERRADA')>Cerrada</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-center mt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="es_anonima" name="es_anonima" value="1" @checked(old('es_anonima', $encuesta->es_anonima))>
                            <label class="form-check-label fw-semibold" for="es_anonima">Encuesta 100% Anónima</label>
                        </div>
                    </div>

                    <div class="col-md-4 d-flex align-items-center mt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="permite_multiples_respuestas" name="permite_multiples_respuestas" value="1" @checked(old('permite_multiples_respuestas', $encuesta->permite_multiples_respuestas))>
                            <label class="form-check-label fw-semibold" for="permite_multiples_respuestas">Permitir responder más de una vez</label>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold text-slate-800 mb-3 border-bottom pb-2">2. Preguntas de la Encuesta</h5>

                @if(!$tieneRespuestas)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="text-muted small mb-0">
                            Configura los reactivos de la encuesta. Puedes modificar enunciados, cambiar tipos de respuesta (Likert, Valoración, Opciones) y añadir o retirar preguntas.
                        </p>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 flex-shrink-0" id="btnAddPregunta">
                            <i class="bi bi-plus-lg me-1"></i> Agregar Pregunta
                        </button>
                    </div>

                    <div class="d-flex flex-column gap-3 mb-4" id="preguntasContainer"></div>
                @else
                    <div class="d-flex flex-column gap-3 mb-4">
                        @foreach($encuesta->preguntas as $idx => $p)
                            <div class="card border border-light-subtle rounded-4 shadow-sm p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary rounded-pill px-3 py-1">Pregunta {{ $idx + 1 }}</span>
                                    <span class="badge bg-light text-muted border rounded-pill">{{ $p->tipo_pregunta }}</span>
                                </div>
                                <div class="fw-semibold text-slate-800 mb-2">{{ $p->texto_pregunta }}</div>
                                @if($p->es_obligatoria)
                                    <div class="text-danger small"><i class="bi bi-asterisk"></i> Obligatoria</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.encuestas.index') }}" class="btn btn-light rounded-pill px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Actualizar Encuesta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(!$tieneRespuestas)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('preguntasContainer');
    const btnAdd = document.getElementById('btnAddPregunta');
    const initialPreguntas = @json(old('preguntas', $preguntasJson ?? []));

    let pregIndex = 0;

    function renderPregunta(data = {}) {
        const idx = pregIndex++;
        const card = document.createElement('div');
        card.className = 'card border border-light-subtle rounded-4 shadow-sm p-3 preg-card';
        card.dataset.index = idx;

        const tipo = data.tipo || 'LIKERT';
        const isRequired = data.es_obligatoria !== undefined ? Boolean(data.es_obligatoria) : true;

        card.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-primary text-white rounded-pill px-3 py-1 preg-num-badge">Pregunta</span>
                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remove-preg" title="Eliminar pregunta">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Texto de la Pregunta <span class="text-danger">*</span></label>
                    <input type="text" name="preguntas[${idx}][texto]" class="form-control form-control-sm rounded-3" value="${escapeHtml(data.texto || '')}" placeholder="Ej. ¿Cómo calificas la atención recibida?" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Tipo de Respuesta <span class="text-danger">*</span></label>
                    <select name="preguntas[${idx}][tipo]" class="form-select form-select-sm rounded-3 select-tipo-preg" required>
                        <option value="LIKERT" ${tipo === 'LIKERT' ? 'selected' : ''}>Escala Likert (Totalmente desacuerdo a Totalmente de acuerdo)</option>
                        <option value="VALORACION" ${tipo === 'VALORACION' ? 'selected' : ''}>Valoración Numérica (Escala 1 al 10)</option>
                        <option value="OPCION_UNICA" ${tipo === 'OPCION_UNICA' ? 'selected' : ''}>Opción Única (Selección exclusiva)</option>
                        <option value="OPCION_MULTIPLE" ${tipo === 'OPCION_MULTIPLE' ? 'selected' : ''}>Opción Múltiple (Casillas varias)</option>
                        <option value="TEXTO_LIBRE" ${tipo === 'TEXTO_LIBRE' ? 'selected' : ''}>Texto Libre (Comentarios abiertos)</option>
                    </select>
                </div>
                <div class="col-12 options-container" style="display: ${tipo === 'OPCION_UNICA' || tipo === 'OPCION_MULTIPLE' ? 'block' : 'none'};">
                    <label class="form-label small fw-semibold">Opciones de Respuesta (Escribe una opción por línea)</label>
                    <textarea name="preguntas[${idx}][opciones]" class="form-control form-control-sm rounded-3" rows="3" placeholder="Opción 1&#10;Opción 2&#10;Opción 3">${escapeHtml(data.opciones || '')}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="preguntas[${idx}][es_obligatoria]" value="1" ${isRequired ? 'checked' : ''}>
                        <label class="form-check-label small fw-semibold">Respuesta obligatoria</label>
                    </div>
                </div>
            </div>
        `;

        const selectTipo = card.querySelector('.select-tipo-preg');
        const optContainer = card.querySelector('.options-container');

        selectTipo.addEventListener('change', function () {
            if (this.value === 'OPCION_UNICA' || this.value === 'OPCION_MULTIPLE') {
                optContainer.style.display = 'block';
            } else {
                optContainer.style.display = 'none';
            }
        });

        card.querySelector('.btn-remove-preg').addEventListener('click', function () {
            if (container.querySelectorAll('.preg-card').length > 1) {
                card.remove();
                renumberPreguntas();
                const fb = document.getElementById('pregFeedback');
                if (fb) fb.classList.add('d-none');
            } else {
                let fb = document.getElementById('pregFeedback');
                if (!fb) {
                    fb = document.createElement('div');
                    fb.id = 'pregFeedback';
                    fb.className = 'alert alert-warning py-2 px-3 small rounded-3 mt-2';
                    fb.textContent = 'Debe existir al menos una pregunta en la encuesta.';
                    container.parentNode.appendChild(fb);
                }
                fb.classList.remove('d-none');
                setTimeout(() => fb.classList.add('d-none'), 3500);
            }
        });

        container.appendChild(card);
        renumberPreguntas();
    }

    function renumberPreguntas() {
        const cards = container.querySelectorAll('.preg-card');
        cards.forEach((c, i) => {
            c.querySelector('.preg-num-badge').textContent = `Pregunta ${i + 1}`;
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    btnAdd?.addEventListener('click', function () {
        renderPregunta({ tipo: 'LIKERT' });
    });

    if (initialPreguntas && initialPreguntas.length > 0) {
        initialPreguntas.forEach(p => renderPregunta(p));
    } else {
        renderPregunta({ tipo: 'LIKERT' });
    }
});
</script>
@endpush
@endif
@endsection

