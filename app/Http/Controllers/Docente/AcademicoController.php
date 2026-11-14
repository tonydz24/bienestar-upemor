<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\DocenteAsignacion;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\User;
use App\Services\Academico\CatedraAcademicaService;
use App\Services\Academico\PeriodoOperativoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controlador de Gestión Académica y Cátedras para el Portal Docente.
 *
 * Permite a los profesores consultar sus asignaciones de cátedra en el periodo activo,
 * postular solicitudes de asignaturas a grupos institucionales y gestionar las listas de alumnos.
 */
class AcademicoController extends Controller
{
    /**
     * Inicializa el controlador con los servicios de periodos operativos y cátedras docentes.
     *
     * @param PeriodoOperativoService $periodoService Servicio para resolución de periodos consultables y vigentes.
     * @param CatedraAcademicaService $catedraService Servicio de validación y registro de asignaciones docentes.
     */
    public function __construct(
        protected PeriodoOperativoService $periodoService,
        protected CatedraAcademicaService $catedraService
    ) {}

    /**
     * Muestra las asignaturas y grupos asignados al docente autenticado en el periodo activo,
     * así como el seguimiento de sus solicitudes de cátedra.
     */
    public function index(): View|RedirectResponse
    {
        if (!Schema::hasTable('docente_asignaciones') || !Schema::hasTable('periodos_academicos')) {
            return redirect()->route('docente.dashboard')->with('info', 'El módulo académico se encuentra en preparación.');
        }

        $usuario = Auth::user();
        $periodoActivo = $this->periodoService->resolverPeriodoOperativo();

        $queryBase = DocenteAsignacion::with(['asignatura.programa', 'grupo.programa', 'periodo', 'recompensasActivadas'])
            ->where('docente_id', $usuario->usuario_id)
            ->when($periodoActivo, fn ($q) => $q->where('periodo_id', $periodoActivo->periodo_id));

        if (Schema::hasColumn('docente_asignaciones', 'estado')) {
            $asignaciones = (clone $queryBase)
                ->where('estado', DocenteAsignacion::ESTADO_APROBADA)
                ->where('activo', true)
                ->get();

            $solicitudesPendientes = (clone $queryBase)
                ->where('estado', DocenteAsignacion::ESTADO_PENDIENTE)
                ->orderByDesc('created_at')
                ->get();

            $solicitudesRechazadas = (clone $queryBase)
                ->where('estado', DocenteAsignacion::ESTADO_RECHAZADA)
                ->orderByDesc('created_at')
                ->get();
        } else {
            // CORRECCIÓN DETERMINISTA:
            // Si la columna física 'estado' no existe aún en MariaDB sandbox (migración 32 pendiente),
            // las filas con activo = 0 representan las solicitudes recién enviadas que esperan resolución.
            $asignaciones = (clone $queryBase)->where('activo', true)->get();
            $solicitudesPendientes = (clone $queryBase)->where('activo', false)->orderByDesc('created_at')->get();
            $solicitudesRechazadas = collect();
        }

        // Consolidación por asignatura: Una tarjeta por materia agrupando subgrupos
        $asignaturasConsolidadas = $asignaciones->groupBy('asignatura_id')->map(function ($items) {
            $primera = $items->first();
            return [
                'asignatura' => $primera->asignatura,
                'subgrupos' => $items,
                'total_subgrupos' => $items->count(),
                'total_recompensas' => $items->flatMap->recompensasActivadas->where('estado', 'ACTIVA')->count(),
            ];
        })->values();

        return view('docente.academico.index', compact('periodoActivo', 'asignaciones', 'asignaturasConsolidadas', 'solicitudesPendientes', 'solicitudesRechazadas'));
    }

    /**
     * Muestra el formulario para solicitar la impartición de una asignatura.
     * Excluye del catálogo aquellas materias que el docente ya tenga aprobadas o en trámite en este periodo.
     */
    public function createSolicitud(): View|RedirectResponse
    {
        $periodoActivo = $this->periodoService->resolverPeriodoOperativo();
        if (!$periodoActivo) {
            return redirect()->route('docente.academico.index')->with('warning', 'No existe un periodo académico activo para registrar solicitudes.');
        }

        $usuario = Auth::user();

        // Identificar asignaturas que ya cubren todos los grupos o tienen alcance global (TODOS) para este docente en el periodo
        $asignacionesDocente = DocenteAsignacion::where('docente_id', $usuario->usuario_id)
            ->where('periodo_id', $periodoActivo->periodo_id)
            ->where(function ($q) {
                $q->where('activo', true);
                if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                    $q->orWhereIn('estado', [DocenteAsignacion::ESTADO_APROBADA, DocenteAsignacion::ESTADO_PENDIENTE]);
                }
            })
            ->get();

        $gruposDelPeriodo = Grupo::where('periodo_id', $periodoActivo->periodo_id)
            ->where('activo', true)
            ->get();

