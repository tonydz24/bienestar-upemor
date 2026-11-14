<?php

namespace Tests\Feature;

use App\Models\BitacoraEmocional;
use App\Models\Emocion;
use App\Models\EscalaOpcion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\InstrumentoEvaluacion;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Services\Reportes\ExcelReporteService;
use App\Services\Reportes\ReporteInstitucionalService;
use App\Services\Respaldos\RespaldoDatabaseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CorreccionAceptacion041Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $alumno;
    private Estudiante $estudiante;
    private ProgramaEducativo $programa;
    private PeriodoAcademico $periodo;
    private array $archivosGenerados = [];

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['rol_id' => 1]);
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador'], ['rol_id' => 3]);

        $this->programa = ProgramaEducativo::firstOrCreate(['clave' => 'ITI'], [
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);

        $this->periodo = PeriodoAcademico::firstOrCreate(['clave' => '2026-3'], [
            'nombre' => '2026-3 — Septiembre a Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-31',
            'activo' => true,
        ]);

        $this->admin = User::firstOrCreate(['email' => 'admin.test041@upemor.edu.mx'], [
            'rol_id' => $rolAdmin->rol_id,
            'nombre' => 'Admin',
            'apellido_paterno' => 'Test',
            'password' => 'AdminPass123!',
            'estado' => 'ACTIVO',
        ]);
        $this->admin->roles()->sync([$rolAdmin->rol_id]);

        $this->alumno = User::firstOrCreate(['email' => 'carlos.ramirez041@upemor.edu.mx'], [
            'rol_id' => $rolAlumno->rol_id,
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
            'password' => 'AlumnoPass123!',
            'estado' => 'ACTIVO',
        ]);
        $this->alumno->roles()->sync([$rolAlumno->rol_id]);

        $this->estudiante = Estudiante::firstOrCreate(['usuario_id' => $this->alumno->usuario_id], [
            'matricula' => 'ITI2026041',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'saldo_puntos' => 250,
        ]);

        $this->archivosGenerados = [];
    }

    protected function tearDown(): void
    {
        $service = app(RespaldoDatabaseService::class);
        foreach ($this->archivosGenerados as $archivo) {
            $service->eliminarRespaldo($archivo);
        }

        // Limpiar preventivos generados durante las pruebas
        $todos = $service->listarRespaldos();
        foreach ($todos as $respaldo) {
            if (str_starts_with($respaldo['nombre'], 'pre_restore_backup') || str_starts_with($respaldo['nombre'], 'test_restore')) {
                $service->eliminarRespaldo($respaldo['nombre']);
            }
        }

        parent::tearDown();
    }

    /**
     * PRIORIDAD 1: Verificación de que la restauración física recupera a un alumno eliminado.
     */
    public function test_restauracion_fisica_recupera_usuario_alumno_eliminado(): void
    {
        $service = app(RespaldoDatabaseService::class);

        // 1. Crear respaldo inicial conteniendo al alumno
        $creacion = $service->crearRespaldo('test_restore_base');
        $this->assertTrue($creacion['success']);
        $archivoRespaldo = $creacion['nombre'];
        $this->archivosGenerados[] = $archivoRespaldo;

        // 2. Eliminar al alumno y su perfil estudiantil de la base de datos
        $alumnoId = $this->alumno->usuario_id;
        $this->estudiante->delete();
        $this->alumno->roles()->detach();
        $this->alumno->delete();

        // 3. Comprobar que el alumno NO existe en la base
        $this->assertNull(User::find($alumnoId));
        $this->assertNull(Estudiante::where('usuario_id', $alumnoId)->first());

        // 4. Ejecutar la restauración física del respaldo previo
        $resultado = $service->ejecutarRestauracionFisica($archivoRespaldo, 'AdminPass123!', $this->admin);

        // 5. Verificar que la restauración fue exitosa y física (no simulada)
        $this->assertTrue($resultado['success']);
        $this->assertFalse($resultado['simulado']);
        $this->assertNotEmpty($resultado['sha256']);
        $this->assertNotEmpty($resultado['respaldo_preventivo']);

        // 6. Comprobar que el alumno y su perfil REAPARECIERON en la base de datos
        $alumnoRestaurado = User::find($alumnoId);
        $this->assertNotNull($alumnoRestaurado, 'El alumno eliminado debe reaparecer tras restaurar el respaldo.');
        $this->assertEquals('carlos.ramirez041@upemor.edu.mx', $alumnoRestaurado->email);

        $estudianteRestaurado = Estudiante::where('usuario_id', $alumnoId)->first();
        $this->assertNotNull($estudianteRestaurado, 'El perfil del estudiante debe ser recuperado.');
        $this->assertEquals('ITI2026041', $estudianteRestaurado->matricula);
    }

    /**
     * PRIORIDAD 1: Flujo HTTP de restauración con confirmación de contraseña y alertas visibles.
     */
    public function test_admin_restaura_respaldo_via_http_con_alertas_completas(): void
    {
        $service = app(RespaldoDatabaseService::class);
        $creacion = $service->crearRespaldo('test_restore_http');
        $this->assertTrue($creacion['success']);
        $archivo = $creacion['nombre'];
        $this->archivosGenerados[] = $archivo;

        $response = $this->actingAs($this->admin)->post(route('admin.respaldos.solicitar-restauracion', $archivo), [
            'password' => 'AdminPass123!',
        ]);

        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('status');

        $mensaje = session('success');
        $this->assertStringContainsString('exitosamente', $mensaje);
        $this->assertStringContainsString('preventiv', $mensaje);
    }

    /**
     * PRIORIDAD 1: El listado administrativo muestra los 4 botones diferenciados con sus clases y etiquetas.
     */
    public function test_interfaz_respaldos_muestra_acciones_claramente_diferenciadas(): void
    {
        $service = app(RespaldoDatabaseService::class);
        $creacion = $service->crearRespaldo('test_ui_acciones');
        $archivo = $creacion['nombre'];
        $this->archivosGenerados[] = $archivo;

        $response = $this->actingAs($this->admin)->get(route('admin.respaldos.index'));

        $response->assertStatus(200);
        $response->assertSee('Generar Respaldo Ahora');
        $response->assertSee('Descargar');
        $response->assertSee('Verificar');
        $response->assertSee('Restaurar');
        $response->assertSee('Eliminar');
        $response->assertSee('modalRestaurarRespaldo_0', false);
        $response->assertSee('modalEliminarRespaldo_0', false);
    }

    /**
     * PRIORIDAD 2: Exportación PDF del reporte de Premios Más Canjeados incluye gráficas SVG, resumen y tablas.
     */
    public function test_reporte_premios_canjeados_pdf_incluye_graficas_y_resumen(): void
    {
        $rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['rol_id' => 2]);
        $docenteUser = User::firstOrCreate(['email' => 'docente.test041@upemor.edu.mx'], [
            'rol_id' => $rolDocente->rol_id,
            'nombre' => 'Docente',
            'apellido_paterno' => 'Test',
            'estado' => 'ACTIVO',
            'password' => 'DocentePass123!',
        ]);
        \App\Models\Docente::firstOrCreate(['usuario_id' => $docenteUser->usuario_id], [
            'numero_empleado' => 'EMP-TEST-041',
            'activo' => true,
        ]);
        $asignatura = \App\Models\Asignatura::firstOrCreate(['clave' => 'ITI-TEST'], [
            'programa_id' => $this->programa->programa_id,
            'nombre' => 'Materia Test',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);
        $grupo = \App\Models\Grupo::firstOrCreate(['programa_id' => $this->programa->programa_id, 'periodo_id' => $this->periodo->periodo_id, 'nombre' => 'B'], [
            'cuatrimestre' => 4,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);
        $asignacion = \App\Models\DocenteAsignacion::firstOrCreate([
            'docente_id' => $docenteUser->usuario_id,
            'asignatura_id' => $asignatura->asignatura_id,
            'grupo_id' => $grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
        ], [
            'activo' => true,
        ]);

        $recompensa = RecompensaCatalogo::firstOrCreate(['nombre' => 'Boleto Cafetería UPEMOR'], [
            'descripcion' => 'Boleto para desayuno o almuerzo institucional',
            'costo_puntos_base' => 100,
            'stock_disponible' => 50,
            'activo' => true,
        ]);

        $activacion = RecompensaActivada::firstOrCreate([
            'recompensa_id' => $recompensa->recompensa_id,
            'asignacion_id' => $asignacion->asignacion_id,
        ], [
            'costo_puntos' => 100,
            'cupo_maximo' => 10,
            'cupo_disponible' => 9,
            'vigencia_inicio' => '2026-09-01',
            'vigencia_fin' => '2026-12-31',
            'activo' => true,
        ]);

        RecompensaCanje::create([
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->estudiante->usuario_id,
            'puntos_canjeados' => 100,
            'estado' => 'CANJEADA',
            'codigo_canje' => 'CANJE-TEST-041',
            'fecha_solicitud' => now(),
            'fecha_canje' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reportes.exportar-pdf', 'premios-canjeados'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Comprobar contenido en la vista compilada del PDF
        $serviceReporte = app(ReporteInstitucionalService::class);
        $datos = $serviceReporte->reportePremiosCanjeados([]);

        $this->assertNotEmpty($datos['svg_donut_data']);
        $this->assertNotEmpty($datos['svg_barras_data']);
        $this->assertEquals('Boleto Cafetería UPEMOR', $datos['datos'][0]->recompensa_titulo);
        $this->assertEquals(1, $datos['total_general']);

        $viewHtml = view('admin.reportes.pdf.premios-canjeados', ['datosReporte' => $datos])->render();
        $this->assertStringContainsString('DISTRIBUCIÓN VISUAL INSTITUCIONAL DE PREMIOS Y RECOMPENSAS CANJEADOS', $viewHtml);
        $this->assertStringContainsString('Total Canjes', $viewHtml);
        $this->assertStringContainsString('Puntos Redimidos', $viewHtml);
        $this->assertStringContainsString('Boleto Cafetería UPEMOR', $viewHtml);
    }

    /**
     * PRIORIDAD 2: Exportación PDF del reporte de Resultados de Tests Psicométricos incluye gráficas SVG y clasificaciones.
     */
    public function test_reporte_resultados_tests_pdf_incluye_graficas_y_resumen(): void
    {
        $instrumento = InstrumentoEvaluacion::firstOrCreate(['codigo' => 'GAD-7'], [
            'nombre' => 'Escala de Ansiedad GAD-7',
            'siglas' => 'GAD7',
            'categoria' => 'ANSIEDAD',
            'descripcion' => 'Prueba de ansiedad GAD-7.',
            'tipo' => 'PSICOMETRICO',
            'activo' => true,
        ]);

        $version = VersionInstrumento::firstOrCreate(['instrumento_id' => $instrumento->instrumento_id, 'version' => 1], [
            'vigente_desde' => '2026-01-01',
            'instrucciones' => 'Responde con sinceridad las siguientes preguntas.',
            'activo' => true,
        ]);

        EvaluacionIntento::create([
            'version_id' => $version->version_id,
            'usuario_id' => $this->alumno->usuario_id,
            'estudiante_id' => $this->estudiante->usuario_id,
            'fecha_inicio' => now()->subMinutes(15),
            'fecha_finalizacion' => now(),
            'estado' => EvaluacionIntento::ESTADO_FINALIZADO,
            'puntuacion_total' => 14,
            'nivel_resultado' => 'Moderado',
            'tiene_alerta_critica' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reportes.exportar-pdf', 'resultados-tests'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        $serviceReporte = app(ReporteInstitucionalService::class);
        $datos = $serviceReporte->reporteResultadosTests([]);

        $this->assertNotEmpty($datos['svg_barras_data']);
        $this->assertEquals(1, $datos['total_general']);
        $this->assertArrayHasKey('Moderado', $datos['distribucion_niveles']);

        $viewHtml = view('admin.reportes.pdf.resultados-tests', ['datosReporte' => $datos])->render();
        $this->assertStringContainsString('DISTRIBUCIÓN VISUAL DE RESULTADOS Y NIVELES DE RIESGO', $viewHtml);
        $this->assertStringContainsString('Total Evaluaciones', $viewHtml);
        $this->assertStringContainsString('Moderado', $viewHtml);
        $this->assertStringContainsString('Escala de Ansiedad GAD-7', $viewHtml);
    }

    /**
     * PRIORIDAD 2: Exportación XLSX de Premios y Tests genera archivos OpenXML válidos con DrawingML.
     */
    public function test_exportaciones_xlsx_contienen_graficas_drawingml(): void
    {
        $serviceReporte = app(ReporteInstitucionalService::class);
        $excelService = app(ExcelReporteService::class);

        // 1. Premios Canjeados
        $datosPremios = $serviceReporte->reportePremiosCanjeados([]);
        $responsePremios = $excelService->exportarPremiosCanjeados($datosPremios, 'test_premios');
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $responsePremios->headers->get('Content-Type'));
        $this->assertFileExists($responsePremios->getFile()->getPathname());

        // 2. Resultados Tests
        $datosTests = $serviceReporte->reporteResultadosTests([]);
        $responseTests = $excelService->exportarResultadosTests($datosTests, 'test_tests');
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $responseTests->headers->get('Content-Type'));
        $this->assertFileExists($responseTests->getFile()->getPathname());
    }

    /**
     * PRIORIDAD 3: Calendario de Bitácora Emocional con selector de tres vistas (Día | Semana | Mes) y navegación.
     */
    public function test_calendario_bitacora_contiene_selector_de_tres_vistas_y_navegacion(): void
    {
        // Registrar emociones institucionales
        $emociones = [
            ['nombre' => 'Feliz', 'slug' => 'feliz', 'color_hex' => '#eab308', 'icono' => '😊', 'descripcion' => 'Alegría y satisfacción'],
            ['nombre' => 'Motivado', 'slug' => 'motivado', 'color_hex' => '#10b981', 'icono' => '💪', 'descripcion' => 'Energía y propósito'],
            ['nombre' => 'Neutral', 'slug' => 'neutral', 'color_hex' => '#6366f1', 'icono' => '😐', 'descripcion' => 'Tranquilidad cotidiana'],
            ['nombre' => 'Estresado', 'slug' => 'estresado', 'color_hex' => '#f97316', 'icono' => '😫', 'descripcion' => 'Sobrecarga de tareas'],
            ['nombre' => 'Triste', 'slug' => 'triste', 'color_hex' => '#3b82f6', 'icono' => '😢', 'descripcion' => 'Desánimo o nostalgia'],
            ['nombre' => 'Ansioso', 'slug' => 'ansioso', 'color_hex' => '#ec4899', 'icono' => '😰', 'descripcion' => 'Inquietud o preocupación'],
        ];
        foreach ($emociones as $e) {
            Emocion::firstOrCreate(['slug' => $e['slug']], array_merge($e, ['activo' => true]));
        }

        // Crear un registro en la bitácora del alumno
        $emocionFeliz = Emocion::where('slug', 'feliz')->first();
        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $emocionFeliz->emocion_id,
            'fecha' => now()->format('Y-m-d'),
            'intensidad' => 4,
            'descripcion' => 'Aprobé mi examen de Laravel',
            'observaciones' => 'Me sentí muy contento con mi equipo',
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.index'));

        $response->assertStatus(200);

        // Selector de 3 vistas
        $response->assertSee('btnViewMes', false);
        $response->assertSee('btnViewSemana', false);
        $response->assertSee('btnViewDia', false);

        // Contenedores para las 3 vistas
        $response->assertSee('viewContainerMes', false);
        $response->assertSee('viewContainerSemana', false);
        $response->assertSee('viewContainerDia', false);

        // Controles de navegación
        $response->assertSee('btnNavAnterior', false);
        $response->assertSee('btnNavHoy', false);
        $response->assertSee('btnNavSiguiente', false);

        // Confirmar presencia del registro emocional y de la esfera
        $response->assertSee('Feliz');
        $response->assertSee('Aprobé mi examen de Laravel');
    }
}

