<?php

namespace App\Policies;

use App\Models\BitacoraEmocional;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BitacoraEmocionalPolicy
{
    /**
     * Determina si el usuario puede ver la lista de bitácoras.
     * Solo alumnos con cuenta activa pueden acceder a su propia bitácora.
     */
    public function viewAny(User $user): Response
    {
        if ($user->esAlumno() && $user->estado === 'ACTIVO') {
            return Response::allow();
        }

        return Response::deny('Solo los alumnos con cuenta activa pueden acceder a la bitácora emocional.');
    }

    /**
     * Determina si el usuario puede ver un registro de bitácora específico.
     * Solo el alumno propietario puede ver su registro. Docentes y administradores no tienen acceso automático.
     */
    public function view(User $user, BitacoraEmocional $bitacora): Response
    {
        if ($user->esAlumno() && $user->estado === 'ACTIVO' && (int) $user->usuario_id === (int) $bitacora->usuario_id) {
            return Response::allow();
        }

        return Response::deny('No tienes autorización para consultar este registro emocional sensible.');
    }

    /**
     * Determina si el usuario puede crear registros de bitácora.
     */
    public function create(User $user): Response
    {
        if ($user->esAlumno() && $user->estado === 'ACTIVO') {
            return Response::allow();
        }

        return Response::deny('Solo los alumnos activos pueden registrar en la bitácora.');
    }

    /**
     * Determina si el usuario puede actualizar un registro de bitácora.
     */
    public function update(User $user, BitacoraEmocional $bitacora): Response
    {
        if ($user->esAlumno() && $user->estado === 'ACTIVO' && (int) $user->usuario_id === (int) $bitacora->usuario_id) {
            return Response::allow();
        }

        return Response::deny('No tienes autorización para modificar este registro.');
    }

    /**
     * Determina si el usuario puede eliminar un registro de bitácora.
     * Solo el alumno propietario activo puede eliminar sus propios registros,
     * siempre que no formen parte de un seguimiento psicológico (Regla FN.4).
     */
    public function delete(User $user, BitacoraEmocional $bitacora): Response
    {
        if (!$bitacora->puedeSerEliminado()) {
            return Response::deny('Este registro emocional forma parte de un seguimiento de orientación psicológica y no puede ser eliminado.');
        }

        if ($user->esAlumno() && $user->estado === 'ACTIVO' && (int) $user->usuario_id === (int) $bitacora->usuario_id) {
            return Response::allow();
        }

        return Response::deny('No tienes autorización para eliminar este registro emocional.');
    }
}
