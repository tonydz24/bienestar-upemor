<?php

namespace Tests\Feature;

use App\Models\CitaDisponibilidad;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitaSolicitudTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;
    private Estudiante $estudiante;
    private User $docenteUser;
    private CitaProfesional $profesional;
    private CitaDisponibilidad $disponibilidadLunes;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $programa = ProgramaEducativo::factory()->create(['clave' => 'ITI', 'activo' => true]);

        // Alumno autenticado
        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'alumno.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2026101111',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 10,
            'permite_visualizacion' => true,
        ]);

        // Orientador profesional
        $this->docenteUser = User::factory()->withRole($rolDocente)->create([
            'email' => 'orientador.test@upemor.edu.mx',
            'nombre' => 'Ana',
            'apellido_paterno' => 'Martínez',
            'apellido_materno' => 'Rojas',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docenteUser->usuario_id,
            'numero_empleado' => 'DOC-OR-99',
            'puesto' => 'Orientadora Psicológica',
            'especialidad' => 'Psicología',
            'cedula_profesional' => 'PSI-12345',
            'activo' => true,
        ]);
        $this->profesional = CitaProfesional::create([
            'usuario_id' => $this->docenteUser->usuario_id,
            'cargo' => 'Orientadora Psicológica',
            'cedula_profesional' => 'PSI-12345',
            'lugar_atencion' => 'Cubículo 3',
            'modalidades_permitidas' => 'AMBAS',
            'permite_citas' => true,
            'activo' => true,
        ]);

        // Disponibilidad Lunes de 09:00 a 12:00 (duración 45 min)
        $this->disponibilidadLunes = CitaDisponibilidad::create([
            'profesional_id' => $this->profesional->profesional_id,
            'dia_semana' => 1, // Lunes
            'hora_inicio' => '09:00:00',
            'hora_fin' => '12:00:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
            'activo' => true,
        ]);
    }

    public function test_alumno_can_access_citas_index(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.citas.index'));

        $response->assertOk();
        $response->assertSee('Citas de Orientación y Acompañamiento');
        $response->assertSee('Solicitar nueva cita');
    }

    public function test_alumno_can_fetch_available_horarios_via_ajax(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $response = $this->actingAs($this->alumno)->getJson(
            route('alumno.citas.horarios-disponibles', [
                'profesional_id' => $this->profesional->profesional_id,
                'fecha' => $proximoLunes,
            ])
        );

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'fecha' => $proximoLunes,
        ]);
        $this->assertNotEmpty($response->json('slots'));
        $this->assertSame('09:00', $response->json('slots.0.hora_inicio'));
        $this->assertSame('09:45', $response->json('slots.0.hora_fin'));
        $this->assertTrue($response->json('slots.0.disponible'));
    }

    public function test_alumno_can_successfully_request_appointment(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $response = $this->actingAs($this->alumno)->post(route('alumno.citas.store'), [
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '09:00',
            'modalidad' => 'PRESENCIAL',
            'motivo_consulta' => 'Dificultades para concentrarme en época de evaluaciones.',
        ]);

        $this->assertDatabaseHas('citas_orientacion', [
            'estudiante_id' => $this->alumno->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_SOLICITADA,
        ]);

        $cita = CitaOrientacion::where('estudiante_id', $this->alumno->usuario_id)->first();
        $response->assertRedirect(route('alumno.citas.show', $cita->cita_id));
        $response->assertSessionHas('success');
    }

    public function test_alumno_cannot_request_appointment_in_the_past(): void
    {
        $fechaPasada = Carbon::now()->subDays(2)->toDateString();

        $response = $this->actingAs($this->alumno)->post(route('alumno.citas.store'), [
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $fechaPasada,
            'hora_inicio' => '09:00',
            'modalidad' => 'PRESENCIAL',
        ]);

        $response->assertSessionHasErrors(['fecha_cita']);
        $this->assertDatabaseEmpty('citas_orientacion');
    }

    public function test_alumno_can_view_own_appointment_details(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_SOLICITADA,
            'motivo_consulta' => 'Consulta confidencial de orientación.',
            'fecha_solicitud' => now(),
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.citas.show', $cita->cita_id));

        $response->assertOk();
        $response->assertSee('Detalle de la Cita de Orientación');
        $response->assertSee('Ana Martínez Rojas');
        $response->assertSee('Consulta confidencial de orientación.');
    }

    public function test_alumno_cannot_view_another_students_appointment(): void
    {
        $rolAlumno = Rol::where('nombre', 'Alumno')->first();
        $otroAlumno = User::factory()->withRole($rolAlumno)->create(['estado' => 'ACTIVO']);
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $citaOtro = CitaOrientacion::create([
            'estudiante_id' => $otroAlumno->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_SOLICITADA,
            'motivo_consulta' => 'Asunto estrictamente privado de otro alumno.',
            'fecha_solicitud' => now(),
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.citas.show', $citaOtro->cita_id));

        $response->assertForbidden();
    }

    public function test_alumno_can_cancel_own_pending_or_confirmed_appointment(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_SOLICITADA,
            'fecha_solicitud' => now(),
        ]);

        $response = $this->actingAs($this->alumno)->patch(route('alumno.citas.cancelar', $cita->cita_id), [
            'motivo' => 'Cruza con examen cuatrimestral.',
        ]);

        $response->assertRedirect(route('alumno.citas.index'));
        $this->assertDatabaseHas('citas_orientacion', [
            'cita_id' => $cita->cita_id,
            'estado' => CitaOrientacion::ESTADO_CANCELADA_ALUMNO,
            'motivo_cancelacion_rechazo' => 'Cruza con examen cuatrimestral.',
        ]);
    }

    public function test_cancelled_appointment_frees_slot_for_another_student(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        // Creamos cita y la cancelamos
        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CANCELADA_ALUMNO,
            'fecha_solicitud' => now(),
        ]);

        // Verificamos que al consultar slots, 09:00 está nuevamente libre
        $response = $this->actingAs($this->alumno)->getJson(
            route('alumno.citas.horarios-disponibles', [
                'profesional_id' => $this->profesional->profesional_id,
                'fecha' => $proximoLunes,
            ])
        );

        $response->assertOk();
        $slot9am = collect($response->json('slots'))->firstWhere('hora_inicio', '09:00');
        $this->assertNotNull($slot9am);
        $this->assertTrue($slot9am['disponible']);
    }
}
