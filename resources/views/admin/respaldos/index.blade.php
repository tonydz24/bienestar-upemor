@extends('layouts.dashboard')

@section('title', 'Respaldos de Base de Datos')

@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb y Cabecera --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Respaldos de Base de Datos</li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Respaldos de Base de Datos</h1>
            <p class="text-muted mb-0">Gestión de copias de seguridad de la base de datos institucional en almacenamiento seguro y protegido.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGenerarRespaldo">
                <i class="bi bi-cloud-arrow-down-fill me-1"></i> Generar Respaldo Ahora
            </button>
        </div>
    </div>

    @include('components.portal-alerts')

    {{-- Detalle Completo de Verificación de Integridad (Incremento 13) --}}
    @if(session('verificacion_detalle'))
        @php $vd = session('verificacion_detalle'); @endphp
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 {{ ($vd['valido'] ?? false) ? 'border-success' : 'border-danger' }}" style="background: {{ ($vd['valido'] ?? false) ? '#f0fdf4' : '#fef2f2' }};">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle p-2 {{ ($vd['valido'] ?? false) ? 'bg-success text-white' : 'bg-danger text-white' }}">
                            <i class="bi {{ ($vd['valido'] ?? false) ? 'bi-shield-check' : 'bi-shield-x' }} fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Resultado de Verificación Técnica de Integridad</h5>
                            <span class="small text-muted font-monospace">{{ $vd['nombre'] ?? 'Archivo' }}</span>
                        </div>
                    </div>
                    <span class="badge {{ ($vd['valido'] ?? false) ? 'bg-success' : 'bg-danger' }} px-3 py-2 rounded-pill shadow-sm">
                        {{ ($vd['valido'] ?? false) ? 'INTEGRIDAD COMPROBADA (100% VÁLIDO)' : 'FALLO EN VERIFICACIÓN' }}
                    </span>
                </div>

                <div class="row g-3 small mb-3">
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted d-block">Fecha y Hora (24h):</span>
                        <strong class="text-dark">{{ $vd['fecha_hora_24h'] ?? now()->format('d/m/Y H:i:s') }}</strong>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted d-block">Tamaño del archivo:</span>
                        <strong class="text-dark">{{ $vd['tamano_formateado'] ?? 'N/A' }} ({{ number_format($vd['tamano_bytes'] ?? 0) }} bytes)</strong>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted d-block">Tablas en el Dump:</span>
                        <strong class="text-dark">{{ $vd['total_tablas'] ?? 0 }} tablas detectadas</strong>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted d-block">Bloques de Datos:</span>
                        <strong class="text-dark">{{ number_format($vd['total_inserts'] ?? 0) }} sentencias INSERT</strong>
                    </div>
                </div>

                <div class="p-2.5 bg-white rounded-3 border small mb-3">
                    <span class="text-muted d-block mb-1"><strong>Firma Criptográfica SHA-256:</strong></span>
                    <code class="text-break user-select-all" style="color: #4C1D95; font-size: 0.82rem;">{{ $vd['sha256'] ?? 'N/A' }}</code>
                </div>

                @if(!empty($vd['advertencias']) && count($vd['advertencias']) > 0)
                    <div class="alert alert-light border border-warning-subtle py-2 px-3 rounded-3 small mb-0 text-dark">
                        <strong class="text-warning-emphasis"><i class="bi bi-info-circle-fill me-1"></i>Observaciones técnicas de consistencia:</strong>
                        <ul class="mb-0 mt-1 ps-3 text-muted">
                            @foreach($vd['advertencias'] as $adv)
                                <li>{{ $adv }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Tarjetas KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-database-check fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Respaldos Almacenados</div>
                        <div class="fs-4 fw-bold text-dark">{{ count($respaldos) }} copias</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-clock-history fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Último Respaldo Generado</div>
                        <div class="fs-5 fw-bold text-dark">
                            {{ !empty($respaldos) ? $respaldos[0]['fecha_modificacion'] : 'Sin respaldos' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Ubicación Segura</div>
                        <div class="small fw-semibold text-dark text-truncate" style="max-width: 220px;">
                            storage/app/backups/
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de Respaldos --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0"><i class="bi bi-folder2-open text-primary me-2"></i>Archivos de Respaldo Disponibles</h6>
                <span class="small text-muted">Copias de seguridad SQL con firma criptográfica SHA-256 e integridad verificada</span>
            </div>
            <span class="badge bg-light text-muted border">Extensión .SQL</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-3">Nombre del Archivo</th>
                        <th class="text-center">Tamaño</th>
                        <th class="text-center">Fecha de Generación</th>
                        <th>Firma SHA-256</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($respaldos as $item)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-code text-primary fs-5"></i>
                                    <div>
                                        <span class="fw-medium text-dark font-monospace">{{ $item['nombre'] }}</span>
                                        <div class="small text-muted" style="font-size: 0.72rem;">Base institucional UPEMOR</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center small fw-semibold">{{ $item['tamano_formateado'] }}</td>
                            <td class="text-center small">{{ $item['fecha_modificacion'] }}</td>
                            <td>
                                @if(!empty($item['sha256']))
                                    <div class="d-flex align-items-center gap-1 font-monospace small" style="font-size: 0.75rem;">
                                        <span class="text-truncate d-inline-block text-muted" style="max-width: 140px;" title="{{ $item['sha256'] }}">
                                            {{ substr($item['sha256'], 0, 16) }}...
                                        </span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-primary" onclick="navigator.clipboard.writeText('{{ $item['sha256'] }}'); const icon = this.querySelector('i'); icon.className='bi bi-check-lg text-success'; setTimeout(() => { icon.className='bi bi-clipboard'; }, 2000);" title="Copiar hash SHA-256 completo">
                                            <i class="bi bi-clipboard" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted small">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex align-items-center gap-1">
                                    {{-- Botón Descargar --}}
                                    <a href="{{ route('admin.respaldos.descargar', $item['nombre']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="min-height: 36px; display: inline-flex; align-items: center;" title="Descargar copia SQL a tu equipo">
                                        <i class="bi bi-download me-1"></i> Descargar
                                    </a>

                                    {{-- Botón Verificar Integridad --}}
                                    <form action="{{ route('admin.respaldos.verificar', $item['nombre']) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-info rounded-pill px-3" style="min-height: 36px; display: inline-flex; align-items: center;" title="Comprobar sintaxis y firma SHA-256 del archivo">
                                            <i class="bi bi-shield-check me-1"></i> Verificar
                                        </button>
                                    </form>

                                    {{-- Botón Solicitar Recuperación / Restauración Segura --}}
                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 text-dark fw-semibold" style="min-height: 36px; display: inline-flex; align-items: center;" title="Restaurar base de datos a este punto en el tiempo" data-bs-toggle="modal" data-bs-target="#modalRestaurarRespaldo_{{ $loop->index }}">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar
                                    </button>

                                    {{-- Botón Eliminar --}}
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-none" style="min-height: 36px;" title="Eliminar archivo" aria-label="Eliminar respaldo {{ $item['nombre'] }}" data-bs-toggle="modal" data-bs-target="#modalEliminarRespaldo_{{ $loop->index }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i> <span>Eliminar</span>
                                    </button>

                                    {{-- Modal Solicitar Restauración --}}
                                    <div class="modal fade" id="modalRestaurarRespaldo_{{ $loop->index }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="{{ route('admin.respaldos.solicitar-restauracion', $item['nombre']) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                                                            <i class="bi bi-arrow-counterclockwise text-warning fs-4"></i>
                                                            Restaurar Copia de Seguridad
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <div class="alert alert-warning border-0 small rounded-3 mb-3">
                                                            <div class="d-flex align-items-start gap-2">
                                                                <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0"></i>
                                                                <div>
                                                                    <strong>Atención:</strong> Esta acción restablecerá la información del sistema al estado exacto registrado en esta copia de seguridad. Los cambios posteriores serán reemplazados.
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <p class="text-muted small mb-2">
                                                            Copia de seguridad seleccionada: <strong class="font-monospace text-dark">{{ $item['nombre'] }}</strong> ({{ $item['tamano_formateado'] }} — {{ $item['fecha_modificacion'] }})
                                                        </p>
                                                        <p class="text-muted small mb-3">
                                                            Por seguridad institucional, antes de iniciar la restauración el sistema generará automáticamente un <strong>respaldo preventivo</strong> con los datos actuales.
                                                        </p>

                                                        <div class="mb-3">
                                                            <label for="password_confirm_{{ $loop->index }}" class="form-label small fw-semibold text-dark">
                                                                Ingresa tu contraseña actual de administrador para confirmar: <span class="text-danger">*</span>
                                                            </label>
                                                            <input type="password" name="password" id="password_confirm_{{ $loop->index }}" class="form-control rounded-3" placeholder="••••••••" required autocomplete="current-password">
                                                            <div class="form-text small text-muted">Se requiere tu contraseña institucional vigente para validar la operación.</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-warning btn-sm rounded-pill px-4 fw-semibold text-dark">
                                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Confirmar y Restaurar
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Modal Eliminar Respaldo --}}
                                    <div class="modal fade" id="modalEliminarRespaldo_{{ $loop->index }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-danger">Eliminar copia de seguridad</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <p class="text-muted small mb-0">
                                                        ¿Seguro que deseas eliminar permanentemente el archivo <strong class="font-monospace text-dark">{{ $item['nombre'] }}</strong>? Esta acción no se puede deshacer.
                                                    </p>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                    <form action="{{ route('admin.respaldos.destroy', $item['nombre']) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4">Eliminar respaldo</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-5 text-secondary d-block mb-2"></i>
                                Aún no se han generado respaldos en este directorio. Haz clic en <strong>Generar Respaldo Ahora</strong> para crear el primero.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Protocolo de Seguridad y Salvaguarda Institucional (FN.16) --}}
    <div class="card border-0 shadow-sm border-start border-4 border-primary mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-primary"></i>
                Protocolo de Salvaguarda y Seguridad de Bases de Datos
            </h5>
            <p class="text-muted small mb-3">
                La plataforma Bienestar UPEMOR administra la información institucional mediante estrictos controles de integridad física y criptográfica:
            </p>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 rounded-4 bg-light h-100 border">
                        <div class="fw-bold text-dark small mb-1"><i class="bi bi-fingerprint text-primary me-1"></i> Verificación Criptográfica SHA-256</div>
                        <div class="small text-muted">Cada copia de seguridad cuenta con una firma digital única que permite comprobar que el archivo no ha sufrido alteraciones ni pérdidas de bytes.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-4 bg-light h-100 border">
                        <div class="fw-bold text-dark small mb-1"><i class="bi bi-arrow-repeat text-success me-1"></i> Respaldo Preventivo Automático</div>
                        <div class="small text-muted">Cualquier solicitud de restauración genera de forma previa un respaldo del estado actual, garantizando puntos de recuperación inmediatos.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-4 bg-light h-100 border">
                        <div class="fw-bold text-dark small mb-1"><i class="bi bi-key-fill text-warning me-1"></i> Reautenticación por Contraseña</div>
                        <div class="small text-muted">Las operaciones de alto impacto requieren la confirmación de la contraseña del Administrador en sesión para evitar ejecuciones accidentales.</div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light border border-info-subtle bg-info-subtle text-info-emphasis rounded-3 p-3 mt-3 mb-0 small">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 text-info"></i>
                    <div>
                        <strong>Política de Seguridad y Salvaguarda Institucional:</strong>
                        Toda operación de recuperación crea de forma automática e inmediata una copia preventiva previa. El acceso a la restauración está restringido a personal administrativo autorizado y autenticado mediante credenciales vigentes, protegiendo en todo momento la continuidad y veracidad de la información universitaria.
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal Generar Respaldo --}}
    <div class="modal fade" id="modalGenerarRespaldo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Generar respaldo de base de datos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-muted small mb-0">
                        ¿Confirmas que deseas generar un nuevo respaldo de la base de datos ahora? Se generará un volcado SQL íntegro con estructura y datos en almacenamiento local seguro.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route('admin.respaldos.store') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i> Sí, generar respaldo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

