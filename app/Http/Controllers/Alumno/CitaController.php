<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Services\Citas\CitaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador de Gestión de Citas de Orientación Psicológica y Académica (Portal Alumno).
 *
 * Permite a los estudiantes consultar horarios disponibles, agendar citas con profesionales,
 * solicitar reprogramaciones justificadas y cancelar sesiones dentro del marco normativo.
 */
class CitaController extends Controller
{
    /**
     * Inicializa el controlador con el servicio especializado de citas de orientación.
     *
     * @param CitaService $citaService Servicio con reglas de negocio, validaciones y notificaciones de citas.
     */
    public function __construct(
        protected CitaService $citaService
    ) {}

    /**
     * Pantalla principal del alumno: Citas programadas e historial.
     */
    public function index(Request $request)
    {
        if (!Schema::hasTable('citas_orientacion') || !Schema::hasTable('citas_profesionales')) {
            return redirect()->route('alumno.dashboard')->with('info', 'El módulo de Citas de Orientación se encuentra temporalmente en preparación.');
        }

        Gate::authorize('viewAny', CitaOrientacion::class);

        $usuario = Auth::user();

        // Citas activas o próximas (SOLICITADA, CONFIRMADA)
        $citasProximas = CitaOrientacion::with(['profesional.usuario'])
            ->where('estudiante_id', $usuario->usuario_id)
            ->whereIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
            ->orderBy('fecha_cita', 'asc')
            ->orderBy('hora_inicio', 'asc')
            ->get();

        // Historial de citas pasadas o resueltas
        $citasHistorial = CitaOrientacion::with(['profesional.usuario'])
            ->where('estudiante_id', $usuario->usuario_id)
            ->whereNotIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
            ->orderBy('fecha_cita', 'desc')
            ->orderBy('hora_inicio', 'desc')
            ->paginate(10);

        // Orientadores disponibles para orientación
        $profesionales = CitaProfesional::activosParaCitas()
            ->with('usuario')
            ->get();

        return view('alumno.citas.index', compact('citasProximas', 'citasHistorial', 'profesionales'));
    }

    /**
     * Formulario interactivo para agendar una nueva cita.
     */
    public function create(Request $request)
    {
        if (!Schema::hasTable('citas_orientacion') || !Schema::hasTable('citas_profesionales')) {
            return redirect()->route('alumno.dashboard')->with('info', 'El módulo de Citas de Orientación se encuentra en preparación.');
        }

        Gate::authorize('viewAny', CitaOrientacion::class);

        $profesionales = CitaProfesional::activosParaCitas()
            ->with(['usuario', 'disponibilidades' => fn ($q) => $q->where('activo', true)])
            ->get();

        $profesionalSeleccionadoId = (int) $request->input('profesional_id', 0);

        return view('alumno.citas.solicitar', compact('profesionales', 'profesionalSeleccionadoId'));
    }

    /**
     * Endpoint AJAX para consultar horarios disponibles de un orientador en una fecha dada.
     */
    public function horariosDisponibles(Request $request)
    {
        $request->validate([
            'profesional_id' => 'required|integer|exists:citas_profesionales,profesional_id',
            'fecha' => 'required|date|after_or_equal:today',
        ]);

        $profesional = CitaProfesional::where('profesional_id', $request->input('profesional_id'))
            ->where('activo', true)
            ->where('permite_citas', true)
            ->first();

        if (!$profesional) {
            return response()->json([
                'success' => false,
                'message' => 'Orientador no disponible.',
                'slots' => [],
            ], 404);
        }

        $fecha = Carbon::parse($request->input('fecha'));
        $slots = $this->citaService->obtenerHorariosDisponibles($profesional, $fecha);

        return response()->json([
            'success' => true,
            'profesional' => [
                'id' => $profesional->profesional_id,
                'nombre' => $profesional->nombre_completo,
                'cargo' => $profesional->cargo,
                'modalidades' => $profesional->modalidades_permitidas,
                'lugar' => $profesional->lugar_atencion,
            ],
            'fecha' => $fecha->toDateString(),
            'dia_nombre' => $fecha->translatedFormat('l, d \d\e F \d\e Y'),
            'slots' => $slots,
        ]);
    }

