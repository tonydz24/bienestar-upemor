<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Policies\EvaluacionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluacionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;
    private Estudiante $estudiante;
    private User $otroAlumno;
    private Estudiante $otroEstudiante;
    private User $docente;
    private User $admin;
    private EvaluacionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $programa = ProgramaEducativo::factory()->create([
            'clave' => 'IID',
            'activo' => true,
        ]);

        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'alumno1@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023101111',
            'cuatrimestre' => 4,
            'permite_visualizacion' => false,
        ]);

        $this->otroAlumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'alumno2@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->otroEstudiante = Estudiante::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023102222',
            'cuatrimestre' => 4,
            'permite_visualizacion' => true,
        ]);

        $this->docente = User::factory()->withRole($rolDocente)->create([
            'email' => 'docente@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'DOC2002',
            'puesto' => 'Profesor Orientador',
            'activo' => true,
        ]);

        $this->admin = User::factory()->withRole($rolAdmin)->create([
            'email' => 'admin@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->policy = new EvaluacionPolicy();
    }

    public function test_active_student_can_view_any_evaluaciones(): void
    {
        $response = $this->policy->viewAny($this->alumno);
        $this->assertTrue($response->allowed());
    }

    public function test_inactive_student_cannot_view_evaluaciones(): void
    {
        $this->alumno->update(['estado' => 'INACTIVO']);
        $response = $this->policy->viewAny($this->alumno);
        $this->assertFalse($response->allowed());
    }

    public function test_docente_and_admin_cannot_view_evaluaciones_list(): void
    {
        $responseDocente = $this->policy->viewAny($this->docente);
        $this->assertFalse($responseDocente->allowed());

        $responseAdmin = $this->policy->viewAny($this->admin);
        $this->assertFalse($responseAdmin->allowed());
    }

    public function test_student_can_view_own_attempt(): void
    {
        $version = VersionInstrumento::factory()->create();
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now(),
        ]);

        $response = $this->policy->view($this->alumno, $intento);
        $this->assertTrue($response->allowed());
    }

    public function test_student_cannot_view_other_students_attempt(): void
    {
        $version = VersionInstrumento::factory()->create();
        $intentoAjeno = EvaluacionIntento::create([
            'usuario_id' => $this->otroEstudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now(),
        ]);

        $response = $this->policy->view($this->alumno, $intentoAjeno);
        $this->assertFalse($response->allowed());
    }

    public function test_docente_cannot_view_student_attempt_even_if_permite_visualizacion_is_true_without_verified_care(): void
    {
        // Requisito ético: la sola existencia de 'permite_visualizacion' en true no otorga
        // acceso automático indiscriminado a los docentes.
        $version = VersionInstrumento::factory()->create();
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->otroEstudiante->usuario_id, // tiene permite_visualizacion = true
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now(),
        ]);

        $response = $this->policy->view($this->docente, $intento);
        $this->assertFalse($response->allowed());
        $this->assertEquals(
            'No tienes autorización para consultar esta evaluación psicológica confidencial.',
            $response->message()
        );
    }

    public function test_admin_cannot_view_student_attempt(): void
    {
        $version = VersionInstrumento::factory()->create();
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now(),
        ]);

        $response = $this->policy->view($this->admin, $intento);
        $this->assertFalse($response->allowed());
    }

    public function test_student_can_update_in_progress_attempt(): void
    {
        $version = VersionInstrumento::factory()->create();
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $response = $this->policy->update($this->alumno, $intento);
        $this->assertTrue($response->allowed());
    }

    public function test_student_cannot_update_finished_attempt(): void
    {
        $version = VersionInstrumento::factory()->create();
        $intentoFinalizado = EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 12,
            'fecha_inicio' => now()->subMinutes(10),
            'fecha_finalizacion' => now(),
        ]);

        $response = $this->policy->update($this->alumno, $intentoFinalizado);
        $this->assertFalse($response->allowed());
        $this->assertEquals(
            'Esta evaluación ya ha sido finalizada y sus respuestas no pueden modificarse.',
            $response->message()
        );
    }

    public function test_student_cannot_delete_attempt(): void
    {
        $version = VersionInstrumento::factory()->create();
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now(),
        ]);

        $response = $this->policy->delete($this->alumno, $intento);
        $this->assertFalse($response->allowed());
        $this->assertEquals(
            'Los registros de evaluaciones psicológicas forman parte del historial de bienestar y no pueden eliminarse.',
            $response->message()
        );
    }
}

