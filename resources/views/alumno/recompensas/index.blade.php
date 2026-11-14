@extends('layouts.dashboard')

@section('title', 'Puntos y Recompensas - Bienestar UPEMOR')

@push('styles')
<style>
    .recompensa-card {
        background: #ffffff;
        border: 1px solid rgba(139, 92, 246, 0.16);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 18px -2px rgba(109, 40, 217, 0.06);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .recompensa-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px -2px rgba(109, 40, 217, 0.12);
    }
    .badge-recompensa-solicitada {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        font-weight: 600;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
    }
    .badge-recompensa-aprobada {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        font-weight: 600;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
    }
    .badge-recompensa-rechazada {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
        font-weight: 600;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    <!-- Tarjeta Destacada de Saldo de Puntos (Elevated Card) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%); border-left: 5px solid #7c3aed !important;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="badge rounded-pill px-3 py-1 small fw-semibold mb-2" style="background: #e9d5ff; color: #581c87;">
                        🌟 Reconocimiento y Hábitos Saludables
                    </span>
                    <h1 class="h3 fw-bold mb-1" style="color: #4c1d95;">Puntos y Recompensas Académicas</h1>
                    <p class="mb-0 text-muted" style="max-width: 560px;">
                        Tienes <strong class="text-dark">{{ $saldoPuntos }} puntos acumulados</strong> para canjear beneficios académicos autorizados por tus profesores como reconocimiento a tu constancia en el seguimiento emocional y hábitos de bienestar.
                    </p>
                </div>
                <div class="bg-white p-3 px-4 rounded-4 shadow-sm border border-purple-subtle text-center text-md-end" style="min-width: 200px;">
                    <span class="d-block small text-muted text-uppercase fw-semibold" style="letter-spacing: 0.05em; font-size: 0.75rem;">Mis puntos disponibles</span>
                    <span class="display-6 fw-bold" style="color: #6d28d9;">{{ $saldoPuntos }} <span class="fs-6 fw-normal text-muted">pts</span></span>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-xs border-0 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-success flex-shrink-0">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-xs border-0 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-danger flex-shrink-0">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- SECCIÓN 1: Recompensas Disponibles para sus Materias -->
    <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="color: #7c3aed;">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Beneficios habilitados en tus asignaturas</span>
            </h2>
            <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                {{ $recompensasDisponibles->count() }} activa(s)
            </span>
        </div>

        @if ($recompensasDisponibles->isEmpty())
            <div class="recompensa-card text-center py-5">
                <div class="p-3 rounded-circle bg-light d-inline-flex mb-3" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <h3 class="h6 fw-bold text-dark mb-1">Aún no hay recompensas activas en tus grupos</h3>
                <p class="text-muted small mb-0 max-w-md mx-auto" style="max-width: 440px;">
                    Cuando tus profesores activen beneficios en sus asignaturas correspondientes a este periodo, aparecerán aquí para que puedas canjear tus puntos.
                </p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($recompensasDisponibles as $rec)
                    <div class="col-md-6 col-lg-4">
                        <div class="recompensa-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 small fw-semibold">
                                        {{ $rec->recompensaCatalogo->categoria }}
                                    </span>
                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                        {{ $rec->cupo_disponible }} cupos libres
                                    </span>
                                </div>

                                <h3 class="h5 fw-bold text-dark mb-1">{{ $rec->recompensaCatalogo->nombre }}</h3>
                                <p class="text-muted small mb-3">{{ $rec->recompensaCatalogo->descripcion }}</p>

                                <div class="p-3 rounded-3 bg-light small mb-3 border">
                                    <div class="d-flex justify-content-between text-muted mb-1 gap-2">
                                        <span>Asignatura:</span>
                                        <span class="text-dark fw-semibold text-end">
                                            {{ $rec->asignacion->asignatura->nombre }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-1 gap-2">
                                        <span>Grupo y Nivel:</span>
                                        <span class="text-dark text-end">
                                            {{ $rec->asignacion->asignatura->cuatrimestre }}.º cuatrimestre &bull; Grupo {{ $rec->asignacion->grupo->nombre ?? 'A' }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-1 gap-2">
                                        <span>Profesor(a):</span>
                                        <span class="text-dark text-end">{{ $rec->asignacion->docenteUsuario->nombre }} {{ $rec->asignacion->docenteUsuario->apellido_paterno }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted gap-2">
                                        <span>Vigencia:</span>
                                        <span class="text-dark text-end">{{ \Carbon\Carbon::parse($rec->vigencia_fin)->format('d/m/Y') }}</span>
                                    </div>
                                </div>

                                @if ($rec->condiciones_docente)
                                    <div class="mb-3 small text-muted fst-italic">
                                        Nota del docente: "{{ $rec->condiciones_docente }}"
                                    </div>
                                @endif
                            </div>

                            <div class="pt-3 border-top d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <span class="fs-5 fw-bold" style="color: #6d28d9;">{{ $rec->costo_puntos }}</span>
                                    <span class="small text-muted">pts</span>
                                </div>

                                @if ($saldoPuntos >= $rec->costo_puntos && $rec->tieneCupo())
                                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold" style="background-color: #7c3aed; border-color: #7c3aed;" data-bs-toggle="modal" data-bs-target="#modalCanjear-{{ $rec->activacion_id }}">
                                        Solicitar canje
                                    </button>
                                @elseif ($saldoPuntos < $rec->costo_puntos)
                                    @php $faltan = $rec->costo_puntos - $saldoPuntos; @endphp
                                    <div class="text-end">
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 disabled" disabled title="Puntos insuficientes">
                                            Puntos insuficientes
                                        </button>
                                        <div class="small text-danger mt-1" style="font-size: 0.72rem;">
                                            Te faltan {{ $faltan }} pts (tienes {{ $saldoPuntos }} pts, costo: {{ $rec->costo_puntos }} pts)
                                        </div>
                                    </div>
                                @else
                                    <div class="text-end">
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 disabled" disabled>
                                            Cupo agotado
                                        </button>
                                        <div class="small text-muted mt-1" style="font-size: 0.72rem;">0 lugares disponibles</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Modal Confirmación Canje -->
                    <div class="modal fade" id="modalCanjear-{{ $rec->activacion_id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('alumno.recompensas.canjear', $rec->activacion_id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Canjear {{ $rec->recompensaCatalogo->nombre }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <p class="text-muted small">
                                            Se descontarán <strong>{{ $rec->costo_puntos }} puntos</strong> de tu saldo disponible (saldo actual: {{ $saldoPuntos }} pts). La solicitud será enviada al profesor <strong>{{ $rec->asignacion->docenteUsuario->nombre }}</strong> para su autorización.
                                        </p>
                                        <div class="mb-3">
                                            <label for="motivo-canje-{{ $rec->activacion_id }}" class="form-label small fw-semibold">Nota o motivo para el profesor (opcional):</label>
                                            <textarea name="motivo" id="motivo-canje-{{ $rec->activacion_id }}" rows="2" class="form-control form-control-sm rounded-3" placeholder="Ej. Solicito este beneficio para la entrega de la práctica del viernes..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3" style="background-color: #7c3aed; border-color: #7c3aed;">Confirmar canje</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- SECCIÓN 2: Beneficios Adquiridos Listos para Usar -->
    @if ($canjesActivos->isNotEmpty())
        <div class="mb-5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-0">Tus beneficios digitales adquiridos</h2>
                    <span class="text-muted small">Canjeados y listos para ser presentados ante tu profesor</span>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 small fw-bold">
                    {{ $canjesActivos->count() }} pendiente(s) de uso
                </span>
            </div>

            <div class="row g-3">
                @foreach ($canjesActivos as $ca)
                    <div class="col-md-6">
                        <div class="recompensa-card border-primary border-opacity-25 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="{{ $ca->estado_badge_class }}">
                                    {{ $ca->estado_label }}
                                </span>
                                <span class="small text-muted fw-semibold">{{ $ca->puntos_canjeados }} pts descontados</span>
                            </div>

                            <h3 class="h6 fw-bold text-dark mb-1">{{ $ca->activacion->recompensaCatalogo->nombre }}</h3>
                            <p class="text-muted small mb-2">
                                Materia: <strong>{{ $ca->activacion->asignacion->asignatura->nombre }}</strong> (Grupo {{ $ca->activacion->asignacion->grupo->nombre ?? 'A' }}) &bull; Profesor: {{ $ca->activacion->asignacion->docenteUsuario->nombre }} {{ $ca->activacion->asignacion->docenteUsuario->apellido_paterno }}
                            </p>

                            @if ($ca->codigo_beneficio)
                                <div class="p-2.5 px-3 rounded-3 bg-light border d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <span class="d-block text-muted" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em;">Código de cupón digital</span>
                                        <code class="fs-6 fw-bold" style="color: #6d28d9;">{{ $ca->codigo_beneficio }}</code>
                                    </div>
                                    <span class="badge bg-white text-secondary border rounded-pill px-2 py-1 small" style="font-size: 0.7rem;">
                                        Presentar al docente
                                    </span>
                                </div>
                            @endif

                            @if ($ca->condiciones_snapshot)
                                <div class="small text-muted fst-italic mb-2" style="font-size: 0.8rem;">
                                    Condición de uso: "{{ $ca->condiciones_snapshot }}"
                                </div>
                            @endif

                            <div class="d-flex justify-content-end pt-2 border-top">
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" style="font-size: 0.8rem;" data-bs-toggle="modal" data-bs-target="#modalCancelarCanje{{ $ca->canje_id }}">
                                    Cancelar beneficio y reembolsar puntos
                                </button>

                                <!-- Modal Cancelar Canje -->
                                <div class="modal fade" id="modalCancelarCanje{{ $ca->canje_id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold text-danger">Cancelar canje de beneficio</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <p class="text-muted small mb-0">
                                                    ¿Seguro que deseas desistir de este beneficio? Tus <strong>{{ $ca->puntos_canjeados }} puntos</strong> serán devueltos íntegramente a tu saldo para que puedas utilizarlos en otra recompensa.
                                                </p>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Regresar</button>
                                                <form action="{{ route('alumno.recompensas.cancelar', $ca->canje_id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">
                                                        Sí, cancelar y reembolsar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- SECCIÓN 3: Historial de Canjes -->
    <div class="mb-4">
        <h2 class="h5 fw-bold text-dark mb-3">Historial de canjes anteriores</h2>
        @if ($canjesHistorial->isEmpty())
            <div class="recompensa-card text-center py-4">
                <p class="text-muted small mb-0">No cuentas con registros en el historial previo.</p>
            </div>
        @else
            <div class="recompensa-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-4 py-3 small text-uppercase text-muted fw-semibold">Beneficio</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Materia y Grupo</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Puntos</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Fecha</th>
                                <th class="border-0 pe-4 py-3 small text-uppercase text-muted fw-semibold text-end">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($canjesHistorial as $ch)
                                <tr>
                                    <td class="ps-4 py-3 small fw-bold text-dark">
                                        {{ $ch->activacion->recompensaCatalogo->nombre }}
                                    </td>
                                    <td class="py-3 small text-muted">
                                        {{ $ch->activacion->asignacion->asignatura->nombre }}
                                        <span class="d-block text-muted" style="font-size: 0.72rem;">
                                            Grupo {{ $ch->activacion->asignacion->grupo->nombre ?? 'A' }} &bull; {{ $ch->activacion->asignacion->asignatura->clave }}
                                        </span>
                                    </td>
                                    <td class="py-3 small text-dark">
                                        {{ $ch->puntos_canjeados }} pts
                                    </td>
                                    <td class="py-3 small text-muted">
                                        {{ $ch->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="pe-4 py-3 text-end">
                                        <span class="{{ $ch->estado_badge_class }}">
                                            {{ $ch->estado_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    <!-- SECCIÓN 4: Libro Mayor de Movimientos de Puntos (Auditoría FN.5 / FN.11) -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Libro mayor de puntos de bienestar
                </h2>
                <p class="text-muted small mb-0">Registro inmutable de créditos y débitos con tope ordinario diario de 5 puntos y principio de neutralidad ética.</p>
            </div>
            <span class="badge bg-light text-dark border">Auditoría transparente</span>
        </div>

        @if (!isset($historialPuntos) || $historialPuntos->isEmpty())
            <div class="recompensa-card text-center py-4">
                <p class="text-muted small mb-0">Aún no se registran movimientos de puntos en tu cuenta. Completa tu registro emocional diario (+1 pt), prácticas de meditación (+2 pts) o cuestionarios para comenzar a acumular.</p>
            </div>
        @else
            <div class="recompensa-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-4 py-3 small text-uppercase text-muted fw-semibold">Fecha</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Concepto / Actividad</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold">Tipo</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold text-center">Variación</th>
                                <th class="border-0 py-3 small text-uppercase text-muted fw-semibold text-center">Saldo Anterior</th>
                                <th class="border-0 pe-4 py-3 small text-uppercase text-muted fw-semibold text-end">Saldo Resultante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($historialPuntos as $mov)
                                <tr>
                                    <td class="ps-4 py-3 small text-muted">
                                        {{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y') }}
                                        <span class="d-block text-muted" style="font-size: 0.72rem;">{{ $mov->created_at->format('H:i') }} hrs</span>
                                    </td>
                                    <td class="py-3 small text-dark fw-semibold">
                                        {{ $mov->descripcion }}
                                    </td>
                                    <td class="py-3 small">
                                        @if($mov->tipo === 'EMOCION_DIARIA')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Emoción diaria (+1)</span>
                                        @elseif($mov->tipo === 'MEDITACION')
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Meditación (+2)</span>
                                        @elseif($mov->tipo === 'EVALUACION')
                                            <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">Tamizaje (+5)</span>
                                        @elseif($mov->tipo === 'ENCUESTA')
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Encuesta (+1)</span>
                                        @elseif($mov->tipo === 'CANJE_RECOMPENSA')
                                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">Canje beneficio</span>
                                        @elseif($mov->tipo === 'REEMBOLSO_CANJE')
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Reembolso canje</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ $mov->tipo }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 small text-center fw-bold {{ $mov->puntos >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $mov->puntos > 0 ? '+' . $mov->puntos : $mov->puntos }} pts
                                    </td>
                                    <td class="py-3 small text-center text-muted">
                                        {{ $mov->saldo_anterior }} pts
                                    </td>
                                    <td class="pe-4 py-3 text-end fw-bold text-dark">
                                        {{ $mov->saldo_nuevo }} pts
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (method_exists($historialPuntos, 'hasPages') && $historialPuntos->hasPages())
                    <div class="p-3 border-top bg-light">
                        {{ $historialPuntos->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
