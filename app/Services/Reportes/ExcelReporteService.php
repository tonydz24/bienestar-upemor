<?php

namespace App\Services\Reportes;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;

class ExcelReporteService
{
    /**
     * Paleta institucional UPEMOR para estilos Excel.
     */
    protected const COLOR_HEADER_BG = '004D35';      // Verde institucional UPEMOR
    protected const COLOR_HEADER_TEXT = 'FFFFFF';    // Blanco
    protected const COLOR_ZEBRA_BG = 'F9FAFB';       // Gris tenue para filas pares
    protected const COLOR_BORDER = 'D1D5DB';         // Borde gris claro sutil

    /**
     * Convierte un índice numérico de columna (0, 1, 2...) a letra Excel (A, B, C... AA, AB...).
     */
    public static function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int) (($index - $mod) / 26);
        }
        return $letter;
    }

    /**
     * Sanitiza un valor string contra inyección de fórmulas en hojas de cálculo.
     */
    public static function sanitizeCellValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        $str = (string) $value;
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@'], true)) {
            return "'" . $str;
        }
        return $str;
    }

    /**
     * Genera un archivo OpenXML .xlsx nativo en una ruta temporal y lo devuelve como BinaryFileResponse.
     *
     * @param string $nombreArchivo Base del nombre del archivo sin extensión
     * @param string $tituloHoja Nombre de la pestaña de la hoja
     * @param array $encabezados Array de títulos de columna
     * @param array $filas Array de arrays con los valores de cada fila
     * @param array $opciones Configuración adicional (anchos de columna, alineaciones, tipos)
     */
    public function descargarXlsx(
        string $nombreArchivo,
        string $tituloHoja,
        array $encabezados,
        array $filas,
        array $opciones = []
    ): BinaryFileResponse {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_upemor_');
        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No fue posible crear el archivo ZIP temporal para Excel.");
        }

        // 1. [Content_Types].xml
        $graficas = $this->normalizeGraficas($opciones);
        $hasChart = count($graficas) > 0;
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n"
            . '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n"
            . '  <Default Extension="xml" ContentType="application/xml"/>' . "\n"
            . '  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n"
            . '  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n"
            . '  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n";
        if ($hasChart) {
            $contentTypes .= '  <Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' . "\n";
            foreach ($graficas as $idx => $g) {
                $cNum = $idx + 1;
                $contentTypes .= "  <Override PartName=\"/xl/charts/chart{$cNum}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.drawingml.chart+xml\"/>\n";
            }
        }
        $contentTypes .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n"
            . '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n"
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n"
            . '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . "\n"
            . '  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n"
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $cleanSheetName = substr(preg_replace('/[\\\\\\/\?\*\[\]\:]/', '', $tituloHoja) ?: 'Reporte', 0, 31);
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n"
            . '  <sheets>' . "\n"
            . '    <sheet name="' . htmlspecialchars($cleanSheetName, ENT_XML1, 'UTF-8') . '" sheetId="1" r:id="rId1"/>' . "\n"
            . '  </sheets>' . "\n"
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // 5. xl/styles.xml (Paleta institucional violeta UPEMOR)
        $stylesXml = $this->buildStylesXml();
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = $this->buildWorksheetXml($encabezados, $filas, $opciones);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        // 7. Gráfica nativa DrawingML (soporte para 1 o 2 visualizaciones por hoja)
        if ($hasChart) {
            $sheetRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n"
                . '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>' . "\n"
                . '</Relationships>';
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheetRels);

            $drawingRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
            foreach ($graficas as $idx => $g) {
                $cNum = $idx + 1;
                $drawingRels .= "  <Relationship Id=\"rId{$cNum}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart\" Target=\"../charts/chart{$cNum}.xml\"/>\n";
            }
            $drawingRels .= '</Relationships>';
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $drawingRels);

            $zip->addFromString('xl/drawings/drawing1.xml', $this->buildMultiDrawingXml($graficas));
            foreach ($graficas as $idx => $g) {
                $cNum = $idx + 1;
                $zip->addFromString("xl/charts/chart{$cNum}.xml", $this->buildChartXml($g, $cleanSheetName));
            }
        }

        $zip->close();

        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            "{$nombreArchivo}.xlsx"
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Genera un archivo OpenXML .xlsx nativo con múltiples pestañas/hojas de cálculo.
     *
     * @param string $nombreArchivo
     * @param array $hojas Array de hojas: [['titulo' => string, 'encabezados' => array, 'filas' => array, 'opciones' => array], ...]
     */
    public function descargarXlsxMultiHoja(string $nombreArchivo, array $hojas): BinaryFileResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_upemor_multi_');
        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No fue posible crear el archivo ZIP temporal para Excel multi-hoja.");
        }

        // 1. [Content_Types].xml
        $graficasSheet1 = $this->normalizeGraficas($hojas[0]['opciones'] ?? []);
        $hasChartSheet1 = count($graficasSheet1) > 0;
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n"
            . '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n"
            . '  <Default Extension="xml" ContentType="application/xml"/>' . "\n"
            . '  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n"
            . '  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n";

        foreach ($hojas as $idx => $hoja) {
            $sNum = $idx + 1;
            $contentTypes .= "  <Override PartName=\"/xl/worksheets/sheet{$sNum}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>\n";
        }
        if ($hasChartSheet1) {
            $contentTypes .= "  <Override PartName=\"/xl/drawings/drawing1.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.drawing+xml\"/>\n";
            foreach ($graficasSheet1 as $idx => $g) {
                $cNum = $idx + 1;
                $contentTypes .= "  <Override PartName=\"/xl/charts/chart{$cNum}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.drawingml.chart+xml\"/>\n";
            }
        }
        $contentTypes .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n"
            . '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n"
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        foreach ($hojas as $idx => $hoja) {
            $sNum = $idx + 1;
            $wbRels .= "  <Relationship Id=\"rId{$sNum}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet{$sNum}.xml\"/>\n";
        }
        $stylesRId = 'rId' . (count($hojas) + 1);
        $wbRels .= "  <Relationship Id=\"{$stylesRId}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/>\n";
        $wbRels .= '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n"
            . '  <sheets>' . "\n";
        foreach ($hojas as $idx => $hoja) {
            $sNum = $idx + 1;
            $cleanSheetName = substr(preg_replace('/[\\\\\\/\?\*\[\]\:]/', '', $hoja['titulo'] ?? "Hoja {$sNum}") ?: "Hoja {$sNum}", 0, 31);
            $workbookXml .= '    <sheet name="' . htmlspecialchars($cleanSheetName, ENT_XML1, 'UTF-8') . '" sheetId="' . $sNum . '" r:id="rId' . $sNum . '"/>' . "\n";
        }
        $workbookXml .= '  </sheets>' . "\n"
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // 5. xl/styles.xml (Paleta institucional UPEMOR)
        $stylesXml = $this->buildStylesXml();
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // 6. worksheets
        foreach ($hojas as $idx => $hoja) {
            $sNum = $idx + 1;
            $sheetXml = $this->buildWorksheetXml($hoja['encabezados'], $hoja['filas'], $hoja['opciones'] ?? []);
            $zip->addFromString("xl/worksheets/sheet{$sNum}.xml", $sheetXml);
        }

        // 7. Gráfica nativa DrawingML para Hoja 1 (si fue solicitada)
        if ($hasChartSheet1) {
            $sheetRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n"
                . '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>' . "\n"
                . '</Relationships>';
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheetRels);

            $drawingRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
            foreach ($graficasSheet1 as $idx => $g) {
                $cNum = $idx + 1;
                $drawingRels .= "  <Relationship Id=\"rId{$cNum}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart\" Target=\"../charts/chart{$cNum}.xml\"/>\n";
            }
            $drawingRels .= '</Relationships>';
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $drawingRels);

            $cleanSheet1Title = substr(preg_replace('/[\\\\\\/\?\*\[\]\:]/', '', $hojas[0]['titulo'] ?? 'Reporte') ?: 'Reporte', 0, 31);
            $zip->addFromString('xl/drawings/drawing1.xml', $this->buildMultiDrawingXml($graficasSheet1));
            foreach ($graficasSheet1 as $idx => $g) {
                $cNum = $idx + 1;
                $zip->addFromString("xl/charts/chart{$cNum}.xml", $this->buildChartXml($g, $cleanSheet1Title));
            }
        }

        $zip->close();

        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            "{$nombreArchivo}.xlsx"
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Construye la especificación de estilos OpenXML con paleta institucional.
     */
    protected function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n"
            . '  <numFmts count="3">' . "\n"
            . '    <numFmt numFmtId="164" formatCode="#,##0"/>' . "\n"
            . '    <numFmt numFmtId="165" formatCode="0.0%"/>' . "\n"
            . '    <numFmt numFmtId="166" formatCode="#,##0.00"/>' . "\n"
            . '  </numFmts>' . "\n"
            . '  <fonts count="3">' . "\n"
            . '    <font><name val="Segoe UI"/><sz val="10"/><color rgb="FF1F2937"/></font>' . "\n" // 0: Regular
            . '    <font><b/><name val="Segoe UI"/><sz val="10.5"/><color rgb="FFFFFFFF"/></font>' . "\n" // 1: Header bold blanco
            . '    <font><b/><name val="Segoe UI"/><sz val="10"/><color rgb="FF1E1B4B"/></font>' . "\n" // 2: Bold dark
            . '  </fonts>' . "\n"
            . '  <fills count="4">' . "\n"
            . '    <fill><patternFill patternType="none"/></fill>' . "\n"
            . '    <fill><patternFill patternType="gray125"/></fill>' . "\n"
            . '    <fill><patternFill patternType="solid"><fgColor rgb="FF' . self::COLOR_HEADER_BG . '"/></patternFill></fill>' . "\n" // 2: Header purple
            . '    <fill><patternFill patternType="solid"><fgColor rgb="FF' . self::COLOR_ZEBRA_BG . '"/></patternFill></fill>' . "\n" // 3: Zebra light
            . '  </fills>' . "\n"
            . '  <borders count="2">' . "\n"
            . '    <border><left/><right/><top/><bottom/><diagonal/></border>' . "\n" // 0: None
            . '    <border>' . "\n" // 1: Thin border light
            . '      <left style="thin"><color rgb="FF' . self::COLOR_BORDER . '"/></left>' . "\n"
            . '      <right style="thin"><color rgb="FF' . self::COLOR_BORDER . '"/></right>' . "\n"
            . '      <top style="thin"><color rgb="FF' . self::COLOR_BORDER . '"/></top>' . "\n"
            . '      <bottom style="thin"><color rgb="FF' . self::COLOR_BORDER . '"/></bottom>' . "\n"
            . '    </border>' . "\n"
            . '  </borders>' . "\n"
            . '  <cellStyleXfs count="1">' . "\n"
            . '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' . "\n"
            . '  </cellStyleXfs>' . "\n"
            . '  <cellXfs count="8">' . "\n"
            . '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' . "\n" // s=0: default
            . '    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' . "\n" // s=1: Header
            . '    <xf numFmtId="164" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . "\n" // s=2: Entero con border
            . '    <xf numFmtId="165" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . "\n" // s=3: Porcentaje
            . '    <xf numFmtId="166" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . "\n" // s=4: Decimal
            . '    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n" // s=5: Texto izq
            . '    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n" // s=6: Texto centro
            . '    <xf numFmtId="0" fontId="2" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . "\n" // s=7: Totales bold
            . '  </cellXfs>' . "\n"
            . '</styleSheet>';
    }

    /**
     * Normaliza las opciones de gráficas DrawingML soportando una o múltiples gráficas por hoja.
     */
    protected function normalizeGraficas(array $opciones): array
    {
        if (!empty($opciones['graficas']) && is_array($opciones['graficas'])) {
            return array_values($opciones['graficas']);
        }
        if (!empty($opciones['grafica']) && is_array($opciones['grafica'])) {
            return [$opciones['grafica']];
        }
        return [];
    }

    /**
     * Construye el contenedor DrawingML (drawing1.xml) anclando múltiples gráficas en las celdas deseadas.
     */
    protected function buildMultiDrawingXml(array $graficas): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";

        foreach ($graficas as $idx => $grafica) {
            $cNum = $idx + 1;
            $rId = "rId{$cNum}";
            $fromCol = $grafica['from_col'] ?? ($idx === 0 ? 5 : 5);
            $fromRow = $grafica['from_row'] ?? ($idx === 0 ? 1 : 20);
            $toCol = $grafica['to_col'] ?? ($idx === 0 ? 14 : 14);
            $toRow = $grafica['to_row'] ?? ($idx === 0 ? 18 : 37);
            $nombre = htmlspecialchars($grafica['titulo'] ?? "Grafica {$cNum}", ENT_XML1, 'UTF-8');
            $elementId = $idx + 2;

            $xml .= '  <xdr:twoCellAnchor editAs="oneCell">' . "\n"
                . '    <xdr:from><xdr:col>' . $fromCol . '</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . $fromRow . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>' . "\n"
                . '    <xdr:to><xdr:col>' . $toCol . '</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . $toRow . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>' . "\n"
                . '    <xdr:graphicFrame macro="">' . "\n"
                . '      <xdr:nvGraphicFramePr>' . "\n"
                . '        <xdr:cNvPr id="' . $elementId . '" name="' . $nombre . '"/>' . "\n"
                . '        <xdr:cNvGraphicFramePr><a:graphicFrameLocks noGrp="1"/></xdr:cNvGraphicFramePr>' . "\n"
                . '      </xdr:nvGraphicFramePr>' . "\n"
                . '      <xdr:xfrm><a:off x="0" y="0"/><a:ext cx="5486400" cy="3200400"/></xdr:xfrm>' . "\n"
                . '      <a:graphic>' . "\n"
                . '        <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart">' . "\n"
                . '          <c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="' . $rId . '"/>' . "\n"
                . '        </a:graphicData>' . "\n"
                . '      </a:graphic>' . "\n"
                . '    </xdr:graphicFrame>' . "\n"
                . '    <xdr:clientData/>' . "\n"
                . '  </xdr:twoCellAnchor>' . "\n";
        }

        $xml .= '</xdr:wsDr>';
        return $xml;
    }

    /**
     * Construye el contenedor DrawingML (drawing1.xml) anclando una sola gráfica (compatibilidad).
     */
    protected function buildDrawingXml(array $grafica): string
    {
        return $this->buildMultiDrawingXml([$grafica]);
    }

    /**
     * Construye la especificación OpenXML ChartML (chart1.xml, chart2.xml...) para dona, barras o líneas.
     */
    protected function buildChartXml(array $grafica, string $sheetTitle): string
    {
        $tipo = $grafica['tipo'] ?? 'barra';
        $titulo = htmlspecialchars($grafica['titulo'] ?? 'Grafica Institucional', ENT_XML1, 'UTF-8');
        $colCat = $grafica['col_cat'] ?? 'A';
        $colVal = $grafica['col_val'] ?? 'B';
        $rIni = (int) ($grafica['rango_inicio'] ?? 2);
        $rFin = (int) ($grafica['rango_fin'] ?? 7);

        $catFormula = htmlspecialchars("'{$sheetTitle}'!\${$colCat}\${$rIni}:\${$colCat}\${$rFin}", ENT_XML1, 'UTF-8');
        $valFormula = htmlspecialchars("'{$sheetTitle}'!\${$colVal}\${$rIni}:\${$colVal}\${$rFin}", ENT_XML1, 'UTF-8');
        $titleFormula = htmlspecialchars("'{$sheetTitle}'!\${$colVal}\$1", ENT_XML1, 'UTF-8');
        $titleCacheXml = '<c:strCache><c:ptCount val="1"/><c:pt idx="0"><c:v>' . $titulo . '</c:v></c:strCache>';

        $catCacheXml = '';
        if (!empty($grafica['cat_cache']) && is_array($grafica['cat_cache'])) {
            $catCacheXml = '<c:strCache><c:ptCount val="' . count($grafica['cat_cache']) . '"/>';
            foreach (array_values($grafica['cat_cache']) as $idx => $val) {
                $catCacheXml .= '<c:pt idx="' . $idx . '"><c:v>' . htmlspecialchars((string) $val, ENT_XML1, 'UTF-8') . '</c:v></c:pt>';
            }
            $catCacheXml .= '</c:strCache>';
        }

        $valCacheXml = '';
        if (!empty($grafica['val_cache']) && is_array($grafica['val_cache'])) {
            $valCacheXml = '<c:numCache><c:formatCode>General</c:formatCode><c:ptCount val="' . count($grafica['val_cache']) . '"/>';
            foreach (array_values($grafica['val_cache']) as $idx => $val) {
                $valCacheXml .= '<c:pt idx="' . $idx . '"><c:v>' . (is_numeric($val) ? $val : 0) . '</c:v></c:pt>';
            }
            $valCacheXml .= '</c:numCache>';
        }

        $chartXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n"
            . '  <c:date1904 val="0"/>' . "\n"
            . '  <c:lang val="es-MX"/>' . "\n"
            . '  <c:roundedCorners val="0"/>' . "\n"
            . '  <c:chart>' . "\n"
            . '    <c:title>' . "\n"
            . '      <c:tx>' . "\n"
            . '        <c:rich>' . "\n"
            . '          <a:bodyPr/>' . "\n"
            . '          <a:lstStyle/>' . "\n"
            . '          <a:p><a:pPr><a:defRPr sz="1200" b="1"/></a:pPr><a:r><a:t>' . $titulo . '</a:t></a:r></a:p>' . "\n"
            . '        </c:rich>' . "\n"
            . '      </c:tx>' . "\n"
            . '      <c:overlay val="0"/>' . "\n"
            . '    </c:title>' . "\n"
            . '    <c:plotArea>' . "\n"
            . '      <c:layout/>' . "\n";

        if ($tipo === 'dona' || $tipo === 'pie') {
            $chartXml .= '      <c:doughnutChart>' . "\n"
                . '        <c:varyColors val="1"/>' . "\n"
                . '        <c:ser>' . "\n"
                . '          <c:idx val="0"/><c:order val="0"/>' . "\n"
                . '          <c:tx><c:strRef><c:f>' . $titleFormula . '</c:f>' . $titleCacheXml . '</c:strRef></c:tx>' . "\n"
                . '          <c:cat><c:strRef><c:f>' . $catFormula . '</c:f>' . $catCacheXml . '</c:strRef></c:cat>' . "\n"
                . '          <c:val><c:numRef><c:f>' . $valFormula . '</c:f>' . $valCacheXml . '</c:numRef></c:val>' . "\n"
                . '        </c:ser>' . "\n"
                . '        <c:holeSize val="55"/>' . "\n"
                . '      </c:doughnutChart>' . "\n";
        } elseif ($tipo === 'linea') {
            $chartXml .= '      <c:lineChart>' . "\n"
                . '        <c:grouping val="standard"/>' . "\n"
                . '        <c:varyColors val="0"/>' . "\n"
                . '        <c:ser>' . "\n"
                . '          <c:idx val="0"/><c:order val="0"/>' . "\n"
                . '          <c:tx><c:strRef><c:f>' . $titleFormula . '</c:f>' . $titleCacheXml . '</c:strRef></c:tx>' . "\n"
                . '          <c:cat><c:strRef><c:f>' . $catFormula . '</c:f>' . $catCacheXml . '</c:strRef></c:cat>' . "\n"
                . '          <c:val><c:numRef><c:f>' . $valFormula . '</c:f>' . $valCacheXml . '</c:numRef></c:val>' . "\n"
                . '          <c:smooth val="0"/>' . "\n"
                . '        </c:ser>' . "\n"
                . '        <c:axId val="100001"/>' . "\n"
                . '        <c:axId val="100002"/>' . "\n"
                . '      </c:lineChart>' . "\n"
                . '      <c:catAx>' . "\n"
                . '        <c:axId val="100001"/><c:scaling><c:orientation val="minMax"/></c:scaling>' . "\n"
                . '        <c:delete val="0"/><c:axPos val="b"/><c:crossAx val="100002"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/>' . "\n"
                . '      </c:catAx>' . "\n"
                . '      <c:valAx>' . "\n"
                . '        <c:axId val="100002"/><c:scaling><c:orientation val="minMax"/></c:scaling>' . "\n"
                . '        <c:delete val="0"/><c:axPos val="l"/><c:majorGridlines/><c:crossAx val="100001"/><c:crosses val="autoZero"/>' . "\n"
                . '      </c:valAx>' . "\n";
        } else {
            $chartXml .= '      <c:barChart>' . "\n"
                . '        <c:barDir val="col"/>' . "\n"
                . '        <c:grouping val="clustered"/>' . "\n"
                . '        <c:varyColors val="0"/>' . "\n";

            if (!empty($grafica['series']) && is_array($grafica['series'])) {
                foreach ($grafica['series'] as $sIdx => $s) {
                    $serCol = $s['col_val'];
                    $serTitleFormula = htmlspecialchars("'{$sheetTitle}'!\${$serCol}\$1", ENT_XML1, 'UTF-8');
                    $serValFormula = htmlspecialchars("'{$sheetTitle}'!\${$serCol}\${$rIni}:\${$serCol}\${$rFin}", ENT_XML1, 'UTF-8');
                    $sTitle = htmlspecialchars($s['titulo'] ?? "Serie {$sIdx}", ENT_XML1, 'UTF-8');
                    $serTitleCacheXml = '<c:strCache><c:ptCount val="1"/><c:pt idx="0"><c:v>' . $sTitle . '</c:v></c:strCache>';
                    $serValCacheXml = '';
                    if (!empty($s['val_cache']) && is_array($s['val_cache'])) {
                        $serValCacheXml = '<c:numCache><c:formatCode>General</c:formatCode><c:ptCount val="' . count($s['val_cache']) . '"/>';
                        foreach (array_values($s['val_cache']) as $idx => $val) {
                            $serValCacheXml .= '<c:pt idx="' . $idx . '"><c:v>' . (is_numeric($val) ? $val : 0) . '</c:v></c:pt>';
                        }
                        $serValCacheXml .= '</c:numCache>';
                    }
                    $chartXml .= '        <c:ser>' . "\n"
                        . '          <c:idx val="' . $sIdx . '"/><c:order val="' . $sIdx . '"/>' . "\n"
                        . '          <c:tx><c:strRef><c:f>' . $serTitleFormula . '</c:f>' . $serTitleCacheXml . '</c:strRef></c:tx>' . "\n"
                        . '          <c:cat><c:strRef><c:f>' . $catFormula . '</c:f>' . $catCacheXml . '</c:strRef></c:cat>' . "\n"
                        . '          <c:val><c:numRef><c:f>' . $serValFormula . '</c:f>' . $serValCacheXml . '</c:numRef></c:val>' . "\n"
                        . '        </c:ser>' . "\n";
                }
            } else {
                $chartXml .= '        <c:ser>' . "\n"
                    . '          <c:idx val="0"/><c:order val="0"/>' . "\n"
                    . '          <c:tx><c:strRef><c:f>' . $titleFormula . '</c:f>' . $titleCacheXml . '</c:strRef></c:tx>' . "\n"
                    . '          <c:cat><c:strRef><c:f>' . $catFormula . '</c:f>' . $catCacheXml . '</c:strRef></c:cat>' . "\n"
                    . '          <c:val><c:numRef><c:f>' . $valFormula . '</c:f>' . $valCacheXml . '</c:numRef></c:val>' . "\n"
                    . '        </c:ser>' . "\n";
            }

            $chartXml .= '        <c:axId val="100001"/>' . "\n"
                . '        <c:axId val="100002"/>' . "\n"
                . '      </c:barChart>' . "\n"
                . '      <c:catAx>' . "\n"
                . '        <c:axId val="100001"/><c:scaling><c:orientation val="minMax"/></c:scaling>' . "\n"
                . '        <c:delete val="0"/><c:axPos val="b"/><c:crossAx val="100002"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/>' . "\n"
                . '      </c:catAx>' . "\n"
                . '      <c:valAx>' . "\n"
                . '        <c:axId val="100002"/><c:scaling><c:orientation val="minMax"/></c:scaling>' . "\n"
                . '        <c:delete val="0"/><c:axPos val="l"/><c:majorGridlines/><c:crossAx val="100001"/><c:crosses val="autoZero"/>' . "\n"
                . '      </c:valAx>' . "\n";
        }

        $chartXml .= '    </c:plotArea>' . "\n"
            . '    <c:legend><c:legendPos val="r"/><c:overlay val="0"/></c:legend>' . "\n"
            . '    <c:plotVisOnly val="1"/>' . "\n"
            . '  </c:chart>' . "\n"
            . '</c:chartSpace>';

        return $chartXml;
    }

    /**
     * Construye la hoja de cálculo OpenXML con datos, anchos calculados, panel congelado y autofiltro.
     */
    protected function buildWorksheetXml(array $encabezados, array $filas, array $opciones): string
    {
        $numCols = count($encabezados);
        $totalFilas = count($filas);
        $lastColLetter = self::colLetter(max(0, $numCols - 1));
        $lastRowIndex = $totalFilas + 1; // Fila 1 es cabecera

        // Calcular anchos de columna aproximados
        $colWidths = [];
        foreach ($encabezados as $i => $h) {
            $colWidths[$i] = mb_strlen((string) $h) + 4;
        }

        foreach ($filas as $fila) {
            foreach ($fila as $i => $val) {
                if (isset($colWidths[$i])) {
                    $len = mb_strlen((string) $val);
                    if ($len + 3 > $colWidths[$i]) {
                        $colWidths[$i] = min(48, $len + 3);
                    }
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";

        // Congelar fila de cabecera
        $xml .= '  <sheetViews>' . "\n"
            . '    <sheetView tabSelected="1" workbookViewId="0">' . "\n"
            . '      <pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>' . "\n"
            . '    </sheetView>' . "\n"
            . '  </sheetViews>' . "\n";

        // Anchos de columna
        $xml .= '  <cols>' . "\n";
        foreach ($colWidths as $i => $width) {
            $colNum = $i + 1;
            $w = max(11, $width);
            $xml .= "    <col min=\"{$colNum}\" max=\"{$colNum}\" width=\"{$w}\" customWidth=\"1\"/>\n";
        }
        $xml .= '  </cols>' . "\n";

        $xml .= '  <sheetData>' . "\n";

        // Fila 1: Cabeceras
        $xml .= '    <row r="1" ht="28" customHeight="1">' . "\n";
        foreach ($encabezados as $i => $titulo) {
            $ref = self::colLetter($i) . '1';
            $safeTitle = htmlspecialchars(self::sanitizeCellValue($titulo), ENT_XML1, 'UTF-8');
            $xml .= "      <c r=\"{$ref}\" t=\"inlineStr\" s=\"1\"><is><t>{$safeTitle}</t></is></c>\n";
        }
        $xml .= '    </row>' . "\n";

        // Filas de datos
        foreach ($filas as $rowIdx => $fila) {
            $rNum = $rowIdx + 2;
            $xml .= "    <row r=\"{$rNum}\" ht=\"20\" customHeight=\"1\">\n";

            foreach ($fila as $colIdx => $valor) {
                $ref = self::colLetter($colIdx) . $rNum;

                if ($valor === null || $valor === '') {
                    $xml .= "      <c r=\"{$ref}\" s=\"5\"/>\n";
                } elseif (is_int($valor)) {
                    // Entero
                    $xml .= "      <c r=\"{$ref}\" s=\"2\"><v>{$valor}</v></c>\n";
                } elseif (is_float($valor)) {
                    // Decimal
                    $xml .= "      <c r=\"{$ref}\" s=\"4\"><v>{$valor}</v></c>\n";
                } else {
                    $strVal = (string) $valor;
                    $cleanVal = self::sanitizeCellValue($strVal);
                    $safeText = htmlspecialchars($cleanVal, ENT_XML1, 'UTF-8');

                    // Determinar estilo según si parece porcentaje o número
                    if (str_ends_with($strVal, '%') && is_numeric(rtrim(str_replace(',', '', $strVal), '%'))) {
                        $xml .= "      <c r=\"{$ref}\" t=\"inlineStr\" s=\"6\"><is><t>{$safeText}</t></is></c>\n";
                    } elseif (in_array(mb_strtolower($cleanVal), ['activo', 'aprobado', 'exitosa', 'completada', 'sí', 'si', 'utilizada'])) {
                        $xml .= "      <c r=\"{$ref}\" t=\"inlineStr\" s=\"6\"><is><t>{$safeText}</t></is></c>\n";
                    } else {
                        $xml .= "      <c r=\"{$ref}\" t=\"inlineStr\" s=\"5\"><is><t>{$safeText}</t></is></c>\n";
                    }
                }
            }
            $xml .= "    </row>\n";
        }

        $xml .= '  </sheetData>' . "\n";

        // Autofiltro sobre el rango completo
        if ($numCols > 0 && $lastRowIndex >= 1) {
            $xml .= "  <autoFilter ref=\"A1:{$lastColLetter}{$lastRowIndex}\"/>\n";
        }

        if (!empty($opciones['grafica']) || !empty($opciones['graficas'])) {
            $xml .= "  <drawing r:id=\"rId1\"/>\n";
        }

        $xml .= '</worksheet>';

        return $xml;
    }

    /**
     * REPORTE 1: Exportación XLSX de Emociones Registradas.
     */
    public function exportarEmocionesRegistradas(array $datos, string $nombreArchivo): BinaryFileResponse
    {
        $encabezados = ['#', 'Emoción Institucional', 'Total Registros', 'Porcentaje (%)', 'Intensidad Promedio (escala 1–5)'];
        $filas = [];

        foreach ($datos['datos'] as $idx => $item) {
            $filas[] = [
                $idx + 1,
                $item->emocion_nombre,
                (int) $item->total,
                number_format($item->porcentaje, 1) . '%',
                $item->intensidad_promedio !== null ? (float) round($item->intensidad_promedio, 2) : 0.0,
            ];
        }

        if ($datos['total_general'] > 0) {
            $filas[] = [
                '',
                'TOTAL GENERAL',
                (int) $datos['total_general'],
                '100.0%',
                '',
            ];
        }

        $opciones = [];
        if (!empty($datos['datos']) && count($datos['datos']) > 0) {
            $catCache = [];
            $valCacheTotales = [];
            $valCacheIntensidad = [];
            foreach ($datos['datos'] as $item) {
                $catCache[] = (string) $item->emocion_nombre;
                $valCacheTotales[] = (int) $item->total;
                $valCacheIntensidad[] = $item->intensidad_promedio !== null ? (float) round($item->intensidad_promedio, 2) : 0.0;
            }

            $opciones['graficas'] = [
                [
                    'tipo' => 'dona',
                    'titulo' => 'Distribución de Emociones Registradas',
                    'col_cat' => 'B',
                    'col_val' => 'C',
                    'rango_inicio' => 2,
                    'rango_fin' => 1 + count($datos['datos']),
                    'cat_cache' => $catCache,
                    'val_cache' => $valCacheTotales,
                    'from_col' => 6,
                    'from_row' => 1,
                    'to_col' => 14,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'barra',
                    'titulo' => 'Intensidad Promedio por Emoción (1-5)',
                    'col_cat' => 'B',
                    'col_val' => 'E',
                    'rango_inicio' => 2,
                    'rango_fin' => 1 + count($datos['datos']),
                    'cat_cache' => $catCache,
                    'val_cache' => $valCacheIntensidad,
                    'from_col' => 6,
                    'from_row' => 19,
                    'to_col' => 14,
                    'to_row' => 35,
                ],
            ];
        } else {
            $opciones['graficas'] = [
                [
                    'tipo' => 'dona',
                    'titulo' => 'Distribución de Emociones Registradas',
                    'col_cat' => 'B',
                    'col_val' => 'C',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 6,
                    'from_row' => 1,
                    'to_col' => 14,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'barra',
                    'titulo' => 'Intensidad Promedio por Emoción (1-5)',
                    'col_cat' => 'B',
                    'col_val' => 'E',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 6,
                    'from_row' => 19,
                    'to_col' => 14,
                    'to_row' => 35,
                ],
            ];
        }

        return $this->descargarXlsx($nombreArchivo, 'Emociones Registradas', $encabezados, $filas, $opciones);
    }

    /**
     * REPORTE 2: Exportación XLSX de Emociones por Programa Educativo.
     */
    public function exportarEmocionesPorPrograma(array $datos, string $nombreArchivo): BinaryFileResponse
    {
        $encabezados = ['Programa Educativo', 'Siglas', 'Emoción', 'Total Registros', '% del Programa', '% del Total Institucional'];
        $filas = [];

        foreach ($datos['programas'] as $prog) {
            foreach ($prog['emociones'] as $emo) {
                $filas[] = [
                    $prog['nombre'],
                    $prog['siglas'],
                    $emo['emocion'],
                    (int) $emo['cantidad'],
                    number_format($emo['porcentaje_programa'], 1) . '%',
                    number_format(($datos['total_general'] > 0 ? ($emo['cantidad'] / $datos['total_general']) * 100 : 0), 1) . '%',
                ];
            }
        }

        $opciones = [];
        if (count($filas) > 0) {
            $slice = array_slice($filas, 0, min(14, count($filas)));
            $opciones['graficas'] = [
                [
                    'tipo' => 'barra',
                    'titulo' => 'Frecuencia de Emociones por Programa',
                    'col_cat' => 'C',
                    'col_val' => 'D',
                    'rango_inicio' => 2,
                    'rango_fin' => min(15, 1 + count($filas)),
                    'cat_cache' => array_column($slice, 2),
                    'val_cache' => array_column($slice, 3),
                    'from_col' => 7,
                    'from_row' => 1,
                    'to_col' => 15,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'dona',
                    'titulo' => 'Distribución de Registros por Programa',
                    'col_cat' => 'B',
                    'col_val' => 'D',
                    'rango_inicio' => 2,
                    'rango_fin' => min(15, 1 + count($filas)),
                    'cat_cache' => array_column($slice, 1),
                    'val_cache' => array_column($slice, 3),
                    'from_col' => 7,
                    'from_row' => 19,
                    'to_col' => 15,
                    'to_row' => 35,
                ],
            ];
        } else {
            $opciones['graficas'] = [
                [
                    'tipo' => 'barra',
                    'titulo' => 'Frecuencia de Emociones por Programa',
                    'col_cat' => 'C',
                    'col_val' => 'D',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 7,
                    'from_row' => 1,
                    'to_col' => 15,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'dona',
                    'titulo' => 'Distribución de Registros por Programa',
                    'col_cat' => 'B',
                    'col_val' => 'D',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 7,
                    'from_row' => 19,
                    'to_col' => 15,
                    'to_row' => 35,
                ],
            ];
        }

        return $this->descargarXlsx($nombreArchivo, 'Emociones por Programa', $encabezados, $filas, $opciones);
    }

    /**
     * REPORTE 3: Exportación XLSX de Historial Individual por Alumno.
     */
    public function exportarHistorialAlumno(array $datos, string $nombreArchivo): BinaryFileResponse
    {
        $encabezados = ['Fecha y Hora', 'Tipo de Registro', 'Emoción / Modalidad', 'Detalle', 'Estado', 'Intensidad (1–5)', 'Observaciones'];
        $filas = [];

        foreach ($datos['historial'] as $h) {
            $intensidad = isset($h['intensidad']) && is_numeric($h['intensidad']) ? (float) $h['intensidad'] : 0.0;
            $filas[] = [
                $h['fecha_formateada'],
                $h['tipo'],
                $h['emocion_evento'],
                $h['detalle'],
                $h['estado'],
                $intensidad > 0 ? $intensidad : '',
                $h['observaciones'],
            ];
        }

        $opciones = [];
        if (count($filas) > 0) {
            $slice = array_slice($filas, 0, min(20, count($filas)));
            $catCache = array_column($slice, 0);
            $valCacheInt = array_map(fn($f) => is_numeric($f[5]) ? (float) $f[5] : 0.0, $slice);

            $opciones['graficas'] = [
                [
                    'tipo' => 'linea',
                    'titulo' => 'Evolución de Intensidad Emocional',
                    'col_cat' => 'A',
                    'col_val' => 'F',
                    'rango_inicio' => 2,
                    'rango_fin' => min(21, 1 + count($filas)),
                    'cat_cache' => $catCache,
                    'val_cache' => $valCacheInt,
                    'from_col' => 8,
                    'from_row' => 1,
                    'to_col' => 16,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'barra',
                    'titulo' => 'Eventos por Tipo de Registro',
                    'col_cat' => 'B',
                    'col_val' => 'F',
                    'rango_inicio' => 2,
                    'rango_fin' => min(21, 1 + count($filas)),
                    'cat_cache' => array_column($slice, 1),
                    'val_cache' => $valCacheInt,
                    'from_col' => 8,
                    'from_row' => 19,
                    'to_col' => 16,
                    'to_row' => 35,
                ],
            ];
        } else {
            $opciones['graficas'] = [
                [
                    'tipo' => 'linea',
                    'titulo' => 'Evolución de Intensidad Emocional',
                    'col_cat' => 'A',
                    'col_val' => 'F',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 8,
                    'from_row' => 1,
                    'to_col' => 16,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'barra',
                    'titulo' => 'Eventos por Tipo de Registro',
                    'col_cat' => 'B',
                    'col_val' => 'F',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 8,
                    'from_row' => 19,
                    'to_col' => 16,
                    'to_row' => 35,
                ],
            ];
        }

        return $this->descargarXlsx($nombreArchivo, 'Historial del Alumno', $encabezados, $filas, $opciones);
    }

    /**
     * REPORTE 4: Exportación XLSX de Premios y Recompensas Más Canjeados.
     */
    public function exportarPremiosCanjeados(array $datos, string $nombreArchivo): BinaryFileResponse
    {
        $encabezados = [
            'Recompensa / Premio',
            'Costo Puntos Base',
            'Total Canjes',
            'Canjes Exitosos / Utilizados',
            'Canjes Pendientes',
            'Canjes Rechazados',
            'Puntos Totales Redimidos',
            '% del Total',
        ];
        $filas = [];

        foreach (($datos['datos'] ?? []) as $d) {
            $filas[] = [
                $d->recompensa_titulo,
                (int) $d->costo_puntos_base,
                (int) $d->total_canjes,
                (int) $d->canjes_exitosos,
                (int) $d->canjes_pendientes,
                (int) $d->canjes_rechazados,
                (int) $d->puntos_totales_canjeados,
                number_format($d->porcentaje_uso, 1) . '%',
            ];
        }

        if (($datos['total_general'] ?? 0) > 0) {
            $filas[] = [
                'TOTAL CONSOLIDADO',
                '',
                (int) $datos['total_general'],
                '',
                '',
                '',
                (int) ($datos['puntos_totales'] ?? 0),
                '100.0%',
            ];
        }

        $opciones = [];
        if (!empty($datos['datos']) && count($datos['datos']) > 0) {
            $catCache = [];
            $valCacheTotales = [];
            $valCacheExitosos = [];
            foreach (($datos['datos'] ?? []) as $d) {
                $catCache[] = (string) $d->recompensa_titulo;
                $valCacheTotales[] = (int) $d->total_canjes;
                $valCacheExitosos[] = (int) $d->canjes_exitosos;
            }

            $opciones['graficas'] = [
                [
                    'tipo' => 'barra',
                    'titulo' => 'Total de Canjes por Recompensa',
                    'col_cat' => 'A',
                    'col_val' => 'C',
                    'rango_inicio' => 2,
                    'rango_fin' => 1 + count($datos['datos']),
                    'cat_cache' => $catCache,
                    'val_cache' => $valCacheTotales,
                    'from_col' => 9,
                    'from_row' => 1,
                    'to_col' => 17,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'dona',
                    'titulo' => 'Proporción de Canjes por Recompensa',
                    'col_cat' => 'A',
                    'col_val' => 'C',
                    'rango_inicio' => 2,
                    'rango_fin' => 1 + count($datos['datos']),
                    'cat_cache' => $catCache,
                    'val_cache' => $valCacheTotales,
                    'from_col' => 9,
                    'from_row' => 19,
                    'to_col' => 17,
                    'to_row' => 35,
                ],
            ];
        } else {
            $opciones['graficas'] = [
                [
                    'tipo' => 'barra',
                    'titulo' => 'Total de Canjes por Recompensa',
                    'col_cat' => 'A',
                    'col_val' => 'C',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 9,
                    'from_row' => 1,
                    'to_col' => 17,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'dona',
                    'titulo' => 'Proporción de Canjes por Recompensa',
                    'col_cat' => 'A',
                    'col_val' => 'C',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 9,
                    'from_row' => 19,
                    'to_col' => 17,
                    'to_row' => 35,
                ],
            ];
        }

        return $this->descargarXlsx($nombreArchivo, 'Premios Canjeados', $encabezados, $filas, $opciones);
    }

    /**
     * REPORTE 5: Exportación XLSX de Sesiones Registradas por Día (Multi-hoja).
     * Hoja 1: Resumen Diario (totales, únicos y desglose de roles separados)
     * Hoja 2: Detalle Individual de Accesos (cronológico de logins)
     * Hoja 3: Indicadores Clave (métricas del periodo)
     * Hoja 4: Metodología y Criterios Institucionales
     */
    public function exportarSesionesPorDia(array $datos, string $nombreArchivo, ?string $rolSeleccionado = null): BinaryFileResponse
    {
        // Hoja 1: Resumen Diario
        $encabezadosResumen = [
            'Fecha',
            'Total de Accesos',
            'Accesos Exitosos',
            'Accesos Fallidos',
            'Usuarios Únicos',
            'Alumnos',
            'Docentes',
            'Orientadores',
            'Administradores',
        ];
        $filasResumen = [];
        $sumAlumnos = 0;
        $sumDocentes = 0;
        $sumOrientadores = 0;
        $sumAdmins = 0;
        $sumExitosos = 0;
        $sumFallidos = 0;

        foreach (($datos['resumen_diario'] ?? []) as $r) {
            $rArr = (array) $r;
            $exitosos = (int) ($rArr['accesos_exitosos'] ?? ($rArr['total_accesos'] ?? 0));
            $fallidos = (int) ($rArr['accesos_fallidos'] ?? 0);
            $sumExitosos += $exitosos;
            $sumFallidos += $fallidos;
            $sumAlumnos += (int) ($rArr['accesos_alumnos'] ?? 0);
            $sumDocentes += (int) ($rArr['accesos_docentes'] ?? 0);
            $sumOrientadores += (int) ($rArr['accesos_orientadores'] ?? 0);
            $sumAdmins += (int) ($rArr['accesos_admins'] ?? 0);

            $filasResumen[] = [
                $rArr['fecha'] ?? '',
                (int) ($rArr['total_accesos'] ?? 0),
                $exitosos,
                $fallidos,
                (int) ($rArr['usuarios_unicos'] ?? 0),
                (int) ($rArr['accesos_alumnos'] ?? 0),
                (int) ($rArr['accesos_docentes'] ?? 0),
                (int) ($rArr['accesos_orientadores'] ?? 0),
                (int) ($rArr['accesos_admins'] ?? 0),
            ];
        }

        if (count($filasResumen) > 0) {
            $filasResumen[] = [
                'TOTALES DEL PERIODO',
                (int) ($datos['total_general'] ?? 0),
                $sumExitosos,
                $sumFallidos,
                (int) ($datos['total_usuarios_unicos'] ?? 0),
                $sumAlumnos,
                $sumDocentes,
                $sumOrientadores,
                $sumAdmins,
            ];
        }

        // Hoja 2: Detalle Individual de Accesos
        $encabezadosDetalle = [
            '#',
            'Fecha y Hora',
            'Nombre del Usuario',
            'Rol Institucional',
            'Correo Institucional',
            'Dirección IP',
            'Estado del Acceso',
        ];
        $filasDetalle = [];
        $detalle = $datos['detalle_accesos'] ?? collect();
        foreach ($detalle as $idx => $a) {
            $filasDetalle[] = [
                $idx + 1,
                $a->fecha_acceso ? \Carbon\Carbon::parse($a->fecha_acceso)->format('d/m/Y H:i:s') : '-',
                $a->nombre_usuario ?? 'Desconocido',
                $a->rol_nombre ?? 'Sin Rol',
                $a->email ?? 'N/A',
                $a->ip_address ?? 'N/A',
                $a->exitoso ? 'Exitoso' : 'Fallido',
            ];
        }

        // Hoja 3: Indicadores Clave
        $encabezadosIndicadores = ['Indicador Institucional', 'Valor Registrado', 'Unidad / Criterio de Medición'];
        $filasIndicadores = [
            ['Total de Accesos al Sistema', (int) $datos['total_general'], 'Eventos registrados en el periodo'],
            ['Usuarios Únicos del Periodo', (int) $datos['total_usuarios_unicos'], 'Cuentas institucionales distintas desduplicadas'],
            ['Jornadas con Actividad', (int) ($datos['dias_con_actividad'] ?? count($filasResumen)), 'Días con al menos un acceso registrado'],
            ['Promedio Diario de Accesos', (float) ($datos['promedio_diario'] ?? 0), 'Accesos promedio por día activo'],
            ['Filtro por Rol Aplicado', $rolSeleccionado ?: 'Todos los roles institucionales', 'Segmento analizado'],
            ['Fecha y Hora de Generación', $datos['fecha_generacion'] ?? now()->format('d/m/Y H:i'), 'Hora del Centro de México (America/Mexico_City)'],
        ];

        // Hoja 4: Metodología y Notas
        $encabezadosMetodologia = ['Parámetro Metodológico', 'Descripción y Consideraciones Institucionales'];
        $filasMetodologia = [
            ['Cálculo de Usuarios Únicos', 'La métrica institucional de "Usuarios Únicos del Periodo" se calcula desduplicando los IDs de usuario sobre el conjunto completo de registros del rango seleccionado. No corresponde a la suma aritmética de los usuarios únicos diarios, ya que un mismo estudiante o colaborador puede autenticarse en días distintos.'],
            ['Separación de Orientadores y Administradores', 'El personal Orientador y el personal Administrador se presentan en columnas y categorías separadas para reflejar fielmente las actividades de acompañamiento psicopedagógico frente a las de administración técnica de la plataforma.'],
            ['Integridad y Confidencialidad', 'El sistema protege los datos sensibles de los usuarios. No se almacenan contraseñas en texto claro ni tokens de sesión. Las direcciones IP se resguardan para trazabilidad y seguridad institucional.'],
            ['Estandarización de Horarios', 'Todos los sellos de tiempo de login se generan y presentan en la zona horaria institucional de México (America/Mexico_City).'],
        ];

        $opcionesResumen = [];
        if (!empty($datos['resumen_diario']) && count($datos['resumen_diario']) > 0) {
            $catCache = [];
            $valCacheAccesos = [];
            $valCacheExitosos = [];
            $valCacheFallidos = [];
            foreach ($datos['resumen_diario'] as $filaDiaria) {
                $r = (array) $filaDiaria; // Acepta stdClass o arreglo sin provocar error de acceso
                $catCache[] = (string) ($r['fecha'] ?? '');
                $valCacheAccesos[] = (int) ($r['total_accesos'] ?? 0);
                $valCacheExitosos[] = (int) ($r['accesos_exitosos'] ?? ($r['total_accesos'] ?? 0));
                $valCacheFallidos[] = (int) ($r['accesos_fallidos'] ?? 0);
            }

            $opcionesResumen['graficas'] = [
                [
                    'tipo' => 'linea',
                    'titulo' => 'Volumen Diario de Accesos al Sistema',
                    'col_cat' => 'A',
                    'col_val' => 'B',
                    'rango_inicio' => 2,
                    'rango_fin' => 1 + count($datos['resumen_diario']),
                    'cat_cache' => $catCache,
                    'val_cache' => $valCacheAccesos,
                    'from_col' => 10,
                    'from_row' => 1,
                    'to_col' => 18,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'barra',
                    'titulo' => 'Accesos Exitosos vs Intentos Fallidos',
                    'col_cat' => 'A',
                    'col_val' => 'C',
                    'rango_inicio' => 2,
                    'rango_fin' => 1 + count($datos['resumen_diario']),
                    'cat_cache' => $catCache,
                    'val_cache' => $valCacheExitosos,
                    'series' => [
                        [
                            'col_val' => 'C',
                            'titulo' => 'Accesos Exitosos',
                            'val_cache' => $valCacheExitosos,
                        ],
                        [
                            'col_val' => 'D',
                            'titulo' => 'Accesos Fallidos',
                            'val_cache' => $valCacheFallidos,
                        ],
                    ],
                    'from_col' => 10,
                    'from_row' => 19,
                    'to_col' => 18,
                    'to_row' => 35,
                ],
            ];
        } else {
            $opcionesResumen['graficas'] = [
                [
                    'tipo' => 'linea',
                    'titulo' => 'Volumen Diario de Accesos al Sistema',
                    'col_cat' => 'A',
                    'col_val' => 'B',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 10,
                    'from_row' => 1,
                    'to_col' => 18,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'barra',
                    'titulo' => 'Accesos Exitosos vs Intentos Fallidos',
                    'col_cat' => 'A',
                    'col_val' => 'C',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'series' => [
                        [
                            'col_val' => 'C',
                            'titulo' => 'Accesos Exitosos',
                            'val_cache' => [0],
                        ],
                        [
                            'col_val' => 'D',
                            'titulo' => 'Accesos Fallidos',
                            'val_cache' => [0],
                        ],
                    ],
                    'from_col' => 10,
                    'from_row' => 19,
                    'to_col' => 18,
                    'to_row' => 35,
                ],
            ];
        }

        $hojas = [
            [
                'titulo' => 'Resumen Diario',
                'encabezados' => $encabezadosResumen,
                'filas' => $filasResumen,
                'opciones' => $opcionesResumen,
            ],
            [
                'titulo' => 'Detalle de Accesos',
                'encabezados' => $encabezadosDetalle,
                'filas' => $filasDetalle,
            ],
            [
                'titulo' => 'Indicadores Clave',
                'encabezados' => $encabezadosIndicadores,
                'filas' => $filasIndicadores,
            ],
            [
                'titulo' => 'Metodología',
                'encabezados' => $encabezadosMetodologia,
                'filas' => $filasMetodologia,
            ],
        ];

        return $this->descargarXlsxMultiHoja($nombreArchivo, $hojas);
    }

    /**
     * REPORTE 6: Exportación XLSX de Resultados Generales de Tests Psicométricos.
     */
    public function exportarResultadosTests(array $datos, string $nombreArchivo): BinaryFileResponse
    {
        $encabezados = [
            'Folio Intento',
            'Fecha Finalización',
            'Instrumento / Test',
            'Matrícula',
            'Estudiante',
            'Programa Educativo',
            'Puntuación Total',
            'Clasificación / Nivel',
            'Alerta Crítica',
        ];
        $filas = [];

        $intentosRaw = $datos['intentos'] ?? ($datos['detalle_intentos'] ?? []);
        $intentosList = is_array($intentosRaw)
            ? $intentosRaw
            : ($intentosRaw instanceof \Illuminate\Support\Collection ? $intentosRaw->toArray() : (array) $intentosRaw);

        foreach ($intentosList as $i) {
            $iArr = (array) $i;
            $filas[] = [
                '#INT-' . ($iArr['intento_id'] ?? ''),
                $iArr['fecha_finalizacion'] ?? '',
                $iArr['instrumento_nombre'] ?? '',
                $iArr['matricula'] ?? '',
                $iArr['estudiante_nombre'] ?? '',
                $iArr['programa_nombre'] ?? '',
                $iArr['puntuacion_total'] ?? 0,
                $iArr['nivel_resultado'] ?? '',
                !empty($iArr['tiene_alerta_critica']) ? 'SÍ' : 'NO',
            ];
        }

        $opciones = [];

        if (!empty($intentosList) && count($intentosList) > 0) {
            $slice = array_slice($intentosList, 0, min(19, count($intentosList)));
            $opciones['graficas'] = [
                [
                    'tipo' => 'barra',
                    'titulo' => 'Puntuaciones Obtenidas por Test',
                    'col_cat' => 'C',
                    'col_val' => 'G',
                    'rango_inicio' => 2,
                    'rango_fin' => min(20, 1 + count($intentosList)),
                    'cat_cache' => array_column($slice, 'instrumento_nombre'),
                    'val_cache' => array_column($slice, 'puntuacion_total'),
                    'from_col' => 10,
                    'from_row' => 1,
                    'to_col' => 18,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'dona',
                    'titulo' => 'Distribución por Nivel de Riesgo',
                    'col_cat' => 'H',
                    'col_val' => 'G',
                    'rango_inicio' => 2,
                    'rango_fin' => min(20, 1 + count($intentosList)),
                    'cat_cache' => array_column($slice, 'nivel_resultado'),
                    'val_cache' => array_column($slice, 'puntuacion_total'),
                    'from_col' => 10,
                    'from_row' => 19,
                    'to_col' => 18,
                    'to_row' => 35,
                ],
            ];
        } else {
            $opciones['graficas'] = [
                [
                    'tipo' => 'barra',
                    'titulo' => 'Puntuaciones Obtenidas por Test',
                    'col_cat' => 'C',
                    'col_val' => 'G',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 10,
                    'from_row' => 1,
                    'to_col' => 18,
                    'to_row' => 17,
                ],
                [
                    'tipo' => 'dona',
                    'titulo' => 'Distribución por Nivel de Riesgo',
                    'col_cat' => 'H',
                    'col_val' => 'G',
                    'rango_inicio' => 2,
                    'rango_fin' => 2,
                    'cat_cache' => ['Sin registros'],
                    'val_cache' => [0],
                    'from_col' => 10,
                    'from_row' => 19,
                    'to_col' => 18,
                    'to_row' => 35,
                ],
            ];
        }

        return $this->descargarXlsx($nombreArchivo, 'Resultados de Tests', $encabezados, $filas, $opciones);
    }
}

