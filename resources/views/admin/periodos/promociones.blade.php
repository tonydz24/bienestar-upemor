@extends('layouts.dashboard')

@section('title', 'Promoción Administrativa de Estudiantes - Bienestar UPEMOR')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.periodos.index') }}" class="text-decoration-none">Periodos Académicos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Promociones - {{ $periodo->clave }}</li>
            </ol>
        </nav>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: var(--bienestar-text-primary);">Promoción Administrativa de Estudiantes</h1>
                <p class="text-muted mb-0">Periodo de origen: <strong>{{ $periodo->clave }} - {{ $periodo->nombre }}</strong> ({{ $periodo->inscripciones_count }} inscripciones activas).</p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show portal-auto-dismiss mb-4" role="alert" data-auto-dismiss="4000">
            <strong>Éxito:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h6 class="fw-bold mb-2">Por favor corrige los siguientes errores:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <form action="{{ route('admin.periodos.promociones.ejecutar', $periodo->periodo_id) }}" method="POST" id="formPromociones">
        @csrf
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="periodo_destino_id" class="form-label fw-bold">Periodo Académico de Destino <span class="text-danger">*</span></label>
                        <select name="periodo_destino_id" id="periodo_destino_id" class="form-select" required>
                            <option value="">-- Selecciona el periodo siguiente --</option>
                            @foreach($periodosSiguientes as $siguiente)
                                <option value="{{ $siguiente->periodo_id }}">
                                    {{ $siguiente->clave }} ({{ $siguiente->nombre }}) — Inicia: {{ $siguiente->fecha_inicio?->format('d/m/Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button type="button" class="btn btn-primary px-4" id="btnAbrirModalPromocion">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                <polyline points="17 11 12 6 7 11"></polyline>
                                <polyline points="17 18 12 13 7 18"></polyline>
                            </svg>
                            Ejecutar Promoción de Seleccionados
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom border-light d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="checkTodos">
                    <label class="form-check-label fw-semibold text-secondary" for="checkTodos">Seleccionar todos los estudiantes de esta página</label>
                </div>
                <span class="text-muted small">Mostrando {{ $inscripcionesActivas->count() }} de {{ $inscripcionesActivas->total() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4" style="width: 40px;"></th>
                            <th scope="col">Matrícula</th>
                            <th scope="col">Estudiante</th>
                            <th scope="col">Programa</th>
                            <th scope="col" class="text-center">Cuatri Actual</th>
                            <th scope="col" class="text-center">Cuatri Destino</th>
                            <th scope="col">Grupo Actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inscripcionesActivas as $inscripcion)
                            @php
                                $u = $inscripcion->estudianteUsuario;
                                $est = $inscripcion->estudiante;
                                $cuatriActual = $est?->cuatrimestre ?? 1;
                                $cuatriDestino = min(10, $cuatriActual + 1);
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <input class="form-check-input estudiante-check" type="checkbox" name="estudiantes_ids[]" value="{{ $inscripcion->estudiante_id }}">
                                </td>
                                <td class="font-monospace fw-bold">{{ $est?->matricula ?? 'N/D' }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $u?->nombre }} {{ $u?->apellido_paterno }} {{ $u?->apellido_materno }}</div>
                                    <small class="text-muted">{{ $u?->email }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $est?->programa?->clave }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary px-2 py-1">{{ $cuatriActual }}°</span>
                                </td>
                                <td class="text-center">
                                    @if($cuatriActual < 10)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                            {{ $cuatriDestino }}°
                                        </span>
                                    @else
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Egresado</span>
                                    @endif
                                </td>
                                <td>{{ $inscripcion->grupo?->clave ?? 'Sin grupo' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No hay estudiantes activos en este periodo para promover.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($inscripcionesActivas->hasPages())
                <div class="card-footer bg-white border-top border-light py-3 px-4">
                    {{ $inscripcionesActivas->links() }}
                </div>
            @endif
        </div>
    </form>

    <!-- Modal Confirmación Promoción -->
    <div class="modal fade" id="modalConfirmarPromocion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Confirmar promoción académica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-muted small mb-0">
                        ¿Confirmas la promoción de los estudiantes seleccionados al cuatrimestre superior? Esta acción concluirá su ciclo en el periodo actual y registrará su nueva inscripción académica.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" id="btnConfirmarPromocionSubmit">
                        Sí, ejecutar promoción
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkTodos = document.getElementById('checkTodos');
        const checks = document.querySelectorAll('.estudiante-check');
        const btnAbrir = document.getElementById('btnAbrirModalPromocion');
        const btnConfirmar = document.getElementById('btnConfirmarPromocionSubmit');
        const form = document.getElementById('formPromociones');
        const selectDestino = document.getElementById('periodo_destino_id');

        checkTodos?.addEventListener('change', function() {
            checks.forEach(c => c.checked = checkTodos.checked);
        });

        btnAbrir?.addEventListener('click', function() {
            if (!selectDestino.value) {
                selectDestino.focus();
                selectDestino.reportValidity();
                return;
            }
            const seleccionados = document.querySelectorAll('.estudiante-check:checked');
            if (seleccionados.length === 0) {
                if (checks.length > 0) checks[0].focus();
                let warn = document.getElementById('promocionAlertaSeleccion');
                if (!warn) {
                    warn = document.createElement('div');
                    warn.id = 'promocionAlertaSeleccion';
                    warn.className = 'alert alert-warning py-2 px-3 small rounded-3 mt-3 shadow-sm';
                    warn.textContent = 'Debes seleccionar al menos un estudiante de la lista inferior para ejecutar la promoción.';
                    form.parentNode.insertBefore(warn, form);
                }
                warn.classList.remove('d-none');
                return;
            }
            const modalEl = document.getElementById('modalConfirmarPromocion');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });

        btnConfirmar?.addEventListener('click', function() {
            form.submit();
        });
    });
</script>
@endsection

