@extends('layouts.dashboard')

@section('title', 'Inicio - Bienestar UPEMOR')

@section('content')
    <!-- Encabezado de bienvenida personalizado -->
    <div class="mb-4">
        <h1 class="portal-greeting-title">¡Hola, {{ $alumno->nombre }}! <span aria-hidden="true">👋</span></h1>
        <p class="portal-greeting-sub mb-2">Este es tu espacio para cuidar tu bienestar emocional.</p>
        @if($alumno->estudiante)
            <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                    <i class="bi bi-mortarboard me-1 text-primary"></i>{{ $alumno->estudiante->programa?->nombre ?? 'UPEMOR' }}
                </span>
                <span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle rounded-pill px-2.5 py-1">
                    Cuatrimestre {{ $alumno->estudiante->cuatrimestre }} - Grupo {{ $alumno->estudiante->grupo }}
                </span>
                <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1">
                    Matrícula: {{ $alumno->estudiante->matricula }}
                </span>
            </div>
        @endif
    </div>

    <!-- Fila de 4 tarjetas métricas / resúmenes -->
    <div class="row g-3 g-xl-4 mb-4">
        <!-- 1. Estado actual -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-summary-card h-100 d-flex flex-column justify-content-between">
                <div class="metric-card-top">
                    <div class="metric-icon-circle metric-circle-green" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 4.44-2.04z"/>
                            <path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-4.44-2.04z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="metric-label">Estado actual</span>
                        @if ($registroHoy)
                            <div class="metric-value metric-value-green d-flex align-items-center gap-2">
                                <x-emocion-esfera :emocion="$registroHoy->emocion" size="sm" />
                                <span>{{ $registroHoy->emocion->nombre }}</span>
                            </div>
                        @elseif (! ($bitacoraDisponible ?? true))
                            <div class="metric-value text-muted fs-6">Módulo no disponible</div>
                        @else
                            <div class="metric-value metric-value-green">Sin evaluar</div>
                        @endif
                    </div>
                </div>
                <div>
                    @if (! ($bitacoraDisponible ?? true))
                        <span class="text-muted small">Próximamente disponible</span>
                    @else
                        <a href="{{ route('alumno.evaluaciones') }}" class="metric-link text-decoration-none">
                            <span>Explorar evaluaciones</span>
                            <span aria-hidden="true">&rsaquo;</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. Bitácora Emocional -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-summary-card h-100 d-flex flex-column justify-content-between">
                <div class="metric-card-top">
                    <div class="metric-icon-circle metric-circle-orange" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <span class="metric-label">Bitácora Personal</span>
                        <div class="metric-value metric-value-orange">{{ $totalRegistrosEmocionales }} {{ $totalRegistrosEmocionales === 1 ? 'registro' : 'registros' }}</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('alumno.bitacora.index') }}" class="metric-link text-decoration-none">
                        <span>Abrir bitácora diaria</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. Evaluaciones Psicológicas -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-summary-card h-100 d-flex flex-column justify-content-between">
                <div class="metric-card-top">
                    <div class="metric-icon-circle metric-circle-blue" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                    </div>
                    <div>
                        <span class="metric-label">Evaluaciones</span>
                        <div class="metric-value metric-value-blue">{{ $evaluacionesDisponiblesConteo }} disponibles</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('alumno.evaluaciones') }}" class="metric-link text-decoration-none">
                        <span>Explorar cuestionarios</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Puntos y Beneficios Digitales -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-summary-card h-100 d-flex flex-column justify-content-between">
                <div class="metric-card-top">
                    <div class="metric-icon-circle metric-circle-purple" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </div>
                    <div>
                        <span class="metric-label">Puntos bienestar</span>
                        <div class="metric-value metric-value-purple">{{ $alumno->estudiante?->saldo_puntos ?? ($saldoPuntos ?? 0) }} pts</div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('alumno.recompensas.index') }}" class="metric-link text-decoration-none">
                        <span>Canjear recompensas</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                    @if($beneficiosPendientesConteo > 0)
                        <span class="badge bg-purple-subtle text-primary border border-primary-subtle rounded-pill small" title="Cupones digitales activos para presentar a docentes">
                            {{ $beneficiosPendientesConteo }} cupón(es)
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Banner de Carga Académica Institucional -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2.5 rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-mortarboard fs-5"></i>
                </div>
                <div>
                    <h2 class="h6 fw-bold text-dark mb-0">Carga Académica y Horarios</h2>
                    <p class="text-muted small mb-0">
                        @if(($materiasInscritasConteo ?? 0) > 0)
                            Tienes <strong>{{ $materiasInscritasConteo }} materia(s)</strong> inscrita(s) y autorizada(s) para este periodo.
                        @else
                            Consulta tus asignaturas asignadas a tu grupo e inscríbete a tus clases.
                        @endif
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ route('alumno.academico.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold text-nowrap d-inline-flex align-items-center gap-1">
                    <i class="bi bi-book me-1"></i> Ver mis materias &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Fila de 2 tarjetas principales inferiores -->
    <div class="row g-4 mb-4">
        <!-- Próxima cita -->
        <div class="col-lg-6">
            <div class="dashboard-section-card h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="dashboard-card-header d-flex justify-content-between align-items-center mb-3">
                        <h2 class="dashboard-card-title mb-0">Próxima cita</h2>
                        <a href="{{ route('alumno.citas.index') }}" class="small text-primary text-decoration-none fw-semibold">Ver mis citas &rsaquo;</a>
                    </div>

                    @if($proximaCita)
                        <div class="appointment-item-box mb-3">
                            <div class="appointment-icon-box" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h3 class="h6 mb-0 fw-bold text-dark">
                                        {{ $proximaCita->profesional?->usuario?->nombre }} {{ $proximaCita->profesional?->usuario?->apellido_paterno }}
                                    </h3>
                                    @if($proximaCita->estado === 'CONFIRMADA')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Confirmada</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Solicitada</span>
                                    @endif
                                </div>
                                <p class="text-muted small mb-0">{{ $proximaCita->profesional?->cargo ?? 'Orientación y Acompañamiento' }}</p>
                            </div>
                        </div>

                        <div class="appointment-meta mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2 text-dark small">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                </svg>
                                <span class="fw-semibold">{{ $proximaCita->fecha_cita?->translatedFormat('l d \d\e F, Y') }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>{{ substr($proximaCita->hora_inicio, 0, 5) }} a {{ substr($proximaCita->hora_fin, 0, 5) }} hrs &bull; Modalidad {{ strtolower($proximaCita->modalidad) }}</span>
                            </div>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('alumno.citas.show', $proximaCita->cita_id) }}" class="btn btn-portal-primary w-100">
                                Ver detalles de la cita
                            </a>
                        </div>
                    @else
                        <div class="appointment-item-box mb-3">
                            <div class="appointment-icon-box" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="h6 mb-1 fw-bold text-dark">Acompañamiento individual</h3>
                                <p class="text-muted small mb-0">Atención personalizada y confidencial con el equipo de orientación de UPEMOR.</p>
                            </div>
                        </div>

                        <div class="appointment-meta mb-3">
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>No tienes citas programadas en este momento.</span>
                            </div>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('alumno.citas.create') }}" class="btn btn-portal-primary w-100">
                                Agendar cita
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recomendado para ti -->
        <div class="col-lg-6">
            <div class="dashboard-section-card h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="dashboard-card-header">
                        <h2 class="dashboard-card-title">Recomendado para ti</h2>
                        <span class="dashboard-card-link text-muted">Recursos preventivos</span>
                    </div>

                    <div class="recommended-body mb-3">
                        <div>
                            <span class="recommended-pill">Técnicas de relajación</span>
                            <h3 class="h5 fw-bold text-dark mb-2">Reduce la ansiedad en minutos</h3>
                            <p class="text-muted small mb-0">
                                Ejercicios breves de respiración consciente y relajación guiada, diseñados para acompañarte en periodos de sobrecarga académica o estrés.
                            </p>
                        </div>

                        <div class="d-none d-sm-block flex-shrink-0" aria-hidden="true">
                            <svg viewBox="0 0 120 120" width="100" height="100" fill="none">
                                <circle cx="60" cy="60" r="50" fill="#f5efff" />
                                <path d="M40 85C40 85 46 78 60 78C74 78 80 85 80 85" stroke="#7c3aed" stroke-width="4" stroke-linecap="round"/>
                                <path d="M60 48V78M48 62L60 68L72 62" stroke="#5c3be8" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="60" cy="38" r="10" fill="#ffb8b8"/>
                                <path d="M52 38C52 30 68 30 68 38" fill="#181c2e"/>
                                <circle cx="36" cy="80" r="4" fill="#a78bfa"/>
                                <circle cx="84" cy="80" r="4" fill="#a78bfa"/>
                                <path d="M28 50C28 42 38 46 38 46C38 46 42 56 34 56C28 56 28 50 28 50Z" fill="#a7f3d0"/>
                                <path d="M92 50C92 42 82 46 82 46C82 46 78 56 86 56C92 56 92 50 92 50Z" fill="#a7f3d0"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-primary w-100 rounded-pill fw-semibold">
                        <i class="bi bi-flower1 me-1"></i> Explorar ejercicios de relajación guiada
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner institucional de apoyo respetuoso y no diagnóstico -->
    <div class="portal-guidance-banner">
        <div class="portal-guidance-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
        </div>
        <div>
            <h3 class="h6 fw-bold mb-1 text-dark">Espacio seguro e institucional</h3>
            <p class="text-muted small mb-0">
                Bienestar UPEMOR tiene fines de acompañamiento preventivo y psicoeducativo. La información que compartas es confidencial y no constituye un diagnóstico clínico automatizado. Si necesitas asistencia emocional prioritaria, acércate a los servicios de orientación estudiantil de la universidad.
            </p>
        </div>
    </div>
@endsection
