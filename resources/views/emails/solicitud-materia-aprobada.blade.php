@extends('emails.layout')

@section('title', 'Resolución de Inscripción a Asignatura — Bienestar UPEMOR')

@section('content')
    <div class="email-greeting">¡Hola, {{ $estudianteNombre ?? 'Estudiante' }}!</div>

    @if($estado === 'APROBADA')
        <p class="email-paragraph">
            Nos complace informarte que tu solicitud de inscripción a la asignatura <strong>{{ $asignaturaNombre }}</strong> ({{ $grupoNombre }}) ha sido <strong style="color: #15803d;">APROBADA</strong> por el docente titular.
        </p>
    @else
        <p class="email-paragraph">
            Te informamos que tu solicitud de inscripción a la asignatura <strong>{{ $asignaturaNombre }}</strong> ({{ $grupoNombre }}) ha sido <strong style="color: #b91c1c;">RECHAZADA</strong> por el docente titular.
        </p>
    @endif

    @if(!empty($motivoResolucion))
        <div class="email-info-box" style="{{ $estado === 'APROBADA' ? 'background-color: #f0fdf4; border-left-color: #16a34a;' : 'background-color: #fef2f2; border-left-color: #dc2626; color: #991b1b;' }}">
            <strong>Observaciones del docente:</strong><br>
            {{ $motivoResolucion }}
        </div>
    @endif

    <p class="email-paragraph">
        Accede a tu módulo académico para consultar tu lista oficial de asignaturas vigentes o enviar nuevas solicitudes de cátedra.
    </p>

    <div class="email-btn-wrapper">
        <a href="{{ $urlAction ?? route('alumno.academico.index') }}" class="email-btn" target="_blank" rel="noopener">
            Ver Mi Portal Académico
        </a>
    </div>

    <p class="email-paragraph" style="font-size: 13px; color: #64748b; margin-top: 24px;">
        Atentamente,<br>
        <strong>Coordinación de Bienestar UPEMOR</strong>
    </p>
@endsection

