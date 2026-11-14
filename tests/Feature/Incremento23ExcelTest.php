<?php

namespace Tests\Feature;

use App\Models\BitacoraEmocional;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\InstrumentoEvaluacion;
use App\Models\LoginAcceso;
use App\Models\Meditacion;
use App\Models\MeditacionSesion;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\PuntoMovimiento;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Services\Reportes\ExcelReporteService;
use App\Services\Reportes\ReporteInstitucionalService;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class Incremento23ExcelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $alumno;
    protected Estudiante $estudiante;
    protected ProgramaEducativo $pe;
    protected Emocion $emocion;
    protected PeriodoAcademico $periodo;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 1, 'nombre' => 'Administrador']);
        $rolAlumno = Rol::create(['rol_id' => 3, 'nombre' => 'Alumno']);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-04-30',
            'activo' => true,
        ]);

        $this->pe = ProgramaEducativo::create([
            'programa_id' => 1,
            'clave' => 'ITI',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);

        $this->admin = User::factory()->create([
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'admin_test@upemor.edu.mx',
        ]);

        $this->alumno = User::factory()->create([
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'alumno_test@upemor.edu.mx',
        ]);

        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->pe->programa_id,
            'matricula' => 'ITI2026999',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 350,
        ]);

        $this->emocion = Emocion::create([
            'nombre' => 'Calma',
            'slug' => 'calma',
            'color_hex' => '#06b6d4',
            'activo' => true,
        ]);

        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocion->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 5,
            'descripcion' => 'Registro de prueba para reporte de emociones',
        ]);

        $meditacion = Meditacion::create([
            'titulo' => 'Respiración Profunda',
            'descripcion' => 'Sesión de relajación y respiración consciente',
            'instrucciones' => [['paso' => 1, 'titulo' => 'Inhala', 'duracion_segundos' => 10, 'indicacion' => 'Respira suave', 'fase' => 'inicio']],
            'duracion_minutos' => 10,
            'es_publicado' => true,
        ]);

        MeditacionSesion::create([
            'usuario_id' => $this->alumno->usuario_id,
            'meditacion_id' => $meditacion->meditacion_id,
            'fecha_sesion' => now(),
            'completada' => true,
            'duracion_segundos' => 600,
        ]);

        $asigModel = \App\Models\Asignatura::create([
            'programa_id' => $this->pe->programa_id,
            'clave' => 'ASIG-01',
            'nombre' => 'Materia Test',
            'cuatrimestre' => 1,
            'creditos' => 5,
            'activo' => true,
        ]);

        $grupo = \App\Models\Grupo::create([
            'programa_id' => $this->pe->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'codigo' => 'ITI-4A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $asignacion = \App\Models\DocenteAsignacion::create([
            'docente_id' => $this->admin->usuario_id,
            'asignatura_id' => $asigModel->asignatura_id,
            'grupo_id' => $grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'estado_asignacion' => 'APROBADA',
            'fecha_resolucion' => now(),
        ]);

        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Termo UPEMOR',
            'descripcion' => 'Termo institucional',
            'costo_puntos_base' => 100,
            'activo' => true,
        ]);

        $activada = RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $asignacion->asignacion_id,
            'vigencia_inicio' => now()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
            'cupo_maximo' => 10,
            'cupo_disponible' => 9,
            'costo_puntos' => 100,
            'estado' => 'ACTIVA',
        ]);

        RecompensaCanje::create([
            'activacion_id' => $activada->activacion_id,
            'estudiante_id' => $this->alumno->usuario_id,
            'puntos_canjeados' => 100,
            'estado' => 'APROBADO',
            'fecha_solicitud' => now(),
            'created_at' => now(),
        ]);

        LoginAcceso::create([
            'usuario_id' => $this->alumno->usuario_id,
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'exitoso' => true,
            'fecha_acceso' => now(),
            'created_at' => now(),
        ]);

        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'TEST-01',
            'nombre' => 'Evaluación de Bienestar',
            'categoria' => 'ANSIEDAD',
            'activo' => true,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => 1,
            'instrucciones' => 'Por favor responda sinceramente a las preguntas.',
            'activa' => true,
        ]);

        EvaluacionIntento::create([
            'version_id' => $version->version_id,
            'usuario_id' => $this->alumno->usuario_id,
            'estado' => 'FINALIZADO',
            'puntaje_total' => 85,
            'interpretacion' => 'Nivel adecuado de bienestar',
            'fecha_inicio' => now()->subMinutes(10),
            'fecha_fin' => now(),
        ]);
    }

    /**
     * Valida la estructura OpenXML de un archivo XLSX generado en memoria o en disco.
     */
    protected function assertValidOpenXmlArchive(string $binaryData, bool $expectCharts = true): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_val_');
        file_put_contents($tempPath, $binaryData);

        $zip = new ZipArchive();
        $res = $zip->open($tempPath);
        $this->assertTrue($res === true, "El archivo generado debe ser un archivo ZIP válido.");

        $xmlFilesToCheck = [
            '[Content_Types].xml',
            'xl/workbook.xml',
            'xl/worksheets/sheet1.xml',
            '_rels/.rels',
            'xl/_rels/workbook.xml.rels',
        ];

        if ($expectCharts) {
            $xmlFilesToCheck = array_merge($xmlFilesToCheck, [
                'xl/drawings/drawing1.xml',
                'xl/charts/chart1.xml',
                'xl/charts/chart2.xml',
                'xl/drawings/_rels/drawing1.xml.rels',
            ]);
        }

        foreach ($xmlFilesToCheck as $xmlFile) {
            $content = $zip->getFromName($xmlFile);
            $this->assertNotEmpty($content, "El archivo [{$xmlFile}] debe existir dentro del paquete XLSX.");

            $dom = new DOMDocument();
            $dom->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);
            $this->assertEmpty(
                libxml_get_errors(),
                "El archivo [{$xmlFile}] tiene errores de sintaxis XML: " . json_encode(libxml_get_errors())
            );
            libxml_clear_errors();
        }

        if ($expectCharts) {
            $drawingXml = $zip->getFromName('xl/drawings/drawing1.xml');
            $this->assertEquals(2, substr_count($drawingXml, '<xdr:twoCellAnchor'), 'El dibujo debe contener exactamente 2 gráficos.');
        }

        $zip->close();
        @unlink($tempPath);
    }

    public function test_reporte1_emociones_registradas_genera_xlsx_valido_con_dos_graficos(): void
    {
        $reporteService = app(ReporteInstitucionalService::class);
        $excelService = app(ExcelReporteService::class);

        $datos = $reporteService->reporteEmocionesRegistradas([]);
        $response = $excelService->exportarEmocionesRegistradas($datos, 'reporte1.xlsx');
        $binary = file_get_contents($response->getFile()->getPathname());

        $this->assertValidOpenXmlArchive($binary);
    }

    public function test_reporte2_emociones_por_programa_genera_xlsx_valido_con_dos_graficos(): void
    {
        $reporteService = app(ReporteInstitucionalService::class);
        $excelService = app(ExcelReporteService::class);

        $datos = $reporteService->reporteEmocionesPorPrograma([]);
        $response = $excelService->exportarEmocionesPorPrograma($datos, 'reporte2.xlsx');
        $binary = file_get_contents($response->getFile()->getPathname());

        $this->assertValidOpenXmlArchive($binary);
    }

    public function test_reporte3_historial_alumno_genera_xlsx_valido_con_dos_graficos(): void
    {
        $reporteService = app(ReporteInstitucionalService::class);
        $excelService = app(ExcelReporteService::class);

        $datos = $reporteService->reporteHistorialAlumno(['estudiante_id' => $this->estudiante->estudiante_id]);
        $response = $excelService->exportarHistorialAlumno($datos, 'reporte3.xlsx');
        $binary = file_get_contents($response->getFile()->getPathname());

        $this->assertValidOpenXmlArchive($binary);
    }

    public function test_reporte4_premios_canjeados_genera_xlsx_valido_con_dos_graficos(): void
    {
        $reporteService = app(ReporteInstitucionalService::class);
        $excelService = app(ExcelReporteService::class);

        $datos = $reporteService->reportePremiosCanjeados([]);
        $response = $excelService->exportarPremiosCanjeados($datos, 'reporte4.xlsx');
        $binary = file_get_contents($response->getFile()->getPathname());

        $this->assertValidOpenXmlArchive($binary);
    }

    public function test_reporte5_sesiones_por_dia_genera_xlsx_valido_con_dos_graficos(): void
    {
        $reporteService = app(ReporteInstitucionalService::class);
        $excelService = app(ExcelReporteService::class);

        $datos = $reporteService->reporteSesionesPorDia([], false);
        $response = $excelService->exportarSesionesPorDia($datos, 'reporte5.xlsx');
        $binary = file_get_contents($response->getFile()->getPathname());

        $this->assertValidOpenXmlArchive($binary);
    }

    public function test_reporte6_resultados_tests_genera_xlsx_valido_con_dos_graficos(): void
    {
        $reporteService = app(ReporteInstitucionalService::class);
        $excelService = app(ExcelReporteService::class);

        $datos = $reporteService->reporteResultadosTests([]);
        $response = $excelService->exportarResultadosTests($datos, 'reporte6.xlsx');
        $binary = file_get_contents($response->getFile()->getPathname());

        $this->assertValidOpenXmlArchive($binary);
    }

    public function test_reportes_con_conjuntos_vacios_no_corrompen_xml(): void
    {
        $excelService = app(ExcelReporteService::class);

        // Estructura vacía para R4 (premios)
        $datosVaciosR4 = [
            'total_canjes' => 0,
            'puntos_totales_canjeados' => 0,
            'distribucion_estados' => [],
            'canjes_por_programa' => [],
            'top_premios' => [],
            'detalle_canjes' => [],
        ];
        $responseR4 = $excelService->exportarPremiosCanjeados($datosVaciosR4, 'vacio_r4.xlsx');
        $binaryR4 = file_get_contents($responseR4->getFile()->getPathname());
        $this->assertValidOpenXmlArchive($binaryR4, false);

        // Estructura vacía para R5 (sesiones por día)
        $datosVaciosR5 = [
            'total_general' => 0,
            'total_usuarios_unicos' => 0,
            'promedio_diario' => 0,
            'resumen_diario' => [],
            'detalle_accesos' => [],
        ];
        $responseR5 = $excelService->exportarSesionesPorDia($datosVaciosR5, 'vacio_r5.xlsx');
        $binaryR5 = file_get_contents($responseR5->getFile()->getPathname());
        $this->assertValidOpenXmlArchive($binaryR5, false);

        // Estructura vacía para R6 (resultados tests)
        $datosVaciosR6 = [
            'total_intentos' => 0,
            'estudiantes_evaluados' => 0,
            'distribucion_clasificacion' => [],
            'intentos_por_instrumento' => [],
            'detalle_intentos' => [],
        ];
        $responseR6 = $excelService->exportarResultadosTests($datosVaciosR6, 'vacio_r6.xlsx');
        $binaryR6 = file_get_contents($responseR6->getFile()->getPathname());
        $this->assertValidOpenXmlArchive($binaryR6, false);
    }

    public function test_admin_puede_descargar_los_seis_reportes_por_ruta_http(): void
    {
        $tipos = [
            'emociones-registradas',
            'emociones-por-programa',
            'historial-alumno',
            'premios-canjeados',
            'sesiones-por-dia',
            'resultados-tests',
        ];

        foreach ($tipos as $tipo) {
            $response = $this->actingAs($this->admin)->get(route('admin.reportes.exportar-excel', [
                'tipo' => $tipo,
                'estudiante_id' => $this->estudiante->estudiante_id,
            ]));

            $response->assertStatus(200);
            $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        }
    }
}
