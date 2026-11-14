@extends('admin.reportes.pdf.layout')

@section('title', 'Historial Individual de Bitácoras y Citas')
@section('report-title', 'Historial Individual de Bitácoras Emocionales y Citas de Orientación')
@section('report-meta', 'Expediente Estudiantil • Generado el ' . $datosReporte['fecha_generacion'])

@section('content')
@if($datosReporte['estudiante'])
    <div class="card-box" style="margin-bottom: 15px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; border: none; vertical-align: top;">
                    <strong>Estudiante:</strong> {{ $datosReporte['estudiante']->usuario ? ($datosReporte['estudiante']->usuario->nombre_completo ?? trim($datosReporte['estudiante']->usuario->nombre . ' ' . ($datosReporte['estudiante']->usuario->apellido_paterno ?? '') . ' ' . ($datosReporte['estudiante']->usuario->apellido_materno ?? ''))) : 'N/A' }}<br>
                    <strong>Matrícula:</strong> {{ $datosReporte['estudiante']->matricula }}<br>
                    <strong>Correo:</strong> {{ $datosReporte['estudiante']->usuario?->email ?? 'N/A' }}<br>
                    <strong>Privacidad de Bitácora:</strong> {{ $datosReporte['estudiante']->permite_visualizacion ? 'Permite visualización institucional' : 'Confidencial (descripciones íntimas reservadas)' }}
                </td>
                <td style="width: 50%; border: none; vertical-align: top;">
                    <strong>Programa:</strong> {{ $datosReporte['estudiante']->programaEducativo?->nombre ?? 'N/A' }}<br>
                    <strong>Plan de Estudio:</strong> {{ $datosReporte['estudiante']->plan_estudio_asignado?->nombre ?? $datosReporte['estudiante']->planEstudio?->nombre ?? 'Plan Oficial Vigente' }}<br>
                    <strong>Generación:</strong> {{ $datosReporte['estudiante']->generacion ?? ($datosReporte['estudiante']->anio_ingreso ? 'Gen. ' . $datosReporte['estudiante']->anio_ingreso : 'N/A') }}<br>
                    <strong>Cuatrimestre / Grupo:</strong> {{ $datosReporte['estudiante']->cuatrimestre }}° "{{ $datosReporte['estudiante']->grupo }}" • <strong>Eventos:</strong> {{ $datosReporte['total_registros'] }}
                </td>
            </tr>
        </table>
    </div>

    {{-- Dos Visualizaciones Gráficas --}}
    @if(!empty($datosReporte['svg_linea_tiempo_data']) || !empty($datosReporte['svg_barras_citas_data']))
    <div style="margin-bottom: 15px; page-break-inside: avoid;">
        <table style="width: 100%; border-collapse: collapse; border: none;">
            <tr>
                @if(!empty($datosReporte['svg_linea_tiempo_data']))
                    <td style="width: 60%; vertical-align: top; padding-right: 10px; border: none;">
                        <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 4px;">Gráfica 1: Evolución de Intensidad Emocional (escala 1 a 5):</div>
                        <img src="{{ $datosReporte['svg_linea_tiempo_data'] }}" style="width: 100%; max-width: 380px; height: auto;" alt="Evolución emocional">
                    </td>
                @endif
                @if(!empty($datosReporte['svg_barras_citas_data']))
                    <td style="width: 40%; vertical-align: top; border: none;">
                        <div style="font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 4px;">Gráfica 2: Citas de Orientación:</div>
                        <img src="{{ $datosReporte['svg_barras_citas_data'] }}" style="width: 100%; max-width: 250px; height: auto;" alt="Citas por estado">
                    </td>
                @endif
            </tr>
        </table>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Fecha</th>
                <th style="width: 18%;">Tipo Evento</th>
                <th style="width: 20%;">Emoción / Modalidad</th>
                <th style="width: 30%;">Detalle / Contexto</th>
                <th style="width: 20%;">Estado / Nota</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datosReporte['historial'] as $row)
                <tr>
                    <td class="small">{{ $row['fecha_formateada'] }}</td>
                    <td>
                        <span class="badge {{ $row['tipo'] === 'Bitácora Emocional' ? 'badge-positive' : 'badge-warning' }}">
                            {{ $row['tipo'] }}
                        </span>
                    </td>
                    <td class="fw-bold">{{ $row['emocion_evento'] }}</td>
                    <td class="small">{{ $row['detalle'] }}</td>
                    <td class="small">{{ $row['estado'] }} • {{ $row['observaciones'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No se encontraron eventos registrados para este alumno.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@else
    <p class="text-center" style="margin-top: 30px;">Debes seleccionar un alumno para emitir este informe.</p>
@endif
@endsection

