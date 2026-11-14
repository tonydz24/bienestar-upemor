<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\User;
use App\Notifications\PropuestaRecompensaResueltaNotification;
use App\Services\Puntos\PuntoService;
use App\Services\Recompensas\RecompensaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Controlador de Gestión Administrativa de Recompensas Institucionales.
 *
 * Administra el catálogo de recompensas de la UPEMOR, la revisión de propuestas
 * docentes, la activación institucional de beneficios, cancelación atómica con
 * reembolso y visualización de trazabilidad de canjes.
 */
class RecompensaAdminController extends Controller
{
    /**
     * Inicializa el controlador inyectando el servicio de gestión de recompensas.
     *
     * @param RecompensaService $recompensaService Servicio de reglas de recompensas y canjes.
     */
    public function __construct(
        protected RecompensaService $recompensaService
    ) {}

    /**
     * Muestra el catálogo institucional y las propuestas de beneficios docentes.
     *
     * @return View Vista del catálogo institucional con propuestas pendientes y resueltas.
     */
    public function catalogo(): View
    {
        $institucionales = RecompensaCatalogo::withCount('activaciones')
            ->where('estado_propuesta', RecompensaCatalogo::PROPUESTA_INSTITUCIONAL)
            ->orderBy('nombre')
            ->get();

        $propuestasPendientes = RecompensaCatalogo::with('docenteSolicitante')
            ->where('estado_propuesta', RecompensaCatalogo::PROPUESTA_PENDIENTE)
            ->orderBy('created_at', 'asc')
            ->get();

        $propuestasResueltas = RecompensaCatalogo::with('docenteSolicitante')
            ->whereIn('estado_propuesta', [RecompensaCatalogo::PROPUESTA_APROBADA, RecompensaCatalogo::PROPUESTA_RECHAZADA])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return view('admin.recompensas.catalogo', compact('institucionales', 'propuestasPendientes', 'propuestasResueltas'));
    }

