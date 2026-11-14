<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\DocenteAsignacion;
use App\Models\PeriodoAcademico;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Services\Recompensas\RecompensaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador de Gestión de Recompensas y Activaciones para el Portal Docente.
 *
 * Administra la habilitación de beneficios en cátedras asignadas, seguimiento
 * y validación de canjes solicitados por estudiantes y propuesta de nuevas recompensas.
 */
class RecompensaDocenteController extends Controller
{
    /**
     * Inicializa el controlador con el servicio especializado de recompensas.
     *
     * @param RecompensaService $recompensaService Servicio con reglas transaccionales de recompensas.
     */
    public function __construct(
        protected RecompensaService $recompensaService
    ) {}

    /**
     * Muestra las recompensas activadas y los beneficios digitales pendientes de utilización.
     */
    public function index(Request $request)
    {
        if (!Schema::hasTable('recompensas_activadas') || !Schema::hasTable('recompensas_catalogo')) {
            return redirect()->route('docente.dashboard')->with('info', 'El módulo de recompensas se encuentra en preparación.');
        }

        $usuario = Auth::user();
        $tab = $request->input('tab', 'solicitudes');

        // Asignaciones activas del docente con sus relaciones completas
        $asignacionesDocente = DocenteAsignacion::with([
            'asignatura.programa',
            'grupo.programa',
            'periodo',
            'recompensasActivadas.recompensaCatalogo',
            'recompensasActivadas.canjes'
        ])
            ->where('docente_id', $usuario->usuario_id)
            ->where('activo', true)
            ->get();

        $asignacionesIds = $asignacionesDocente->pluck('asignacion_id');

        // Beneficios digitales pendientes de ser utilizados por los alumnos
        $solicitudes = RecompensaCanje::with([
            'activacion.recompensaCatalogo',
            'activacion.asignacion.asignatura',
            'activacion.asignacion.grupo',
            'estudianteUsuario.estudiante.programa'
        ])
            ->whereHas('activacion', fn ($q) => $q->whereIn('asignacion_id', $asignacionesIds))
            ->whereIn('estado', [
                RecompensaCanje::ESTADO_CANJEADA,
                RecompensaCanje::ESTADO_SOLICITADO,
                RecompensaCanje::ESTADO_APROBADO
            ])
            ->orderBy('fecha_canje', 'asc')
            ->get();

        // Recompensas activas ofrecidas por el docente
        $activadas = RecompensaActivada::with([
            'recompensaCatalogo',
            'asignacion.asignatura',
            'asignacion.grupo',
            'canjes'
        ])
            ->whereIn('asignacion_id', $asignacionesIds)
            ->orderBy('created_at', 'desc')
            ->get();

        // Historial de canjes concluidos (utilizados, rechazados o cancelados)
        $historial = RecompensaCanje::with([
            'activacion.recompensaCatalogo',
            'activacion.asignacion.asignatura',
            'activacion.asignacion.grupo',
            'estudianteUsuario.estudiante'
        ])
            ->whereHas('activacion', fn ($q) => $q->whereIn('asignacion_id', $asignacionesIds))
            ->whereNotIn('estado', [
                RecompensaCanje::ESTADO_CANJEADA,
                RecompensaCanje::ESTADO_SOLICITADO,
                RecompensaCanje::ESTADO_APROBADO
            ])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        // Propuestas personalizadas enviadas por este docente
        $misPropuestas = RecompensaCatalogo::where('docente_solicitante_id', $usuario->usuario_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('docente.recompensas.index', compact(
            'asignacionesDocente',
            'solicitudes',
            'activadas',
            'historial',
            'misPropuestas',
            'tab'
        ));
    }

    /**
     * Formulario para habilitar una recompensa del catálogo en una asignatura asignada.
     */
    public function create(int $asignacionId)
    {
        $asignacion = DocenteAsignacion::with(['asignatura', 'grupo.programa', 'periodo'])->findOrFail($asignacionId);

        Gate::authorize('activarEnAsignacion', $asignacion);

        $catalogo = RecompensaCatalogo::disponiblesParaDocente(Auth::id())->orderBy('nombre')->get();

        // Identificar recompensas que ya están habilitadas o pausadas en esta misma asignatura y grupo
        $habilitadasIds = RecompensaActivada::where('asignacion_id', $asignacion->asignacion_id)
            ->whereIn('estado', [RecompensaActivada::ESTADO_ACTIVA, RecompensaActivada::ESTADO_SUSPENDIDA])
            ->pluck('activacion_id', 'recompensa_id')
            ->toArray();

        return view('docente.recompensas.activar', compact('asignacion', 'catalogo', 'habilitadasIds'));
    }

    /**
     * Formulario para editar la configuración de un beneficio habilitado.
     */
    public function edit(int $activacionId)
    {
        $activacion = RecompensaActivada::with(['recompensaCatalogo', 'asignacion.asignatura', 'asignacion.grupo.programa', 'asignacion.periodo'])
            ->findOrFail($activacionId);

        Gate::authorize('manageActivacion', $activacion);

        $cuponesConsumidos = $activacion->cupo_maximo - $activacion->cupo_disponible;

        return view('docente.recompensas.editar', compact('activacion', 'cuponesConsumidos'));
    }

    /**
     * Actualiza la configuración vigente del beneficio respetando cupones ya canjeados.
     */
    public function update(Request $request, int $activacionId)
    {
        $activacion = RecompensaActivada::with('asignacion')->findOrFail($activacionId);
        Gate::authorize('manageActivacion', $activacion);

        $cuponesConsumidos = $activacion->cupo_maximo - $activacion->cupo_disponible;

        $request->validate([
            'cupo_maximo' => "required|integer|min:{$cuponesConsumidos}|max:100",
            'costo_puntos' => 'required|integer|min:0|max:500',
            'vigencia_inicio' => 'required|date',
            'vigencia_fin' => 'required|date|after_or_equal:vigencia_inicio',
            'condiciones_docente' => 'nullable|string|max:1000',
            'estado' => 'required|in:ACTIVA,SUSPENDIDA,FINALIZADA',
        ], [
            'cupo_maximo.min' => "El cupo no puede ser menor a los {$cuponesConsumidos} cupones ya canjeados.",
            'vigencia_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        ]);

        try {
            $this->recompensaService->actualizarConfiguracionActivacion(
                activacion: $activacion,
                cupoMaximo: (int) $request->input('cupo_maximo'),
                costoPuntos: (int) $request->input('costo_puntos'),
                vigenciaInicio: $request->input('vigencia_inicio'),
                vigenciaFin: $request->input('vigencia_fin'),
                condiciones: $request->input('condiciones_docente'),
                estado: $request->input('estado')
            );

            return redirect()
                ->route('docente.recompensas.index', ['tab' => 'activadas'])
                ->with('success', 'La configuración del beneficio fue actualizada exitosamente.');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Guarda la activación de una recompensa en la asignatura.
     */
    public function store(Request $request, int $asignacionId)
    {
        $asignacion = DocenteAsignacion::findOrFail($asignacionId);
        Gate::authorize('activarEnAsignacion', $asignacion);

        $request->validate([
            'recompensa_id' => 'required|integer|exists:recompensas_catalogo,recompensa_id',
            'cupo_maximo' => 'required|integer|min:1|max:50',
            'costo_puntos' => 'required|integer|min:0|max:500',
            'vigencia_inicio' => 'required|date',
            'vigencia_fin' => 'required|date|after_or_equal:vigencia_inicio',
            'condiciones_docente' => 'nullable|string|max:1000',
        ], [
            'recompensa_id.required' => 'Selecciona un beneficio del catálogo institucional.',
            'cupo_maximo.min' => 'El cupo debe ser al menos de 1.',
            'vigencia_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
        ]);

        try {
            $this->recompensaService->activarRecompensa(
                asignacion: $asignacion,
                recompensaId: (int) $request->input('recompensa_id'),
                cupoMaximo: (int) $request->input('cupo_maximo'),
                costoPuntos: (int) $request->input('costo_puntos'),
                vigenciaInicio: $request->input('vigencia_inicio'),
                vigenciaFin: $request->input('vigencia_fin'),
                condiciones: $request->input('condiciones_docente')
            );

            return redirect()
                ->route('docente.recompensas.index', ['tab' => 'activadas'])
                ->with('success', 'La recompensa fue habilitada exitosamente para los estudiantes del grupo.');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * El docente marca el beneficio digital adquirido por el estudiante como UTILIZADO.
     */
    public function utilizar(int $canjeId)
    {
        $canje = RecompensaCanje::with('activacion.asignacion')->findOrFail($canjeId);
        Gate::authorize('manageCanje', $canje);

        try {
            $this->recompensaService->utilizarBeneficio($canje, Auth::user());

            $canje->load(['estudianteUsuario', 'estudiante.usuario', 'activacion.recompensaCatalogo', 'activacion.asignacion.asignatura']);
            $destinatario = $canje->estudianteUsuario ?? $canje->estudiante?->usuario ?? $canje->estudiante;
            $destinatario?->notify(new \App\Notifications\CanjeResueltoNotification(
                recompensaNombre: $canje->activacion?->recompensaCatalogo?->nombre ?? 'Beneficio',
                materiaNombre: $canje->activacion?->asignacion?->asignatura?->nombre ?? 'Asignatura',
                estado: 'UTILIZADA'
            ));

            return back()->with('success', "El beneficio '{$canje->codigo_beneficio}' ha sido marcado como UTILIZADO correctamente.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Aprueba la solicitud de canje (compatibilidad con pruebas y flujo anterior).
     */
    public function aprobar(int $canjeId)
    {
        $canje = RecompensaCanje::with('activacion.asignacion')->findOrFail($canjeId);
        Gate::authorize('manageCanje', $canje);

        try {
            $this->recompensaService->aprobarCanje($canje, Auth::user());

            $canje->load(['estudianteUsuario', 'estudiante.usuario', 'activacion.recompensaCatalogo', 'activacion.asignacion.asignatura']);
            $destinatario = $canje->estudianteUsuario ?? $canje->estudiante?->usuario ?? $canje->estudiante;
            $destinatario?->notify(new \App\Notifications\CanjeResueltoNotification(
                recompensaNombre: $canje->activacion?->recompensaCatalogo?->nombre ?? 'Beneficio',
                materiaNombre: $canje->activacion?->asignacion?->asignatura?->nombre ?? 'Asignatura',
                estado: 'APROBADO'
            ));

            return back()->with('success', 'El beneficio ha sido aprobado. El alumno podrá hacer uso de él según las condiciones fijadas.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Rechaza la solicitud de canje y reintegra los puntos al alumno.
     */
    public function rechazar(Request $request, int $canjeId)
    {
        $canje = RecompensaCanje::with('activacion.asignacion')->findOrFail($canjeId);
        Gate::authorize('manageCanje', $canje);

        $request->validate([
            'motivo' => 'required|string|max:255',
        ], [
            'motivo.required' => 'Debes indicar el motivo del rechazo para informar al alumno.',
        ]);

        try {
            $this->recompensaService->rechazarCanje($canje, Auth::user(), $request->input('motivo'));

            $canje->load(['estudianteUsuario', 'estudiante.usuario', 'activacion.recompensaCatalogo', 'activacion.asignacion.asignatura']);
            $destinatario = $canje->estudianteUsuario ?? $canje->estudiante?->usuario ?? $canje->estudiante;
            $destinatario?->notify(new \App\Notifications\CanjeResueltoNotification(
                recompensaNombre: $canje->activacion?->recompensaCatalogo?->nombre ?? 'Beneficio',
                materiaNombre: $canje->activacion?->asignacion?->asignatura?->nombre ?? 'Asignatura',
                estado: 'RECHAZADO',
                motivo: $request->input('motivo')
            ));

            return back()->with('success', 'La solicitud fue rechazada y los puntos han sido reembolsados al alumno.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Modifica el estado de una recompensa activada (Pausar o Concluir).
     */
    public function cambiarEstado(Request $request, int $activacionId)
    {
        $activacion = RecompensaActivada::with('asignacion')->findOrFail($activacionId);
        Gate::authorize('manageActivacion', $activacion);

        $request->validate([
            'estado' => 'required|in:ACTIVA,SUSPENDIDA,FINALIZADA',
        ]);

        try {
            $this->recompensaService->cambiarEstadoActivacion($activacion, $request->input('estado'));

            return back()->with('success', 'El estado de la recompensa ha sido actualizado.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * El docente envía una propuesta de recompensa personalizada al administrador.
     */
    public function proponer(Request $request)
    {
        // Homogeneizar nombres de inputs provenientes de la vista
        $costo = $request->input('costo_puntos', $request->input('costo_puntos_base', 50));
        $justificacion = $request->input('justificacion', $request->input('justificacion_docente'));
        $condiciones = $request->input('condiciones', $request->input('condiciones_generales'));

        $request->merge([
            'costo_puntos' => $costo,
            'justificacion' => $justificacion,
            'condiciones' => $condiciones,
        ]);

        $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string|max:1000',
            'categoria' => 'nullable|in:ACADEMICA,TIEMPO,PARTICIPACION,RECONOCIMIENTO',
            'costo_puntos' => 'nullable|integer|min:1|max:500',
            'condiciones' => 'nullable|string|max:1000',
            'justificacion' => 'required|string|max:1000',
        ], [
            'nombre.required' => 'Por favor, escribe el nombre del beneficio o recompensa.',
            'descripcion.required' => 'Por favor, describe en qué consiste el beneficio para los estudiantes.',
            'justificacion.required' => 'Por favor, explica por qué propones este beneficio y cómo apoya a los alumnos.',
            'costo_puntos.integer' => 'El costo en puntos debe ser un valor numérico.',
            'costo_puntos.min' => 'El costo sugerido debe ser de al menos 1 punto.',
        ]);

        $categoria = $request->input('categoria') ?: 'ACADEMICA';
        $costoPuntos = (int) ($request->input('costo_puntos') ?: 50);

        try {
            $this->recompensaService->proponerRecompensaDocente(
                docenteUser: Auth::user(),
                nombre: (string) $request->input('nombre'),
                descripcion: (string) $request->input('descripcion'),
                categoria: $categoria,
                costoPuntos: $costoPuntos,
                condiciones: $request->input('condiciones'),
                justificacion: (string) $request->input('justificacion')
            );

            return redirect()
                ->route('docente.recompensas.index', ['tab' => 'propuestas'])
                ->with('success', 'Tu propuesta de beneficio ha sido enviada al Administrador para su validación institucional.');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