        $materiasTotalmenteCubiertasIds = collect();
        foreach ($asignacionesDocente->groupBy('asignatura_id') as $asigId => $asigsMateria) {
            $tieneTodos = $asigsMateria->contains(fn ($a) => (
                (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance') && $a->modalidad_alcance === 'TODOS')
                || is_null($a->grupo_id)
            ));

            if ($tieneTodos) {
                $materiasTotalmenteCubiertasIds->push($asigId);
                continue;
            }

            $asigObj = Asignatura::find($asigId);
            if ($asigObj) {
                $gruposTotales = $gruposDelPeriodo->where('programa_id', $asigObj->programa_id)
                    ->where('cuatrimestre', $asigObj->cuatrimestre);
                $gruposCubiertos = $asigsMateria->pluck('grupo_id')->filter()->unique();

                if ($gruposTotales->isNotEmpty() && $gruposTotales->pluck('grupo_id')->diff($gruposCubiertos)->isEmpty()) {
                    $materiasTotalmenteCubiertasIds->push($asigId);
                }
            }
        }

        $asignaturasYaRegistradasIds = $materiasTotalmenteCubiertasIds;

        $programas = ProgramaEducativo::where('activo', true)->orderBy('nombre')->get();
        $planes = PlanEstudio::where('es_vigente', true)->orWhere('estado', 'VIGENTE')->orderBy('nombre')->get();

        // Excluir únicamente materias totalmente cubiertas
        $asignaturas = Asignatura::with(['programa', 'plan'])
            ->where('activo', true)
            ->whereNotIn('asignatura_id', $materiasTotalmenteCubiertasIds)
            ->orderBy('cuatrimestre')
            ->orderBy('nombre')
            ->get();

        $grupos = Grupo::with('programa')
            ->where('periodo_id', $periodoActivo->periodo_id)
            ->where('activo', true)
            ->orderBy('cuatrimestre')
            ->orderBy('nombre')
            ->get();

        return view('docente.academico.solicitar', compact('periodoActivo', 'programas', 'planes', 'asignaturas', 'grupos', 'asignaturasYaRegistradasIds'));
    }

