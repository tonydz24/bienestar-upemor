@extends('admin.reportes.pdf.layout')

@section('title', 'Reporte: Emociones por Programa Educativo')
@section('report-title', 'Reporte Comparativo de Emociones por Programa Educativo')
@section('report-meta', 'Total de Registros Universitarios: ' . number_format($datosReporte['total_general']) . ' • Generado el ' . $datosReporte['fecha_generacion'])

@section('content')
{{-- Dos Visualizaciones Gráficas Institucionales Comparativas --}}
@if(!empty($datosReporte['svg_agrupadas_data']) || !empty($datosReporte['svg_apiladas_data']))
<div style="margin-bottom: 20px; page-break-inside: avoid;">
    <div style="font-size: 9.5pt; font-weight: bold; color: #4A154B; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-bottom: 10px;">
        PANORAMA COMPARATIVO INSTITUCIONAL POR PROGRAMA EDUCATIVO
    </div>
    @if(!empty($datosReporte['svg_agrupadas_data']))
        <div style="text-align: center; margin-bottom: 12px;">
            <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 4px; text-align: left;">Gráfica 1: Frecuencia de estados emocionales por programa:</div>
            <img src="{{ $datosReporte['svg_agrupadas_data'] }}" style="width: 100%; max-width: 620px; height: auto;" alt="Frecuencia por programa">
        </div>
    @endif
    @if(!empty($datosReporte['svg_apiladas_data']))
        <div style="text-align: center; margin-bottom: 12px;">
            <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 4px; text-align: left;">Gráfica 2: Proporción relativa interna (100% apilado por carrera):</div>
            <img src="{{ $datosReporte['svg_apiladas_data'] }}" style="width: 100%; max-width: 620px; height: auto;" alt="Distribución apilada por programa">
        </div>
    @endif
</div>
@endif

@forelse($datosReporte['programas'] as $prog)
    <div style="margin-bottom: 22px; page-break-inside: avoid; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; background: #ffffff;">
        <div style="background-color: #f3e8f8; padding: 7px 12px; border-left: 4px solid #4A154B; border-radius: 4px; margin-bottom: 10px;">
            <table style="width: 100%; border-collapse: collapse; border: none;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <strong style="color: #4A154B; font-size: 10pt;">{{ $prog['siglas'] }} — {{ $prog['nombre'] }}</strong>
                    </td>
                    <td style="border: none; padding: 0; text-align: right; font-size: 8pt; color: #475569;">
                        Total: <strong>{{ number_format($prog['total_programa']) }}</strong> registros (<strong>{{ $prog['porcentaje_global'] }}%</strong> institucional)
                    </td>
                </tr>
            </table>
        </div>

        @if(!empty($prog['svg_barras_data']))
            <div style="text-align: center; margin-bottom: 10px;">
                <img src="{{ $prog['svg_barras_data'] }}" style="width: 100%; max-width: 580px; height: auto;" alt="Gráfica de emociones de {{ $prog['siglas'] }}">
            </div>
        @endif

        <table class="data-table" style="margin-top: 0; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 50%;">Emoción</th>
                    <th style="width: 25%; text-align: center;">Cantidad</th>
                    <th style="width: 25%; text-align: right;">% en la Carrera</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prog['emociones'] as $emo)
                    <tr>
                        <td style="font-weight: 500;">
                            <table style="border-collapse: collapse; display: inline-table; vertical-align: middle; border: none;">
                                <tr>
                                    <td style="padding: 0; vertical-align: middle; border: none; width: 13px;">
                                        <div style="width: 9px; height: 9px; border-radius: 50%; background-color: {{ $emo['codigo_color'] ?: '#64748b' }};"></div>
                                    </td>
                                    <td style="padding: 0 0 0 5px; vertical-align: middle; border: none; white-space: nowrap;">
                                        <span style="color: {{ $emo['codigo_color'] ?: '#1e293b' }}; font-weight: 600;">{{ $emo['emocion'] }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="text-center fw-bold">{{ number_format($emo['cantidad']) }}</td>
                        <td class="text-right fw-bold" style="color: #4A154B;">{{ number_format($emo['porcentaje_programa'], 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p class="text-center" style="margin-top: 30px; color: #64748b;">No se registraron datos en los programas educativos evaluados.</p>
@endforelse
@endsection
