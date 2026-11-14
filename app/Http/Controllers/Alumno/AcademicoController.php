<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\DocenteAsignacion;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Services\Academico\CatedraAcademicaService;
use App\Services\Academico\EstudianteGrupoResolverService;
use App\Services\Academico\PeriodoOperativoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controlador de Módulo Académico para el Portal del Alumno.
 *
 * Administra la consulta de carga académica, asignaturas inscritas por cuatrimestre,
 * resolución del grupo escolar asignado y envío de solicitudes de inscripción en cátedras.
 */
class AcademicoController extends Controller
{
    /**
     * Inicializa las dependencias de servicios para gestión académica del estudiante.
     *
     * @param PeriodoOperativoService $periodoService Servicio para resolver periodos operativos.
     * @param EstudianteGrupoResolverService $grupoResolverService Servicio de resolución de grupo por cohorte y periodo.
     * @param CatedraAcademicaService $catedraService Servicio de reglas de cátedras y elegibilidad de materias.
     */
    public function __construct(
        protected PeriodoOperativoService $periodoService,
        protected EstudianteGrupoResolverService $grupoResolverService,
        protected CatedraAcademicaService $catedraService
    ) {}

    /**
     * Resuelve el grupo del estudiante para el periodo operativo (mantiene compatibilidad pública/protegida).
     */
    public function resolverGrupoEstudiante($usuario, $periodoActivo): ?Grupo
    {
        if (!$periodoActivo) {
            return null;
        }
        return $this->grupoResolverService->resolverGrupo($usuario, $periodoActivo);
    }

    /**
     * Muestra la vista académica del alumno: Su grupo asignado, materias inscritas y oferta disponible para SU grupo.
     * Estructurada en tres secciones clave:
     * A. Mis asignaturas inscritas
     * B. Solicitudes de inscripción
     * C. Oferta académica disponible agrupada por asignatura
     */
    public function index(): View|RedirectResponse
    {
        $usuario = Auth::user();
        $periodoActivo = $this->periodoService->resolverPeriodoOperativo();

        if (!$periodoActivo) {
            return view('alumno.academico.index', [
                'periodoActivo' => null,
                'miGrupo' => null,
                'materiasInscritas' => collect(),
                'solicitudesPendientes' => collect(),
                'solicitudesOtras' => collect(),
                'ofertaDisponible' => collect(),
                'ofertaAgrupada' => collect(),
                'tablaInscripcionesDisponible' => Schema::hasTable('estudiante_asignatura_inscripciones'),
                'mensaje' => 'No hay un periodo académico activo configurado actualmente.',
            ]);
        }

        $miGrupo = $this->grupoResolverService->resolverGrupo($usuario, $periodoActivo);

        $materiasInscritas = collect();
        $solicitudesPendientes = collect();
        $solicitudesOtras = collect();

        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            $inscripciones = EstudianteAsignaturaInscripcion::with([
                'asignacion.docenteUsuario',
                'asignacion.asignatura',
                'grupo.programa',
                'asignatura.programa',
            ])
                ->where('estudiante_id', $usuario->usuario_id)
                ->where('periodo_id', $periodoActivo->periodo_id)
                ->get();

            $materiasInscritas = $inscripciones->where('estado', EstudianteAsignaturaInscripcion::ESTADO_APROBADA);
            $solicitudesPendientes = $inscripciones->where('estado', EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE);
            $solicitudesOtras = $inscripciones->whereIn('estado', [
                EstudianteAsignaturaInscripcion::ESTADO_RECHAZADA,
                EstudianteAsignaturaInscripcion::ESTADO_CANCELADA,
            ]);
        }

        // Obtener la oferta docente aprobada para el grupo oficial del estudiante
        $ofertaDisponible = ($miGrupo)
            ? $this->catedraService->obtenerOfertaDocenteParaEstudiante($usuario, $miGrupo, $periodoActivo)
            : collect();

        // Agrupación requerida: primero por Asignatura, y dentro los docentes disponibles
        $ofertaAgrupada = $this->catedraService->agruparOfertaPorAsignatura($ofertaDisponible);

        // Materias del plan sin oferta docente en el periodo (Sección 6.3 del Requisito Institucional)
        $materiasSinOferta = ($miGrupo)
            ? $this->catedraService->obtenerMateriasSinOfertaDocente($usuario, $miGrupo, $periodoActivo)
            : collect();

        $estudiante = $usuario->estudiante;
        $tablaInscripcionesDisponible = Schema::hasTable('estudiante_asignatura_inscripciones');

