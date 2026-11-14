<?php

namespace App\Services\Recompensas;

use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecompensaService
{
    /**
     * Activa una recompensa del catálogo para una asignatura y grupo docente específicos.
     */
    public function activarRecompensa(
        DocenteAsignacion $asignacion,
        int $recompensaId,
        int $cupoMaximo,
        int $costoPuntos,
        string $vigenciaInicio,
        string $vigenciaFin,
        ?string $condiciones = null
    ): RecompensaActivada {
        $catalogo = RecompensaCatalogo::where('recompensa_id', $recompensaId)
            ->where('activo', true)
            ->firstOrFail();

        $inicio = Carbon::parse($vigenciaInicio);
        $fin = Carbon::parse($vigenciaFin);

        if ($fin->lt($inicio)) {
            throw new \DomainException('La fecha de fin de vigencia no puede ser anterior a la fecha de inicio.');
        }

        if ($cupoMaximo < 1) {
            throw new \DomainException('El cupo de recompensas debe ser al menos de 1.');
        }

        // FASE 3: Evitar duplicados activos en la misma materia, grupo y asignación docente
        $existente = RecompensaActivada::where('recompensa_id', $catalogo->recompensa_id)
            ->where('asignacion_id', $asignacion->asignacion_id)
            ->whereIn('estado', [RecompensaActivada::ESTADO_ACTIVA, RecompensaActivada::ESTADO_SUSPENDIDA])
            ->first();

        if ($existente) {
            throw new \DomainException("El beneficio '{$catalogo->nombre}' ya está habilitado en este grupo (Estado: {$existente->estado}). Para modificar cupos, costos o fechas, edita la configuración existente.");
        }

        $activacion = RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $asignacion->asignacion_id,
            'cupo_maximo' => $cupoMaximo,
            'cupo_disponible' => $cupoMaximo,
            'costo_puntos' => $costoPuntos >= 0 ? $costoPuntos : $catalogo->costo_puntos_base,
            'vigencia_inicio' => $inicio->toDateString(),
            'vigencia_fin' => $fin->toDateString(),
            'condiciones_docente' => $condiciones ? trim($condiciones) : null,
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        try {
            $asignacion->loadMissing('asignatura');
            $materiaNombre = $asignacion->asignatura?->nombre ?? 'Asignatura';
            $estudianteIds = EstudianteInscripcion::where('grupo_id', $asignacion->grupo_id)
                ->where('periodo_id', $asignacion->periodo_id)
                ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
                ->pluck('estudiante_id')
                ->toArray();

            if (\Illuminate\Support\Facades\Schema::hasTable('estudiante_asignatura_inscripciones')) {
                $porMateriaIds = \App\Models\EstudianteAsignaturaInscripcion::where('asignacion_id', $asignacion->asignacion_id)
                    ->where('estado', \App\Models\EstudianteAsignaturaInscripcion::ESTADO_APROBADA)
                    ->pluck('estudiante_id')
                    ->toArray();
                $estudianteIds = array_values(array_unique(array_merge($estudianteIds, $porMateriaIds)));
            }

            if (!empty($estudianteIds)) {
                $alumnos = User::whereIn('usuario_id', $estudianteIds)->get();
                foreach ($alumnos as $alumno) {
                    try {
                        $alumno->notify(new \App\Notifications\RecompensaHabilitadaNotification(
                            nombreRecompensa: $catalogo->nombre,
                            materiaNombre: $materiaNombre,
                            costoPuntos: (int) $activacion->costo_puntos
                        ));
                    } catch (\Throwable $e) {
                        Log::warning("Error notificando recompensa habilitada a alumno #{$alumno->usuario_id}: {$e->getMessage()}");
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Error procesando notificaciones de recompensa habilitada: {$e->getMessage()}");
        }

        return $activacion;
    }

    /**
     * Permite al docente actualizar la configuración vigente de una recompensa habilitada
     * sin alterar retroactivamente los cupones ya canjeados ni reducir el cupo por debajo de los consumidos.
     */
    public function actualizarConfiguracionActivacion(
        RecompensaActivada $activacion,
        int $cupoMaximo,
        int $costoPuntos,
        string $vigenciaInicio,
        string $vigenciaFin,
        ?string $condiciones = null,
        ?string $estado = null
    ): RecompensaActivada {
        $inicio = Carbon::parse($vigenciaInicio);
        $fin = Carbon::parse($vigenciaFin);

        if ($fin->lt($inicio)) {
            throw new \DomainException('La fecha de fin de vigencia no puede ser anterior a la fecha de inicio.');
        }

        $cuponesConsumidos = $activacion->cupo_maximo - $activacion->cupo_disponible;
        if ($cupoMaximo < $cuponesConsumidos) {
            throw new \DomainException("El cupo total ({$cupoMaximo}) no puede ser menor a los {$cuponesConsumidos} cupones que ya fueron canjeados por los estudiantes.");
        }

        $nuevoCupoDisponible = $cupoMaximo - $cuponesConsumidos;

        $activacion->cupo_maximo = $cupoMaximo;
        $activacion->cupo_disponible = $nuevoCupoDisponible;
        $activacion->costo_puntos = max(0, $costoPuntos);
        $activacion->vigencia_inicio = $inicio->toDateString();
        $activacion->vigencia_fin = $fin->toDateString();
        $activacion->condiciones_docente = $condiciones ? trim($condiciones) : null;

        if ($estado && in_array($estado, [RecompensaActivada::ESTADO_ACTIVA, RecompensaActivada::ESTADO_SUSPENDIDA, RecompensaActivada::ESTADO_FINALIZADA])) {
            $activacion->estado = $estado;
        }

        $activacion->save();

        return $activacion;
    }

    /**
     * Pausar o reanudar una recompensa activa para prevenir nuevos canjes temporalmente.
     */
    public function togglePausaActivacion(RecompensaActivada $activacion): RecompensaActivada
    {
        if ($activacion->estado === RecompensaActivada::ESTADO_ACTIVA) {
            $activacion->estado = RecompensaActivada::ESTADO_SUSPENDIDA;
        } elseif ($activacion->estado === RecompensaActivada::ESTADO_SUSPENDIDA) {
            $activacion->estado = RecompensaActivada::ESTADO_ACTIVA;
        }

        $activacion->save();

        return $activacion;
    }

    /**
     * Procesa el canje directo e inmediato por parte de un estudiante inscrito:
     * Valida inscripción, vigencia, cupo y puntos atómicamente, descontando los puntos
     * y generando el beneficio digital con código único.
     */
    public function solicitarCanje(
        User $alumnoUser,
        int $activacionId,
        ?string $motivo = null
    ): RecompensaCanje {
        return DB::transaction(function () use ($alumnoUser, $activacionId, $motivo) {
            $estudiante = Estudiante::where('usuario_id', $alumnoUser->usuario_id)
                ->lockForUpdate()
                ->first();

            if (!$estudiante) {
                throw new \DomainException('No cuentas con un perfil de estudiante activo.');
            }

            $activacion = RecompensaActivada::with(['asignacion.grupo', 'recompensaCatalogo'])
                ->where('activacion_id', $activacionId)
                ->lockForUpdate()
                ->firstOrFail();

            // Prioridad absoluta del administrador institucional sobre activaciones
            if (!$activacion->recompensaCatalogo || !$activacion->recompensaCatalogo->activo || $activacion->recompensaCatalogo->estado_propuesta === RecompensaCatalogo::PROPUESTA_RECHAZADA) {
                throw new \DomainException('Esta recompensa ha sido suspendida por la administración institucional y no puede ser canjeada.');
            }

            if ($activacion->estado !== RecompensaActivada::ESTADO_ACTIVA) {
                throw new \DomainException('Esta recompensa se encuentra pausada o no disponible actualmente.');
            }

            if (!$activacion->estaVigente()) {
                throw new \DomainException('Esta recompensa no se encuentra vigente en este momento.');
            }

            if (!$activacion->tieneCupo()) {
                throw new \DomainException('Lo sentimos, los cupos para esta recompensa se han agotado.');
            }

            // 1. Validar que el alumno esté inscrito formalmente en el grupo y/o asignatura correspondiente
            $asignacion = $activacion->asignacion;
            $estaInscritoGrupo = EstudianteInscripcion::where('estudiante_id', $alumnoUser->usuario_id)
                ->where('grupo_id', $asignacion->grupo_id)
                ->where('periodo_id', $asignacion->periodo_id)
                ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
                ->exists();

            if (\Illuminate\Support\Facades\Schema::hasTable('estudiante_asignatura_inscripciones')) {
                $inscritoMateria = \App\Models\EstudianteAsignaturaInscripcion::where('estudiante_id', $alumnoUser->usuario_id)
                    ->where(function ($q) use ($asignacion) {
                        $q->where('asignacion_id', $asignacion->asignacion_id)
                          ->orWhere(function ($sub) use ($asignacion) {
                              $sub->where('asignatura_id', $asignacion->asignatura_id)
                                  ->where('grupo_id', $asignacion->grupo_id);
                          });
                    })
                    ->where('estado', \App\Models\EstudianteAsignaturaInscripcion::ESTADO_APROBADA)
                    ->exists();

                if (!$inscritoMateria && !$estaInscritoGrupo) {
                    throw new \DomainException('Solo los estudiantes inscritos en esta materia y grupo pueden solicitar esta recompensa.');
                }
            } elseif (!$estaInscritoGrupo) {
                throw new \DomainException('Solo los estudiantes inscritos en esta materia y grupo pueden solicitar esta recompensa.');
            }

            // 2. Validar que no tenga ya un beneficio pendiente de uso para esta misma activación
            $yaCanjeado = RecompensaCanje::where('activacion_id', $activacionId)
                ->where('estudiante_id', $alumnoUser->usuario_id)
                ->whereIn('estado', [
                    RecompensaCanje::ESTADO_CANJEADA,
                    RecompensaCanje::ESTADO_SOLICITADO,
                    RecompensaCanje::ESTADO_APROBADO
                ])
                ->exists();

            if ($yaCanjeado) {
                throw new \DomainException('Ya cuentas con un beneficio digital adquirido para esta recompensa pendiente de uso.');
            }

            // 3. Validar saldo de puntos sin permitir saldos negativos
            if ($estudiante->saldo_puntos < $activacion->costo_puntos) {
                throw new \DomainException("No cuentas con saldo suficiente de puntos ({$estudiante->saldo_puntos} pts disponibles, requeridos: {$activacion->costo_puntos} pts).");
            }

            // 4. Descontar puntos y cupo de manera atómica
            $estudiante->decrement('saldo_puntos', $activacion->costo_puntos);
            $activacion->decrement('cupo_disponible', 1);

            // Generar código de beneficio digital único
            $codigoBeneficio = 'BEN-' . strtoupper(Str::random(10));
            $snapshotCondiciones = $activacion->condiciones_docente ?: $activacion->recompensaCatalogo->condiciones_generales;

            $canje = RecompensaCanje::create([
                'activacion_id' => $activacion->activacion_id,
                'estudiante_id' => $alumnoUser->usuario_id,
                'codigo_beneficio' => $codigoBeneficio,
                'puntos_canjeados' => $activacion->costo_puntos,
                'estado' => RecompensaCanje::ESTADO_CANJEADA,
                'motivo_solicitud' => $motivo ? trim($motivo) : null,
                'fecha_solicitud' => now(),
                'fecha_canje' => now(),
                'condiciones_snapshot' => $snapshotCondiciones,
            ]);

            app(\App\Services\Puntos\PuntoService::class)->registrarGastoCanje($alumnoUser, $canje, (int) $activacion->costo_puntos);

            Log::info("Beneficio digital canjeado inmediatamente", [
                'canje_id' => $canje->canje_id,
                'codigo_beneficio' => $codigoBeneficio,
                'estudiante_id' => $alumnoUser->usuario_id,
                'activacion_id' => $activacion->activacion_id,
                'puntos' => $activacion->costo_puntos,
            ]);

            return $canje;
        });
    }

    /**
     * El docente titular marca el beneficio digital como UTILIZADO.
     */
    public function utilizarBeneficio(RecompensaCanje $canje, User $docenteUser): RecompensaCanje
    {
        if (!$canje->esPendienteDeUso()) {
            throw new \DomainException('Este beneficio digital no se encuentra en estado pendiente de utilización.');
        }

        $canje->estado = RecompensaCanje::ESTADO_UTILIZADA;
        $canje->fecha_utilizacion = now();
        $canje->fecha_resolucion = now();
        $canje->utilizado_por = $docenteUser->usuario_id;
        $canje->resuelto_por = $docenteUser->usuario_id;
        $canje->save();

        Log::info("Beneficio #{$canje->canje_id} ({$canje->codigo_beneficio}) marcado como UTILIZADO por docente #{$docenteUser->usuario_id}");

        return $canje;
    }

    /**
     * Aprueba un canje de recompensa (compatibilidad con flujo previo).
     */
    public function aprobarCanje(RecompensaCanje $canje, User $docenteUser): RecompensaCanje
    {
        $canje->estado = RecompensaCanje::ESTADO_APROBADO;
        $canje->fecha_resolucion = now();
        $canje->resuelto_por = $docenteUser->usuario_id;
        $canje->save();

        return $canje;
    }

    /**
     * Rechaza un canje y devuelve los puntos al estudiante y el cupo a la activación.
     */
    public function rechazarCanje(RecompensaCanje $canje, User $docenteUser, string $motivoRechazo): RecompensaCanje
    {
        return DB::transaction(function () use ($canje, $docenteUser, $motivoRechazo) {
            // Reembolsar puntos al estudiante
            $estudiante = Estudiante::where('usuario_id', $canje->estudiante_id)->lockForUpdate()->first();
            if ($estudiante) {
                $estudiante->increment('saldo_puntos', $canje->puntos_canjeados);
            }

            // Restaurar cupo disponible
            $activacion = RecompensaActivada::where('activacion_id', $canje->activacion_id)->lockForUpdate()->first();
            if ($activacion) {
                $activacion->increment('cupo_disponible', 1);
            }

            $canje->estado = RecompensaCanje::ESTADO_RECHAZADO;
            $canje->motivo_rechazo = trim($motivoRechazo);
            $canje->fecha_resolucion = now();
            $canje->resuelto_por = $docenteUser->usuario_id;
            $canje->save();

            $usuarioEstudiante = User::find($canje->estudiante_id);
            if ($usuarioEstudiante) {
                app(\App\Services\Puntos\PuntoService::class)->registrarReembolsoCanje($usuarioEstudiante, $canje, (int) $canje->puntos_canjeados);
            }

            Log::info("Canje #{$canje->canje_id} rechazado con reembolso de puntos");

            return $canje;
        });
    }

    /**
     * Cancela la solicitud o canje por parte del propio estudiante antes de que sea utilizado.
     */
    public function cancelarCanjePorAlumno(RecompensaCanje $canje, User $alumnoUser): RecompensaCanje
    {
        if ((int) $canje->estudiante_id !== (int) $alumnoUser->usuario_id) {
            throw new \DomainException('No eres el titular de esta solicitud de canje.');
        }

        if (!$canje->esPendienteDeUso()) {
            throw new \DomainException('Solo puedes cancelar beneficios que aún no hayan sido utilizados.');
        }

        return DB::transaction(function () use ($canje, $alumnoUser) {
            // Reembolsar puntos
            $estudiante = Estudiante::where('usuario_id', $alumnoUser->usuario_id)->lockForUpdate()->first();
            if ($estudiante) {
                $estudiante->increment('saldo_puntos', $canje->puntos_canjeados);
            }

            // Restaurar cupo
            $activacion = RecompensaActivada::where('activacion_id', $canje->activacion_id)->lockForUpdate()->first();
            if ($activacion) {
                $activacion->increment('cupo_disponible', 1);
            }

            $canje->estado = RecompensaCanje::ESTADO_CANCELADO;
            $canje->fecha_resolucion = now();
            $canje->resuelto_por = $alumnoUser->usuario_id;
            $canje->save();

            app(\App\Services\Puntos\PuntoService::class)->registrarReembolsoCanje($alumnoUser, $canje, (int) $canje->puntos_canjeados);

            Log::info("Canje #{$canje->canje_id} cancelado con reembolso de puntos");

            return $canje;
        });
    }

    /**
     * Cambia el estado de una activación docente (ACTIVA, SUSPENDIDA, FINALIZADA).
     */
    public function cambiarEstadoActivacion(RecompensaActivada $activacion, string $nuevoEstado): RecompensaActivada
    {
        if (!in_array($nuevoEstado, [RecompensaActivada::ESTADO_ACTIVA, RecompensaActivada::ESTADO_SUSPENDIDA, RecompensaActivada::ESTADO_FINALIZADA], true)) {
            throw new \DomainException('Estado de activación no válido.');
        }

        $activacion->estado = $nuevoEstado;
        $activacion->save();

        return $activacion;
    }

    /**
     * Un docente propone un beneficio personalizado para sus materias.
     */
    public function proponerRecompensaDocente(
        User $docenteUser,
        string $nombre,
        string $descripcion,
        string $categoria,
        int $costoPuntos,
        ?string $condiciones,
        string $justificacion
    ): RecompensaCatalogo {
        $propuesta = RecompensaCatalogo::create([
            'nombre' => trim($nombre),
            'descripcion' => trim($descripcion),
            'categoria' => $categoria,
            'costo_puntos_base' => $costoPuntos,
            'condiciones_generales' => $condiciones ? trim($condiciones) : null,
            'docente_solicitante_id' => $docenteUser->usuario_id,
            'estado_propuesta' => RecompensaCatalogo::PROPUESTA_PENDIENTE,
            'justificacion_docente' => trim($justificacion),
            'activo' => false,
        ]);

        try {
            $admins = User::whereHas('rol', fn($q) => $q->where('nombre', 'Administrador'))
                ->where(function ($q) {
                    $q->where('estado', 'ACTIVO')->orWhereNull('estado');
                })
                ->get();
            $docenteNombre = trim(($docenteUser->nombre ?? '') . ' ' . ($docenteUser->apellido_paterno ?? ''));
            foreach ($admins as $admin) {
                try {
                    $admin->notify(new \App\Notifications\PropuestaRecompensaPendienteNotification(
                        nombreRecompensa: $propuesta->nombre,
                        docenteNombre: $docenteNombre ?: 'Docente',
                        costoPuntos: (int) $propuesta->costo_puntos_base
                    ));
                } catch (\Throwable $e) {
                    Log::warning("Error notificando propuesta docente a admin #{$admin->usuario_id}: {$e->getMessage()}");
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Error procesando notificaciones de propuesta docente: {$e->getMessage()}");
        }

        return $propuesta;
    }

    /**
     * El administrador aprueba una propuesta de recompensa para el docente que la solicitó.
     * Operación transaccional e idempotente.
     */
    public function aprobarPropuestaAdmin(RecompensaCatalogo $recompensa, User $adminUser): RecompensaCatalogo
    {
        if ($recompensa->estado_propuesta === RecompensaCatalogo::PROPUESTA_APROBADA) {
            return $recompensa;
        }

        $recompensa->estado_propuesta = RecompensaCatalogo::PROPUESTA_APROBADA;
        $recompensa->admin_resolutor_id = $adminUser->usuario_id;
        $recompensa->fecha_resolucion = now();
        $recompensa->motivo_resolucion_admin = null;
        $recompensa->activo = true;
        $recompensa->save();

        return $recompensa;
    }

    /**
     * El administrador rechaza una propuesta docente de recompensa con justificación.
     * Operación transaccional e idempotente.
     */
    public function rechazarPropuestaAdmin(RecompensaCatalogo $recompensa, User $adminUser, string $motivo): RecompensaCatalogo
    {
        if ($recompensa->estado_propuesta === RecompensaCatalogo::PROPUESTA_RECHAZADA && $recompensa->motivo_resolucion_admin === trim($motivo)) {
            return $recompensa;
        }

        $recompensa->estado_propuesta = RecompensaCatalogo::PROPUESTA_RECHAZADA;
        $recompensa->admin_resolutor_id = $adminUser->usuario_id;
        $recompensa->fecha_resolucion = now();
        $recompensa->motivo_resolucion_admin = trim($motivo);
        $recompensa->activo = false;
        $recompensa->save();

        return $recompensa;
    }
}
