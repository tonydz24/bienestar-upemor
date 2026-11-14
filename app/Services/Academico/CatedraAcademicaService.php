<?php

namespace App\Services\Academico;

use App\Models\Asignatura;
use App\Models\DocenteAsignacion;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteAsignaturaInscripcionHistorial;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\User;
use App\Notifications\AsignacionDocenteResueltaNotification;
use App\Notifications\InscripcionEstudianteResueltaNotification;
use App\Notifications\SolicitudDocenteCreadaNotification;
use App\Notifications\SolicitudInscripcionEstudianteNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CatedraAcademicaService
{
    /**
     * Obtiene la oferta docente disponible para un estudiante específico en su grupo oficial y periodo activo.
     *
     * Desduplica por combinación (asignatura_id, docente_id), priorizando la asignación
     * que corresponda exactamente al grupo del estudiante frente a asignaciones genéricas TODOS.
     *
     * @param User $usuario Usuario estudiante autenticado
     * @param Grupo|null $miGrupo Grupo oficial del estudiante en el periodo
     * @param PeriodoAcademico $periodo Periodo académico operativo
     * @return Collection Colección de asignaciones docentes elegibles y desduplicadas
     */
    public function obtenerOfertaDocenteParaEstudiante(User $usuario, ?Grupo $miGrupo, PeriodoAcademico $periodo): Collection
    {
        if (!$miGrupo) {
            return collect();
        }

        $estudiante = $usuario->estudiante;
        if (!$estudiante) {
            return collect();
        }

        // Consultar inscripciones y solicitudes previas del alumno en el periodo
        $asignaturasYaRegistradas = collect();
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            $asignaturasYaRegistradas = EstudianteAsignaturaInscripcion::where('estudiante_id', $usuario->usuario_id)
                ->where('periodo_id', $periodo->periodo_id)
                ->whereIn('estado', [
                    EstudianteAsignaturaInscripcion::ESTADO_APROBADA,
                    EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
                ])
                ->pluck('asignatura_id')
                ->unique();
        }

        // Consulta de ofertas autorizadas y vigentes
        $query = DocenteAsignacion::with(['asignatura.programa', 'docenteUsuario', 'grupo.programa', 'periodo'])
            ->where('periodo_id', $periodo->periodo_id)
            ->where('activo', true);

        if (Schema::hasColumn('docente_asignaciones', 'estado')) {
            $query->where('estado', DocenteAsignacion::ESTADO_APROBADA);
        }

        // REGLA DE ALCANCE: Asignaciones específicas para el grupo del alumno O asignaciones de apertura general (TODOS)
        $query->where(function ($sub) use ($miGrupo) {
            $sub->where('grupo_id', $miGrupo->grupo_id);
            if (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance')) {
                $sub->orWhere('modalidad_alcance', 'TODOS');
            }
            $sub->orWhereNull('grupo_id');
        });

        // REGLA DE CONGRUENCIA CURRICULAR: Mismo programa académico y cuatrimestre
        $query->whereHas('asignatura', function ($q) use ($estudiante) {
            if ($estudiante->programa_id) {
                $q->where('programa_id', $estudiante->programa_id);
            }
            if ($estudiante->cuatrimestre) {
                $q->where('cuatrimestre', $estudiante->cuatrimestre);
            }
            $q->where('activo', true);
        });

        $ofertas = $query->get();

        // DESDUPLICACIÓN RIGUROSA (Incremento 23):
        // Por cada par (asignatura_id, docente_id), retornar exactamente una oferta docente,
        // priorizando siempre la asignación directa que coincida con el grupo oficial del estudiante.
        $desduplicadas = collect();
        $agrupadasPorPar = $ofertas->groupBy(fn ($item) => "{$item->asignatura_id}_{$item->docente_id}");

        foreach ($agrupadasPorPar as $clave => $coleccionPar) {
            $ofertaElegida = $coleccionPar->first(fn ($item) => (int) $item->grupo_id === (int) $miGrupo->grupo_id)
                ?? $coleccionPar->first();

            if ($ofertaElegida) {
                $desduplicadas->push($ofertaElegida);
            }
        }

        // Excluir materias en las que el alumno ya está inscrito o tiene solicitud pendiente
        return $desduplicadas->filter(function ($asig) use ($asignaturasYaRegistradas) {
            return !$asignaturasYaRegistradas->contains($asig->asignatura_id);
        })->values();
    }

    /**
     * Agrupa la oferta disponible primero por Asignatura, y dentro de cada una lista los docentes disponibles.
     * Cumple estrictamente el requisito institucional del Incremento 18.
     *
     * @param Collection $oferta Colección de ofertas docentes desduplicadas
     * @return Collection Colección agrupada con estructura [asignatura_id, asignatura, total_docentes, docentes_oferta]
     */
    public function agruparOfertaPorAsignatura(Collection $oferta): Collection
    {
        return $oferta->groupBy('asignatura_id')->map(function ($asignaciones, $asignaturaId) {
            $primera = $asignaciones->first();
            return [
                'asignatura_id' => $asignaturaId,
                'asignatura' => $primera->asignatura,
                'total_docentes' => $asignaciones->count(),
                'docentes_oferta' => $asignaciones,
            ];
        })->values();
    }

    /**
     * Obtiene las materias curriculares del plan de estudios del estudiante que aún no tienen oferta docente
     * autorizada o disponible para su grupo en el periodo activo (Sección 6.3 del Requisito Institucional).
     *
     * @param User $usuario Usuario estudiante
     * @param Grupo|null $miGrupo Grupo del estudiante
     * @param PeriodoAcademico $periodo Periodo operativo
     * @return Collection Colección de asignaturas del plan sin oferta disponible
     */
    public function obtenerMateriasSinOfertaDocente(User $usuario, ?Grupo $miGrupo, PeriodoAcademico $periodo): Collection
    {
        if (!$miGrupo) {
            return collect();
        }

        $estudiante = $usuario->estudiante;
        if (!$estudiante || !$estudiante->programa_id || !$estudiante->cuatrimestre) {
            return collect();
        }

        // Obtener todas las materias del plan para el cuatrimestre y carrera del alumno
        $materiasPlan = Asignatura::where('programa_id', $estudiante->programa_id)
            ->where('cuatrimestre', $estudiante->cuatrimestre)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        if ($materiasPlan->isEmpty()) {
            return collect();
        }

        // Obtener los IDs de materias que ya cuentan con oferta (general o de su grupo) en el periodo
        $asignaturasConOferta = DocenteAsignacion::where('periodo_id', $periodo->periodo_id)
            ->where('activo', true)
            ->where(function ($sub) use ($miGrupo) {
                $sub->where('grupo_id', $miGrupo->grupo_id);
                if (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance')) {
                    $sub->orWhere('modalidad_alcance', 'TODOS');
                }
                $sub->orWhereNull('grupo_id');
            });

        if (Schema::hasColumn('docente_asignaciones', 'estado')) {
            $asignaturasConOferta->where('estado', DocenteAsignacion::ESTADO_APROBADA);
        }

        $ofertadasIds = $asignaturasConOferta->pluck('asignatura_id')->unique();

        // Filtrar las del plan que no tengan oferta disponible
        return $materiasPlan->reject(function ($materia) use ($ofertadasIds) {
            return $ofertadasIds->contains($materia->asignatura_id);
        })->values();
    }

    /**
     * Valida de forma estricta si un alumno es elegible para solicitar una oferta docente específica.
     *
     * @param User $usuario Usuario estudiante autenticado
     * @param DocenteAsignacion $asignacion Asignación docente que se pretende cursar
     * @param Grupo|null $miGrupo Grupo oficial del alumno
     * @return array{valido: bool, mensaje: string|null} Resultado de la validación
     */
    public function validarElegibilidadSolicitud(User $usuario, DocenteAsignacion $asignacion, ?Grupo $miGrupo): array
    {
        if (!$miGrupo) {
            return ['valido' => false, 'mensaje' => 'No cuentas con un grupo académico oficial asignado en este periodo.'];
        }

        // 1. Validar que la asignación esté activa y aprobada
        if (!$asignacion->activo) {
            return ['valido' => false, 'mensaje' => 'Esta asignatura no cuenta con una oferta docente activa en este periodo.'];
        }

        if (Schema::hasColumn('docente_asignaciones', 'estado') && $asignacion->estado !== DocenteAsignacion::ESTADO_APROBADA) {
            return ['valido' => false, 'mensaje' => 'La oferta de esta asignatura se encuentra pendiente de autorización por la Dirección.'];
        }

        // 2. Validar alcance de grupo (específico o general)
        $tieneAlcanceGeneral = (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance') && $asignacion->modalidad_alcance === 'TODOS')
            || is_null($asignacion->grupo_id);

        if (!$tieneAlcanceGeneral && (int) $asignacion->grupo_id !== (int) $miGrupo->grupo_id) {
            return [
                'valido' => false,
                'mensaje' => "Operación rechazada: La materia '{$asignacion->asignatura?->nombre}' está ofertada exclusivamente para el Grupo {$asignacion->grupo?->nombre}. Tu adscripción corresponde al Grupo {$miGrupo->nombre}. No se admiten inscripciones cruzadas entre secciones.",
            ];
        }

        // 3. Validar congruencia curricular (Carrera y Cuatrimestre)
        $estudiante = $usuario->estudiante;
        if ($estudiante && $asignacion->asignatura) {
            if ($estudiante->programa_id && $asignacion->asignatura->programa_id && (int) $asignacion->asignatura->programa_id !== (int) $estudiante->programa_id) {
                return ['valido' => false, 'mensaje' => "Operación rechazada: La materia '{$asignacion->asignatura->nombre}' no pertenece a tu programa educativo oficial."];
            }
            if ($estudiante->cuatrimestre && $asignacion->asignatura->cuatrimestre && (int) $asignacion->asignatura->cuatrimestre !== (int) $estudiante->cuatrimestre) {
                return ['valido' => false, 'mensaje' => "Operación rechazada: La materia '{$asignacion->asignatura->nombre}' corresponde al {$asignacion->asignatura->cuatrimestre}° cuatrimestre y tu registro oficial es en {$estudiante->cuatrimestre}° cuatrimestre."];
            }
        }

        // 4. Validar existencia física de la tabla de inscripciones
        if (!Schema::hasTable('estudiante_asignatura_inscripciones')) {
            return ['valido' => false, 'mensaje' => 'El sistema de registro de asignaturas se encuentra en preparación administrativa temporal.'];
        }

        // 5. Validar que no exista ya solicitud o inscripción activa para esta misma materia en el periodo
        $yaExiste = EstudianteAsignaturaInscripcion::where('estudiante_id', $usuario->usuario_id)
            ->where('periodo_id', $asignacion->periodo_id)
            ->where('asignatura_id', $asignacion->asignatura_id)
            ->whereIn('estado', [
                EstudianteAsignaturaInscripcion::ESTADO_APROBADA,
                EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
            ])
            ->exists();

        if ($yaExiste) {
            return ['valido' => false, 'mensaje' => "Ya cuentas con una solicitud activa o una inscripción aprobada para la asignatura '{$asignacion->asignatura?->nombre}' en este ciclo."];
        }

        return ['valido' => true, 'mensaje' => null];
    }

    /**
     * Procesa la solicitud de inscripción de un estudiante con protección transaccional e idempotencia.
     *
     * @param User $usuario Usuario estudiante
     * @param DocenteAsignacion $asignacion Asignación docente a cursar
     * @param Grupo $miGrupo Grupo oficial del estudiante
     * @param string|null $motivo Justificación opcional
     * @return EstudianteAsignaturaInscripcion Registro de inscripción creado o existente
     * @throws RuntimeException Si el alumno no es elegible
     */
    public function solicitarInscripcion(User $usuario, DocenteAsignacion $asignacion, Grupo $miGrupo, ?string $motivo = null): EstudianteAsignaturaInscripcion
    {
        return DB::transaction(function () use ($usuario, $asignacion, $miGrupo, $motivo) {
            // Verificación atómica bajo bloqueo de lectura
            $existente = EstudianteAsignaturaInscripcion::where('estudiante_id', $usuario->usuario_id)
                ->where('periodo_id', $asignacion->periodo_id)
                ->where('asignatura_id', $asignacion->asignatura_id)
                ->whereIn('estado', [
                    EstudianteAsignaturaInscripcion::ESTADO_APROBADA,
                    EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
                ])
                ->lockForUpdate()
                ->first();

            if ($existente) {
                throw new RuntimeException("Ya cuentas con una solicitud activa o inscripción previa para la materia '{$asignacion->asignatura?->nombre}'.");
            }

            // Buscar si ya existe un registro previo para este par (estudiante_id, asignacion_id)
            // (por ejemplo, cancelado o rechazado previamente) para reactivarlo sin violar la restricción UNIQUE 'idx_est_asig_inscrip_unique'
            $registroPrevio = EstudianteAsignaturaInscripcion::where('estudiante_id', $usuario->usuario_id)
                ->where('asignacion_id', $asignacion->asignacion_id)
                ->lockForUpdate()
                ->first();

            if ($registroPrevio) {
                // Trazabilidad: resguardar la transición previa para preservar historial de fechas, rechazos y cancelaciones
                $estadoAnterior = $registroPrevio->estado;
                $transiciones = $registroPrevio->historial_transiciones ?? [];
                $transiciones[] = [
                    'fecha' => now()->toIso8601String(),
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
                    'motivo' => $motivo ? "Re-solicitud de cátedra: {$motivo}" : 'Re-solicitud de cátedra',
                    'solicitado_anterior' => $registroPrevio->solicitado_en?->toIso8601String(),
                    'resuelto_por_anterior' => $registroPrevio->resuelto_por,
                    'resuelto_en_anterior' => $registroPrevio->resuelto_en?->toIso8601String(),
                    'motivo_resolucion_anterior' => $registroPrevio->motivo_resolucion,
                ];

                if (Schema::hasTable('estudiante_asignatura_inscripciones_historial')) {
                    try {
                        EstudianteAsignaturaInscripcionHistorial::create([
                            'inscripcion_materia_id' => $registroPrevio->inscripcion_materia_id,
                            'estudiante_id' => $usuario->usuario_id,
                            'asignacion_id' => $asignacion->asignacion_id,
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
                            'motivo' => $motivo ? "Re-solicitud: {$motivo}" : "Re-solicitud tras estado {$estadoAnterior}",
                            'resuelto_por' => null,
                            'resuelto_en' => null,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning("No se pudo registrar historial relacional de re-solicitud: {$e->getMessage()}");
                    }
                }

                $registroPrevio->historial_transiciones = $transiciones;
                $registroPrevio->estado = EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE;
                $registroPrevio->grupo_id = $miGrupo->grupo_id; // Asegurar grupo oficial
                $registroPrevio->asignatura_id = $asignacion->asignatura_id;
                $registroPrevio->periodo_id = $asignacion->periodo_id;
                $registroPrevio->solicitado_en = now();
                $registroPrevio->motivo_solicitud = $motivo;
                $registroPrevio->resuelto_por = null;
                $registroPrevio->resuelto_en = null;
                $registroPrevio->motivo_resolucion = null;
                $registroPrevio->save();

                $inscripcion = $registroPrevio;
            } else {
                $transicionInicial = [
                    [
                        'fecha' => now()->toIso8601String(),
                        'estado_anterior' => null,
                        'estado_nuevo' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
                        'motivo' => $motivo ?? 'Solicitud inicial de inscripción a cátedra',
                        'resuelto_por' => null,
                        'resuelto_en' => null,
                    ]
                ];

                $inscripcion = EstudianteAsignaturaInscripcion::create([
                    'estudiante_id' => $usuario->usuario_id,
                    'asignacion_id' => $asignacion->asignacion_id,
                    'grupo_id' => $miGrupo->grupo_id, // Siempre asigna el grupo oficial del estudiante
                    'asignatura_id' => $asignacion->asignatura_id,
                    'periodo_id' => $asignacion->periodo_id,
                    'estado' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
                    'solicitado_en' => now(),
                    'motivo_solicitud' => $motivo,
                    'historial_transiciones' => $transicionInicial,
                ]);

                if (Schema::hasTable('estudiante_asignatura_inscripciones_historial')) {
                    try {
                        EstudianteAsignaturaInscripcionHistorial::create([
                            'inscripcion_materia_id' => $inscripcion->inscripcion_materia_id,
                            'estudiante_id' => $usuario->usuario_id,
                            'asignacion_id' => $asignacion->asignacion_id,
                            'estado_anterior' => null,
                            'estado_nuevo' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
                            'motivo' => $motivo ?? 'Solicitud inicial de inscripción a cátedra',
                            'resuelto_por' => null,
                            'resuelto_en' => null,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning("No se pudo registrar historial relacional de solicitud inicial: {$e->getMessage()}");
                    }
                }
            }

            // Notificar al docente titular
            if ($asignacion->docenteUsuario) {
                try {
                    $asignacion->docenteUsuario->notify(new SolicitudInscripcionEstudianteNotification(
                        estudianteNombre: $usuario->nombre . ' ' . $usuario->apellido_paterno,
                        matricula: $usuario->estudiante?->matricula ?? 'S/M',
                        asignaturaNombre: $asignacion->asignatura?->nombre ?? 'Materia',
                        grupoNombre: $miGrupo->nombre_completo ?? 'Grupo',
                        asignacionId: $asignacion->asignacion_id
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enviar notificación al docente: {$e->getMessage()}");
                }
            }

            return $inscripcion;
        });
    }

    /**
     * Registra una nueva solicitud de cátedra enviada por un docente, evitando duplicidades y solicitudes redundantes.
     *
     * @param User $docente Usuario con rol Docente
     * @param Asignatura $asignatura Asignatura a impartir
     * @param PeriodoAcademico $periodo Periodo operativo
     * @param string $modalidad Modalidad de alcance ('todos' o 'especifico')
     * @param int|null $grupoId Identificador del grupo (obligatorio en modalidad especifico)
     * @param string|null $motivo Justificación opcional de la solicitud
     * @return DocenteAsignacion Asignación creada en estado PENDIENTE
     * @throws RuntimeException Si ya existe una asignación activa o trámite pendiente incompatible
     */
    public function solicitarCatedra(
        User $docente,
        Asignatura $asignatura,
        PeriodoAcademico $periodo,
        string $modalidad = 'todos',
        ?int $grupoId = null,
        ?string $motivo = null
    ): array {
        return DB::transaction(function () use ($docente, $asignatura, $periodo, $modalidad, $grupoId, $motivo) {
            // Verificar si el docente ya solicitó o imparte esta materia en este periodo (Validación por alcance e individualidad de grupo)
            if ($modalidad === 'especifico' && $grupoId) {
                // Para solicitud de grupo específico: Bloquear solo si ya existe solicitud/cátedra activa para ese mismo grupo o con alcance TODOS
                $queryExistente = DocenteAsignacion::where('docente_id', $docente->usuario_id)
                    ->where('asignatura_id', $asignatura->asignatura_id)
                    ->where('periodo_id', $periodo->periodo_id)
                    ->where(function ($q) use ($grupoId) {
                        $q->where('grupo_id', $grupoId);
                        if (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance')) {
                            $q->orWhere('modalidad_alcance', 'TODOS');
                        }
                    })
                    ->where(function ($q) {
                        $q->where('activo', true);
                        if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                            $q->orWhereIn('estado', [DocenteAsignacion::ESTADO_APROBADA, DocenteAsignacion::ESTADO_PENDIENTE]);
                        }
                    });

                if ($queryExistente->exists()) {
                    throw new RuntimeException("Ya cuentas con una cátedra activa o una solicitud en trámite para el grupo seleccionado en la asignatura '{$asignatura->nombre}'.");
                }
            } else {
                // Para solicitud global (TODOS): Bloquear si ya existe una cátedra global o si ya imparte la materia
                $queryExistente = DocenteAsignacion::where('docente_id', $docente->usuario_id)
                    ->where('asignatura_id', $asignatura->asignatura_id)
                    ->where('periodo_id', $periodo->periodo_id)
                    ->where(function ($q) {
                        $q->where('activo', true);
                        if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                            $q->orWhereIn('estado', [DocenteAsignacion::ESTADO_APROBADA, DocenteAsignacion::ESTADO_PENDIENTE]);
                        }
                    })
                    ->where(function ($q) {
                        if (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance')) {
                            $q->where('modalidad_alcance', 'TODOS');
                        }
                    });

                if ($queryExistente->exists()) {
                    throw new RuntimeException("Ya cuentas con una cátedra activa o solicitud de alcance global (TODOS) para la asignatura '{$asignatura->nombre}' en este periodo académico.");
                }
            }

            // Resolver grupos destinatarios
            if ($modalidad === 'todos') {
                $gruposObjetivo = Grupo::where('periodo_id', $periodo->periodo_id)
                    ->where('programa_id', $asignatura->programa_id)
                    ->where('cuatrimestre', $asignatura->cuatrimestre)
                    ->where('activo', true)
                    ->get();

                if ($gruposObjetivo->isEmpty()) {
                    // Si no existen grupos en el periodo para ese cuatrimestre, crear el Grupo A formal
                    $grupoDefault = Grupo::create([
                        'programa_id' => $asignatura->programa_id,
                        'periodo_id' => $periodo->periodo_id,
                        'cuatrimestre' => $asignatura->cuatrimestre,
                        'nombre' => 'A',
                        'turno' => 'MATUTINO',
                        'activo' => true,
                    ]);
                    $gruposObjetivo = collect([$grupoDefault]);
                }
            } else {
                $grupoEspecifico = Grupo::findOrFail($grupoId);
                $gruposObjetivo = collect([$grupoEspecifico]);
            }

            $asignacionesCreadas = [];
            $nombresGrupos = [];

            foreach ($gruposObjetivo as $grp) {
                $asignacion = new DocenteAsignacion();
                $asignacion->docente_id = $docente->usuario_id;
                $asignacion->asignatura_id = $asignatura->asignatura_id;
                $asignacion->grupo_id = $grp->grupo_id;
                $asignacion->periodo_id = $periodo->periodo_id;
                $asignacion->activo = false; // Permanece inactiva hasta la aprobación administrativa

                if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                    $asignacion->estado = DocenteAsignacion::ESTADO_PENDIENTE;
                    $asignacion->solicitado_en = now();
                    $asignacion->motivo_solicitud = $motivo;
                }

                if (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance')) {
                    $asignacion->modalidad_alcance = ($modalidad === 'todos') ? 'TODOS' : 'ESPECIFICO';
                }

                $asignacion->save();
                $asignacionesCreadas[] = $asignacion;
                $nombresGrupos[] = $grp->nombre_completo ?? "Grupo {$grp->nombre}";
            }

            // Notificar a los administradores
            $administradores = User::whereHas('rol', fn ($q) => $q->where('nombre', 'Administrador'))
                ->orWhere('rol_id', 1)
                ->get();
            $textoGrupos = implode(', ', $nombresGrupos);

            foreach ($administradores as $admin) {
                try {
                    $admin->notify(new SolicitudDocenteCreadaNotification(
                        docenteNombre: $docente->nombre . ' ' . $docente->apellido_paterno,
                        asignaturaNombre: $asignatura->nombre,
                        grupoNombre: $textoGrupos,
                        motivo: $motivo
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo notificar al administrador: {$e->getMessage()}");
                }
            }

            return [
                'total_creadas' => count($asignacionesCreadas),
                'grupos' => $textoGrupos,
                'asignaciones' => $asignacionesCreadas,
            ];
        });
    }

    /**
     * Aprueba una solicitud de cátedra docente.
     * En modalidad TODOS, aprueba atómicamente el lote vinculado a esa solicitud general.
     * En modalidad ESPECIFICO, aprueba única y exclusivamente el grupo solicitado, preservando solicitudes de otros grupos independientes.
     *
     * @param int $asignacionId Identificador de la asignación a aprobar
     * @param User $admin Usuario administrador resolutor
     * @param string|null $motivo Motivo u observación opcional
     * @return array{asignacion: DocenteAsignacion, total_aprobadas: int, es_alcance_todos: bool}
     * @throws RuntimeException Si el usuario no tiene permisos administrativos
     */
    public function aprobarCatedra(int $asignacionId, User $admin, ?string $motivo = null): array
    {
        return DB::transaction(function () use ($asignacionId, $admin, $motivo) {
            $asignacion = DocenteAsignacion::with(['docenteUsuario', 'asignatura', 'grupo'])->findOrFail($asignacionId);

            $esAlcanceTodos = (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance') && $asignacion->modalidad_alcance === 'TODOS');

            if ($esAlcanceTodos) {
                // Lote de apertura general: aprobar todas las asignaciones hermanas con modalidad_alcance = 'TODOS'
                $asignacionesAResolver = DocenteAsignacion::where('docente_id', $asignacion->docente_id)
                    ->where('asignatura_id', $asignacion->asignatura_id)
                    ->where('periodo_id', $asignacion->periodo_id)
                    ->where('modalidad_alcance', 'TODOS')
                    ->where(function ($q) {
                        $q->where('activo', false);
                        if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                            $q->orWhere('estado', DocenteAsignacion::ESTADO_PENDIENTE);
                        }
                    })
                    ->get();

                if ($asignacionesAResolver->isEmpty()) {
                    $asignacionesAResolver = collect([$asignacion]);
                }
            } else {
                // Alcance específico: resolver estrictamente la asignación individual del grupo seleccionado
                $asignacionesAResolver = collect([$asignacion]);
            }

            foreach ($asignacionesAResolver as $asig) {
                $asig->activo = true;
                if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                    $asig->estado = DocenteAsignacion::ESTADO_APROBADA;
                    $asig->resuelto_por = $admin->usuario_id;
                    $asig->resuelto_en = now();
                    $asig->motivo_resolucion = $motivo;
                }
                $asig->save();
            }

            // Notificar al docente
            if ($asignacion->docenteUsuario) {
                try {
                    $grupoTexto = $esAlcanceTodos
                        ? 'Todos los grupos del cuatrimestre'
                        : ($asignacion->grupo?->nombre_completo ?? 'Grupo ' . $asignacion->grupo?->nombre);

                    $asignacion->docenteUsuario->notify(new AsignacionDocenteResueltaNotification(
                        asignaturaNombre: $asignacion->asignatura?->nombre ?? 'Asignatura',
                        grupoNombre: $grupoTexto,
                        estado: DocenteAsignacion::ESTADO_APROBADA,
                        motivoResolucion: $motivo
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo notificar al docente sobre aprobación: {$e->getMessage()}");
                }
            }

            return [
                'total_aprobadas' => $asignacionesAResolver->count(),
                'asignacion' => $asignacion,
                'es_alcance_todos' => $esAlcanceTodos,
            ];
        });
    }

    /**
     * Rechaza la solicitud de cátedra docente.
     * En modalidad TODOS, rechaza el lote global vinculado.
     * En modalidad ESPECIFICO, rechaza únicamente el grupo solicitado.
     *
     * @param int $asignacionId Identificador de la asignación a rechazar
     * @param User $admin Usuario administrador resolutor
     * @param string $motivo Motivo obligatorio del rechazo
     * @return array{asignacion: DocenteAsignacion, total_rechazadas: int, es_alcance_todos: bool}
     * @throws RuntimeException Si el usuario no tiene permisos administrativos
     */
    public function rechazarCatedra(int $asignacionId, User $admin, string $motivo): array
    {
        return DB::transaction(function () use ($asignacionId, $admin, $motivo) {
            $asignacion = DocenteAsignacion::with(['docenteUsuario', 'asignatura', 'grupo'])->findOrFail($asignacionId);

            $esAlcanceTodos = (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance') && $asignacion->modalidad_alcance === 'TODOS');

            if ($esAlcanceTodos) {
                $asignacionesAResolver = DocenteAsignacion::where('docente_id', $asignacion->docente_id)
                    ->where('asignatura_id', $asignacion->asignatura_id)
                    ->where('periodo_id', $asignacion->periodo_id)
                    ->where('modalidad_alcance', 'TODOS')
                    ->where(function ($q) {
                        $q->where('activo', false);
                        if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                            $q->orWhere('estado', DocenteAsignacion::ESTADO_PENDIENTE);
                        }
                    })
                    ->get();

                if ($asignacionesAResolver->isEmpty()) {
                    $asignacionesAResolver = collect([$asignacion]);
                }
            } else {
                $asignacionesAResolver = collect([$asignacion]);
            }

            foreach ($asignacionesAResolver as $asig) {
                $asig->activo = false;
                if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                    $asig->estado = DocenteAsignacion::ESTADO_RECHAZADA;
                    $asig->resuelto_por = $admin->usuario_id;
                    $asig->resuelto_en = now();
                    $asig->motivo_resolucion = $motivo;
                }
                $asig->save();
            }

            if ($asignacion->docenteUsuario) {
                try {
                    $grupoTexto = $esAlcanceTodos
                        ? 'Todos los grupos del cuatrimestre'
                        : ($asignacion->grupo?->nombre_completo ?? 'Grupo ' . $asignacion->grupo?->nombre);

                    $asignacion->docenteUsuario->notify(new AsignacionDocenteResueltaNotification(
                        asignaturaNombre: $asignacion->asignatura?->nombre ?? 'Asignatura',
                        grupoNombre: $grupoTexto,
                        estado: DocenteAsignacion::ESTADO_RECHAZADA,
                        motivoResolucion: $motivo
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo notificar al docente sobre rechazo: {$e->getMessage()}");
                }
            }

            return [
                'total_rechazadas' => $asignacionesAResolver->count(),
                'asignacion' => $asignacion,
                'es_alcance_todos' => $esAlcanceTodos,
            ];
        });
    }

    /**
     * Resuelve la inscripción de un alumno a la asignatura (APROBADA o RECHAZADA) con idempotencia transaccional.
     *
     * @param int $inscripcionMateriaId Identificador de la inscripción
     * @param User $docente Usuario docente a cargo de la cátedra o administrador
     * @param string $nuevoEstado Estado destino ('APROBADA' o 'RECHAZADA')
     * @param string|null $motivo Motivo u observación de la resolución
     * @return array{ya_resuelta: bool, inscripcion: EstudianteAsignaturaInscripcion}
     * @throws RuntimeException Si el usuario no tiene autorización para gestionar la materia
     */
    public function resolverInscripcionEstudiante(int $inscripcionMateriaId, User $docente, string $nuevoEstado, ?string $motivo = null): array
    {
        return DB::transaction(function () use ($inscripcionMateriaId, $docente, $nuevoEstado, $motivo) {
            $inscripcion = EstudianteAsignaturaInscripcion::with([
                'asignacion.docenteUsuario',
                'asignacion.asignatura',
                'asignacion.grupo',
                'estudianteUsuario',
            ])
                ->lockForUpdate()
                ->findOrFail($inscripcionMateriaId);

            // Validar autorización
            if ($inscripcion->asignacion->docente_id !== $docente->usuario_id && !$docente->rol?->es_admin && $docente->rol_id !== 1) {
                throw new RuntimeException("No tienes autorización para gestionar inscripciones de esta asignatura.");
            }

            // Verificación de idempotencia: Si ya fue resuelta previamente, no rehacer
            if ($inscripcion->estado !== EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE) {
                return [
                    'ya_resuelta' => true,
                    'inscripcion' => $inscripcion,
                ];
            }

            $estadoAnterior = $inscripcion->estado;
            $transiciones = $inscripcion->historial_transiciones ?? [];
            $transiciones[] = [
                'fecha' => now()->toIso8601String(),
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
                'motivo' => $motivo,
                'resuelto_por' => $docente->usuario_id,
                'resuelto_en' => now()->toIso8601String(),
            ];

            if (Schema::hasTable('estudiante_asignatura_inscripciones_historial')) {
                try {
                    EstudianteAsignaturaInscripcionHistorial::create([
                        'inscripcion_materia_id' => $inscripcion->inscripcion_materia_id,
                        'estudiante_id' => $inscripcion->estudiante_id,
                        'asignacion_id' => $inscripcion->asignacion_id,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $nuevoEstado,
                        'motivo' => $motivo,
                        'resuelto_por' => $docente->usuario_id,
                        'resuelto_en' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("No se pudo registrar historial relacional de resolución de inscripción: {$e->getMessage()}");
                }
            }

            $inscripcion->historial_transiciones = $transiciones;
            $inscripcion->estado = $nuevoEstado;
            $inscripcion->resuelto_por = $docente->usuario_id;
            $inscripcion->resuelto_en = now();
            $inscripcion->motivo_resolucion = $motivo;
            $inscripcion->save();

            // Notificar al estudiante
            if ($inscripcion->estudianteUsuario) {
                try {
                    $inscripcion->estudianteUsuario->notify(new InscripcionEstudianteResueltaNotification(
                        asignaturaNombre: $inscripcion->asignacion?->asignatura?->nombre ?? 'Asignatura',
                        grupoNombre: $inscripcion->asignacion?->grupo?->nombre_completo ?? 'Grupo',
                        estado: $nuevoEstado,
                        motivoResolucion: $motivo
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enviar notificación de inscripción al estudiante: {$e->getMessage()}");
                }
            }

            return [
                'ya_resuelta' => false,
                'inscripcion' => $inscripcion,
            ];
        });
    }

    /**
     * Aprueba la inscripción de un estudiante a una cátedra académica.
     *
     * @param int $inscripcionMateriaId Identificador de la inscripción
     * @param User $docente Usuario docente o administrador que aprueba
     * @param string|null $motivo Motivo u observación opcional
     * @return EstudianteAsignaturaInscripcion
     */
    public function aprobarInscripcion(int $inscripcionMateriaId, User $docente, ?string $motivo = null): EstudianteAsignaturaInscripcion
    {
        $res = $this->resolverInscripcionEstudiante($inscripcionMateriaId, $docente, EstudianteAsignaturaInscripcion::ESTADO_APROBADA, $motivo);
        return $res['inscripcion'];
    }

    /**
     * Rechaza la inscripción de un estudiante a una cátedra académica.
     *
     * @param int $inscripcionMateriaId Identificador de la inscripción
     * @param User $docente Usuario docente o administrador que rechaza
     * @param string $motivo Motivo obligatorio del rechazo
     * @return EstudianteAsignaturaInscripcion
     */
    public function rechazarInscripcion(int $inscripcionMateriaId, User $docente, string $motivo): EstudianteAsignaturaInscripcion
    {
        $res = $this->resolverInscripcionEstudiante($inscripcionMateriaId, $docente, EstudianteAsignaturaInscripcion::ESTADO_RECHAZADA, $motivo);
        return $res['inscripcion'];
    }
}