        return view('alumno.academico.index', compact(
            'periodoActivo',
            'miGrupo',
            'estudiante',
            'materiasInscritas',
            'solicitudesPendientes',
            'solicitudesOtras',
            'ofertaDisponible',
            'ofertaAgrupada',
            'materiasSinOferta',
            'tablaInscripcionesDisponible'
        ));
    }

    /**
     * Registra la solicitud de inscripción del estudiante a una materia ofertada para su grupo.
     */
    public function solicitar(Request $request): RedirectResponse
    {
        $request->validate([
            'asignacion_id' => 'required|integer|exists:docente_asignaciones,asignacion_id',
            'motivo_solicitud' => 'nullable|string|max:500',
        ], [
            'asignacion_id.required' => 'Debes seleccionar una materia válida.',
        ]);

        $usuario = Auth::user();
        $periodoActivo = $this->periodoService->resolverPeriodoOperativo();
        if (!$periodoActivo) {
            return redirect()->back()->with('error', 'No existe un periodo académico activo para realizar inscripciones.');
        }

        $miGrupo = $this->grupoResolverService->resolverGrupo($usuario, $periodoActivo);
        if (!$miGrupo) {
            return redirect()->back()->with('error', 'No tienes un grupo académico asignado en este periodo. Contacta a Servicios Escolares.');
        }

        $asignacion = DocenteAsignacion::with(['docenteUsuario', 'asignatura', 'grupo'])->findOrFail($request->input('asignacion_id'));

        // Validación estricta institucional
        $validacion = $this->catedraService->validarElegibilidadSolicitud($usuario, $asignacion, $miGrupo);
        if (!$validacion['valido']) {
            return redirect()->back(fallback: route('alumno.academico.index'))->with('error', $validacion['mensaje']);
        }

        try {
            $inscripcion = $this->catedraService->solicitarInscripcion(
                $usuario,
                $asignacion,
                $miGrupo,
                $request->input('motivo_solicitud')
            );

            return redirect()->route('alumno.academico.index')
                ->with('status', "Has solicitado inscribirte a '{$asignacion->asignatura?->nombre}' con el docente {$asignacion->docenteUsuario?->nombre}. Tu solicitud está en proceso de revisión.");
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error("Error de concurrencia al solicitar inscripción: " . $e->getMessage());
            return redirect()->back(fallback: route('alumno.academico.index'))
                ->with('error', 'No fue posible registrar la solicitud en este momento o ya existe un trámite activo para esta materia. Por favor intenta de nuevo.');
        } catch (\Throwable $e) {
            return redirect()->back(fallback: route('alumno.academico.index'))->with('error', $e->getMessage());
        }
    }

    /**
     * Permite al alumno cancelar una solicitud de inscripción pendiente.
     */
    public function cancelar(Request $request, int $inscripcionMateriaId): RedirectResponse
    {
        if (!Schema::hasTable('estudiante_asignatura_inscripciones')) {
            return redirect()->back()->with('error', 'Función no disponible temporalmente.');
        }

        $inscripcion = EstudianteAsignaturaInscripcion::where('estudiante_id', Auth::id())
            ->where('inscripcion_materia_id', $inscripcionMateriaId)
            ->where('estado', EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE)
            ->firstOrFail();

        $estadoAnterior = $inscripcion->estado;
        $transiciones = $inscripcion->historial_transiciones ?? [];
        $transiciones[] = [
            'fecha' => now()->toIso8601String(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => EstudianteAsignaturaInscripcion::ESTADO_CANCELADA,
            'motivo' => 'Cancelada voluntariamente por el estudiante.',
            'resuelto_por' => Auth::id(),
            'resuelto_en' => now()->toIso8601String(),
        ];

        if (Schema::hasTable('estudiante_asignatura_inscripciones_historial')) {
            try {
                \App\Models\EstudianteAsignaturaInscripcionHistorial::create([
                    'inscripcion_materia_id' => $inscripcion->inscripcion_materia_id,
                    'estudiante_id' => $inscripcion->estudiante_id,
                    'asignacion_id' => $inscripcion->asignacion_id,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => EstudianteAsignaturaInscripcion::ESTADO_CANCELADA,
                    'motivo' => 'Cancelada voluntariamente por el estudiante.',
                    'resuelto_por' => Auth::id(),
                    'resuelto_en' => now(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("No se pudo registrar historial relacional de cancelación de inscripción: {$e->getMessage()}");
            }
        }

        $inscripcion->historial_transiciones = $transiciones;
        $inscripcion->estado = EstudianteAsignaturaInscripcion::ESTADO_CANCELADA;
        $inscripcion->motivo_resolucion = 'Cancelada voluntariamente por el estudiante.';
        $inscripcion->resuelto_por = Auth::id();
        $inscripcion->resuelto_en = now();
        $inscripcion->save();

        return redirect()->route('alumno.academico.index')
            ->with('info', 'La solicitud de inscripción ha sido cancelada exitosamente.');
    }
}
