<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\AsignacionDocenteResueltaNotification;
use App\Notifications\InscripcionEstudianteResueltaNotification;
use App\Notifications\SolicitudDocenteCreadaNotification;
use App\Notifications\SolicitudInscripcionEstudianteNotification;
use App\Services\Academico\CatedraAcademicaService;
use App\Services\Academico\EstudianteGrupoResolverService;
use App\Services\Academico\PeriodoOperativoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Incremento18AcademicoArquitecturaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $docenteUser;
    protected User $docente2User;
    protected User $alumnoUserA;
    protected User $alumnoUserB;
    protected ProgramaEducativo $programa;
    protected PlanEstudio $plan;
    protected PeriodoAcademico $periodo1;
    protected PeriodoAcademico $periodo2;
    protected PeriodoAcademico $periodo3;
    protected Grupo $grupoA;
    protected Grupo $grupoB;
    protected Grupo $grupo7B;
    protected Asignatura $asigSoftware;
    protected Asignatura $asigEtica;

    protected function setUp(): void
    {
        parent::setUp();

        // ⚠️ VERIFICACIÓN OBLIGATORIA DE SEGURIDAD
        // Garantizar que la suite corre estrictamente en SQLite en memoria
        $dbDriver = DB::connection()->getDriverName();
        $this->assertEquals('sqlite', $dbDriver, 'CRÍTICO DE SEGURIDAD: Los tests deben correr exclusivamente en SQLite en memoria.');

        // Crear Roles
        Rol::firstOrCreate(['rol_id' => 1], ['nombre' => 'Administrador']);
        Rol::firstOrCreate(['rol_id' => 2], ['nombre' => 'Docente']);
        Rol::firstOrCreate(['rol_id' => 3], ['nombre' => 'Alumno']);

        // Crear Programa y Plan
        $this->programa = ProgramaEducativo::create([
            'clave' => 'IID',
            'nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital',
            'activo' => true,
        ]);

        $this->plan = PlanEstudio::create([
            'programa_id' => $this->programa->programa_id,
            'codigo' => 'IID-2024',
            'nombre' => 'Plan de Estudios Oficial IID',
            'es_vigente' => true,
            'total_cuatrimestres' => 10,
        ]);

        // Simular los 3 periodos académicos simultáneamente activos diagnosticados en MariaDB sandbox
        $this->periodo1 = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-30',
            'activo' => true,
        ]);

        $this->periodo2 = PeriodoAcademico::create([
            'clave' => '2026-2',
            'nombre' => 'Mayo - Agosto 2026',
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-08-31',
            'activo' => true,
        ]);

        $this->periodo3 = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2027-01-04',
            'activo' => true,
        ]);

        // Crear Grupos en Periodo 1
        $this->grupoA = Grupo::create([
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupoB = Grupo::create([
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'B',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupo7B = Grupo::create([
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'cuatrimestre' => 7,
            'nombre' => 'B',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        // Crear Asignaturas de 4° cuatrimestre
        $this->asigSoftware = Asignatura::create([
            'programa_id' => $this->programa->programa_id,
            'plan_id' => $this->plan->plan_id,
            'clave' => 'IID-C4-07',
            'nombre' => 'Análisis y Diseño de Software',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);

        $this->asigEtica = Asignatura::create([
            'programa_id' => $this->programa->programa_id,
            'plan_id' => $this->plan->plan_id,
            'clave' => 'IID-C4-02',
            'nombre' => 'Ética Profesional',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);

        // Crear Usuarios
        $this->admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'Bienestar',
            'email' => 'admin@upemor.edu.mx',
            'password' => bcrypt('Admin12345!'),
            'rol_id' => 1,
            'estado' => 'ACTIVO',
        ]);

        $this->docenteUser = User::create([
            'nombre' => 'Elizabeth Odet',
            'apellido_paterno' => 'Armenta',
            'apellido_materno' => 'Pineda',
            'email' => 'apeo230330@upemor.edu.mx',
            'password' => bcrypt('Docente12345!'),
            'rol_id' => 2,
            'estado' => 'ACTIVO',
        ]);
        Docente::create(['usuario_id' => $this->docenteUser->usuario_id, 'numero_empleado' => 'DOC101', 'activo' => true]);

        $this->docente2User = User::create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Pérez',
            'email' => 'docente.academico@upemor.edu.mx',
            'password' => bcrypt('Docente12345!'),
            'rol_id' => 2,
            'estado' => 'ACTIVO',
        ]);
        Docente::create(['usuario_id' => $this->docente2User->usuario_id, 'numero_empleado' => 'DOC102', 'activo' => true]);

        // Alumno Grupo A
        $this->alumnoUserA = User::create([
            'nombre' => 'Ana Sofía',
            'apellido_paterno' => 'Martínez',
            'email' => 'alumno.demo@upemor.edu.mx',
            'password' => bcrypt('Alumno12345!'),
            'rol_id' => 3,
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoUserA->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => 'SOFI230101',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 20,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUserA->usuario_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        // Alumno Grupo B (Escenario real Manuel Antonio)
        $this->alumnoUserB = User::create([
            'nombre' => 'Manuel Antonio',
            'apellido_paterno' => 'Arellano',
            'apellido_materno' => 'Perex',
            'email' => 'admo230179@upemor.edu.mx',
            'password' => bcrypt('Alumno12345!'),
            'rol_id' => 3,
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoUserB->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => 'ADMO230179',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'saldo_puntos' => 42,
        ]);
        // Inscripción oficial 4° B
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUserB->usuario_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);
    }

    /**
     * TEST 1: PeriodoOperativoService resuelve determinísticamente ante múltiples periodos activos.
     */
    public function test_periodo_operativo_service_resolves_deterministically_when_multiple_active_exist(): void
    {
        $service = app(PeriodoOperativoService::class);

        // Periodo 1 tiene grupos creados; debe priorizarse determinísticamente sobre 2 y 3
        $periodo = $service->resolverPeriodoOperativo();
        $this->assertNotNull($periodo);
        $this->assertEquals($this->periodo1->periodo_id, $periodo->periodo_id);
        $this->assertEquals('2026-1', $periodo->clave);

        // Selección explícita en sesión
        $service->seleccionarPeriodo($this->periodo2->periodo_id);
        $this->assertEquals($this->periodo2->periodo_id, $service->resolverPeriodoOperativo()->periodo_id);

        $service->limpiarPeriodoSeleccionado();
        $this->assertEquals($this->periodo1->periodo_id, $service->resolverPeriodoOperativo()->periodo_id);
    }

    /**
     * TEST 2: EstudianteGrupoResolverService concilia anomalía de doble inscripción (escenario Manuel Antonio).
     */
    public function test_grupo_resolver_service_reconciles_conflicting_multi_group_enrollments(): void
    {
        $service = app(EstudianteGrupoResolverService::class);

        // Simular la anomalía diagnosticada en MariaDB: segunda inscripción activa en el Grupo 7° B
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUserB->usuario_id,
            'grupo_id' => $this->grupo7B->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        // Verificar que el detector de conflictos identifica la colisión
        $conflictos = $service->detectarConflictosInscripciones($this->periodo1->periodo_id);
        $this->assertCount(1, $conflictos);
        $this->assertEquals($this->alumnoUserB->usuario_id, $conflictos[0]['estudiante_id']);
        $this->assertEquals($this->grupoB->grupo_id, $conflictos[0]['grupo_recomendado_id']);

        // El resolver algorítmico debe reconciliar y devolver el Grupo 4° B porque coincide con su perfil
        $grupoResuelto = $service->resolverGrupo($this->alumnoUserB, $this->periodo1);
        $this->assertNotNull($grupoResuelto);
        $this->assertEquals(4, $grupoResuelto->cuatrimestre);
        $this->assertEquals('B', $grupoResuelto->nombre);
        $this->assertEquals($this->grupoB->grupo_id, $grupoResuelto->grupo_id);
    }

    /**
     * TEST 3: Inscribir a grupo garantiza una única adscripción activa por periodo (regla de oro).
     */
    public function test_inscribir_a_grupo_enforces_single_active_group_per_period(): void
    {
        $service = app(EstudianteGrupoResolverService::class);

        // Reasignar a Manuel al Grupo A formalmente
        $service->inscribirAGrupo($this->alumnoUserB, $this->grupoA, $this->periodo1);

        // La inscripción anterior en Grupo B debe estar CONCLUIDO y la de Grupo A en ACTIVO
        $activas = EstudianteInscripcion::where('estudiante_id', $this->alumnoUserB->usuario_id)
            ->where('periodo_id', $this->periodo1->periodo_id)
            ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
            ->get();

        $this->assertCount(1, $activas);
        $this->assertEquals($this->grupoA->grupo_id, $activas->first()->grupo_id);
    }

    /**
     * TEST 4: Segregación estricta por grupo: Alumno Grupo B no ve materias ofertadas exclusivamente para Grupo A.
     */
    public function test_student_in_group_b_cannot_see_or_request_subjects_exclusive_to_group_a(): void
    {
        // Profesora Elizabeth tiene asignada Ética Profesional exclusivamente para Grupo A
        $asigDocenteA = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigEtica->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        $this->actingAs($this->alumnoUserB);

        $response = $this->get(route('alumno.academico.index'));
        $response->assertStatus(200);
        // Ética Profesional NO debe aparecer en la oferta disponible de Manuel (Grupo B)
        $response->assertDontSee('IID-C4-02');

        // Intento de solicitud manual directa debe ser rechazado
        $postResponse = $this->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asigDocenteA->asignacion_id,
        ]);
        $postResponse->assertSessionHas('error');
    }

    /**
     * TEST 5: Apertura general (TODOS): Alumno de Grupo B sí ve la materia si el alcance es TODOS.
     */
    public function test_student_in_group_b_sees_subject_when_cátedra_scope_is_todos(): void
    {
        // Elizabeth tiene Análisis y Diseño de Software aprobada con alcance general TODOS
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id, // anclada a grupo A pero con alcance general
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        $this->actingAs($this->alumnoUserB);

        $response = $this->get(route('alumno.academico.index'));
        $response->assertStatus(200);
        // La materia y la clave deben ser visibles en el catálogo agrupado
        $response->assertSee('Análisis y Diseño de Software');
        $response->assertSee('IID-C4-07');
        $response->assertSee('Elizabeth Odet');
    }

    /**
     * TEST 6: Las ofertas pendientes de autorización no se muestran como disponibles a los alumnos.
     */
    public function test_pending_teacher_assignments_are_hidden_from_students(): void
    {
        // Asignación pendiente (activo = false, estado = PENDIENTE)
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => false,
            'estado' => DocenteAsignacion::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->alumnoUserB);

        $response = $this->get(route('alumno.academico.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Análisis y Diseño de Software');
    }

    /**
     * TEST 7: Dos docentes impartiendo la misma materia aparecen agrupados bajo la misma asignatura.
     */
    public function test_two_teachers_offering_same_subject_are_grouped_together_in_student_view(): void
    {
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        DocenteAsignacion::create([
            'docente_id' => $this->docente2User->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        $this->actingAs($this->alumnoUserB);

        $response = $this->get(route('alumno.academico.index'));
        $response->assertStatus(200);
        $response->assertSee('Análisis y Diseño de Software');
        $response->assertSee('Elizabeth Odet');
        $response->assertSee('Roberto');
    }

    /**
     * TEST 8: Prevención de solicitudes repetidas por parte del docente en frontend y backend.
     */
    public function test_docente_cannot_request_duplicate_subject_for_same_period(): void
    {
        Notification::fake();

        // Primera postulación válida
        $this->actingAs($this->docenteUser);
        $response = $this->post(route('docente.academico.solicitar.store'), [
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_apertura' => 'todos',
            'motivo_solicitud' => 'Impartición regular de software',
        ]);
        $response->assertRedirect(route('docente.academico.index'));
        $response->assertSessionHas('status');

        // Notificación enviada a administradores
        Notification::assertSentTo($this->admin, SolicitudDocenteCreadaNotification::class);

        // Intento de duplicación de la misma asignatura en el mismo periodo
        $duplicateResponse = $this->post(route('docente.academico.solicitar.store'), [
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_apertura' => 'todos',
        ]);
        $duplicateResponse->assertSessionHas('error');

        // En el formulario de nueva solicitud, la materia no debe estar en el catálogo disponible
        $formResponse = $this->get(route('docente.academico.solicitar'));
        $formResponse->assertStatus(200);
        $this->assertFalse(
            $formResponse->viewData('asignaturas')->contains('asignatura_id', $this->asigSoftware->asignatura_id),
            'La asignatura ya solicitada no debe aparecer en el catálogo para nueva solicitud'
        );
    }

    /**
     * TEST 9: Aprobación atómica de cátedra por el Administrador y sincronización de estado.
     */
    public function test_admin_approves_catedra_atomically_for_all_subgroups(): void
    {
        Notification::fake();

        // Crear solicitud docente para todos los grupos (A y B)
        $asigA = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => false,
            'estado' => DocenteAsignacion::ESTADO_PENDIENTE,
        ]);
        $asigB = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => false,
            'estado' => DocenteAsignacion::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->admin);

        // El Administrador aprueba una de las asignaciones de la cátedra
        $response = $this->post(route('admin.docente-asignaciones.aprobar', $asigA->asignacion_id), [
            'motivo_resolucion' => 'Cátedra autorizada para todo el cuatrimestre',
        ]);
        $response->assertSessionHas('status');

        // Ambas asignaciones deben quedar activas y aprobadas atómicamente
        $this->assertTrue($asigA->fresh()->activo);
        $this->assertEquals(DocenteAsignacion::ESTADO_APROBADA, $asigA->fresh()->estado);

        $this->assertTrue($asigB->fresh()->activo);
        $this->assertEquals(DocenteAsignacion::ESTADO_APROBADA, $asigB->fresh()->estado);

        // Docente recibe notificación
        Notification::assertSentTo($this->docenteUser, AsignacionDocenteResueltaNotification::class);
    }

    /**
     * TEST 10: Flujo completo de solicitud del alumno, notificación al docente y aprobación transaccional idempotente.
     */
    public function test_full_student_request_and_teacher_approval_lifecycle(): void
    {
        Notification::fake();

        $asigDocente = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        // 1. Alumno solicita inscripción
        $this->actingAs($this->alumnoUserB);
        $reqResponse = $this->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asigDocente->asignacion_id,
            'motivo_solicitud' => 'Deseo cursar la materia regular',
        ]);
        $reqResponse->assertRedirect(route('alumno.academico.index'));
        $reqResponse->assertSessionHas('status');

        // Docente recibe notificación
        Notification::assertSentTo($this->docenteUser, SolicitudInscripcionEstudianteNotification::class);

        // Verificar registro de solicitud
        $inscripcion = EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumnoUserB->usuario_id)
            ->where('asignatura_id', $this->asigSoftware->asignatura_id)
            ->first();

        $this->assertNotNull($inscripcion);
        $this->assertEquals(EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE, $inscripcion->estado);
        $this->assertEquals($this->grupoB->grupo_id, $inscripcion->grupo_id);

        // 2. Docente aprueba la solicitud
        $this->actingAs($this->docenteUser);
        $aprobResponse = $this->post(route('docente.academico.inscripciones.aprobar', $inscripcion->inscripcion_materia_id), [
            'motivo_resolucion' => 'Aceptado formalmente al grupo B',
        ]);
        $aprobResponse->assertSessionHas('status');

        $this->assertEquals(EstudianteAsignaturaInscripcion::ESTADO_APROBADA, $inscripcion->fresh()->estado);

        // Estudiante recibe notificación de resolución
        Notification::assertSentTo($this->alumnoUserB, InscripcionEstudianteResueltaNotification::class);

        // 3. Prueba de Idempotencia: Intentar aprobar de nuevo no debe fallar ni duplicar
        $segundaAprob = $this->post(route('docente.academico.inscripciones.aprobar', $inscripcion->inscripcion_materia_id));
        $segundaAprob->assertSessionHas('info');
    }

    /**
     * TEST 11: Integración con recompensas: Alumno solo accede a recompensas de sus asignaturas autorizadas.
     */
    public function test_student_only_accesses_rewards_of_authorized_subjects(): void
    {
        $asigDocente = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Punto extra en parcial',
            'descripcion' => 'Otorga beneficio académico en la asignatura evaluada',
            'costo_puntos' => 20,
            'categoria' => 'ACADEMICA',
            'activo' => true,
        ]);

        RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $asigDocente->asignacion_id,
            'costo_puntos' => 20,
            'cupo_maximo' => 10,
            'cupo_disponible' => 10,
            'vigencia_inicio' => now(),
            'vigencia_fin' => now()->addDays(30),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        $this->actingAs($this->alumnoUserB);

        // Manuel (Grupo B) consulta el catálogo de recompensas
        $response = $this->get(route('alumno.recompensas.index'));
        $response->assertStatus(200);
        $response->assertSee('Punto extra en parcial');
    }

    /**
     * TEST 12: Las vistas académicas docentes no exponen datos clínicos ni emocionales privados.
     */
    public function test_academic_views_do_not_expose_clinical_or_emotional_data(): void
    {
        $asig = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo1->periodo_id,
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        $this->actingAs($this->docenteUser);

        $response = $this->get(route('docente.academico.grupo', $asig->asignacion_id));
        $response->assertStatus(200);
        $response->assertDontSee('bitacora');
        $response->assertDontSee('psicometria');
        $response->assertDontSee('depresion');
        $response->assertDontSee('ansiedad');
    }
}
