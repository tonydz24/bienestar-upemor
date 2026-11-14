<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Muestra la vista para solicitar el enlace de restablecimiento.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Envía el enlace de restablecimiento al correo del usuario.
     * Implementa protección estricta contra enumeración de cuentas.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un formato de correo electrónico válido.',
        ]);

        // Broker envía la notificación si existe el usuario
        Password::broker()->sendResetLink(
            $request->only('email')
        );

        // Respuesta genérica anti-enumeración de cuentas
        return back()->with(
            'status',
            'Si la dirección ingresada está registrada en Bienestar UPEMOR, recibirás un enlace para restablecer tu contraseña. Revisa también tu carpeta de Spam o Correo no deseado.'
        );
    }

    /**
     * Muestra la vista para ingresar la nueva contraseña con el token provisto.
     */
    public function showResetForm(Request $request, ?string $token = null)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Valida el token y restablece la contraseña del usuario.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'token.required' => 'El token de recuperación es obligatorio o ha expirado.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        // Protección de seguridad: impedir reutilizar la contraseña actual
        $emailNormalizado = strtolower(trim((string) $request->input('email')));
        $usuario = User::where('email', $emailNormalizado)->first();

        if ($usuario && Hash::check($request->input('password'), $usuario->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'password' => 'Por motivos de seguridad, la nueva contraseña no puede ser idéntica a tu contraseña actual.',
                ]);
        }

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión con tu nueva credencial.')
                ->with('success', 'Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión con tu nueva credencial.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'El enlace para restablecer la contraseña es inválido, ya fue utilizado o ha expirado. Por favor solicita uno nuevo.',
            ]);
    }
}

