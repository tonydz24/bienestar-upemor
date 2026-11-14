<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\InstrumentoEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Services\Reportes\ExcelReporteService;
use App\Services\Reportes\ReporteInstitucionalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de Emisión y Exportación de Reportes Institucionales.
 *
 * Administra los 6 reportes analíticos de Bienestar UPEMOR, visualización de métricas
 * y gráficos estadísticos, y exportación de archivos estructurados en formatos PDF y Excel (XLSX).
 */
class ReporteController extends Controller
{
    /**
     * Inicializa los servicios requeridos para la consolidación analítica y exportación de reportes.
     *
     * @param ReporteInstitucionalService $reporteService Servicio de agregación y lógica de negocio para los 6 reportes.
     * @param ExcelReporteService $excelService Servicio de generación OpenXML de hojas de cálculo con gráficos.
     */
    public function __construct(
        protected ReporteInstitucionalService $reporteService,
        protected ExcelReporteService $excelService
    ) {}

    /**
     * Centro de Reportes Institucionales (FN.15).
     */
    public function index(): View
    {
        $catalogos = [
            'emociones' => Emocion::orderBy('nombre')->get(),
            'programas' => ProgramaEducativo::orderBy('nombre')->get(),
            'instrumentos' => InstrumentoEvaluacion::where('activo', true)->get(),
            'recompensas' => RecompensaCatalogo::orderBy('nombre')->get(),
            'roles' => Rol::all(),
            'estudiantes' => Estudiante::with(['usuario', 'programaEducativo'])
                ->join('users', 'estudiantes.usuario_id', '=', 'users.usuario_id')
                ->orderBy('users.apellido_paterno')
                ->orderBy('users.nombre')
                ->select('estudiantes.*')
                ->get(),
        ];

        return view('admin.reportes.index', compact('catalogos'));
    }

    /**
     * Vista interactiva y filtrada de un reporte.
     */
    public function show(Request $request, string $tipo): View|RedirectResponse
    {
        $filtros = $request->all();

        // En la vista web de historial-alumno, preseleccionar automáticamente el primer estudiante con actividad
        // si no se envió explícitamente ningún estudiante, para evitar mostrar una página vacía
        if ($tipo === 'historial-alumno' && empty($filtros['estudiante_id'])) {
            $primerConActividad = Estudiante::whereHas('usuario.bitacoras')
                ->orWhereHas('usuario.citasEstudiante')
                ->first() ?? Estudiante::first();
            if ($primerConActividad) {
                $filtros['estudiante_id'] = $primerConActividad->usuario_id;
            }
        }

        $data = match ($tipo) {
            'emociones-registradas' => $this->reporteService->reporteEmocionesRegistradas($filtros),
            'emociones-por-programa' => $this->reporteService->reporteEmocionesPorPrograma($filtros),
            'historial-alumno' => $this->reporteService->reporteHistorialAlumno($filtros),
            'premios-canjeados' => $this->reporteService->reportePremiosCanjeados($filtros),
            'sesiones-por-dia' => $this->reporteService->reporteSesionesPorDia($filtros),
            'resultados-tests' => $this->reporteService->reporteResultadosTests($filtros),
            default => null,
        };

        if (!$data) {
            return redirect()->route('admin.reportes.index')
                ->with('error', 'El tipo de reporte solicitado no es válido.');
        }

        $catalogos = [
            'emociones' => Emocion::orderBy('nombre')->get(),
            'programas' => ProgramaEducativo::orderBy('nombre')->get(),
            'instrumentos' => InstrumentoEvaluacion::where('activo', true)->get(),
            'recompensas' => RecompensaCatalogo::orderBy('nombre')->get(),
            'roles' => Rol::all(),
            'estudiantes' => Estudiante::with(['usuario', 'programaEducativo'])
                ->join('users', 'estudiantes.usuario_id', '=', 'users.usuario_id')
                ->orderBy('users.apellido_paterno')
                ->orderBy('users.nombre')
                ->select('estudiantes.*')
                ->get(),
        ];

        return view('admin.reportes.show', compact('tipo', 'data', 'catalogos', 'filtros'));
    }

