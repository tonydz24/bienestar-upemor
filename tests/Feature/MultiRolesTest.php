<?php

namespace Tests\Feature;

use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiRolesTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Rol $rolAlumno;
    private ProgramaEducativo $programa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $this->rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $this->rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $this->programa = ProgramaEducativo::factory()->create(['clave' => 'IID', 'activo' => true]);
    }

    public function test_user_with_primary_role_matches_has_role(): void
    {
        $docente = User::factory()->withRole($this->rolDocente)->create(['estado' => 'ACTIVO']);

        $this->assertTrue($docente->hasRole('Docente'));
        $this->assertFalse($docente->hasRole('Administrador'));
        $this->assertFalse($docente->hasRole('Alumno'));
    }

    public function test_docente_assigned_as_orientador_has_orientador_capacity(): void
    {
        $docente = User::factory()->withRole($this->rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $docente->usuario_id,
            'numero_empleado' => 'DOC-OR-01',
            'activo' => true,
        ]);
        CitaProfesional::create([
            'usuario_id' => $docente->usuario_id,
            'cargo' => 'Orientadora Institucional',
            'activo' => true,
            'permite_citas' => true,
        ]);

        $this->assertTrue($docente->esOrientador());
        $this->assertTrue($docente->hasRole('Orientador'));
        $this->assertTrue($docente->hasRole('Docente'));

        // Puede acceder a la agenda de citas
        $response = $this->actingAs($docente)->get(route('docente.citas.index'));
        $response->assertOk();
    }

    public function test_user_with_secondary_role_via_pivot_table_is_authorized(): void
    {
        $user = User::factory()->withRole($this->rolDocente)->create(['estado' => 'ACTIVO']);

        // Le asignamos rol secundario de Administrador mediante la tabla pivote
        $user->roles()->attach($this->rolAdmin->rol_id);

        $this->assertTrue($user->hasRole('Docente'));
        $this->assertTrue($user->hasRole('Administrador'));
    }

    public function test_ordinary_docente_without_orientador_profile_cannot_access_citas_panel(): void
    {
        $docenteComun = User::factory()->withRole($this->rolDocente)->create(['estado' => 'ACTIVO']);
        Docente::create([
            'usuario_id' => $docenteComun->usuario_id,
            'numero_empleado' => 'DOC-REG-01',
            'activo' => true,
        ]);

        $this->assertFalse($docenteComun->esOrientador());

        $response = $this->actingAs($docenteComun)->get(route('docente.citas.index'));
        $response->assertForbidden();
    }

    public function test_teachers_and_admins_have_no_automatic_access_to_emotional_logbooks(): void
    {
        $alumno = User::factory()->withRole($this->rolAlumno)->create(['estado' => 'ACTIVO']);
        Estudiante::create([
            'usuario_id' => $alumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => '2026109999',
            'saldo_puntos' => 0,
        ]);

        $docente = User::factory()->withRole($this->rolDocente)->create(['estado' => 'ACTIVO']);
        $admin = User::factory()->withRole($this->rolAdmin)->create(['estado' => 'ACTIVO']);

        // Docente intenta ver la bitácora
        $respDocente = $this->actingAs($docente)->get(route('alumno.bitacora.index'));
        $respDocente->assertForbidden();

        // Admin intenta ver la bitácora
        $respAdmin = $this->actingAs($admin)->get(route('alumno.bitacora.index'));
        $respAdmin->assertForbidden();
    }
}

