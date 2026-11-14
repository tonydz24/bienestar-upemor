<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private string $testPassword = 'testing-only-password';

    public function test_the_login_form_is_displayed(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Iniciar Sesión');
        $response->assertDontSee('Recordarme');
    }

    public function test_an_active_user_can_log_in(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'alumno@example.test',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
        ]);

        $response->assertRedirectToRoute('alumno.dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->alumno()->create([
            'email' => 'alumno@example.test',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_five_failed_login_attempts_are_allowed_before_the_limit(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
                ->post(route('login.post'), [
                    'email' => 'limit@example.test',
                    'password' => 'incorrect-password',
                ]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors('email');
        }
    }

    public function test_the_sixth_failed_attempt_is_temporarily_blocked(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.11'])
                ->post(route('login.post'), [
                    'email' => 'blocked-limit@example.test',
                    'password' => 'incorrect-password',
                ]);
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.11'])
            ->post(route('login.post'), [
                'email' => 'blocked-limit@example.test',
                'password' => 'incorrect-password',
            ]);

        $response->assertStatus(429);
        $response->assertSessionHasErrors('email', 'Las credenciales ingresadas no coinciden con nuestros registros.');
        $this->assertGuest();
    }

    public function test_login_limits_are_independent_by_email_and_ip(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.12'])
                ->post(route('login.post'), [
                    'email' => 'primary-limit@example.test',
                    'password' => 'incorrect-password',
                ]);
        }

        $differentEmail = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.12'])
            ->post(route('login.post'), [
                'email' => 'secondary-limit@example.test',
                'password' => 'incorrect-password',
            ]);
        $differentEmail->assertStatus(302);

        $differentIp = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.13'])
            ->post(route('login.post'), [
                'email' => 'primary-limit@example.test',
                'password' => 'incorrect-password',
            ]);
        $differentIp->assertStatus(302);
    }

    public function test_successful_login_clears_the_failed_attempt_counter(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'recover@example.test',
        ]);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.14'])
                ->post(route('login.post'), [
                    'email' => $user->email,
                    'password' => 'incorrect-password',
                ]);
        }

        $successfulLogin = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.14'])
            ->post(route('login.post'), [
                'email' => $user->email,
                'password' => $this->testPassword,
            ]);
        $successfulLogin->assertRedirectToRoute('alumno.dashboard');
        $this->post(route('logout'));

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $retryAfterSuccess = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.14'])
                ->post(route('login.post'), [
                    'email' => $user->email,
                    'password' => 'incorrect-password',
                ]);
            $retryAfterSuccess->assertStatus(302);
        }
    }

    public function test_inactive_users_cannot_log_in_with_correct_credentials(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'inactive@example.test',
            'estado' => 'INACTIVO',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_blocked_users_cannot_log_in_with_correct_credentials(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'blocked@example.test',
            'estado' => 'BLOQUEADO',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pending_users_cannot_log_in_with_correct_credentials(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'pending@example.test',
            'estado' => 'PENDIENTE',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rejected_users_cannot_log_in_with_correct_credentials(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'rejected@example.test',
            'estado' => 'RECHAZADO',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_users_are_redirected_to_their_role_dashboard(): void
    {
        $adminRole = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $admin = User::factory()->withRole($adminRole)->create(['email' => 'admin@example.test']);

        $responseAdmin = $this->post(route('login.post'), [
            'email' => $admin->email,
            'password' => $this->testPassword,
        ]);
        $responseAdmin->assertRedirectToRoute('admin.dashboard');
        $this->post(route('logout'));

        $docenteRole = Rol::factory()->docente()->create(['rol_id' => 2]);
        $docente = User::factory()->withRole($docenteRole)->create(['email' => 'docente@example.test']);

        $responseDocente = $this->post(route('login.post'), [
            'email' => $docente->email,
            'password' => $this->testPassword,
        ]);
        $responseDocente->assertRedirectToRoute('docente.dashboard');
        $this->post(route('logout'));

        $alumnoRole = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $alumno = User::factory()->withRole($alumnoRole)->create(['email' => 'alumno@example.test']);

        $responseAlumno = $this->post(route('login.post'), [
            'email' => $alumno->email,
            'password' => $this->testPassword,
        ]);
        $responseAlumno->assertRedirectToRoute('alumno.dashboard');
        $this->post(route('logout'));
    }

    public function test_logout_invalidates_the_session(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'logout@example.test',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['test_marker' => 'session-value'])
            ->post(route('logout'));

        $response->assertRedirectToRoute('login');
        $response->assertSessionMissing('test_marker');
        $this->assertGuest();
    }

    public function test_an_inactive_authenticated_session_is_closed_on_dashboard_access(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'session-inactive@example.test',
        ]);

        $this->actingAs($user);
        $user->update(['estado' => 'INACTIVO']);

        $response = $this->get(route('alumno.dashboard'));

        $response->assertRedirectToRoute('login');
        $response->assertSessionHasErrors('email', 'No fue posible validar la sesión.');
        $this->assertGuest();
    }

    public function test_a_blocked_authenticated_session_is_closed_on_dashboard_access(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'session-blocked@example.test',
        ]);

        $this->actingAs($user);
        $user->update(['estado' => 'BLOQUEADO']);

        $response = $this->get(route('alumno.dashboard'));

        $response->assertRedirectToRoute('login');
        $response->assertSessionHasErrors('email', 'No fue posible validar la sesión.');
        $this->assertGuest();
    }

    public function test_each_role_can_only_access_its_own_dashboard(): void
    {
        $roles = [
            'Administrador' => Rol::factory()->administrador()->create(['rol_id' => 1]),
            'Docente' => Rol::factory()->docente()->create(['rol_id' => 2]),
            'Alumno' => Rol::factory()->alumno()->create(['rol_id' => 3]),
        ];
        $users = [];

        foreach ($roles as $roleName => $role) {
            $users[$roleName] = User::factory()->withRole($role)->create([
                'email' => strtolower($roleName).'@example.test',
            ]);
        }

        $dashboards = [
            'Administrador' => 'admin.dashboard',
            'Docente' => 'docente.dashboard',
            'Alumno' => 'alumno.dashboard',
        ];

        foreach ($users as $roleName => $user) {
            foreach ($dashboards as $dashboardRole => $dashboardRoute) {
                $response = $this->actingAs($user)->get(route($dashboardRoute));

                if ($roleName === $dashboardRole) {
                    $response->assertOk();
                } else {
                    $response->assertForbidden();
                }
            }
        }
    }

    public function test_guests_are_redirected_from_protected_dashboards(): void
    {
        foreach (['admin.dashboard', 'docente.dashboard', 'alumno.dashboard'] as $route) {
            $response = $this->get(route($route));

            $response->assertRedirectToRoute('login');
        }
    }

    public function test_users_are_redirected_by_role_name_with_non_standard_ids(): void
    {
        $adminRole = Rol::factory()->create(['rol_id' => 99, 'nombre' => 'Administrador', 'activo' => true]);
        $docenteRole = Rol::factory()->create(['rol_id' => 150, 'nombre' => 'Docente', 'activo' => true]);
        $alumnoRole = Rol::factory()->create(['rol_id' => 200, 'nombre' => 'Alumno', 'activo' => true]);

        $admin = User::factory()->withRole($adminRole)->create(['email' => 'custom-admin@example.test']);
        $docente = User::factory()->withRole($docenteRole)->create(['email' => 'custom-docente@example.test']);
        $alumno = User::factory()->withRole($alumnoRole)->create(['email' => 'custom-alumno@example.test']);

        $cases = [
            [$admin, 'admin.dashboard'],
            [$docente, 'docente.dashboard'],
            [$alumno, 'alumno.dashboard'],
        ];

        foreach ($cases as [$user, $expectedRoute]) {
            $response = $this->post(route('login.post'), [
                'email' => $user->email,
                'password' => $this->testPassword,
            ]);

            $response->assertRedirectToRoute($expectedRoute);
            $this->assertAuthenticatedAs($user);
            $this->post(route('logout'));
        }
    }

    public function test_login_with_uppercase_email_is_normalized_and_accepted(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'estudiante.mayus@upemor.edu.mx',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'ESTUDIANTE.MAYUS@UPEMOR.EDU.MX',
            'password' => $this->testPassword,
        ]);

        $response->assertRedirectToRoute('alumno.dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_does_not_create_persistent_session(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'alumno.recordarme@upemor.edu.mx',
        ]);

        // Intentar iniciar sesión (incluso si se enviase remember=true por cliente manipulado)
        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
            'remember' => true,
        ]);

        $response->assertRedirectToRoute('alumno.dashboard');
        $this->assertNull($user->fresh()->remember_token);
        $response->assertCookieExpired(Auth::getRecallerName());
    }

    public function test_session_expires_after_fifteen_minutes_of_inactivity_and_clears_remember_token(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'inactividad@upemor.edu.mx',
            'remember_token' => 'pre-existing-token-1234567890',
        ]);

        // Simular sesión autenticada con última actividad hace 16 minutos (> 15 min)
        $response = $this->actingAs($user)
            ->withSession([
                'last_activity_time' => time() - (16 * 60),
            ])
            ->get(route('alumno.dashboard'));

        $response->assertRedirectToRoute('login');
        $response->assertSessionHasErrors([
            'email' => 'Su sesión ha expirado por 15 minutos de inactividad. Por favor, ingrese nuevamente.',
        ]);
        $this->assertGuest();

        // El token persistente debe haberse destruido en la base de datos
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_activity_within_fifteen_minutes_keeps_session_alive(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'actividad.valida@upemor.edu.mx',
        ]);

        // Simular actividad reciente (hace 5 minutos)
        $response = $this->actingAs($user)
            ->withSession([
                'last_activity_time' => time() - (5 * 60),
            ])
            ->get(route('alumno.dashboard'));

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_persistent_cookie_reauthentication_is_blocked_on_protected_routes(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'reauth.test@upemor.edu.mx',
            'remember_token' => 'pre-existing-token-1234567890',
        ]);
        $rememberToken = $user->remember_token;

        // Simular intento de acceso directo mediante cookie recaller sin sesión previa
        $cookieHash = hash_hmac('sha256', $user->password, config('app.key'));
        $rawRecaller = $user->usuario_id . '|' . $rememberToken . '|' . $cookieHash;
        $response = $this->withCookie(Auth::getRecallerName(), $rawRecaller)
            ->get(route('alumno.dashboard'));

        // Debe bloquear el acceso y exigir credenciales nuevamente
        $response->assertRedirectToRoute('login');
        $response->assertSessionHasErrors([
            'email' => 'Por seguridad y protección de datos, debes ingresar tus credenciales nuevamente.',
        ]);
        $this->assertGuest();

        // Y debe haber anulado el remember_token en la base de datos
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_manual_logout_destroys_session_and_remember_token_in_database(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'logout.remember@upemor.edu.mx',
            'remember_token' => 'pre-existing-token-9876543210',
        ]);

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
        ]);

        $logoutResponse = $this->post(route('logout'));
        $logoutResponse->assertRedirectToRoute('login');
        $logoutResponse->assertSessionHas('success', 'Sesión cerrada correctamente.');
        $this->assertGuest();

        // Token de remember destruido en la base de datos
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_deactivated_account_loses_remember_token_and_cannot_reauthenticate(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'cuenta.bloqueada@upemor.edu.mx',
            'remember_token' => 'token-para-destruir-12345678',
        ]);

        $this->actingAs($user);
        $user->update(['estado' => 'BLOQUEADO']);

        $response = $this->get(route('alumno.dashboard'));
        $response->assertRedirectToRoute('login');
        $response->assertSessionHasErrors('email', 'No fue posible validar la sesión.');
        $this->assertGuest();

        // Token persistente destruido
        $this->assertNull($user->fresh()->remember_token);
    }
}
