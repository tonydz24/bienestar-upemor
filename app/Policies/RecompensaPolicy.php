<?php

namespace App\Policies;

use App\Models\DocenteAsignacion;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecompensaPolicy
{
    /**
     * Determina si el docente puede activar recompensas en una asignación.
     */
    public function activarEnAsignacion(User $user, DocenteAsignacion $asignacion): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        if ($user->esDocente() && (int) $user->usuario_id === (int) $asignacion->docente_id) {
            return Response::allow();
        }

        return Response::deny('Solo el docente titular de la asignatura puede activar recompensas.');
    }

    /**
     * Determina si el docente puede gestionar una activación de recompensa específica.
     */
    public function manageActivacion(User $user, RecompensaActivada $activacion): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        if ($user->esDocente() && (int) $user->usuario_id === (int) $activacion->asignacion->docente_id) {
            return Response::allow();
        }

        return Response::deny('No estás autorizado para administrar esta recompensa.');
    }

    /**
     * Determina si el docente puede resolver (aprobar o rechazar) un canje.
     */
    public function manageCanje(User $user, RecompensaCanje $canje): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        $docenteId = $canje->activacion->asignacion->docente_id;

        if ($user->esDocente() && (int) $user->usuario_id === (int) $docenteId) {
            return Response::allow();
        }

        return Response::deny('Solo el profesor de la materia puede resolver solicitudes de canje.');
    }

    /**
     * Determina si el estudiante puede cancelar su solicitud de canje.
     */
    public function cancelByStudent(User $user, RecompensaCanje $canje): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        if ($user->esAlumno() && (int) $user->usuario_id === (int) $canje->estudiante_id) {
            return Response::allow();
        }

        return Response::deny('No puedes cancelar un canje del cual no eres titular.');
    }
}