    /**
     * Almacena una nueva solicitud de impartición de asignatura enviada por el docente.
     * Utiliza el servicio de cátedra para evitar multiplicaciones innecesarias de solicitudes administrativas.
     */
    public function storeSolicitud(Request $request): RedirectResponse
    {
        $periodoActivo = $this->periodoService->resolverPeriodoOperativo();
        if ($periodoActivo && !$request->filled('periodo_id')) {
            $request->merge(['periodo_id' => $periodoActivo->periodo_id]);
        }

        $modalidad = $request->input('modalidad_apertura', 'todos');
        if ($request->input('grupo_id') === 'todos') {
            $modalidad = 'todos';
        }

        if ($modalidad === 'especifico') {
            $request->validate([
                'asignatura_id' => 'required|integer|exists:asignaturas,asignatura_id',
                'grupo_id' => 'required|integer|exists:grupos,grupo_id',
                'periodo_id' => 'required|integer|exists:periodos_academicos,periodo_id',
                'motivo_solicitud' => 'nullable|string|max:500',
            ], [
                'asignatura_id.required' => 'Debes seleccionar la asignatura que deseas impartir.',
                'grupo_id.required' => 'Debes indicar el grupo académico al que impartirás la materia.',
                'periodo_id.required' => 'El periodo académico es obligatorio.',
            ]);
        } else {
            $request->validate([
                'asignatura_id' => 'required|integer|exists:asignaturas,asignatura_id',
                'periodo_id' => 'required|integer|exists:periodos_academicos,periodo_id',
                'motivo_solicitud' => 'nullable|string|max:500',
            ], [
                'asignatura_id.required' => 'Debes seleccionar la asignatura que deseas impartir.',
                'periodo_id.required' => 'El periodo académico es obligatorio.',
            ]);
        }

        $usuario = Auth::user();
        $asignatura = Asignatura::findOrFail($request->input('asignatura_id'));
        $periodo = PeriodoAcademico::findOrFail((int) $request->input('periodo_id'));

        try {
            $resultado = $this->catedraService->solicitarCatedra(
                $usuario,
                $asignatura,
                $periodo,
                $modalidad,
                $modalidad === 'especifico' ? (int) $request->input('grupo_id') : null,
                $request->input('motivo_solicitud')
            );

            return redirect()->route('docente.academico.index')
                ->with('status', "Tu solicitud para impartir la asignatura '{$asignatura->nombre}' ({$resultado['grupos']}) ha sido enviada a la Dirección para su revisión.");
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Muestra los estudiantes inscritos en el grupo asignado y las solicitudes de inscripción a la materia.
     * Mantiene estricta separación de datos confidenciales (cero datos clínicos/emocionales).
     */
    public function grupo(int $asignacionId): View
    {
        $asignacion = DocenteAsignacion::with(['asignatura.programa', 'grupo.programa', 'periodo'])
            ->findOrFail($asignacionId);

        Gate::authorize('viewAsignacion', $asignacion);

        $usuario = Auth::user();

        // Obtener todos los subgrupos asignados al docente para esta misma asignatura en el periodo
        $subgruposAsignados = DocenteAsignacion::with(['asignatura.programa', 'grupo.programa', 'periodo'])
            ->where('docente_id', $usuario->usuario_id)
            ->where('asignatura_id', $asignacion->asignatura_id)
            ->where('periodo_id', $asignacion->periodo_id)
            ->where('activo', true)
            ->orderBy('grupo_id')
            ->get();

        if ($subgruposAsignados->isEmpty()) {
            $subgruposAsignados = collect([$asignacion]);
        }

        // Estructurar datos específicos por cada subgrupo
        $datosPorSubgrupo = $subgruposAsignados->map(function ($subAsig) {
            $inscripcionesGrupo = EstudianteInscripcion::with(['estudianteUsuario.estudiante.programa'])
                ->where('grupo_id', $subAsig->grupo_id)
                ->where('periodo_id', $subAsig->periodo_id)
                ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
                ->get();

            $solicitudesMateria = collect();
            $inscritosMateria = collect();

            if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
                $solicitudesMateria = EstudianteAsignaturaInscripcion::with(['estudianteUsuario.estudiante.programa'])
                    ->where('asignacion_id', $subAsig->asignacion_id)
                    ->where('estado', EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE)
                    ->orderByDesc('solicitado_en')
                    ->get();

                $inscritosMateria = EstudianteAsignaturaInscripcion::with(['estudianteUsuario.estudiante.programa'])
                    ->where('asignacion_id', $subAsig->asignacion_id)
                    ->where('estado', EstudianteAsignaturaInscripcion::ESTADO_APROBADA)
                    ->orderBy('created_at')
                    ->get();
            }

            return [
                'asignacion' => $subAsig,
                'grupo' => $subAsig->grupo,
                'inscripcionesGrupo' => $inscripcionesGrupo,
                'solicitudesMateria' => $solicitudesMateria,
                'inscritosMateria' => $inscritosMateria,
            ];
        });

        $asignacionActualData = $datosPorSubgrupo->firstWhere('asignacion.asignacion_id', $asignacionId) ?? $datosPorSubgrupo->first();
        $inscripcionesGrupo = $asignacionActualData['inscripcionesGrupo'];
        $solicitudesMateria = $asignacionActualData['solicitudesMateria'];
        $inscritosMateria = $asignacionActualData['inscritosMateria'];

        return view('docente.academico.grupo', compact(
            'asignacion',
            'subgruposAsignados',
            'datosPorSubgrupo',
            'inscripcionesGrupo',
            'solicitudesMateria',
            'inscritosMateria'
        ));
    }

    /**
     * Aprueba la inscripción de un estudiante a la asignatura del docente de manera transaccional e idempotente.
     */
    public function aprobarInscripcion(Request $request, int $inscripcionMateriaId): RedirectResponse
    {
        if (!Schema::hasTable('estudiante_asignatura_inscripciones')) {
            return redirect()->back()->with('error', 'La función de inscripción a asignaturas aún no está disponible.');
        }

        try {
            $resultado = $this->catedraService->resolverInscripcionEstudiante(
                $inscripcionMateriaId,
                Auth::user(),
                EstudianteAsignaturaInscripcion::ESTADO_APROBADA,
                $request->input('motivo_resolucion')
            );

            if ($resultado['ya_resuelta']) {
                return redirect()->back(fallback: route('docente.academico.index'))
                    ->with('info', 'Esta solicitud de inscripción ya había sido resuelta previamente.');
            }

            $inscripcion = $resultado['inscripcion'];
            return redirect()->back(fallback: route('docente.academico.index'))
                ->with('status', "La inscripción del alumno {$inscripcion->estudianteUsuario?->nombre} ha sido aprobada exitosamente.");
        } catch (\Throwable $e) {
            return redirect()->back(fallback: route('docente.academico.index'))
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Rechaza la inscripción de un estudiante a la asignatura del docente.
     */
    public function rechazarInscripcion(Request $request, int $inscripcionMateriaId): RedirectResponse
    {
        if (!Schema::hasTable('estudiante_asignatura_inscripciones')) {
            return redirect()->back()->with('error', 'La función de inscripción a asignaturas aún no está disponible.');
        }

        $request->validate([
            'motivo_resolucion' => 'required|string|max:500',
        ], [
            'motivo_resolucion.required' => 'Debes registrar un motivo para el rechazo de la inscripción.',
        ]);

        try {
            $resultado = $this->catedraService->resolverInscripcionEstudiante(
                $inscripcionMateriaId,
                Auth::user(),
                EstudianteAsignaturaInscripcion::ESTADO_RECHAZADA,
                $request->input('motivo_resolucion')
            );

            if ($resultado['ya_resuelta']) {
                return redirect()->back(fallback: route('docente.academico.index'))
                    ->with('info', 'Esta solicitud de inscripción ya había sido resuelta previamente.');
            }

            $inscripcion = $resultado['inscripcion'];
            return redirect()->back(fallback: route('docente.academico.index'))
                ->with('warning', "La inscripción del alumno {$inscripcion->estudianteUsuario?->nombre} ha sido rechazada.");
        } catch (\Throwable $e) {
            return redirect()->back(fallback: route('docente.academico.index'))
                ->with('error', $e->getMessage());
        }
    }
}
