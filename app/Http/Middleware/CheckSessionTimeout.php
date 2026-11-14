<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para Control de Tiempo de Inactividad de Sesión y Expiración Segura.
 *
 * Aplica la política de seguridad institucional:
 * 1. Cierre automático e invalidación tras 15 minutos (900 segundos) sin actividad.
 * 2. Revocación de accesos vía remember token (no persistencia).
 * 3. Exclusión de sondeo asíncrono en segundo plano (polling de notificaciones)
 *    para evitar que peticiones automáticas extiendan artificialmente la sesión.
 */
class CheckSessionTimeout
{
    /**
     * Rutas o patrones de peticiones en segundo plano que no deben reiniciar
     * el contador de inactividad de la sesión del usuario.
     *
     * @var array<int, string>
     */
    protected array $backgroundPollingRoutes = [
        'notificaciones.recientes-unread',
        'notificaciones.recientes',
    ];

    /**
     * Maneja la petición entrante verificando inactividad y políticas de sesión.
     *
     * @param Request $request Petición HTTP entrante.
     * @param Closure(Request): (Response) $next Siguiente middleware en la canalización.
     * @return Response Respuesta HTTP o redirección por sesión expirada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Política de Bienestar UPEMOR: Si el usuario accedió exclusivamente vía cookie persistente
            // tras haber expirado la sesión previa o cerrado el navegador, exigir credenciales nuevamente.
            if (Auth::viaRemember()) {
                $user = Auth::user();
                if ($user) {
                    $user->setRememberToken(null);
                    $user->save();
                }

                Cookie::queue(Cookie::forget(Auth::getRecallerName()));
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'session_expired' => true,
                        'message' => 'Por seguridad y protección de datos, debes ingresar tus credenciales nuevamente.',
                        'redirect' => route('login'),
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'Por seguridad y protección de datos, debes ingresar tus credenciales nuevamente.'
                ]);
            }

            $lastActivity = session('last_activity_time');
            $timeout = 15 * 60; // 15 minutos exactos (900 segundos)

            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                $user = Auth::user();
                if ($user) {
                    $user->setRememberToken(null);
                    $user->save();
                }

                Cookie::queue(Cookie::forget(Auth::getRecallerName()));
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $mensajeExpiracion = 'Por tu seguridad y para proteger la confidencialidad de tu información, tu sesión ha finalizado después de 15 minutos sin actividad. Para continuar, vuelve a iniciar sesión.';

                if ($request->expectsJson() || $request->ajax() || $request->header('X-Background-Polling') === 'true') {
                    return response()->json([
                        'session_expired' => true,
                        'message' => $mensajeExpiracion,
                        'redirect' => route('login'),
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'Su sesión ha expirado por 15 minutos de inactividad. Por favor, ingrese nuevamente.'
                ]);
            }

            // REGLA INSTITUCIONAL: El sondeo asíncrono periódico en segundo plano (polling)
            // NO debe renovar el tiempo de inactividad de la sesión si el usuario no interactúa.
            $isPolling = $request->header('X-Background-Polling') === 'true'
                || $request->is('notificaciones/recientes-unread')
                || $request->is('notificaciones/recientes')
                || in_array($request->route()?->getName(), $this->backgroundPollingRoutes);

            if (!$isPolling) {
                session(['last_activity_time' => time()]);
            }
        }

        return $next($request);
    }
}
