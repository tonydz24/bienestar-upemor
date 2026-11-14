<?php

namespace App\Services\Reportes;

use App\Models\Emocion;

class ReporteGraficaService
{
    /**
     * Paleta oficial institucional de emociones UPEMOR.
     */
    public static function colorParaEmocion(string $nombre): string
    {
        return Emocion::colorInstitucional($nombre);
    }

    /**
     * Genera un gráfico de barras horizontales vectorial en SVG puro compatible con DomPDF.
     *
     * @param iterable $datos Colección o array de objetos/arrays con (emocion_nombre, total, porcentaje, codigo_color)
     * @param int $ancho Ancho del SVG en px
     * @param int $altoFila Altura de cada fila en px
     * @return string Código SVG listo para incrustar en HTML/PDF
     */
    public function generarBarrasHorizontalesSvg(iterable $datos, int $ancho = 620, int $altoFila = 34): string
    {
        $items = collect($datos)->values();
        $totalItems = $items->count();

        if ($totalItems === 0) {
            return '<svg width="' . $ancho . '" height="60" xmlns="http://www.w3.org/2000/svg"><text x="' . ($ancho / 2) . '" y="35" text-anchor="middle" fill="#9ca3af" font-size="12" font-family="Helvetica, Arial, sans-serif">Sin datos para graficar</text></svg>';
        }

        $paddingTop = 15;
        $paddingBottom = 15;
        $altoTotal = ($totalItems * $altoFila) + $paddingTop + $paddingBottom;

        $anchoEtiqueta = 110;
        $anchoValores = 95;
        $anchoBarraMax = $ancho - $anchoEtiqueta - $anchoValores - 30;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $altoTotal . '" viewBox="0 0 ' . $ancho . ' ' . $altoTotal . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';
        $svg .= '<defs>';
        $svg .= '<filter id="shadow" x="-5%" y="-5%" width="110%" height="110%"><feDropShadow dx="0" dy="1" stdDeviation="1" flood-opacity="0.1"/></filter>';
        $svg .= '</defs>';

        foreach ($items as $idx => $item) {
            $nombre = is_array($item) ? ($item['emocion_nombre'] ?? $item['emocion'] ?? 'Emoción') : ($item->emocion_nombre ?? 'Emoción');
            $total = is_array($item) ? ($item['total'] ?? 0) : ($item->total ?? 0);
            $porcentaje = is_array($item) ? (float) ($item['porcentaje'] ?? 0) : (float) ($item->porcentaje ?? 0);
            $color = is_array($item) ? ($item['codigo_color'] ?? self::colorParaEmocion($nombre)) : ($item->codigo_color ?? self::colorParaEmocion($nombre));

            $yBase = $paddingTop + ($idx * $altoFila);
            $yTexto = $yBase + 18;
            $yBarra = $yBase + 6;
            $alturaBarra = 16;

            // Ancho proporcional de la barra (0% a 100%)
            $anchoCalculado = max(0, min($anchoBarraMax, ($porcentaje / 100) * $anchoBarraMax));

            // 1. Etiqueta de la emoción con círculo de color
            $svg .= '<circle cx="14" cy="' . ($yBarra + 8) . '" r="5" fill="' . htmlspecialchars($color, ENT_QUOTES) . '" />';
            $svg .= '<text x="26" y="' . $yTexto . '" fill="#1f2937" font-size="11" font-weight="bold">' . htmlspecialchars($nombre, ENT_QUOTES) . '</text>';

            // 2. Fondo de la barra (pista gris claro)
            $xBarra = $anchoEtiqueta + 15;
            $svg .= '<rect x="' . $xBarra . '" y="' . $yBarra . '" width="' . $anchoBarraMax . '" height="' . $alturaBarra . '" rx="4" ry="4" fill="#f3f4f6" />';

            // 3. Barra de progreso coloreada
            if ($anchoCalculado > 0) {
                $svg .= '<rect x="' . $xBarra . '" y="' . $yBarra . '" width="' . round($anchoCalculado, 1) . '" height="' . $alturaBarra . '" rx="4" ry="4" fill="' . htmlspecialchars($color, ENT_QUOTES) . '" />';
            }

            // 4. Texto del valor y porcentaje a la derecha
            $xValor = $xBarra + $anchoBarraMax + 12;
            $textoValor = number_format($total) . ' (' . number_format($porcentaje, 1) . '%)';
            $svg .= '<text x="' . $xValor . '" y="' . $yTexto . '" fill="#374151" font-size="11" font-weight="' . ($total > 0 ? 'bold' : 'normal') . '">' . htmlspecialchars($textoValor, ENT_QUOTES) . '</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Genera un gráfico Donut (circular con centro hueco) en SVG vectorial puro compatible con DomPDF.
     *
     * @param iterable $datos Colección o array de objetos/arrays con (emocion_nombre, total, porcentaje, codigo_color)
     * @param int $tamano Tamaño del viewport cuadrado (ancho = alto)
     * @return string Código SVG
     */
    public function generarDonutSvg(iterable $datos, int $tamano = 220): string
    {
        $items = collect($datos)->filter(fn($d) => (is_array($d) ? ($d['total'] ?? 0) : ($d->total ?? 0)) > 0)->values();
        $totalGeneral = $items->sum(fn($d) => is_array($d) ? ($d['total'] ?? 0) : ($d->total ?? 0));

        $centro = $tamano / 2;
        $radioExt = ($tamano / 2) - 15;
        $radioInt = $radioExt * 0.55;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $tamano . '" height="' . $tamano . '" viewBox="0 0 ' . $tamano . ' ' . $tamano . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';

        if ($totalGeneral <= 0 || $items->isEmpty()) {
            // Círculo neutro de estado vacío
            $svg .= '<circle cx="' . $centro . '" cy="' . $centro . '" r="' . $radioExt . '" fill="#f3f4f6" stroke="#e5e7eb" stroke-width="2"/>';
            $svg .= '<circle cx="' . $centro . '" cy="' . $centro . '" r="' . $radioInt . '" fill="#ffffff" stroke="#e5e7eb" stroke-width="1"/>';
            $svg .= '<text x="' . $centro . '" y="' . ($centro + 4) . '" text-anchor="middle" fill="#9ca3af" font-size="10">Sin datos</text>';
            $svg .= '</svg>';
            return $svg;
        }

        // Si solo hay un elemento con 100%
        if ($items->count() === 1) {
            $item = $items->first();
            $color = is_array($item) ? ($item['codigo_color'] ?? '#7c3aed') : ($item->codigo_color ?? '#7c3aed');
            $svg .= '<circle cx="' . $centro . '" cy="' . $centro . '" r="' . $radioExt . '" fill="' . htmlspecialchars($color, ENT_QUOTES) . '" />';
            $svg .= '<circle cx="' . $centro . '" cy="' . $centro . '" r="' . $radioInt . '" fill="#ffffff" />';
            $svg .= '<text x="' . $centro . '" y="' . ($centro - 2) . '" text-anchor="middle" fill="#111827" font-size="14" font-weight="bold">' . number_format($totalGeneral) . '</text>';
            $svg .= '<text x="' . $centro . '" y="' . ($centro + 13) . '" text-anchor="middle" fill="#6b7280" font-size="9">Total</text>';
            $svg .= '</svg>';
            return $svg;
        }

        $anguloInicio = -90.0; // Comenzar en las 12 en punto

        foreach ($items as $item) {
            $total = is_array($item) ? ($item['total'] ?? 0) : ($item->total ?? 0);
            $nombre = is_array($item) ? ($item['emocion_nombre'] ?? 'Emoción') : ($item->emocion_nombre ?? 'Emoción');
            $color = is_array($item) ? ($item['codigo_color'] ?? self::colorParaEmocion($nombre)) : ($item->codigo_color ?? self::colorParaEmocion($nombre));

            $fraccion = $total / $totalGeneral;
            $amplitud = $fraccion * 360.0;
            $anguloFin = $anguloInicio + $amplitud;

            // Coordenadas trigonométricas
            $radInicio = deg2rad($anguloInicio);
            $radFin = deg2rad($anguloFin);

            $x1Ext = $centro + ($radioExt * cos($radInicio));
            $y1Ext = $centro + ($radioExt * sin($radInicio));
            $x2Ext = $centro + ($radioExt * cos($radFin));
            $y2Ext = $centro + ($radioExt * sin($radFin));

            $x1Int = $centro + ($radioInt * cos($radInicio));
            $y1Int = $centro + ($radioInt * sin($radInicio));
            $x2Int = $centro + ($radioInt * cos($radFin));
            $y2Int = $centro + ($radioInt * sin($radFin));

            $largeArc = ($amplitud > 180.0) ? 1 : 0;

            // Trazo Donut: desde borde exterior inicio, arco hasta fin exterior, línea hacia centro fin interior, arco inverso hacia inicio interior, cerrar
            $d = sprintf(
                'M %.2f %.2f A %.2f %.2f 0 %d 1 %.2f %.2f L %.2f %.2f A %.2f %.2f 0 %d 0 %.2f %.2f Z',
                $x1Ext, $y1Ext,
                $radioExt, $radioExt,
                $largeArc,
                $x2Ext, $y2Ext,
                $x2Int, $y2Int,
                $radioInt, $radioInt,
                $largeArc,
                $x1Int, $y1Int
            );

            $svg .= '<path d="' . $d . '" fill="' . htmlspecialchars($color, ENT_QUOTES) . '" stroke="#ffffff" stroke-width="1.5" />';

            $anguloInicio = $anguloFin;
        }

        // Centro blanco y texto totalizador
        $svg .= '<circle cx="' . $centro . '" cy="' . $centro . '" r="' . ($radioInt - 1) . '" fill="#ffffff" />';
        $svg .= '<text x="' . $centro . '" y="' . ($centro - 2) . '" text-anchor="middle" fill="#111827" font-size="14" font-weight="bold">' . number_format($totalGeneral) . '</text>';
        $svg .= '<text x="' . $centro . '" y="' . ($centro + 13) . '" text-anchor="middle" fill="#6b7280" font-size="9">Registros</text>';
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Convierte el código SVG en URI data:image/svg+xml;base64,... para incrustación directa en <img>.
     */
    public function svgDataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * REPORTE 1 - GRÁFICA 2: Genera un gráfico de barras de Intensidad Media (escala 1 a 5) por emoción.
     */
    public function generarBarrasIntensidadSvg(iterable $datos, int $ancho = 620, int $altoFila = 34): string
    {
        $items = collect($datos)->values();
        $totalItems = $items->count();

        if ($totalItems === 0) {
            return '<svg width="' . $ancho . '" height="60" xmlns="http://www.w3.org/2000/svg"><text x="' . ($ancho / 2) . '" y="35" text-anchor="middle" fill="#9ca3af" font-size="12" font-family="Helvetica, Arial, sans-serif">Sin datos de intensidad</text></svg>';
        }

        $paddingTop = 20;
        $paddingBottom = 20;
        $altoTotal = ($totalItems * $altoFila) + $paddingTop + $paddingBottom;

        $anchoEtiqueta = 110;
        $anchoValores = 110;
        $anchoBarraMax = $ancho - $anchoEtiqueta - $anchoValores - 30;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $altoTotal . '" viewBox="0 0 ' . $ancho . ' ' . $altoTotal . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';
        
        // Líneas guía de fondo para escala 1 a 5
        for ($i = 1; $i <= 5; $i++) {
            $xGuia = $anchoEtiqueta + 15 + (($i / 5.0) * $anchoBarraMax);
            $svg .= '<line x1="' . $xGuia . '" y1="' . ($paddingTop - 5) . '" x2="' . $xGuia . '" y2="' . ($altoTotal - $paddingBottom + 5) . '" stroke="#e5e7eb" stroke-dasharray="2,2" stroke-width="1"/>';
            $svg .= '<text x="' . $xGuia . '" y="' . ($paddingTop - 8) . '" fill="#9ca3af" font-size="9" text-anchor="middle">' . $i . '.0</text>';
        }

        foreach ($items as $idx => $item) {
            $nombre = is_array($item) ? ($item['emocion_nombre'] ?? 'Emoción') : ($item->emocion_nombre ?? 'Emoción');
            $intensidad = is_array($item) ? ($item['intensidad_promedio'] ?? null) : ($item->intensidad_promedio ?? null);
            $color = is_array($item) ? ($item['codigo_color'] ?? self::colorParaEmocion($nombre)) : ($item->codigo_color ?? self::colorParaEmocion($nombre));

            $yBase = $paddingTop + ($idx * $altoFila);
            $yTexto = $yBase + 18;
            $yBarra = $yBase + 6;
            $alturaBarra = 16;

            $svg .= '<circle cx="14" cy="' . ($yBarra + 8) . '" r="5" fill="' . htmlspecialchars($color, ENT_QUOTES) . '" />';
            $svg .= '<text x="26" y="' . $yTexto . '" fill="#1f2937" font-size="11" font-weight="bold">' . htmlspecialchars($nombre, ENT_QUOTES) . '</text>';

            $xBarra = $anchoEtiqueta + 15;
            $svg .= '<rect x="' . $xBarra . '" y="' . $yBarra . '" width="' . $anchoBarraMax . '" height="' . $alturaBarra . '" rx="4" fill="#f3f4f6" />';

            if ($intensidad !== null && $intensidad > 0) {
                $anchoCalculado = max(0, min($anchoBarraMax, ($intensidad / 5.0) * $anchoBarraMax));
                $svg .= '<rect x="' . $xBarra . '" y="' . $yBarra . '" width="' . round($anchoCalculado, 1) . '" height="' . $alturaBarra . '" rx="4" fill="' . htmlspecialchars($color, ENT_QUOTES) . '" />';
                $textoValor = number_format($intensidad, 2) . ' / 5.0';
            } else {
                $textoValor = 'Sin registros';
            }

            $xValor = $xBarra + $anchoBarraMax + 12;
            $svg .= '<text x="' . $xValor . '" y="' . $yTexto . '" fill="' . ($intensidad !== null ? '#111827' : '#9ca3af') . '" font-size="11" font-weight="' . ($intensidad !== null ? 'bold' : 'normal') . '">' . htmlspecialchars($textoValor, ENT_QUOTES) . '</text>';
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * REPORTE 2 - GRÁFICA 1: Barras agrupadas comparativas por programa educativo.
     */
    public function generarBarrasAgrupadasProgramaSvg(array $programas, int $ancho = 680, int $alto = 280): string
    {
        if (empty($programas)) {
            return '<svg width="' . $ancho . '" height="' . $alto . '" xmlns="http://www.w3.org/2000/svg"><text x="' . ($ancho / 2) . '" y="' . ($alto / 2) . '" text-anchor="middle" fill="#9ca3af" font-size="12" font-family="Helvetica, Arial, sans-serif">Sin datos comparativos</text></svg>';
        }

        $paddingLeft = 50;
        $paddingRight = 20;
        $paddingTop = 35;
        $paddingBottom = 55;

        $anchoUtil = $ancho - $paddingLeft - $paddingRight;
        $altoUtil = $alto - $paddingTop - $paddingBottom;

        $maxVal = 1;
        foreach ($programas as $p) {
            foreach ($p['emociones'] as $e) {
                $maxVal = max($maxVal, (int) $e['cantidad']);
            }
        }
        $maxVal = max(5, (int) (ceil($maxVal / 5.0) * 5));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $alto . '" viewBox="0 0 ' . $ancho . ' ' . $alto . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';

        // Título de la gráfica
        $svg .= '<text x="' . ($ancho / 2) . '" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#374151">Frecuencia de Emociones por Programa Educativo</text>';

        // Guías horizontales
        for ($i = 0; $i <= 4; $i++) {
            $val = round(($maxVal / 4.0) * $i);
            $yGuia = $paddingTop + $altoUtil - (($i / 4.0) * $altoUtil);
            $svg .= '<line x1="' . $paddingLeft . '" y1="' . $yGuia . '" x2="' . ($paddingLeft + $anchoUtil) . '" y2="' . $yGuia . '" stroke="#e5e7eb" stroke-dasharray="2,2"/>';
            $svg .= '<text x="' . ($paddingLeft - 8) . '" y="' . ($yGuia + 4) . '" text-anchor="end" font-size="9" fill="#9ca3af">' . $val . '</text>';
        }

        $numProgs = count($programas);
        $anchoGrupo = $anchoUtil / max(1, $numProgs);

        foreach ($programas as $pIdx => $prog) {
            $xGrupoBase = $paddingLeft + ($pIdx * $anchoGrupo);
            $emociones = array_values($prog['emociones']);
            $numEmos = count($emociones);
            $anchoBarra = max(4, min(16, ($anchoGrupo - 14) / max(1, $numEmos)));

            foreach ($emociones as $eIdx => $emo) {
                $xBarra = $xGrupoBase + 7 + ($eIdx * $anchoBarra);
                $alturaBarra = ($emo['cantidad'] / $maxVal) * $altoUtil;
                $yBarra = $paddingTop + $altoUtil - $alturaBarra;
                $color = $emo['codigo_color'] ?: self::colorParaEmocion($emo['emocion']);

                if ($alturaBarra > 0) {
                    $svg .= '<rect x="' . $xBarra . '" y="' . $yBarra . '" width="' . ($anchoBarra - 1) . '" height="' . $alturaBarra . '" fill="' . htmlspecialchars($color, ENT_QUOTES) . '" rx="2"/>';
                }
            }

            // Etiqueta del programa
            $siglas = htmlspecialchars($prog['siglas'] ?? substr($prog['nombre'], 0, 4), ENT_QUOTES);
            $svg .= '<text x="' . ($xGrupoBase + ($anchoGrupo / 2)) . '" y="' . ($paddingTop + $altoUtil + 18) . '" text-anchor="middle" font-size="10" font-weight="bold" fill="#4b5563">' . $siglas . '</text>';
            $svg .= '<text x="' . ($xGrupoBase + ($anchoGrupo / 2)) . '" y="' . ($paddingTop + $altoUtil + 32) . '" text-anchor="middle" font-size="8" fill="#9ca3af">(' . $prog['total_programa'] . ' reg)</text>';
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * REPORTE 2 - GRÁFICA 2: Barras apiladas al 100% de distribución porcentual por programa.
     */
    public function generarBarrasApiladasSvg(array $programas, int $ancho = 680, int $altoFila = 40): string
    {
        if (empty($programas)) {
            return '<svg width="' . $ancho . '" height="60" xmlns="http://www.w3.org/2000/svg"><text x="' . ($ancho / 2) . '" y="35" text-anchor="middle" fill="#9ca3af" font-size="12" font-family="Helvetica, Arial, sans-serif">Sin datos apilados</text></svg>';
        }

        $paddingTop = 25;
        $paddingBottom = 20;
        $anchoEtiqueta = 110;
        $anchoBarraMax = $ancho - $anchoEtiqueta - 40;
        $altoTotal = (count($programas) * $altoFila) + $paddingTop + $paddingBottom;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $altoTotal . '" viewBox="0 0 ' . $ancho . ' ' . $altoTotal . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';
        $svg .= '<text x="' . ($ancho / 2) . '" y="16" text-anchor="middle" font-size="12" font-weight="bold" fill="#374151">Distribución Porcentual Interna (100% Apilado)</text>';

        foreach ($programas as $pIdx => $prog) {
            $yBase = $paddingTop + ($pIdx * $altoFila);
            $yBarra = $yBase + 6;
            $alturaBarra = 20;
            $xActual = $anchoEtiqueta + 10;

            $siglas = htmlspecialchars($prog['siglas'] ?? 'Prog', ENT_QUOTES);
            $svg .= '<text x="' . ($anchoEtiqueta) . '" y="' . ($yBarra + 14) . '" text-anchor="end" font-size="10" font-weight="bold" fill="#374151">' . $siglas . '</text>';

            $totalProg = max(1, $prog['total_programa']);

            foreach ($prog['emociones'] as $emo) {
                $pct = ($emo['cantidad'] / $totalProg) * 100.0;
                $anchoSegmento = ($pct / 100.0) * $anchoBarraMax;
                $color = $emo['codigo_color'] ?: self::colorParaEmocion($emo['emocion']);

                if ($anchoSegmento > 0.5) {
                    $svg .= '<rect x="' . $xActual . '" y="' . $yBarra . '" width="' . round($anchoSegmento, 1) . '" height="' . $alturaBarra . '" fill="' . htmlspecialchars($color, ENT_QUOTES) . '"/>';
                    if ($anchoSegmento >= 26) {
                        $svg .= '<text x="' . ($xActual + ($anchoSegmento / 2)) . '" y="' . ($yBarra + 14) . '" text-anchor="middle" font-size="8.5" font-weight="bold" fill="#ffffff">' . round($pct) . '%</text>';
                    }
                    $xActual += $anchoSegmento;
                }
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * REPORTE 3 - GRÁFICA 1 & REPORTE 5 - GRÁFICA 1: Línea temporal vectorial.
     */
    public function generarLineaTemporalSvg(iterable $puntos, string $titulo = 'Evolución Temporal', string $tituloEjeY = 'Valor', int $minY = 1, ?int $maxY = 5, int $ancho = 640, int $alto = 220): string
    {
        $items = collect($puntos)->values();
        $totalItems = $items->count();

        if ($totalItems === 0) {
            return '<svg width="' . $ancho . '" height="' . $alto . '" xmlns="http://www.w3.org/2000/svg"><text x="' . ($ancho / 2) . '" y="' . ($alto / 2) . '" text-anchor="middle" fill="#9ca3af" font-size="12" font-family="Helvetica, Arial, sans-serif">Sin registros en la línea temporal</text></svg>';
        }

        $paddingLeft = 50;
        $paddingRight = 30;
        $paddingTop = 35;
        $paddingBottom = 45;

        $anchoUtil = $ancho - $paddingLeft - $paddingRight;
        $altoUtil = $alto - $paddingTop - $paddingBottom;

        $valores = $items->pluck('valor')->map(fn($v) => (float) $v);
        $valMin = $minY !== null ? (float) $minY : $valores->min();
        $valMax = $maxY !== null ? (float) $maxY : max(1, $valores->max());
        $rangoY = max(1.0, $valMax - $valMin);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $alto . '" viewBox="0 0 ' . $ancho . ' ' . $alto . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';
        $svg .= '<text x="' . ($ancho / 2) . '" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#374151">' . htmlspecialchars($titulo, ENT_QUOTES) . '</text>';

        // Guías horizontales
        for ($i = 0; $i <= 4; $i++) {
            $val = $valMin + (($rangoY / 4.0) * $i);
            $yGuia = $paddingTop + $altoUtil - (($i / 4.0) * $altoUtil);
            $svg .= '<line x1="' . $paddingLeft . '" y1="' . $yGuia . '" x2="' . ($paddingLeft + $anchoUtil) . '" y2="' . $yGuia . '" stroke="#e5e7eb" stroke-dasharray="2,2"/>';
            $svg .= '<text x="' . ($paddingLeft - 8) . '" y="' . ($yGuia + 4) . '" text-anchor="end" font-size="9" fill="#9ca3af">' . (is_int($val) || $val == (int)$val ? (int)$val : number_format($val, 1)) . '</text>';
        }

        $coordenadas = [];
        foreach ($items as $idx => $p) {
            $x = ($totalItems === 1)
                ? $paddingLeft + ($anchoUtil / 2)
                : $paddingLeft + (($idx / ($totalItems - 1)) * $anchoUtil);
            $y = $paddingTop + $altoUtil - ((($p['valor'] - $valMin) / $rangoY) * $altoUtil);
            $coordenadas[] = ['x' => $x, 'y' => $y, 'fecha' => $p['fecha'] ?? '', 'valor' => $p['valor']];
        }

        // Trazar línea poligonal
        $puntosStr = implode(' ', array_map(fn($c) => round($c['x'], 1) . ',' . round($c['y'], 1), $coordenadas));
        $svg .= '<polyline fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="' . $puntosStr . '"/>';

        // Puntos circulares
        foreach ($coordenadas as $c) {
            $svg .= '<circle cx="' . round($c['x'], 1) . '" cy="' . round($c['y'], 1) . '" r="4.5" fill="#4338ca" stroke="#ffffff" stroke-width="2"/>';
        }

        // Etiquetas eje X (primer, medio y último punto para evitar empalme)
        $indicesEtiquetas = array_unique([0, (int) floor(($totalItems - 1) / 2), $totalItems - 1]);
        foreach ($indicesEtiquetas as $idx) {
            if (isset($coordenadas[$idx])) {
                $c = $coordenadas[$idx];
                $svg .= '<text x="' . round($c['x'], 1) . '" y="' . ($paddingTop + $altoUtil + 18) . '" text-anchor="middle" font-size="9" fill="#6b7280">' . htmlspecialchars($c['fecha'], ENT_QUOTES) . '</text>';
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * REPORTE 3 - GRÁFICA 2: Barras de citas por estado.
     */
    public function generarBarrasCitasSvg(array $conteoEstados, int $ancho = 480, int $alto = 220): string
    {
        $items = collect($conteoEstados)->filter(fn($v) => $v > 0);
        if ($items->isEmpty()) {
            return '<svg width="' . $ancho . '" height="' . $alto . '" xmlns="http://www.w3.org/2000/svg"><text x="' . ($ancho / 2) . '" y="' . ($alto / 2) . '" text-anchor="middle" fill="#9ca3af" font-size="12" font-family="Helvetica, Arial, sans-serif">Sin citas registradas</text></svg>';
        }

        $colores = [
            'ATENDIDA' => '#10b981',
            'CONFIRMADA' => '#2563eb',
            'PROGRAMADA' => '#f59e0b',
            'CANCELADA' => '#ef4444',
            'SOLICITADA' => '#8b5cf6',
        ];

        $paddingLeft = 85;
        $paddingRight = 45;
        $paddingTop = 35;
        $paddingBottom = 20;

        $anchoBarraMax = $ancho - $paddingLeft - $paddingRight;
        $maxVal = max(1, $items->max());
        $altoFila = 34;
        $altoTotal = ($items->count() * $altoFila) + $paddingTop + $paddingBottom;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $altoTotal . '" viewBox="0 0 ' . $ancho . ' ' . $altoTotal . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';
        $svg .= '<text x="' . ($ancho / 2) . '" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#374151">Citas de Orientación por Estado</text>';

        $idx = 0;
        foreach ($items as $estado => $total) {
            $yBase = $paddingTop + ($idx * $altoFila);
            $yBarra = $yBase + 6;
            $color = $colores[strtoupper($estado)] ?? '#64748b';
            $anchoCalculado = ($total / $maxVal) * $anchoBarraMax;

            $svg .= '<text x="' . ($paddingLeft - 10) . '" y="' . ($yBarra + 12) . '" text-anchor="end" font-size="10" font-weight="bold" fill="#374151">' . htmlspecialchars($estado, ENT_QUOTES) . '</text>';
            $svg .= '<rect x="' . $paddingLeft . '" y="' . $yBarra . '" width="' . $anchoBarraMax . '" height="16" rx="4" fill="#f3f4f6"/>';
            $svg .= '<rect x="' . $paddingLeft . '" y="' . $yBarra . '" width="' . round($anchoCalculado, 1) . '" height="16" rx="4" fill="' . $color . '"/>';
            $svg .= '<text x="' . ($paddingLeft + $anchoCalculado + 8) . '" y="' . ($yBarra + 12) . '" font-size="10" font-weight="bold" fill="#111827">' . $total . '</text>';
            $idx++;
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * REPORTE 5 - GRÁFICA 2: Barras apiladas de accesos exitosos vs fallidos por día.
     */
    public function generarBarrasApiladasAccesosSvg(iterable $serieDiaria, int $ancho = 640, int $alto = 220): string
    {
        $dias = collect($serieDiaria)->values();
        if ($dias->isEmpty()) {
            return '<svg width="' . $ancho . '" height="' . $alto . '" xmlns="http://www.w3.org/2000/svg"><text x="' . ($ancho / 2) . '" y="' . ($alto / 2) . '" text-anchor="middle" fill="#9ca3af" font-size="12" font-family="Helvetica, Arial, sans-serif">Sin accesos para graficar</text></svg>';
        }

        $paddingLeft = 50;
        $paddingRight = 20;
        $paddingTop = 35;
        $paddingBottom = 45;

        $anchoUtil = $ancho - $paddingLeft - $paddingRight;
        $altoUtil = $alto - $paddingTop - $paddingBottom;

        $maxVal = max(5, $dias->max(fn($d) => (int) (is_array($d) ? ($d['total_accesos'] ?? 0) : ($d->total_accesos ?? 0))));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $alto . '" viewBox="0 0 ' . $ancho . ' ' . $alto . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';
        $svg .= '<text x="' . ($ancho / 2) . '" y="18" text-anchor="middle" font-size="12" font-weight="bold" fill="#374151">Accesos Exitosos vs Intentos Fallidos por Día</text>';

        // Guías horizontales
        for ($i = 0; $i <= 4; $i++) {
            $val = round(($maxVal / 4.0) * $i);
            $yGuia = $paddingTop + $altoUtil - (($i / 4.0) * $altoUtil);
            $svg .= '<line x1="' . $paddingLeft . '" y1="' . $yGuia . '" x2="' . ($paddingLeft + $anchoUtil) . '" y2="' . $yGuia . '" stroke="#e5e7eb" stroke-dasharray="2,2"/>';
            $svg .= '<text x="' . ($paddingLeft - 8) . '" y="' . ($yGuia + 4) . '" text-anchor="end" font-size="9" fill="#9ca3af">' . $val . '</text>';
        }

        $numDias = $dias->count();
        $anchoSlot = $anchoUtil / max(1, $numDias);
        $anchoBarra = max(3, min(24, $anchoSlot - 4));

        foreach ($dias as $idx => $d) {
            $total = (int) (is_array($d) ? ($d['total_accesos'] ?? 0) : ($d->total_accesos ?? 0));
            $exitosos = (int) (is_array($d) ? ($d['accesos_exitosos'] ?? $total) : ($d->accesos_exitosos ?? $total));
            $fallidos = max(0, $total - $exitosos);

            $xBarra = $paddingLeft + ($idx * $anchoSlot) + (($anchoSlot - $anchoBarra) / 2);
            $hExitosos = ($exitosos / $maxVal) * $altoUtil;
            $hFallidos = ($fallidos / $maxVal) * $altoUtil;

            $yBase = $paddingTop + $altoUtil;
            if ($hExitosos > 0) {
                $svg .= '<rect x="' . $xBarra . '" y="' . ($yBase - $hExitosos) . '" width="' . $anchoBarra . '" height="' . $hExitosos . '" fill="#10b981" rx="2"/>';
            }
            if ($hFallidos > 0) {
                $svg .= '<rect x="' . $xBarra . '" y="' . ($yBase - $hExitosos - $hFallidos) . '" width="' . $anchoBarra . '" height="' . $hFallidos . '" fill="#ef4444" rx="2"/>';
            }

            // Fechas en eje X (selección estratégica)
            if ($numDias <= 10 || $idx % (int)ceil($numDias / 7) === 0 || $idx === $numDias - 1) {
                $fechaRaw = is_array($d) ? ($d['fecha'] ?? '') : ($d->fecha ?? '');
                $fechaStr = substr($fechaRaw, 5); // mm-dd
                $svg .= '<text x="' . ($xBarra + ($anchoBarra / 2)) . '" y="' . ($yBase + 16) . '" text-anchor="middle" font-size="8.5" fill="#6b7280">' . $fechaStr . '</text>';
            }
        }

        // Leyenda
        $svg .= '<circle cx="' . ($ancho - 160) . '" cy="20" r="4.5" fill="#10b981"/>';
        $svg .= '<text x="' . ($ancho - 150) . '" y="23" font-size="9" fill="#374151">Exitosos</text>';
        $svg .= '<circle cx="' . ($ancho - 90) . '" cy="20" r="4.5" fill="#ef4444"/>';
        $svg .= '<text x="' . ($ancho - 80) . '" y="23" font-size="9" fill="#374151">Fallidos</text>';

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * REPORTE 6 - GRÁFICA 2: Barras de aplicaciones por instrumento psicométrico.
     */
    public function generarBarrasPorInstrumentoSvg(iterable $instrumentos, int $ancho = 600, int $altoFila = 34): string
    {
        $items = collect($instrumentos)->values();
        if ($items->isEmpty()) {
            return '<svg width="' . $ancho . '" height="60" xmlns="http://www.w3.org/2000/svg"><text x="' . ($ancho / 2) . '" y="35" text-anchor="middle" fill="#9ca3af" font-size="12" font-family="Helvetica, Arial, sans-serif">Sin instrumentos registrados</text></svg>';
        }

        $paddingTop = 20;
        $paddingBottom = 20;
        $altoTotal = ($items->count() * $altoFila) + $paddingTop + $paddingBottom;

        $anchoEtiqueta = 140;
        $anchoValores = 90;
        $anchoBarraMax = $ancho - $anchoEtiqueta - $anchoValores - 30;
        $maxVal = max(1, $items->max(fn($i) => (int) (is_array($i) ? ($i['total'] ?? 0) : ($i->total ?? 0))));

        $colores = ['#004d35', '#2563eb', '#7c3aed', '#f59e0b', '#ec4899', '#10b981'];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $altoTotal . '" viewBox="0 0 ' . $ancho . ' ' . $altoTotal . '" style="background: #ffffff; font-family: Helvetica, Arial, sans-serif;">';

        foreach ($items as $idx => $inst) {
            $nombre = is_array($inst) ? ($inst['nombre'] ?? 'Instrumento') : ($inst->nombre ?? 'Instrumento');
            $total = (int) (is_array($inst) ? ($inst['total'] ?? 0) : ($inst->total ?? 0));
            $color = $colores[$idx % count($colores)];

            $yBase = $paddingTop + ($idx * $altoFila);
            $yTexto = $yBase + 18;
            $yBarra = $yBase + 6;
            $alturaBarra = 16;

            $anchoCalculado = max(0, min($anchoBarraMax, ($total / $maxVal) * $anchoBarraMax));

            $svg .= '<circle cx="14" cy="' . ($yBarra + 8) . '" r="5" fill="' . $color . '"/>';
            $svg .= '<text x="26" y="' . $yTexto . '" fill="#1f2937" font-size="11" font-weight="bold">' . htmlspecialchars(mb_strimwidth($nombre, 0, 18, '...'), ENT_QUOTES) . '</text>';

            $xBarra = $anchoEtiqueta + 15;
            $svg .= '<rect x="' . $xBarra . '" y="' . $yBarra . '" width="' . $anchoBarraMax . '" height="' . $alturaBarra . '" rx="4" fill="#f3f4f6"/>';
            if ($anchoCalculado > 0) {
                $svg .= '<rect x="' . $xBarra . '" y="' . $yBarra . '" width="' . round($anchoCalculado, 1) . '" height="' . $alturaBarra . '" rx="4" fill="' . $color . '"/>';
            }

            $xValor = $xBarra + $anchoBarraMax + 12;
            $svg .= '<text x="' . $xValor . '" y="' . $yTexto . '" fill="#111827" font-size="11" font-weight="bold">' . $total . ' apps</text>';
        }

        $svg .= '</svg>';
        return $svg;
    }
}

