<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Notificación Institucional — Bienestar UPEMOR')</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 32px 12px;
        }
        .email-card {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 40, 27, 0.08);
        }
        .email-header {
            background: linear-gradient(135deg, #00281b 0%, #004d35 100%);
            padding: 36px 28px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
        }
        .email-header p {
            margin: 6px 0 0;
            font-size: 13px;
            color: #d1fae5;
            letter-spacing: 0.02em;
        }
        .email-body {
            padding: 36px 32px;
        }
        .email-greeting {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .email-paragraph {
            font-size: 15px;
            color: #334155;
            margin-bottom: 20px;
        }
        .email-info-box {
            background-color: #f0fdf4;
            border-left: 4px solid #004d35;
            padding: 16px 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #14532d;
            margin: 24px 0;
        }
        .email-btn-wrapper {
            text-align: center;
            margin: 32px 0;
        }
        .email-btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            padding: 14px 34px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
            transition: background-color 0.2s ease;
        }
        .email-btn:hover {
            background-color: #4338ca;
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            line-height: 1.5;
        }
        .email-footer strong {
            color: #334155;
        }
        .email-privacy-notice {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 12px;
            text-align: justify;
        }
        .email-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .email-table th {
            text-align: left;
            padding: 8px 12px;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            color: #475569;
        }
        .email-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-card">
            <!-- Encabezado degradado verde institucional UPEMOR -->
            <div class="email-header">
                <h1>Bienestar UPEMOR</h1>
                <p>Universidad Politécnica del Estado de Morelos</p>
            </div>

            <!-- Contenido Principal -->
            <div class="email-body">
                @yield('content')
            </div>

            <!-- Pie de página institucional y aviso de privacidad -->
            <div class="email-footer">
                <strong>Universidad Politécnica del Estado de Morelos</strong><br>
                Boulevard Cuauhnáhuac #566, Col. Lomas del Texcal, C.P. 62550, Jiutepec, Morelos.<br>
                <div class="email-privacy-notice">
                    <strong>Aviso de Confidencialidad y Protección de Datos:</strong> Este mensaje y cualquier archivo anexo son para uso exclusivo del destinatario institucional y contienen información protegida por la Ley de Protección de Datos Personales en Posesión de Sujetos Obligados del Estado de Morelos. La información psicológica, académica y médica vinculada a la plataforma Bienestar UPEMOR es confidencial. Si has recibido este mensaje por error, notifícalo de inmediato al remitente y elimínalo sin divulgar su contenido.
                </div>
            </div>
        </div>
    </div>
</body>
</html>

