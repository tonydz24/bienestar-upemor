<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EstabilizacionIncrementoTest extends TestCase
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

    public function test_all_four_standardized_demo_credentials_authenticate_successfully(): void
    {
        $admin = User::create([
            'rol_id' => 1,
            'nombre' => 'Admin',
            'apellido_paterno' => 'UPEMOR',
            'email' => 'admin@upemor.edu.mx',
            'password' => Hash::make('Admin2026!'),
            'estado' => 'ACTIVO',
        ]);

        $docente = User::create([
            'rol_id' => 2,
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Pérez',
            'email' => 'docente.academico@upemor.edu.mx',
            'password' => Hash::make('Docente2026!'),
            'estado' => 'ACTIVO',
        ]);
        Docente::create(['usuario_id' => $docente->usuario_id, 'numero_empleado' => 'DOC-01', 'activo' => true]);

        $orientador = User::create([
            'rol_id' => 4,
            'nombre' => 'Valeria',
            'apellido_paterno' => 'Morales',
            'email' => 'orientador.psicologico@upemor.edu.mx',
            'password' => Hash::make('Orientador2026!'),
            'estado' => 'ACTIVO',
        ]);
        CitaProfesional::create(['usuario_id' => $orientador->usuario_id, 'cargo' => 'Orientadora', 'cedula_profesional' => 'PSI-01', 'activo' => true]);

        $alumno = User::create([
            'rol_id' => 3,
            'nombre' => 'Ana Sofía',
            'apellido_paterno' => 'Martínez',
            'email' => 'alumno.demo@upemor.edu.mx',
            'password' => Hash::make('Alumno2026!'),
            'estado' => 'ACTIVO',
        ]);

        // Login Admin
        $respAdmin = $this->post(route('login.post'), ['email' => 'admin@upemor.edu.mx', 'password' => 'Admin2026!']);
        $respAdmin->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));

        // Login Docente
        $respDocente = $this->post(route('login.post'), ['email' => 'docente.academico@upemor.edu.mx', 'password' => 'Docente2026!']);
        $respDocente->assertRedirect(route('docente.dashboard'));
        $this->post(route('logout'));

        // Login Orientador
        $respOrientador = $this->post(route('login.post'), ['email' => 'orientador.psicologico@upemor.edu.mx', 'password' => 'Orientador2026!']);
        $respOrientador->assertRedirect(route('docente.citas.index'));
        $this->post(route('logout'));

        // Login Alumno
        $respAlumno = $this->post(route('login.post'), ['email' => 'alumno.demo@upemor.edu.mx', 'password' => 'Alumno2026!']);
        $respAlumno->assertRedirect(route('alumno.dashboard'));
        $this->post(route('logout'));
    }

    public function test_docente_and_orientador_roles_are_strictly_mutually_exclusive(): void
    {
        $orientador = User::create([
            'rol_id' => 4,
            'nombre' => 'Valeria',
            'apellido_paterno' => 'Morales',
            'email' => 'orientador.seg@upemor.edu.mx',
            'password' => Hash::make('Secret123!'),
            'estado' => 'ACTIVO',
        ]);
        CitaProfesional::create(['usuario_id' => $orientador->usuario_id, 'cargo' => 'Orientadora', 'cedula_profesional' => 'PSI-02', 'activo' => true]);

        $docente = User::create([
            'rol_id' => 2,
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Pérez',
            'email' => 'docente.seg@upemor.edu.mx',
            'password' => Hash::make('Secret123!'),
            'estado' => 'ACTIVO',
        ]);
        Docente::create(['usuario_id' => $docente->usuario_id, 'numero_empleado' => 'DOC-02', 'activo' => true]);

        // Orientador no tiene rol Docente
        $this->assertTrue($orientador->esOrientador());
        $this->assertFalse($orientador->esDocente());
        $this->assertFalse($orientador->hasRole('Docente'));

        // Docente no tiene rol Orientador
        $this->assertTrue($docente->esDocente());
        $this->assertFalse($docente->esOrientador());
        $this->assertFalse($docente->hasRole('Orientador'));

        // Orientador no puede entrar a rutas exclusivas de Docente
        $this->actingAs($orientador)->get(route('docente.academico.index'))->assertForbidden();
        $this->actingAs($orientador)->get(route('docente.recompensas.index'))->assertForbidden();

        // Docente no puede entrar a disponibilidad de Citas
        $this->actingAs($docente)->get(route('docente.citas.disponibilidad'))->assertForbidden();
    }

    public function test_reward_proposals_have_clear_spanish_validation_messages(): void
    {
        $docente = User::create([
            'rol_id' => 2,
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Pérez',
            'email' => 'docente.propuesta@upemor.edu.mx',
            'password' => Hash::make('Secret123!'),
            'estado' => 'ACTIVO',
        ]);
        Docente::create(['usuario_id' => $docente->usuario_id, 'numero_empleado' => 'DOC-03', 'activo' => true]);

        // Envío con campos vacíos
        $response = $this->actingAs($docente)->post(route('docente.recompensas.proponer'), []);

        $response->assertSessionHasErrors([
            'nombre' => 'Por favor, escribe el nombre del beneficio o recompensa.',
            'descripcion' => 'Por favor, describe en qué consiste el beneficio para los estudiantes.',
            'justificacion' => 'Por favor, explica por qué propones este beneficio y cómo apoya a los alumnos.',
        ]);

        // Envío con datos válidos
        $respValida = $this->actingAs($docente)->post(route('docente.recompensas.proponer'), [
            'nombre' => 'Asesoría adicional previa al examen',
            'descripcion' => 'Sesión de 45 minutos de resolución de dudas de la práctica.',
            'categoria' => 'ACADEMICA',
            'costo_puntos' => 20,
            'justificacion' => 'Apoya a estudiantes rezagados en el contenido del segundo parcial.',
        ]);

        $respValida->assertRedirect(route('docente.recompensas.index', ['tab' => 'propuestas']));
        $this->assertDatabaseHas('recompensas_catalogo', [
            'nombre' => 'Asesoría adicional previa al examen',
            'docente_solicitante_id' => $docente->usuario_id,
            'estado_propuesta' => RecompensaCatalogo::PROPUESTA_PENDIENTE,
        ]);
    }

    public function test_digital_coupon_complete_lifecycle(): void
    {
        $periodo = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-24',
            'activo' => true,
        ]);
        $programa = ProgramaEducativo::create(['clave' => 'IID', 'nombre' => 'Ingeniería en Tecnologías de la Información', 'activo' => true]);
        $plan = PlanEstudio::create(['programa_id' => $programa->programa_id, 'codigo' => 'IID-2024', 'nombre' => 'Plan IID', 'total_cuatrimestres' => 10, 'estado' => 'VIGENTE']);
        $grupo = Grupo::create(['programa_id' => $programa->programa_id, 'periodo_id' => $periodo->periodo_id, 'nombre' => 'A', 'cuatrimestre' => 4, 'activo' => true]);
        $asignatura = Asignatura::create(['programa_id' => $programa->programa_id, 'plan_id' => $plan->plan_id, 'clave' => 'IID-C4-04', 'nombre' => 'Aplicaciones Web', 'cuatrimestre' => 4, 'activo' => true]);

        $docente = User::create(['rol_id' => 2, 'nombre' => 'Roberto', 'apellido_paterno' => 'Pérez', 'email' => 'docente.canje@upemor.edu.mx', 'password' => Hash::make('Docente2026!'), 'estado' => 'ACTIVO']);
        Docente::create(['usuario_id' => $docente->usuario_id, 'numero_empleado' => 'DOC-04', 'activo' => true]);

        $asignacion = DocenteAsignacion::create([
            'docente_id' => $docente->usuario_id,
            'asignatura_id' => $asignatura->asignatura_id,
            'grupo_id' => $grupo->grupo_id,
            'periodo_id' => $periodo->periodo_id,
            'activo' => true,
        ]);

        $alumno = User::create(['rol_id' => 3, 'nombre' => 'Ana', 'apellido_paterno' => 'Martínez', 'email' => 'alumno.canje@upemor.edu.mx', 'password' => Hash::make('Alumno2026!'), 'estado' => 'ACTIVO']);
        $estudiante = Estudiante::create([
            'usuario_id' => $alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2026040001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 50,
            'permite_visualizacion' => true,
        ]);

        EstudianteInscripcion::create([
            'estudiante_id' => $alumno->usuario_id,
            'grupo_id' => $grupo->grupo_id,
            'periodo_id' => $periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        $recompensa = RecompensaCatalogo::create([
            'nombre' => 'Prórroga de entrega',
            'descripcion' => 'Extensión de plazo 24h',
            'categoria' => 'TIEMPO',
            'costo_puntos_base' => 15,
            'activo' => true,
        ]);

        $activacion = RecompensaActivada::create([
            'recompensa_id' => $recompensa->recompensa_id,
            'asignacion_id' => $asignacion->asignacion_id,
            'cupo_maximo' => 5,
            'cupo_disponible' => 5,
            'costo_puntos' => 15,
            'vigencia_inicio' => now()->subDay(),
            'vigencia_fin' => now()->addMonth(),
            'estado' => 'ACTIVA',
        ]);

        // 1. Alumno canjea el beneficio digital
        $respCanje = $this->actingAs($alumno)->post(route('alumno.recompensas.canjear', $activacion->activacion_id));
        $respCanje->assertSessionHas('success');

        // Puntos deducidos y cupo decrementado
        $this->assertEquals(35, $estudiante->fresh()->saldo_puntos);
        $this->assertEquals(4, $activacion->fresh()->cupo_disponible);

        // Cupón con código BEN-XXXXXXXXXX generado en estado CANJEADA
        $canje = RecompensaCanje::where('estudiante_id', $alumno->usuario_id)->first();
        $this->assertNotNull($canje);
        $this->assertEquals(RecompensaCanje::ESTADO_CANJEADA, $canje->estado);
        $this->assertStringStartsWith('BEN-', $canje->codigo_beneficio);
        $this->assertEquals(14, strlen($canje->codigo_beneficio)); // BEN- (4) + 10 chars

        // 2. Docente marca el beneficio como UTILIZADO
        $respUtilizar = $this->actingAs($docente)->patch(route('docente.recompensas.utilizar', $canje->canje_id));
        $respUtilizar->assertSessionHas('success');

        $this->assertEquals(RecompensaCanje::ESTADO_UTILIZADA, $canje->fresh()->estado);
        $this->assertNotNull($canje->fresh()->fecha_utilizacion);
        $this->assertEquals($docente->usuario_id, $canje->fresh()->utilizado_por);
    }
}
