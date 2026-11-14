<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocenteAsignacion;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Services\Academico\CatedraAcademicaService;
use App\Services\Academico\PeriodoOperativoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controlador de Asignaciones y Solicitudes Docentes de Cátedra.
 *
 * Administra la vinculación de profesores con asignaturas y grupos académicos
 * por periodo operativo, así como la aprobación o rechazo de solicitudes docentes.
 */
class DocenteAsignacionAdminController extends Controller
{
    /**
     * Inicializa las dependencias de servicios para gestión de periodos y cátedras.
     *
     * @param PeriodoOperativoService $periodoService Servicio para resolución de periodos consultables y activos.
     * @param CatedraAcademicaService $catedraService Servicio para reglas de negocio de asignaciones docentes.
     */
    public function __construct(
        protected PeriodoOperativoService $periodoService,
        protected CatedraAcademicaService $catedraService
    ) {}

    /**
     * Muestra las asignaciones docentes y solicitudes de cátedra pendientes de resolución.
     *
     * @param Request $request Petición HTTP con filtros opcionales (periodo_id, programa_id, estado)
     * @return View Vista con listado paginado y contadores de estado
     */
    public function index(Request $request): View
    {
        $periodos = $this->periodoService->getPeriodosConsultables();
        $programas = ProgramaEducativo::where('activo', true)->orderBy('nombre')->get();

        $periodoOperativo = $this->periodoService->resolverPeriodoOperativo();
        $periodoSeleccionadoId = $request->input('periodo_id') ?? $periodoOperativo?->periodo_id;
        $estadoFiltro = $request->input('estado', 'PENDIENTE');

        $query = DocenteAsignacion::with(['docenteUsuario', 'asignatura.programa', 'grupo', 'periodo', 'resolutor']);

        if ($periodoSeleccionadoId) {
            $query->where('periodo_id', $periodoSeleccionadoId);
        }

        if ($request->filled('programa_id')) {
            $query->whereHas('asignatura', fn ($q) => $q->where('programa_id', $request->input('programa_id')));
        }

        if (Schema::hasColumn('docente_asignaciones', 'estado')) {
            if ($estadoFiltro !== 'TODOS') {
                $query->where('estado', $estadoFiltro);
            }
        } else {
            // Fallback robusto cuando la columna física estado está ausente en MariaDB sandbox
            if ($estadoFiltro === 'PENDIENTE') {
                $query->where('activo', false);
            } elseif ($estadoFiltro === 'APROBADA') {
                $query->where('activo', true);
            }
        }

        $asignaciones = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $totales = [
            'pendientes' => Schema::hasColumn('docente_asignaciones', 'estado')
                ? DocenteAsignacion::where('estado', DocenteAsignacion::ESTADO_PENDIENTE)->count()
                : DocenteAsignacion::where('activo', false)->count(),
            'aprobadas' => Schema::hasColumn('docente_asignaciones', 'estado')
                ? DocenteAsignacion::where('estado', DocenteAsignacion::ESTADO_APROBADA)->count()
                : DocenteAsignacion::where('activo', true)->count(),
            'rechazadas' => Schema::hasColumn('docente_asignaciones', 'estado')
                ? DocenteAsignacion::where('estado', DocenteAsignacion::ESTADO_RECHAZADA)->count()
                : 0,
        ];

        return view('admin.academico.docente-asignaciones.index', compact('asignaciones', 'periodos', 'programas', 'periodoSeleccionadoId', 'estadoFiltro', 'totales'));
    }

    /**
     * Aprueba la solicitud docente de asignación de asignatura.
     *
     * Si la modalidad de alcance es TODOS, aprueba de forma atómica el lote
     * vinculado. Si es ESPECIFICO, aprueba exclusivamente la sección seleccionada.
     *
     * @param Request $request Petición HTTP con motivo opcional
     * @param int $asignacionId Identificador de la asignación a aprobar
     * @return RedirectResponse Redirección con mensaje descriptivo de confirmación
     */
    public function aprobar(Request $request, int $asignacionId): RedirectResponse
    {
        try {
            $res = $this->catedraService->aprobarCatedra($asignacionId, Auth::user(), $request->input('motivo_resolucion'));
            $asignacion = $res['asignacion'];
            $esTodos = $res['es_alcance_todos'] ?? false;

            $mensaje = $esTodos
                ? "La solicitud de cátedra con alcance general (TODOS) del docente {$asignacion->docenteUsuario?->nombre} y sus {$res['total_aprobadas']} subgrupos vinculados han sido aprobados exitosamente."
                : "La solicitud de cátedra del docente {$asignacion->docenteUsuario?->nombre} para la sección {$asignacion->grupo?->nombre_completo} ha sido aprobada individualmente con éxito.";

            return redirect()->back(fallback: route('admin.docente-asignaciones.index'))
                ->with('status', $mensaje);
        } catch (\Throwable $e) {
            return redirect()->back(fallback: route('admin.docente-asignaciones.index'))
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Rechaza la solicitud docente de asignación de asignatura.
     *
     * Si la modalidad de alcance es TODOS, rechaza en lote. Si es ESPECIFICO,
     * rechaza exclusivamente la sección seleccionada sin alterar otros grupos.
     *
     * @param Request $request Petición HTTP con motivo obligatorio de rechazo
     * @param int $asignacionId Identificador de la asignación a rechazar
     * @return RedirectResponse Redirección con mensaje de advertencia
     */
    public function rechazar(Request $request, int $asignacionId): RedirectResponse
    {
        $request->validate([
            'motivo_resolucion' => 'required|string|max:500',
        ], [
            'motivo_resolucion.required' => 'Debes ingresar un motivo u observación para el rechazo de la solicitud.',
        ]);

        try {
            $res = $this->catedraService->rechazarCatedra($asignacionId, Auth::user(), $request->input('motivo_resolucion'));
            $asignacion = $res['asignacion'];
            $esTodos = $res['es_alcance_todos'] ?? false;

            $mensaje = $esTodos
                ? "La solicitud con alcance general (TODOS) del docente {$asignacion->docenteUsuario?->nombre} ha sido rechazada para todos sus subgrupos."
                : "La solicitud del docente {$asignacion->docenteUsuario?->nombre} para la sección {$asignacion->grupo?->nombre_completo} ha sido rechazada individualmente.";

            return redirect()->back(fallback: route('admin.docente-asignaciones.index'))
                ->with('warning', $mensaje);
        } catch (\Throwable $e) {
            return redirect()->back(fallback: route('admin.docente-asignaciones.index'))
                ->with('error', $e->getMessage());
        }
    }
}
