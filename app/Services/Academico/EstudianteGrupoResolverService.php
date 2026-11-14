<?php

namespace App\Services\Academico;

use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstudianteGrupoResolverService
{
    /**
     * Resuelve de forma determinista y consistente el grupo oficial al que pertenece un estudiante en el periodo dado.
     * Corrige y mitiga el escenario diagnosticado de inscripciones múltiples o contradictorias en el mismo periodo.
     */
    public function resolverGrupo(User $usuario, PeriodoAcademico $periodo): ?Grupo
    {
        // 1. Consultar todas las inscripciones activas registradas para el estudiante en el periodo
        $inscripcionesActivas = EstudianteInscripcion::with(['grupo.programa'])
            ->where('estudiante_id', $usuario->usuario_id)
            ->where('periodo_id', $periodo->periodo_id)
            ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
            ->get();

        $estudiante = $usuario->estudiante;

        // CASO A: Existe exactamente una inscripción activa en el periodo
        if ($inscripcionesActivas->count() === 1) {
            $inscripcion = $inscripcionesActivas->first();
            if ($inscripcion->grupo) {
                return $inscripcion->grupo;
            }
        }

        // CASO B: Conflicto de doble/múltiple inscripción activa en el mismo periodo (ej. caso Manuel Antonio)
        if ($inscripcionesActivas->count() > 1) {
            Log::warning("Inconsistencia detectada: El estudiante ID {$usuario->usuario_id} ({$usuario->email}) registra {$inscripcionesActivas->count()} inscripciones activas en el periodo {$periodo->clave}. Iniciando conciliación algorítmica.");

            // Conciliar contra el perfil estudiantil institucional oficial
            if ($estudiante && $estudiante->cuatrimestre && $estudiante->grupo) {
                $grupoPerfilNombre = strtoupper(trim($estudiante->grupo));
                $cuatriPerfil = (int) $estudiante->cuatrimestre;

                // Buscar la inscripción que coincide exactamente con el cuatrimestre y sección nominal del perfil
                $coincidente = $inscripcionesActivas->first(function ($ins) use ($cuatriPerfil, $grupoPerfilNombre, $estudiante) {
                    $grp = $ins->grupo;
                    if (!$grp) {
                        return false;
                    }

                    $mismoCuatri = ((int) $grp->cuatrimestre === $cuatriPerfil);
                    $mismoGrupo = (strtoupper(trim($grp->nombre)) === $grupoPerfilNombre);
                    $mismoProg = (!$estudiante->programa_id || (int) $grp->programa_id === (int) $estudiante->programa_id);

                    return $mismoCuatri && $mismoGrupo && $mismoProg;
                });

                if ($coincidente?->grupo) {
                    return $coincidente->grupo;
                }
            }

            // Fallback secundario en conflicto: grupo del cuatrimestre del perfil, ordenado por fecha de creación más temprana
            $coincidenteCuatri = $inscripcionesActivas->first(function ($ins) use ($estudiante) {
                return $ins->grupo && (int) $ins->grupo->cuatrimestre === (int) $estudiante?->cuatrimestre;
            });

            if ($coincidenteCuatri?->grupo) {
                return $coincidenteCuatri->grupo;
            }

            // Fallback final: primera inscripción válida por fecha ascendente
            $primera = $inscripcionesActivas->sortBy('inscripcion_id')->first();
            if ($primera?->grupo) {
                return $primera->grupo;
            }
        }

        // CASO C: No existe inscripción en estudiante_inscripciones; reconciliar desde el perfil nominal
        if ($estudiante && $estudiante->grupo && $estudiante->programa_id && $estudiante->cuatrimestre) {
            $grupoNombre = strtoupper(trim($estudiante->grupo));

            $grupoOficial = Grupo::with('programa')
                ->where('periodo_id', $periodo->periodo_id)
                ->where('programa_id', $estudiante->programa_id)
                ->where('cuatrimestre', $estudiante->cuatrimestre)
                ->where('nombre', $grupoNombre)
                ->first();

            if (!$grupoOficial) {
                $grupoOficial = Grupo::create([
                    'programa_id' => $estudiante->programa_id,
                    'periodo_id' => $periodo->periodo_id,
                    'cuatrimestre' => $estudiante->cuatrimestre,
                    'nombre' => $grupoNombre,
                    'turno' => 'MATUTINO',
                    'activo' => true,
                ]);
            }

            // Crear la adscripción asegurando que no existan duplicados
            $this->inscribirAGrupo($usuario, $grupoOficial, $periodo);

            return $grupoOficial;
        }

        return null;
    }

    /**
     * Vincula formalmente a un estudiante a un grupo para un periodo dado, garantizando la regla de una sola adscripción activa.
     */
    public function inscribirAGrupo(User $usuario, Grupo $grupo, PeriodoAcademico $periodo): EstudianteInscripcion
    {
        return DB::transaction(function () use ($usuario, $grupo, $periodo) {
            // Dar de baja administrativa cualquier otra inscripción activa del alumno en el mismo periodo (sin simular acreditación CONCLUIDO)
            EstudianteInscripcion::where('estudiante_id', $usuario->usuario_id)
                ->where('periodo_id', $periodo->periodo_id)
                ->where('grupo_id', '!=', $grupo->grupo_id)
                ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
                ->update(['estado' => EstudianteInscripcion::ESTADO_BAJA]);

            // Crear o reactivar la adscripción al grupo oficial indicado
            return EstudianteInscripcion::updateOrCreate([
                'estudiante_id' => $usuario->usuario_id,
                'grupo_id' => $grupo->grupo_id,
                'periodo_id' => $periodo->periodo_id,
            ], [
                'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
            ]);
        });
    }

    /**
     * Detecta y reporta todas las inconsistencias de adscripción múltiple en el sistema.
     * Genera la estructura de datos para el reporte de conciliación sin alterar datos reales en MariaDB.
     */
    public function detectarConflictosInscripciones(?int $periodoId = null): array
    {
        $query = EstudianteInscripcion::with(['estudianteUsuario.estudiante.programa', 'grupo.programa', 'periodo'])
            ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO);

        if ($periodoId) {
            $query->where('periodo_id', $periodoId);
        }

        $todasActivas = $query->get();

        // Agrupar por estudiante y periodo para identificar colisiones
        $gruposPorEstudiantePeriodo = $todasActivas->groupBy(function ($ins) {
            return $ins->estudiante_id . '-' . $ins->periodo_id;
        })->filter(function ($grupo) {
            return $grupo->count() > 1;
        });

        $conflictos = [];

        foreach ($gruposPorEstudiantePeriodo as $clave => $inscripciones) {
            $primera = $inscripciones->first();
            $estudianteUser = $primera->estudianteUsuario;
            $estudiantePerfil = $estudianteUser?->estudiante;
            $periodo = $primera->periodo;

            $gruposDetalle = $inscripciones->map(function ($ins) {
                return [
                    'inscripcion_id' => $ins->inscripcion_id,
                    'grupo_id' => $ins->grupo_id,
                    'cuatrimestre' => $ins->grupo?->cuatrimestre,
                    'grupo_nombre' => $ins->grupo?->nombre,
                    'turno' => $ins->grupo?->turno,
                    'carrera' => $ins->grupo?->programa?->clave,
                    'created_at' => $ins->created_at?->toDateTimeString(),
                ];
            })->values()->all();

            // Identificar el grupo recomendado a conservar
            $grupoRecomendado = null;
            $motivoRecomendacion = '';

            if ($estudiantePerfil) {
                $matchExacto = $inscripciones->first(function ($ins) use ($estudiantePerfil) {
                    return $ins->grupo &&
                        (int) $ins->grupo->cuatrimestre === (int) $estudiantePerfil->cuatrimestre &&
                        strtoupper(trim($ins->grupo->nombre)) === strtoupper(trim($estudiantePerfil->grupo));
                });

                if ($matchExacto) {
                    $grupoRecomendado = $matchExacto->grupo;
                    $motivoRecomendacion = "Coincide exactamente con el cuatrimestre ({$estudiantePerfil->cuatrimestre}°) y grupo nominal ('{$estudiantePerfil->grupo}') del perfil del alumno en la tabla estudiantes.";
                }
            }

            if (!$grupoRecomendado) {
                $grupoRecomendado = $inscripciones->sortBy('inscripcion_id')->first()?->grupo;
                $motivoRecomendacion = "Inscripción más antigua registrada cronológicamente en el periodo.";
            }

            $conflictos[] = [
                'estudiante_id' => $primera->estudiante_id,
                'nombre_completo' => $estudianteUser ? ($estudianteUser->nombre . ' ' . $estudianteUser->apellido_paterno . ' ' . $estudianteUser->apellido_materno) : 'Desconocido',
                'matricula' => $estudiantePerfil?->matricula ?? 'S/M',
                'perfil_cuatrimestre' => $estudiantePerfil?->cuatrimestre,
                'perfil_grupo' => $estudiantePerfil?->grupo,
                'periodo_id' => $periodo?->periodo_id,
                'periodo_clave' => $periodo?->clave,
                'total_inscripciones_activas' => $inscripciones->count(),
                'inscripciones_en_conflicto' => $gruposDetalle,
                'grupo_recomendado_id' => $grupoRecomendado?->grupo_id,
                'grupo_recomendado_nombre' => $grupoRecomendado?->nombre_completo,
                'justificacion' => $motivoRecomendacion,
                'riesgo' => 'El alumno visualiza materias de dos cuatrimestres distintos, puede inscribirse a cursos no autorizados para su cohorte y distorsiona el cómputo de cupos y recompensas por grupo.',
                'accion_propuesta' => "Mantener la inscripción al Grupo {$grupoRecomendado?->nombre_completo} en estado ACTIVO y marcar las restantes como BAJA administrativa (anulación sin antecedente de acreditación).",
            ];
        }

        return $conflictos;
    }
}

