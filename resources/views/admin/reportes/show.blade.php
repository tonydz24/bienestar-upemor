@extends('layouts.dashboard')

@section('title', $data['titulo'] . ' — Reportes UPEMOR')

@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb y Cabecera --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reportes.index') }}" class="text-decoration-none">Reportes Institucionales</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($data['titulo'], 35) }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">{{ $data['titulo'] }}</h1>
            <p class="text-muted mb-0 small">Generado el {{ $data['fecha_generacion'] }} • Plataforma Bienestar UPEMOR</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.reportes.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Todos los Reportes
            </a>
            <a href="{{ route('admin.reportes.exportar-pdf', array_merge(['tipo' => $tipo], request()->query())) }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Exportar PDF
            </a>
            <a href="{{ route('admin.reportes.exportar-excel', array_merge(['tipo' => $tipo], request()->query())) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar Excel (.xlsx)
            </a>
        </div>
    </div>

    @include('components.portal-alerts')

    {{-- Filtros del Reporte --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-funnel-fill text-primary me-2"></i>Filtros de Parámetros</h6>
        </div>
        <div class="card-body p-3">
            <form action="{{ route('admin.reportes.show', $tipo) }}" method="GET" class="row g-3 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold">Fecha Inicio:</label>
                    <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ request('fecha_inicio') }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold">Fecha Fin:</label>
                    <input type="date" name="fecha_fin" class="form-control form-control-sm" value="{{ request('fecha_fin') }}">
                </div>

                {{-- Filtros específicos por tipo --}}
                @if(in_array($tipo, ['emociones-registradas', 'emociones-por-programa']))
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Emoción:</label>
                        <select name="emocion_id" class="form-select form-select-sm">
                            <option value="">Todas las emociones</option>
                            @foreach($catalogos['emociones'] as $e)
                                <option value="{{ $e->emocion_id }}" {{ request('emocion_id') == $e->emocion_id ? 'selected' : '' }}>
                                    {{ $e->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(in_array($tipo, ['emociones-registradas', 'emociones-por-programa', 'resultados-tests']))
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Programa Educativo:</label>
                        <select name="programa_id" class="form-select form-select-sm">
                            <option value="">Todos los programas</option>
                            @foreach($catalogos['programas'] as $pe)
                                <option value="{{ $pe->programa_id }}" {{ request('programa_id') == $pe->programa_id ? 'selected' : '' }}>
                                    {{ $pe->siglas }} — {{ $pe->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($tipo === 'historial-alumno')
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Seleccionar Estudiante:</label>
                        <select name="estudiante_id" class="form-select form-select-sm" required>
                            <option value="">-- Selecciona un estudiante --</option>
                            @foreach($catalogos['estudiantes'] as $est)
                                @php
                                    $u = $est->usuario;
                                    $nombreEst = $u ? trim($u->nombre . ' ' . $u->apellido_paterno . ' ' . ($u->apellido_materno ?? '')) : 'Estudiante';
                                @endphp
                                <option value="{{ $est->usuario_id ?? $est->estudiante_id }}" {{ (request('estudiante_id') == ($est->usuario_id ?? $est->estudiante_id) || ($data['estudiante']?->usuario_id ?? null) == ($est->usuario_id ?? $est->estudiante_id)) ? 'selected' : '' }}>
                                    {{ $est->matricula ?? 'S/M' }} — {{ $nombreEst }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Tipo de Registro:</label>
                        <select name="tipo_registro" class="form-select form-select-sm">
                            <option value="TODOS" {{ request('tipo_registro') == 'TODOS' ? 'selected' : '' }}>Todos (Bitácoras y Citas)</option>
                            <option value="BITACORA" {{ request('tipo_registro') == 'BITACORA' ? 'selected' : '' }}>Únicamente Bitácoras</option>
                            <option value="CITA" {{ request('tipo_registro') == 'CITA' ? 'selected' : '' }}>Únicamente Citas de Orientación</option>
                        </select>
                    </div>
                @endif

                @if($tipo === 'premios-canjeados')
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Premio / Recompensa:</label>
                        <select name="recompensa_id" class="form-select form-select-sm">
                            <option value="">Todas las recompensas</option>
                            @foreach($catalogos['recompensas'] as $r)
                                <option value="{{ $r->recompensa_id }}" {{ request('recompensa_id') == $r->recompensa_id ? 'selected' : '' }}>
                                    {{ $r->titulo }} ({{ $r->costo_puntos_base }} pts)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Estado del Canje:</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="">Todos los estados</option>
                            <option value="UTILIZADA" {{ request('estado') == 'UTILIZADA' ? 'selected' : '' }}>Utilizada / Efectiva</option>
                            <option value="CANJEADA" {{ request('estado') == 'CANJEADA' ? 'selected' : '' }}>Canjeada (En poder del alumno)</option>
                            <option value="APROBADO" {{ request('estado') == 'APROBADO' ? 'selected' : '' }}>Aprobado</option>
                            <option value="SOLICITADO" {{ request('estado') == 'SOLICITADO' ? 'selected' : '' }}>Solicitado</option>
                            <option value="RECHAZADO" {{ request('estado') == 'RECHAZADO' ? 'selected' : '' }}>Rechazado</option>
                        </select>
                    </div>
                @endif

                @if($tipo === 'sesiones-por-dia')
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Rol Institucional:</label>
                        <select name="rol_nombre" class="form-select form-select-sm">
                            <option value="">Todos los roles</option>
                            <option value="Alumno" {{ request('rol_nombre') == 'Alumno' ? 'selected' : '' }}>Alumno</option>
                            <option value="Docente" {{ request('rol_nombre') == 'Docente' ? 'selected' : '' }}>Docente</option>
                            <option value="Orientador" {{ request('rol_nombre') == 'Orientador' ? 'selected' : '' }}>Orientador</option>
                            <option value="Administrador" {{ request('rol_nombre') == 'Administrador' ? 'selected' : '' }}>Administrador</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Buscar Usuario:</label>
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Nombre o correo..." value="{{ request('q') }}">
                    </div>
                @endif

                @if($tipo === 'resultados-tests')
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Instrumento / Test:</label>
                        <select name="instrumento_id" class="form-select form-select-sm">
                            <option value="">Todos los instrumentos</option>
                            @foreach($catalogos['instrumentos'] as $ins)
                                <option value="{{ $ins->instrumento_id }}" {{ request('instrumento_id') == $ins->instrumento_id ? 'selected' : '' }}>
                                    {{ $ins->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold">Clasificación / Nivel:</label>
                        <select name="nivel_resultado" class="form-select form-select-sm">
                            <option value="">Todas las clasificaciones</option>
                            <option value="Bajo" {{ request('nivel_resultado') == 'Bajo' ? 'selected' : '' }}>Bajo / Normal</option>
                            <option value="Medio" {{ request('nivel_resultado') == 'Medio' ? 'selected' : '' }}>Medio / Leve</option>
                            <option value="Alto" {{ request('nivel_resultado') == 'Alto' ? 'selected' : '' }}>Alto / Severo</option>
                        </select>
                    </div>
                @endif

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-filter me-1"></i> Aplicar Filtros
                    </button>
                    <a href="{{ route('admin.reportes.show', $tipo) }}" class="btn btn-outline-secondary btn-sm ms-1">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Renderizado Dinámico según el tipo de reporte --}}

    {{-- REPORTE 1: Número y porcentaje de emociones registradas --}}
    @if($tipo === 'emociones-registradas')
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="text-muted small">Total Registros Emocionales</div>
                    <div class="display-6 fw-bold text-primary">{{ number_format($data['total_general']) }}</div>
                    <div class="small text-muted mt-1">Bitácoras registradas en el periodo</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="text-muted small">Emociones Observadas</div>
                    <div class="display-6 fw-bold text-success">{{ $data['emociones_observadas'] ?? $data['datos']->where('total', '>', 0)->count() }} <span class="fs-5 text-muted fw-normal">de {{ $data['emociones_totales'] ?? $data['datos']->count() }}</span></div>
                    <div class="small text-muted mt-1">Emociones del catálogo con registros</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="text-muted small">Emoción Preponderante</div>
                    <div class="fs-4 fw-bold text-dark text-truncate mt-1">{{ $data['emocion_preponderante'] ?? ($data['datos']->firstWhere('total', '>', 0)?->emocion_nombre ?? 'Sin registros') }}</div>
                    <div class="small text-muted mt-1">Mayor frecuencia en el estudiantado</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Tabla con frecuencias y porcentajes --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">Tabla de Frecuencia y Distribución Porcentual</h6>
                        <span class="badge bg-light text-muted border">6 Emociones Institucionales</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Emoción</th>
                                    <th class="text-center" title="Promedio de la intensidad que los estudiantes asignaron a esta emoción en sus registros">
                                        Intensidad promedio (escala 1–5) <i class="bi bi-info-circle small text-muted"></i>
                                    </th>
                                    <th class="text-center">Total Registros</th>
                                    <th class="text-end">Porcentaje (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['datos'] as $item)
                                    <tr class="{{ $item->total === 0 ? 'bg-light-subtle text-muted' : '' }}">
                                        <td>
                                            <div class="d-inline-flex align-items-center gap-2 text-nowrap">
                                                <span class="rounded-circle flex-shrink-0 shadow-sm" style="width: 12px; height: 12px; background-color: {{ $item->codigo_color ?: '#6c757d' }};"></span>
                                                <strong class="{{ $item->total > 0 ? 'text-dark' : 'text-muted' }}">{{ $item->emocion_nombre }}</strong>
                                                @if($item->total === 0)
                                                    <span class="badge bg-light text-muted border ms-1 small">Sin registros</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold">
                                            @if($item->intensidad_promedio !== null)
                                                <span class="badge bg-light text-dark border px-2 py-1">{{ number_format($item->intensidad_promedio, 2) }} / 5</span>
                                            @else
                                                <span class="badge bg-light text-muted border px-2 py-1"><i class="bi bi-dash-circle me-1"></i>Sin registros</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($item->total) }}</td>
                                        <td class="text-end fw-bold {{ $item->total > 0 ? 'text-primary' : 'text-muted' }}">{{ number_format($item->porcentaje, 1) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No se encontraron registros con los filtros seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 border-top bg-light-subtle small text-muted">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            <strong>Nota técnica y metodológica:</strong> La columna <em>Intensidad promedio (escala 1–5)</em> refleja el nivel medio autoevaluado por los estudiantes en sus bitácoras. Cuando una emoción no cuenta con registros en el periodo seleccionado, se indica claramente <em>«Sin registros»</em> en lugar de un valor numérico cero para evitar distorsiones estadísticas.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gráficas Visuales: Gráfica 1 (Dona) y Gráfica 2 (Barras de Intensidad) --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">Gráfica 1: Distribución Porcentual</h6>
                        <span class="badge bg-light text-muted border">Dona Institucional</span>
                    </div>
                    <div class="card-body p-4 text-center">
                        @if(!empty($data['svg_donut']) && $data['total_general'] > 0)
                            <div class="d-flex justify-content-center mb-4">
                                {!! $data['svg_donut'] !!}
                            </div>
                        @endif
                        @foreach($data['datos'] as $item)
                            <div class="mb-3 text-start">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <div class="d-inline-flex align-items-center gap-2 text-nowrap">
                                        <span class="rounded-circle flex-shrink-0" style="display: inline-block; width: 10px; height: 10px; background-color: {{ $item->codigo_color }}; vertical-align: middle;"></span>
                                        <span class="fw-medium text-dark">{{ $item->emocion_nombre }}</span>
                                    </div>
                                    <span class="fw-bold {{ $item->total > 0 ? 'text-dark' : 'text-muted' }}">{{ $item->total }} ({{ number_format($item->porcentaje, 1) }}%)</span>
                                </div>
                                <div class="progress" style="height: 14px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $item->porcentaje }}%; background-color: {{ $item->codigo_color ?: '#7C3AED' }};" aria-valuenow="{{ $item->porcentaje }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if(!empty($data['svg_intensidad']) && $data['total_general'] > 0)
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Gráfica 2: Intensidad Promedio por Emoción</h6>
                            <span class="badge bg-light text-muted border">Escala 1 a 5</span>
                        </div>
                        <div class="card-body p-3 text-center">
                            <div class="d-flex justify-content-center">
                                {!! $data['svg_intensidad'] !!}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    {{-- REPORTE 2: Emociones por Programa Educativo --}}
    @elseif($tipo === 'emociones-por-programa')
        {{-- Dos Gráficas Institucionales Comparativas --}}
        @if(!empty($data['svg_agrupadas']) || !empty($data['svg_apiladas']))
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Gráfica 1: Frecuencia por Programa Educativo</h6>
                            <span class="badge bg-light text-muted border">Barras Comparativas</span>
                        </div>
                        <div class="card-body p-3 text-center d-flex align-items-center justify-content-center">
                            {!! $data['svg_agrupadas'] !!}
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Gráfica 2: Clima Emocional Relativo</h6>
                            <span class="badge bg-light text-muted border">100% Apilado</span>
                        </div>
                        <div class="card-body p-3 text-center d-flex align-items-center justify-content-center">
                            {!! $data['svg_apiladas'] !!}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Comparativa de Clima Emocional por Plan Educativo</h6>
                <span class="badge bg-primary">{{ number_format($data['total_general']) }} Registros Analizados</span>
            </div>
            <div class="card-body p-4">
                @forelse($data['programas'] as $prog)
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0">
                                <span class="badge bg-secondary me-2">{{ $prog['siglas'] }}</span>
                                {{ $prog['nombre'] }}
                            </h6>
                            <span class="small text-muted">Total: <strong>{{ $prog['total_programa'] }}</strong> registros ({{ $prog['porcentaje_global'] }}% del total universitario)</span>
                        </div>
                        <div class="row g-2">
                            @foreach($prog['emociones'] as $emo)
                                <div class="col-md-3 col-sm-6">
                                    <div class="p-2 rounded bg-light border small">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-inline-flex align-items-center gap-1.5 text-nowrap" style="max-width: 140px;">
                                                <span class="rounded-circle flex-shrink-0" style="width: 8px; height: 8px; background-color: {{ $emo['codigo_color'] ?: '#7C3AED' }};"></span>
                                                <span class="text-truncate fw-medium">{{ $emo['emocion'] }}</span>
                                            </div>
                                            <strong>{{ $emo['porcentaje_programa'] }}%</strong>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" style="width: {{ $emo['porcentaje_programa'] }}%; background-color: {{ $emo['codigo_color'] ?: '#7C3AED' }};"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">No se encontraron datos registrados para los programas educativos con los filtros actuales.</p>
                @endforelse
            </div>
        </div>

    {{-- REPORTE 3: Historial individual de bitácoras y citas por alumno --}}
    @elseif($tipo === 'historial-alumno')
        @if(!$data['estudiante'])
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="bi bi-person-search display-4 text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark">Selecciona un estudiante para consultar su expediente</h5>
                    <p class="text-muted max-w-500 mx-auto">
                        Utiliza el selector de la sección de filtros para elegir la matrícula o nombre del alumno que deseas auditar.
                    </p>
                </div>
            </div>
        @else
            {{-- Ficha Académica del Alumno (Requisito 7 e Incremento 21) --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 border-start border-4 border-primary">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="badge bg-primary fs-6">{{ $data['estudiante']->matricula }}</span>
                                <span class="badge bg-secondary">{{ $data['estudiante']->programaEducativo?->siglas ?? 'PE' }}</span>
                                @if($data['estudiante']->plan_estudio_asignado || $data['estudiante']->planEstudio)
                                    <span class="badge bg-dark-subtle text-dark border">
                                        <i class="bi bi-mortarboard-fill me-1 text-primary"></i>Plan: {{ $data['estudiante']->plan_estudio_asignado?->nombre ?? $data['estudiante']->planEstudio?->nombre }}
                                    </span>
                                @endif
                                @if($data['estudiante']->generacion)
                                    <span class="badge bg-info-subtle text-info border">
                                        <i class="bi bi-calendar3 me-1"></i>Gen: {{ $data['estudiante']->generacion }}
                                    </span>
                                @endif
                            </div>
                            <h4 class="fw-bold text-dark mb-1">
                                {{ $data['estudiante']->usuario ? ($data['estudiante']->usuario->nombre_completo ?? trim($data['estudiante']->usuario->nombre . ' ' . $data['estudiante']->usuario->apellido_paterno)) : 'Estudiante UPEMOR' }}
                            </h4>
                            <p class="text-muted small mb-0">
                                <strong>Carrera:</strong> {{ $data['estudiante']->programaEducativo?->nombre ?? 'N/A' }} •
                                <strong>Cuatrimestre:</strong> {{ $data['estudiante']->cuatrimestre }}° "{{ $data['estudiante']->grupo }}" •
                                <strong>Email:</strong> {{ $data['estudiante']->usuario?->email ?? 'N/A' }} •
                                <strong>Privacidad de Bitácora:</strong>
                                @if($data['estudiante']->permite_visualizacion)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Permite visualización institucional</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Privacidad protegida (solo datos no íntimos)</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="fs-4 fw-bold text-primary">{{ $data['total_registros'] }}</span>
                            <div class="text-muted small">Registros encontrados</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dos Visualizaciones Gráficas para R3: Evolución de Intensidad y Citas por Estado --}}
            @if(!empty($data['svg_linea_tiempo']) || !empty($data['svg_barras_citas']))
                <div class="row g-4 mb-4">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0">Gráfica 1: Evolución Temporal de Intensidad Emocional</h6>
                                <span class="badge bg-light text-muted border">Línea Temporal (1–5)</span>
                            </div>
                            <div class="card-body p-3 text-center d-flex align-items-center justify-content-center">
                                {!! $data['svg_linea_tiempo'] !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0">Gráfica 2: Citas de Orientación por Estado</h6>
                                <span class="badge bg-light text-muted border">Barras de Estado</span>
                            </div>
                            <div class="card-body p-3 text-center d-flex align-items-center justify-content-center">
                                {!! $data['svg_barras_citas'] !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tabla Cronológica Individual --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0">Línea de Tiempo de Intervenciones y Registros</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo de Registro</th>
                                <th>Emoción / Modalidad</th>
                                <th>Detalle / Contexto</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['historial'] as $row)
                                <tr>
                                    <td class="text-nowrap small fw-medium">{{ $row['fecha_formateada'] }}</td>
                                    <td>
                                        <span class="badge {{ $row['tipo'] === 'Bitácora Emocional' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">
                                            {{ $row['tipo'] }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold">{{ $row['emocion_evento'] }}</td>
                                    <td class="small">{{ $row['detalle'] }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $row['estado'] }}</span>
                                    </td>
                                    <td class="small text-muted">{{ $row['observaciones'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No se registran bitácoras ni citas para este alumno con los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    {{-- REPORTE 4: Premios más canjeados --}}
    @elseif($tipo === 'premios-canjeados')
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Total Canjes Registrados</div>
                    <div class="display-6 fw-bold text-primary">{{ number_format($data['total_general']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Puntos Totales Redimidos</div>
                    <div class="display-6 fw-bold text-success">{{ number_format($data['puntos_totales']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Recompensa Estrella</div>
                    <div class="fs-4 fw-bold text-dark text-truncate">{{ $data['datos']->first()?->recompensa_titulo ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        @if(!empty($data['svg_donut']) && $data['total_general'] > 0)
            <div class="row g-4 mb-4">
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm text-center p-3 h-100">
                        <div class="card-header bg-white py-2 border-0">
                            <h6 class="fw-bold mb-0">Distribución de Beneficios (Gráfica Donut)</h6>
                        </div>
                        <div class="d-flex justify-content-center align-items-center py-3">
                            {!! $data['svg_donut'] !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="card-header bg-white py-2 border-0">
                            <h6 class="fw-bold mb-0">Volumen Comparativo por Beneficio</h6>
                        </div>
                        <div class="d-flex justify-content-center align-items-center py-2">
                            {!! $data['svg_barras'] !!}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0">Tabla de Canjes y Estadísticas por Recompensa</h6>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Recompensa / Premio</th>
                            <th class="text-center">Costo Base</th>
                            <th class="text-center">Total Canjes</th>
                            <th class="text-center text-success">Exitosos</th>
                            <th class="text-center text-warning">Pendientes</th>
                            <th class="text-center text-danger">Rechazados</th>
                            <th class="text-center">Puntos Totales</th>
                            <th class="text-end">% del Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['datos'] as $item)
                            <tr>
                                <td class="fw-bold text-dark">{{ $item->recompensa_titulo }}</td>
                                <td class="text-center">{{ number_format($item->costo_puntos_base) }} pts</td>
                                <td class="text-center fw-bold">{{ number_format($item->total_canjes) }}</td>
                                <td class="text-center fw-semibold text-success">{{ number_format($item->canjes_exitosos) }}</td>
                                <td class="text-center fw-semibold text-warning">{{ number_format($item->canjes_pendientes) }}</td>
                                <td class="text-center fw-semibold text-danger">{{ number_format($item->canjes_rechazados) }}</td>
                                <td class="text-center fw-bold">{{ number_format($item->puntos_totales_canjeados) }}</td>
                                <td class="text-end fw-bold text-primary">{{ number_format($item->porcentaje_uso, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No se han registrado canjes con los filtros indicados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- REPORTE 5: Sesiones registradas por día --}}
    @elseif($tipo === 'sesiones-por-dia')
        @php
            $rolSeleccionado = request('rol_nombre');
            $busquedaUsuario = request('q');
            $maxAccesosDia = $data['serie_temporal']->max('total_accesos') ?: 1;
        @endphp

        {{-- Banner cuando hay búsqueda de usuario --}}
        @if($busquedaUsuario)
            <div class="alert alert-primary border-0 shadow-sm rounded-4 d-flex align-items-center justify-content-between p-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-primary p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 42px; height: 42px;">
                        <i class="bi bi-person-badge-fill fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Filtrando actividad para: «{{ $busquedaUsuario }}»</div>
                        <div class="small text-muted">Ambas vistas reflejan exclusivamente a este usuario: el Resumen Diario agrupa los días en que ingresó y el Detalle Individual lista cada acceso con su hora precisa.</div>
                    </div>
                </div>
                <a href="{{ route('admin.reportes.show', array_merge(['tipo' => $tipo], request()->except('q'))) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    Quitar filtro
                </a>
            </div>
        @endif

        {{-- KPI Cards orientadas a responder preguntas institucionales --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="text-muted small fw-semibold">Total de Accesos</div>
                    <div class="display-6 fw-bold text-primary mt-1">{{ number_format($data['total_general']) }}</div>
                    <div class="small text-muted mt-1">¿Cuántas veces iniciaron sesión?</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="text-muted small fw-semibold">Usuarios Únicos del Periodo</div>
                    <div class="display-6 fw-bold text-success mt-1">{{ number_format($data['total_usuarios_unicos']) }}</div>
                    <div class="small text-muted mt-1">¿Cuántas personas distintas ingresaron?</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="text-muted small fw-semibold">Días con Actividad</div>
                    <div class="display-6 fw-bold text-info mt-1">{{ number_format($data['dias_con_actividad']) }}</div>
                    <div class="small text-muted mt-1">Jornadas con registros de acceso</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="text-muted small fw-semibold">Promedio Diario</div>
                    <div class="display-6 fw-bold text-warning mt-1">{{ number_format($data['promedio_diario'], 1) }}</div>
                    <div class="small text-muted mt-1">Accesos por jornada activa</div>
                </div>
            </div>
        </div>

        {{-- Selector de Vistas: Vista 1 (Resumen agrupado por día) y Vista 2 (Detalle individual) --}}
        <ul class="nav nav-pills mb-4 gap-2" id="sesionesTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4 py-2 fw-semibold active" id="resumen-tab" data-bs-toggle="pill" data-bs-target="#vista-resumen" type="button" role="tab" aria-controls="vista-resumen" aria-selected="true">
                    <i class="bi bi-calendar-week me-2"></i> Vista 1: Resumen Diario Agrupado
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4 py-2 fw-semibold" id="detalle-tab" data-bs-toggle="pill" data-bs-target="#vista-detalle" type="button" role="tab" aria-controls="vista-detalle" aria-selected="false">
                    <i class="bi bi-list-check me-2"></i> Vista 2: Detalle Individual de Accesos
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-2">{{ $data['detalle_accesos']->count() }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="sesionesTabContent">
            {{-- VISTA 1: RESUMEN DIARIO AGRUPADO --}}
            <div class="tab-pane fade show active" id="vista-resumen" role="tabpanel" aria-labelledby="resumen-tab">
                {{-- Dos Visualizaciones Gráficas para R5: Volumen Cronológico y Desglose Exitosos vs Fallidos --}}
                @if(!empty($data['svg_linea_tiempo']) || !empty($data['svg_apiladas_accesos']))
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0">Gráfica 1: Volumen Diario de Accesos</h6>
                                    <span class="badge bg-light text-primary border">Línea Temporal</span>
                                </div>
                                <div class="card-body p-3 text-center d-flex align-items-center justify-content-center">
                                    {!! $data['svg_linea_tiempo'] !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0">Gráfica 2: Accesos Exitosos vs Intentos Fallidos</h6>
                                    <span class="badge bg-light text-muted border">Barras Apiladas</span>
                                </div>
                                <div class="card-body p-3 text-center d-flex align-items-center justify-content-center">
                                    {!! $data['svg_apiladas_accesos'] !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0">Tabla de Resumen Diario de Accesos</h6>
                            <span class="small text-muted">Total de inicios de sesión y usuarios físicos únicos de cada jornada</span>
                        </div>
                        @if($rolSeleccionado)
                            <span class="badge bg-primary-subtle text-primary border border-primary">Filtrado por: Rol {{ $rolSeleccionado }}</span>
                        @else
                            <span class="badge bg-light text-muted border">Todos los Roles Institucionales</span>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-center">Total Accesos</th>
                                    <th class="text-center">Personas Distintas (Únicas)</th>
                                    @if(!$rolSeleccionado)
                                        <th class="text-center text-info">Alumnos</th>
                                        <th class="text-center text-secondary">Docentes</th>
                                        <th class="text-center text-dark">Orientadores</th>
                                        <th class="text-center text-danger">Administradores</th>
                                    @else
                                        <th class="text-center text-primary">Accesos de {{ $rolSeleccionado }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['resumen_diario'] as $dia)
                                    <tr>
                                        <td class="fw-bold">{{ \Carbon\Carbon::parse($dia->fecha)->translatedFormat('l, d/m/Y') }}</td>
                                        <td class="text-center fw-bold text-primary">{{ number_format($dia->total_accesos) }}</td>
                                        <td class="text-center fw-semibold text-success">{{ number_format($dia->usuarios_unicos) }}</td>
                                        @if(!$rolSeleccionado)
                                            <td class="text-center text-info fw-semibold">{{ number_format($dia->accesos_alumnos) }}</td>
                                            <td class="text-center text-secondary fw-semibold">{{ number_format($dia->accesos_docentes) }}</td>
                                            <td class="text-center text-dark fw-semibold">{{ number_format($dia->accesos_orientadores) }}</td>
                                            <td class="text-center text-danger fw-semibold">{{ number_format($dia->accesos_admins) }}</td>
                                        @else
                                            @php
                                                $accesosRol = match($rolSeleccionado) {
                                                    'Alumno' => $dia->accesos_alumnos,
                                                    'Docente' => $dia->accesos_docentes,
                                                    'Orientador' => $dia->accesos_orientadores,
                                                    'Administrador' => $dia->accesos_admins,
                                                    default => $dia->total_accesos,
                                                };
                                            @endphp
                                            <td class="text-center text-primary fw-bold">{{ number_format($accesosRol) }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $rolSeleccionado ? 4 : 7 }}" class="text-center text-muted py-4">No se registran accesos en el periodo seleccionado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light-subtle py-3 px-4 small text-muted border-top">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill text-primary mt-1"></i>
                            <div>
                                <strong>Nota metodológica sobre Usuarios Únicos:</strong>
                                La métrica <em>«Usuarios Únicos del Periodo»</em> ({{ number_format($data['total_usuarios_unicos']) }}) representa el número total de cuentas individuales que iniciaron sesión al menos una vez en todo el periodo filtrado. Esta cifra <u>no es la suma matemática</u> de las personas distintas de cada día, ya que un mismo usuario que ingrese en dos o más jornadas se computa en cada una de ellas, pero en el consolidado global se contabiliza una sola vez.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- VISTA 2: DETALLE INDIVIDUAL DE ACCESOS --}}
            <div class="tab-pane fade" id="vista-detalle" role="tabpanel" aria-labelledby="detalle-tab">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0">Detalle Individual de Accesos e Inicios de Sesión</h6>
                            <span class="small text-muted">Auditoría cronológica exacta de quiénes, cuándo y desde dónde ingresaron</span>
                        </div>
                        <span class="badge bg-light text-muted border">Mostrando hasta 100 registros recientes</span>
                    </div>
                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3">Fecha y Hora Exacta</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Correo Institucional</th>
                                    <th>Dirección IP</th>
                                    <th class="text-center pe-3">Resultado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['detalle_accesos'] as $acceso)
                                    <tr>
                                        <td class="ps-3 small text-muted font-monospace">{{ $acceso->fecha_acceso?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                        <td class="fw-semibold text-dark">{{ $acceso->nombre_usuario ?: 'Usuario #' . $acceso->usuario_id }}</td>
                                        <td>
                                            @php
                                                $badgeRol = match($acceso->rol_nombre) {
                                                    'Alumno' => 'badge-rol-alumno',
                                                    'Docente' => 'badge-rol-docente',
                                                    'Orientador' => 'badge-rol-orientador',
                                                    'Administrador' => 'badge-rol-admin',
                                                    default => 'badge bg-light text-dark border',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeRol }} rounded-pill px-2.5 py-1">{{ $acceso->rol_nombre }}</span>
                                        </td>
                                        <td class="small text-muted">{{ $acceso->email ?: 'N/D' }}</td>
                                        <td class="small text-muted font-monospace">{{ $acceso->ip_address ?: '127.0.0.1' }}</td>
                                        <td class="text-center pe-3">
                                            @if($acceso->exitoso)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-check-circle me-1"></i> Exitoso
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-x-circle me-1"></i> Fallido
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No se encontraron accesos individuales con los criterios seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light-subtle py-2 px-3 small text-muted">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        <strong>Seguridad y trazabilidad:</strong> Registros institucionales protegidos. Ninguna contraseña ni dato biométrico se almacena ni expone en este módulo.
                    </div>
                </div>
            </div>
        </div>

    {{-- REPORTE 6: Resultados generales de los tests aplicados --}}
    @elseif($tipo === 'resultados-tests')
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Total Pruebas Completadas</div>
                    <div class="display-6 fw-bold text-primary">{{ number_format($data['total_general']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Alertas Críticas Detectadas</div>
                    <div class="display-6 fw-bold text-danger">{{ number_format($data['total_alertas_criticas']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Nivel con Mayor Frecuencia</div>
                    <div class="fs-4 fw-bold text-dark">{{ array_key_first($data['distribucion_niveles']) ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Dos Gráficas Institucionales para R6 --}}
        @if(!empty($data['svg_barras_niveles']) || !empty($data['svg_barras_instrumentos']))
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Gráfica 1: Distribución por Nivel de Riesgo</h6>
                            <span class="badge bg-light text-muted border">Tamizaje</span>
                        </div>
                        <div class="card-body p-3 text-center d-flex align-items-center justify-content-center">
                            {!! $data['svg_barras_niveles'] ?? $data['svg_barras'] !!}
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Gráfica 2: Aplicaciones por Instrumento Psicométrico</h6>
                            <span class="badge bg-light text-muted border">Instrumentos</span>
                        </div>
                        <div class="card-body p-3 text-center d-flex align-items-center justify-content-center">
                            {!! $data['svg_barras_instrumentos'] !!}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Tamizaje Psicométrico y Clasificación de Intentos</h6>
                <span class="badge bg-primary">{{ count($data['intentos']) }} Evaluaciones</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Instrumento</th>
                            <th>Matrícula</th>
                            <th>Programa Educativo</th>
                            <th class="text-center">Puntaje</th>
                            <th class="text-center">Clasificación / Riesgo</th>
                            <th class="text-center">Alerta Crítica</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['intentos'] as $intento)
                            <tr>
                                <td class="small fw-bold">#INT-{{ $intento['intento_id'] }}</td>
                                <td class="small">{{ $intento['fecha_finalizacion'] }}</td>
                                <td class="fw-semibold">{{ $intento['instrumento_nombre'] }}</td>
                                <td class="small">{{ $intento['matricula'] }}</td>
                                <td class="small text-truncate" style="max-width: 180px;">{{ $intento['programa_nombre'] }}</td>
                                <td class="text-center fw-bold">{{ $intento['puntuacion_total'] }}</td>
                                <td class="text-center">
                                    <span class="badge {{ str_contains(strtolower($intento['nivel_resultado']), 'alto') || str_contains(strtolower($intento['nivel_resultado']), 'severo') ? 'bg-danger' : (str_contains(strtolower($intento['nivel_resultado']), 'medio') || str_contains(strtolower($intento['nivel_resultado']), 'leve') ? 'bg-warning text-dark' : 'bg-success') }}">
                                        {{ $intento['nivel_resultado'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($intento['tiene_alerta_critica'])
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> SÍ</span>
                                    @else
                                        <span class="text-muted small">No</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No se han registrado evaluaciones con los filtros indicados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

