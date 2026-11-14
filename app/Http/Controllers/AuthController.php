<?php

namespace App\Http\Controllers;

use App\Models\LoginAcceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Controlador de Autenticación, Control de Sesión y Auditoría de Accesos.
 *
 * Gestiona el inicio y cierre de sesión seguro, política de contraseñas,
 * rate limiting contra ataques de fuerza bruta, no-persistencia (sin recordar credenciales)
 * y bitácora de auditoría en la tabla `logins_accesos`.
 */
class AuthController extends Controller
{
    /**
     * Muestra el formulario institucional de inicio de sesión o redirige si ya hay sesión activa.
     *
     * @return View|RedirectResponse Formulario de login o redirección según el rol del usuario autenticado.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectUserByRole();
        }
        return view('auth.login');
    }

    /**
     * Procesa la solicitud de autenticación con rate-limiting, verificación de estado ACTIVO y registro de auditoría.
     *
     * @param Request $request Petición HTTP con credenciales (email y password).
     * @return RedirectResponse Redirección al dashboard correspondiente o retorno con errores.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);
        $credentials['email'] = Str::lower(trim($credentials['email']));
        $credentials['estado'] = 'ACTIVO';
        $throttleKey = 'login|'.$credentials['email'].'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors([
                'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
            ])->onlyInput('email')->setStatusCode(429);
        }

        // Inicio de sesión no persistente (política institucional estricta de seguridad)
        if (Auth::attempt($credentials, false)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            // Garantizar que no existan cookies de recordarme ni tokens persistentes
            Cookie::queue(Cookie::forget(Auth::getRecallerName()));
            $user = Auth::user();
            $user->setRememberToken(null);

            // Iniciar estampa de tiempo de actividad y actualizar último acceso
            session(['last_activity_time' => time()]);

            $user->ultimo_acceso = now();
            $user->save();

            // Auditoría institucional de accesos por día (FN.15)
            try {
                LoginAcceso::create([
                    'usuario_id' => $user->usuario_id,
                    'rol_id' => $user->rol_id,
                    'nombre_usuario' => trim($user->nombre . ' ' . ($user->apellido_paterno ?? '')),
                    'email' => $user->email,
                    'rol_nombre' => $user->rol?->nombre ?? 'Alumno',
                    'fecha_acceso' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'exitoso' => true,
                ]);
            } catch (\Throwable $e) {
                // Silencioso para tolerancia a fallos
            }

            return $this->redirectUserByRole();
        }

        RateLimiter::hit($throttleKey, 60);

        // Registro de intento fallido para trazabilidad de seguridad y reportes
        try {
            LoginAcceso::create([
                'usuario_id' => null,
                'rol_id' => null,
                'nombre_usuario' => $credentials['email'],
                'email' => $credentials['email'],
                'rol_nombre' => 'Intento Fallido',
                'fecha_acceso' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'exitoso' => false,
            ]);
        } catch (\Throwable $e) {
            // Silencioso para tolerancia a fallos
        }

        return back()->withErrors([
            'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Cierra de forma segura la sesión del usuario invalidando el token y cookies de sesión.
     *
     * @param Request $request Petición HTTP de logout.
     * @return RedirectResponse Redirección al formulario de login con mensaje de éxito.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            $user->setRememberToken(null);
            $user->save();
        }

        Cookie::queue(Cookie::forget(Auth::getRecallerName()));
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }

    /**
     * Resuelve y redirige al panel correspondiente según el rol del usuario autenticado.
     *
     * @return RedirectResponse Redirección a la ruta correspondiente por rol.
     */
    private function redirectUserByRole(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->esAdministrador()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->esOrientador()) {
            return redirect()->route('docente.citas.index');
        }

        if ($user->esDocente()) {
            return redirect()->route('docente.dashboard');
        }

        if ($user->esAlumno()) {
            return redirect()->route('alumno.dashboard');
        }

        return tap(redirect()->route('login')->withErrors(['email' => 'Perfil no autorizado.']), function () {
            Auth::logout();
        });
    }
}
