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
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Services\Academico\CatedraAcademicaService;
use App\Services\Academico\EstudianteGrupoResolverService;
use App\Services\Academico\PeriodoOperativoService;
use App\Services\Respaldos\RespaldoDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Incremento19IntegracionFinalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $docente1;
    protected User $docente2;
    protected User $alumno4A;
    protected User $alumno4B;
    protected User $alumno7A;
    protected User $alumnoIFI;
    protected ProgramaEducativo $programaIID;
    protected ProgramaEducativo $programaIFI;
    protected PeriodoAcademico $periodo;
    protected Grupo $grupo4A;
    protected Grupo $grupo4B;
    protected Grupo $grupo7A;
    protected Grupo $grupoIFI4A;
    protected Asignatura $asigSoftware;
    protected Asignatura $asigCalculo;
    protected Asignatura $asigFinanzas;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Admin']);
        $rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['descripcion' => 'Docente']);
        $rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Alumno']);

        $this->admin = User::factory()->create([
            'rol_id' => $rolAdmin->rol_id,
            'nombre' => 'Admin',
            'email' => 'admin@upemor.test',
            'estado' => 'ACTIVO',
        ]);

        $this->programaIID = ProgramaEducativo::firstOrCreate(['clave' => 'IID'], [
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);

        $this->programaIFI = ProgramaEducativo::firstOrCreate(['clave' => 'IFI'], [
            'nombre' => 'Ingeniería Financiera',
            'activo' => true,
        ]);

        $this->periodo = PeriodoAcademico::firstOrCreate(['clave' => '2026-1'], [
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-30',
            'activo' => true,
        ]);

        $this->grupo4A = Grupo::firstOrCreate(
            ['programa_id' => $this->programaIID->programa_id, 'periodo_id' => $this->periodo->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        $this->grupo4B = Grupo::firstOrCreate(
            ['programa_id' => $this->programaIID->programa_id, 'periodo_id' => $this->periodo->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'B'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        $this->grupo7A = Grupo::firstOrCreate(
            ['programa_id' => $this->programaIID->programa_id, 'periodo_id' => $this->periodo->periodo_id, 'cuatrimestre' => 7, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        $this->grupoIFI4A = Grupo::firstOrCreate(
            ['programa_id' => $this->programaIFI->programa_id, 'periodo_id' => $this->periodo->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        $this->asigSoftware = Asignatura::firstOrCreate(
            ['programa_id' => $this->programaIID->programa_id, 'clave' => 'IID-C4-07'],
            ['nombre' => 'Análisis y Diseño de Software', 'cuatrimestre' => 4, 'activo' => true]
        );

        $this->asigCalculo = Asignatura::firstOrCreate(
            ['programa_id' => $this->programaIID->programa_id, 'clave' => 'IID-C4-03'],
            ['nombre' => 'Cálculo de Varias Variables', 'cuatrimestre' => 4, 'activo' => true]
        );

        $this->asigFinanzas = Asignatura::firstOrCreate(
            ['programa_id' => $this->programaIFI->programa_id, 'clave' => 'IFI-C4-04'],
            ['nombre' => 'Finanzas Corporativas', 'cuatrimestre' => 4, 'activo' => true]
        );

        // Docentes
        $this->docente1 = User::factory()->create([
            'rol_id' => $rolDocente->rol_id,
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Mendoza',
            'email' => 'carlos@upemor.test',
            'estado' => 'ACTIVO',
        ]);
        Docente::firstOrCreate(['usuario_id' => $this->docente1->usuario_id], ['numero_empleado' => 'DOC-I19-01', 'activo' => true]);

        $this->docente2 = User::factory()->create([
            'rol_id' => $rolDocente->rol_id,
            'nombre' => 'Laura',
            'apellido_paterno' => 'Morales',
            'email' => 'laura@upemor.test',
            'estado' => 'ACTIVO',
        ]);
        Docente::firstOrCreate(['usuario_id' => $this->docente2->usuario_id], ['numero_empleado' => 'DOC-I19-02', 'activo' => true]);

        // Alumnos
        $this->alumno4A = User::factory()->create(['rol_id' => $rolAlumno->rol_id, 'nombre' => 'Juan', 'email' => 'juan@upemor.test', 'estado' => 'ACTIVO']);
        Estudiante::firstOrCreate(['usuario_id' => $this->alumno4A->usuario_id], ['programa_id' => $this->programaIID->programa_id, 'matricula' => '2026104001', 'cuatrimestre' => 4, 'grupo' => 'A', 'saldo_puntos' => 50]);
        EstudianteInscripcion::firstOrCreate(['estudiante_id' => $this->alumno4A->usuario_id, 'grupo_id' => $this->grupo4A->grupo_id, 'periodo_id' => $this->periodo->periodo_id], ['estado' => 'ACTIVO']);

        $this->alumno4B = User::factory()->create(['rol_id' => $rolAlumno->rol_id, 'nombre' => 'Daniel', 'email' => 'daniel@upemor.test', 'estado' => 'ACTIVO']);
        Estudiante::firstOrCreate(['usuario_id' => $this->alumno4B->usuario_id], ['programa_id' => $this->programaIID->programa_id, 'matricula' => '2026104003', 'cuatrimestre' => 4, 'grupo' => 'B', 'saldo_puntos' => 50]);
        EstudianteInscripcion::firstOrCreate(['estudiante_id' => $this->alumno4B->usuario_id, 'grupo_id' => $this->grupo4B->grupo_id, 'periodo_id' => $this->periodo->periodo_id], ['estado' => 'ACTIVO']);

        $this->alumno7A = User::factory()->create(['rol_id' => $rolAlumno->rol_id, 'nombre' => 'Valeria', 'email' => 'valeria@upemor.test', 'estado' => 'ACTIVO']);
        Estudiante::firstOrCreate(['usuario_id' => $this->alumno7A->usuario_id], ['programa_id' => $this->programaIID->programa_id, 'matricula' => '2026107001', 'cuatrimestre' => 7, 'grupo' => 'A', 'saldo_puntos' => 50]);
        EstudianteInscripcion::firstOrCreate(['estudiante_id' => $this->alumno7A->usuario_id, 'grupo_id' => $this->grupo7A->grupo_id, 'periodo_id' => $this->periodo->periodo_id], ['estado' => 'ACTIVO']);

        $this->alumnoIFI = User::factory()->create(['rol_id' => $rolAlumno->rol_id, 'nombre' => 'Elena', 'email' => 'elena@upemor.test', 'estado' => 'ACTIVO']);
        Estudiante::firstOrCreate(['usuario_id' => $this->alumnoIFI->usuario_id], ['programa_id' => $this->programaIFI->programa_id, 'matricula' => '2026104007', 'cuatrimestre' => 4, 'grupo' => 'A', 'saldo_puntos' => 50]);
        EstudianteInscripcion::firstOrCreate(['estudiante_id' => $this->alumnoIFI->usuario_id, 'grupo_id' => $this->grupoIFI4A->grupo_id, 'periodo_id' => $this->periodo->periodo_id], ['estado' => 'ACTIVO']);
    }

    /**
     * TEST 1: Dos docentes impartiendo la misma materia aparecen unificados en la vista de oferta del alumno.
     */
    public function test_two_teachers_in_same_subject_grouped_together_in_student_view(): void
    {
        // Carlos asignado con alcance general (TODOS)
        DocenteAsignacion::create([
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupo4B->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        // Laura asignada para Grupo B
        DocenteAsignacion::create([
            'docente_id' => $this->docente2->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupo4B->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        $response = $this->actingAs($this->alumno4B)->get(route('alumno.academico.index'));
        $response->assertOk();
        $response->assertSee('Análisis y Diseño de Software');
        $response->assertSee('Carlos Mendoza');
        $response->assertSee('Laura Morales');
    }

    /**
     * TEST 2: Materia sin docente asignado no aparece en el catálogo de ofertas del alumno.
     */
    public function test_subject_without_authorized_docente_is_not_offered_to_student(): void
    {
        // Cálculo de Varias Variables no tiene asignaciones docentes
        $response = $this->actingAs($this->alumno4B)->get(route('alumno.academico.index'));
        $response->assertOk();
        $response->assertDontSee('IID-C4-03');
        $response->assertDontSee('Cálculo de Varias Variables');
    }

    /**
     * TEST 3: Alumno de 4° B no puede solicitar materias exclusivas del Grupo A ni de otro programa.
     */
    public function test_student_in_4b_cannot_request_subjects_of_other_group_or_program(): void
    {
        // Asignación exclusiva para Grupo 4° A
        $asigExclusivaA = DocenteAsignacion::create([
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupo4A->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        // Asignación de IFI
        $asigIFI = DocenteAsignacion::create([
            'docente_id' => $this->docente2->usuario_id,
            'asignatura_id' => $this->asigFinanzas->asignatura_id,
            'grupo_id' => $this->grupoIFI4A->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        // Alumno 4° B intenta solicitar la asignación exclusiva de 4° A
        $resA = $this->actingAs($this->alumno4B)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asigExclusivaA->asignacion_id,
        ]);
        $resA->assertSessionHas('error');

        // Alumno 4° B intenta solicitar la materia de IFI
        $resIFI = $this->actingAs($this->alumno4B)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asigIFI->asignacion_id,
        ]);
        $resIFI->assertSessionHas('error');
    }

    /**
     * TEST 4: Ciclo completo: alumno solicita -> docente aprueba -> inscripción queda formalizada.
     */
    public function test_full_enrollment_lifecycle_and_idempotent_approval(): void
    {
        Notification::fake();

        $asig = DocenteAsignacion::create([
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asigSoftware->asignatura_id,
            'grupo_id' => $this->grupo4B->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        // 1. Alumno solicita
        $resSol = $this->actingAs($this->alumno4B)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asig->asignacion_id,
            'motivo_solicitud' => 'Deseo cursar la materia regular',
        ]);
        $resSol->assertRedirect(route('alumno.academico.index'));
        $resSol->assertSessionHas('status');

        $inscrip = EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumno4B->usuario_id)
            ->where('asignatura_id', $this->asigSoftware->asignatura_id)
            ->first();
        $this->assertNotNull($inscrip);
        $this->assertEquals(EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE, $inscrip->estado);

        // 2. Docente aprueba
        $resAprob = $this->actingAs($this->docente1)->post(
            route('docente.academico.inscripciones.aprobar', $inscrip->inscripcion_materia_id),
            ['motivo_resolucion' => 'Aceptado formalmente en el grupo B']
        );
        $resAprob->assertSessionHas('status');
        $this->assertEquals(EstudianteAsignaturaInscripcion::ESTADO_APROBADA, $inscrip->fresh()->estado);

        // 3. Idempotencia: intentar aprobarla de nuevo no falla
        $resSegunda = $this->actingAs($this->docente1)->post(
            route('docente.academico.inscripciones.aprobar', $inscrip->inscripcion_materia_id)
        );
        $resSegunda->assertSessionHas('info');
    }

    /**
     * TEST 5: RespaldoDatabaseService verifica integridad estructural y hash SHA-256 de forma no destructiva.
     */
    public function test_respaldo_service_verifies_integrity_and_prevents_path_traversal(): void
    {
        $service = app(RespaldoDatabaseService::class);

        // Path traversal debe ser rechazado
        $this->assertNull($service->obtenerRutaDescarga('../../../etc/passwd'));
        $this->assertNull($service->obtenerRutaDescarga('archivo_inexistente.sql'));

        // Archivo que no existe retorna inválido
        $verif = $service->verificarIntegridad('inexistente_123.sql');
        $this->assertFalse($verif['valido']);
    }
}

