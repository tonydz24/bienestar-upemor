@extends('admin.reportes.pdf.layout')

@section('title', 'Reporte: Sesiones Registradas por Día')
@section('report-title', 'Reporte de Sesiones y Accesos Registrados por Día')
@section('report-meta', 'Total Accesos: ' . number_format($datosReporte['total_general']) . ' • Usuarios Únicos del Periodo: ' . number_format($datosReporte['total_usuarios_unicos']) . ' • Promedio Diario: ' . number_format($datosReporte['promedio_diario'], 1) . ' • Fecha: ' . $datosReporte['fecha_generacion'])

@section('content')
<!-- VISTA 1: RESUMEN DIARIO CON DESGLOSE COMPLETO DE ROLES -->
{{-- Dos Visualizaciones Gráficas para R5 --}}
@if(!empty($datosReporte['svg_linea_tiempo_data']) || !empty($datosReporte['svg_apiladas_accesos_data']))
<div style="margin-bottom: 18px; page-break-inside: avoid;">
    <div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-bottom: 8px;">
        MONITOREO VISUAL TEMPORAL Y DE AUTENTICACIÓN
    </div>
    <table style="width: 100%; border-collapse: collapse; border: none;">
        <tr>
            @if(!empty($datosReporte['svg_linea_tiempo_data']))
                <td style="width: 50%; vertical-align: top; padding-right: 8px; border: none;">
                    <div style="font-size: 7.5pt; font-weight: bold; color: #475569; margin-bottom: 4px;">Gráfica 1: Volumen diario de accesos:</div>
                    <img src="{{ $datosReporte['svg_linea_tiempo_data'] }}" style="width: 100%; max-width: 320px; height: auto;" alt="Línea temporal de accesos">
                </td>
            @endif
            @if(!empty($datosReporte['svg_apiladas_accesos_data']))
                <td style="width: 50%; vertical-align: top; border: none;">
                    <div style="font-size: 7.5pt; font-weight: bold; color: #475569; margin-bottom: 4px;">Gráfica 2: Exitosos vs Intentos fallidos por día:</div>
                    <img src="{{ $datosReporte['svg_apiladas_accesos_data'] }}" style="width: 100%; max-width: 320px; height: auto;" alt="Accesos exitosos vs fallidos">
                </td>
            @endif
        </tr>
    </table>
</div>
@endif

<div style="font-size: 10pt; font-weight: bold; color: #4A154B; margin-bottom: 6px;">
    Vista 1: Resumen Diario de Actividad
</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 22%;">Fecha</th>
            <th style="width: 13%; text-align: center;">Total Accesos</th>
            <th style="width: 13%; text-align: center;">Usuarios Únicos</th>
            <th style="width: 13%; text-align: center;">Alumnos</th>
            <th style="width: 13%; text-align: center;">Docentes</th>
            <th style="width: 13%; text-align: center;">Orientadores</th>
            <th style="width: 13%; text-align: center;">Administradores</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datosReporte['resumen_diario'] as $dia)
            <tr>
                <td class="fw-bold">{{ \Carbon\Carbon::parse($dia->fecha)->translatedFormat('l, d/m/Y') }}</td>
                <td class="text-center fw-bold" style="color: #4A154B;">{{ number_format($dia->total_accesos) }}</td>
                <td class="text-center">{{ number_format($dia->usuarios_unicos) }}</td>
                <td class="text-center">{{ number_format($dia->accesos_alumnos) }}</td>
                <td class="text-center">{{ number_format($dia->accesos_docentes) }}</td>
                <td class="text-center">{{ number_format($dia->accesos_orientadores) }}</td>
                <td class="text-center">{{ number_format($dia->accesos_admins) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No se registran accesos para el periodo seleccionado.</td>
            </tr>
        @endforelse
    </tbody>
    @if(count($datosReporte['resumen_diario']) > 0)
    <tfoot>
        <tr style="background-color: #f3e8ff; font-weight: bold;">
            <td style="color: #4A154B;">Totales del Periodo</td>
            <td class="text-center" style="color: #4A154B;">{{ number_format($datosReporte['total_general']) }}</td>
            <td class="text-center">{{ number_format($datosReporte['total_usuarios_unicos']) }}*</td>
            <td class="text-center">{{ number_format($datosReporte['resumen_diario']->sum('accesos_alumnos')) }}</td>
            <td class="text-center">{{ number_format($datosReporte['resumen_diario']->sum('accesos_docentes')) }}</td>
            <td class="text-center">{{ number_format($datosReporte['resumen_diario']->sum('accesos_orientadores')) }}</td>
            <td class="text-center">{{ number_format($datosReporte['resumen_diario']->sum('accesos_admins')) }}</td>
        </tr>
    </tfoot>
    @endif
</table>
<div style="margin-top: 8px; font-size: 8pt; color: #6c757d; line-height: 1.4;">
    <strong>* Nota metodológica sobre Usuarios Únicos:</strong> La métrica de usuarios únicos del periodo ({{ number_format($datosReporte['total_usuarios_unicos']) }}) refleja cuentas desduplicadas en el intervalo completo de consulta. No equivale a la suma aritmética de las columnas diarias, dado que una misma persona puede acceder en días distintos. Los roles de Orientador y Administrador se auditan por separado de acuerdo con la normativa técnica institucional.
</div>

<!-- SALTO DE PÁGINA PARA VISTA 2 -->
<div style="page-break-before: always; margin-top: 25px;"></div>

<!-- VISTA 2: DETALLE INDIVIDUAL DE ACCESOS -->
<div style="font-size: 10pt; font-weight: bold; color: #4A154B; margin-bottom: 4px;">
    Vista 2: Detalle Cronológico Individual de Accesos
</div>
<div style="font-size: 8pt; color: #6c757d; margin-bottom: 8px;">
    Total de eventos de autenticación incluidos en este reporte: {{ number_format($datosReporte['detalle_accesos']->count()) }} registros
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 17%;">Fecha y Hora</th>
            <th style="width: 25%;">Nombre de Usuario</th>
            <th style="width: 15%;">Rol Institucional</th>
            <th style="width: 23%;">Correo Institucional</th>
            <th style="width: 12%;">Dirección IP</th>
            <th style="width: 8%; text-align: center;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datosReporte['detalle_accesos'] as $acceso)
            <tr>
                <td style="white-space: nowrap;">
                    {{ $acceso->fecha_acceso ? \Carbon\Carbon::parse($acceso->fecha_acceso)->format('d/m/Y H:i:s') : '-' }}
                </td>
                <td class="fw-bold">{{ $acceso->nombre_usuario ?? 'Desconocido' }}</td>
                <td>
                    <span class="badge" style="background-color: #ede9fe; color: #4c1d95; border: 1px solid #c4b5fd;">
                        {{ $acceso->rol_nombre ?? 'Sin Rol' }}
                    </span>
                </td>
                <td style="word-break: break-all; font-size: 8pt;">{{ $acceso->email ?? 'N/A' }}</td>
                <td style="font-family: monospace; font-size: 8pt;">{{ $acceso->ip_address ?? 'N/A' }}</td>
                <td class="text-center">
                    @if($acceso->exitoso)
                        <span class="badge badge-positive">Exitoso</span>
                    @else
                        <span class="badge badge-danger">Fallido</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No se registran eventos individuales para el periodo seleccionado.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
