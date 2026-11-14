<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected PeriodoAcademico $periodo;
    protected ProgramaEducativo $programa;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 4, 'nombre' => 'Administrador']);
        $this->admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'Institucional',
            'apellido_materno' => 'UPEMOR',
            'email' => 'admin.grupos@upemor.edu.mx',
            'password' => bcrypt('AdminPass123!'),
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-20',
            'activo' => true,
        ]);

        $this->programa = ProgramaEducativo::create([
            'clave' => 'ITI',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);
    }

    public function test_admin_can_view_grupos_index(): void
    {
        $this->actingAs($this->admin);

        Grupo::create([
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $response = $this->get(route('admin.grupos.index'));
        $response->assertOk();
        $response->assertSee('Catálogo de Grupos Académicos');
        $response->assertSee('Ingeniería en Tecnologías de la Información');
    }

    public function test_admin_can_create_a_new_grupo(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.grupos.store'), [
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'B',
            'turno' => 'MATUTINO',
        ]);

        $response->assertRedirect(route('admin.grupos.index', ['periodo_id' => $this->periodo->periodo_id]));
        $this->assertDatabaseHas('grupos', [
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'B',
        ]);
    }

    public function test_admin_cannot_create_duplicate_grupo(): void
    {
        $this->actingAs($this->admin);

        Grupo::create([
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $response = $this->post(route('admin.grupos.store'), [
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_admin_can_toggle_grupo_status(): void
    {
        $this->actingAs($this->admin);

        $grupo = Grupo::create([
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'C',
            'turno' => 'VESPERTINO',
            'activo' => true,
        ]);

        $response = $this->patch(route('admin.grupos.toggle-activo', $grupo->grupo_id));
        $response->assertRedirect();

        $grupo->refresh();
        $this->assertFalse($grupo->activo);
    }
}
