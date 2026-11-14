@extends('admin.reportes.pdf.layout')

@section('title', 'Reporte: Premios Más Canjeados')
@section('report-title', 'Reporte Institucional de Premios y Recompensas Más Canjeados')
@section('report-meta', 'Total Canjes Registrados: ' . number_format($datosReporte['total_general']) . ' • Puntos Redimidos: ' . number_format($datosReporte['puntos_totales']) . ' • Fecha: ' . $datosReporte['fecha_generacion'])

@section('content')
{{-- Resumen Ejecutivo en Tarjetas --}}
<table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
    <tr>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Total Canjes</div>
            <div style="font-size: 16pt; font-weight: bold; color: #4A154B; margin-top: 2px;">{{ number_format($datosReporte['total_general']) }}</div>
            <div style="font-size: 7pt; color: #94a3b8;">Solicitudes registradas</div>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Puntos Redimidos</div>
            <div style="font-size: 16pt; font-weight: bold; color: #004d35; margin-top: 2px;">{{ number_format($datosReporte['puntos_totales']) }}</div>
            <div style="font-size: 7pt; color: #94a3b8;">Puntos canjeados en total</div>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Recompensas Solicitadas</div>
            <div style="font-size: 16pt; font-weight: bold; color: #2563eb; margin-top: 2px;">{{ count($datosReporte['datos']) }}</div>
            <div style="font-size: 7pt; color: #94a3b8;">Distintos premios canjeados</div>
        </td>
    </tr>
</table>

{{-- Visualización Gráfica Vectorial (Server-side SVG) --}}
@if(!empty($datosReporte['svg_barras']) || !empty($datosReporte['svg_donut']))
<div style="margin-bottom: 20px; page-break-inside: avoid;">
    <div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-bottom: 10px;">
        DISTRIBUCIÓN VISUAL INSTITUCIONAL DE PREMIOS Y RECOMPENSAS CANJEADOS
    </div>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 65%; vertical-align: top; padding-right: 15px; border: none;">
                <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 6px;">Frecuencia de canjes por recompensa:</div>
                @if(!empty($datosReporte['svg_barras_data']))
                    <img src="{{ $datosReporte['svg_barras_data'] }}" style="width: 100%; max-width: 440px; height: auto;" alt="Gráfica de barras de premios canjeados">
                @else
                    {!! $datosReporte['svg_barras'] ?? '' !!}
                @endif
            </td>
            <td style="width: 35%; vertical-align: top; text-align: center; border: none; background-color: #fcfcfd; border-radius: 6px; padding: 6px;">
                <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 6px;">Distribución Relativa:</div>
                @if(!empty($datosReporte['svg_donut_data']))
                    <img src="{{ $datosReporte['svg_donut_data'] }}" style="width: 160px; height: 160px;" alt="Gráfica circular de premios">
                @else
                    {!! $datosReporte['svg_donut'] ?? '' !!}
                @endif
            </td>
        </tr>
    </table>
</div>
@endif

{{-- Tabla de Detalle de Premios y Canjes --}}
<div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; margin-bottom: 6px;">
    Detalle Desglosado por Recompensa
</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 35%;">Recompensa / Premio</th>
            <th style="width: 12%; text-align: center;">Costo Base</th>
            <th style="width: 12%; text-align: center;">Total Canjes</th>
            <th style="width: 12%; text-align: center;">Exitosos</th>
            <th style="width: 12%; text-align: center;">Puntos Totales</th>
            <th style="width: 12%; text-align: right;">% del Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datosReporte['datos'] as $idx => $item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td class="fw-bold">{{ $item->recompensa_titulo }}</td>
                <td class="text-center">{{ number_format($item->costo_puntos_base) }} pts</td>
                <td class="text-center fw-bold">{{ number_format($item->total_canjes) }}</td>
                <td class="text-center">{{ number_format($item->canjes_exitosos) }}</td>
                <td class="text-center fw-bold">{{ number_format($item->puntos_totales_canjeados) }}</td>
                <td class="text-right fw-bold" style="color: #4A154B;">{{ number_format($item->porcentaje_uso, 1) }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No se han registrado canjes con los filtros indicados.</td>
            </tr>
        @endforelse
    </tbody>
    @if(count($datosReporte['datos']) > 0)
    <tfoot>
        <tr style="background-color: #f3e8ff; font-weight: bold;">
            <td colspan="3" style="color: #4A154B;">TOTAL CONSOLIDADO</td>
            <td class="text-center" style="color: #4A154B;">{{ number_format($datosReporte['total_general']) }}</td>
            <td class="text-center">{{ number_format($datosReporte['datos']->sum('canjes_exitosos')) }}</td>
            <td class="text-center" style="color: #004d35;">{{ number_format($datosReporte['puntos_totales']) }}</td>
            <td class="text-right" style="color: #4A154B;">100.0%</td>
        </tr>
    </tfoot>
    @endif
</table>
@endsection

