@extends('layouts.dashboard')

@section('title', 'Nuevo Ejercicio de Meditación - Administración Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <div class="mb-3">
        <a href="{{ route('admin.meditaciones.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted p-0">
            <i class="bi bi-arrow-left me-1"></i> Volver a la lista de meditaciones
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-4">
            <h4 class="fw-bold text-slate-800 mb-1">Registrar Nuevo Ejercicio de Calma y Relajación</h4>
            <p class="text-muted small mb-0">Configura la información general y la secuencia guiada de respiración o meditación para los estudiantes.</p>
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

            <form action="{{ route('admin.meditaciones.store') }}" method="POST" id="meditacionForm">
                @csrf

                <h5 class="fw-bold text-slate-800 mb-3 border-bottom pb-2">1. Información General</h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label for="titulo" class="form-label fw-semibold">Título del Ejercicio <span class="text-danger">*</span></label>
                        <input type="text" name="titulo" id="titulo" class="form-control rounded-3" value="{{ old('titulo') }}" placeholder="Ej. Respiración Diafragmática Profunda" required>
                    </div>

                    <div class="col-md-4">
                        <label for="categoria" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                        <select name="categoria" id="categoria" class="form-select rounded-3" required>
                            <option value="">Selecciona una categoría...</option>
                            @foreach($categorias as $key => $cat)
                                <option value="{{ $key }}" @selected(old('categoria') === $key)>{{ $cat['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="tipo_contenido" class="form-label fw-semibold">Tipo de Contenido <span class="text-danger">*</span></label>
                        <select name="tipo_contenido" id="tipo_contenido" class="form-select rounded-3" required>
                            @foreach($tiposContenido as $tKey => $tInfo)
                                <option value="{{ $tKey }}" @selected(old('tipo_contenido', 'INTERACTIVO') === $tKey)>{{ $tInfo['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="nivel" class="form-label fw-semibold">Nivel <span class="text-danger">*</span></label>
                        <select name="nivel" id="nivel" class="form-select rounded-3" required>
                            <option value="PRINCIPIANTE" @selected(old('nivel', 'PRINCIPIANTE') === 'PRINCIPIANTE')>Principiante</option>
                            <option value="INTERMEDIO" @selected(old('nivel') === 'INTERMEDIO')>Intermedio</option>
                            <option value="AVANZADO" @selected(old('nivel') === 'AVANZADO')>Avanzado</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="duracion_minutos" class="form-label fw-semibold">Duración Estimada (minutos) <span class="text-danger">*</span></label>
                        <input type="number" name="duracion_minutos" id="duracion_minutos" class="form-control rounded-3" value="{{ old('duracion_minutos', 5) }}" min="1" max="60" required>
                    </div>

                    <div class="col-md-4">
                        <label for="icono" class="form-label fw-semibold">Ícono Bootstrap</label>
                        <input type="text" name="icono" id="icono" class="form-control rounded-3" value="{{ old('icono', 'bi-lungs') }}" placeholder="Ej. bi-lungs, bi-moon-stars">
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label fw-semibold">Descripción y Beneficios para el Alumno <span class="text-danger">*</span></label>
                        <textarea name="descripcion" id="descripcion" rows="3" class="form-control rounded-3" placeholder="Explica en qué consiste el ejercicio, qué objetivos persigue y cuándo se recomienda realizarlo..." required>{{ old('descripcion') }}</textarea>
                    </div>
                </div>

                <!-- Configuración Multimedia / Accesibilidad -->
                <div class="card bg-light border-0 rounded-4 p-3 mb-4" id="multimediaCard">
                    <h6 class="fw-bold text-slate-800 mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-play-circle text-primary"></i>
                        <span>Recurso Audiovisual y Accesibilidad</span>
                    </h6>
                    <div class="row g-3">
                        <div class="col-12" id="mediaUrlGroup">
                            <label for="media_url" class="form-label small fw-semibold">URL del Recurso Audiovisual (YouTube, Vimeo, SoundCloud, MP3 o MP4)</label>
                            <input type="url" name="media_url" id="media_url" class="form-control rounded-3" value="{{ old('media_url') }}" placeholder="https://www.youtube.com/watch?v=... o https://ejemplo.com/audio.mp3">
                            <div class="form-text small">Válido para audio y video embebido o directo (YouTube, YouTube Shorts, Vimeo, etc.).</div>

                            {{-- Vista previa en tiempo real --}}
                            <div id="videoPreviewContainer" class="mt-3 p-3 bg-white rounded-3 border" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-dark"><i class="bi bi-youtube text-danger me-1"></i>Vista Previa del Video Incrustado</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small" id="videoPreviewBadge">Video detectado</span>
                                </div>
                                <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm" style="max-width: 480px; margin: 0 auto;">
                                    <iframe id="videoPreviewIframe" src="" title="Vista Previa de Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                        <div class="col-12" id="transcripcionGroup">
                            <label for="transcripcion" class="form-label small fw-semibold">Transcripción / Texto Completo de la Guía</label>
                            <textarea name="transcripcion" id="transcripcion" rows="4" class="form-control rounded-3" placeholder="Instrucciones detalladas, guion de audio o transcripción completa para accesibilidad (WCAG AA)...">{{ old('transcripcion') }}</textarea>
                            <div class="form-text small">Permite a los estudiantes seguir la sesión mediante lectura o tecnologías asistivas.</div>
                        </div>
                    </div>
                </div>

                <div class="card bg-light border-0 rounded-4 p-3 mb-4">
                    <div class="d-flex flex-column flex-sm-row gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="es_publicado" name="es_publicado" value="1" @checked(old('es_publicado', true))>
                            <label class="form-check-label fw-semibold" for="es_publicado">Publicar inmediatamente en el catálogo de alumnos</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="notificar_alumnos" name="notificar_alumnos" value="1" @checked(old('notificar_alumnos', true))>
                            <label class="form-check-label fw-semibold" for="notificar_alumnos">Enviar notificación en la app a los estudiantes activos</label>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold text-slate-800 mb-3 border-bottom pb-2 d-flex align-items-center justify-content-between">
                    <span>2. Secuencia de Pasos Guiados Interactivos</span>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="btnAddPaso">
                        <i class="bi bi-plus-lg me-1"></i> Añadir Paso
                    </button>
                </h5>

                <p class="text-muted small mb-4">
                    Cada paso representará una fase en la sala visual guiada (temporizador digital + círculo de respiración suave).
                </p>

                <div id="pasosContainer" class="d-flex flex-column gap-3 mb-4">
                    <!-- Pasos dinámicos generados por JavaScript o con old() -->
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.meditaciones.index') }}" class="btn btn-light rounded-pill px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Guardar Ejercicio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('pasosContainer');
    const btnAdd = document.getElementById('btnAddPaso');

    const defaultPasos = [
        { titulo: 'Preparación y Postura', duracion_segundos: 15, fase: 'preparacion', indicacion: 'Ubícate en una posición cómoda y relaja los hombros.' },
        { titulo: 'Inhalación Profunda', duracion_segundos: 4, fase: 'inhala', indicacion: 'Inhala suavemente llenando tus pulmones.' },
        { titulo: 'Sostén con Calma', duracion_segundos: 4, fase: 'sosten', indicacion: 'Mantén el aire con serenidad.' },
        { titulo: 'Exhalación Lenta', duracion_segundos: 4, fase: 'exhala', indicacion: 'Suelta todo el aire por la boca liberando tensiones.' },
        { titulo: 'Pausa en Vacío', duracion_segundos: 4, fase: 'pausa', indicacion: 'Permanece en reposo antes del siguiente ciclo.' }
    ];

    const tipoSelect = document.getElementById('tipo_contenido');
    const mediaUrlGroup = document.getElementById('mediaUrlGroup');
    const transcripcionGroup = document.getElementById('transcripcionGroup');
    const pasosHeader = document.querySelector('h5.border-bottom span').parentElement;

    function adjustFieldsByTipo() {
        const val = tipoSelect ? tipoSelect.value : 'INTERACTIVO';
        if (val === 'INTERACTIVO') {
            if (mediaUrlGroup) mediaUrlGroup.style.display = 'none';
            if (transcripcionGroup) transcripcionGroup.style.display = 'block';
            if (pasosHeader) pasosHeader.style.display = 'flex';
            if (container) container.style.display = 'flex';
        } else if (val === 'GUIA_TEXTUAL') {
            if (mediaUrlGroup) mediaUrlGroup.style.display = 'none';
            if (transcripcionGroup) transcripcionGroup.style.display = 'block';
            if (pasosHeader) pasosHeader.style.display = 'flex';
            if (container) container.style.display = 'flex';
        } else {
            // AUDIO o VIDEO
            if (mediaUrlGroup) mediaUrlGroup.style.display = 'block';
            if (transcripcionGroup) transcripcionGroup.style.display = 'block';
        }
    }

    if (tipoSelect) {
        tipoSelect.addEventListener('change', adjustFieldsByTipo);
        adjustFieldsByTipo();
    }

    let pasoIndex = 0;

    function renderPaso(data = {}) {
        const idx = pasoIndex++;
        const card = document.createElement('div');
        card.className = 'card border border-light-subtle rounded-4 shadow-sm p-3 paso-card';
        card.dataset.index = idx;

        card.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-primary text-white rounded-pill px-3 py-1 paso-num-badge">Paso</span>
                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remove-paso" title="Eliminar paso">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Título de la Fase <span class="text-danger">*</span></label>
                    <input type="text" name="pasos[${idx}][titulo]" class="form-control form-control-sm rounded-3" value="${data.titulo || ''}" placeholder="Ej. Inhalación" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Duración (segundos) <span class="text-danger">*</span></label>
                    <input type="number" name="pasos[${idx}][duracion_segundos]" class="form-control form-control-sm rounded-3" value="${data.duracion_segundos || 4}" min="2" max="600" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Fase Visual <span class="text-danger">*</span></label>
                    <select name="pasos[${idx}][fase]" class="form-select form-select-sm rounded-3" required>
                        <option value="inhala" ${data.fase === 'inhala' ? 'selected' : ''}>Inhalación (Círculo expansivo)</option>
                        <option value="sosten" ${data.fase === 'sosten' ? 'selected' : ''}>Sostén (Pulsación celeste)</option>
                        <option value="exhala" ${data.fase === 'exhala' ? 'selected' : ''}>Exhalación (Círculo contractivo)</option>
                        <option value="pausa" ${data.fase === 'pausa' ? 'selected' : ''}>Pausa / Vacío (Reposo grisáceo)</option>
                        <option value="preparacion" ${data.fase === 'preparacion' ? 'selected' : ''}>Preparación / Apertura</option>
                        <option value="cierre" ${data.fase === 'cierre' ? 'selected' : ''}>Consolidación / Cierre</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Indicación Guía para el Alumno <span class="text-danger">*</span></label>
                    <input type="text" name="pasos[${idx}][indicacion]" class="form-control form-control-sm rounded-3" value="${data.indicacion || ''}" placeholder="Instrucción que leerá el alumno durante esta fase..." required>
                </div>
            </div>
        `;

        card.querySelector('.btn-remove-paso').addEventListener('click', function () {
            if (container.querySelectorAll('.paso-card').length > 1) {
                card.remove();
                renumberPasos();
                const fb = document.getElementById('pasoFeedback');
                if (fb) fb.classList.add('d-none');
            } else {
                let fb = document.getElementById('pasoFeedback');
                if (!fb) {
                    fb = document.createElement('div');
                    fb.id = 'pasoFeedback';
                    fb.className = 'alert alert-warning py-2 px-3 small rounded-3 mt-2';
                    fb.textContent = 'Debe existir al menos un paso en el ejercicio.';
                    container.parentNode.appendChild(fb);
                }
                fb.classList.remove('d-none');
                setTimeout(() => fb.classList.add('d-none'), 3500);
            }
        });

        container.appendChild(card);
        renumberPasos();
    }

    function renumberPasos() {
        const cards = container.querySelectorAll('.paso-card');
        cards.forEach((c, i) => {
            c.querySelector('.paso-num-badge').textContent = `Paso ${i + 1}`;
        });
    }

    defaultPasos.forEach(p => renderPaso(p));

    btnAdd.addEventListener('click', function () {
        renderPaso({
            titulo: 'Nuevo Ciclo',
            duracion_segundos: 4,
            fase: 'inhala',
            indicacion: 'Continúa respirando de manera consciente y sosegada.'
        });
    });

    // Vista previa interactiva de Video (YouTube / Vimeo)
    const mediaInput = document.getElementById('media_url');
    const previewContainer = document.getElementById('videoPreviewContainer');
    const previewIframe = document.getElementById('videoPreviewIframe');
    const previewBadge = document.getElementById('videoPreviewBadge');

    function updateMediaPreview() {
        if (!mediaInput || !previewContainer) return;
        const val = mediaInput.value.trim();
        const ytRegex = /(?:youtube(?:-nocookie)?\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|live|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?/\s]{11})/i;
        const ytMatch = val.match(ytRegex);

        if (ytMatch && ytMatch[1]) {
            previewIframe.src = 'https://www.youtube-nocookie.com/embed/' + ytMatch[1] + '?rel=0';
            previewBadge.textContent = 'YouTube ID: ' + ytMatch[1];
            previewContainer.style.display = 'block';
            return;
        }

        const vimeoRegex = /vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)/i;
        const vimMatch = val.match(vimeoRegex);
        if (vimMatch && vimMatch[1]) {
            previewIframe.src = 'https://player.vimeo.com/video/' + vimMatch[1];
            previewBadge.textContent = 'Vimeo ID: ' + vimMatch[1];
            previewContainer.style.display = 'block';
            return;
        }

        previewContainer.style.display = 'none';
        previewIframe.src = '';
    }

    if (mediaInput) {
        mediaInput.addEventListener('input', updateMediaPreview);
        mediaInput.addEventListener('change', updateMediaPreview);
        updateMediaPreview();
    }
});
</script>
@endpush

