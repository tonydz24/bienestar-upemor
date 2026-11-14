@extends('emails.layout')

@section('title', 'Nueva Solicitud de Registro de Alumno — Bienestar UPEMOR')

@section('content')
    <div class="email-greeting">Estimado/a Administrador/a,</div>

    <p class="email-paragraph">
        Se ha recibido una nueva solicitud de registro de estudiante en la plataforma institucional <strong>Bienestar UPEMOR</strong>.
    </p>

    <div class="email-info-box">
        <table class="email-table" style="margin: 0;">
            <tr>
                <td style="width: 35%; font-weight: 600; color: #004d35; border: none; padding: 6px 0;">Estudiante:</td>
                <td style="border: none; padding: 6px 0;"><strong>{{ $alumnoNombre }}</strong></td>
            </tr>
            <tr>
                <td style="font-weight: 600; color: #004d35; border: none; padding: 6px 0;">Matrícula:</td>
                <td style="border: none; padding: 6px 0; font-family: monospace;">{{ $matricula }}</td>
            </tr>
            <tr>
                <td style="font-weight: 600; color: #004d35; border: none; padding: 6px 0;">Programa:</td>
                <td style="border: none; padding: 6px 0;">{{ $programaNombre }}</td>
            </tr>
        </table>
    </div>

    <p class="email-paragraph">
        Por favor ingresa al módulo de solicitudes administrativas para validar la adscripción académica y dictaminar el acceso a la plataforma.
    </p>

    <div class="email-btn-wrapper">
        <a href="{{ $urlAction ?? route('admin.solicitudes.index') }}" class="email-btn" target="_blank" rel="noopener">
            Revisar Solicitudes de Alumnos
        </a>
    </div>

    <p class="email-paragraph" style="font-size: 13px; color: #64748b; margin-top: 24px;">
        Atentamente,<br>
        <strong>Coordinación de Bienestar UPEMOR</strong>
    </p>
@endsection

