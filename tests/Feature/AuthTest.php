<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                'email' => 'different-limit@example.test',
                'password' => 'incorrect-password',
            ]);
        $differentEmail->assertStatus(302);

        $differentIp = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.13'])
            ->post(route('login.post'), [
                'email' => 'primary-limit@example.test',
                'password' => 'incorrect-password',
            ]);
        $differentIp->assertStatus(302);

        $primary = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.12'])
            ->post(route('login.post'), [
                'email' => 'primary-limit@example.test',
                'password' => 'incorrect-password',
            ]);
        $primary->assertStatus(429);
    }

    public function test_successful_login_clears_the_failed_attempt_counter(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'reset-limit@example.test',
        ]);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.14'])
                ->post(route('login.post'), [
                    'email' => $user->email,
                    'password' => 'incorrect-password',
                ]);
        }

        $login = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.14'])
            ->post(route('login.post'), [
                'email' => $user->email,
                'password' => $this->testPassword,
            ]);
        $login->assertRedirectToRoute('alumno.dashboard');
        $this->post(route('logout'));

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.14'])
                ->post(route('login.post'), [
                    'email' => $user->email,
                    'password' => 'incorrect-password',
                ]);

            $response->assertStatus(302);
        }
    }

    public function test_inactive_users_cannot_log_in_with_correct_credentials(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'inactivo@example.test',
            'estado' => 'INACTIVO',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
        ]);

        $response->assertSessionHasErrors('email', 'Las credenciales ingresadas no coinciden con nuestros registros.');
        $this->assertGuest();
    }

    public function test_blocked_users_cannot_log_in_with_correct_credentials(): void
    {
        $role = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $user = User::factory()->withRole($role)->create([
            'email' => 'bloqueado@example.test',
            'estado' => 'BLOQUEADO',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $this->testPassword,
        ]);

        $response->assertSessionHasErrors('email', 'Las credenciales ingresadas no coinciden con nuestros registros.');
        $this->assertGuest();
    }

    public function test_users_are_redirected_to_their_role_dashboard(): void
    {
        $users = [
            [User::factory()->administrador()->create(['email' => 'admin@example.test']), 'admin.dashboard'],
            [User::factory()->docente()->create(['email' => 'docente@example.test']), 'docente.dashboard'],
            [User::factory()->alumno()->create(['email' => 'alumno@example.test']), 'alumno.dashboard'],
        ];

        foreach ($users as [$user, $route]) {
            $response = $this->post(route('login.post'), [
                'email' => $user->email,
                'password' => $this->testPassword,
            ]);

            $response->assertRedirectToRoute($route);
            $this->assertAuthenticatedAs($user);
            $this->post(route('logout'));
        }
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->alumno()->create([
            'email' => 'alumno@example.test',
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
}