    /**
     * Procesa la solicitud de cita con validaciones y control de concurrencia.
     */
    public function store(Request $request)
    {
        Gate::authorize('viewAny', CitaOrientacion::class);

        $request->validate([
            'profesional_id' => 'required|integer|exists:citas_profesionales,profesional_id',
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|string',
            'modalidad' => 'required|in:PRESENCIAL,VIRTUAL',
            'motivo_consulta' => 'nullable|string|max:1000',
        ], [
            'profesional_id.required' => 'Debes seleccionar un orientador.',
            'fecha_cita.required' => 'Debes indicar la fecha deseada.',
            'fecha_cita.after_or_equal' => 'La fecha no puede ser en el pasado.',
            'hora_inicio.required' => 'Debes seleccionar un horario disponible.',
            'modalidad.required' => 'Selecciona la modalidad de atención (Presencial o Virtual).',
            'motivo_consulta.max' => 'El motivo de orientación no debe superar los 1000 caracteres.',
        ]);

        try {
            $cita = $this->citaService->solicitarCita(
                alumnoUser: Auth::user(),
                profesionalId: (int) $request->input('profesional_id'),
                fecha: $request->input('fecha_cita'),
                horaInicio: $request->input('hora_inicio'),
                modalidad: $request->input('modalidad'),
                motivo: $request->input('motivo_consulta'),
                ip: $request->ip()
            );

            // Notificar al orientador asignado sobre la nueva solicitud
            $cita->load('profesional.usuario');
            if ($cita->profesional?->usuario) {
                $cita->profesional->usuario->notify(new \App\Notifications\SolicitudCitaNotification(
                    alumnoNombre: Auth::user()->nombre . ' ' . Auth::user()->apellido_paterno,
                    fechaCita: $cita->fecha_cita ? $cita->fecha_cita->format('d/m/Y') : 'programada',
                    horaInicio: substr($cita->hora_inicio, 0, 5),
                    modalidad: $cita->modalidad,
                    citaId: $cita->cita_id
                ));
            }

            return redirect()
                ->route('alumno.citas.show', $cita->cita_id)
                ->with('success', '¡Tu solicitud de cita ha sido registrada con éxito! El orientador la revisará a la brevedad.');
        } catch (\DomainException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->with('error', 'Ocurrió un error inesperado al procesar tu solicitud. Por favor intenta nuevamente.');
        }
    }

    /**
     * Muestra el detalle de una cita específica.
     */
    public function show(int $citaId)
    {
        $cita = CitaOrientacion::with(['profesional.usuario', 'resueltoPorUsuario'])->findOrFail($citaId);

        Gate::authorize('view', $cita);

        return view('alumno.citas.show', compact('cita'));
    }

    /**
     * Cancela una cita voluntariamente por el estudiante.
     */
    public function cancelar(Request $request, int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);

        Gate::authorize('cancelByStudent', $cita);

        $request->validate([
            'motivo' => 'nullable|string|max:255',
        ]);

        try {
            $this->citaService->cancelarPorAlumno($cita, Auth::user(), $request->input('motivo'));

            $cita->load('profesional.usuario');
            if ($cita->profesional?->usuario) {
                $cita->profesional->usuario->notify(new \App\Notifications\CitaActualizadaNotification(
                    'CANCELADA_POR_ALUMNO',
                    $cita->fecha_cita ? $cita->fecha_cita->format('d/m/Y') : 'programada',
                    $request->input('motivo')
                ));
            }

            return redirect()
                ->route('alumno.citas.index')
                ->with('success', 'La cita fue cancelada exitosamente y el espacio ha quedado liberado.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Procesa la solicitud de reprogramación enviada por el estudiante.
     */
    public function solicitarReprogramacion(Request $request, int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);

        if ($cita->estudiante_id !== Auth::id()) {
            abort(403, 'No tienes autorización para reprogramar esta cita.');
        }

        $request->validate([
            'nueva_fecha' => 'required|date|after_or_equal:today',
            'nueva_hora_inicio' => 'required|string',
            'motivo' => 'nullable|string|max:500',
        ], [
            'nueva_fecha.required' => 'Debes seleccionar una fecha para la reprogramación.',
            'nueva_fecha.after_or_equal' => 'La fecha no puede ser en el pasado.',
            'nueva_hora_inicio.required' => 'Debes seleccionar un horario disponible.',
            'motivo.max' => 'El motivo no debe exceder los 500 caracteres.',
        ]);

        try {
            $this->citaService->solicitarReprogramacion(
                cita: $cita,
                alumnoUser: Auth::user(),
                nuevaFecha: Carbon::parse($request->input('nueva_fecha')),
                horaInicio: $request->input('nueva_hora_inicio'),
                motivo: $request->input('motivo')
            );

            return redirect()
                ->route('alumno.citas.show', $cita->cita_id)
                ->with('success', '¡Tu solicitud de reprogramación ha sido enviada con éxito! El orientador la evaluará conservando tu cita actual hasta su resolución.');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'Ocurrió un error inesperado al procesar la reprogramación.');
        }
    }

    /**
     * Cancela una solicitud de reprogramación pendiente, manteniendo la cita original intacta.
     */
    public function cancelarReprogramacion(int $citaId)
    {
        $cita = CitaOrientacion::findOrFail($citaId);

        if ($cita->estudiante_id !== Auth::id()) {
            abort(403, 'No tienes autorización para gestionar esta cita.');
        }

        try {
            $this->citaService->cancelarReprogramacion($cita, Auth::user());

            return redirect()
                ->route('alumno.citas.show', $cita->cita_id)
                ->with('success', 'La solicitud de reprogramación ha sido cancelada. Tu cita original continúa vigente.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

