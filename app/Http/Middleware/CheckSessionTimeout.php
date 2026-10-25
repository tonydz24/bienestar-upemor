<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity_time');
            $timeout = 15 * 60; // 15 minutos expresados en segundos (FF.4 / RD.2)

            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Su sesión ha expirado por 15 minutos de inactividad. Por favor, ingrese nuevamente.'
                ]);
            }

            // Actualizar la estampa de tiempo de la última actividad
            session(['last_activity_time' => time()]);
        }

        return $next($request);
    }
}