    /**
     * Registra un nuevo beneficio en el catálogo institucional.
     *
     * @param Request $request Petición HTTP con nombre, descripción, categoría y costo en puntos.
     * @return RedirectResponse Redirección a la vista previa con confirmación de éxito.
     * @throws \Illuminate\Validation\ValidationException Si los datos no superan la validación.
     */
    public function storeCatalogo(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string|max:1000',
            'categoria' => 'required|in:ACADEMICA,TIEMPO,PARTICIPACION,RECONOCIMIENTO',
            'costo_puntos_base' => 'required|integer|min:1|max:500',
            'condiciones_generales' => 'nullable|string|max:1000',
        ]);

        RecompensaCatalogo::create([
            'nombre' => trim($request->input('nombre')),
            'descripcion' => trim($request->input('descripcion')),
            'categoria' => $request->input('categoria'),
            'costo_puntos_base' => (int) $request->input('costo_puntos_base'),
            'condiciones_generales' => $request->input('condiciones_generales'),
            'estado_propuesta' => RecompensaCatalogo::PROPUESTA_INSTITUCIONAL,
            'docente_solicitante_id' => null,
            'activo' => true,
        ]);

        return back()->with('success', 'Beneficio institucional agregado exitosamente al catálogo.');
    }

    /**
     * Actualiza los datos de un beneficio existente en el catálogo institucional.
     *
     * @param Request $request Petición HTTP con campos actualizados del beneficio.
     * @param int $recompensaId Identificador del registro en el catálogo.
     * @return RedirectResponse Redirección con mensaje informativo.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la recompensa no existe.
     * @throws \Illuminate\Validation\ValidationException Si la validación falla.
     */
    public function updateCatalogo(Request $request, int $recompensaId): RedirectResponse
    {
        $recompensa = RecompensaCatalogo::findOrFail($recompensaId);

        $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string|max:1000',
            'categoria' => 'required|in:ACADEMICA,TIEMPO,PARTICIPACION,RECONOCIMIENTO',
            'costo_puntos_base' => 'required|integer|min:1|max:500',
            'condiciones_generales' => 'nullable|string|max:1000',
        ]);

        $recompensa->update([
            'nombre' => trim($request->input('nombre')),
            'descripcion' => trim($request->input('descripcion')),
            'categoria' => $request->input('categoria'),
            'costo_puntos_base' => (int) $request->input('costo_puntos_base'),
            'condiciones_generales' => $request->input('condiciones_generales'),
            'activo' => $request->has('activo') ? $request->boolean('activo') : $recompensa->activo,
        ]);

        return back()->with('success', "Beneficio institucional '{$recompensa->nombre}' actualizado exitosamente. Los canjes históricos previos conservan sus condiciones de canje.");
    }

    /**
     * Alterna la disponibilidad activa de un beneficio institucional en el catálogo.
     *
     * @param int $recompensaId Identificador de la recompensa.
     * @return RedirectResponse Redirección con estado actualizado.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la recompensa no existe.
     */
    public function toggleActivo(int $recompensaId): RedirectResponse
    {
        $recompensa = RecompensaCatalogo::findOrFail($recompensaId);
        $recompensa->activo = ! $recompensa->activo;
        $recompensa->save();

        $estadoStr = $recompensa->activo ? 'activado' : 'desactivado';
        return back()->with('success', "El beneficio '{$recompensa->nombre}' ha sido {$estadoStr} en el catálogo institucional.");
    }

    /**
     * Procesa la eliminación física o baja lógica segura de una recompensa del catálogo.
     *
     * Cancela atómicamente solicitudes de canje pendientes reintegrando puntos a los alumnos,
     * y aplica baja lógica si existen registros históricos para proteger la contabilidad.
     *
     * @param Request $request Petición HTTP entrante.
     * @param int $recompensaId Identificador de la recompensa.
     * @return RedirectResponse Redirección con mensaje de resultado.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la recompensa no existe.
     */
    public function destroyCatalogo(Request $request, int $recompensaId): RedirectResponse
    {
        $recompensa = RecompensaCatalogo::with('activaciones')->findOrFail($recompensaId);
        $nombre = $recompensa->nombre;
        $activacionesIds = $recompensa->activaciones->pluck('activacion_id')->toArray();

        // 1. Cancelar atómicamente solicitudes pendientes liberando y reembolsando puntos
        $canjesPendientes = RecompensaCanje::whereIn('activacion_id', $activacionesIds)
            ->whereIn('estado', [RecompensaCanje::ESTADO_SOLICITADO, 'PENDIENTE'])
            ->get();

        $puntosReembolsados = 0;
        $solicitudesCanceladas = 0;

        foreach ($canjesPendientes as $canjePendiente) {
            DB::transaction(function () use ($canjePendiente, &$puntosReembolsados, &$solicitudesCanceladas) {
                $estudiante = Estudiante::where('usuario_id', $canjePendiente->estudiante_id)->lockForUpdate()->first();
                if ($estudiante) {
                    $estudiante->increment('saldo_puntos', $canjePendiente->puntos_canjeados);
                }

                $activacion = RecompensaActivada::where('activacion_id', $canjePendiente->activacion_id)->lockForUpdate()->first();
                if ($activacion) {
                    $activacion->increment('cupo_disponible', 1);
                }

                $canjePendiente->estado = RecompensaCanje::ESTADO_CANCELADO;
                $canjePendiente->motivo_rechazo = 'Cancelado automáticamente por eliminación del beneficio en el catálogo institucional.';
                $canjePendiente->fecha_resolucion = now();
                $canjePendiente->save();

                $alumnoUser = User::find($canjePendiente->estudiante_id);
                if ($alumnoUser) {
                    app(PuntoService::class)->registrarReembolsoCanje(
                        $alumnoUser,
                        $canjePendiente,
                        (int) $canjePendiente->puntos_canjeados
                    );
                }

                $puntosReembolsados += (int) $canjePendiente->puntos_canjeados;
                $solicitudesCanceladas++;
            });
        }

        // 2. Contar canjes históricos asociados (concluidos, utilizados, cancelados o rechazados)
        $totalCanjes = RecompensaCanje::whereIn('activacion_id', $activacionesIds)->count();

        if ($totalCanjes > 0) {
            // Protección de integridad contable e histórica: baja lógica inmutable
            DB::transaction(function () use ($recompensa, $activacionesIds) {
                $recompensa->update(['activo' => false]);
                RecompensaActivada::whereIn('activacion_id', $activacionesIds)
                    ->update(['estado' => RecompensaActivada::ESTADO_FINALIZADA]);
            });

            $msg = "La recompensa '{$nombre}' cuenta con {$totalCanjes} registro(s) histórico(s) de canje. Para proteger la trazabilidad contable, la recompensa ha sido desactivada del catálogo institucional.";
            if ($solicitudesCanceladas > 0) {
                $msg .= " Se cancelaron {$solicitudesCanceladas} solicitud(es) pendiente(s) y se reembolsaron {$puntosReembolsados} puntos a los estudiantes.";
            }

            return back()->with('info', $msg);
        }

        // 3. Si no tiene canjes históricos de ningún tipo, eliminación física completa
        DB::transaction(function () use ($recompensa) {
            $recompensa->activaciones()->delete();
            $recompensa->delete();
        });

        return back()->with('success', "La recompensa '{$nombre}' ha sido eliminada físicamente de forma exitosa del catálogo institucional.");
    }

    /**
     * Devuelve en JSON las activaciones de un beneficio en grupos académicos.
     *
     * @param int $recompensaId Identificador de la recompensa.
     * @return JsonResponse Respuesta JSON con la lista de activaciones docentes.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la recompensa no existe.
     */
    public function activaciones(int $recompensaId): JsonResponse
    {
        $recompensa = RecompensaCatalogo::with([
            'activaciones.asignacion.grupo.programa',
            'activaciones.asignacion.asignatura',
            'activaciones.asignacion.docente.usuario',
        ])->findOrFail($recompensaId);

        return response()->json([
            'recompensa' => $recompensa->nombre,
            'activaciones' => $recompensa->activaciones,
        ]);
    }

    /**
     * Devuelve en JSON el historial de canjes realizados de una recompensa.
     *
     * @param int $recompensaId Identificador de la recompensa.
     * @return JsonResponse Respuesta JSON con los registros de canje.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la recompensa no existe.
     */
    public function canjes(int $recompensaId): JsonResponse
    {
        $recompensa = RecompensaCatalogo::findOrFail($recompensaId);

        $canjes = RecompensaCanje::with([
            'estudianteUsuario.estudiante.programa',
            'activacion.asignacion.asignatura',
            'activacion.asignacion.grupo',
        ])->whereIn('activacion_id', $recompensa->activaciones()->pluck('activacion_id'))
          ->orderByDesc('fecha_canje')
          ->orderByDesc('canje_id')
          ->get();

        return response()->json([
            'recompensa' => $recompensa->nombre,
            'canjes' => $canjes,
        ]);
    }

    /**
     * Aprueba una propuesta de beneficio solicitada por un docente.
     *
     * @param int $recompensaId Identificador de la propuesta docente.
     * @return RedirectResponse Redirección con mensaje de éxito y notificación emitida.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la propuesta no existe.
     */
    public function aprobarPropuesta(int $recompensaId): RedirectResponse
    {
        $recompensa = RecompensaCatalogo::findOrFail($recompensaId);

        $this->recompensaService->aprobarPropuestaAdmin($recompensa, Auth::user());

        if ($recompensa->docente_solicitante_id) {
            $profesor = User::find($recompensa->docente_solicitante_id);
            $profesor?->notify(new PropuestaRecompensaResueltaNotification(
                $recompensa->nombre,
                'aprobada'
            ));
        }

        return back()->with('success', "Propuesta '{$recompensa->nombre}' aprobada. Ahora está disponible exclusivamente para el profesor solicitante.");
    }

    /**
     * Rechaza una propuesta de beneficio docente indicando el motivo justificado.
     *
     * @param Request $request Petición con el motivo obligatorio de rechazo.
     * @param int $recompensaId Identificador de la propuesta.
     * @return RedirectResponse Redirección con mensaje de rechazo registrado.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la propuesta no existe.
     * @throws \Illuminate\Validation\ValidationException Si el motivo no es provisto.
     */
    public function rechazarPropuesta(Request $request, int $recompensaId): RedirectResponse
    {
        $recompensa = RecompensaCatalogo::findOrFail($recompensaId);

        $request->validate([
            'motivo' => 'required|string|max:255',
        ]);

        $this->recompensaService->rechazarPropuestaAdmin($recompensa, Auth::user(), $request->input('motivo'));

        if ($recompensa->docente_solicitante_id) {
            $profesor = User::find($recompensa->docente_solicitante_id);
            $profesor?->notify(new PropuestaRecompensaResueltaNotification(
                $recompensa->nombre,
                'rechazada',
                $request->input('motivo')
            ));
        }

        return back()->with('success', "Propuesta rechazada debidamente.");
    }
}
