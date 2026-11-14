<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\BitacoraEmocional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicoDocenteTest extends TestCase
{
    use RefreshDatabase;

    private User $docente1;
    private User $docente2;
    private User $alumnoUser;
    private Estudiante $estudiante;
    private PeriodoAcademico $periodo;
    private Asignatura $asignatura;
    private Grupo $grupo;
    private DocenteAsignacion $asignacion1;

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

        // Docente 1
        $this->docente1 = User::factory()->withRole($rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $this->docente1->usuario_id,
            'numero_empleado' => 'DOC-01',
            'activo' => true,
        ]);
        $this->asignacion1 = DocenteAsignacion::create([
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
        ]);

        // Docente 2 (otro profesor)
        $this->docente2 = User::factory()->withRole($rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $this->docente2->usuario_id,
            'numero_empleado' => 'DOC-02',
            'activo' => true,
        ]);

        // Alumno inscrito
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
            'saldo_puntos' => 20,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'grupo_id' => $this->grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);
    }

    public function test_docente_can_access_academico_index_and_see_assigned_subjects(): void
    {
        $response = $this->actingAs($this->docente1)->get(route('docente.academico.index'));

        $response->assertOk();
        $response->assertSee('Desarrollo Web');
        $response->assertSee('IID-501');
        $response->assertSee('5°');
    }

    public function test_docente_can_view_enrolled_students_in_assigned_group(): void
    {
        $response = $this->actingAs($this->docente1)->get(
            route('docente.academico.grupo', $this->asignacion1->asignacion_id)
        );

        $response->assertOk();
        $response->assertSee('Carlos Mendoza Ríos');
        $response->assertSee('2026105555');
    }

    public function test_docente_cannot_view_students_of_group_assigned_to_another_teacher(): void
    {
        // Docente 2 intenta ver la asignación del Docente 1
        $response = $this->actingAs($this->docente2)->get(
            route('docente.academico.grupo', $this->asignacion1->asignacion_id)
        );

        $response->assertForbidden();
    }

    public function test_student_cannot_access_docente_academico(): void
    {
        $response = $this->actingAs($this->alumnoUser)->get(route('docente.academico.index'));

        $response->assertForbidden();
    }

    public function test_academic_views_do_not_expose_emotional_bitacora_or_psychological_data(): void
    {
        // Creamos una bitácora emocional íntima para el estudiante
        $emocion = Emocion::create([
            'nombre' => 'Tristeza',
            'slug' => 'tristeza',
            'descripcion' => 'Sensación de decaimiento',
            'activo' => true,
        ]);
        BitacoraEmocional::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 4,
            'motivo' => 'Confesión privada y confidencial del estudiante.',
        ]);

        $response = $this->actingAs($this->docente1)->get(
            route('docente.academico.grupo', $this->asignacion1->asignacion_id)
        );

        $response->assertOk();
        $response->assertSee('Carlos Mendoza Ríos');
        $response->assertDontSee('Confesión privada y confidencial del estudiante.');
        $response->assertDontSee('Tristeza');
    }
}
