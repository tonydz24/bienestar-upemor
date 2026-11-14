<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para Control de Acceso Basado en Roles (RBAC).
 *
 * Verifica que el usuario autenticado cuente con al menos uno de los roles
 * autorizados para la ruta solicitada, emitiendo un error 403 Forbidden en caso contrario.
 */
class EnsureUserHasRole
{
    /**
     * Valida que el usuario tenga asignado alguno de los roles permitidos.
     *
     * @param Request $request Petición HTTP entrante.
     * @param Closure(Request): (Response) $next Siguiente middleware en la canalización.
     * @param string ...$roles Uno o más nombres de rol autorizados (e.g. 'Administrador', 'Docente').
     * @return Response Respuesta HTTP procedente de la canalización.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Con código 403 si el rol no coincide.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole($roles)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
