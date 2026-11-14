<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocenteAsignacion;
use App\Models\Encuesta;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaCatalogo;
use App\Models\User;
use App\Services\Respaldos\RespaldoDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Dashboard principal del Administrador.
     */
    public function index(Request $request)
    {
        $admin = Auth::user();

        // 1. Métricas de usuarios y solicitudes
        $solicitudesPendientes = User::query()
            ->whereHas('rol', fn ($q) => $q->where('nombre', 'Alumno'))
            ->where('estado', 'PENDIENTE')
            ->count();

        $alumnosActivos = User::query()
            ->whereHas('rol', fn ($q) => $q->where('nombre', 'Alumno'))
            ->where('estado', 'ACTIVO')
            ->count();

        $solicitudesRechazadas = User::query()
            ->whereHas('rol', fn ($q) => $q->where('nombre', 'Alumno'))
            ->where('estado', 'RECHAZADO')
            ->count();

        $docentesActivos = User::query()
            ->whereHas('rol', fn ($q) => $q->where('nombre', 'Docente'))
            ->where('estado', 'ACTIVO')
            ->count();

        // 2. Métricas de estructura y operación institucional
        $carrerasActivas = ProgramaEducativo::query()
            ->where('activo', true)
            ->count();

        $periodoVigente = app(\App\Services\Academico\PeriodoOperativoService::class)->resolverPeriodoOperativo();

        $encuestasPublicadas = Encuesta::query()
            ->where('estado', Encuesta::ESTADO_PUBLICADA)
            ->count();

        $recompensasActivas = RecompensaCatalogo::query()
            ->where('activo', true)
            ->count();

        $respaldoService = app(RespaldoDatabaseService::class);
        $totalRespaldos = count($respaldoService->listarRespaldos());

        // 3. Métricas académicas y cátedras
        $solicitudesCatedraPendientes = 0;
        try {
            if (Schema::hasTable('docente_asignaciones')) {
                if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                    $solicitudesCatedraPendientes = DocenteAsignacion::where('estado', 'PENDIENTE')->count();
                } else {
                    $solicitudesCatedraPendientes = DocenteAsignacion::where('activo', false)->count();
                }
            }
        } catch (\Throwable) {
            $solicitudesCatedraPendientes = 0;
        }

        return view('admin.dashboard', [
            'admin' => $admin,
            'metricas' => [
                'solicitudes_pendientes' => $solicitudesPendientes,
                'alumnos_activos' => $alumnosActivos,
                'solicitudes_rechazadas' => $solicitudesRechazadas,
                'docentes_activos' => $docentesActivos,
                'carreras_activas' => $carrerasActivas,
                'periodo_vigente' => $periodoVigente,
                'encuestas_publicadas' => $encuestasPublicadas,
                'recompensas_activas' => $recompensasActivas,
                'total_respaldos' => $totalRespaldos,
                'solicitudes_catedra_pendientes' => $solicitudesCatedraPendientes,
            ],
        ]);
    }
}
