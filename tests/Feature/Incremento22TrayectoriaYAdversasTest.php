<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\EstudiantePlanHistorial;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Services\Reportes\ExcelReporteService;
use App\Services\Reportes\ReporteInstitucionalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Incremento22TrayectoriaYAdversasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $alumnoUser;
    private Estudiante $estudiante;
    private ProgramaEducativo $programa1;
    private ProgramaEducativo $programa2;
    private PlanEstudio $plan2022;
    private PlanEstudio $plan2026;
    private PlanEstudio $planOtraCarrera;
    private PeriodoAcademico $periodo;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['nombre' => 'Administrador', 'descripcion' => 'Admin']);
        $rolDocente = Rol::create(['nombre' => 'Docente', 'descripcion' => 'Docente']);
        $rolAlumno = Rol::create(['nombre' => 'Alumno', 'descripcion' => 'Alumno']);

        $this->admin = User::create([
            'rol_id' => $rolAdmin->rol_id,
            'nombre' => 'Super',
            'apellido_paterno' => 'Admin',
            'email' => 'admin.test@upemor.edu.mx',
            'password' => 'Secret123!',
            'estado' => 'ACTIVO',
        ]);

        $this->programa1 = ProgramaEducativo::create([
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'clave' => 'ITI',
            'activo' => true,
        ]);

        $this->programa2 = ProgramaEducativo::create([
            'nombre' => 'Ingeniería en Electrónica y Telecomunicaciones',
            'clave' => 'IET',
            'activo' => true,
        ]);

        $this->periodo = PeriodoAcademico::create([
            'nombre' => 'Septiembre - Diciembre 2026',
            'clave' => '2026-3',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-20',
            'activo' => true,
        ]);

        $this->grupo = Grupo::create([
            'programa_id' => $this->programa1->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 3,
            'nombre' => 'A',
            'codigo' => 'ITI-3A',
            'turno' => 'MATUTINO',
        ]);

        $this->plan2022 = PlanEstudio::create([
            'programa_id' => $this->programa1->programa_id,
            'codigo' => 'ITI-2022',
            'nombre' => 'Plan de Estudios ITI 2022',
            'version' => '2022.1',
            'anio_aprobacion' => 2022,
            'vigencia_inicio' => '2022-01-01',
            'vigencia_fin' => '2025-12-31',
            'estado' => PlanEstudio::ESTADO_HISTORICO,
            'es_vigente' => false,
        ]);

        $this->plan2026 = PlanEstudio::create([
            'programa_id' => $this->programa1->programa_id,
            'codigo' => 'ITI-2026',
            'nombre' => 'Plan de Estudios ITI 2026 Oficial',
            'version' => '2026.1',
            'anio_aprobacion' => 2026,
            'vigencia_inicio' => '2026-01-01',
            'vigencia_fin' => null,
            'estado' => PlanEstudio::ESTADO_VIGENTE,
            'es_vigente' => true,
        ]);

        $this->planOtraCarrera = PlanEstudio::create([
            'programa_id' => $this->programa2->programa_id,
            'codigo' => 'IET-2026',
            'nombre' => 'Plan Oficial IET 2026',
            'version' => '2026.1',
            'anio_aprobacion' => 2026,
            'vigencia_inicio' => '2026-01-01',
            'vigencia_fin' => null,
            'estado' => PlanEstudio::ESTADO_VIGENTE,
            'es_vigente' => true,
        ]);

        $this->alumnoUser = User::create([
            'rol_id' => $rolAlumno->rol_id,
            'nombre' => 'Estudiante',
            'apellido_paterno' => 'Prueba',
            'email' => 'estudiante.test@upemor.edu.mx',
            'password' => 'Secret123!',
            'estado' => 'ACTIVO',
        ]);

        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'programa_id' => $this->programa1->programa_id,
            'plan_id' => $this->plan2022->plan_id,
            'matricula' => 'ITI202401',
            'cuatrimestre' => 3,
            'grupo' => 'A',
            'saldo_puntos' => 300,
            'permite_visualizacion' => true,
        ]);
    }

    public function test_admin_puede_ver_ficha_de_trayectoria_academica(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.estudiantes.trayectoria', $this->alumnoUser->usuario_id));

        $response->assertStatus(200);
        $response->assertSee('Trayectoria Académica');
        $response->assertSee('ITI202401');
        $response->assertSee('ITI-2022');
        $response->assertSee('Cambiar plan de estudios');
    }

    public function test_usuario_no_admin_es_rechazado_al_intentar_ver_trayectoria(): void
    {
        $response = $this->actingAs($this->alumnoUser)->get(route('admin.estudiantes.trayectoria', $this->alumnoUser->usuario_id));

        $response->assertStatus(403);
    }

    public function test_admin_cambia_plan_de_estudios_compatible_con_justificacion_y_registra_auditoria(): void
    {
        $this->assertEquals($this->plan2022->plan_id, $this->estudiante->fresh()->plan_id);

        $response = $this->actingAs($this->admin)->post(route('admin.estudiantes.cambiar-plan', $this->alumnoUser->usuario_id), [
            'plan_id' => $this->plan2026->plan_id,
            'motivo' => 'Actualización por dictamen de homologación curricular 2026 aprobado por el consejo técnico.',
            'confirmacion' => '1',
        ]);

        $response->assertRedirect(route('admin.estudiantes.trayectoria', $this->alumnoUser->usuario_id));
        $response->assertSessionHas('success');

        // Verificar cambio persistido
        $this->assertEquals($this->plan2026->plan_id, $this->estudiante->fresh()->plan_id);

        // Verificar registro de auditoría en base de datos
        $this->assertDatabaseHas('estudiante_plan_historial', [
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'plan_anterior_id' => $this->plan2022->plan_id,
            'plan_nuevo_id' => $this->plan2026->plan_id,
            'admin_usuario_id' => $this->admin->usuario_id,
            'resultado' => 'EXITOSO',
        ]);
    }

    public function test_cambio_de_plan_preserva_integramente_materias_e_inscripciones_anteriores(): void
    {
        // Crear asignaturas e inscripciones docentes previas
        $docenteUser = User::create([
            'rol_id' => Rol::where('nombre', 'Docente')->first()->rol_id,
            'nombre' => 'Profesor',
            'apellido_paterno' => 'Titular',
            'email' => 'prof.titular@upemor.edu.mx',
            'password' => 'Doc123!',
            'estado' => 'ACTIVO',
        ]);
        $docente = Docente::create([
            'usuario_id' => $docenteUser->usuario_id,
            'numero_empleado' => 'EMP-2201',
        ]);

        $asignatura = Asignatura::create([
            'programa_id' => $this->programa1->programa_id,
            'plan_id' => $this->plan2022->plan_id,
            'nombre' => 'Estructura de Datos',
            'clave' => 'ED-101',
            'cuatrimestre' => 3,
            'activo' => true,
        ]);

        $asignacion = DocenteAsignacion::create([
            'docente_id' => $docente->usuario_id,
            'asignatura_id' => $asignatura->asignatura_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
        ]);

        // Inscripción histórica de materia
        EstudianteAsignaturaInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'asignacion_id' => $asignacion->asignacion_id,
            'grupo_id' => $this->grupo->grupo_id,
            'asignatura_id' => $asignatura->asignatura_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'APROBADA',
        ]);

        // Inscripción histórica de periodo
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'ACTIVO',
        ]);

        $conteoMateriasAntes = EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumnoUser->usuario_id)->count();
        $conteoInscripcionesAntes = EstudianteInscripcion::where('estudiante_id', $this->alumnoUser->usuario_id)->count();

        // Ejecutar cambio de plan
        $this->actingAs($this->admin)->post(route('admin.estudiantes.cambiar-plan', $this->alumnoUser->usuario_id), [
            'plan_id' => $this->plan2026->plan_id,
            'motivo' => 'Cambio curricular autorizado respetando materias previas cursadas.',
            'confirmacion' => '1',
        ]);

        // Comprobar que NINGUNA materia ni inscripción previa fue eliminada
        $this->assertEquals($conteoMateriasAntes, EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumnoUser->usuario_id)->count());
        $this->assertEquals($conteoInscripcionesAntes, EstudianteInscripcion::where('estudiante_id', $this->alumnoUser->usuario_id)->count());
    }

    public function test_admin_no_puede_cambiar_a_plan_de_otra_carrera(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.estudiantes.cambiar-plan', $this->alumnoUser->usuario_id), [
            'plan_id' => $this->planOtraCarrera->plan_id,
            'motivo' => 'Intento no autorizado de cambiar a una carrera diferente sin proceso de convalidación.',
            'confirmacion' => '1',
        ]);

        $response->assertSessionHas('error');
        // El plan del alumno debe permanecer inalterado
        $this->assertEquals($this->plan2022->plan_id, $this->estudiante->fresh()->plan_id);
    }

    public function test_admin_no_puede_cambiar_plan_sin_motivo_o_con_motivo_muy_corto(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.estudiantes.cambiar-plan', $this->alumnoUser->usuario_id), [
            'plan_id' => $this->plan2026->plan_id,
            'motivo' => 'Corto',
            'confirmacion' => '1',
        ]);

        $response->assertSessionHasErrors(['motivo']);
        $this->assertEquals($this->plan2022->plan_id, $this->estudiante->fresh()->plan_id);
    }

    public function test_admin_no_puede_cambiar_a_mismo_plan_actual(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.estudiantes.cambiar-plan', $this->alumnoUser->usuario_id), [
            'plan_id' => $this->plan2022->plan_id,
            'motivo' => 'Intento de seleccionar el mismo plan que ya tiene asignado.',
            'confirmacion' => '1',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_exportacion_xlsx_genera_dos_graficas_incluso_con_cero_registros(): void
    {
        $excelService = app(ExcelReporteService::class);

        // Exportar con dataset intencionalmente vacío
        $datosVacios = [
            'datos' => [],
            'total_general' => 0,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];

        $response = $excelService->exportarEmocionesRegistradas($datosVacios, 'test_vacio.xlsx');
        $this->assertEquals(200, $response->getStatusCode());

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_vacio_');
        file_put_contents($tmpFile, file_get_contents($response->getFile()->getPathname()));

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmpFile));
        $this->assertNotFalse($zip->locateName('xl/drawings/drawing1.xml'));
        $this->assertNotFalse($zip->locateName('xl/charts/chart1.xml'));
        $this->assertNotFalse($zip->locateName('xl/charts/chart2.xml'));

        $drawingXml = $zip->getFromName('xl/drawings/drawing1.xml');
        $this->assertEquals(2, substr_count($drawingXml, '<xdr:twoCellAnchor'));

        $zip->close();
        unlink($tmpFile);
    }
}
