<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\EvaluacionIntento;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\PeriodoAcademico;
use App\Models\PeriodoParcial;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Services\Recompensas\RecompensaService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Incremento62Test extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Rol $rolAlumno;
    private Rol $rolOrientador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::updateOrCreate(['rol_id' => 1], ['nombre' => 'Administrador', 'descripcion' => 'Admin', 'activo' => true]);
        $this->rolDocente = Rol::updateOrCreate(['rol_id' => 2], ['nombre' => 'Docente', 'descripcion' => 'Docente', 'activo' => true]);
        $this->rolAlumno = Rol::updateOrCreate(['rol_id' => 3], ['nombre' => 'Alumno', 'descripcion' => 'Alumno', 'activo' => true]);
        $this->rolOrientador = Rol::updateOrCreate(['rol_id' => 4], ['nombre' => 'Orientador', 'descripcion' => 'Orientador', 'activo' => true]);
    }

    public function test_evaluaciones_intentos_can_be_created_and_persisted(): void
    {
        $programa = ProgramaEducativo::create([
            'clave' => 'IID',
            'nombre' => 'Ingeniería en Informática y Desarrollo Digital',
            'activo' => true,
        ]);

        $alumno = User::create([
            'rol_id' => 3,
            'nombre' => 'Estudiante',
            'apellido_paterno' => 'Test',
            'email' => 'alumno.eval@upemor.edu.mx',
            'password' => Hash::make('Password123!'),
            'estado' => 'ACTIVO',
        ]);

        Estudiante::create([
            'usuario_id' => $alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => 'TEST001',
            'cuatrimestre' => 4,
            'saldo_puntos' => 100,
        ]);

        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'DEMO-HABITOS',
            'nombre' => 'Evaluación de Hábitos de Estudio',
            'categoria' => 'ACADEMICO',
            'activo' => true,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => 1,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
            'preguntas_totales' => 10,
            'instrucciones' => 'Responde con sinceridad cada pregunta.',
        ]);

        $intento = EvaluacionIntento::create([
            'usuario_id' => $alumno->usuario_id,
            'version_id' => $version->version_id,
            'estado' => EvaluacionIntento::ESTADO_FINALIZADO,
            'fecha_inicio' => Carbon::now()->subMinutes(15),
            'fecha_finalizacion' => Carbon::now(),
            'puntuacion_total' => 28,
            'nivel_resultado' => 'ADECUADO',
            'tiene_alerta_critica' => false,
            'ip_registro' => '127.0.0.1',
        ]);

        $this->assertDatabaseHas('evaluaciones_intentos', [
            'intento_id' => $intento->intento_id,
            'usuario_id' => $alumno->usuario_id,
            'estado' => EvaluacionIntento::ESTADO_FINALIZADO,
        ]);

        $response = $this->actingAs($alumno)->get(route('alumno.evaluaciones'));
        $response->assertOk();
    }

    public function test_recompensa_propuesta_single_source_of_truth_and_status_accessors(): void
    {
        $docente = User::create([
            'rol_id' => 2,
            'nombre' => 'Profesor',
            'apellido_paterno' => 'Test',
            'email' => 'docente.prop@upemor.edu.mx',
            'password' => Hash::make('Docente123!'),
            'estado' => 'ACTIVO',
        ]);

        $admin = User::create([
            'rol_id' => 1,
            'nombre' => 'Administrador',
            'apellido_paterno' => 'UPEMOR',
            'email' => 'admin.prop@upemor.edu.mx',
            'password' => Hash::make('Admin123!'),
            'estado' => 'ACTIVO',
        ]);

        $propuesta = RecompensaCatalogo::create([
            'clave' => 'PROP-TEST-01',
            'nombre' => 'Asesoría adicional personalizada',
            'descripcion' => '30 minutos de asesoría 1 a 1 para resolver dudas.',
            'categoria' => 'ACADEMICO',
            'costo_puntos_base' => 150,
            'propuesta_por_docente_id' => $docente->usuario_id,
            'estado_propuesta' => RecompensaCatalogo::PROPUESTA_PENDIENTE,
            'es_institucional' => false,
            'activo' => false,
        ]);

        $this->assertTrue($propuesta->esPropuestaPendiente());
        $this->assertFalse($propuesta->esPropuestaAprobada());
        $this->assertFalse($propuesta->esPropuestaRechazada());

        $service = app(RecompensaService::class);
        $aprobada = $service->aprobarPropuestaAdmin($propuesta, $admin);

        $this->assertTrue($aprobada->esPropuestaAprobada());
        $this->assertEquals(RecompensaCatalogo::PROPUESTA_APROBADA, $aprobada->estado_propuesta);
        $this->assertEquals($admin->usuario_id, $aprobada->admin_resolutor_id);
        $this->assertNotNull($aprobada->fecha_resolucion);
        $this->assertTrue($aprobada->activo);
        $this->assertEquals(150, $aprobada->costo_puntos_base);
    }

    public function test_prevent_duplicate_active_reward_in_same_assignment(): void
    {
        $periodo = PeriodoAcademico::create([
            'clave' => '2026-TEST',
            'nombre' => 'Periodo Prueba',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-04-30',
            'activo' => true,
        ]);

        $programa = ProgramaEducativo::create([
            'clave' => 'IID',
            'nombre' => 'Ingeniería en Informática y Desarrollo Digital',
            'activo' => true,
            'estado_operativo' => ProgramaEducativo::OPERATIVO_ACTIVO,
        ]);

        $asig = Asignatura::create([
            'programa_id' => $programa->programa_id,
            'clave' => 'IID-TEST',
            'nombre' => 'Programación Avanzada',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);

        $grupo = Grupo::create([
            'programa_id' => $programa->programa_id,
            'periodo_id' => $periodo->periodo_id,
            'nombre' => 'A',
            'cuatrimestre' => 4,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $docente = User::create([
            'rol_id' => 2,
            'nombre' => 'Docente',
            'apellido_paterno' => 'Asig',
            'email' => 'docente.asig@upemor.edu.mx',
            'password' => Hash::make('Docente123!'),
            'estado' => 'ACTIVO',
        ]);

        $asignacion = DocenteAsignacion::create([
            'docente_id' => $docente->usuario_id,
            'asignatura_id' => $asig->asignatura_id,
            'grupo_id' => $grupo->grupo_id,
            'periodo_id' => $periodo->periodo_id,
            'activo' => true,
        ]);

        $recompensa = RecompensaCatalogo::create([
            'clave' => 'REC-01',
            'nombre' => 'Puntos extra en práctica',
            'descripcion' => 'Bonificación de puntos',
            'categoria' => 'ACADEMICO',
            'costo_puntos_base' => 100,
            'es_institucional' => true,
            'activo' => true,
        ]);

        $service = app(RecompensaService::class);

        // Primera activación debe ser exitosa
        $activacion1 = $service->activarRecompensa(
            asignacion: $asignacion,
            recompensaId: $recompensa->recompensa_id,
            cupoMaximo: 10,
            costoPuntos: 120,
            vigenciaInicio: '2026-01-01',
            vigenciaFin: '2026-04-30',
        );

        $this->assertNotNull($activacion1);

        // Segunda activación idéntica debe lanzar DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('ya está habilitado');

        $service->activarRecompensa(
            asignacion: $asignacion,
            recompensaId: $recompensa->recompensa_id,
            cupoMaximo: 5,
            costoPuntos: 150,
            vigenciaInicio: '2026-01-01',
            vigenciaFin: '2026-04-30',
        );
    }

    public function test_periodo_academico_calendar_status_and_overlap_prevention(): void
    {
        $pasado = PeriodoAcademico::create([
            'clave' => '2025-1',
            'nombre' => 'Enero - Abril 2025',
            'fecha_inicio' => '2025-01-05',
            'fecha_fin' => '2025-04-30',
            'activo' => true,
        ]);

        $this->assertEquals(PeriodoAcademico::ESTADO_CONCLUIDO, $pasado->estado_calendario);
        $this->assertFalse($pasado->estaVigentePorCalendario());
        $this->assertEquals('Concluido por calendario', $pasado->estado_calendario_label);

        $futuro = PeriodoAcademico::create([
            'clave' => '2030-1',
            'nombre' => 'Enero - Abril 2030',
            'fecha_inicio' => '2030-01-05',
            'fecha_fin' => '2030-04-30',
            'activo' => true,
        ]);

        $this->assertEquals(PeriodoAcademico::ESTADO_PROGRAMADO, $futuro->estado_calendario);
        $this->assertFalse($futuro->estaVigentePorCalendario());

        $admin = User::create([
            'rol_id' => 1,
            'nombre' => 'Admin',
            'apellido_paterno' => 'Periodos',
            'email' => 'admin.periodos@upemor.edu.mx',
            'password' => Hash::make('Admin123!'),
            'estado' => 'ACTIVO',
        ]);

        // Intentar registrar un nuevo periodo con solapamiento temporal respecto a 2030-1
        $response = $this->actingAs($admin)->post(route('admin.periodos.store'), [
            'clave' => '2030-SOLAPADO',
            'nombre' => 'Periodo Solapado Conflicto',
            'fecha_inicio' => '2030-02-01',
            'fecha_fin' => '2030-05-30',
            'activo' => '1',
        ]);

        $response->assertSessionHasErrors('fecha_inicio');
    }

    public function test_programa_educativo_scopes_and_historical_distinction(): void
    {
        $activo = ProgramaEducativo::create([
            'clave' => 'IID',
            'nombre' => 'Ingeniería en Informática y Desarrollo Digital',
            'activo' => true,
            'estado_operativo' => ProgramaEducativo::OPERATIVO_ACTIVO,
        ]);

        $historico = ProgramaEducativo::create([
            'clave' => 'ITA',
            'nombre' => 'Ingeniería en Tecnologías Ambientales',
            'activo' => false,
            'estado_operativo' => ProgramaEducativo::OPERATIVO_INACTIVO,
        ]);

        $admin = User::create([
            'rol_id' => 1,
            'nombre' => 'Admin',
            'apellido_paterno' => 'Curricular',
            'email' => 'admin.curricula@upemor.edu.mx',
            'password' => Hash::make('Admin123!'),
            'estado' => 'ACTIVO',
        ]);

        // Ver pestaña oficiales
        $responseOficiales = $this->actingAs($admin)->get(route('admin.carreras.index', ['tab' => 'oficiales']));
        $responseOficiales->assertOk();
        $responseOficiales->assertSee('IID');
        $responseOficiales->assertDontSee('Ingeniería en Tecnologías Ambientales');

        // Ver pestaña históricos
        $responseHistoricos = $this->actingAs($admin)->get(route('admin.carreras.index', ['tab' => 'historicos']));
        $responseHistoricos->assertOk();
        $responseHistoricos->assertSee('ITA');
    }
}
