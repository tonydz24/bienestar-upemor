<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\CitaDisponibilidad;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Notifications\CitaActualizadaNotification;
use App\Services\Citas\CitaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador de Gestión de Citas y Horarios para Orientadores y Psicólogos.
 *
 * Administra la agenda de disponibilidad clínica, confirmación, reprogramación,
 * cancelación y registro de asistencia/conclusión de sesiones de orientación psicológica.
 */
class CitaGestionController extends Controller
{
    /**
     * Inicializa el controlador con el servicio especializado de citas de orientación.
     *
     * @param CitaService $citaService Servicio que implementa la lógica de gestión de citas y notificaciones.
     */
    public function __construct(
        protected CitaService $citaService
    ) {}

    /**
     * Valida que el usuario autenticado sea un profesional orientador activo.
     */
    protected function obtenerProfesionalAutenticado(): CitaProfesional
    {
        $user = Auth::user();

        if (!$user->esOrientador()) {
            abort(403, 'Acceso restringido a orientadores psicológicos autorizados.');
        }

        $profesional = $user->citaProfesional;

        if (!$profesional || !$profesional->activo) {
            abort(403, 'Tu cuenta no está asignada como orientador de citas en el sistema de Bienestar.');
        }

        return $profesional;
    }

    /**
     * Tablero principal de citas del orientador.
     */
    public function index(Request $request)
    {
        if (!Schema::hasTable('citas_orientacion') || !Schema::hasTable('citas_profesionales')) {
            return redirect()->route('docente.dashboard')->with('info', 'El módulo de Gestión de Citas se encuentra en preparación.');
        }

        $profesional = $this->obtenerProfesionalAutenticado();

        $tab = $request->input('tab', 'solicitadas');

        // Solicitudes pendientes de confirmación
        $solicitadas = CitaOrientacion::with(['estudianteUsuario.estudiante.programa'])
            ->where('profesional_id', $profesional->profesional_id)
            ->where('estado', CitaOrientacion::ESTADO_SOLICITADA)
            ->orderBy('fecha_cita', 'asc')
            ->orderBy('hora_inicio', 'asc')
            ->get();

        // Citas confirmadas próximas
        $confirmadas = CitaOrientacion::with(['estudianteUsuario.estudiante.programa'])
            ->where('profesional_id', $profesional->profesional_id)
            ->where('estado', CitaOrientacion::ESTADO_CONFIRMADA)
            ->where('fecha_cita', '>=', now()->toDateString())
            ->orderBy('fecha_cita', 'asc')
            ->orderBy('hora_inicio', 'asc')
            ->get();

        // Historial completo resuelto
        $historial = CitaOrientacion::with(['estudianteUsuario.estudiante.programa'])
            ->where('profesional_id', $profesional->profesional_id)
            ->where(function ($q) {
                $q->whereNotIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
                  ->orWhere(function ($sub) {
                      $sub->where('estado', CitaOrientacion::ESTADO_CONFIRMADA)
                          ->where('fecha_cita', '<', now()->toDateString());
                  });
            })
            ->orderBy('fecha_cita', 'desc')
            ->orderBy('hora_inicio', 'desc')
            ->paginate(15);

        return view('docente.citas.index', compact('profesional', 'solicitadas', 'confirmadas', 'historial', 'tab'));
    }

    /**
     * Detalle completo y gestión de una cita.
     */
    public function show(int $citaId)
    {
        $cita = CitaOrientacion::with(['estudianteUsuario.estudiante.programa', 'profesional.usuario', 'resueltoPorUsuario'])
            ->findOrFail($citaId);

        Gate::authorize('manageByProfessional', $cita);

        $evaluacionesEstudiante = collect();
        $user = Auth::user();

        // FN.3: Vista confidencial para Orientador (con consentimiento informado del alumno)
        if ($user->esOrientador() && Schema::hasTable('evaluaciones_intentos')) {
            $evaluacionesEstudiante = \App\Models\EvaluacionIntento::with(['version.instrumento', 'consentimiento'])
                ->where('usuario_id', $cita->estudiante_id)
                ->where('estado', \App\Models\EvaluacionIntento::ESTADO_FINALIZADO)
                ->whereHas('consentimiento')
                ->orderBy('fecha_finalizacion', 'desc')
                ->get();
        }

        return view('docente.citas.show', compact('cita', 'evaluacionesEstudiante'));
    }

