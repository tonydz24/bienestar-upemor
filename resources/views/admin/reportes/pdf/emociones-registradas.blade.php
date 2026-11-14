@extends('admin.reportes.pdf.layout')

@section('title', 'Reporte: Número y Porcentaje de Emociones Registradas')
@section('report-title', 'Reporte de Número y Porcentaje de Emociones Registradas')
@section('report-meta', 'Total de Registros Analizados: ' . number_format($datosReporte['total_general']) . ' • Generado el ' . $datosReporte['fecha_generacion'])

@section('content')
{{-- Resumen Ejecutivo en Tarjetas --}}
<table style="width: 100%; border-collapse: collapse; margin-bottom: 18px;">
    <tr>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Total Registros</div>
            <div style="font-size: 16pt; font-weight: bold; color: #4A154B; margin-top: 2px;">{{ number_format($datosReporte['total_general']) }}</div>
            <div style="font-size: 7pt; color: #94a3b8;">Bitácoras en el periodo</div>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Emociones Observadas</div>
            <div style="font-size: 16pt; font-weight: bold; color: #059669; margin-top: 2px;">{{ $datosReporte['emociones_observadas'] }} <span style="font-size: 9pt; font-weight: normal; color: #64748b;">de {{ $datosReporte['emociones_totales'] }}</span></div>
            <div style="font-size: 7pt; color: #94a3b8;">Del catálogo institucional</div>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Emoción Preponderante</div>
            <div style="font-size: 13pt; font-weight: bold; color: #1e293b; margin-top: 4px; white-space: nowrap; overflow: hidden;">{{ $datosReporte['emocion_preponderante'] }}</div>
            <div style="font-size: 7pt; color: #94a3b8;">Mayor frecuencia observada</div>
        </td>
    </tr>
</table>

{{-- Visualización Gráfica Vectorial (Server-side SVG) --}}
@if(!empty($datosReporte['svg_barras']) || !empty($datosReporte['svg_donut']))
<div style="margin-bottom: 20px; page-break-inside: avoid;">
    <div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-bottom: 10px;">
        DISTRIBUCIÓN VISUAL INSTITUCIONAL DE EMOCIONES
    </div>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 62%; vertical-align: top; padding-right: 15px; border: none;">
                <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 6px;">Gráfica 2: Intensidad promedio autoevaluada por emoción (escala 1 a 5):</div>
                @if(!empty($datosReporte['svg_intensidad_data']))
                    <img src="{{ $datosReporte['svg_intensidad_data'] }}" style="width: 100%; max-width: 440px; height: auto;" alt="Gráfica de intensidad media">
                @elseif(!empty($datosReporte['svg_barras_data']))
                    <img src="{{ $datosReporte['svg_barras_data'] }}" style="width: 100%; max-width: 440px; height: auto;" alt="Gráfica de barras de emociones">
                @else
                    {!! $datosReporte['svg_intensidad'] ?? $datosReporte['svg_barras'] ?? '' !!}
                @endif
            </td>
            <td style="width: 38%; vertical-align: top; text-align: center; border: none; background-color: #fcfcfd; border-radius: 6px; padding: 6px;">
                <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 6px;">Gráfica 1: Proporción Relativa:</div>
                @if(!empty($datosReporte['svg_donut_data']))
                    <img src="{{ $datosReporte['svg_donut_data'] }}" style="width: 160px; height: 160px;" alt="Gráfica circular de emociones">
                @else
                    {!! $datosReporte['svg_donut'] ?? '' !!}
                @endif
            </td>
        </tr>
    </table>
</div>
@endif

{{-- Tabla de Frecuencias y Porcentajes --}}
<div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-bottom: 6px;">
    TABLA DETALLADA DE FRECUENCIA Y DISTRIBUCIÓN PORCENTUAL
</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 6%; text-align: center;">#</th>
            <th style="width: 36%;">Emoción</th>
            <th style="width: 22%; text-align: center;">Intensidad promedio (escala 1–5)</th>
            <th style="width: 18%; text-align: center;">Total Registros</th>
            <th style="width: 18%; text-align: right;">Porcentaje (%)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datosReporte['datos'] as $idx => $item)
            <tr>
                <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                <td>
                    <table style="border-collapse: collapse; display: inline-table; vertical-align: middle; border: none;">
                        <tr>
                            <td style="padding: 0; vertical-align: middle; border: none; width: 14px;">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background-color: {{ $item->codigo_color ?: '#64748b' }};"></div>
                            </td>
                            <td style="padding: 0 0 0 6px; vertical-align: middle; border: none; white-space: nowrap;">
                                <strong style="color: {{ $item->codigo_color ?: '#1e293b' }};">{{ $item->emocion_nombre }}</strong>
                                @if($item->total === 0)
                                    <span style="font-size: 7pt; color: #94a3b8; font-weight: normal; margin-left: 4px;">(Sin registros)</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="text-center">
                    @if($item->intensidad_promedio !== null)
                        <strong>{{ number_format($item->intensidad_promedio, 2) }}</strong> / 5
                    @else
                        <span style="color: #94a3b8; font-style: italic; font-size: 7.5pt;">Sin registros</span>
                    @endif
                </td>
                <td class="text-center fw-bold">{{ number_format($item->total) }}</td>
                <td class="text-right fw-bold" style="color: #4A154B;">{{ number_format($item->porcentaje, 2) }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center" style="padding: 16px; color: #64748b;">No se encontraron registros de bitácoras para los filtros seleccionados.</td>
            </tr>
        @endforelse
    </tbody>
    @if($datosReporte['total_general'] > 0)
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="3" style="text-align: right; padding-right: 12px; font-size: 8.5pt;">TOTAL GENERAL:</td>
                <td class="text-center" style="font-size: 9pt;">{{ number_format($datosReporte['total_general']) }}</td>
                <td class="text-right" style="color: #4A154B; font-size: 9pt;">100.00%</td>
            </tr>
        </tfoot>
    @endif
</table>

<div style="margin-top: 15px; font-size: 8pt; color: #64748b; line-height: 1.45; border-top: 1px solid #e2e8f0; padding-top: 8px;">
    <strong>Nota técnica y metodológica:</strong> La columna <em>Intensidad promedio (escala 1–5)</em> refleja el nivel medio autoevaluado por los estudiantes en sus bitácoras. Cuando una emoción no cuenta con registros en el periodo seleccionado, se indica claramente <em>«Sin registros»</em> para evitar distorsiones aritméticas. Las emociones se presentan como estados afectivos y psicológicos naturales sin juicios morales de valor, con fines estrictamente preventivos e institucionales.
</div>
@endsection
