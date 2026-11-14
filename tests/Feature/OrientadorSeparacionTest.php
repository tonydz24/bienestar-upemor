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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrientadorSeparacionTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolDocente;
    private Rol $rolOrientador;
    private Rol $rolAlumno;
    private Rol $rolAdmin;
    private ProgramaEducativo $programa;

    private User $pureDocente;
    private User $pureOrientador;
    private User $multiRole;
    private User $alumnoUser;

    private CitaProfesional $profOrientador;
    private CitaProfesional $profMulti;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $this->rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $this->rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $this->rolOrientador = Rol::firstOrCreate(
            ['nombre' => 'Orientador'],
            ['descripcion' => 'Orientación estudiantil', 'rol_id' => 4]
        );

        $this->programa = ProgramaEducativo::factory()->create(['clave' => 'IID', 'activo' => true]);

        // 1. Pure Docente (docente académico)
        $this->pureDocente = User::factory()->withRole($this->rolDocente)->create([
            'email' => 'docente.puro@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->pureDocente->usuario_id,
            'numero_empleado' => 'DOC-001',
            'activo' => true,
        ]);

        // 2. Pure Orientador (solo orientador)
        $this->pureOrientador = User::factory()->withRole($this->rolOrientador)->create([
            'email' => 'orientador.puro@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->profOrientador = CitaProfesional::create([
            'usuario_id' => $this->pureOrientador->usuario_id,
            'cargo' => 'Orientador Institucional',
            'cedula_profesional' => 'PSI-12345',
            'lugar_atencion' => 'Consultorio 1',
            'modalidades_permitidas' => 'AMBAS',
            'permite_citas' => true,
            'activo' => true,
        ]);

        // 3. Multi-role (Docente + Orientador)
        $this->multiRole = User::factory()->withRole($this->rolDocente)->create([
            'email' => 'docente.orientador@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->multiRole->usuario_id,
            'numero_empleado' => 'DOC-002',
            'activo' => true,
        ]);
        $this->profMulti = CitaProfesional::create([
            'usuario_id' => $this->multiRole->usuario_id,
            'cargo' => 'Docente y Orientadora',
            'cedula_profesional' => 'PSI-67890',
            'lugar_atencion' => 'Consultorio 2',
            'modalidades_permitidas' => 'AMBAS',
            'permite_citas' => true,
            'activo' => true,
        ]);

        // 4. Alumno
        $this->alumnoUser = User::factory()->withRole($this->rolAlumno)->create([
            'email' => 'alumno.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => '20261001',
            'saldo_puntos' => 20,
        ]);
    }

    public function test_pure_orientador_can_access_citas_and_disponibilidad(): void
    {
        $response = $this->actingAs($this->pureOrientador)->get(route('docente.citas.index'));
        $response->assertOk();

        $responseDisp = $this->actingAs($this->pureOrientador)->get(route('docente.citas.disponibilidad'));
        $responseDisp->assertOk();
    }

    public function test_pure_orientador_cannot_access_docente_academico(): void
    {
        $response = $this->actingAs($this->pureOrientador)->get(route('docente.academico.index'));
        $response->assertForbidden();
    }

    public function test_pure_docente_cannot_access_citas_disponibilidad(): void
    {
        $response = $this->actingAs($this->pureDocente)->get(route('docente.citas.disponibilidad'));
        $response->assertForbidden();
    }

    public function test_multi_role_user_can_access_both_academico_and_citas(): void
    {
        $responseAcademico = $this->actingAs($this->multiRole)->get(route('docente.academico.index'));
        $responseAcademico->assertOk();

        $responseCitas = $this->actingAs($this->multiRole)->get(route('docente.citas.disponibilidad'));
        $responseCitas->assertOk();
    }

    public function test_cannot_delete_disponibilidad_with_active_appointments(): void
    {
        // 1. Crear bloque de disponibilidad para Martes de 09:00 a 13:00
        $disp = CitaDisponibilidad::create([
            'profesional_id' => $this->profOrientador->profesional_id,
            'dia_semana' => 2, // Martes
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:00:00',
            'activo' => true,
        ]);

        // 2. Crear una cita confirmada en el próximo martes dentro de ese horario
        $proximoMartes = now()->next(2)->toDateString();
        CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profOrientador->profesional_id,
            'fecha_cita' => $proximoMartes,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '10:50:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now(),
        ]);

        // 3. Intentar eliminar el bloque de disponibilidad
        $response = $this->actingAs($this->pureOrientador)->delete(
            route('docente.citas.disponibilidad.destroy', $disp->disponibilidad_id)
        );

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('citas_disponibilidades', [
            'disponibilidad_id' => $disp->disponibilidad_id,
        ]);
    }

    public function test_can_delete_disponibilidad_without_active_appointments(): void
    {
        $disp = CitaDisponibilidad::create([
            'profesional_id' => $this->profOrientador->profesional_id,
            'dia_semana' => 5, // Viernes
            'hora_inicio' => '10:00:00',
            'hora_fin' => '12:00:00',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->pureOrientador)->delete(
            route('docente.citas.disponibilidad.destroy', $disp->disponibilidad_id)
        );

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('citas_disponibilidades', [
            'disponibilidad_id' => $disp->disponibilidad_id,
        ]);
    }
}
