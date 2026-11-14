@extends('layouts.dashboard')

@section('title', 'Centro de Reportes Institucionales')

@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb y Cabecera --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Reportes Institucionales</li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Módulo de Reportes Institucionales</h1>
            <p class="text-muted mb-0">Informes estadísticos, psicométricos y de auditoría conforme a las especificaciones institucionales de UPEMOR.</p>
        </div>
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fs-6">
                <i class="bi bi-file-earmark-bar-graph-fill me-1"></i> 6 Reportes Académicos Oficiales
            </span>
        </div>
    </div>

    @include('components.portal-alerts')

    {{-- Cuadrícula de los 6 Reportes Oficiales --}}
    <div class="row g-4">
        {{-- Reporte 1 --}}
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-pie-chart-fill fs-3"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF + <i class="bi bi-filetype-xlsx ms-1"></i> Excel
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">1. Número y porcentaje de emociones registradas</h5>
                    <p class="text-muted small flex-grow-1 mb-3">
                        Distribución cuantitativa y porcentual de los estados de ánimo registrados por el alumnado, con desglose de intensidad media y gráficas analíticas.
                    </p>
                    <div class="border-top pt-3 mt-auto">
                        <a href="{{ route('admin.reportes.show', 'emociones-registradas') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i> Consultar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reporte 2 --}}
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-3 p-3 bg-info bg-opacity-10 text-info">
                            <i class="bi bi-bar-chart-steps fs-3"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF + <i class="bi bi-filetype-xlsx ms-1"></i> Excel
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">2. Emociones por Programa Educativo</h5>
                    <p class="text-muted small flex-grow-1 mb-3">
                        Matriz comparativa de clima emocional entre las carreras e ingenierías de UPEMOR, con gráficas agrupadas por plan curricular.
                    </p>
                    <div class="border-top pt-3 mt-auto">
                        <a href="{{ route('admin.reportes.show', 'emociones-por-programa') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i> Consultar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reporte 3 --}}
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-3 p-3 bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-person-lines-fill fs-3"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF + <i class="bi bi-filetype-xlsx ms-1"></i> Excel
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">3. Historial de bitácoras y citas por alumno</h5>
                    <p class="text-muted small flex-grow-1 mb-3">
                        Expediente cronológico e individualizado con la trazabilidad completa de registros emocionales y citas de orientación psicológica de cada estudiante.
                    </p>
                    <div class="border-top pt-3 mt-auto">
                        <a href="{{ route('admin.reportes.show', 'historial-alumno') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i> Consultar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reporte 4 --}}
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-gift-fill fs-3"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF + <i class="bi bi-filetype-xlsx ms-1"></i> Excel
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">4. Premios más canjeados</h5>
                    <p class="text-muted small flex-grow-1 mb-3">
                        Estadísticas de popularidad y uso de recompensas digitales, puntos totales redimidos, tasas de aprobación y porcentaje de preferencia.
                    </p>
                    <div class="border-top pt-3 mt-auto">
                        <a href="{{ route('admin.reportes.show', 'premios-canjeados') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i> Consultar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reporte 5 --}}
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success">
                            <i class="bi bi-calendar-check-fill fs-3"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF + <i class="bi bi-filetype-xlsx ms-1"></i> Excel
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">5. Sesiones registradas por día</h5>
                    <p class="text-muted small flex-grow-1 mb-3">
                        Auditoría de actividad y adopción diaria de la plataforma, con desglose de accesos por roles (alumnos, docentes, orientadores y administradores).
                    </p>
                    <div class="border-top pt-3 mt-auto">
                        <a href="{{ route('admin.reportes.show', 'sesiones-por-dia') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i> Consultar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reporte 6 --}}
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-3 p-3 bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-card-checklist fs-3"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF + <i class="bi bi-filetype-xlsx ms-1"></i> Excel
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">6. Resultados generales de los tests aplicados</h5>
                    <p class="text-muted small flex-grow-1 mb-3">
                        Tamizaje psicométrico institucional con baremos, clasificaciones de riesgo y detección oportuna de alertas críticas para acompañamiento estudiantil.
                    </p>
                    <div class="border-top pt-3 mt-auto">
                        <a href="{{ route('admin.reportes.show', 'resultados-tests') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i> Consultar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

