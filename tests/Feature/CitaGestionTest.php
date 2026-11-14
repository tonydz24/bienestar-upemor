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

class CitaGestionTest extends TestCase
{
    use RefreshDatabase;

    private User $orientadorUser;
    private CitaProfesional $profesional;
    private User $docenteComun;
    private User $alumnoUser;
    private Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $programa = ProgramaEducativo::factory()->create(['clave' => 'ITI', 'activo' => true]);

        // Orientador Acreditado
        $this->orientadorUser = User::factory()->withRole($rolDocente)->create([
            'email' => 'orientador.gestion@upemor.edu.mx',
            'nombre' => 'Ana',
            'apellido_paterno' => 'Martínez',
            'apellido_materno' => 'Rojas',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->orientadorUser->usuario_id,
            'numero_empleado' => 'DOC-01',
            'puesto' => 'Orientadora Institucional',
            'especialidad' => 'Psicología',
            'cedula_profesional' => 'CED-111',
            'activo' => true,
        ]);
        $this->profesional = CitaProfesional::create([
            'usuario_id' => $this->orientadorUser->usuario_id,
            'cargo' => 'Orientadora Institucional',
            'cedula_profesional' => 'CED-111',
            'lugar_atencion' => 'Cubículo 3',
            'modalidades_permitidas' => 'AMBAS',
            'permite_citas' => true,
            'activo' => true,
        ]);

        // Docente regular (no orientador)
        $this->docenteComun = User::factory()->withRole($rolDocente)->create([
            'email' => 'docente.regular@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docenteComun->usuario_id,
            'numero_empleado' => 'DOC-02',
            'puesto' => 'Profesor de Asignatura',
            'especialidad' => 'Matemáticas',
            'activo' => true,
        ]);

        // Alumno
        $this->alumnoUser = User::factory()->withRole($rolAlumno)->create([
            'email' => 'alumno.cita@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2026102222',
            'cuatrimestre' => 3,
            'grupo' => 'B',
            'saldo_puntos' => 5,
            'permite_visualizacion' => true,
        ]);
    }

    public function test_designated_orientador_can_access_management_dashboard(): void
    {
        $response = $this->actingAs($this->orientadorUser)->get(route('docente.citas.index'));

        $response->assertOk();
        $response->assertSee('Agenda de Citas de Orientación');
        $response->assertSee('Ana Martínez Rojas');
    }

    public function test_ordinary_docente_without_orientador_role_is_forbidden(): void
    {
        $response = $this->actingAs($this->docenteComun)->get(route('docente.citas.index'));

        $response->assertForbidden();
    }

    public function test_orientador_can_confirm_requested_appointment(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '10:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_SOLICITADA,
            'fecha_solicitud' => now(),
        ]);

        $response = $this->actingAs($this->orientadorUser)->patch(
            route('docente.citas.confirmar', $cita->cita_id),
            ['lugar_o_enlace' => 'Cubículo 3 - Edificio de Tutorías']
        );

        $response->assertRedirect(route('docente.citas.show', $cita->cita_id));
        $this->assertDatabaseHas('citas_orientacion', [
            'cita_id' => $cita->cita_id,
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'lugar_o_enlace' => 'Cubículo 3 - Edificio de Tutorías',
            'resuelto_por' => $this->orientadorUser->usuario_id,
        ]);
    }

    public function test_orientador_can_reject_requested_appointment_with_motive(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '10:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_SOLICITADA,
            'fecha_solicitud' => now(),
        ]);

        $response = $this->actingAs($this->orientadorUser)->patch(
            route('docente.citas.rechazar', $cita->cita_id),
            ['motivo' => 'Reunión colegiada imprevista a esa hora. Favor de reagendar.']
        );

        $response->assertRedirect(route('docente.citas.index'));
        $this->assertDatabaseHas('citas_orientacion', [
            'cita_id' => $cita->cita_id,
            'estado' => CitaOrientacion::ESTADO_RECHAZADA,
            'motivo_cancelacion_rechazo' => 'Reunión colegiada imprevista a esa hora. Favor de reagendar.',
            'resuelto_por' => $this->orientadorUser->usuario_id,
        ]);
    }

    public function test_orientador_can_finalize_confirmed_appointment(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '10:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now(),
        ]);

        $response = $this->actingAs($this->orientadorUser)->patch(
            route('docente.citas.finalizar', $cita->cita_id)
        );

        $response->assertRedirect(route('docente.citas.show', $cita->cita_id));
        $this->assertDatabaseHas('citas_orientacion', [
            'cita_id' => $cita->cita_id,
            'estado' => CitaOrientacion::ESTADO_FINALIZADA,
            'resuelto_por' => $this->orientadorUser->usuario_id,
        ]);
    }

    public function test_orientador_can_mark_no_show(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '10:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now(),
        ]);

        $response = $this->actingAs($this->orientadorUser)->patch(
            route('docente.citas.no-asistio', $cita->cita_id)
        );

        $response->assertRedirect(route('docente.citas.show', $cita->cita_id));
        $this->assertDatabaseHas('citas_orientacion', [
            'cita_id' => $cita->cita_id,
            'estado' => CitaOrientacion::ESTADO_NO_ASISTIO,
        ]);
    }

    public function test_orientador_can_add_and_delete_weekly_availability_block(): void
    {
        // 1. Agregar nuevo horario
        $response = $this->actingAs($this->orientadorUser)->post(
            route('docente.citas.disponibilidad.store'),
            [
                'dia_semana' => 2, // Martes
                'hora_inicio' => '10:00',
                'hora_fin' => '13:00',
                'duracion_cita_min' => 45,
                'modalidad' => 'AMBAS',
            ]
        );

        $response->assertRedirect(route('docente.citas.disponibilidad'));
        $this->assertDatabaseHas('citas_disponibilidades', [
            'profesional_id' => $this->profesional->profesional_id,
            'dia_semana' => 2,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '13:00:00',
            'duracion_cita_min' => 45,
        ]);

        $disp = CitaDisponibilidad::where('profesional_id', $this->profesional->profesional_id)
            ->where('dia_semana', 2)
            ->first();

        // 2. Eliminar horario
        $delResponse = $this->actingAs($this->orientadorUser)->delete(
            route('docente.citas.disponibilidad.destroy', $disp->disponibilidad_id)
        );

        $delResponse->assertRedirect(route('docente.citas.disponibilidad'));
        $this->assertDatabaseMissing('citas_disponibilidades', [
            'disponibilidad_id' => $disp->disponibilidad_id,
        ]);
    }
}

