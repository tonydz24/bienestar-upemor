<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Reporte Institucional UPEMOR')</title>
    <style>
        @page {
            margin: 2.5cm 1.5cm 2cm 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #212529;
            margin: 0;
            padding: 0;
        }
        .header {
            position: fixed;
            top: -2cm;
            left: 0;
            right: 0;
            height: 1.8cm;
            border-bottom: 2px solid #4A154B;
            padding-bottom: 4px;
        }
        .footer {
            position: fixed;
            bottom: -1.5cm;
            left: 0;
            right: 0;
            height: 1cm;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 8pt;
            color: #6c757d;
            padding-top: 5px;
        }
        .inst-title {
            color: #4A154B;
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .inst-subtitle {
            font-size: 8.5pt;
            color: #495057;
            margin: 2px 0 0 0;
        }
        .report-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a1a;
            margin: 10px 0 4px 0;
            text-align: center;
        }
        .report-meta {
            text-align: center;
            font-size: 8.5pt;
            color: #6c757d;
            margin-bottom: 15px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #4A154B;
            color: #ffffff;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #4A154B;
        }
        table.data-table td {
            font-size: 8.5pt;
            padding: 5px 8px;
            border: 1px solid #e9ecef;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7.5pt;
            font-weight: bold;
        }
        .badge-positive {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #664d03;
        }
        .badge-danger {
            background-color: #f8d7da;
            color: #842029;
        }
        .card-box {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
            background-color: #fdfdfd;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 70%; border: none; vertical-align: middle;">
                    <div class="inst-title">Universidad Politécnica del Estado de Morelos</div>
                    <div class="inst-subtitle">Plataforma Institucional Bienestar UPEMOR • Dirección de Acompañamiento y Tutoría</div>
                </td>
                <td style="width: 30%; border: none; text-align: right; vertical-align: middle;">
                    <div style="font-size: 8pt; color: #6c757d;">
                        <strong>Folio:</strong> REP-{{ date('Ymd-His') }}<br>
                        <strong>Fecha:</strong> {{ date('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Documento oficial emitido por el Sistema Institucional Bienestar UPEMOR • Confidencial y de Uso Exclusivo • Página <span class="page-number"></span>
    </div>

    <div class="report-title">@yield('report-title')</div>
    <div class="report-meta">@yield('report-meta')</div>

    @yield('content')
</body>
</html>

