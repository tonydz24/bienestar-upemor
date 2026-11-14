<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\DocenteAsignacion;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\PeriodoAcademico;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Dashboard académico principal del Docente.
     */
    public function index(Request $request)
    {
        $usuario = Auth::user();
        $docente = $usuario->load('docente');

        // Asignaciones del docente con sus relaciones
        $asignaciones = DocenteAsignacion::with([
            'asignatura.programa',
            'grupo.programa',
            'periodo',
            'recompensasActivadas' => function ($q) {
                $q->where('estado', RecompensaActivada::ESTADO_ACTIVA);
            }
        ])
        ->where('docente_id', $usuario->usuario_id)
        ->where('activo', true)
        ->get();

        $asignacionesIds = $asignaciones->pluck('asignacion_id');
        $gruposIds = $asignaciones->pluck('grupo_id')->unique();

        // 1. Indicadores reales del docente
        $totalAsignaturas = $asignaciones->pluck('asignatura_id')->unique()->count();
        $totalGrupos = $gruposIds->count();

        // Estudiantes inscritos únicos en los grupos atendidos por este docente (sin duplicados)
        $totalEstudiantesUnicos = EstudianteInscripcion::whereIn('grupo_id', $gruposIds)
            ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
            ->distinct('estudiante_id')
            ->count('estudiante_id');

        // Total recompensas activadas activas
        $totalRecompensasHabilitadas = RecompensaActivada::whereIn('asignacion_id', $asignacionesIds)
            ->where('estado', RecompensaActivada::ESTADO_ACTIVA)
            ->count();

        // Beneficios canjeados que esperan validación del docente
        $beneficiosPendientesValidar = RecompensaCanje::whereHas('activacion', function ($q) use ($asignacionesIds) {
            $q->whereIn('asignacion_id', $asignacionesIds);
        })
        ->whereIn('estado', [
            RecompensaCanje::ESTADO_SOLICITADO,
            RecompensaCanje::ESTADO_CANJEADA,
            RecompensaCanje::ESTADO_APROBADO
        ])
        ->count();

        // Propuestas de recompensa enviadas por este docente pendientes de aprobación
        $propuestasPendientes = RecompensaCatalogo::where('docente_solicitante_id', $usuario->usuario_id)
            ->where('estado_propuesta', RecompensaCatalogo::PROPUESTA_PENDIENTE)
            ->count();

        // Conteo de estudiantes por grupo para las tarjetas
        $conteoAlumnosPorGrupo = EstudianteInscripcion::whereIn('grupo_id', $gruposIds)
            ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
            ->groupBy('grupo_id')
            ->select('grupo_id', DB::raw('count(*) as total'))
            ->pluck('total', 'grupo_id');

        // 2. Clasificación temporal de asignaciones por periodo operativo y calendario
        $periodoOperativo = app(\App\Services\Academico\PeriodoOperativoService::class)->resolverPeriodoOperativo();
        $asignacionesVigentes = $asignaciones->filter(fn ($a) => ($periodoOperativo && $a->periodo_id === $periodoOperativo->periodo_id) || $a->periodo?->estaVigentePorCalendario());
        $asignacionesOtras = $asignaciones->filter(fn ($a) => !($periodoOperativo && $a->periodo_id === $periodoOperativo->periodo_id) && !$a->periodo?->estaVigentePorCalendario());

        // 3. Actividad reciente real
        // A) Canjes recientes que requieren validación o fueron resueltos recientemente
        $canjesRecientes = RecompensaCanje::with([
            'activacion.recompensaCatalogo',
            'activacion.asignacion.asignatura',
            'activacion.asignacion.grupo',
            'estudianteUsuario'
        ])
        ->whereHas('activacion', fn ($q) => $q->whereIn('asignacion_id', $asignacionesIds))
        ->latest('updated_at')
        ->limit(6)
        ->get();

        // B) Propuestas resueltas recientemente
        $propuestasResueltas = RecompensaCatalogo::where('docente_solicitante_id', $usuario->usuario_id)
            ->whereIn('estado_propuesta', [RecompensaCatalogo::PROPUESTA_APROBADA, RecompensaCatalogo::PROPUESTA_RECHAZADA])
            ->latest('fecha_resolucion')
            ->limit(4)
            ->get();

        // Solicitudes de inscripción de alumnos a materias impartidas pendientes de aprobación
        $solicitudesInscripcionPendientes = 0;
        try {
            if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
                $solicitudesInscripcionPendientes = EstudianteAsignaturaInscripcion::whereIn('asignacion_id', $asignacionesIds)
                    ->where('estado', 'PENDIENTE')
                    ->count();
            }
        } catch (\Throwable) {
            $solicitudesInscripcionPendientes = 0;
        }

        return view('docente.dashboard', compact(
            'docente',
            'asignaciones',
            'asignacionesVigentes',
            'asignacionesOtras',
            'totalAsignaturas',
            'totalGrupos',
            'totalEstudiantesUnicos',
            'totalRecompensasHabilitadas',
            'beneficiosPendientesValidar',
            'propuestasPendientes',
            'solicitudesInscripcionPendientes',
            'conteoAlumnosPorGrupo',
            'canjesRecientes',
            'propuestasResueltas'
        ));
    }
}
