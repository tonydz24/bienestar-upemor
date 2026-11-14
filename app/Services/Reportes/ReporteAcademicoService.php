<?php

namespace App\Services\Reportes;

use App\Models\Asignatura;
use App\Models\CitaOrientacion;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Inteligencia Institucional y Reportería Académica.
 * 
 * POLÍTICA ESTRICTA DE PRIVACIDAD:
 * Este servicio produce agregaciones numéricas y métricas de gestión escolar,
 * curricular y de incentivos formativos. Bajo ninguna circunstancia incluye,
 * expone o procesa notas clínicas, diagnósticos psicológicos ni registros
 * emocionales individuales de los alumnos.
 */
class ReporteAcademicoService
{
    /**
     * Resumen macro institucional del estado de la plataforma.
     */
    public function resumenInstitucional(): array
    {
        return [
            'total_programas' => ProgramaEducativo::where('activo', true)->count(),
            'total_planes_vigentes' => PlanEstudio::where('es_vigente', true)->count(),
            'total_asignaturas_activas' => Asignatura::where('activo', true)->count(),
            'total_estudiantes_activos' => Estudiante::count(),
            'total_periodos' => PeriodoAcademico::count(),
            'periodo_vigente' => app(\App\Services\Academico\PeriodoOperativoService::class)->resolverPeriodoOperativo()?->clave,
            'total_beneficios_catalogo' => RecompensaCatalogo::where('activo', true)->count(),
            'total_canjes_digitales' => RecompensaCanje::count(),
        ];
    }

    /**
     * Métricas desagregadas por cada programa educativo oficial.
     */
    public function estadisticasPorPrograma(): Collection
    {
        return ProgramaEducativo::with(['planVigente'])
            ->withCount(['estudiantes', 'asignaturas', 'planes', 'grupos'])
            ->orderBy('nombre')
            ->get()
            ->map(function (ProgramaEducativo $prog) {
                return [
                    'programa_id' => $prog->programa_id,
                    'clave' => $prog->clave,
                    'nombre' => $prog->nombre,
                    'plan_vigente' => $prog->planVigente?->codigo ?? 'Sin plan vigente',
                    'total_alumnos' => $prog->estudiantes_count,
                    'total_asignaturas' => $prog->asignaturas_count,
                    'total_planes' => $prog->planes_count,
                    'total_grupos' => $prog->grupos_count,
                    'activo' => (bool) $prog->activo,
                    'archivado' => (bool) $prog->archivado,
                ];
            });
    }

    /**
     * Métricas de efectividad y dinamismo del programa de recompensas digitales.
     */
    public function metricasRecompensas(): array
    {
        $totalCanjes = RecompensaCanje::count();
        $canjeados = RecompensaCanje::where('estado', RecompensaCanje::ESTADO_CANJEADA)->count();
        $utilizados = RecompensaCanje::where('estado', RecompensaCanje::ESTADO_UTILIZADA)->count();
        $rechazados = RecompensaCanje::whereIn('estado', [RecompensaCanje::ESTADO_RECHAZADO, RecompensaCanje::ESTADO_ANULADA])->count();

        $tasaUtilizacion = $totalCanjes > 0 ? round(($utilizados / $totalCanjes) * 100, 1) : 0.0;

        $porCategoria = RecompensaCatalogo::select('categoria', DB::raw('count(*) as total'))
            ->groupBy('categoria')
            ->pluck('total', 'categoria')
            ->toArray();

        $puntosCanjeadosTotal = RecompensaCanje::sum('puntos_canjeados');

        return [
            'total_activaciones_docentes' => RecompensaActivada::where('estado', 'ACTIVA')->count(),
            'total_canjes_emitidos' => $totalCanjes,
            'cupones_pendientes_uso' => $canjeados,
            'cupones_utilizados' => $utilizados,
            'cupones_anulados_o_rechazados' => $rechazados,
            'tasa_utilizacion_pct' => $tasaUtilizacion,
            'puntos_redimidos_historico' => (int) $puntosCanjeadosTotal,
            'distribucion_por_categoria' => $porCategoria,
        ];
    }

    /**
     * Métricas operativas de orientación y acompañamiento estudiantil (completamente anonimizadas).
     */
    public function metricasOrientacion(): array
    {
        $totalCitas = CitaOrientacion::count();
        $citasPorEstado = CitaOrientacion::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $citasPorModalidad = CitaOrientacion::select('modalidad', DB::raw('count(*) as total'))
            ->groupBy('modalidad')
            ->pluck('total', 'modalidad')
            ->toArray();

        $alumnosBeneficiados = CitaOrientacion::distinct('estudiante_id')->count('estudiante_id');

        return [
            'total_citas' => $totalCitas,
            'alumnos_distintos_atendidos' => $alumnosBeneficiados,
            'por_estado' => $citasPorEstado,
            'por_modalidad' => $citasPorModalidad,
        ];
    }

    /**
     * Distribución de matrícula activa por cuatrimestre (1° a 10°).
     */
    public function distribucionPorCuatrimestre(): array
    {
        $distribucion = [];
        for ($i = 1; $i <= 10; $i++) {
            $distribucion[$i] = Estudiante::where('cuatrimestre', $i)->count();
        }

        return $distribucion;
    }
}

