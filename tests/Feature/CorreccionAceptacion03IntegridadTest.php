<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\BitacoraEmocional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\Meditacion;
use App\Models\MeditacionSesion;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\PuntoMovimiento;
use App\Models\Rol;
use App\Models\User;
use App\Services\Academico\CatedraAcademicaService;
use App\Services\Puntos\PuntoService;
use Database\Seeders\EmocionesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorreccionAceptacion03IntegridadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $docente;
    private User $alumno;
    private User $otroAlumno;
    private ProgramaEducativo $programaIID;
    private PlanEstudio $plan;
    private PeriodoAcademico $periodo;
    private Grupo $grupoA;
    private Grupo $grupoB;
    private Asignatura $asignatura;
    private DocenteAsignacion $asignacion;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        // Programa y Plan
        $this->programaIID = ProgramaEducativo::create([
            'clave' => 'IID',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'estado_operativo' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->plan = PlanEstudio::create([
            'programa_id' => $this->programaIID->programa_id,
            'codigo' => 'IID-2026',
            'nombre' => 'Plan Curricular 2026',
            'version' => '1.0',
            'anio_aprobacion' => 2026,
            'vigencia_inicio' => now()->toDateString(),
            'estado' => PlanEstudio::ESTADO_VIGENTE,
            'es_vigente' => true,
            'total_cuatrimestres' => 10,
        ]);

        // Asignatura
        $this->asignatura = Asignatura::create([
            'programa_id' => $this->programaIID->programa_id,
            'plan_id' => $this->plan->plan_id,
            'clave' => 'IID-C4-01',
            'nombre' => 'Ética Profesional',
            'cuatrimestre' => 4,
            'tipo' => 'ORDINARIA',
            'activo' => true,
        ]);

        // Periodo
        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-18',
            'activo' => true,
        ]);

        // Grupos
        $this->grupoA = Grupo::create([
            'programa_id' => $this->programaIID->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupoB = Grupo::create([
            'programa_id' => $this->programaIID->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'B',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        // Usuarios
        $this->admin = User::factory()->withRole($rolAdmin)->create([
            'email' => 'admin.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->docente = User::factory()->withRole($rolDocente)->create([
            'email' => 'docente.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'DOC-TEST-01',
            'activo' => true,
        ]);

        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'alumno.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->programaIID->programa_id,
            'matricula' => '2026104001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 10,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        $this->otroAlumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'otro.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'programa_id' => $this->programaIID->programa_id,
            'matricula' => '2026104002',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'saldo_puntos' => 5,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->otroAlumno->usuario_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        // Asignación docente específica para el Grupo A
        $this->asignacion = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
            'activo' => true,
        ]);

        // Emociones
        $this->seed(EmocionesSeeder::class);
    }

    // =========================================================================
    // MÓDULO E: CICLO DE SOLICITUD, CANCELACIÓN Y RE-SOLICITUD DE ASIGNATURAS
    // =========================================================================

    public function test_alumno_can_request_cancel_and_re_request_subject_without_unique_constraint_violation(): void
    {
        // 1. Solicitud inicial
        $response = $this->actingAs($this->alumno)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $this->asignacion->asignacion_id,
            'motivo_solicitud' => 'Deseo cursar esta materia en mi grupo.',
        ]);

        $response->assertRedirect(route('alumno.academico.index'));
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'estudiante_id' => $this->alumno->usuario_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'estado' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
        ]);

        $inscripcion = EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumno->usuario_id)
            ->where('asignacion_id', $this->asignacion->asignacion_id)
            ->first();

        // 2. Cancelación voluntaria por el estudiante
        $cancelResponse = $this->actingAs($this->alumno)->post(route('alumno.academico.cancelar', $inscripcion->inscripcion_materia_id));
        $cancelResponse->assertRedirect(route('alumno.academico.index'));

        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'inscripcion_materia_id' => $inscripcion->inscripcion_materia_id,
            'estado' => EstudianteAsignaturaInscripcion::ESTADO_CANCELADA,
        ]);

        // 3. Re-solicitud de la misma materia (no debe lanzar duplicate entry 1062)
        $reRequestResponse = $this->actingAs($this->alumno)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $this->asignacion->asignacion_id,
            'motivo_solicitud' => 'Re-solicitud tras cancelación previa.',
        ]);

        $reRequestResponse->assertRedirect(route('alumno.academico.index'));
        $reRequestResponse->assertSessionHas('status');

        // Comprobar que la fila fue reactivada a PENDIENTE conservando la unicidad
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'inscripcion_materia_id' => $inscripcion->inscripcion_materia_id,
            'estado' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
            'motivo_solicitud' => 'Re-solicitud tras cancelación previa.',
        ]);

        // Comprobar que no se crearon registros duplicados
        $totalRegistros = EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumno->usuario_id)
            ->where('asignacion_id', $this->asignacion->asignacion_id)
            ->count();
        $this->assertEquals(1, $totalRegistros);
    }

    public function test_duplicate_pending_request_for_same_subject_is_gracefully_rejected(): void
    {
        // Crear solicitud inicial
        EstudianteAsignaturaInscripcion::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
            'solicitado_en' => now(),
        ]);

        // Intentar solicitar de nuevo
        $response = $this->actingAs($this->alumno)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $this->asignacion->asignacion_id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =========================================================================
    // MÓDULO F: CONSISTENCIA DE CÁTEDRAS, ALCANCE Y VISTA DOCENTE
    // =========================================================================

    public function test_student_from_different_section_cannot_request_specific_course(): void
    {
        // El alumno del grupo B intenta solicitar la materia del grupo A (alcance ESPECIFICO)
        $response = $this->actingAs($this->otroAlumno)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $this->asignacion->asignacion_id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('no se admiten inscripciones cruzadas', mb_strtolower(session('error')));
    }

    public function test_student_can_request_course_with_todos_scope(): void
    {
        // Cambiar alcance a TODOS
        $this->asignacion->update(['modalidad_alcance' => 'TODOS']);

        $response = $this->actingAs($this->otroAlumno)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $this->asignacion->asignacion_id,
            'motivo_solicitud' => 'Solicitud con alcance general',
        ]);

        $response->assertRedirect(route('alumno.academico.index'));
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'estudiante_id' => $this->otroAlumno->usuario_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'estado' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
        ]);
    }

    public function test_docente_group_view_distinguishes_enrolled_from_school_roster(): void
    {
        // El docente consulta la vista del grupo
        $response = $this->actingAs($this->docente)->get(route('docente.academico.grupo', $this->asignacion->asignacion_id));

        $response->assertOk();
        $response->assertSee('Estudiantes Cursantes con Inscripción Aprobada');
        $response->assertSee('Padrón Nominal Oficial de la Sección Escolar');
        $response->assertSee('Sin inscripción');
    }

    // =========================================================================
    // MÓDULO A: MEDITACIONES — RETIRO SEGURO VS ELIMINACIÓN FÍSICA
    // =========================================================================

    public function test_meditacion_without_sessions_can_be_deleted(): void
    {
        $meditacion = Meditacion::create([
            'titulo' => 'Meditación Nueva Sin Sesiones',
            'slug' => 'meditacion-nueva-sin-sesiones',
            'categoria' => 'MINDFULNESS',
            'nivel' => 'PRINCIPIANTE',
            'duracion_minutos' => 5,
            'descripcion' => 'Ejercicio sin uso histórico',
            'es_publicado' => false,
            'instrucciones' => ['tipo_contenido' => 'INTERACTIVO', 'pasos' => []],
            'creado_por' => $this->admin->usuario_id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.meditaciones.destroy', $meditacion));

        $response->assertRedirect(route('admin.meditaciones.index'));
        $this->assertDatabaseMissing('meditaciones', [
            'meditacion_id' => $meditacion->meditacion_id,
        ]);
    }

    public function test_meditacion_with_sessions_cannot_be_deleted_physically_but_can_be_retired(): void
    {
        $meditacion = Meditacion::create([
            'titulo' => 'Meditación Histórica Con Sesiones',
            'slug' => 'meditacion-historica-con-sesiones',
            'categoria' => 'RESPIRACION',
            'nivel' => 'INTERMEDIO',
            'duracion_minutos' => 10,
            'descripcion' => 'Ejercicio completado por alumnos',
            'es_publicado' => true,
            'instrucciones' => ['tipo_contenido' => 'INTERACTIVO', 'pasos' => []],
            'creado_por' => $this->admin->usuario_id,
        ]);

        MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $this->alumno->usuario_id,
            'fecha_sesion' => now(),
            'completada' => true,
            'duracion_segundos' => 600,
        ]);

        // Intento de borrado físico debe ser rechazado
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.meditaciones.destroy', $meditacion));
        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('meditaciones', [
            'meditacion_id' => $meditacion->meditacion_id,
        ]);

        // Retiro seguro del catálogo
        $retirarResponse = $this->actingAs($this->admin)->patch(route('admin.meditaciones.retirar', $meditacion));
        $retirarResponse->assertSessionHas('status');

        $this->assertFalse($meditacion->fresh()->es_publicado);
        $this->assertDatabaseHas('meditaciones_sesiones', [
            'meditacion_id' => $meditacion->meditacion_id,
        ]);
    }

    // =========================================================================
    // MÓDULO C: PROGRAMAS EDUCATIVOS Y PLANES DE ESTUDIO
    // =========================================================================

    public function test_newly_created_career_with_empty_initial_plan_can_be_deleted(): void
    {
        // Crear un programa educativo nuevo
        $nuevaCarrera = ProgramaEducativo::create([
            'clave' => 'IET',
            'nombre' => 'Ingeniería en Electrónica y Telecomunicaciones',
            'estado_operativo' => 'ACTIVO',
            'activo' => true,
        ]);

        // Crear su plan inicial vacío
        PlanEstudio::create([
            'programa_id' => $nuevaCarrera->programa_id,
            'codigo' => 'IET-2026',
            'nombre' => 'Plan Inicial IET',
            'version' => '1.0',
            'anio_aprobacion' => 2026,
            'estado' => PlanEstudio::ESTADO_BORRADOR,
            'es_vigente' => false,
            'total_cuatrimestres' => 10,
        ]);

        // Comprobar que no tiene dependencias activas porque su plan no tiene asignaturas ni alumnos
        $this->assertFalse($nuevaCarrera->tieneDependenciasActivas());

        $response = $this->actingAs($this->admin)->delete(route('admin.carreras.destroy', $nuevaCarrera->programa_id));

        $response->assertRedirect(route('admin.carreras.index'));
        $this->assertDatabaseMissing('programas_educativos', [
            'programa_id' => $nuevaCarrera->programa_id,
        ]);
    }

    public function test_plan_with_historical_assignments_cannot_be_deleted(): void
    {
        // El plan actual tiene una asignatura asignada a un docente
        $response = $this->actingAs($this->admin)->delete(route('admin.planes.destroy', $this->plan->plan_id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('planes_estudio', [
            'plan_id' => $this->plan->plan_id,
        ]);
    }

    public function test_draft_plan_without_historical_dependencies_can_be_deleted(): void
    {
        $planBorrador = PlanEstudio::create([
            'programa_id' => $this->programaIID->programa_id,
            'codigo' => 'IID-2030-BORRADOR',
            'nombre' => 'Plan Borrador Futuro',
            'version' => '0.1',
            'anio_aprobacion' => 2030,
            'estado' => PlanEstudio::ESTADO_BORRADOR,
            'es_vigente' => false,
            'total_cuatrimestres' => 10,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.planes.destroy', $planBorrador->plan_id));

        $response->assertRedirect(route('admin.planes.index', $this->programaIID->programa_id));
        $this->assertDatabaseMissing('planes_estudio', [
            'plan_id' => $planBorrador->plan_id,
        ]);
    }

    // =========================================================================
    // MÓDULO D: ELIMINACIÓN DE REGISTROS EN BITÁCORA Y REVERSIÓN DE PUNTOS
    // =========================================================================

    public function test_student_can_delete_emotional_log_and_points_are_reverted(): void
    {
        $emocion = Emocion::where('slug', 'alegria')->first();

        $bitacora = BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 4,
            'descripcion' => 'Día muy productivo',
        ]);

        // Otorgar 1 punto de bienestar
        app(PuntoService::class)->otorgarPorEmocionDiaria($this->alumno, $bitacora);
        $this->assertEquals(11, $this->alumno->estudiante->fresh()->saldo_puntos);

        // Eliminar registro
        $response = $this->actingAs($this->alumno)->delete(route('alumno.bitacora.destroy', $bitacora->bitacora_id));

        $response->assertRedirect(route('alumno.bitacora.index'));
        $this->assertDatabaseMissing('bitacoras_emocionales', [
            'bitacora_id' => $bitacora->bitacora_id,
        ]);

        // Saldo decrementado a 10
        $this->assertEquals(10, $this->alumno->estudiante->fresh()->saldo_puntos);

        // Movimiento de ajuste contable registrado
        $this->assertDatabaseHas('estudiante_puntos_movimientos', [
            'estudiante_id' => $this->alumno->usuario_id,
            'tipo' => PuntoMovimiento::TIPO_AJUSTE_ADMINISTRATIVO,
            'puntos' => -1,
        ]);
    }
}
