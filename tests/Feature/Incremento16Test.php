<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Notifications\CanjePendienteValidacionNotification;
use App\Notifications\InscripcionEstudianteResueltaNotification;
use App\Notifications\NuevoAlumnoRegistradoNotification;
use App\Services\Respaldos\RespaldoDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Incremento16Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $docente;
    protected User $alumno;
    protected Estudiante $estudiante;
    protected ProgramaEducativo $programaIFI;
    protected ProgramaEducativo $programaIME;
    protected PeriodoAcademico $periodo;
    protected Grupo $grupoA;
    protected Grupo $grupoB;
    protected Asignatura $asignaturaRegular;
    protected DocenteAsignacion $asignacionA;
    protected DocenteAsignacion $asignacionB;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 1, 'nombre' => 'Administrador', 'activo' => true]);
        $rolDocente = Rol::create(['rol_id' => 2, 'nombre' => 'Docente', 'activo' => true]);
        $rolAlumno = Rol::create(['rol_id' => 3, 'nombre' => 'Alumno', 'activo' => true]);

        $this->programaIFI = ProgramaEducativo::create([
            'nombre' => 'Ingeniería en Informática',
            'clave' => 'IFI',
            'siglas' => 'IFI',
            'activo' => true,
        ]);

        $this->programaIME = ProgramaEducativo::create([
            'nombre' => 'Ingeniería Mecatrónica',
            'clave' => 'IME',
            'siglas' => 'IME',
            'activo' => true,
        ]);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-30',
            'activo' => true,
        ]);

        $this->grupoA = Grupo::create([
            'programa_id' => $this->programaIFI->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'A',
            'cuatrimestre' => 7,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupoB = Grupo::create([
            'programa_id' => $this->programaIFI->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'B',
            'cuatrimestre' => 7,
            'turno' => 'VESPERTINO',
            'activo' => true,
        ]);

        // Docente
        $this->docente = User::factory()->create([
            'rol_id' => $rolDocente->rol_id,
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Hernández',
            'apellido_materno' => 'Díaz',
            'estado' => 'ACTIVO',
            'email' => 'roberto.docente@upemor.edu.mx',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'DOC-1601',
            'activo' => true,
        ]);

        // Admin
        $this->admin = User::factory()->create([
            'rol_id' => $rolAdmin->rol_id,
            'nombre' => 'Admin',
            'apellido_paterno' => 'General',
            'estado' => 'ACTIVO',
            'email' => 'admin@upemor.edu.mx',
            'password' => bcrypt('AdminSecret123!'),
        ]);

        // Alumno en IFI Cuatrimestre 7, Grupo A
        $this->alumno = User::factory()->create([
            'rol_id' => $rolAlumno->rol_id,
            'nombre' => 'Valeria',
            'apellido_paterno' => 'Sánchez',
            'apellido_materno' => 'Ruiz',
            'estado' => 'ACTIVO',
            'email' => 'valeria.sanchez@upemor.edu.mx',
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->programaIFI->programa_id,
            'matricula' => '2026107777',
            'cuatrimestre' => 7,
            'grupo' => 'A',
            'saldo_puntos' => 50,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        // Asignatura de 7mo cuatrimestre IFI
        $this->asignaturaRegular = Asignatura::create([
            'programa_id' => $this->programaIFI->programa_id,
            'clave' => 'IFI-701',
            'nombre' => 'Arquitectura de Software Distribuida',
            'cuatrimestre' => 7,
            'activo' => true,
        ]);

        // Asignación Docente a ambos grupos (A y B)
        $this->asignacionA = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->asignaturaRegular->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        $this->asignacionB = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->asignaturaRegular->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);
    }

    public function test_docente_sees_consolidated_subject_card_and_subgroups_accordion(): void
    {
        $response = $this->actingAs($this->docente)->get(route('docente.academico.index'));

        $response->assertOk();
        // Debe mostrar la materia consolidada
        $response->assertSee('Arquitectura de Software Distribuida');
        $response->assertSee('IFI-701');
        // Debe listar ambos subgrupos dentro de la estructura de la materia
        $response->assertSee('Subgrupo 7° "A"', false);
        $response->assertSee('Subgrupo 7° "B"', false);
        $response->assertSee('Subgrupos Asignados (2)');
    }

    public function test_docente_group_detail_displays_subgroup_tabs_with_students_and_enrollment_date(): void
    {
        $response = $this->actingAs($this->docente)->get(
            route('docente.academico.grupo', $this->asignacionA->asignacion_id)
        );

        $response->assertOk();
        // Verificar pestañas de subgrupos
        $response->assertSee('Subgrupo 7° "A"', false);
        $response->assertSee('Subgrupo 7° "B"', false);
        // Verificar estudiante inscrito en Subgrupo A
        $response->assertSee('Valeria Sánchez Ruiz');
        $response->assertSee('2026107777');
        // Verificar columna de Fecha Inscripción
        $response->assertSee('Fecha Inscripción');
    }

    public function test_alumno_only_sees_and_can_request_subjects_for_own_career_and_cuatrimestre(): void
    {
        // Crear materia de 8vo cuatrimestre IFI
        $asigOctavo = Asignatura::create([
            'programa_id' => $this->programaIFI->programa_id,
            'clave' => 'IFI-801',
            'nombre' => 'Ciberseguridad Avanzada',
            'cuatrimestre' => 8,
            'activo' => true,
        ]);
        $asigDocenteOctavo = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $asigOctavo->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        // Crear materia de otra carrera (IME)
        $asigIME = Asignatura::create([
            'programa_id' => $this->programaIME->programa_id,
            'clave' => 'IME-701',
            'nombre' => 'Robótica Industrial',
            'cuatrimestre' => 7,
            'activo' => true,
        ]);
        $asigDocenteIME = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $asigIME->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        // Alumno consulta su oferta
        $response = $this->actingAs($this->alumno)->get(route('alumno.academico.index'));

        $response->assertOk();
        // Debe ver su materia regular de 7mo IFI
        $response->assertSee('Arquitectura de Software Distribuida');
        // NO debe ver la materia de 8vo cuatrimestre
        $response->assertDontSee('Ciberseguridad Avanzada');
        // NO debe ver la materia de Robótica (otra carrera)
        $response->assertDontSee('Robótica Industrial');

        // Intento de forzar solicitud a materia de otro cuatrimestre
        $postOctavo = $this->actingAs($this->alumno)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asigDocenteOctavo->asignacion_id,
        ]);
        $postOctavo->assertSessionHas('error');

        // Intento de forzar solicitud a materia de otra carrera
        $postIME = $this->actingAs($this->alumno)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asigDocenteIME->asignacion_id,
        ]);
        $postIME->assertSessionHas('error');
    }

    public function test_alumno_cannot_request_subject_from_another_group(): void
    {
        // Alumno está en Grupo A, intenta solicitar la materia en Grupo B
        $response = $this->actingAs($this->alumno)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $this->asignacionB->asignacion_id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('estudiante_asignatura_inscripciones', [
            'estudiante_id' => $this->alumno->usuario_id,
            'asignacion_id' => $this->asignacionB->asignacion_id,
        ]);
    }

    public function test_dashboard_layout_contains_15_minute_timeout_and_expired_modal(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.dashboard'));

        $response->assertOk();
        // Modal de 15 minutos de inactividad
        $response->assertSee('Sesión finalizada por inactividad');
        $response->assertSee('modalSesionFinalizadaInactividad');
        $response->assertSee('data-bs-backdrop="static"', false);
        $response->assertSee('Aceptar e iniciar sesión');
        $response->assertSee('15 * 60 * 1000', false);
    }

    public function test_admin_can_create_new_psychometric_test_with_initial_published_version(): void
    {
        // Vista create
        $getCreate = $this->actingAs($this->admin)->get(route('admin.tests.create'));
        $getCreate->assertOk();
        $getCreate->assertSee('Registrar Nuevo Instrumento Psicométrico');
        $getCreate->assertSee('Aviso Institucional de Confidencialidad y Ética Psicométrica');

        // Guardar nuevo test
        $postStore = $this->actingAs($this->admin)->post(route('admin.tests.store'), [
            'codigo' => 'STAI-E',
            'nombre' => 'Cuestionario de Ansiedad Estado-Rasgo',
            'siglas' => 'STAI',
            'categoria' => 'Estrés y Bienestar',
            'descripcion' => 'Instrumento psicométrico para evaluar niveles transitorios y disposicionales de ansiedad en estudiantes.',
            'autor' => 'Charles D. Spielberger',
            'anio_publicacion' => 1983,
            'dias_espera_reaplicacion' => 21,
            'activo' => 1,
            'instrucciones' => 'Responde a cada una de las afirmaciones según cómo te sientes en este preciso momento.',
            'duracion_estimada_min' => 12,
            'puntos_recompensa' => 25,
        ]);

        $testCreado = InstrumentoEvaluacion::where('codigo', 'STAI-E')->first();
        $this->assertNotNull($testCreado);
        $this->assertEquals('Cuestionario de Ansiedad Estado-Rasgo', $testCreado->nombre);
        $this->assertTrue($testCreado->activo);

        // Verificar versión inicial publicada
        $version = $testCreado->versionVigente;
        $this->assertNotNull($version);
        $this->assertEquals('1.0', $version->version);
        $this->assertEquals('PUBLICADA', $version->estado);
        $this->assertTrue($version->es_vigente);
        $this->assertEquals(25, $version->puntos_recompensa);

        $postStore->assertRedirect(route('admin.tests.show', $testCreado->instrumento_id));
    }

    public function test_native_pdo_restore_executes_valid_sql_dump_safely(): void
    {
        $service = new RespaldoDatabaseService();
        $pdo = DB::connection()->getPdo();

        // Ejecutar script SQL de prueba mediante el método PDO nativo
        $sqlPrueba = "-- Comentario institucional\n"
            . "CREATE TABLE IF NOT EXISTS _test_pdo_restore (id INTEGER PRIMARY KEY, valor TEXT);\n"
            . "INSERT INTO _test_pdo_restore (valor) VALUES ('Prueba Incremento 16');\n";

        $service->ejecutarSqlDumpPdo($pdo, $sqlPrueba);

        $resultado = DB::table('_test_pdo_restore')->where('valor', 'Prueba Incremento 16')->first();
        $this->assertNotNull($resultado);

        // Limpiar
        DB::statement('DROP TABLE IF EXISTS _test_pdo_restore');
    }

    public function test_institutional_email_templates_render_upemor_gradient_and_cta_button(): void
    {
        // 1. Notificación de nuevo alumno
        $notifAlumno = new NuevoAlumnoRegistradoNotification(
            alumnoNombre: 'Pedro Ramírez',
            matricula: '2026109999',
            programaNombre: 'Ingeniería en Informática'
        );
        $mailAlumno = $notifAlumno->toMail($this->admin);
        $htmlAlumno = $mailAlumno->render();

        $this->assertStringContainsString('#00281b', (string)$htmlAlumno);
        $this->assertStringContainsString('#004d35', (string)$htmlAlumno);
        $this->assertStringContainsString('#4f46e5', (string)$htmlAlumno);
        $this->assertStringContainsString('Pedro Ramírez', (string)$htmlAlumno);
        $this->assertStringContainsString('Aviso de Confidencialidad y Protección de Datos', (string)$htmlAlumno);

        // 2. Notificación de canje docente
        $notifCanje = new CanjePendienteValidacionNotification(
            alumnoNombre: 'Valeria Sánchez',
            recompensaNombre: 'Punto Extra en Tarea',
            materiaNombre: 'Arquitectura de Software',
            codigoBeneficio: 'CANJE-7788'
        );
        $mailCanje = $notifCanje->toMail($this->docente);
        $htmlCanje = $mailCanje->render();

        $this->assertStringContainsString('CANJE-7788', (string)$htmlCanje);
        $this->assertStringContainsString('#4f46e5', (string)$htmlCanje);

        // 3. Notificación de materia aprobada
        $notifMateria = new InscripcionEstudianteResueltaNotification(
            asignaturaNombre: 'Arquitectura de Software',
            grupoNombre: '7° "A"',
            estado: 'APROBADA',
            motivoResolucion: 'Bienvenido al curso regular'
        );
        $mailMateria = $notifMateria->toMail($this->alumno);
        $htmlMateria = $mailMateria->render();

        $this->assertStringContainsString('APROBADA', (string)$htmlMateria);
        $this->assertStringContainsString('Bienvenido al curso regular', (string)$htmlMateria);
    }
}
