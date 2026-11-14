@extends('layouts.dashboard')

@section('title', 'Mi bitácora emocional - Bienestar UPEMOR')

@section('content')
<div class="bitacora-dashboard-container">
    <!-- 1. Encabezado Hero Compacto y Atractivo -->
    <header class="bitacora-hero-header">
        <div class="bitacora-hero-left">
            <div class="bitacora-hero-icon-box" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"></path>
                </svg>
            </div>
            <div>
                <h1 class="bitacora-hero-title">Mi bitácora emocional</h1>
                <p class="bitacora-hero-subtitle">
                    Registra cómo te sientes día con día para identificar patrones y cuidar tu bienestar integral.
                </p>
            </div>
        </div>
        <div>
            <button type="button" class="btn-bitacora-register" data-bs-toggle="modal" data-bs-target="#modalNuevoRegistro" id="btnNuevoRegistro">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Registrar emoción de hoy</span>
            </button>
        </div>
    </header>

    @php
        $mesAnterior = $fechaReferencia->copy()->subMonth();
        $mesSiguiente = $fechaReferencia->copy()->addMonth();
        $nombreMes = $fechaReferencia->locale('es')->translatedFormat('F Y');
        
        $primerDiaSemana = ($fechaReferencia->copy()->startOfMonth()->dayOfWeekIso) - 1; // 0 = Lunes, 6 = Domingo
        $diasEnMes = $fechaReferencia->daysInMonth;
        $hoyStr = now()->format('Y-m-d');
    @endphp

    <!-- 2. Fila Principal: Calendario Emocional (Izquierda) + Información Contextual (Derecha) -->
    <div class="row g-4 mb-4 align-items-start">
        <!-- Columna Izquierda: Calendario (Mes | Semana | Día) -->
        <div class="col-lg-7 col-xl-7">
            <div class="bitacora-card h-100">
                <div class="bitacora-card-header flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#6b21a8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <h2 class="bitacora-calendar-nav-title mb-0 text-capitalize" id="calendarNavTitle">{{ $nombreMes }}</h2>
                    </div>

                    <div class="d-flex align-items-center gap-2 ms-auto">
                        {{-- Selector de Vista: Mes | Semana | Día --}}
                        <div class="btn-group btn-group-sm rounded-pill p-0.5 bg-light border shadow-none" role="group" aria-label="Modo de visualización de calendario">
                            <button type="button" class="btn btn-sm rounded-pill px-2.5 py-1 btn-view-mode active" data-view="mes" id="btnViewMes" title="Vista mensual">Mes</button>
                            <button type="button" class="btn btn-sm rounded-pill px-2.5 py-1 btn-view-mode" data-view="semana" id="btnViewSemana" title="Vista semanal">Semana</button>
                            <button type="button" class="btn btn-sm rounded-pill px-2.5 py-1 btn-view-mode" data-view="dia" id="btnViewDia" title="Vista diaria">Día</button>
                        </div>

                        {{-- Botones de Navegación --}}
                        <div class="d-flex align-items-center gap-1">
                            <a href="{{ route('alumno.bitacora.index', ['mes' => $mesAnterior->month, 'anio' => $mesAnterior->year]) }}" class="btn-calendar-nav" id="btnNavAnterior" title="Anterior" aria-label="Anterior">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </a>
                            <a href="{{ route('alumno.bitacora.index', ['mes' => now()->month, 'anio' => now()->year]) }}" class="btn-calendar-nav fw-semibold" id="btnNavHoy" title="Ir a hoy">
                                Hoy
                            </a>
                            <a href="{{ route('alumno.bitacora.index', ['mes' => $mesSiguiente->month, 'anio' => $mesSiguiente->year]) }}" class="btn-calendar-nav" id="btnNavSiguiente" title="Siguiente" aria-label="Siguiente">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bitacora-card-body p-3">
                    {{-- VISTA 1: MENSUAL (Grilla completa 7x5) --}}
                    <div id="viewContainerMes">
                        <div class="bitacora-calendar-wrapper">
                            <div class="bitacora-calendar-header">
                                <div>Lun</div>
                                <div>Mar</div>
                                <div>Mié</div>
                                <div>Jue</div>
                                <div>Vie</div>
                                <div>Sáb</div>
                                <div>Dom</div>
                            </div>

                            <div class="bitacora-calendar-grid" id="calendarioGrid">
                                {{-- Días vacíos previos --}}
                                @for ($empty = 0; $empty < $primerDiaSemana; $empty++)
                                    <div class="calendar-day calendar-day-empty"></div>
                                @endfor

                                {{-- Días del mes --}}
                                @for ($day = 1; $day <= $diasEnMes; $day++)
                                    @php
                                        $currentDateStr = sprintf('%04d-%02d-%02d', $anio, $mes, $day);
                                        $isToday = ($currentDateStr === $hoyStr);
                                        $isFuture = ($currentDateStr > $hoyStr);
                                        $entry = $registrosMes->get($currentDateStr);
                                    @endphp

                                    <div class="calendar-day {{ $isToday ? 'is-today' : '' }} {{ $isFuture ? 'is-future' : '' }} {{ $entry ? 'has-entry' : '' }}"
                                         data-date="{{ $currentDateStr }}"
                                         @if ($entry)
                                            data-entry-id="{{ $entry->bitacora_id }}"
                                            data-emocion-id="{{ $entry->emocion_id }}"
                                            data-emocion-slug="{{ $entry->emocion->slug }}"
                                            data-emocion-nombre="{{ $entry->emocion->nombre }}"
                                            data-emocion-color="{{ $entry->emocion->color_hex }}"
                                            data-emocion-icono="{{ $entry->emocion->icono }}"
                                            data-intensidad="{{ $entry->intensidad }}"
                                            data-descripcion="{{ $entry->descripcion }}"
                                            data-observaciones="{{ $entry->observaciones }}"
                                            tabindex="0"
                                            role="button"
                                            aria-label="Día {{ $day }}: {{ $entry->emocion->nombre }}, intensidad {{ $entry->intensidad }}"
                                         @elseif(!$isFuture)
                                            tabindex="0"
                                            role="button"
                                            aria-label="Día {{ $day }}, sin registro emocional"
                                         @else
                                            tabindex="-1"
                                            aria-label="Día {{ $day }}, fecha futura"
                                         @endif
                                    >
                                        <div class="day-number">{{ $day }}</div>
                                        @if ($entry)
                                            <div class="entry-badge" style="background-color: {{ $entry->emocion->color_hex }}15; border: 1px solid {{ $entry->emocion->color_hex }}40;" title="{{ $entry->emocion->nombre }} (Nivel {{ $entry->intensidad }})">
                                                <x-emocion-esfera :emocion="$entry->emocion" size="xs" />
                                            </div>
                                        @endif
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <!-- Leyenda del calendario mensual -->
                        <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center justify-content-between gap-2 text-muted" style="font-size: 0.78rem;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="d-inline-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle" style="width: 8px; height: 8px; background: #581c87;"></span> Hoy
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle" style="width: 8px; height: 8px; background: #8b5cf6;"></span> Con registro
                                </span>
                            </div>
                            <div>
                                <em>Haz clic en cualquier día para consultar o registrar.</em>
                            </div>
                        </div>
                    </div>

                    {{-- VISTA 2: SEMANAL (7 días de la semana activa) --}}
                    <div id="viewContainerSemana" class="d-none">
                        <div class="bitacora-week-wrapper" id="semanaWrapper">
                            {{-- Contenido inyectado dinámicamente vía JavaScript --}}
                        </div>
                        <div class="mt-2 text-center text-muted small" style="font-size: 0.76rem;">
                            <em>Mostrando los 7 días de la semana. Haz clic en cualquier jornada para consultar o registrar.</em>
                        </div>
                    </div>

                    {{-- VISTA 3: DIARIA (Día individual enfocado) --}}
                    <div id="viewContainerDia" class="d-none">
                        <div class="bitacora-day-wrapper" id="diaWrapper">
                            {{-- Contenido inyectado dinámicamente vía JavaScript --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Información Contextual Apilada (Detalle + Significado de Emociones) -->
        <div class="col-lg-5 col-xl-5">
            <div class="d-flex flex-column gap-4">
                <!-- Tarjeta 1: Detalle de registro seleccionado -->
                <div class="bitacora-card" id="panelDetalleDia">
                    <div class="bitacora-card-header">
                        <h3 class="bitacora-card-title">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#6b21a8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span>Detalle de registro</span>
                        </h3>
                        <span class="badge bg-light text-muted border small" id="detalleFechaStr">Hoy</span>
                    </div>
                    <div class="bitacora-card-body d-flex flex-column justify-content-center p-3 p-md-4">
                        <!-- Estado vacío: visible cuando se selecciona un día sin registro o antes de elegir -->
                        <div id="detalleContenidoVacio" class="detail-empty-box py-3 text-center">
                            <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="#cbd5e1" stroke-width="1.5" class="mb-2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <p class="mb-1 fw-semibold text-dark small" id="detalleVacioTitulo">Selecciona un día en el calendario</p>
                            <p class="small text-muted mb-2" id="detalleVacioMensaje">Podrás consultar tus reflexiones o registrar cómo te sentiste.</p>
                            <button type="button" class="btn btn-primary btn-sm mt-2 d-none" id="btnRegistrarDesdeDetalle" data-bs-toggle="modal" data-bs-target="#modalNuevoRegistro">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="me-1">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Registrar emoción para este día</span>
                            </button>
                        </div>

                        <!-- Estado con datos: visible al hacer clic en un día registrado -->
                        <div id="detalleContenido" class="d-none">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div id="detalleEsferaImgWrap" class="flex-shrink-0">
                                    {{-- Renderizado dinámico vía JS --}}
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <h4 class="h5 fw-bold mb-1 text-dark text-truncate" id="detalleBadgeEmocion">Emoción</h4>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="detalleIntensidadTexto">Intensidad: 3/5</span>
                                        <div class="d-flex align-items-center text-warning" id="detalleIntensidadEstrellas" aria-hidden="true"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-quote-box mb-3" id="detalleDescripcionWrap">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="detail-quote-icon fs-5 lh-1 text-primary" aria-hidden="true">“</span>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium text-dark small" id="detalleDescripcion">Sin situación registrada</div>
                                        <div class="text-muted small mt-1" id="detalleObservaciones" style="font-size: 0.8rem;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-2 border-top d-flex gap-2">
                                <button type="button"
                                        class="btn btn-outline-primary btn-detail-action flex-grow-1 justify-content-center"
                                        id="btnEditarDesdeDetalle"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditarRegistro"
                                        title="Editar este registro">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                    </svg>
                                    <span>Editar</span>
                                </button>
                                <button type="button"
                                        class="btn btn-outline-danger btn-detail-action justify-content-center px-3"
                                        id="btnEliminarDesdeDetalle"
                                        title="Eliminar este registro">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    <span>Eliminar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 2: Significado de las 6 Emociones (Lista vertical compacta) -->
                <div class="bitacora-card">
                    <div class="bitacora-card-header">
                        <h3 class="bitacora-card-title">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#6b21a8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                            <span>Significado de emociones</span>
                        </h3>
                        <span class="badge bg-light text-muted border small">6 estados</span>
                    </div>
                    <div class="bitacora-card-body p-3 p-md-4">
                        <div class="emotions-list-compact">
                            @foreach ($emociones as $emocion)
                                <div class="emotion-list-item"
                                     tabindex="0"
                                     role="note"
                                     aria-label="{{ $emocion->nombre }}: {{ $emocion->descripcion }}">
                                    <div class="emotion-list-item-icon">
                                        <x-emocion-esfera :emocion="$emocion" size="36" interactive="true" />
                                    </div>
                                    <div class="emotion-list-item-content">
                                        <div class="emotion-list-item-name">{{ $emocion->nombre }}</div>
                                        <p class="emotion-list-item-desc">{{ $emocion->descripcion }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Sección Inferior: Historial Cronológico de Registros a Ancho Completo -->
    <div class="row">
        <div class="col-12">
            <div class="bitacora-card shadow-sm mb-4">
                <div class="bitacora-card-header flex-column flex-md-row align-items-start align-items-md-center gap-3">
                    <div>
                        <h2 class="bitacora-card-title">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#6b21a8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Historial cronológico</span>
                        </h2>
                        <p class="text-muted small mb-0 mt-1">Tus reflexiones y registros emocionales ordenados por fecha.</p>
                    </div>

                    <!-- Filtro de fechas -->
                    <form action="{{ route('alumno.bitacora.index') }}" method="GET" class="history-filter-bar" role="search">
                        <input type="hidden" name="mes" value="{{ $mes }}">
                        <input type="hidden" name="anio" value="{{ $anio }}">
                        
                        <div class="d-flex align-items-center gap-1">
                            <label for="filtro_desde" class="small text-muted fw-semibold mb-0">Desde:</label>
                            <input type="date" name="filtro_desde" id="filtro_desde" class="input-date-custom" value="{{ request('filtro_desde') }}" max="{{ now()->format('Y-m-d') }}" aria-label="Fecha inicial de filtrado">
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            <label for="filtro_hasta" class="small text-muted fw-semibold mb-0">Hasta:</label>
                            <input type="date" name="filtro_hasta" id="filtro_hasta" class="input-date-custom" value="{{ request('filtro_hasta') }}" max="{{ now()->format('Y-m-d') }}" aria-label="Fecha final de filtrado">
                        </div>

                        <button type="submit" class="btn btn-primary btn-filter-action">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            <span>Filtrar</span>
                        </button>

                        @if (request('filtro_desde') || request('filtro_hasta'))
                            <a href="{{ route('alumno.bitacora.index', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-outline-secondary btn-filter-action" title="Limpiar filtros">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                <span>Limpiar</span>
                            </a>
                        @endif
                    </form>
                </div>

                <div class="p-0">
                    @if ($historial->isEmpty())
                        <div class="history-empty-state">
                            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#cbd5e1" stroke-width="1.5" class="mb-3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                <line x1="8" y1="11" x2="14" y2="11"></line>
                            </svg>
                            @if (request('filtro_desde') || request('filtro_hasta'))
                                <h3 class="h6 fw-bold text-dark mb-1">No se encontraron registros para el rango seleccionado</h3>
                                <p class="text-muted small mb-3">Intenta ampliar el rango de fechas o limpia el filtro para ver todo el historial.</p>
                                <a href="{{ route('alumno.bitacora.index', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-outline-primary btn-sm">Limpiar filtros</a>
                            @else
                                <h3 class="h6 fw-bold text-dark mb-1">Aún no hay registros en tu bitácora</h3>
                                <p class="text-muted small mb-3">Llevar un registro constante de tu estado de ánimo te ayudará a reconocer tus fortalezas y cuidar tu bienestar.</p>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoRegistro">Registrar primera emoción</button>
                            @endif
                        </div>
                    @else
                        <!-- Tabla para pantallas medianas y grandes (Desktop) -->
                        <div class="d-none d-md-block table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th scope="col" class="ps-4" style="width: 160px;">Fecha</th>
                                        <th scope="col" style="width: 170px;">Emoción</th>
                                        <th scope="col" style="width: 140px;">Intensidad</th>
                                        <th scope="col">Motivo / Situación</th>
                                        <th scope="col" class="text-end pe-4" style="width: 80px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($historial as $item)
                                        <tr>
                                            <td class="ps-4 fw-medium text-dark">
                                                {{ Carbon\Carbon::parse($item->fecha)->locale('es')->isoFormat('D MMM YYYY') }}
                                                @if ($item->fecha->isToday())
                                                    <span class="badge bg-primary-subtle text-primary ms-1 small">Hoy</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2"
                                                      style="background-color: {{ $item->emocion->color_hex }}15; color: {{ $item->emocion->color_hex }}; border: 1px solid {{ $item->emocion->color_hex }}40;">
                                                    <x-emocion-esfera :emocion="$item->emocion" size="xs" />
                                                    <span>{{ $item->emocion->nombre }}</span>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1 text-warning" title="Nivel {{ $item->intensidad }} de 5">
                                                    @for ($s = 1; $s <= 5; $s++)
                                                        @if ($s <= $item->intensidad)
                                                            <span aria-hidden="true">★</span>
                                                        @else
                                                            <span class="text-muted opacity-25" aria-hidden="true">★</span>
                                                        @endif
                                                    @endfor
                                                    <span class="text-muted ms-1 small">({{ $item->intensidad }}/5)</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate text-secondary" style="max-width: 440px;">
                                                    {{ $item->descripcion ?: ($item->observaciones ?: 'Sin descripción adicional') }}
                                                </div>
                                            </td>
                                            <td class="text-end pe-4 text-nowrap">
                                                <button type="button"
                                                        class="btn-table-action btn-table-action-edit btn-editar-fila me-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditarRegistro"
                                                        data-id="{{ $item->bitacora_id }}"
                                                        data-fecha="{{ $item->fecha->format('Y-m-d') }}"
                                                        data-emocion-id="{{ $item->emocion_id }}"
                                                        data-emocion-slug="{{ $item->emocion->slug }}"
                                                        data-intensidad="{{ $item->intensidad }}"
                                                        data-descripcion="{{ $item->descripcion }}"
                                                        data-observaciones="{{ $item->observaciones }}"
                                                        title="Editar registro del {{ $item->fecha->format('d/m/Y') }}"
                                                        aria-label="Editar registro del {{ $item->fecha->format('d/m/Y') }}">
                                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M12 20h9"></path>
                                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                                    </svg>
                                                </button>

                                                <button type="button"
                                                        class="btn-table-action btn-table-action-delete btn-eliminar-fila"
                                                        data-id="{{ $item->bitacora_id }}"
                                                        data-fecha="{{ $item->fecha->format('d/m/Y') }}"
                                                        title="Eliminar registro del {{ $item->fecha->format('d/m/Y') }}"
                                                        aria-label="Eliminar registro del {{ $item->fecha->format('d/m/Y') }}">
                                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Tarjetas para dispositivos móviles (evita scroll horizontal y botones aplastados) -->
                        <div class="d-md-none p-3">
                            @foreach ($historial as $item)
                                <div class="history-mobile-card">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fw-semibold text-dark small">
                                            {{ Carbon\Carbon::parse($item->fecha)->locale('es')->isoFormat('D MMM YYYY') }}
                                            @if ($item->fecha->isToday())
                                                <span class="badge bg-primary-subtle text-primary ms-1">Hoy</span>
                                            @endif
                                        </span>
                                        <div class="d-flex gap-1">
                                            <button type="button"
                                                    class="btn-table-action btn-table-action-edit btn-editar-fila"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditarRegistro"
                                                    data-id="{{ $item->bitacora_id }}"
                                                    data-fecha="{{ $item->fecha->format('Y-m-d') }}"
                                                    data-emocion-id="{{ $item->emocion_id }}"
                                                    data-emocion-slug="{{ $item->emocion->slug }}"
                                                    data-intensidad="{{ $item->intensidad }}"
                                                    data-descripcion="{{ $item->descripcion }}"
                                                    data-observaciones="{{ $item->observaciones }}"
                                                    aria-label="Editar registro">
                                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                            </button>
                                            <button type="button"
                                                    class="btn-table-action btn-table-action-delete btn-eliminar-fila"
                                                    data-id="{{ $item->bitacora_id }}"
                                                    data-fecha="{{ $item->fecha->format('d/m/Y') }}"
                                                    aria-label="Eliminar registro">
                                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <x-emocion-esfera :emocion="$item->emocion" size="xs" />
                                        <span class="fw-bold small" style="color: {{ $item->emocion->color_hex }};">{{ $item->emocion->nombre }}</span>
                                        <span class="text-muted small">· Nivel {{ $item->intensidad }}/5</span>
                                    </div>
                                    <p class="text-secondary small mb-0">
                                        {{ $item->descripcion ?: ($item->observaciones ?: 'Sin descripción') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <!-- Paginación -->
                        @if ($historial->hasPages())
                            <div class="p-3 border-top d-flex justify-content-center">
                                {{ $historial->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODALES (Nuevo, Editar)
     ========================================================================= -->

<!-- MODAL: Nuevo Registro Emocional -->
<div class="modal fade" id="modalNuevoRegistro" tabindex="-1" aria-labelledby="modalNuevoRegistroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4" style="background: var(--bienestar-lavender-soft);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-circle bg-white text-primary shadow-sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M2 12h20"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="modal-title h5 fw-bold mb-0 text-dark" id="modalNuevoRegistroLabel">Registrar estado emocional</h2>
                        <span class="text-muted small">Tu información es estrictamente personal y confidencial.</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form action="{{ route('alumno.bitacora.store') }}" method="POST" id="formNuevoRegistro">
                @csrf
                <div class="modal-body p-4">
                    <!-- Fecha del registro -->
                    <div class="mb-3">
                        <label for="nuevoFecha" class="form-label fw-semibold">Fecha del registro</label>
                        <input type="date" name="fecha" id="nuevoFecha" class="form-control" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
                        <div class="form-text">Puedes registrar el día de hoy o un día pasado que no hayas registrado antes.</div>
                    </div>

                    <!-- Selector Visual de Emoción -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">¿Cómo te has sentido principalmente?</label>
                        <div class="row g-2">
                            @foreach ($emociones as $emocion)
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="emocion_id" id="emocion_{{ $emocion->emocion_id }}" value="{{ $emocion->emocion_id }}" required {{ $loop->first ? 'checked' : '' }}>
                                    <label class="btn btn-outline-light text-dark w-100 p-2 d-flex align-items-center gap-2 border rounded-3 emotion-card-label" for="emocion_{{ $emocion->emocion_id }}">
                                        <x-emocion-esfera :emocion="$emocion" size="md" interactive="true" />
                                        <div class="text-start">
                                            <div class="fw-bold small">{{ $emocion->nombre }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ Str::limit($emocion->descripcion, 28) }}</div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Selector de Intensidad (1 a 5) -->
                    <div class="mb-3">
                        <label for="nuevoIntensidad" class="form-label fw-semibold d-flex justify-content-between align-items-center">
                            <span>Nivel de intensidad de la emoción</span>
                            <span class="badge bg-primary-subtle text-primary" id="lblIntensidadTexto">3 - Moderada</span>
                        </label>
                        <input type="range" class="form-range" min="1" max="5" step="1" id="nuevoIntensidad" name="intensidad" value="3">
                        <div class="d-flex justify-content-between text-muted small px-1">
                            <span>1 (Muy leve)</span>
                            <span>2 (Leve)</span>
                            <span>3 (Moderada)</span>
                            <span>4 (Intensa)</span>
                            <span>5 (Muy intensa)</span>
                        </div>
                    </div>

                    <!-- Descripción breve (opcional) -->
                    <div class="mb-3">
                        <label for="nuevoDescripcion" class="form-label fw-semibold">Motivo o situación (opcional)</label>
                        <input type="text" name="descripcion" id="nuevoDescripcion" class="form-control" maxlength="255" placeholder="Ej. Presentación de proyecto en clase, entrega de calificaciones...">
                        <div class="form-text">¿Qué evento o pensamiento detonó principalmente esta emoción?</div>
                    </div>

                    <!-- Observaciones personales (opcional) -->
                    <div class="mb-2">
                        <label for="nuevoObservaciones" class="form-label fw-semibold">Reflexión personal (opcional)</label>
                        <textarea name="observaciones" id="nuevoObservaciones" class="form-control" rows="3" maxlength="2000" placeholder="¿Cómo reaccionaste? ¿Qué hiciste para sentirte mejor o qué aprendiste hoy sobre ti?"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar en mi bitácora</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Editar Registro Emocional -->
<div class="modal fade" id="modalEditarRegistro" tabindex="-1" aria-labelledby="modalEditarRegistroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4" style="background: var(--bienestar-lavender-soft);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-circle bg-white text-primary shadow-sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="modal-title h5 fw-bold mb-0 text-dark" id="modalEditarRegistroLabel">Editar registro emocional</h2>
                        <span class="text-muted small" id="editFechaSubtitulo">Actualiza los detalles de este día</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form action="" method="POST" id="formEditarRegistro">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Selector Visual de Emoción -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Emoción sentida</label>
                        <div class="row g-2">
                            @foreach ($emociones as $emocion)
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="emocion_id" id="edit_emocion_{{ $emocion->emocion_id }}" value="{{ $emocion->emocion_id }}" required>
                                    <label class="btn btn-outline-light text-dark w-100 p-2 d-flex align-items-center gap-2 border rounded-3 emotion-card-label" for="edit_emocion_{{ $emocion->emocion_id }}">
                                        <x-emocion-esfera :emocion="$emocion" size="md" interactive="true" />
                                        <div class="text-start">
                                            <div class="fw-bold small">{{ $emocion->nombre }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ Str::limit($emocion->descripcion, 28) }}</div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Selector de Intensidad -->
                    <div class="mb-3">
                        <label for="editIntensidad" class="form-label fw-semibold d-flex justify-content-between align-items-center">
                            <span>Nivel de intensidad</span>
                            <span class="badge bg-primary-subtle text-primary" id="lblEditIntensidadTexto">3 - Moderada</span>
                        </label>
                        <input type="range" class="form-range" min="1" max="5" step="1" id="editIntensidad" name="intensidad" value="3">
                        <div class="d-flex justify-content-between text-muted small px-1">
                            <span>1 (Muy leve)</span>
                            <span>2 (Leve)</span>
                            <span>3 (Moderada)</span>
                            <span>4 (Intensa)</span>
                            <span>5 (Muy intensa)</span>
                        </div>
                    </div>

                    <!-- Descripción breve -->
                    <div class="mb-3">
                        <label for="editDescripcion" class="form-label fw-semibold">Motivo o situación (opcional)</label>
                        <input type="text" name="descripcion" id="editDescripcion" class="form-control" maxlength="255">
                    </div>

                    <!-- Observaciones personales -->
                    <div class="mb-2">
                        <label for="editObservaciones" class="form-label fw-semibold">Reflexión personal (opcional)</label>
                        <textarea name="observaciones" id="editObservaciones" class="form-control" rows="3" maxlength="2000"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1" id="btnEliminarDesdeModalEditar">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        <span>Eliminar registro</span>
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Actualizar registro</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Confirmar Eliminación de Registro Emocional -->
<div class="modal fade" id="modalEliminarBitacoraConfirm" tabindex="-1" aria-hidden="true" style="white-space: normal !important;">
    <div class="modal-dialog modal-dialog-centered" style="white-space: normal !important; max-width: 480px;">
        <div class="modal-content rounded-4 border-0 shadow" style="white-space: normal !important; word-break: break-word;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    <span>Eliminar registro emocional</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-start" style="white-space: normal !important; word-break: break-word;">
                <p class="text-muted small mb-2" id="eliminarBitacoraMensajeTexto">
                    ¿Confirmas que deseas eliminar este registro de tu bitácora emocional?
                </p>
                <div class="p-2.5 rounded-3 bg-light border small text-muted">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Si este registro te otorgó puntos de bienestar diarios, el saldo de puntos será revertido de forma automática y trazable en tu cuenta. Si los puntos otorgados ya fueron utilizados en canjes de recompensas y tu saldo disponible actual es insuficiente, la eliminación no se completará.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                <form action="" method="POST" id="formEliminarBitacora" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold">Confirmar Eliminación</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Mapa de esferas y emociones accesibles
    const emotionDataMap = {
        @foreach ($emociones as $e)
            "{{ $e->emocion_id }}": {
                id: {{ $e->emocion_id }},
                slug: "{{ $e->slug }}",
                nombre: "{{ $e->nombre }}",
                color: "{{ $e->color_hex }}",
                icono: "{{ $e->icono }}",
                imgSrc: "{{ asset('images/emociones/' . $e->slug . '.png') }}"
            },
        @endforeach
    };

    const intensityLabels = {
        1: '1 - Muy leve',
        2: '2 - Leve',
        3: '3 - Moderada',
        4: '4 - Intensa',
        5: '5 - Muy intensa'
    };

    // 2. Actualizadores de etiqueta de intensidad en tiempo real
    const rangeNuevo = document.getElementById('nuevoIntensidad');
    const lblNuevo = document.getElementById('lblIntensidadTexto');
    rangeNuevo?.addEventListener('input', function () {
        if (lblNuevo) lblNuevo.textContent = intensityLabels[this.value] || this.value;
    });

    const rangeEdit = document.getElementById('editIntensidad');
    const lblEdit = document.getElementById('lblEditIntensidadTexto');
    rangeEdit?.addEventListener('input', function () {
        if (lblEdit) lblEdit.textContent = intensityLabels[this.value] || this.value;
    });

    // 3. Formateador de fechas a formato legible institucional en español
    const mesesNombres = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    function formatearFechaLegible(fechaStr) {
        if (!fechaStr) return '';
        const partes = fechaStr.split('-');
        if (partes.length === 3) {
            const d = parseInt(partes[2], 10);
            const m = mesesNombres[parseInt(partes[1], 10) - 1] || partes[1];
            const y = partes[0];
            return `${d} de ${m} de ${y}`;
        }
        return fechaStr;
    }

    // 4. Gestión de entradas del mes y selección unificada de calendario
    const monthEntries = {};
    document.querySelectorAll('#calendarioGrid .calendar-day[data-date]').forEach(cell => {
        const date = cell.dataset.date;
        if (cell.classList.contains('has-entry')) {
            monthEntries[date] = {
                id: cell.dataset.entryId,
                fecha: date,
                emocionId: cell.dataset.emocionId,
                emocionSlug: cell.dataset.emocionSlug || '',
                emocionNombre: cell.dataset.emocionNombre,
                emocionColor: cell.dataset.emocionColor || '#6366f1',
                emocionIcono: cell.dataset.emocionIcono,
                intensidad: cell.dataset.intensidad,
                descripcion: cell.dataset.descripcion || '',
                observaciones: cell.dataset.observaciones || ''
            };
        }
    });

    let currentSelectedEntry = null;
    let activeDate = "{{ $hoyStr }}";
    let currentView = "mes";

    function parseDate(str) {
        if (!str) return new Date();
        const parts = str.split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function getMonday(d) {
        const date = new Date(d);
        const day = date.getDay();
        const diff = date.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(date.setDate(diff));
    }

    function addDays(d, days) {
        const res = new Date(d);
        res.setDate(res.getDate() + days);
        return res;
    }

    function renderizarEstrellas(intensidad) {
        const intVal = parseInt(intensidad, 10) || 3;
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= intVal) {
                html += '<span class="fs-6" aria-hidden="true">★</span>';
            } else {
                html += '<span class="text-muted opacity-25 fs-6" aria-hidden="true">★</span>';
            }
        }
        return html;
    }

    function mostrarDetalleEnPanel(entry) {
        const boxVacio = document.getElementById('detalleContenidoVacio');
        const boxContenido = document.getElementById('detalleContenido');
        const badgeFecha = document.getElementById('detalleFechaStr');

        if (boxVacio) boxVacio.classList.add('d-none');
        if (boxContenido) boxContenido.classList.remove('d-none');

        if (badgeFecha) {
            badgeFecha.textContent = formatearFechaLegible(entry.fecha);
        }

        // Esfera 3D o fallback
        const esferaWrap = document.getElementById('detalleEsferaImgWrap');
        if (esferaWrap) {
            const emoMeta = emotionDataMap[entry.emocionId];
            const imgSrc = emoMeta ? emoMeta.imgSrc : `/images/emociones/${entry.emocionSlug}.png`;
            const icono = (emoMeta ? emoMeta.icono : entry.emocionIcono) || '✨';

            esferaWrap.innerHTML = `<img src="${imgSrc}" 
                                         class="emotion-sphere-img emotion-sphere-${entry.emocionSlug || ''}" 
                                         alt="Esfera 3D de ${entry.emocionNombre || 'Emoción'}" 
                                         width="48" height="48" 
                                         loading="lazy"
                                         onerror="this.onerror=null; this.replaceWith('${icono}');">`;
        }

        const nombreEl = document.getElementById('detalleBadgeEmocion');
        if (nombreEl) nombreEl.textContent = entry.emocionNombre || 'Emoción';

        const intTxt = document.getElementById('detalleIntensidadTexto');
        if (intTxt) intTxt.textContent = `Intensidad: ${entry.intensidad}/5`;

        const starsContainer = document.getElementById('detalleIntensidadEstrellas');
        if (starsContainer) starsContainer.innerHTML = renderizarEstrellas(entry.intensidad);

        const descEl = document.getElementById('detalleDescripcion');
        if (descEl) {
            descEl.textContent = entry.descripcion || 'Sin situación o detonante registrado';
        }

        const obsEl = document.getElementById('detalleObservaciones');
        if (obsEl) {
            obsEl.textContent = entry.observaciones || '';
            obsEl.style.display = entry.observaciones ? 'block' : 'none';
        }

        // Configurar botón de edición del panel
        const btnEditar = document.getElementById('btnEditarDesdeDetalle');
        if (btnEditar) {
            btnEditar.dataset.id = entry.id;
            btnEditar.dataset.fecha = entry.fecha;
            btnEditar.dataset.emocionId = entry.emocionId;
            btnEditar.dataset.intensidad = entry.intensidad;
            btnEditar.dataset.descripcion = entry.descripcion || '';
            btnEditar.dataset.observaciones = entry.observaciones || '';
        }
    }

    function mostrarVacioEnPanel(fechaStr, isFuture) {
        const boxVacio = document.getElementById('detalleContenidoVacio');
        const boxContenido = document.getElementById('detalleContenido');
        const badgeFecha = document.getElementById('detalleFechaStr');
        const tituloVacio = document.getElementById('detalleVacioTitulo');
        const mensajeVacio = document.getElementById('detalleVacioMensaje');
        const btnRegistrar = document.getElementById('btnRegistrarDesdeDetalle');

        if (boxContenido) boxContenido.classList.add('d-none');
        if (boxVacio) boxVacio.classList.remove('d-none');

        if (badgeFecha) {
            badgeFecha.textContent = formatearFechaLegible(fechaStr);
        }

        if (isFuture) {
            if (tituloVacio) tituloVacio.textContent = 'Fecha posterior a hoy';
            if (mensajeVacio) mensajeVacio.textContent = 'Aún no es posible registrar emociones para fechas futuras.';
            if (btnRegistrar) btnRegistrar.classList.add('d-none');
        } else {
            if (tituloVacio) tituloVacio.textContent = 'Sin registro en este día';
            if (mensajeVacio) mensajeVacio.textContent = 'No registraste tu estado emocional el ' + formatearFechaLegible(fechaStr) + '.';
            if (btnRegistrar) {
                btnRegistrar.classList.remove('d-none');
                btnRegistrar.dataset.fecha = fechaStr;
            }
        }
    }

    // Selección centralizada de fecha
    function seleccionarFecha(fechaStr) {
        if (!fechaStr) return;
        activeDate = fechaStr;

        // 1. Sincronizar selección en Vista Mensual
        document.querySelectorAll('#calendarioGrid .calendar-day').forEach(c => {
            if (c.dataset.date === fechaStr) {
                c.classList.add('selected-day');
            } else {
                c.classList.remove('selected-day');
            }
        });

        // 2. Sincronizar selección en Vista Semanal
        document.querySelectorAll('#semanaWrapper .week-day-card').forEach(card => {
            if (card.dataset.date === fechaStr) {
                card.classList.add('selected-day');
            } else {
                card.classList.remove('selected-day');
            }
        });

        // 3. Actualizar contenido si estamos en Vista Diaria
        if (currentView === 'dia') {
            renderizarDia(fechaStr);
        }

        // 4. Actualizar Panel Lateral de Detalle
        const entry = monthEntries[fechaStr];
        if (entry) {
            currentSelectedEntry = entry;
            mostrarDetalleEnPanel(entry);
        } else {
            currentSelectedEntry = null;
            const isFuture = fechaStr > "{{ $hoyStr }}";
            mostrarVacioEnPanel(fechaStr, isFuture);
        }
    }

    // Renderizado de Vista Semanal (7 días)
    function renderizarSemana(fechaRefStr) {
        const wrapper = document.getElementById('semanaWrapper');
        if (!wrapper) return;

        const refDate = parseDate(fechaRefStr);
        const monday = getMonday(refDate);
        const sunday = addDays(monday, 6);
        const dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // Actualizar título de cabecera con el rango semanal
        const titleEl = document.getElementById('calendarNavTitle');
        if (titleEl) {
            const mesIni = mesesNombres[monday.getMonth()];
            const mesFin = mesesNombres[sunday.getMonth()];
            if (mesIni === mesFin) {
                titleEl.textContent = `Semana: ${monday.getDate()} al ${sunday.getDate()} de ${mesIni} ${sunday.getFullYear()}`;
            } else {
                titleEl.textContent = `Semana: ${monday.getDate()} de ${mesIni} al ${sunday.getDate()} de ${mesFin} ${sunday.getFullYear()}`;
            }
        }

        let html = '';
        for (let i = 0; i < 7; i++) {
            const curDate = addDays(monday, i);
            const curStr = formatDate(curDate);
            const isToday = (curStr === "{{ $hoyStr }}");
            const isFuture = (curStr > "{{ $hoyStr }}");
            const isSelected = (curStr === activeDate);
            const entry = monthEntries[curStr];

            let badgeHtml = '';
            let styleBg = '';
            if (entry) {
                const emo = emotionDataMap[entry.emocionId];
                const imgSrc = emo ? emo.imgSrc : `/images/emociones/${entry.emocionSlug}.png`;
                badgeHtml = `
                    <div class="d-flex align-items-center gap-2">
                        <img src="${imgSrc}" width="26" height="26" class="emotion-sphere-img" alt="${entry.emocionNombre}" onerror="this.onerror=null; this.replaceWith('${entry.emocionIcono || '✨'}');">
                        <span class="badge px-2.5 py-1 rounded-pill fw-semibold" style="background-color: ${entry.emocionColor}18; color: ${entry.emocionColor}; border: 1px solid ${entry.emocionColor}40;">
                            ${entry.emocionNombre} (${entry.intensidad}/5)
                        </span>
                    </div>
                `;
            } else if (!isFuture) {
                badgeHtml = `<span class="badge bg-light text-muted border small">Sin registro</span>`;
            } else {
                badgeHtml = `<span class="badge bg-light text-muted border small opacity-50">Fecha futura</span>`;
            }

            html += `
                <div class="week-day-card ${isToday ? 'is-today' : ''} ${isFuture ? 'is-future' : ''} ${isSelected ? 'selected-day' : ''}"
                     data-date="${curStr}"
                     role="button"
                     tabindex="0"
                     aria-label="${dayNames[i]} ${curDate.getDate()}: ${entry ? entry.emocionNombre : 'sin registro'}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold small text-dark" style="min-width: 65px;">${dayNames[i]}</span>
                        <span class="badge ${isToday ? 'bg-primary' : 'bg-light text-dark border'} rounded-pill px-2">
                            ${curDate.getDate()}
                        </span>
                        ${isToday ? '<span class="badge bg-primary-subtle text-primary small">Hoy</span>' : ''}
                    </div>
                    <div>
                        ${badgeHtml}
                    </div>
                </div>
            `;
        }

        wrapper.innerHTML = html;

        // Listeners para clics en tarjetas de la semana
        wrapper.querySelectorAll('.week-day-card').forEach(card => {
            card.addEventListener('click', function () {
                const date = this.dataset.date;
                if (date) {
                    seleccionarFecha(date);
                }
            });
        });
    }

    // Renderizado de Vista Diaria Enfocada
    function renderizarDia(fechaStr) {
        const wrapper = document.getElementById('diaWrapper');
        if (!wrapper) return;

        const curDate = parseDate(fechaStr);
        const dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const dayName = dayNames[curDate.getDay()];
        const fullLegible = `${dayName}, ${formatearFechaLegible(fechaStr)}`;

        // Actualizar título de cabecera con el día enfocado
        const titleEl = document.getElementById('calendarNavTitle');
        if (titleEl) {
            titleEl.textContent = fullLegible;
        }

        const entry = monthEntries[fechaStr];
        const isToday = (fechaStr === "{{ $hoyStr }}");
        const isFuture = (fechaStr > "{{ $hoyStr }}");

        if (entry) {
            const emo = emotionDataMap[entry.emocionId];
            const imgSrc = emo ? emo.imgSrc : `/images/emociones/${entry.emocionSlug}.png`;
            const icono = (emo ? emo.icono : entry.emocionIcono) || '✨';

            wrapper.innerHTML = `
                <div class="day-view-hero">
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                        <span class="badge ${isToday ? 'bg-primary' : 'bg-light text-dark border'} px-3 py-1.5 rounded-pill fs-6 fw-bold">
                            ${fullLegible} ${isToday ? '• Hoy' : ''}
                        </span>
                    </div>

                    <div class="my-3">
                        <div class="d-inline-block p-2 rounded-circle shadow-sm" style="background: ${entry.emocionColor}15; border: 3px solid ${entry.emocionColor};">
                            <img src="${imgSrc}" width="64" height="64" class="emotion-sphere-img" alt="${entry.emocionNombre}" onerror="this.onerror=null; this.replaceWith('${icono}');">
                        </div>
                    </div>

                    <h3 class="h4 fw-bold mb-1" style="color: ${entry.emocionColor};">${entry.emocionNombre}</h3>
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                        <span class="badge bg-light text-dark border">Intensidad: ${entry.intensidad}/5</span>
                        <div class="text-warning">${renderizarEstrellas(entry.intensidad)}</div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border text-start mb-3 small">
                        <strong class="d-block text-dark mb-1">Motivo o situación:</strong>
                        <div class="text-secondary">${entry.descripcion || 'Sin situación registrada'}</div>
                        ${entry.observaciones ? `<div class="mt-2 text-muted fst-italic">“${entry.observaciones}”</div>` : ''}
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4 btn-editar-dia-focused" title="Editar este registro">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-4 btn-eliminar-dia-focused" title="Eliminar este registro">
                            <i class="bi bi-trash me-1"></i> Eliminar
                        </button>
                    </div>
                </div>
            `;

            // Botones de acción dentro de la vista diaria
            wrapper.querySelector('.btn-editar-dia-focused')?.addEventListener('click', function () {
                poblarFormularioEditar(entry);
                const modalEl = document.getElementById('modalEditarRegistro');
                if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });

            wrapper.querySelector('.btn-eliminar-dia-focused')?.addEventListener('click', function () {
                prepararModalEliminacion(entry.id, formatearFechaLegible(entry.fecha));
            });

        } else if (!isFuture) {
            wrapper.innerHTML = `
                <div class="py-4 text-center">
                    <div class="mb-2">
                        <span class="badge ${isToday ? 'bg-primary' : 'bg-light text-dark border'} px-3 py-1.5 rounded-pill fs-6 fw-bold">
                            ${fullLegible} ${isToday ? '• Hoy' : ''}
                        </span>
                    </div>
                    <div class="my-3 text-secondary">
                        <i class="bi bi-journal-plus display-5 text-muted opacity-50"></i>
                    </div>
                    <h4 class="h6 fw-bold text-dark mb-1">Sin registro emocional para este día</h4>
                    <p class="text-muted small mb-3">Aún no has registrado cómo te sentiste el ${formatearFechaLegible(fechaStr)}.</p>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 btn-registrar-dia-focused" data-fecha="${fechaStr}">
                        <i class="bi bi-plus-circle me-1"></i> Registrar emoción para este día
                    </button>
                </div>
            `;

            wrapper.querySelector('.btn-registrar-dia-focused')?.addEventListener('click', function () {
                const inputFecha = document.getElementById('nuevoFecha');
                if (inputFecha) inputFecha.value = this.dataset.fecha;
                const modalEl = document.getElementById('modalNuevoRegistro');
                if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });

        } else {
            wrapper.innerHTML = `
                <div class="py-4 text-center text-muted">
                    <span class="badge bg-light text-muted border px-3 py-1.5 rounded-pill fs-6 fw-semibold mb-2">
                        ${fullLegible}
                    </span>
                    <div class="my-3">
                        <i class="bi bi-calendar-x display-5 text-muted opacity-25"></i>
                    </div>
                    <h4 class="h6 fw-bold text-secondary mb-1">Fecha posterior a hoy</h4>
                    <p class="small text-muted mb-0">No es posible registrar emociones para fechas futuras.</p>
                </div>
            `;
        }
    }

    // Alternador general de modos de vista
    function cambiarModoVista(modo) {
        currentView = modo;
        try {
            sessionStorage.setItem('bitacora_view', modo);
        } catch (e) {}

        const btnMes = document.getElementById('btnViewMes');
        const btnSemana = document.getElementById('btnViewSemana');
        const btnDia = document.getElementById('btnViewDia');

        if (btnMes) btnMes.classList.toggle('active', modo === 'mes');
        if (btnSemana) btnSemana.classList.toggle('active', modo === 'semana');
        if (btnDia) btnDia.classList.toggle('active', modo === 'dia');

        const boxMes = document.getElementById('viewContainerMes');
        const boxSemana = document.getElementById('viewContainerSemana');
        const boxDia = document.getElementById('viewContainerDia');

        if (boxMes) boxMes.classList.toggle('d-none', modo !== 'mes');
        if (boxSemana) boxSemana.classList.toggle('d-none', modo !== 'semana');
        if (boxDia) boxDia.classList.toggle('d-none', modo !== 'dia');

        if (modo === 'mes') {
            const titleEl = document.getElementById('calendarNavTitle');
            if (titleEl) titleEl.textContent = "{{ $nombreMes }}";
            seleccionarFecha(activeDate);
        } else if (modo === 'semana') {
            renderizarSemana(activeDate);
            seleccionarFecha(activeDate);
        } else if (modo === 'dia') {
            renderizarDia(activeDate);
            seleccionarFecha(activeDate);
        }
    }

    // Conectar botones selectores de vista
    document.getElementById('btnViewMes')?.addEventListener('click', () => cambiarModoVista('mes'));
    document.getElementById('btnViewSemana')?.addEventListener('click', () => cambiarModoVista('semana'));
    document.getElementById('btnViewDia')?.addEventListener('click', () => cambiarModoVista('dia'));

    // Navegación inteligente (Anterior / Siguiente / Hoy) según la vista activa
    const btnNavAnt = document.getElementById('btnNavAnterior');
    const btnNavSig = document.getElementById('btnNavSiguiente');
    const btnNavHoy = document.getElementById('btnNavHoy');

    btnNavAnt?.addEventListener('click', function (e) {
        if (currentView === 'semana') {
            e.preventDefault();
            const prevDate = formatDate(addDays(parseDate(activeDate), -7));
            const [ny, nm] = prevDate.split('-').map(Number);
            if (nm === {{ $mes }} && ny === {{ $anio }}) {
                seleccionarFecha(prevDate);
                renderizarSemana(prevDate);
            } else {
                window.location.href = `{{ route('alumno.bitacora.index') }}?mes=${nm}&anio=${ny}&date=${prevDate}&view=semana`;
            }
        } else if (currentView === 'dia') {
            e.preventDefault();
            const prevDate = formatDate(addDays(parseDate(activeDate), -1));
            const [ny, nm] = prevDate.split('-').map(Number);
            if (nm === {{ $mes }} && ny === {{ $anio }}) {
                seleccionarFecha(prevDate);
                renderizarDia(prevDate);
            } else {
                window.location.href = `{{ route('alumno.bitacora.index') }}?mes=${nm}&anio=${ny}&date=${prevDate}&view=dia`;
            }
        }
    });

    btnNavSig?.addEventListener('click', function (e) {
        if (currentView === 'semana') {
            e.preventDefault();
            const nextDate = formatDate(addDays(parseDate(activeDate), 7));
            const [ny, nm] = nextDate.split('-').map(Number);
            if (nm === {{ $mes }} && ny === {{ $anio }}) {
                seleccionarFecha(nextDate);
                renderizarSemana(nextDate);
            } else {
                window.location.href = `{{ route('alumno.bitacora.index') }}?mes=${nm}&anio=${ny}&date=${nextDate}&view=semana`;
            }
        } else if (currentView === 'dia') {
            e.preventDefault();
            const nextDate = formatDate(addDays(parseDate(activeDate), 1));
            const [ny, nm] = nextDate.split('-').map(Number);
            if (nm === {{ $mes }} && ny === {{ $anio }}) {
                seleccionarFecha(nextDate);
                renderizarDia(nextDate);
            } else {
                window.location.href = `{{ route('alumno.bitacora.index') }}?mes=${nm}&anio=${ny}&date=${nextDate}&view=dia`;
            }
        }
    });

    btnNavHoy?.addEventListener('click', function (e) {
        if ({{ $mes }} === {{ now()->month }} && {{ $anio }} === {{ now()->year }}) {
            e.preventDefault();
            seleccionarFecha("{{ $hoyStr }}");
            if (currentView === 'semana') renderizarSemana("{{ $hoyStr }}");
            if (currentView === 'dia') renderizarDia("{{ $hoyStr }}");
        }
    });

    // Botón de registrar desde el panel de detalle vacío
    document.getElementById('btnRegistrarDesdeDetalle')?.addEventListener('click', function () {
        const fecha = this.dataset.fecha || activeDate;
        if (fecha) {
            const inputFecha = document.getElementById('nuevoFecha');
            if (inputFecha) inputFecha.value = fecha;
        }
    });

    // Clic en celdas del calendario mensual
    document.querySelectorAll('#calendarioGrid .calendar-day:not(.calendar-day-empty)').forEach(cell => {
        cell.addEventListener('click', function () {
            const date = this.dataset.date;
            if (date) {
                seleccionarFecha(date);
            }
        });
    });

    // 5. Gestión del Modal de Edición
    function poblarFormularioEditar(data) {
        const form = document.getElementById('formEditarRegistro');
        if (form && data.id) {
            form.action = `/alumno/bitacora/${data.id}`;
        }

        const sub = document.getElementById('editFechaSubtitulo');
        if (sub && data.fecha) {
            sub.textContent = `Actualizando registro del ${formatearFechaLegible(data.fecha)}`;
        }

        // Seleccionar radio button de la emoción
        if (data.emocionId) {
            const radio = document.getElementById(`edit_emocion_${data.emocionId}`);
            if (radio) {
                radio.checked = true;
            }
        }

        // Establecer intensidad
        if (data.intensidad) {
            const range = document.getElementById('editIntensidad');
            if (range) {
                range.value = data.intensidad;
            }
            if (lblEdit) {
                lblEdit.textContent = intensityLabels[data.intensidad] || data.intensidad;
            }
        }

        // Establecer descripción y observaciones
        const descInput = document.getElementById('editDescripcion');
        if (descInput) {
            descInput.value = data.descripcion || '';
        }

        const obsInput = document.getElementById('editObservaciones');
        if (obsInput) {
            obsInput.value = data.observaciones || '';
        }
    }

    // Listener para show.bs.modal en modalEditarRegistro
    const modalEditarEl = document.getElementById('modalEditarRegistro');
    modalEditarEl?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (button && button.dataset.id) {
            poblarFormularioEditar({
                id: button.dataset.id,
                fecha: button.dataset.fecha,
                emocionId: button.dataset.emocionId,
                intensidad: button.dataset.intensidad,
                descripcion: button.dataset.descripcion,
                observaciones: button.dataset.observaciones
            });
        } else if (currentSelectedEntry) {
            poblarFormularioEditar(currentSelectedEntry);
        }
    });

    // Soporte para clics directos sobre botones de editar
    document.querySelectorAll('.btn-editar-fila').forEach(btn => {
        btn.addEventListener('click', function () {
            poblarFormularioEditar({
                id: this.dataset.id,
                fecha: this.dataset.fecha,
                emocionId: this.dataset.emocionId,
                intensidad: this.dataset.intensidad,
                descripcion: this.dataset.descripcion,
                observaciones: this.dataset.observaciones
            });
        });
    });

    // Botón de nuevo registro principal
    document.getElementById('btnNuevoRegistro')?.addEventListener('click', function () {
        const inputFecha = document.getElementById('nuevoFecha');
        if (inputFecha) inputFecha.value = "{{ now()->format('Y-m-d') }}";
    });

    // 6. Gestión de Eliminación de Registros de Bitácora
    function prepararModalEliminacion(id, fechaStr) {
        const form = document.getElementById('formEliminarBitacora');
        if (form && id) {
            form.action = `/alumno/bitacora/${id}`;
        }
        const txt = document.getElementById('eliminarBitacoraMensajeTexto');
        if (txt) {
            txt.innerHTML = `¿Confirmas que deseas eliminar el registro de tu bitácora emocional del día <strong>${fechaStr || 'seleccionado'}</strong>? Esta acción no se puede deshacer.`;
        }
        const modalEl = document.getElementById('modalEliminarBitacoraConfirm');
        if (modalEl) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        }
    }

    document.querySelectorAll('.btn-eliminar-fila').forEach(btn => {
        btn.addEventListener('click', function () {
            prepararModalEliminacion(this.dataset.id, this.dataset.fecha);
        });
    });

    document.getElementById('btnEliminarDesdeDetalle')?.addEventListener('click', function () {
        if (currentSelectedEntry && currentSelectedEntry.id) {
            prepararModalEliminacion(currentSelectedEntry.id, formatearFechaLegible(currentSelectedEntry.fecha));
        }
    });

    document.getElementById('btnEliminarDesdeModalEditar')?.addEventListener('click', function () {
        const formEditar = document.getElementById('formEditarRegistro');
        const match = formEditar?.action?.match(/\/alumno\/bitacora\/(\d+)/);
        if (match && match[1]) {
            const editModalEl = document.getElementById('modalEditarRegistro');
            if (editModalEl) {
                const bsEdit = bootstrap.Modal.getInstance(editModalEl);
                bsEdit?.hide();
            }
            prepararModalEliminacion(match[1], '');
        }
    });

    // 7. Selección e inicialización de vista y fecha activa
    const urlParams = new URLSearchParams(window.location.search);
    const requestedView = urlParams.get('view') || (function() {
        try { return sessionStorage.getItem('bitacora_view'); } catch (e) { return null; }
    })() || 'mes';
    const requestedDate = urlParams.get('date');

    const todayCell = document.querySelector('#calendarioGrid .calendar-day.is-today');
    const firstEntryCell = document.querySelector('#calendarioGrid .calendar-day.has-entry');

    if (requestedDate && requestedDate.startsWith("{{ sprintf('%04d-%02d', $anio, $mes) }}")) {
        activeDate = requestedDate;
    } else if (todayCell && todayCell.dataset.date) {
        activeDate = todayCell.dataset.date;
    } else if (firstEntryCell && firstEntryCell.dataset.date) {
        activeDate = firstEntryCell.dataset.date;
    } else {
        activeDate = "{{ sprintf('%04d-%02d-01', $anio, $mes) }}";
    }

    // Inicializar en la vista solicitada (Mes, Semana o Día)
    cambiarModoVista(requestedView);
    seleccionarFecha(activeDate);
});
</script>
@endpush
