<?php

namespace Tests\Feature;

use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private array $validData;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $programa = ProgramaEducativo::factory()->create([
            'programa_id' => 1,
            'nombre' => 'Ingeniería Financiera',
            'clave' => 'IFI',
            'activo' => true,
        ]);

        $this->validData = [
            'nombre' => 'Ana',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'López',
            'email' => 'ana.garcia@upemor.edu.mx',
            'matricula' => '20231001',
            'programa_id' => $programa->programa_id,
            'cuatrimestre' => 3,
            'password' => 'Contraseña123!',
            'password_confirmation' => 'Contraseña123!',
        ];
    }

    public function test_register_form_is_displayed(): void
    {
        $response = $this->get(route('register'));
        $response->assertOk();
        $response->assertSee('Regístrate');
    }

    public function test_alumno_can_register_with_valid_data_and_is_pending(): void
    {
        $response = $this->post(route('register.store'), $this->validData);

        $response->assertRedirectToRoute('login');
        $response->assertSessionHas('success', 'Tu solicitud fue recibida. Serás notificado cuando un administrador la apruebe.');

        $user = User::where('email', 'ana.garcia@upemor.edu.mx')->first();
        $this->assertNotNull($user);
        $this->assertSame('PENDIENTE', $user->estado);
        $this->assertSame(3, $user->rol_id);

        $this->assertDatabaseHas('estudiantes', [
            'usuario_id' => $user->usuario_id,
            'matricula' => '20231001',
            'permite_visualizacion' => false,
        ]);
    }

    public function test_duplicate_matricula_is_rejected(): void
    {
        $this->post(route('register.store'), $this->validData);
        $this->validData['email'] = 'otro@upemor.edu.mx';

        $response = $this->post(route('register.store'), $this->validData);

        $response->assertSessionHasErrors('matricula');
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->post(route('register.store'), $this->validData);
        $this->validData['matricula'] = '20231002';

        $response = $this->post(route('register.store'), $this->validData);

        $response->assertSessionHasErrors('email');
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->validData['password'] = '123';
        $this->validData['password_confirmation'] = '123';

        $response = $this->post(route('register.store'), $this->validData);

        $response->assertSessionHasErrors('password');
    }

    public function test_non_institutional_email_is_rejected(): void
    {
        $this->validData['email'] = 'ana.garcia@gmail.com';

        $response = $this->post(route('register.store'), $this->validData);

        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_program_is_rejected(): void
    {
        $programaInactivo = ProgramaEducativo::factory()->create([
            'programa_id' => 2,
            'activo' => false,
        ]);
        $this->validData['programa_id'] = $programaInactivo->programa_id;

        $response = $this->post(route('register.store'), $this->validData);

        $response->assertSessionHasErrors('programa_id');
    }

    public function test_role_cannot_be_manipulated(): void
    {
        $data = $this->validData;
        $data['rol_id'] = 1;

        $response = $this->post(route('register.store'), $data);

        $user = User::where('email', 'ana.garcia@upemor.edu.mx')->first();
        $this->assertNotNull($user);
        $this->assertSame(3, $user->rol_id);
    }

    public function test_user_and_student_are_created_together_in_transaction(): void
    {
        $this->post(route('register.store'), $this->validData);

        $user = User::where('email', 'ana.garcia@upemor.edu.mx')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->estudiante);
        $this->assertSame($user->usuario_id, $user->estudiante->usuario_id);
    }

    public function test_pending_account_cannot_log_in(): void
    {
        $this->post(route('register.store'), $this->validData);

        $response = $this->post(route('login.post'), [
            'email' => 'ana.garcia@upemor.edu.mx',
            'password' => 'Contraseña123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_register_link_is_visible_in_login(): void
    {
        $response = $this->get(route('login'));
        $response->assertSee(route('register'));
    }

    public function test_only_six_allowed_carreras_are_visible_in_form(): void
    {
        ProgramaEducativo::factory()->create(['clave' => 'ITI', 'nombre' => 'Histórico ITI', 'activo' => true]);
        ProgramaEducativo::factory()->create(['clave' => 'LAD', 'nombre' => 'Licenciatura en Administración', 'activo' => true]);

        $response = $this->get(route('register'));
        $response->assertOk();
        $response->assertSee('Licenciatura en Administración');
        $response->assertSee('Ingeniería Financiera');
        $response->assertDontSee('Histórico ITI');
    }

    public function test_historical_program_is_rejected_by_server(): void
    {
        $historico = ProgramaEducativo::factory()->create(['clave' => 'ITI', 'nombre' => 'Histórico ITI', 'activo' => true]);
        $this->validData['programa_id'] = $historico->programa_id;

        $response = $this->post(route('register.store'), $this->validData);
        $response->assertSessionHasErrors('programa_id');
    }

    public function test_nonexistent_program_is_rejected(): void
    {
        $this->validData['programa_id'] = 9999;

        $response = $this->post(route('register.store'), $this->validData);
        $response->assertSessionHasErrors('programa_id');
    }
}