    /**
     * Confirmar una cita solicitada.
     */
    public function confirmar(Request $request, int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);
        Gate::authorize('manageByProfessional', $cita);

        $request->validate([
            'lugar_o_enlace' => 'nullable|string|max:255',
        ]);

        try {
            $this->citaService->confirmarCita($cita, Auth::user(), $request->input('lugar_o_enlace'));

            $cita->estudianteUsuario?->notify(new CitaActualizadaNotification(
                'CONFIRMADA',
                $cita->fecha_cita ? $cita->fecha_cita->format('d/m/Y') : 'programada'
            ));

            return redirect()
                ->route('docente.citas.show', $cita->cita_id)
                ->with('success', 'La cita ha sido confirmada exitosamente. El estudiante podrá consultar los detalles actualizados.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Rechazar una solicitud de cita.
     */
    public function rechazar(Request $request, int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);
        Gate::authorize('manageByProfessional', $cita);

        $request->validate([
            'motivo' => 'required|string|max:255',
        ], [
            'motivo.required' => 'Debes especificar el motivo por el cual no es posible aceptar la cita.',
        ]);

        try {
            $this->citaService->rechazarCita($cita, Auth::user(), $request->input('motivo'));

            $cita->estudianteUsuario?->notify(new CitaActualizadaNotification(
                'RECHAZADA',
                $cita->fecha_cita ? $cita->fecha_cita->format('d/m/Y') : 'programada',
                $request->input('motivo')
            ));

            return redirect()
                ->route('docente.citas.index')
                ->with('success', 'La solicitud ha sido rechazada y el horario se ha liberado.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancelar una cita activa por imprevisto del orientador.
     */
    public function cancelar(Request $request, int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);
        Gate::authorize('manageByProfessional', $cita);

        $request->validate([
            'motivo' => 'required|string|max:255',
        ], [
            'motivo.required' => 'Debes indicar el motivo de la cancelación para informar al alumno.',
        ]);

        try {
            $this->citaService->cancelarPorProfesional($cita, Auth::user(), $request->input('motivo'));

            $cita->estudianteUsuario?->notify(new CitaActualizadaNotification(
                'CANCELADA_PROFESIONAL',
                $cita->fecha_cita ? $cita->fecha_cita->format('d/m/Y') : 'programada',
                $request->input('motivo')
            ));

            return redirect()
                ->route('docente.citas.show', $cita->cita_id)
                ->with('success', 'La cita fue cancelada y el horario ha quedado liberado.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Marcar una cita confirmada como completada / atendida.
     */
    public function finalizar(Request $request, int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);
        Gate::authorize('manageByProfessional', $cita);

        try {
            $this->citaService->finalizarCita($cita, Auth::user());

            $fechaCitaStr = $cita->fecha_cita ? $cita->fecha_cita->format('d/m/Y') : 'atendida';

            $cita->estudianteUsuario?->notify(new CitaActualizadaNotification(
                'FINALIZADA',
                $fechaCitaStr
            ));

            $nombreProf = trim(Auth::user()->nombre . ' ' . Auth::user()->apellido_paterno);
            $cita->estudianteUsuario?->notify(new \App\Notifications\EncuestaPostCitaNotification(
                $cita->cita_id,
                $fechaCitaStr,
                $nombreProf
            ));

            return redirect()
                ->route('docente.citas.show', $cita->cita_id)
                ->with('success', 'La cita ha sido marcada como concluida exitosamente.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Registrar que el estudiante no acudió a la sesión.
     */
    public function noAsistio(Request $request, int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);
        Gate::authorize('manageByProfessional', $cita);

        try {
            $this->citaService->marcarNoAsistio($cita, Auth::user());

            return redirect()
                ->route('docente.citas.show', $cita->cita_id)
                ->with('success', 'Se ha registrado la inasistencia a la cita.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pantalla de configuración de horarios y disponibilidad semanal.
     */
    public function disponibilidadIndex()
    {
        $profesional = $this->obtenerProfesionalAutenticado();

        $disponibilidades = CitaDisponibilidad::where('profesional_id', $profesional->profesional_id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();

        return view('docente.citas.disponibilidad', compact('profesional', 'disponibilidades'));
    }

    /**
     * Guarda una nueva regla de disponibilidad horaria con validación de solapamiento y confirmación de reemplazo.
     */
    public function disponibilidadStore(Request $request)
    {
        $profesional = $this->obtenerProfesionalAutenticado();

        $request->validate([
            'dia_semana' => 'required|integer|between:1,6',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'duracion_cita_min' => 'required|integer|in:30,45,60',
            'modalidad' => 'required|in:PRESENCIAL,VIRTUAL,AMBAS',
            'confirmar_reemplazo' => 'nullable|boolean',
            'reemplazar_id' => 'nullable|integer',
        ], [
            'dia_semana.required' => 'Selecciona un día de la semana.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        $confirmarReemplazo = (bool) $request->input('confirmar_reemplazo', false);
        $reemplazarId = $request->input('reemplazar_id') ? (int) $request->input('reemplazar_id') : null;

        $resultado = $this->citaService->guardarDisponibilidad(
            profesional: $profesional,
            diaSemana: (int) $request->input('dia_semana'),
            horaInicio: $request->input('hora_inicio'),
            horaFin: $request->input('hora_fin'),
            duracionMin: (int) $request->input('duracion_cita_min'),
            modalidad: $request->input('modalidad'),
            confirmarReemplazo: $confirmarReemplazo,
            reemplazarId: $reemplazarId
        );

        if (!$resultado['success']) {
            if (!empty($resultado['bloqueado'])) {
                return back()
                    ->withInput()
                    ->with('error', $resultado['mensaje']);
            }

            // Conflicto superpuesto sin citas activas: ofrecer reemplazo interactivo
            return back()
                ->withInput()
                ->with('alerta_solapamiento', [
                    'mensaje' => $resultado['mensaje'],
                    'reemplazar_id' => $resultado['reemplazar_id'],
                    'dia_semana' => $request->input('dia_semana'),
                    'hora_inicio' => $request->input('hora_inicio'),
                    'hora_fin' => $request->input('hora_fin'),
                    'duracion_cita_min' => $request->input('duracion_cita_min'),
                    'modalidad' => $request->input('modalidad'),
                ]);
        }

        return redirect()
            ->route('docente.citas.disponibilidad')
            ->with('success', $resultado['mensaje']);
    }

    /**
     * Elimina una regla de disponibilidad comprobando la ausencia de citas activas comprometidas.
     */
    public function disponibilidadDestroy(int $id)
    {
        $profesional = $this->obtenerProfesionalAutenticado();

        $resultado = $this->citaService->eliminarDisponibilidad($profesional, $id);

        if (!$resultado['success']) {
            return back()->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->route('docente.citas.disponibilidad')
            ->with('success', $resultado['mensaje']);
    }

    /**
     * Aprueba la solicitud de reprogramación pendiente de un estudiante, re-validando disponibilidad.
     */
    public function aprobarReprogramacion(int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);
        Gate::authorize('manageByProfessional', $cita);

        try {
            $this->citaService->aprobarReprogramacion($cita, Auth::user());

            return redirect()
                ->route('docente.citas.show', $cita->cita_id)
                ->with('success', '¡Reprogramación aprobada con éxito! La fecha y hora de la cita han sido actualizadas.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Ocurrió un error inesperado al procesar la aprobación de la reprogramación.');
        }
    }

    /**
     * Rechaza la solicitud de reprogramación de un estudiante, indicando el motivo.
     */
    public function rechazarReprogramacion(Request $request, int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);
        Gate::authorize('manageByProfessional', $cita);

        $request->validate([
            'motivo' => 'required|string|max:500',
        ], [
            'motivo.required' => 'Debes indicar el motivo del rechazo de la reprogramación.',
            'motivo.max' => 'El motivo no debe superar los 500 caracteres.',
        ]);

        try {
            $this->citaService->rechazarReprogramacion($cita, Auth::user(), $request->input('motivo'));

            return redirect()
                ->route('docente.citas.show', $cita->cita_id)
                ->with('success', 'La solicitud de reprogramación fue rechazada. La cita original continúa activa.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Ocurrió un error inesperado al procesar el rechazo.');
        }
    }
}

