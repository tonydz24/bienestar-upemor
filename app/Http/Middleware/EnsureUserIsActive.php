<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para Asegurar Estado Operativo Activo del Usuario.
 *
 * Expulsa e invalida la sesión de cualquier usuario cuyo estado no sea 'ACTIVO'
 * (por ejemplo, si fue cambiado a BLOQUEADO, INACTIVO o RECHAZADO por un administrador).
 */
class EnsureUserIsActive
{
    /**
     * Valida que el usuario autenticado mantenga el estado 'ACTIVO'.
     *
     * @param Request $request Petición HTTP entrante.
     * @param Closure(Request): (Response) $next Siguiente middleware en la canalización.
     * @return Response Respuesta HTTP o redirección al login con mensaje explicativo.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->estado !== 'ACTIVO') {
            $user = $request->user();
            if ($user) {
                $user->setRememberToken(null);
                $user->save();
            }

            Cookie::queue(Cookie::forget(Auth::getRecallerName()));
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'No fue posible validar la sesión.',
            ]);
        }

        return $next($request);
    }
}
