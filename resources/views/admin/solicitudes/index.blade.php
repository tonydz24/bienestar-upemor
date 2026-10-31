@extends('layouts.admin')

@section('title', 'Solicitudes de alumnos - Bienestar UPEMOR')

@php
    $etiquetas = ['PENDIENTE' => 'Pendiente', 'ACTIVO' => 'Aprobada', 'RECHAZADO' => 'Rechazada'];
@endphp

@section('content')
    <div class="admin-heading">
        <h1>Solicitudes de alumnos</h1>
        <p>Revisa las solicitudes de registro y decide si el alumno puede acceder a la plataforma.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

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
                <a href="{{ route('admin.solicitudes.index') }}" class="btn btn-outline-secondary">Limpiar</a>
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
                            <td>{{ $solicitud->estudiante->programa?->nombre }}</td>
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
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endpush
