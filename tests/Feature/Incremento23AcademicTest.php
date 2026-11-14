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
use App\Models\Rol;
use App\Models\User;
use App\Services\Academico\CatedraAcademicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Incremento23AcademicTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $docenteUser;
    private User $alumnoUserB;
    private User $alumnoUserA;
    private User $alumnoOtroPrograma;
    private Estudiante $estudianteB;
    private Estudiante $estudianteA;
    private Estudiante $estudianteOtro;
    private ProgramaEducativo $programaIID;
    private ProgramaEducativo $programaOtro;
    private PlanEstudio $planIID2024;
    private PlanEstudio $planIID2026;
    private PlanEstudio $planOtro;
    private PeriodoAcademico $periodo;
    private Grupo $grupoA;
    private Grupo $grupoB;
    private Asignatura $eticaProfesional;
    private Asignatura $appsMoviles;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['nombre' => 'Administrador', 'descripcion' => 'Admin']);
        $rolDocente = Rol::create(['nombre' => 'Docente', 'descripcion' => 'Docente']);
        $rolAlumno = Rol::create(['nombre' => 'Alumno', 'descripcion' => 'Alumno']);

        $this->admin = User::create([
            'rol_id' => $rolAdmin->rol_id,
            'nombre' => 'Admin',
            'apellido_paterno' => 'Sistema',
            'email' => 'admin.i23@upemor.edu.mx',
            'password' => 'Secret123!',
            'estado' => 'ACTIVO',
        ]);

        $this->docenteUser = User::create([
            'rol_id' => $rolDocente->rol_id,
            'nombre' => 'Elizabeth Odet',
            'apellido_paterno' => 'Armenta',
            'email' => 'odet.armenta@upemor.edu.mx',
            'password' => 'Doc123!',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docenteUser->usuario_id,
            'numero_empleado' => 'DOC-I23-01',
            'activo' => true,
        ]);

        $this->programaIID = ProgramaEducativo::create([
            'nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital',
            'clave' => 'IID',
            'activo' => true,
        ]);

        $this->programaOtro = ProgramaEducativo::create([
            'nombre' => 'Ingeniería Financiera',
            'clave' => 'IFI',
            'activo' => true,
        ]);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-20',
            'activo' => true,
        ]);

        $this->planIID2024 = PlanEstudio::create([
            'programa_id' => $this->programaIID->programa_id,
            'codigo' => 'IID-2024',
            'nombre' => 'Plan de Estudios Oficial IID 2024',
            'version' => '2024.1',
            'anio_aprobacion' => 2024,
            'vigencia_inicio' => '2024-01-01',
            'estado' => PlanEstudio::ESTADO_HISTORICO,
            'es_vigente' => false,
        ]);

        $this->planIID2026 = PlanEstudio::create([
            'programa_id' => $this->programaIID->programa_id,
            'codigo' => 'IID-2026',
            'nombre' => 'Plan Reformado IID 2026',
            'version' => '2026.1',
            'anio_aprobacion' => 2026,
            'vigencia_inicio' => '2026-01-01',
            'estado' => PlanEstudio::ESTADO_VIGENTE,
            'es_vigente' => true,
        ]);

        $this->planOtro = PlanEstudio::create([
            'programa_id' => $this->programaOtro->programa_id,
            'codigo' => 'IFI-2024',
            'nombre' => 'Plan Oficial IFI 2024',
            'version' => '2024.1',
            'anio_aprobacion' => 2024,
            'vigencia_inicio' => '2024-01-01',
            'estado' => PlanEstudio::ESTADO_VIGENTE,
            'es_vigente' => true,
        ]);

        $this->grupoA = Grupo::create([
            'programa_id' => $this->programaIID->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'codigo' => 'IID-4A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupoB = Grupo::create([
            'programa_id' => $this->programaIID->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'B',
            'codigo' => 'IID-4B',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->eticaProfesional = Asignatura::create([
            'programa_id' => $this->programaIID->programa_id,
            'plan_id' => $this->planIID2024->plan_id,
            'clave' => 'IID-C4-02',
            'nombre' => 'Ética Profesional',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);

        $this->appsMoviles = Asignatura::create([
            'programa_id' => $this->programaIID->programa_id,
            'plan_id' => $this->planIID2024->plan_id,
            'clave' => 'IID-C4-06',
            'nombre' => 'Desarrollo de Aplicaciones Móviles',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);

        // Alumno Manuel Antonio (Grupo B)
        $this->alumnoUserB = User::create([
            'rol_id' => $rolAlumno->rol_id,
            'nombre' => 'Manuel Antonio',
            'apellido_paterno' => 'Arellano',
            'email' => 'manuel.arellano@upemor.edu.mx',
            'password' => 'Alumno123!',
            'estado' => 'ACTIVO',
        ]);
        $this->estudianteB = Estudiante::create([
            'usuario_id' => $this->alumnoUserB->usuario_id,
            'programa_id' => $this->programaIID->programa_id,
            'plan_id' => $this->planIID2024->plan_id,
            'matricula' => '2024107011',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'saldo_puntos' => 150,
            'permite_visualizacion' => true,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUserB->usuario_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        // Alumno Grupo A
        $this->alumnoUserA = User::create([
            'rol_id' => $rolAlumno->rol_id,
            'nombre' => 'Estudiante A',
            'apellido_paterno' => 'Grupo A',
            'email' => 'alumno.a@upemor.edu.mx',
            'password' => 'Alumno123!',
            'estado' => 'ACTIVO',
        ]);
        $this->estudianteA = Estudiante::create([
            'usuario_id' => $this->alumnoUserA->usuario_id,
            'programa_id' => $this->programaIID->programa_id,
            'plan_id' => $this->planIID2024->plan_id,
            'matricula' => '2024107012',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 100,
            'permite_visualizacion' => true,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUserA->usuario_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        // Alumno de otra carrera
        $this->alumnoOtroPrograma = User::create([
            'rol_id' => $rolAlumno->rol_id,
            'nombre' => 'Estudiante IFI',
            'apellido_paterno' => 'Financiera',
            'email' => 'alumno.ifi@upemor.edu.mx',
            'password' => 'Alumno123!',
            'estado' => 'ACTIVO',
        ]);
        $this->estudianteOtro = Estudiante::create([
            'usuario_id' => $this->alumnoOtroPrograma->usuario_id,
            'programa_id' => $this->programaOtro->programa_id,
            'plan_id' => $this->planOtro->plan_id,
            'matricula' => '2024108001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 100,
            'permite_visualizacion' => true,
        ]);
    }

    /**
     * TRAYECTORIA ACADÉMICA:
     * Comprueba cambio excepcional de plan entre planes de la misma carrera con justificación y auditoría.
     */
    public function test_cambio_excepcional_de_plan_actualiza_estudiante_y_registra_historial(): void
    {
        $this->assertEquals($this->planIID2024->plan_id, $this->estudianteB->fresh()->plan_id);

        $response = $this->actingAs($this->admin)->post(
            route('admin.estudiantes.cambiar-plan', $this->alumnoUserB->usuario_id),
            [
                'plan_id' => $this->planIID2026->plan_id,
                'motivo' => 'Dictamen de convalidación académica por transición curricular aprobada en consejo.',
                'confirmacion' => '1',
            ]
        );

        $response->assertRedirect(route('admin.estudiantes.trayectoria', $this->alumnoUserB->usuario_id));
        $response->assertSessionHas('success');

        // Plan actualizado
        $this->assertEquals($this->planIID2026->plan_id, $this->estudianteB->fresh()->plan_id);

        // Historial registrado
        $this->assertDatabaseHas('estudiante_plan_historial', [
            'estudiante_id' => $this->alumnoUserB->usuario_id,
            'plan_anterior_id' => $this->planIID2024->plan_id,
            'plan_nuevo_id' => $this->planIID2026->plan_id,
            'admin_usuario_id' => $this->admin->usuario_id,
            'resultado' => 'EXITOSO',
        ]);
    }

    /**
     * TRAYECTORIA ACADÉMICA:
     * Intento de cambio al mismo plan actual es rechazado por el backend.
     */
    public function test_intento_de_cambiar_al_mismo_plan_es_rechazado(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.estudiantes.cambiar-plan', $this->alumnoUserB->usuario_id),
            [
                'plan_id' => $this->planIID2024->plan_id,
                'motivo' => 'Intento redundante de reasignar el plan que el estudiante ya tiene asignado.',
                'confirmacion' => '1',
            ]
        );

        $response->assertSessionHas('error');
        $this->assertEquals($this->planIID2024->plan_id, $this->estudianteB->fresh()->plan_id);
    }

    /**
     * TRAYECTORIA ACADÉMICA:
     * Intento de cambio a plan de otra carrera es rechazado.
     */
    public function test_intento_de_cambiar_a_plan_de_otra_carrera_es_rechazado(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.estudiantes.cambiar-plan', $this->alumnoUserB->usuario_id),
            [
                'plan_id' => $this->planOtro->plan_id,
                'motivo' => 'Intento inválido de cambiar a plan de otra carrera.',
                'confirmacion' => '1',
            ]
        );

        $response->assertSessionHas('error');
        $this->assertEquals($this->planIID2024->plan_id, $this->estudianteB->fresh()->plan_id);
    }

    /**
     * ASIGNACIONES DOCENTES POR GRUPO Y ELEGIBILIDAD (ÉTICA PROFESIONAL):
     * Comprueba el flujo completo donde Odet imparte para Grupo A (ESPECIFICO),
     * Manuel (Grupo B) no la ve inicialmente, luego Odet solicita impartir en Grupo B,
     * el admin aprueba solo Grupo B sin tocar Grupo A, y Manuel puede solicitar e inscribirse.
     */
    public function test_flujo_completo_asignacion_grupo_especifico_y_elegibilidad(): void
    {
        $service = app(CatedraAcademicaService::class);

        // 1. Asignación inicial de Ética Profesional aprobada para Grupo A con alcance ESPECIFICO
        $asigGrupoA = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->eticaProfesional->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
            'activo' => true,
        ]);

        // 2. Manuel (Grupo B) NO debe ver Ética Profesional en su oferta disponible
        $ofertaInicialManuel = $service->obtenerOfertaDocenteParaEstudiante(
            $this->alumnoUserB,
            $this->grupoB,
            $this->periodo
        );
        $this->assertFalse(
            $ofertaInicialManuel->contains('asignatura_id', $this->eticaProfesional->asignatura_id),
            'Manuel (Grupo B) no debe ver cátedra exclusiva del Grupo A.'
        );

        // 3. Estudiante del Grupo A sí debe verla
        $ofertaEstudianteA = $service->obtenerOfertaDocenteParaEstudiante(
            $this->alumnoUserA,
            $this->grupoA,
            $this->periodo
        );
        $this->assertTrue(
            $ofertaEstudianteA->contains('asignatura_id', $this->eticaProfesional->asignatura_id)
        );

        // 4. Odet solicita impartir Ética Profesional para el Grupo B
        $resultadoSolicitud = $service->solicitarCatedra(
            $this->docenteUser,
            $this->eticaProfesional,
            $this->periodo,
            'especifico',
            $this->grupoB->grupo_id,
            'Apertura de grupo B por demanda de matrícula'
        );

        $this->assertEquals(1, $resultadoSolicitud['total_creadas']);
        $asigGrupoB = $resultadoSolicitud['asignaciones'][0];
        $this->assertEquals('PENDIENTE', $asigGrupoB->estado);
        $this->assertEquals('ESPECIFICO', $asigGrupoB->modalidad_alcance);
        $this->assertEquals($this->grupoB->grupo_id, $asigGrupoB->grupo_id);

        // 5. El Admin aprueba ÚNICAMENTE la solicitud del Grupo B
        $resAprobacion = $service->aprobarCatedra(
            $asigGrupoB->asignacion_id,
            $this->admin,
            'Aprobado para grupo B por capacidad docente'
        );

        $this->assertEquals(1, $resAprobacion['total_aprobadas']);
        $this->assertEquals('APROBADA', $asigGrupoB->fresh()->estado);

        // La asignación del Grupo A debe permanecer INTACTA y activa
        $this->assertEquals('APROBADA', $asigGrupoA->fresh()->estado);
        $this->assertTrue((bool)$asigGrupoA->fresh()->activo);

        // 6. Ahora Manuel (Grupo B) SÍ visualiza la oferta de Ética Profesional
        $ofertaActualizadaManuel = $service->obtenerOfertaDocenteParaEstudiante(
            $this->alumnoUserB,
            $this->grupoB,
            $this->periodo
        );
        $this->assertTrue(
            $ofertaActualizadaManuel->contains('asignatura_id', $this->eticaProfesional->asignatura_id),
            'Manuel (Grupo B) ahora debe visualizar la cátedra aprobada para su grupo.'
        );

        // 7. Estudiante de otra carrera NUNCA debe ver la oferta
        $ofertaOtro = $service->obtenerOfertaDocenteParaEstudiante(
            $this->alumnoOtroPrograma,
            $this->grupoB,
            $this->periodo
        );
        $this->assertFalse(
            $ofertaOtro->contains('asignatura_id', $this->eticaProfesional->asignatura_id),
            'Estudiante de otro programa no debe ver cátedras de IID.'
        );

        // 8. Manuel solicita inscripción a la materia
        $inscripcion = $service->solicitarInscripcion(
            $this->alumnoUserB,
            $asigGrupoB,
            $this->grupoB,
            'Solicitud ordinaria de cuatrimestre'
        );
        $this->assertEquals('PENDIENTE', $inscripcion->estado);

        // 9. Docente aprueba inscripción del alumno
        $inscripcionAprobada = $service->aprobarInscripcion(
            $inscripcion->inscripcion_materia_id,
            $this->docenteUser
        );
        $this->assertEquals('APROBADA', $inscripcionAprobada->estado);
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'inscripcion_materia_id' => $inscripcion->inscripcion_materia_id,
            'estudiante_id' => $this->alumnoUserB->usuario_id,
            'asignatura_id' => $this->eticaProfesional->asignatura_id,
            'estado' => 'APROBADA',
        ]);
    }

    /**
     * DESDUPLICACIÓN EN MIS ASIGNATURAS:
     * Si existen dos registros aprobados con alcance TODOS para la misma materia y docente
     * (uno para grupo A y otro para grupo B), el estudiante debe recibir EXACTAMENTE UNA oferta.
     */
    public function test_desduplicacion_de_ofertas_docentes_para_el_mismo_docente_y_materia(): void
    {
        $service = app(CatedraAcademicaService::class);

        // Crear dos asignaciones activas con alcance TODOS para la misma materia y docente
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->appsMoviles->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
            'activo' => true,
        ]);

        $asigParaB = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->appsMoviles->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
            'activo' => true,
        ]);

        // Consultar ofertas para Manuel (Grupo B)
        $ofertaManuel = $service->obtenerOfertaDocenteParaEstudiante(
            $this->alumnoUserB,
            $this->grupoB,
            $this->periodo
        );

        // Debe haber EXACTAMENTE una oferta para Desarrollo de Apps Móviles
        $ofertasAppsMoviles = $ofertaManuel->where('asignatura_id', $this->appsMoviles->asignatura_id);
        $this->assertCount(1, $ofertasAppsMoviles, 'Debe haber exactamente una tarjeta para la misma materia y docente.');

        // Y la oferta elegida debe ser prioritariamente la correspondiente al grupo B del alumno
        $this->assertEquals($asigParaB->asignacion_id, $ofertasAppsMoviles->first()->asignacion_id);
    }
}

