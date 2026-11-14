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
use App\Services\Citas\CitaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitaConcurrenciaTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno1;
    private User $alumno2;
    private User $orientadorUser;
    private CitaProfesional $profesional;
    private CitaService $citaService;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $programa = ProgramaEducativo::factory()->create(['clave' => 'ITI', 'activo' => true]);

        $this->alumno1 = User::factory()->withRole($rolAlumno)->create(['estado' => 'ACTIVO']);
        Estudiante::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2026103331',
            'saldo_puntos' => 0,
        ]);

        $this->alumno2 = User::factory()->withRole($rolAlumno)->create(['estado' => 'ACTIVO']);
        Estudiante::create([
            'usuario_id' => $this->alumno2->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2026103332',
            'saldo_puntos' => 0,
        ]);

        $this->orientadorUser = User::factory()->withRole($rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $this->orientadorUser->usuario_id,
            'numero_empleado' => 'DOC-03',
            'puesto' => 'Orientador',
            'activo' => true,
        ]);

        $this->profesional = CitaProfesional::create([
            'usuario_id' => $this->orientadorUser->usuario_id,
            'cargo' => 'Orientador Psicológico',
            'modalidades_permitidas' => 'AMBAS',
            'permite_citas' => true,
            'activo' => true,
        ]);

        CitaDisponibilidad::create([
            'profesional_id' => $this->profesional->profesional_id,
            'dia_semana' => 1, // Lunes
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:00:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
            'activo' => true,
        ]);

        $this->citaService = app(CitaService::class);
    }

    public function test_double_booking_same_slot_is_strictly_prevented(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        // Alumno 1 reserva el horario de las 09:00
        $this->citaService->solicitarCita(
            alumnoUser: $this->alumno1,
            profesionalId: $this->profesional->profesional_id,
            fecha: $proximoLunes,
            horaInicio: '09:00',
            modalidad: 'PRESENCIAL',
            motivo: 'Primera reserva.'
        );

        // Alumno 2 intenta reservar el mismo horario
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Lo sentimos, este horario acaba de ser reservado.');

        $this->citaService->solicitarCita(
            alumnoUser: $this->alumno2,
            profesionalId: $this->profesional->profesional_id,
            fecha: $proximoLunes,
            horaInicio: '09:00',
            modalidad: 'PRESENCIAL',
            motivo: 'Intento concurrente.'
        );
    }

    public function test_student_cannot_have_more_than_two_pending_requests(): void
    {
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        // 1ª reserva
        $this->citaService->solicitarCita(
            alumnoUser: $this->alumno1,
            profesionalId: $this->profesional->profesional_id,
            fecha: $proximoLunes,
            horaInicio: '09:00',
            modalidad: 'PRESENCIAL'
        );

        // 2ª reserva en horario diferente
        $this->citaService->solicitarCita(
            alumnoUser: $this->alumno1,
            profesionalId: $this->profesional->profesional_id,
            fecha: $proximoLunes,
            horaInicio: '09:45',
            modalidad: 'PRESENCIAL'
        );

        // 3ª reserva debe ser rechazada para evitar acaparamiento
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tienes 2 citas en espera de confirmación.');

        $this->citaService->solicitarCita(
            alumnoUser: $this->alumno1,
            profesionalId: $this->profesional->profesional_id,
            fecha: $proximoLunes,
            horaInicio: '10:30',
            modalidad: 'PRESENCIAL'
        );
    }

    public function test_inactive_orientador_cannot_receive_bookings(): void
    {
        $this->profesional->update(['permite_citas' => false]);
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('El profesional seleccionado no está habilitado para recibir citas');

        $this->citaService->solicitarCita(
            alumnoUser: $this->alumno1,
            profesionalId: $this->profesional->profesional_id,
            fecha: $proximoLunes,
            horaInicio: '09:00',
            modalidad: 'PRESENCIAL'
        );
    }
}

