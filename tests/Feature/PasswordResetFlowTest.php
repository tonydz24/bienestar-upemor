<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Notifications\RestablecerPasswordNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Rol $rolAlumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Alumno']);

        $this->user = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Hernandez',
            'apellido_materno' => 'Ruiz',
            'email' => 'carlos.recovery@upemor.edu.mx',
            'password' => Hash::make('ClaveOriginal2026!'),
            'estado' => 'ACTIVO',
        ]);
    }

    public function test_vista_solicitud_restablecimiento_es_accesible(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('Recupera tu acceso');
        $response->assertSee('Enviar enlace de recuperación');
    }

    public function test_solicitud_con_correo_inexistente_no_revela_existencia(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'correo.fantasma.inexistente@upemor.edu.mx',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        // El mensaje de éxito debe ser genérico (anti-enumeración de usuarios)
        $this->assertStringContainsString('Si la dirección ingresada está registrada', session('status'));
    }

    public function test_solicitud_con_correo_valido_genera_token_y_envia_notificacion(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Notification::assertSentTo(
            $this->user,
            RestablecerPasswordNotification::class,
            function ($notification) {
                return !empty($notification->token);
            }
        );
    }

    public function test_vista_reset_password_con_token_valido(): void
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $this->user->email,
        ]));

        $response->assertOk();
        $response->assertSee('Crear Contraseña');
        $response->assertSee('Restablecer y actualizar contraseña');
        $response->assertSee('Al menos 8 caracteres');
    }

    public function test_bloqueo_de_reutilizacion_de_misma_contrasena(): void
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'ClaveOriginal2026!',
            'password_confirmation' => 'ClaveOriginal2026!',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertTrue(Hash::check('ClaveOriginal2026!', $this->user->fresh()->password));
    }

    public function test_validacion_de_contrasena_debil_o_discrepante(): void
    {
        $token = Password::broker()->createToken($this->user);

        // Menor a 8 caracteres
        $responseCorta = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'Ab1!',
            'password_confirmation' => 'Ab1!',
        ]);
        $responseCorta->assertSessionHasErrors(['password']);

        // Discrepancia en confirmación
        $responseMismatch = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NuevaClaveSegura2026!',
            'password_confirmation' => 'DiferenteClave2026!',
        ]);
        $responseMismatch->assertSessionHasErrors(['password']);
    }

    public function test_restablecimiento_exitoso_actualiza_hash_e_invalida_token_para_segundo_uso(): void
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NuevaClaveValida2026!',
            'password_confirmation' => 'NuevaClaveValida2026!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->user->refresh();

        // La nueva clave funciona
        $this->assertTrue(Hash::check('NuevaClaveValida2026!', $this->user->password));
        // La clave antigua ya no funciona
        $this->assertFalse(Hash::check('ClaveOriginal2026!', $this->user->password));

        // Intento de reusar el mismo token de un solo uso
        $responseReuso = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'OtraClaveMas2026!',
            'password_confirmation' => 'OtraClaveMas2026!',
        ]);

        $responseReuso->assertSessionHasErrors(['email']);
    }

    public function test_token_expirado_es_rechazado(): void
    {
        $token = Password::broker()->createToken($this->user);

        // Forzar expiración adelantando el tiempo más de 60 minutos
        Carbon::setTestNow(now()->addMinutes(65));

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'ClaveExpirada2026!',
            'password_confirmation' => 'ClaveExpirada2026!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Hash::check('ClaveExpirada2026!', $this->user->fresh()->password));

        Carbon::setTestNow(); // Restaurar tiempo
    }
}
