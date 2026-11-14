<?php

namespace App\Policies;

use App\Models\EvaluacionIntento;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvaluacionPolicy
{
    /**
     * Determina si el usuario puede ver la lista de evaluaciones disponibles.
     * Solo alumnos con cuenta activa pueden acceder a sus evaluaciones.
     */
    public function viewAny(User $user): Response
    {
        if ($user->esAlumno() && $user->estado === 'ACTIVO') {
            return Response::allow();
        }

        return Response::deny('Solo los alumnos con cuenta activa pueden acceder al módulo de evaluaciones.');
    }

    /**
     * Determina si el usuario puede ver el resultado de un intento específico.
     * El acceso pertenece exclusivamente al alumno que lo realizó.
     * El acceso profesional de docentes/psicólogos no es automático ni se concede
     * únicamente por 'permite_visualizacion'; requiere relación de atención verificada.
     */
    public function view(User $user, EvaluacionIntento $intento): Response
    {
        if ($user->esAlumno() && $user->estado === 'ACTIVO' && (int) $user->usuario_id === (int) $intento->usuario_id) {
            return Response::allow();
        }

        return Response::deny('No tienes autorización para consultar esta evaluación psicológica confidencial.');
    }

    /**
     * Determina si el usuario puede iniciar un nuevo intento de evaluación.
     */
    public function create(User $user): Response
    {
        if ($user->esAlumno() && $user->estado === 'ACTIVO') {
            return Response::allow();
        }

        return Response::deny('Solo los alumnos activos pueden realizar evaluaciones.');
    }

    /**
     * Determina si el usuario puede actualizar o registrar respuestas en un intento.
     */
    public function update(User $user, EvaluacionIntento $intento): Response
    {
        if (! ($user->esAlumno() && $user->estado === 'ACTIVO' && (int) $user->usuario_id === (int) $intento->usuario_id)) {
            return Response::deny('No tienes autorización para responder o modificar este cuestionario.');
        }

        if ($intento->estaFinalizado()) {
            return Response::deny('Esta evaluación ya ha sido finalizada y sus respuestas no pueden modificarse.');
        }

        return Response::allow();
    }

    /**
     * Determina si un intento puede ser eliminado.
     * En Bienestar UPEMOR, los registros e intentos de evaluaciones no pueden eliminarse
     * por los alumnos para preservar la integridad histórica del acompañamiento.
     */
    public function delete(User $user, EvaluacionIntento $intento): Response
    {
        return Response::deny('Los registros de evaluaciones psicológicas forman parte del historial de bienestar y no pueden eliminarse.');
    }
}

