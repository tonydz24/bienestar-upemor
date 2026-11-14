<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controlador de Aprobación y Seguimiento de Solicitudes de Registro de Alumnos.
 *
 * Administra las solicitudes de alta enviadas por los alumnos aspirantes,
 * gestionando transiciones de estado (PENDIENTE, ACTIVO, RECHAZADO) y la
 * asignación determinística del plan de estudio correspondiente a su cohorte.
 */
class SolicitudAlumnoController extends Controller
{
    /**
     * Catálogo de estados válidos para el filtrado de solicitudes.
     *
     * @var array<int, string>
     */
    private const ESTADOS = ['TODOS', 'PENDIENTE', 'ACTIVO', 'RECHAZADO'];

    /**
     * Muestra la bandeja de solicitudes de registro de alumnos con filtros y paginación.
     *
     * @param Request $request Petición HTTP con posibles parámetros de filtro ('estado', 'buscar').
     * @return View Vista con la lista paginada de solicitudes y contadores.
     */
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'PENDIENTE');
        if (! in_array($estado, self::ESTADOS, true)) {
            $estado = 'PENDIENTE';
        }
        $buscar = trim((string) $request->query('buscar', ''));

        $query = User::query()
            ->with('estudiante.programa')
            ->whereHas('rol', fn ($query) => $query->where('nombre', 'Alumno'))
            ->whereHas('estudiante');

        if ($estado !== 'TODOS') {
            $query->where('estado', $estado);
        }

        $solicitudes = $query
            ->when($buscar !== '', function ($query) use ($buscar) {
                $like = '%'.addcslashes($buscar, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('nombre', 'like', $like)
                        ->orWhere('apellido_paterno', 'like', $like)
                        ->orWhere('apellido_materno', 'like', $like)
                        ->orWhereHas('estudiante', fn ($estudiante) => $estudiante->where('matricula', 'like', $like));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('usuario_id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.solicitudes.index', [
            'solicitudes' => $solicitudes,
            'estado' => $estado,
            'buscar' => $buscar,
            'estados' => self::ESTADOS,
        ]);
    }

    /**
     * Aprueba la solicitud de un alumno cambiando su estado a ACTIVO.
     *
     * @param Request $request Petición HTTP autenticada del administrador.
     * @param int $usuario Identificador primario del usuario alumno.
     * @return RedirectResponse Redirección con mensaje de éxito o bloqueo.
     */
    public function aprobar(Request $request, int $usuario): RedirectResponse
    {
        return $this->cambiarEstado($request, $usuario, 'ACTIVO', 'La solicitud fue aprobada. El alumno ya puede iniciar sesión.');
    }

    /**
     * Rechaza la solicitud de registro de un alumno.
     *
     * @param Request $request Petición HTTP autenticada del administrador.
     * @param int $usuario Identificador primario del usuario alumno.
     * @return RedirectResponse Redirección con mensaje de rechazo o bloqueo.
     */
    public function rechazar(Request $request, int $usuario): RedirectResponse
    {
        return $this->cambiarEstado($request, $usuario, 'RECHAZADO', 'La solicitud fue rechazada.');
    }

    /**
     * Aplica la transición de estado de manera atómica verificando condiciones de seguridad.
     *
     * @param Request $request Petición HTTP del usuario administrador.
     * @param int $usuarioId Identificador del usuario a modificar.
     * @param string $nuevoEstado Nuevo estado a asignar ('ACTIVO' o 'RECHAZADO').
     * @param string $mensaje Mensaje de éxito para la sesión flash.
     * @return RedirectResponse Redirección con mensaje flash.
     */
    private function cambiarEstado(Request $request, int $usuarioId, string $nuevoEstado, string $mensaje): RedirectResponse
    {
        // Prevención de auto-modificación
        if ($usuarioId === $request->user()->usuario_id) {
            return back()->with('error', 'No puedes modificar tu propia cuenta desde este módulo.');
        }

        // La condición en el UPDATE hace atómica la transición PENDIENTE -> nuevo estado.
        $afectados = User::query()
            ->whereKey($usuarioId)
            ->where('estado', 'PENDIENTE')
            ->whereHas('rol', fn ($query) => $query->where('nombre', 'Alumno'))
            ->whereHas('estudiante')
            ->update(['estado' => $nuevoEstado]);

        if ($afectados === 0) {
            return back()->with('error', 'La solicitud no existe o ya fue atendida. No se realizaron cambios.');
        }

        // Al aprobar la solicitud, asociar determinísticamente el plan de estudio según cohorte
        // garantizando que no se inscriba automáticamente a materias
        if ($nuevoEstado === 'ACTIVO' && Schema::hasColumn('estudiantes', 'plan_id')) {
            $estudiante = Estudiante::find($usuarioId);
            if ($estudiante && empty($estudiante->plan_id)) {
                $plan = $estudiante->resolverPlanEstudio();
                if ($plan) {
                    $estudiante->update(['plan_id' => $plan->plan_id]);
                }
            }
        }

        return back()->with('success', $mensaje);
    }
}
