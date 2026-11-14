@extends('emails.layout')

@section('title', 'Solicitud de Canje de Recompensa Académica — Bienestar UPEMOR')

@section('content')
    <div class="email-greeting">¡Hola, {{ $docenteNombre ?? 'Docente' }}!</div>

    <p class="email-paragraph">
        El estudiante <strong>{{ $alumnoNombre }}</strong> ha canjeado un incentivo académico por sus actividades de bienestar emocional y ha solicitado hacerlo efectivo en tu asignatura.
    </p>

    <div class="email-info-box">
        <table class="email-table" style="margin: 0;">
            <tr>
                <td style="width: 35%; font-weight: 600; color: #004d35; border: none; padding: 6px 0;">Beneficio:</td>
                <td style="border: none; padding: 6px 0;"><strong>{{ $recompensaNombre }}</strong></td>
            </tr>
            <tr>
                <td style="font-weight: 600; color: #004d35; border: none; padding: 6px 0;">Asignatura:</td>
                <td style="border: none; padding: 6px 0;">{{ $materiaNombre }}</td>
            </tr>
            <tr>
                <td style="font-weight: 600; color: #004d35; border: none; padding: 6px 0;">Código de Canje:</td>
                <td style="border: none; padding: 6px 0;"><code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: bold; color: #0f172a;">{{ $codigoBeneficio }}</code></td>
            </tr>
        </table>
    </div>

    <p class="email-paragraph">
        Por favor ingresa al módulo docente para verificar la validez del código y registrar la aplicación del beneficio en los registros de evaluación continua de la materia.
    </p>

    <div class="email-btn-wrapper">
        <a href="{{ $urlAction ?? route('docente.recompensas.index', ['tab' => 'solicitudes']) }}" class="email-btn" target="_blank" rel="noopener">
            Gestionar Canje en Plataforma
        </a>
    </div>

    <p class="email-paragraph" style="font-size: 13px; color: #64748b; margin-top: 24px;">
        Atentamente,<br>
        <strong>Coordinación de Bienestar UPEMOR</strong>
    </p>
@endsection

