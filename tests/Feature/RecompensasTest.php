<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecompensasTest extends TestCase
{
    use RefreshDatabase;

    protected User $docente1;
    protected User $docente2;
    protected User $alumnoUser;
    protected User $alumnoUser2;
    protected Estudiante $estudiante;
    protected Estudiante $estudiante2;
    protected PeriodoAcademico $periodo;
    protected Asignatura $asignatura;
    protected Grupo $grupo;
    protected DocenteAsignacion $asignacion1;
    protected RecompensaCatalogo $recompensaCat;

    protected function setUp(): void
    {
        parent::setUp();

        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $programa = ProgramaEducativo::factory()->create(['clave' => 'IID', 'activo' => true]);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-30',
            'activo' => true,
        ]);

        $this->asignatura = Asignatura::create([
            'programa_id' => $programa->programa_id,
            'clave' => 'IID-501',
            'nombre' => 'Desarrollo Web',
            'cuatrimestre' => 5,
            'activo' => true,
        ]);

        $this->grupo = Grupo::create([
            'programa_id' => $programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'A',
            'cuatrimestre' => 5,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->docente1 = User::factory()->withRole($rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $this->docente1->usuario_id,
            'numero_empleado' => 'EMP-101',
            'activo' => true,
        ]);

        $this->docente2 = User::factory()->withRole($rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $this->docente2->usuario_id,
            'numero_empleado' => 'EMP-102',
            'activo' => true,
        ]);

        $this->asignacion1 = DocenteAsignacion::create([
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
        ]);

        $this->alumnoUser = User::factory()->withRole($rolAlumno)->create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Mendoza',
            'apellido_materno' => 'Ríos',
            'estado' => 'ACTIVO',
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2026105555',
            'cuatrimestre' => 5,
            'saldo_puntos' => 30,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        $this->alumnoUser2 = User::factory()->withRole($rolAlumno)->create([
            'nombre' => 'Mariana',
            'apellido_paterno' => 'Flores',
            'apellido_materno' => 'Cruz',
            'estado' => 'ACTIVO',
        ]);
        $this->estudiante2 = Estudiante::create([
            'usuario_id' => $this->alumnoUser2->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2026106666',
            'cuatrimestre' => 5,
            'saldo_puntos' => 5,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser2->usuario_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        // Catálogo institucional de recompensas
        $this->recompensaCat = RecompensaCatalogo::create([
            'nombre' => 'Prórroga de 24h para entrega de práctica',
            'descripcion' => 'Otorga 24 horas adicionales para subir una práctica.',
            'categoria' => RecompensaCatalogo::CATEGORIA_ACADEMICA,
            'costo_puntos_base' => 15,
            'condiciones_generales' => 'Aplica una sola vez por cuatrimestre.',
            'activo' => true,
        ]);
    }

    public function test_docente_can_activate_reward_for_assigned_group(): void
    {
        $response = $this->actingAs($this->docente1)->post(
            route('docente.recompensas.store', $this->asignacion1->asignacion_id),
            [
                'recompensa_id' => $this->recompensaCat->recompensa_id,
                'cupo_maximo' => 5,
                'costo_puntos' => 20,
                'vigencia_inicio' => now()->toDateString(),
                'vigencia_fin' => now()->addDays(30)->toDateString(),
                'condiciones_docente' => 'Aplica para la práctica de la unidad 2.',
            ]
        );

        $response->assertRedirect(route('docente.recompensas.index', ['tab' => 'activadas']));
        $this->assertDatabaseHas('recompensas_activadas', [
            'recompensa_id' => $this->recompensaCat->recompensa_id,
            'asignacion_id' => $this->asignacion1->asignacion_id,
            'costo_puntos' => 20,
            'cupo_maximo' => 5,
            'cupo_disponible' => 5,
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);
    }

    public function test_docente_cannot_activate_reward_for_unassigned_group(): void
    {
        // Docente 2 intenta activar una recompensa en la asignación del Docente 1
        $response = $this->actingAs($this->docente2)->post(
            route('docente.recompensas.store', $this->asignacion1->asignacion_id),
            [
                'recompensa_id' => $this->recompensaCat->recompensa_id,
                'cupo_maximo' => 5,
                'costo_puntos' => 20,
                'vigencia_inicio' => now()->toDateString(),
                'vigencia_fin' => now()->addDays(30)->toDateString(),
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('recompensas_activadas', [
            'recompensa_id' => $this->recompensaCat->recompensa_id,
            'asignacion_id' => $this->asignacion1->asignacion_id,
        ]);
    }

    public function test_student_can_view_and_request_redemption_with_sufficient_points(): void
    {
        $activacion = RecompensaActivada::create([
            'recompensa_id' => $this->recompensaCat->recompensa_id,
            'asignacion_id' => $this->asignacion1->asignacion_id,
            'costo_puntos' => 15,
            'cupo_maximo' => 5,
            'cupo_disponible' => 5,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        $responseView = $this->actingAs($this->alumnoUser)->get(route('alumno.recompensas.index'));
        $responseView->assertOk();
        $responseView->assertSee('Prórroga de 24h');
        $responseView->assertSee('30');

        // Solicita el canje
        $responseCanje = $this->actingAs($this->alumnoUser)->post(
            route('alumno.recompensas.canjear', $activacion->activacion_id),
            [
                'motivo' => 'Solicito para práctica de CSS',
            ]
        );

        $responseCanje->assertRedirect(route('alumno.recompensas.index'));
        $responseCanje->assertSessionHas('success');

        // Verificar que los puntos del estudiante disminuyeron de 30 a 15
        $this->assertEquals(15, $this->estudiante->fresh()->saldo_puntos);

        // Verificar que el cupo disponible disminuyó de 5 a 4
        $this->assertEquals(4, $activacion->fresh()->cupo_disponible);

        // Verificar que se creó el canje en estado CANJEADA con código digital institucional
        $this->assertDatabaseHas('recompensas_canjes', [
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'puntos_canjeados' => 15,
            'estado' => RecompensaCanje::ESTADO_CANJEADA,
        ]);

        $canjeCreado = RecompensaCanje::where('estudiante_id', $this->alumnoUser->usuario_id)->first();
        $this->assertNotNull($canjeCreado->codigo_beneficio);
        $this->assertStringStartsWith('BEN-', $canjeCreado->codigo_beneficio);
    }

    public function test_student_cannot_redeem_with_insufficient_points(): void
    {
        $activacion = RecompensaActivada::create([
            'recompensa_id' => $this->recompensaCat->recompensa_id,
            'asignacion_id' => $this->asignacion1->asignacion_id,
            'costo_puntos' => 15,
            'cupo_maximo' => 5,
            'cupo_disponible' => 5,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        // alumnoUser2 tiene solo 5 puntos, el costo es 15
        $response = $this->actingAs($this->alumnoUser2)->post(
            route('alumno.recompensas.canjear', $activacion->activacion_id)
        );

        $response->assertSessionHas('error');
        // Puntos intactos
        $this->assertEquals(5, $this->estudiante2->fresh()->saldo_puntos);
        // Cupo intacto
        $this->assertEquals(5, $activacion->fresh()->cupo_disponible);
        // No se creó el canje
        $this->assertDatabaseMissing('recompensas_canjes', [
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser2->usuario_id,
        ]);
    }

    public function test_student_cannot_redeem_when_cupo_is_zero(): void
    {
        $activacion = RecompensaActivada::create([
            'recompensa_id' => $this->recompensaCat->recompensa_id,
            'asignacion_id' => $this->asignacion1->asignacion_id,
            'costo_puntos' => 10,
            'cupo_maximo' => 2,
            'cupo_disponible' => 0, // Agotado
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        $response = $this->actingAs($this->alumnoUser)->post(
            route('alumno.recompensas.canjear', $activacion->activacion_id)
        );

        $response->assertSessionHas('error');
        $this->assertEquals(30, $this->estudiante->fresh()->saldo_puntos);
        $this->assertDatabaseMissing('recompensas_canjes', [
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
        ]);
    }

    public function test_docente_can_approve_redemption(): void
    {
        $activacion = RecompensaActivada::create([
            'recompensa_id' => $this->recompensaCat->recompensa_id,
            'asignacion_id' => $this->asignacion1->asignacion_id,
            'costo_puntos' => 15,
            'cupo_maximo' => 5,
            'cupo_disponible' => 4,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        $canje = RecompensaCanje::create([
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'puntos_canjeados' => 15,
            'fecha_solicitud' => now(),
            'estado' => RecompensaCanje::ESTADO_SOLICITADO,
        ]);

        $response = $this->actingAs($this->docente1)->patch(
            route('docente.recompensas.aprobar', $canje->canje_id)
        );

        $response->assertRedirect();
        $this->assertEquals(RecompensaCanje::ESTADO_APROBADO, $canje->fresh()->estado);
        $this->assertNotNull($canje->fresh()->fecha_resolucion);
    }

    public function test_docente_can_reject_redemption_and_refunds_points_and_cupo(): void
    {
        $activacion = RecompensaActivada::create([
            'recompensa_id' => $this->recompensaCat->recompensa_id,
            'asignacion_id' => $this->asignacion1->asignacion_id,
            'costo_puntos' => 15,
            'cupo_maximo' => 5,
            'cupo_disponible' => 4,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        // Simular que el estudiante ya gastó 15 puntos (de 30 iniciales a 15)
        $this->estudiante->update(['saldo_puntos' => 15]);

        $canje = RecompensaCanje::create([
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'puntos_canjeados' => 15,
            'fecha_solicitud' => now(),
            'estado' => RecompensaCanje::ESTADO_SOLICITADO,
        ]);

        $response = $this->actingAs($this->docente1)->patch(
            route('docente.recompensas.rechazar', $canje->canje_id),
            ['motivo' => 'No aplica para exámenes departamentales.']
        );

        $response->assertRedirect();
        $this->assertEquals(RecompensaCanje::ESTADO_RECHAZADO, $canje->fresh()->estado);
        $this->assertEquals('No aplica para exámenes departamentales.', $canje->fresh()->motivo_rechazo);

        // Se deben reembolsar los 15 puntos al estudiante (15 + 15 = 30)
        $this->assertEquals(30, $this->estudiante->fresh()->saldo_puntos);

        // Se debe restituir el cupo disponible de 4 a 5
        $this->assertEquals(5, $activacion->fresh()->cupo_disponible);
    }

    public function test_student_can_cancel_pending_redemption_and_gets_refund(): void
    {
        $activacion = RecompensaActivada::create([
            'recompensa_id' => $this->recompensaCat->recompensa_id,
            'asignacion_id' => $this->asignacion1->asignacion_id,
            'costo_puntos' => 15,
            'cupo_maximo' => 5,
            'cupo_disponible' => 4,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        $this->estudiante->update(['saldo_puntos' => 15]);

        $canje = RecompensaCanje::create([
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'puntos_canjeados' => 15,
            'fecha_solicitud' => now(),
            'estado' => RecompensaCanje::ESTADO_SOLICITADO,
        ]);

        $response = $this->actingAs($this->alumnoUser)->delete(
            route('alumno.recompensas.cancelar', $canje->canje_id)
        );

        $response->assertRedirect(route('alumno.recompensas.index'));
        $this->assertEquals(RecompensaCanje::ESTADO_CANCELADO, $canje->fresh()->estado);

        // Puntos reembolsados
        $this->assertEquals(30, $this->estudiante->fresh()->saldo_puntos);

        // Cupo restaurado
        $this->assertEquals(5, $activacion->fresh()->cupo_disponible);
    }
}
