<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    // Muestra la vista del formulario de login
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectUserByRole();
        }
        return view('auth.login');
    }

    // Procesa el inicio de sesión
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Evita mantener una cookie de "recordarme" previa si no se marcó la casilla.
            if (! $remember) {
                Cookie::queue(Cookie::forget(Auth::getRecallerName()));

                // Invalida cualquier token "remember me" previo para evitar auto-login no deseado.
                Auth::user()->setRememberToken(Str::random(60));
            }

            // Actualizar último acceso
            $user = Auth::user();
            $user->ultimo_acceso = now();
            $user->save();

            return $this->redirectUserByRole();
        }

        return back()->withErrors([
            'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    // Cierre de sesión manual
    public function logout(Request $request)
    {
        Cookie::queue(Cookie::forget(Auth::getRecallerName()));
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }

    // Auxiliar para redirigir según el perfil/rol
    private function redirectUserByRole()
    {
        $rolId = Auth::user()->rol_id;

        switch ($rolId) {
            case 1:
                return redirect()->route('admin.dashboard');
            case 2:
                return redirect()->route('docente.dashboard');
            case 3:
                return redirect()->route('alumno.dashboard');
            default:
                Auth::logout();
                return redirect()->route('login')->withErrors(['email' => 'Perfil no autorizado.']);
        }
    }
}