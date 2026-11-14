<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PeriodoParcial;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Services\Reportes\ReporteAcademicoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurricularYRecompensasTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Rol $rolAlumno;

    private User $adminUser;
    private User $docenteUser;
    private User $alumnoUser;

    private ProgramaEducativo $programa;
    private PlanEstudio $plan;
    private PeriodoAcademico $periodo;
    private Grupo $grupo;
    private Asignatura $asignatura;
    private DocenteAsignacion $asignacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $this->rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $this->rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $this->adminUser = User::factory()->withRole($this->rolAdmin)->create(['estado' => 'ACTIVO']);
        $this->docenteUser = User::factory()->withRole($this->rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $this->docenteUser->usuario_id,
            'numero_empleado' => 'DOC-ADM-01',
            'activo' => true,
        ]);

        $this->alumnoUser = User::factory()->withRole($this->rolAlumno)->create(['estado' => 'ACTIVO']);

        $this->programa = ProgramaEducativo::create([
            'clave' => 'IID',
            'nombre' => 'Ingeniería en Informática y Desarrollo Digital',
            'activo' => true,
            'archivado' => false,
        ]);

        $this->plan = PlanEstudio::create([
            'programa_id' => $this->programa->programa_id,
            'codigo' => 'IID-2026',
            'nombre' => 'Plan Oficial IID 2026',
            'version' => '1.0',
            'anio_aprobacion' => 2026,
            'total_cuatrimestres' => 10,
            'estado' => PlanEstudio::ESTADO_VIGENTE,
            'es_vigente' => true,
        ]);

        $this->asignatura = Asignatura::create([
            'programa_id' => $this->programa->programa_id,
            'plan_id' => $this->plan->plan_id,
            'clave' => 'IID-C1-01',
            'nombre' => 'Fundamentos de Algoritmos',
            'tipo' => Asignatura::TIPO_ORDINARIA,
            'cuatrimestre' => 1,
            'horas_semana' => 5,
            'creditos' => 5,
            'orden' => 1,
            'activo' => true,
        ]);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-20',
            'activo' => true,
        ]);

        PeriodoParcial::create([
            'periodo_id' => $this->periodo->periodo_id,
            'numero_parcial' => 1,
            'nombre' => 'Primer Parcial',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-02-10',
            'activo' => true,
        ]);

        $this->grupo = Grupo::create([
            'periodo_id' => $this->periodo->periodo_id,
            'programa_id' => $this->programa->programa_id,
            'clave' => 'IID-1A',
            'nombre' => 'Grupo 1A Informática',
            'cuatrimestre' => 1,
            'turno' => 'MATUTINO',
            'capacidad' => 30,
            'activo' => true,
        ]);

        $this->asignacion = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
        ]);

        Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => '202610099',
            'cuatrimestre' => 1,
            'saldo_puntos' => 50,
        ]);

        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);
    }

    /* -------------------------------------------------------------
     * PRUEBAS DE GESTIÓN CURRICULAR (CARRERAS, PLANES Y MATERIAS)
     * ------------------------------------------------------------- */

    public function test_admin_can_access_carreras_and_planes(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.carreras.index'));
        $response->assertOk();
        $response->assertSee('Ingeniería en Informática y Desarrollo Digital');

        $responsePlanes = $this->actingAs($this->adminUser)->get(route('admin.planes.index', $this->programa->programa_id));
        $responsePlanes->assertOk();
        $responsePlanes->assertSee('IID-2026');

        $responseShow = $this->actingAs($this->adminUser)->get(route('admin.planes.show', $this->plan->plan_id));
        $responseShow->assertOk();
        $responseShow->assertSee('Fundamentos de Algoritmos');
    }

    public function test_admin_can_create_new_carrera_and_generates_initial_plan(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.carreras.store'), [
            'clave' => 'IBT',
            'nombre' => 'Ingeniería en Biotecnología',
            'clave_historica' => 'IB',
            'descripcion' => 'Programa de biotecnología aplicada.',
            'activo' => 1,
        ]);

        $carreraCreada = ProgramaEducativo::where('clave', 'IBT')->first();
        $this->assertNotNull($carreraCreada);
        $response->assertRedirect(route('admin.planes.index', $carreraCreada->programa_id));

        // Debe haber generado automáticamente el plan borrador inicial
        $this->assertDatabaseHas('planes_estudio', [
            'programa_id' => $carreraCreada->programa_id,
            'codigo' => 'IBT-' . date('Y'),
        ]);
    }

    public function test_admin_cannot_delete_carrera_with_active_students(): void
    {
        $response = $this->actingAs($this->adminUser)->delete(
            route('admin.carreras.destroy', $this->programa->programa_id)
        );

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('programas_educativos', [
            'programa_id' => $this->programa->programa_id,
        ]);
    }

    public function test_admin_can_add_subject_to_plan(): void
    {
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.planes.materias.store', $this->plan->plan_id),
            [
                'cuatrimestre' => 1,
                'nombre' => 'Cálculo Diferencial',
                'tipo' => 'ORDINARIA',
                'horas_semana' => 5,
                'creditos' => 5,
            ]
        );

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('asignaturas', [
            'plan_id' => $this->plan->plan_id,
            'nombre' => 'Cálculo Diferencial',
            'cuatrimestre' => 1,
        ]);
    }

    public function test_admin_can_toggle_plan_vigencia(): void
    {
        $plan2 = PlanEstudio::create([
            'programa_id' => $this->programa->programa_id,
            'codigo' => 'IID-2027',
            'nombre' => 'Plan Nuevo IID',
            'version' => '2.0',
            'anio_aprobacion' => 2027,
            'total_cuatrimestres' => 10,
            'estado' => PlanEstudio::ESTADO_BORRADOR,
            'es_vigente' => false,
        ]);

        // Cambiar plan2 a VIGENTE
        $response = $this->actingAs($this->adminUser)->patch(
            route('admin.planes.estado', $plan2->plan_id),
            ['estado' => PlanEstudio::ESTADO_VIGENTE]
        );

        $response->assertSessionHas('success');
        $this->assertTrue($plan2->fresh()->es_vigente);
        $this->assertEquals(PlanEstudio::ESTADO_VIGENTE, $plan2->fresh()->estado);

        // El plan previo debe haber cambiado a HISTORICO y es_vigente = false
        $this->assertFalse($this->plan->fresh()->es_vigente);
        $this->assertEquals(PlanEstudio::ESTADO_HISTORICO, $this->plan->fresh()->estado);
    }

    /* -------------------------------------------------------------
     * PRUEBAS DE PERIODOS ACADÉMICOS Y PROMOCIONES
     * ------------------------------------------------------------- */

    public function test_admin_creating_period_auto_creates_three_partials(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.periodos.store'), [
            'clave' => '2026-2',
            'nombre' => 'Mayo - Agosto 2026',
            'fecha_inicio' => '2026-05-04',
            'fecha_fin' => '2026-08-21',
            'activo' => 1,
        ]);

        $response->assertRedirect(route('admin.periodos.index'));

        $periodoNuevo = PeriodoAcademico::where('clave', '2026-2')->first();
        $this->assertNotNull($periodoNuevo);
        $this->assertEquals(3, $periodoNuevo->parciales()->count());
    }

    public function test_admin_can_promote_students_to_next_cuatrimestre(): void
    {
        $periodoDestino = PeriodoAcademico::create([
            'clave' => '2026-2',
            'nombre' => 'Mayo - Agosto 2026',
            'fecha_inicio' => '2026-05-04',
            'fecha_fin' => '2026-08-21',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.periodos.promociones.ejecutar', $this->periodo->periodo_id),
            [
                'periodo_destino_id' => $periodoDestino->periodo_id,
                'estudiantes_ids' => [$this->alumnoUser->usuario_id],
            ]
        );

        $response->assertSessionHas('success');

        // Estudiante pasa de 1° a 2° cuatrimestre
        $estudiante = Estudiante::where('usuario_id', $this->alumnoUser->usuario_id)->first();
        $this->assertEquals(2, $estudiante->cuatrimestre);

        // Inscripción anterior marcada como CONCLUIDA
        $inscripcion = EstudianteInscripcion::where('estudiante_id', $this->alumnoUser->usuario_id)
            ->where('periodo_id', $this->periodo->periodo_id)
            ->first();
        $this->assertEquals(EstudianteInscripcion::ESTADO_CONCLUIDO, $inscripcion->estado);
    }

    /* -------------------------------------------------------------
     * PRUEBAS DE RECOMPENSAS DIGITALES (CUPONES Y MODERACIÓN)
     * ------------------------------------------------------------- */

    public function test_teacher_can_mark_digital_coupon_as_utilized(): void
    {
        $recCat = RecompensaCatalogo::create([
            'nombre' => 'Asesoría 1 a 1',
            'descripcion' => 'Revisión técnica de código.',
            'categoria' => RecompensaCatalogo::CATEGORIA_ACADEMICA,
            'costo_puntos_base' => 20,
            'activo' => true,
        ]);

        $activacion = RecompensaActivada::create([
            'recompensa_id' => $recCat->recompensa_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'costo_puntos' => 20,
            'cupo_maximo' => 5,
            'cupo_disponible' => 5,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        // Alumno canjea el beneficio
        $this->actingAs($this->alumnoUser)->post(
            route('alumno.recompensas.canjear', $activacion->activacion_id)
        );

        $canje = RecompensaCanje::where('estudiante_id', $this->alumnoUser->usuario_id)->first();
        $this->assertEquals(RecompensaCanje::ESTADO_CANJEADA, $canje->estado);
        $this->assertNotNull($canje->codigo_beneficio);

        // Docente valida y marca como UTILIZADO en clase
        $responseDocente = $this->actingAs($this->docenteUser)->patch(
            route('docente.recompensas.utilizar', $canje->canje_id)
        );

        $responseDocente->assertSessionHas('success');
        $this->assertEquals(RecompensaCanje::ESTADO_UTILIZADA, $canje->fresh()->estado);
        $this->assertNotNull($canje->fresh()->fecha_utilizacion);
        $this->assertEquals($this->docenteUser->usuario_id, $canje->fresh()->utilizado_por);
    }

    public function test_teacher_can_propose_custom_reward_and_admin_approves(): void
    {
        // 1. Docente envía propuesta
        $responsePropuesta = $this->actingAs($this->docenteUser)->post(
            route('docente.recompensas.proponer'),
            [
                'nombre' => 'Acceso a Laboratorio Especializado',
                'descripcion' => 'Uso de equipo especializado para prácticas de IoT.',
                'categoria' => 'ACADEMICA',
                'costo_puntos_base' => 75,
                'justificacion_docente' => 'Incentiva el desarrollo práctico de prototipos.',
            ]
        );

        $responsePropuesta->assertSessionHas('success');

        $propuesta = RecompensaCatalogo::where('nombre', 'Acceso a Laboratorio Especializado')->first();
        $this->assertNotNull($propuesta);
        $this->assertEquals(RecompensaCatalogo::PROPUESTA_PENDIENTE, $propuesta->estado_propuesta);
        $this->assertEquals($this->docenteUser->usuario_id, $propuesta->docente_solicitante_id);

        // 2. Administrador aprueba la propuesta
        $responseAdmin = $this->actingAs($this->adminUser)->patch(
            route('admin.recompensas.aprobar', $propuesta->recompensa_id)
        );

        $responseAdmin->assertSessionHas('success');
        $this->assertEquals(RecompensaCatalogo::PROPUESTA_APROBADA, $propuesta->fresh()->estado_propuesta);
    }

    /* -------------------------------------------------------------
     * PRUEBAS DEL SERVICIO DE REPORTERÍA ACADÉMICA
     * ------------------------------------------------------------- */

    public function test_academic_reporting_service_aggregates_without_exposing_clinical_data(): void
    {
        $service = app(ReporteAcademicoService::class);

        $resumen = $service->resumenInstitucional();
        $this->assertArrayHasKey('total_programas', $resumen);
        $this->assertArrayHasKey('total_planes_vigentes', $resumen);
        $this->assertArrayHasKey('total_estudiantes_activos', $resumen);

        $porPrograma = $service->estadisticasPorPrograma();
        $this->assertNotEmpty($porPrograma);
        $this->assertEquals('IID', $porPrograma->first()['clave']);

        $metricasRecompensas = $service->metricasRecompensas();
        $this->assertArrayHasKey('total_canjes_emitidos', $metricasRecompensas);
        $this->assertArrayHasKey('tasa_utilizacion_pct', $metricasRecompensas);

        $distribucion = $service->distribucionPorCuatrimestre();
        $this->assertCount(10, $distribucion);
        $this->assertEquals(1, $distribucion[1]); // Nuestro alumnoUser está en 1°
    }
}
