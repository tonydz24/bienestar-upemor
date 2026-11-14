@extends('layouts.dashboard')

@section('title', 'Solicitudes de alumnos - Bienestar UPEMOR')

@php
    $etiquetas = [
        'TODOS' => 'Todos los estados',
        'PENDIENTE' => 'Pendiente de revisión',
        'ACTIVO' => 'Aprobada (Activo)',
        'RECHAZADO' => 'Rechazada'
    ];
@endphp

@section('content')
    <div class="admin-heading">
        <h1>Solicitudes de alumnos</h1>
        <p>Revisa las solicitudes de registro y decide si el alumno puede acceder a la plataforma.</p>
    </div>

    <section class="admin-card" aria-label="Listado de solicitudes">
        <form method="GET" action="{{ route('admin.solicitudes.index') }}" class="admin-filters" role="search">
            <div>
                <label for="estado" class="form-label">Estado</label>
                <select name="estado" id="estado" class="form-select">
                    @foreach ($estados as $opcion)
                        <option value="{{ $opcion }}" @selected($estado === $opcion)>{{ $etiquetas[$opcion] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-filters-search">
                <label for="buscar" class="form-label">Buscar por nombre o matrícula</label>
                <input type="search" name="buscar" id="buscar" class="form-control" value="{{ $buscar }}" maxlength="80" placeholder="Nombre o matrícula">
            </div>
            <div class="admin-filters-actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.solicitudes.index', ['estado' => 'TODOS']) }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Alumno</th>
                        <th scope="col">Matrícula</th>
                        <th scope="col">Correo institucional</th>
                        <th scope="col">Carrera</th>
                        <th scope="col">Registro</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitudes as $solicitud)
                        @php
                            $nombreCompleto = trim($solicitud->nombre.' '.$solicitud->apellido_paterno.' '.$solicitud->apellido_materno);
                        @endphp
                        <tr>
                            <td>{{ $nombreCompleto }}</td>
                            <td>{{ $solicitud->estudiante->matricula }}</td>
                            <td>{{ $solicitud->email }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $solicitud->estudiante->programa?->nombre ?? 'Programa Institucional' }}</div>
                                <div class="small text-muted">
                                    <span class="badge bg-light text-dark border">Plan: {{ $solicitud->estudiante->plan_estudio_asignado?->codigo ?? 'Resolución Automática' }}</span>
                                    <span class="ms-1">Gen: <strong>{{ $solicitud->estudiante->generacion }}</strong></span>
                                </div>
                                @if($solicitud->estudiante?->cuatrimestre)
                                    <div class="small text-muted mt-1">
                                        Cuatrimestre {{ $solicitud->estudiante->cuatrimestre }} &bull; Grupo {{ $solicitud->estudiante->grupo ?? 'S/G' }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $solicitud->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge admin-badge admin-badge-{{ strtolower($solicitud->estado) }}">
                                    {{ $etiquetas[$solicitud->estado] ?? $solicitud->estado }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if ($solicitud->estado === 'PENDIENTE')
                                    <div class="admin-actions">
                                        <form method="POST" action="{{ route('admin.solicitudes.aprobar', $solicitud->usuario_id) }}"
                                              data-confirm="¿Aprobar la solicitud de {{ $nombreCompleto }}?">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-primary btn-sm">Aprobar</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.solicitudes.rechazar', $solicitud->usuario_id) }}"
                                              data-confirm="¿Rechazar la solicitud de {{ $nombreCompleto }}?">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Rechazar</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted">Sin acciones</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No hay solicitudes con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($solicitudes->hasPages())
            <div class="mt-4">{{ $solicitudes->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>

    <!-- Modal Confirmar Acción en Solicitud -->
    <div class="modal fade" id="modalConfirmarSolicitud" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="modalConfirmarSolicitudTitulo">Confirmar acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-muted small mb-0" id="modalConfirmarSolicitudTexto">
                        ¿Confirmas que deseas realizar esta acción sobre la solicitud del estudiante?
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" id="btnConfirmarSolicitudAceptar">Continuar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let pendingFormToSubmit = null;
            const modalEl = document.getElementById('modalConfirmarSolicitud');
            const modalTexto = document.getElementById('modalConfirmarSolicitudTexto');
            const btnAceptar = document.getElementById('btnConfirmarSolicitudAceptar');
            const bsModal = modalEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

            document.querySelectorAll('form[data-confirm]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (form.dataset.confirmed === 'true') {
                        return;
                    }
                    event.preventDefault();
                    pendingFormToSubmit = form;
                    if (modalTexto && form.dataset.confirm) {
                        modalTexto.textContent = form.dataset.confirm;
                    }
                    if (bsModal) {
                        bsModal.show();
                    } else {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            });

            btnAceptar?.addEventListener('click', function() {
                if (pendingFormToSubmit) {
                    pendingFormToSubmit.dataset.confirmed = 'true';
                    pendingFormToSubmit.submit();
                }
            });
        });
    </script>
@endpush
