@extends('admin.reportes.pdf.layout')

@section('title', 'Resultados Generales de Tests Psicométricos')
@section('report-title', 'Reporte General de Resultados de Tests y Tamizajes Psicométricos')
@section('report-meta', 'Total Evaluaciones: ' . number_format($datosReporte['total_general']) . ' • Alertas Críticas: ' . number_format($datosReporte['total_alertas_criticas']) . ' • Fecha: ' . $datosReporte['fecha_generacion'])

@section('content')
{{-- Resumen Ejecutivo en Tarjetas --}}
<table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
    <tr>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Total Evaluaciones</div>
            <div style="font-size: 16pt; font-weight: bold; color: #4A154B; margin-top: 2px;">{{ number_format($datosReporte['total_general']) }}</div>
            <div style="font-size: 7pt; color: #94a3b8;">Intentos finalizados</div>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Alertas Críticas</div>
            <div style="font-size: 16pt; font-weight: bold; color: {{ ($datosReporte['total_alertas_criticas'] ?? 0) > 0 ? '#ef4444' : '#10b981' }}; margin-top: 2px;">{{ number_format($datosReporte['total_alertas_criticas']) }}</div>
            <div style="font-size: 7pt; color: #94a3b8;">Reactivos de riesgo afirmativos</div>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 32%; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center;">
            <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Niveles Detectados</div>
            <div style="font-size: 16pt; font-weight: bold; color: #2563eb; margin-top: 2px;">{{ count($datosReporte['distribucion_niveles'] ?? []) }}</div>
            <div style="font-size: 7pt; color: #94a3b8;">Clasificaciones baremadas</div>
        </td>
    </tr>
</table>

{{-- Visualización Gráfica Vectorial (Server-side SVG con 2 visualizaciones) --}}
@if(!empty($datosReporte['svg_barras_niveles_data']) || !empty($datosReporte['svg_barras_data']) || !empty($datosReporte['svg_barras_instrumentos_data']))
<div style="margin-bottom: 20px; page-break-inside: avoid;">
    <div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-bottom: 10px;">
        DISTRIBUCIÓN VISUAL DE RESULTADOS Y NIVELES DE RIESGO
    </div>
    <table style="width: 100%; border-collapse: collapse; border: none;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8px; border: none;">
                <div style="font-size: 7.5pt; font-weight: bold; color: #475569; margin-bottom: 4px;">Gráfica 1: Niveles de riesgo psicométrico:</div>
                @if(!empty($datosReporte['svg_barras_niveles_data']))
                    <img src="{{ $datosReporte['svg_barras_niveles_data'] }}" style="width: 100%; max-width: 320px; height: auto;" alt="Gráfica de niveles de tests psicométricos">
                @elseif(!empty($datosReporte['svg_barras_data']))
                    <img src="{{ $datosReporte['svg_barras_data'] }}" style="width: 100%; max-width: 320px; height: auto;" alt="Gráfica de niveles de tests psicométricos">
                @else
                    {!! $datosReporte['svg_barras'] ?? '' !!}
                @endif
            </td>
            <td style="width: 50%; vertical-align: top; border: none;">
                <div style="font-size: 7.5pt; font-weight: bold; color: #475569; margin-bottom: 4px;">Gráfica 2: Aplicaciones por instrumento:</div>
                @if(!empty($datosReporte['svg_barras_instrumentos_data']))
                    <img src="{{ $datosReporte['svg_barras_instrumentos_data'] }}" style="width: 100%; max-width: 320px; height: auto;" alt="Gráfica de aplicaciones por instrumento">
                @else
                    {!! $datosReporte['svg_barras_instrumentos'] ?? '' !!}
                @endif
            </td>
        </tr>
    </table>
</div>
@endif

{{-- Tabla de Frecuencias por Nivel de Riesgo --}}
@if(!empty($datosReporte['distribucion_niveles']) && count($datosReporte['distribucion_niveles']) > 0)
<div style="margin-bottom: 18px; page-break-inside: avoid;">
    <div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; margin-bottom: 6px;">
        Resumen de Tamizaje por Clasificación
    </div>
    <table class="data-table" style="margin-top: 0; margin-bottom: 0;">
        <thead>
            <tr>
                <th style="width: 50%;">Clasificación / Nivel Obtenido</th>
                <th style="width: 25%; text-align: center;">Cantidad</th>
                <th style="width: 25%; text-align: right;">% del Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datosReporte['distribucion_niveles'] as $nivel => $tot)
                @php
                    $pct = ($datosReporte['total_general'] ?? 0) > 0 ? round(($tot / $datosReporte['total_general']) * 100, 1) : 0;
                @endphp
                <tr>
                    <td class="fw-bold">{{ $nivel ?: 'Sin clasificar' }}</td>
                    <td class="text-center fw-bold">{{ number_format($tot) }}</td>
                    <td class="text-right fw-bold" style="color: #4A154B;">{{ number_format($pct, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Tabla de Evaluaciones Individuales --}}
<div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; margin-bottom: 6px;">
    Detalle Cronológico de Aplicaciones
</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 10%;">Folio</th>
            <th style="width: 14%;">Fecha</th>
            <th style="width: 24%;">Test / Instrumento</th>
            <th style="width: 12%;">Matrícula</th>
            <th style="width: 18%;">Programa Educativo</th>
            <th style="width: 8%; text-align: center;">Puntaje</th>
            <th style="width: 14%; text-align: center;">Clasificación</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datosReporte['intentos'] as $item)
            <tr>
                <td class="small fw-bold">#INT-{{ $item['intento_id'] }}</td>
                <td class="small">{{ $item['fecha_finalizacion'] }}</td>
                <td>{{ $item['instrumento_nombre'] }}</td>
                <td class="small">{{ $item['matricula'] }}</td>
                <td class="small">{{ $item['programa_nombre'] }}</td>
                <td class="text-center fw-bold">{{ $item['puntuacion_total'] }}</td>
                <td class="text-center">
                    <span class="badge {{ str_contains(strtolower($item['nivel_resultado']), 'alto') || str_contains(strtolower($item['nivel_resultado']), 'severo') ? 'badge-danger' : (str_contains(strtolower($item['nivel_resultado']), 'medio') || str_contains(strtolower($item['nivel_resultado']), 'leve') ? 'badge-warning' : 'badge-positive') }}">
                        {{ $item['nivel_resultado'] }}
                    </span>
                    @if($item['tiene_alerta_critica'])
                        <div style="color: #dc3545; font-size: 7.5pt; font-weight: bold; margin-top: 2px;">ALERTA CRÍTICA</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No se encontraron aplicaciones psicométricas con los filtros indicados.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