    /**
     * Exportación de reporte a PDF institucional.
     */
    public function exportarPdf(Request $request, string $tipo): Response|RedirectResponse
    {
        $filtros = $request->all();

        if ($tipo === 'historial-alumno' && empty($filtros['estudiante_id'])) {
            $primerConActividad = Estudiante::whereHas('usuario.bitacoras')
                ->orWhereHas('usuario.citasEstudiante')
                ->first() ?? Estudiante::first();
            if ($primerConActividad) {
                $filtros['estudiante_id'] = $primerConActividad->usuario_id;
            }
        }

        $datosReporte = match ($tipo) {
            'emociones-registradas' => $this->reporteService->reporteEmocionesRegistradas($filtros),
            'emociones-por-programa' => $this->reporteService->reporteEmocionesPorPrograma($filtros),
            'historial-alumno' => $this->reporteService->reporteHistorialAlumno($filtros),
            'premios-canjeados' => $this->reporteService->reportePremiosCanjeados($filtros),
            'sesiones-por-dia' => $this->reporteService->reporteSesionesPorDia($filtros, false),
            'resultados-tests' => $this->reporteService->reporteResultadosTests($filtros),
            default => null,
        };

        if (!$datosReporte) {
            return redirect()->route('admin.reportes.index')->with('error', 'Reporte no válido.');
        }

        $nombreArchivo = 'reporte_' . str_replace('-', '_', $tipo) . '_' . date('Ymd_His');
        $orientacion = in_array($tipo, ['emociones-por-programa', 'resultados-tests']) ? 'landscape' : 'portrait';

        try {
            return $this->reporteService->exportarPdf(
                "admin.reportes.pdf.{$tipo}",
                compact('datosReporte'),
                $nombreArchivo,
                $orientacion
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error al generar PDF de reporte {$tipo}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un problema al procesar el archivo PDF del reporte. Por favor intenta nuevamente o utiliza la consulta interactiva.');
        }
    }

    /**
     * Exportación de reporte a Excel nativo (.xlsx) con paleta institucional UPEMOR.
     */
    public function exportarExcel(Request $request, string $tipo): BinaryFileResponse|RedirectResponse
    {
        $filtros = $request->all();

        if ($tipo === 'historial-alumno' && empty($filtros['estudiante_id'])) {
            $primerConActividad = Estudiante::whereHas('usuario.bitacoras')
                ->orWhereHas('usuario.citasEstudiante')
                ->first() ?? Estudiante::first();
            if ($primerConActividad) {
                $filtros['estudiante_id'] = $primerConActividad->usuario_id;
            }
        }

        $nombreArchivo = 'reporte_' . str_replace('-', '_', $tipo) . '_' . date('Ymd_His');

        try {
            return match ($tipo) {
                'emociones-registradas' => $this->excelService->exportarEmocionesRegistradas(
                    $this->reporteService->reporteEmocionesRegistradas($filtros),
                    $nombreArchivo
                ),
                'emociones-por-programa' => $this->excelService->exportarEmocionesPorPrograma(
                    $this->reporteService->reporteEmocionesPorPrograma($filtros),
                    $nombreArchivo
                ),
                'historial-alumno' => $this->excelService->exportarHistorialAlumno(
                    $this->reporteService->reporteHistorialAlumno($filtros),
                    $nombreArchivo
                ),
                'premios-canjeados' => $this->excelService->exportarPremiosCanjeados(
                    $this->reporteService->reportePremiosCanjeados($filtros),
                    $nombreArchivo
                ),
                'sesiones-por-dia' => $this->excelService->exportarSesionesPorDia(
                    $this->reporteService->reporteSesionesPorDia($filtros, false),
                    $nombreArchivo,
                    $request->input('rol_nombre')
                ),
                'resultados-tests' => $this->excelService->exportarResultadosTests(
                    $this->reporteService->reporteResultadosTests($filtros),
                    $nombreArchivo
                ),
                default => redirect()->route('admin.reportes.index')->with('error', 'Tipo de reporte inválido para exportación.'),
            };
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error al exportar reporte {$tipo} a Excel: " . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al generar el archivo Excel del reporte: ' . $e->getMessage());
        }
    }
}
