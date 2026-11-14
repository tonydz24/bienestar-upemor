<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\EstudianteInscripcion;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Services\Recompensas\RecompensaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador de Consulta y Canje de Recompensas de Bienestar (Portal Alumno).
 *
 * Administra el catálogo de beneficios disponibles para los grupos inscritos del estudiante,
 * visualización del saldo acumulado de puntos y solicitud atómica de canjes.
 */
class RecompensaAlumnoController extends Controller
{
    /**
     * Inicializa el controlador con el servicio especializado de recompensas.
     *
     * @param RecompensaService $recompensaService Servicio con reglas transaccionales de canje y puntos.
     */
    public function __construct(
        protected RecompensaService $recompensaService
    ) {}

    /**
     * Catálogo de recompensas habilitadas para las materias del alumno, saldo de puntos e historial.
     */
    public function index()
    {
        if (!Schema::hasTable('recompensas_activadas') || !Schema::hasTable('recompensas_catalogo')) {
            return redirect()->route('alumno.dashboard')->with('info', 'El módulo de recompensas se encuentra en preparación.');
        }

        $usuario = Auth::user();
        $estudiante = $usuario->estudiante;
        $saldoPuntos = $estudiante?->saldo_puntos ?? 0;

        // Grupo oficial en el que el estudiante se encuentra formalmente adscrito para el periodo operativo
        $periodoOperativo = app(\App\Services\Academico\PeriodoOperativoService::class)->resolverPeriodoOperativo();
        $miGrupo = $periodoOperativo ? app(\App\Services\Academico\EstudianteGrupoResolverService::class)->resolverGrupo($usuario, $periodoOperativo) : null;
        $gruposInscritosIds = $miGrupo ? collect([$miGrupo->grupo_id]) : EstudianteInscripcion::where('estudiante_id', $usuario->usuario_id)
            ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
            ->pluck('grupo_id');

        $asignacionesInscritasIds = collect();
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            $asignacionesInscritasIds = \App\Models\EstudianteAsignaturaInscripcion::where('estudiante_id', $usuario->usuario_id)
                ->where('estado', \App\Models\EstudianteAsignaturaInscripcion::ESTADO_APROBADA)
                ->pluck('asignacion_id')
                ->filter();
        }

        // Recompensas activas ofrecidas por los profesores de sus asignaturas
        // Se respeta la prioridad absoluta del catálogo institucional: si el admin desactiva, no se lista
        $recompensasQuery = RecompensaActivada::with([
            'recompensaCatalogo',
            'asignacion.asignatura',
            'asignacion.grupo',
            'asignacion.docenteUsuario'
        ])
            ->where('estado', RecompensaActivada::ESTADO_ACTIVA)
            ->whereHas('recompensaCatalogo', fn ($q) => $q->where('activo', true)->where('estado_propuesta', '!=', \App\Models\RecompensaCatalogo::PROPUESTA_RECHAZADA))
            ->whereDate('vigencia_inicio', '<=', now()->toDateString())
            ->whereDate('vigencia_fin', '>=', now()->toDateString());

        if ($asignacionesInscritasIds->isNotEmpty()) {
            $recompensasQuery->where(function ($q) use ($gruposInscritosIds, $asignacionesInscritasIds) {
                $q->whereIn('asignacion_id', $asignacionesInscritasIds)
                  ->orWhereHas('asignacion', fn ($sub) => $sub->whereIn('grupo_id', $gruposInscritosIds));
            });
        } else {
            $recompensasQuery->whereHas('asignacion', fn ($q) => $q->whereIn('grupo_id', $gruposInscritosIds));
        }

        $recompensasDisponibles = $recompensasQuery->get();

        // Beneficios digitales activos o pendientes de ser utilizados ante el profesor
        $canjesActivos = RecompensaCanje::with([
            'activacion.recompensaCatalogo',
            'activacion.asignacion.asignatura',
            'activacion.asignacion.docenteUsuario'
        ])
            ->where('estudiante_id', $usuario->usuario_id)
            ->whereIn('estado', [
                RecompensaCanje::ESTADO_CANJEADA,
                RecompensaCanje::ESTADO_SOLICITADO,
                RecompensaCanje::ESTADO_APROBADO
            ])
            ->orderBy('fecha_canje', 'desc')
            ->get();

        // Historial resuelto (utilizados, rechazados, cancelados)
        $canjesHistorial = RecompensaCanje::with([
            'activacion.recompensaCatalogo',
            'activacion.asignacion.asignatura',
            'activacion.asignacion.docenteUsuario'
        ])
            ->where('estudiante_id', $usuario->usuario_id)
            ->whereNotIn('estado', [
                RecompensaCanje::ESTADO_CANJEADA,
                RecompensaCanje::ESTADO_SOLICITADO,
                RecompensaCanje::ESTADO_APROBADO
            ])
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        // FN.5 / FN.11: Libro mayor auditado de puntos
        $historialPuntos = app(\App\Services\Puntos\PuntoService::class)->obtenerHistorial($usuario, 10);

        return view('alumno.recompensas.index', compact('estudiante', 'saldoPuntos', 'recompensasDisponibles', 'canjesActivos', 'canjesHistorial', 'historialPuntos'));
    }

    /**
     * Procesa la solicitud voluntaria de canje.
     */
    public function canjear(Request $request, int $activacionId)
    {
        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        try {
            $canje = $this->recompensaService->solicitarCanje(
                alumnoUser: Auth::user(),
                activacionId: $activacionId,
                motivo: $request->input('motivo')
            );

            // Notificar al docente responsable de la asignatura
            $activacion = $canje->activacion()->with(['asignacion.docenteUsuario', 'recompensaCatalogo', 'asignacion.asignatura'])->first();
            if ($activacion?->asignacion?->docenteUsuario) {
                try {
                    $activacion->asignacion->docenteUsuario->notify(new \App\Notifications\CanjePendienteValidacionNotification(
                        alumnoNombre: Auth::user()->nombre . ' ' . Auth::user()->apellido_paterno,
                        recompensaNombre: $activacion->recompensaCatalogo?->nombre ?? 'Beneficio',
                        materiaNombre: $activacion->asignacion->asignatura?->nombre ?? 'Asignatura',
                        codigoBeneficio: $canje->codigo_beneficio
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enviar notificación de canje a docente: {$e->getMessage()}");
                }
            }

            return redirect()
                ->route('alumno.recompensas.index')
                ->with('success', '¡Tu solicitud de recompensa fue enviada al profesor! Se te notificará una vez revisada.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancela una solicitud de canje pendiente y reembolsa los puntos al alumno.
     */
    public function cancelar(int $canjeId)
    {
        $canje = RecompensaCanje::findOrFail($canjeId);
        Gate::authorize('cancelByStudent', $canje);

        try {
            $this->recompensaService->cancelarCanjePorAlumno($canje, Auth::user());

            return redirect()
                ->route('alumno.recompensas.index')
                ->with('success', 'La solicitud de recompensa fue cancelada y tus puntos fueron reembolsados íntegramente.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

