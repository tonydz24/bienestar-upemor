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
            'matricula' => '2023100101',
            'programa_id' => $programa->programa_id,
            'cuatrimestre' => 3,
            'grupo' => 'A',
            'password' => 'Contraseña123!',
            'password_confirmation' => 'Contraseña123!',
        ];
    }

    public function test_register_form_is_displayed(): void
    {
        $response = $this->get(route('register'));
        $response->assertOk();
        $response->assertSee('Regístrate');
        $response->assertDontSee('No aplica');
        $response->assertDontSee('Debe contener exactamente 10 caracteres alfanuméricos');
        $response->assertSee('placeholder="ADMO230179"', false);
        $response->assertSee('Cuatrimestre 1');
        $response->assertSee('Grupo A');
        $response->assertSee('Grupo B');
        $response->assertSee('Grupo C');
        $response->assertSee('Grupo D');
        $response->assertSee('Grupo E');
        $response->assertSee('Grupo F');
        $response->assertSee('togglePassword');
        $response->assertSee('togglePasswordConfirm');
    }

    public function test_alumno_can_register_with_valid_data_and_is_pending(): void
    {
        $response = $this->post(route('register.store'), $this->validData);

        $response->assertRedirectToRoute('login');
        $response->assertSessionHas('success', 'Tu solicitud de registro fue recibida. Un administrador revisará tu información para activar tu cuenta.');

        $user = User::where('email', 'ana.garcia@upemor.edu.mx')->first();
        $this->assertNotNull($user);
        $this->assertSame('PENDIENTE', $user->estado);
        $this->assertSame(3, $user->rol_id);

        $this->assertDatabaseHas('estudiantes', [
            'usuario_id' => $user->usuario_id,
            'matricula' => '20231001',
            'matricula' => '2023100101',
            'cuatrimestre' => 3,
            'grupo' => 'A',
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
        $this->validData['matricula'] = '2023100202';

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
        $this->assertSame('A', $user->estudiante->grupo);
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

    public function test_registration_with_partially_uppercase_institutional_email_is_normalized_and_accepted(): void
    {
        $this->validData['email'] = 'ANA.GARCIA@UPEMOR.EDU.MX';
        $this->validData['matricula'] = '20239999';
        $this->validData['matricula'] = '2023999901';

        $response = $this->post(route('register.store'), $this->validData);

        $response->assertRedirectToRoute('login');
        $this->assertDatabaseHas('users', [
            'email' => 'ana.garcia@upemor.edu.mx',
            'estado' => 'PENDIENTE',
        ]);
    }

    public function test_registration_endpoint_has_rate_limiting(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $data = $this->validData;
            $data['email'] = "alumno{$i}@upemor.edu.mx";
            $data['matricula'] = "MATR{$i}";
            $data['matricula'] = sprintf('MATR%06d', $i);
            $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.50'])
                ->post(route('register.store'), $data);
        }

        $blockedData = $this->validData;
        $blockedData['email'] = 'alumno_bloqueado@upemor.edu.mx';
        $blockedData['matricula'] = 'MATR_BLOCKED';
        $blockedData['matricula'] = 'MATRBLCK01';

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.50'])
            ->post(route('register.store'), $blockedData);

        $response->assertStatus(429);
    }

    public function test_full_lifecycle_from_registration_to_approval_and_login(): void
    {
        $adminRole = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $admin = User::factory()->withRole($adminRole)->create([
            'email' => 'admin.evaluador@upemor.edu.mx',
            'password' => 'AdminPass1234!',
            'estado' => 'ACTIVO',
        ]);

        // 1. Alumno llena formulario de registro
        $studentData = $this->validData;
        $studentData['email'] = 'NUEVO.ALUMNO@UPEMOR.EDU.MX';
        $studentData['matricula'] = '20261100';
        $studentData['matricula'] = '2026110001';
        $studentData['grupo'] = 'b';
        $studentData['password'] = 'ContraseñaSegura123!';
        $studentData['password_confirmation'] = 'ContraseñaSegura123!';

        $registerResponse = $this->post(route('register.store'), $studentData);
        $registerResponse->assertRedirectToRoute('login');

        // 2. Sistema crea cuenta en PENDIENTE y no puede iniciar sesión
        $student = User::where('email', 'nuevo.alumno@upemor.edu.mx')->first();
        $this->assertNotNull($student);
        $this->assertSame('PENDIENTE', $student->estado);
        $this->assertSame('B', $student->estudiante->grupo);

        $failedLogin = $this->post(route('login.post'), [
            'email' => 'nuevo.alumno@upemor.edu.mx',
            'password' => 'ContraseñaSegura123!',
        ]);
        $failedLogin->assertSessionHasErrors('email');
        $this->assertGuest();

        // 3. Solicitud aparece en el módulo administrativo
        $adminList = $this->actingAs($admin)->get(route('admin.solicitudes.index'));
        $adminList->assertOk();
        $adminList->assertSee('20261100');
        $adminList->assertSee('2026110001');
        $adminList->assertSee('nuevo.alumno@upemor.edu.mx');

        // 4. Administrador aprueba solicitud
        $approveResponse = $this->actingAs($admin)->patch(route('admin.solicitudes.aprobar', $student->usuario_id));
        $approveResponse->assertSessionHas('success');
        $this->assertSame('ACTIVO', $student->fresh()->estado);

        // 5. Alumno inicia sesión con sus credenciales (y correo en minúsculas o mayúsculas)
        $this->post(route('logout'));

        $loginResponse = $this->post(route('login.post'), [
            'email' => 'NUEVO.ALUMNO@UPEMOR.EDU.MX',
            'password' => 'ContraseñaSegura123!',
        ]);

        // 6. Alumno es redirigido a su dashboard provisional
        // 6. Alumno es redirigido a su dashboard
        $loginResponse->assertRedirectToRoute('alumno.dashboard');
        $this->assertAuthenticatedAs($student);
    }

    public function test_full_lifecycle_from_registration_to_rejection_blocks_login(): void
    {
        $adminRole = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $admin = User::factory()->withRole($adminRole)->create([
            'email' => 'admin.rechazo@upemor.edu.mx',
            'password' => 'AdminPass1234!',
            'estado' => 'ACTIVO',
        ]);

        // 1. Registro
        $studentData = $this->validData;
        $studentData['email'] = 'alumno.rechazado@upemor.edu.mx';
        $studentData['matricula'] = '20262200';
        $studentData['matricula'] = '2026220001';
        $studentData['password'] = 'ContraseñaSegura123!';
        $studentData['password_confirmation'] = 'ContraseñaSegura123!';

        $this->post(route('register.store'), $studentData);
        $student = User::where('email', 'alumno.rechazado@upemor.edu.mx')->first();
        $this->assertNotNull($student);

        // 2. Administrador rechaza
        $rejectResponse = $this->actingAs($admin)->patch(route('admin.solicitudes.rechazar', $student->usuario_id));
        $rejectResponse->assertSessionHas('success');
        $this->assertSame('RECHAZADO', $student->fresh()->estado);

        // 3. Alumno intenta iniciar sesión y es bloqueado
        $this->post(route('logout'));

        $loginResponse = $this->post(route('login.post'), [
            'email' => 'alumno.rechazado@upemor.edu.mx',
            'password' => 'ContraseñaSegura123!',
        ]);

        $loginResponse->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_matricula_must_be_exactly_ten_characters(): void
    {
        // Menos de 10 caracteres
        $dataMenor = $this->validData;
        $dataMenor['matricula'] = '202310019'; // 9 caracteres
        $responseMenor = $this->post(route('register.store'), $dataMenor);
        $responseMenor->assertSessionHasErrors('matricula');

        // Más de 10 caracteres
        $dataMayor = $this->validData;
        $dataMayor['matricula'] = '20231001001'; // 11 caracteres
        $responseMayor = $this->post(route('register.store'), $dataMayor);
        $responseMayor->assertSessionHasErrors('matricula');

        // Caracteres no alfanuméricos
        $dataSimbolo = $this->validData;
        $dataSimbolo['matricula'] = '2023-10010'; // contiene guion
        $responseSimbolo = $this->post(route('register.store'), $dataSimbolo);
        $responseSimbolo->assertSessionHasErrors('matricula');
    }

    public function test_matricula_is_normalized_to_uppercase_in_validation_and_database(): void
    {
        $data = $this->validData;
        $data['email'] = 'alumno.minusculas@upemor.edu.mx';
        $data['matricula'] = 'abc1234567'; // 10 caracteres en minúsculas

        $response = $this->post(route('register.store'), $data);
        $response->assertRedirectToRoute('login');

        $this->assertDatabaseHas('estudiantes', [
            'matricula' => 'ABC1234567',
        ]);
    }

    public function test_duplicate_matricula_is_rejected_even_when_submitted_in_lowercase(): void
    {
        $data = $this->validData;
        $data['matricula'] = 'UPEM202601';
        $this->post(route('register.store'), $data);

        $duplicate = $this->validData;
        $duplicate['email'] = 'otro.alumno@upemor.edu.mx';
        $duplicate['matricula'] = 'upem202601'; // Misma matrícula en minúsculas

        $response = $this->post(route('register.store'), $duplicate);
        $response->assertSessionHasErrors('matricula');
    }

    public function test_cuatrimestre_is_required_and_must_be_between_1_and_10(): void
    {
        // Sin cuatrimestre
        $dataSin = $this->validData;
        unset($dataSin['cuatrimestre']);
        $this->post(route('register.store'), $dataSin)->assertSessionHasErrors('cuatrimestre');

        // Cuatrimestre cero
        $dataCero = $this->validData;
        $dataCero['cuatrimestre'] = 0;
        $this->post(route('register.store'), $dataCero)->assertSessionHasErrors('cuatrimestre');

        // Cuatrimestre 11
        $data11 = $this->validData;
        $data11['cuatrimestre'] = 11;
        $this->post(route('register.store'), $data11)->assertSessionHasErrors('cuatrimestre');

        // Cuatrimestre válido 10
        $data10 = $this->validData;
        $data10['email'] = 'cuatrimestre10@upemor.edu.mx';
        $data10['matricula'] = '2026101010';
        $data10['cuatrimestre'] = 10;
        $this->post(route('register.store'), $data10)->assertRedirectToRoute('login');

        $this->assertDatabaseHas('estudiantes', [
            'matricula' => '2026101010',
            'cuatrimestre' => 10,
        ]);
    }

    public function test_grupo_is_required_and_only_options_a_through_f_are_allowed(): void
    {
        // Sin grupo
        $dataSin = $this->validData;
        unset($dataSin['grupo']);
        $this->post(route('register.store'), $dataSin)->assertSessionHasErrors('grupo');

        // Grupo inválido 'G'
        $dataG = $this->validData;
        $dataG['grupo'] = 'G';
        $this->post(route('register.store'), $dataG)->assertSessionHasErrors('grupo');

        // Grupo en minúscula 'f' normalizado a mayúscula 'F'
        $dataF = $this->validData;
        $dataF['email'] = 'alumno.grupof@upemor.edu.mx';
        $dataF['matricula'] = '202600000F';
        $dataF['grupo'] = 'f';
        $this->post(route('register.store'), $dataF)->assertRedirectToRoute('login');

        $this->assertDatabaseHas('estudiantes', [
            'matricula' => '202600000F',
            'grupo' => 'F',
        ]);

        // Acepta letras válidas A, B, C, D, E, F
        foreach (['A', 'B', 'C', 'D', 'E'] as $index => $letra) {
            $dataValid = $this->validData;
            $dataValid['email'] = "alumno.grupo{$letra}@upemor.edu.mx";
            $dataValid['matricula'] = sprintf('20260000%02d', $index + 1);
            $dataValid['grupo'] = $letra;
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.1.{$index}"])
                ->post(route('register.store'), $dataValid)
                ->assertRedirectToRoute('login');

            $this->assertDatabaseHas('estudiantes', [
                'matricula' => sprintf('20260000%02d', $index + 1),
                'grupo' => $letra,
            ]);
        }
    }

    public function test_registration_with_matricula_admo230179_is_accepted(): void
    {
        $data = $this->validData;
        $data['email'] = 'estudiante.admo@upemor.edu.mx';
        $data['matricula'] = 'ADMO230179';
        $data['grupo'] = 'B';

        $response = $this->post(route('register.store'), $data);
        $response->assertRedirectToRoute('login');

        $this->assertDatabaseHas('estudiantes', [
            'matricula' => 'ADMO230179',
            'grupo' => 'B',
        ]);
    }

    public function test_password_mismatch_shows_clear_error_and_retains_other_inputs_without_passwords(): void
    {
        $data = $this->validData;
        $data['password'] = 'ContraseñaSegura123!';
        $data['password_confirmation'] = 'OtraContraseñaDiferente123!';

        $response = $this->from(route('register'))->post(route('register.store'), $data);

        $response->assertRedirectToRoute('register');
        $response->assertSessionHasErrors([
            'password' => 'Las contraseñas no coinciden.',
        ]);

        // Asegurar que conserva los demás campos en old input
        $response->assertSessionHasInput('nombre', 'Ana');
        $response->assertSessionHasInput('apellido_paterno', 'García');
        $response->assertSessionHasInput('apellido_materno', 'López');
        $response->assertSessionHasInput('email', 'ana.garcia@upemor.edu.mx');
        $response->assertSessionHasInput('matricula', '2023100101');
        $response->assertSessionHasInput('cuatrimestre', 3);
        $response->assertSessionHasInput('grupo', 'A');

        // Asegurar que por seguridad las contraseñas NO se conservan en old input
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertFalse(session()->hasOldInput('password_confirmation'));
    }
}
