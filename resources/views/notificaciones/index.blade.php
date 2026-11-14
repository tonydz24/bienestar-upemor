@extends('layouts.dashboard')

@section('title', 'Notificaciones - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <!-- Encabezado -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Centro de Notificaciones</h1>
            <p class="text-muted mb-0">Avisos del sistema, recordatorios de bienestar, actualizaciones de citas y recompensas.</p>
        </div>
        @if ($totalNoLeidas > 0)
            <div id="pageMarkAllWrap">
                <form action="{{ route('notificaciones.marcar-todas') }}" method="POST" id="formMarcarTodasPage">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm rounded-3 d-inline-flex align-items-center gap-1.5 px-3 py-2" id="btnMarcarTodasPage">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Marcar todas como leídas</span>
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-auto-dismiss mb-4 shadow-sm" role="alert" data-auto-dismiss="4000">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- Filtros de pestañas -->
    <div class="d-flex gap-2 mb-4 border-bottom pb-2">
        <a href="{{ route('notificaciones.index', ['filtro' => 'todas']) }}"
           class="btn btn-sm rounded-pill px-3 py-1.5 {{ $filtro === 'todas' ? 'btn-primary' : 'btn-light text-muted' }}">
            Todas ({{ $totalNotificaciones }})
        </a>
        <a href="{{ route('notificaciones.index', ['filtro' => 'no_leidas']) }}"
           class="btn btn-sm rounded-pill px-3 py-1.5 {{ $filtro === 'no_leidas' ? 'btn-primary' : 'btn-light text-muted' }}">
            No leídas (<span id="tabNoLeidasCount">{{ $totalNoLeidas }}</span>)
        </a>
    </div>

    <!-- Lista de notificaciones -->
    <div class="d-flex flex-column gap-3 mb-4">
        @forelse ($notificaciones as $n)
            @php
                $esNoLeida = is_null($n->read_at);
                $data = $n->data;
                $tipo = $data['tipo'] ?? 'general';
                $titulo = $data['titulo'] ?? 'Notificación del sistema';
                $mensaje = $data['mensaje'] ?? '';
                $url = $data['url'] ?? null;
            @endphp
            <div class="card border-0 shadow-sm rounded-4 transition-all notificacion-item {{ $esNoLeida ? 'border-start border-4 border-primary es-no-leida is-unread' : 'es-leida is-read opacity-85' }}"
                 id="notif-card-{{ $n->id }}"
                 data-notification-id="{{ $n->id }}"
                 data-unread="{{ $esNoLeida ? 'true' : 'false' }}"
                 style="{{ $esNoLeida ? 'background: #f3f0ff; border-left: 4px solid #6366f1 !important;' : 'background: #ffffff; border-left: 4px solid transparent;' }}">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-start gap-3">
                        <!-- Icono por tipo -->
                        <div class="p-2.5 rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center notif-icono"
                             style="width: 44px; height: 44px;
                             @if($tipo === 'bitacora') background: #ede9fe; color: #7c3aed;
                             @elseif($tipo === 'recompensa') background: #fef3c7; color: #d97706;
                             @elseif($tipo === 'cita') background: #e0f2fe; color: #0284c7;
                             @elseif($tipo === 'propuesta') background: #ccfbf1; color: #0d9488;
                             @elseif($tipo === 'meditacion') background: #dcfce7; color: #16a34a;
                             @elseif($tipo === 'evaluacion') background: #e0e7ff; color: #4338ca;
                             @else background: #f1f5f9; color: #64748b; @endif">
                            @if($tipo === 'bitacora')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            @elseif($tipo === 'recompensa')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            @elseif($tipo === 'cita')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            @elseif($tipo === 'propuesta')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 16v-4"/>
                                    <path d="M12 8h.01"/>
                                </svg>
                            @elseif($tipo === 'meditacion')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 3a9 9 0 0 0 0 18"/>
                                    <path d="M12 7a5 5 0 0 1 5 5"/>
                                </svg>
                            @elseif($tipo === 'evaluacion')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 11l3 3L22 4"/>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                            @else
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                    <path d="M13.73 21a2 2 0 01-3.46 0"/>
                                </svg>
                            @endif
                        </div>

                        <!-- Contenido -->
                        <div class="flex-grow-1">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1 mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="notif-unread-dot {{ $esNoLeida ? '' : 'd-none' }}" title="Notificación no leída" aria-hidden="true"></span>
                                    <h6 class="{{ $esNoLeida ? 'fw-bold text-dark' : 'fw-semibold text-secondary' }} mb-0 fs-6">{{ $titulo }}</h6>
                                    @if ($esNoLeida)
                                        <span class="badge rounded-pill bg-primary notif-badge-nueva" style="font-size: 0.65rem;">No leída</span>
                                    @else
                                        <span class="badge rounded-pill bg-light text-secondary border notif-badge-leida" style="font-size: 0.65rem;">Leída</span>
                                    @endif
                                </div>
                                <span class="small text-muted" style="font-size: 0.75rem;" title="{{ $n->created_at }}">
                                    {{ $n->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <p class="small text-muted mb-2 text-break" style="line-height: 1.45;">
                                {{ $mensaje }}
                            </p>

                            <div class="d-flex align-items-center gap-2 mt-2 notif-acciones">
                                @if ($url)
                                    <a href="{{ route('notificaciones.ir', $n->id) }}" class="btn btn-sm btn-outline-primary rounded-3 px-3 py-1" style="font-size: 0.78rem;">
                                        Ver detalle
                                    </a>
                                @endif

                                @if ($esNoLeida)
                                    <form action="{{ route('notificaciones.leer', $n->id) }}" method="POST" class="d-inline form-marcar-leida" data-notif-id="{{ $n->id }}">
                                        @csrf
                                        <input type="hidden" name="stay" value="1">
                                        <input type="hidden" name="filtro" value="{{ $filtro }}">
                                        <button type="submit" class="btn btn-sm btn-light border rounded-3 px-2.5 py-1 text-muted btn-marcar-leida" style="font-size: 0.78rem;">
                                            Marcar como leída
                                        </button>
                                    </form>
                                @else
                                    <span class="small text-muted notif-leida-texto" style="font-size: 0.72rem;">Leída {{ $n->read_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                <div class="py-4">
                    <div class="p-3 rounded-circle bg-light d-inline-flex mb-3">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8">
                            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 01-3.46 0"/>
                        </svg>
                    </div>
                    <h5 class="fw-semibold text-dark mb-1">Bandeja al día</h5>
                    <p class="text-muted small mb-0">No tienes notificaciones {{ $filtro === 'no_leidas' ? 'pendientes de leer' : 'registradas' }}.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($notificaciones->hasPages())
        <div class="d-flex justify-content-center">
            {{ $notificaciones->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Manejo asíncrono para marcar una notificación individual como leída
    document.querySelectorAll('.form-marcar-leida').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const notifId = form.getAttribute('data-notif-id');
            const actionUrl = form.getAttribute('action');
            const submitBtn = form.querySelector('.btn-marcar-leida');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            }

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({})
            })
            .then(res => {
                if (!res.ok) throw new Error('Error de red');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    if (window.BienestarNotificaciones && typeof window.BienestarNotificaciones.syncAfterRead === 'function') {
                        window.BienestarNotificaciones.syncAfterRead(notifId, data.unread_count);
                    } else {
                        const card = document.getElementById('notif-card-' + notifId);
                        if (card) {
                            card.classList.remove('border-start', 'border-4', 'border-primary', 'es-no-leida', 'is-unread');
                            card.classList.add('es-leida', 'is-read', 'opacity-85');
                            card.style.background = '#ffffff';
                            card.style.borderLeft = '4px solid transparent';

                            const badgeNueva = card.querySelector('.notif-badge-nueva');
                            if (badgeNueva) {
                                badgeNueva.className = 'badge rounded-pill bg-light text-secondary border notif-badge-leida';
                                badgeNueva.textContent = 'Leída';
                            }

                            const acciones = card.querySelector('.notif-acciones');
                            if (acciones) {
                                form.remove();
                                const spanLeida = document.createElement('span');
                                spanLeida.className = 'small text-muted notif-leida-texto';
                                spanLeida.style.fontSize = '0.72rem';
                                spanLeida.textContent = 'Leída recién';
                                acciones.appendChild(spanLeida);
                            }
                        }
                    }

                    if (data.unread_count === 0) {
                        const markAllWrap = document.getElementById('pageMarkAllWrap');
                        if (markAllWrap) markAllWrap.classList.add('d-none');
                    }
                } else {
                    form.submit();
                }
            })
            .catch(() => {
                // Fallback automático a submit nativo
                form.submit();
            });
        });
    });
});
</script>
@endpush
@endsection

