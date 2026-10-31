<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSolicitudesTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Rol $rolAlumno;
    private ProgramaEducativo $programa;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $this->rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $this->rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $this->programa = ProgramaEducativo::factory()->create([
            'clave' => 'IFI',
            'nombre' => 'Ingeniería Financiera',
        ]);
        $this->admin = User::factory()->withRole($this->rolAdmin)->create();
    }

    private function crearAlumno(string $estado = 'PENDIENTE', array $usuario = [], array $perfil = []): User
    {
        $alumno = User::factory()->withRole($this->rolAlumno)->create(array_merge(['estado' => $estado], $usuario));

        Estudiante::create(array_merge([
            'usuario_id' => $alumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => fake()->unique()->numerify('2023####'),
        ], $perfil));

        return $alumno;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $alumno = $this->crearAlumno();

        $this->get(route('admin.solicitudes.index'))->assertRedirectToRoute('login');
        $this->patch(route('admin.solicitudes.aprobar', $alumno->usuario_id))->assertRedirectToRoute('login');
        $this->patch(route('admin.solicitudes.rechazar', $alumno->usuario_id))->assertRedirectToRoute('login');
        $this->assertSame('PENDIENTE', $alumno->fresh()->estado);
    }

    public function test_non_admin_users_receive_403_and_cannot_change_state(): void
    {
        $pendiente = $this->crearAlumno();
        $alumnoActivo = $this->crearAlumno('ACTIVO');
        $docente = User::factory()->withRole($this->rolDocente)->create();

        foreach ([$alumnoActivo, $docente] as $usuario) {
            $this->actingAs($usuario)->get(route('admin.solicitudes.index'))->assertForbidden();
            $this->actingAs($usuario)->patch(route('admin.solicitudes.aprobar', $pendiente->usuario_id))->assertForbidden();
            $this->actingAs($usuario)->patch(route('admin.solicitudes.rechazar', $pendiente->usuario_id))->assertForbidden();
        }

        $this->assertSame('PENDIENTE', $pendiente->fresh()->estado);
    }

    public function test_admin_sees_pending_requests_with_real_data(): void
    {
        $this->crearAlumno('PENDIENTE', ['nombre' => 'Beatriz', 'apellido_paterno' => 'Núñez', 'email' => 'beatriz@upemor.edu.mx'], ['matricula' => '20230001']);
        $this->crearAlumno('ACTIVO', ['nombre' => 'Aprobado Previo']);

        $response = $this->actingAs($this->admin)->get(route('admin.solicitudes.index'));

        $response->assertOk();
        $response->assertSee('Beatriz Núñez');
        $response->assertSee('20230001');
        $response->assertSee('beatriz@upemor.edu.mx');
        $response->assertSee('Ingeniería Financiera');
        $response->assertSee('Pendiente');
        $response->assertSee('Aprobar');
        $response->assertSee('Rechazar');
        $response->assertDontSee('Aprobado Previo');
    }

    public function test_list_only_includes_students_and_excludes_other_roles(): void
    {
        $docente = User::factory()->withRole($this->rolDocente)->create(['nombre' => 'Docente Pendiente', 'estado' => 'PENDIENTE']);

        $this->actingAs($this->admin)->get(route('admin.solicitudes.index'))
            ->assertOk()
            ->assertDontSee($docente->nombre);
    }

    public function test_status_filter_shows_matching_requests(): void
    {
        $this->crearAlumno('PENDIENTE', ['nombre' => 'Pendiente Uno']);
        $this->crearAlumno('RECHAZADO', ['nombre' => 'Rechazado Uno']);
        $this->crearAlumno('ACTIVO', ['nombre' => 'Activo Uno']);

        $this->actingAs($this->admin)->get(route('admin.solicitudes.index', ['estado' => 'RECHAZADO']))
            ->assertSee('Rechazado Uno')
            ->assertDontSee('Pendiente Uno')
            ->assertDontSee('Activo Uno');

        $this->actingAs($this->admin)->get(route('admin.solicitudes.index', ['estado' => 'ACTIVO']))
            ->assertSee('Activo Uno')
            ->assertDontSee('Rechazado Uno');
    }

    public function test_invalid_status_filter_falls_back_to_pending(): void
    {
        $this->crearAlumno('PENDIENTE', ['nombre' => 'Pendiente Visible']);

        $this->actingAs($this->admin)->get(route('admin.solicitudes.index', ['estado' => 'BLOQUEADO']))
            ->assertOk()
            ->assertSee('Pendiente Visible');
    }

    public function test_search_by_name_and_matricula(): void
    {
        $this->crearAlumno('PENDIENTE', ['nombre' => 'Beatriz'], ['matricula' => '20230001']);
        $this->crearAlumno('PENDIENTE', ['nombre' => 'Carlos'], ['matricula' => '20239999']);

        $this->actingAs($this->admin)->get(route('admin.solicitudes.index', ['buscar' => 'Beatriz']))
            ->assertSee('Beatriz')
            ->assertDontSee('Carlos');

        $this->actingAs($this->admin)->get(route('admin.solicitudes.index', ['buscar' => '20239999']))
            ->assertSee('Carlos')
            ->assertDontSee('Beatriz');
    }

    public function test_admin_can_approve_a_pending_student(): void
    {
        $alumno = $this->crearAlumno();

        $response = $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', $alumno->usuario_id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('ACTIVO', $alumno->fresh()->estado);
    }

    public function test_approved_student_can_log_in(): void
    {
        $alumno = $this->crearAlumno();
        $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', $alumno->usuario_id));
        $this->post(route('logout'));

        $this->post(route('login.post'), [
            'email' => $alumno->email,
            'password' => 'testing-only-password',
        ])->assertRedirectToRoute('alumno.dashboard');
    }

    public function test_admin_can_reject_a_pending_student_who_then_cannot_log_in(): void
    {
        $alumno = $this->crearAlumno();

        $response = $this->actingAs($this->admin)->patch(route('admin.solicitudes.rechazar', $alumno->usuario_id));

        $response->assertSessionHas('success');
        $this->assertSame('RECHAZADO', $alumno->fresh()->estado);

        $this->post(route('logout'));
        $this->post(route('login.post'), [
            'email' => $alumno->email,
            'password' => 'testing-only-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_repeated_actions_do_not_change_an_already_resolved_request(): void
    {
        $alumno = $this->crearAlumno();

        $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', $alumno->usuario_id));
        $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', $alumno->usuario_id))
            ->assertSessionHas('error');
        $this->actingAs($this->admin)->patch(route('admin.solicitudes.rechazar', $alumno->usuario_id))
            ->assertSessionHas('error');

        $this->assertSame('ACTIVO', $alumno->fresh()->estado);
    }

    public function test_rejected_request_cannot_be_approved_through_this_module(): void
    {
        $alumno = $this->crearAlumno('RECHAZADO');

        $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', $alumno->usuario_id))
            ->assertSessionHas('error');

        $this->assertSame('RECHAZADO', $alumno->fresh()->estado);
    }

    public function test_blocked_and_inactive_accounts_cannot_be_modified(): void
    {
        $bloqueado = $this->crearAlumno('BLOQUEADO');
        $inactivo = $this->crearAlumno('INACTIVO');

        foreach ([$bloqueado, $inactivo] as $alumno) {
            $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', $alumno->usuario_id))
                ->assertSessionHas('error');
        }

        $this->assertSame('BLOQUEADO', $bloqueado->fresh()->estado);
        $this->assertSame('INACTIVO', $inactivo->fresh()->estado);
    }

    public function test_non_student_pending_accounts_cannot_be_approved(): void
    {
        $docente = User::factory()->withRole($this->rolDocente)->create(['estado' => 'PENDIENTE']);
        $adminPendiente = User::factory()->withRole($this->rolAdmin)->create(['estado' => 'PENDIENTE']);

        foreach ([$docente, $adminPendiente] as $usuario) {
            $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', $usuario->usuario_id))
                ->assertSessionHas('error');
            $this->assertSame('PENDIENTE', $usuario->fresh()->estado);
        }
    }

    public function test_student_without_profile_cannot_be_approved(): void
    {
        $sinPerfil = User::factory()->withRole($this->rolAlumno)->create(['estado' => 'PENDIENTE']);

        $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', $sinPerfil->usuario_id))
            ->assertSessionHas('error');

        $this->assertSame('PENDIENTE', $sinPerfil->fresh()->estado);
    }

    public function test_admin_cannot_change_their_own_account(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.solicitudes.rechazar', $this->admin->usuario_id))
            ->assertSessionHas('error');

        $this->assertSame('ACTIVO', $this->admin->fresh()->estado);
    }

    public function test_nonexistent_request_returns_an_error_without_changes(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.solicitudes.aprobar', 99999))
            ->assertSessionHas('error');
    }

    public function test_state_changes_are_not_allowed_through_get(): void
    {
        $alumno = $this->crearAlumno();

        $this->actingAs($this->admin)->get('/admin/solicitudes/'.$alumno->usuario_id.'/aprobar')->assertStatus(405);
        $this->actingAs($this->admin)->get('/admin/solicitudes/'.$alumno->usuario_id.'/rechazar')->assertStatus(405);

        $this->assertSame('PENDIENTE', $alumno->fresh()->estado);
    }
}
