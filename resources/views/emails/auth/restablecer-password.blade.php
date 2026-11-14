<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña — Bienestar UPEMOR</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.6;
        }
        .container {
            max-width: 580px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.06);
        }
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);
            padding: 32px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .header p {
            margin: 6px 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .body {
            padding: 36px 32px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .paragraph {
            font-size: 15px;
            color: #475569;
            margin-bottom: 20px;
        }
        .btn-wrapper {
            text-align: center;
            margin: 32px 0;
        }
        .btn-action {
            display: inline-block;
            background-color: #7c3aed;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(124, 58, 237, 0.25);
        }
        .info-card {
            background-color: #f5f3ff;
            border-left: 4px solid #7c3aed;
            padding: 16px;
            border-radius: 8px;
            font-size: 13px;
            color: #5b21b6;
            margin: 24px 0;
        }
        .fallback {
            font-size: 12px;
            color: #64748b;
            word-break: break-all;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienestar UPEMOR</h1>
            <p>Plataforma Universitaria de Acompañamiento y Salud Emocional</p>
        </div>
        <div class="body">
            <div class="greeting">Hola, {{ $nombre }}</div>
            <p class="paragraph">
                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta institucional (<strong>{{ $email }}</strong>).
            </p>
            <p class="paragraph">
                Para definir tu nueva credencial de acceso seguro, por favor haz clic en el siguiente botón:
            </p>

            <div class="btn-wrapper">
                <a href="{{ $url }}" class="btn-action" target="_blank" rel="noopener">Restablecer mi Contraseña</a>
            </div>

            <div class="info-card">
                <strong>Información de seguridad:</strong> Este enlace de recuperación es de un solo uso y expirará en <strong>60 minutos</strong>. Si tú no realizaste esta solicitud, puedes ignorar este mensaje; tu cuenta permanece segura y protegida.
            </div>

            <div class="fallback">
                Si el botón superior no funciona en tu cliente de correo, copia y pega el siguiente enlace en tu navegador web:<br>
                <a href="{{ $url }}" style="color: #7c3aed;">{{ $url }}</a>
            </div>
        </div>
        <div class="footer">
            Universidad Politécnica del Estado de Morelos &bull; Boulevard Cuauhnáhuac #566, Col. Lomas del Texcal, Jiutepec, Morelos.<br>
            Este es un correo institucional transaccional automatizado. Por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>

