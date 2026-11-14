<?php

namespace App\Policies;

use App\Models\CitaOrientacion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CitaPolicy
{
    /**
     * Determina si el usuario puede acceder al módulo de citas.
     */
    public function viewAny(User $user): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        if ($user->esAlumno()) {
            return Response::allow();
        }

        if ($user->esOrientador()) {
            return Response::allow();
        }

        return Response::deny('No cuentas con autorización para acceder al módulo de citas de orientación.');
    }

    /**
     * Determina si el usuario puede consultar el detalle de una cita específica.
     * Protección estricta: Solo el alumno solicitante y el orientador asignado.
     * Docentes regulares y administradores generales quedan bloqueados.
     */
    public function view(User $user, CitaOrientacion $cita): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        // Caso 1: El estudiante solicitante
        if ($user->esAlumno() && (int) $user->usuario_id === (int) $cita->estudiante_id) {
            return Response::allow();
        }

        // Caso 2: El orientador profesional asignado a esta cita
        if ($user->esOrientador() && (int) $user->citaProfesional?->profesional_id === (int) $cita->profesional_id) {
            return Response::allow();
        }

        return Response::deny('Por confidencialidad estudiantil, solo el alumno solicitante y su orientador asignado tienen acceso a este registro.');
    }

    /**
     * Determina si el estudiante puede cancelar su cita.
     */
    public function cancelByStudent(User $user, CitaOrientacion $cita): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        if (!$user->esAlumno() || (int) $user->usuario_id !== (int) $cita->estudiante_id) {
            return Response::deny('No eres el titular de esta cita.');
        }

        if (!$cita->puedeSerCanceladaPorAlumno()) {
            return Response::deny('Esta cita no puede ser cancelada en su estado o fecha actual.');
        }

        return Response::allow();
    }

    /**
     * Determina si el orientador asignado puede gestionar la cita (confirmar, rechazar, reprogramar, finalizar).
     */
    public function manageByProfessional(User $user, CitaOrientacion $cita): Response
    {
        if ($user->estado !== 'ACTIVO') {
            return Response::deny('Tu cuenta no se encuentra activa.');
        }

        if (!$user->esOrientador()) {
            return Response::deny('Solo orientadores acreditados pueden gestionar citas.');
        }

        if ((int) $user->citaProfesional?->profesional_id !== (int) $cita->profesional_id) {
            return Response::deny('No eres el orientador asignado a esta cita.');
        }

        return Response::allow();
    }
}

