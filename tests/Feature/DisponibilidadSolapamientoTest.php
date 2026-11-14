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

class DisponibilidadSolapamientoTest extends TestCase
{
    use RefreshDatabase;

    private User $orientadorUser;
    private CitaProfesional $profesional;
    private User $alumnoUser;

    protected function setUp(): void
    {
        parent::setUp();

        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $this->orientadorUser = User::factory()->withRole($rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $this->orientadorUser->usuario_id,
            'numero_empleado' => 'DOC-OR-50',
            'activo' => true,
        ]);
        $this->profesional = CitaProfesional::create([
            'usuario_id' => $this->orientadorUser->usuario_id,
            'cargo' => 'Orientadora Institucional',
            'activo' => true,
            'permite_citas' => true,
        ]);

        $programa = ProgramaEducativo::factory()->create(['clave' => 'IID', 'activo' => true]);
        $this->alumnoUser = User::factory()->withRole($rolAlumno)->create(['estado' => 'ACTIVO']);
        Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2026107771',
            'saldo_puntos' => 0,
        ]);
    }

    public function test_multiple_non_overlapping_blocks_in_same_day_are_allowed(): void
    {
        // Bloque 1: Martes (2) 09:00 a 12:00
        $resp1 = $this->actingAs($this->orientadorUser)->post(route('docente.citas.disponibilidad.store'), [
            'dia_semana' => 2,
            'hora_inicio' => '09:00',
            'hora_fin' => '12:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
        ]);
        $resp1->assertRedirect(route('docente.citas.disponibilidad'));
        $resp1->assertSessionHas('success');

        // Bloque 2: Martes (2) 14:00 a 17:00 (No se superpone)
        $resp2 = $this->actingAs($this->orientadorUser)->post(route('docente.citas.disponibilidad.store'), [
            'dia_semana' => 2,
            'hora_inicio' => '14:00',
            'hora_fin' => '17:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
        ]);
        $resp2->assertRedirect(route('docente.citas.disponibilidad'));
        $resp2->assertSessionHas('success');

        $this->assertDatabaseCount('citas_disponibilidades', 2);
    }

    public function test_overlapping_block_detects_conflict_and_requests_confirmation(): void
    {
        // Bloque existente: Martes de 09:00 a 13:00
        $dispOriginal = CitaDisponibilidad::create([
            'profesional_id' => $this->profesional->profesional_id,
            'dia_semana' => 2,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:00:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
            'activo' => true,
        ]);

        // Intento de agregar Martes de 09:00 a 17:00 (Superpuesto)
        $response = $this->actingAs($this->orientadorUser)->post(route('docente.citas.disponibilidad.store'), [
            'dia_semana' => 2,
            'hora_inicio' => '09:00',
            'hora_fin' => '17:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
        ]);

        $response->assertSessionHas('alerta_solapamiento');
        // No debe duplicar en base de datos
        $this->assertDatabaseCount('citas_disponibilidades', 1);
        $this->assertDatabaseHas('citas_disponibilidades', [
            'disponibilidad_id' => $dispOriginal->disponibilidad_id,
            'hora_fin' => '13:00:00',
        ]);
    }

    public function test_overlapping_block_with_confirmation_replaces_old_block(): void
    {
        // Bloque existente: Martes de 09:00 a 13:00
        $dispOriginal = CitaDisponibilidad::create([
            'profesional_id' => $this->profesional->profesional_id,
            'dia_semana' => 2,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:00:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
            'activo' => true,
        ]);

        // Envío confirmando el reemplazo
        $response = $this->actingAs($this->orientadorUser)->post(route('docente.citas.disponibilidad.store'), [
            'dia_semana' => 2,
            'hora_inicio' => '09:00',
            'hora_fin' => '17:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
            'confirmar_reemplazo' => 1,
            'reemplazar_id' => $dispOriginal->disponibilidad_id,
        ]);

        $response->assertRedirect(route('docente.citas.disponibilidad'));
        $response->assertSessionHas('success');

        // El bloque viejo fue eliminado y el nuevo persiste
        $this->assertDatabaseMissing('citas_disponibilidades', [
            'disponibilidad_id' => $dispOriginal->disponibilidad_id,
        ]);
        $this->assertDatabaseHas('citas_disponibilidades', [
            'profesional_id' => $this->profesional->profesional_id,
            'dia_semana' => 2,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '17:00:00',
        ]);
    }

    public function test_replacement_is_blocked_if_old_block_has_active_appointments(): void
    {
        // Bloque existente: Lunes de 09:00 a 13:00
        $dispOriginal = CitaDisponibilidad::create([
            'profesional_id' => $this->profesional->profesional_id,
            'dia_semana' => 1, // Lunes
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:00:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
            'activo' => true,
        ]);

        // Alumno tiene una cita agendada para el próximo lunes a las 10:00
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();
        CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => $proximoLunes,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '10:45:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now(),
        ]);

        // Orientador intenta reemplazar el bloque
        $response = $this->actingAs($this->orientadorUser)->post(route('docente.citas.disponibilidad.store'), [
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '14:00',
            'duracion_cita_min' => 45,
            'modalidad' => 'AMBAS',
            'confirmar_reemplazo' => 1,
            'reemplazar_id' => $dispOriginal->disponibilidad_id,
        ]);

        // Debe ser bloqueado por protección a las citas activas de los estudiantes
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('citas_disponibilidades', [
            'disponibilidad_id' => $dispOriginal->disponibilidad_id,
        ]);
        $this->assertDatabaseHas('citas_orientacion', [
            'profesional_id' => $this->profesional->profesional_id,
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
        ]);
    }
}
