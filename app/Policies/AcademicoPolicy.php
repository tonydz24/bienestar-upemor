<?php

namespace App\Policies;

use App\Models\DocenteAsignacion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AcademicoPolicy
{
    /**
     * Determina si el docente puede consultar los detalles y estudiantes de una asignación académica.
     */
    public function viewAsignacion(User $user, DocenteAsignacion $asignacion): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        if ($user->esAdministrador()) {
            return Response::allow();
        }

        if ($user->esDocente() && (int) $user->usuario_id === (int) $asignacion->docente_id) {
            return Response::allow();
        }

        return Response::deny('No tienes asignada esta asignatura o grupo.');
    }

    /**
     * Determina si el docente puede activar recompensas en una asignación.
     */
    public function activarEnAsignacion(User $user, DocenteAsignacion $asignacion): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        if ($user->esAdministrador()) {
            return Response::allow();
        }

        if ($user->esDocente() && (int) $user->usuario_id === (int) $asignacion->docente_id) {
            return Response::allow();
        }

        return Response::deny('Solo el docente titular de la asignatura puede activar recompensas.');
    }
}
