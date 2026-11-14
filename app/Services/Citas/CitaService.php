<?php

namespace App\Services\Citas;

use App\Models\CitaDisponibilidad;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\CitaReprogramacionHistorial;
use App\Models\User;
use App\Notifications\ReprogramacionAprobadaNotification;
use App\Notifications\ReprogramacionRechazadaNotification;
use App\Notifications\SolicitudReprogramacionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CitaService
{
    /**
     * Obtiene los bloques de horarios disponibles para un profesional en una fecha específica.
     *
     * @return array<int, array{hora_inicio: string, hora_fin: string, disponible: bool, motivo_no_disponible: ?string, modalidad: string}>
     */
    public function obtenerHorariosDisponibles(CitaProfesional $profesional, Carbon $fecha): array
    {
        if (!$profesional->activo || !$profesional->permite_citas) {
            return [];
        }

        // Si la fecha es pasada, no hay horarios disponibles
        if ($fecha->clone()->startOfDay()->lt(now()->startOfDay())) {
            return [];
        }

        $diaSemana = $fecha->dayOfWeekIso; // 1 = Lunes, 7 = Domingo

        $disponibilidades = CitaDisponibilidad::where('profesional_id', $profesional->profesional_id)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->orderBy('hora_inicio')
            ->get();

        if ($disponibilidades->isEmpty()) {
            return [];
        }

        // Consultar citas ya programadas (activas) en esta fecha para este profesional
        $citasOcupadas = CitaOrientacion::where('profesional_id', $profesional->profesional_id)
            ->whereDate('fecha_cita', $fecha->toDateString())
            ->whereIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
            ->get(['hora_inicio', 'hora_fin'])
            ->keyBy(fn ($cita) => substr($cita->hora_inicio, 0, 5));

        $slots = [];

        foreach ($disponibilidades as $disp) {
            $duracion = $disp->duracion_cita_min ?: 45;
            $cursor = Carbon::createFromTimeString($disp->hora_inicio);
            $finBloque = Carbon::createFromTimeString($disp->hora_fin);

            while ($cursor->clone()->addMinutes($duracion)->lte($finBloque)) {
                $horaInicioStr = $cursor->format('H:i');
                $cursorFin = $cursor->clone()->addMinutes($duracion);
                $horaFinStr = $cursorFin->format('H:i');

                $disponible = true;
                $motivoNoDisponible = null;

                // Verificar si ya está ocupado por otra cita activa
                if ($citasOcupadas->has($horaInicioStr)) {
                    $disponible = false;
                    $motivoNoDisponible = 'Horario reservado';
                }

                // Si es hoy, verificar si el horario ya pasó en tiempo real
                if ($fecha->isToday()) {
                    $momentoSlot = Carbon::createFromFormat('Y-m-d H:i', $fecha->format('Y-m-d') . ' ' . $horaInicioStr);
                    if ($momentoSlot->lte(now()->addMinutes(15))) {
                        $disponible = false;
                        $motivoNoDisponible = 'Horario pasado o con margen insuficiente';
                    }
                }

                $slots[] = [
                    'hora_inicio' => $horaInicioStr,
                    'hora_fin' => $horaFinStr,
                    'disponible' => $disponible,
                    'motivo_no_disponible' => $motivoNoDisponible,
                    'modalidad' => $disp->modalidad,
                ];

                $cursor->addMinutes($duracion);
            }
        }

        return $slots;
    }

    /**
     * Detecta si un nuevo horario propuesto se solapa con algún bloque activo existente del profesional en el mismo día.
     */
    public function detectarSolapamientoDisponibilidad(
        int $profesionalId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $excluirId = null
    ): ?CitaDisponibilidad {
        $hIniNorm = strlen($horaInicio) === 5 ? $horaInicio . ':00' : $horaInicio;
        $hFinNorm = strlen($horaFin) === 5 ? $horaFin . ':00' : $horaFin;

        $query = CitaDisponibilidad::where('profesional_id', $profesionalId)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->where(function ($q) use ($hIniNorm, $hFinNorm) {
                // Intervalo [hIni, hFin] solapa con [disp.hora_inicio, disp.hora_fin] si:
                // hIni < disp.hora_fin AND hFin > disp.hora_inicio
                $q->where('hora_inicio', '<', $hFinNorm)
                  ->where('hora_fin', '>', $hIniNorm);
            });

        if ($excluirId) {
            $query->where('disponibilidad_id', '!=', $excluirId);
        }

        return $query->first();
    }

    /**
     * Cuenta cuántas citas activas (solicitadas o confirmadas) en fechas futuras quedarían afectadas
     * si se modifica o elimina una regla de disponibilidad horaria.
     */
    public function contarCitasAfectadasPorDisponibilidad(CitaDisponibilidad $disponibilidad): int
    {
        return CitaOrientacion::where('profesional_id', $disponibilidad->profesional_id)
            ->whereIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
            ->where('fecha_cita', '>=', now()->toDateString())
            ->get()
            ->filter(function ($cita) use ($disponibilidad) {
                $diaCita = Carbon::parse($cita->fecha_cita)->dayOfWeekIso;
                if ($diaCita !== $disponibilidad->dia_semana) {
                    return false;
                }
                return ($cita->hora_inicio < $disponibilidad->hora_fin && $cita->hora_fin > $disponibilidad->hora_inicio);
            })
            ->count();
    }

    /**
     * Elimina una regla de disponibilidad horaria verificando que no comprometa citas activas.
     *
     * @return array{success: bool, bloqueado?: bool, citas_afectadas?: int, mensaje: string}
     */
    public function eliminarDisponibilidad(CitaProfesional $profesional, int $disponibilidadId): array
    {
        return DB::transaction(function () use ($profesional, $disponibilidadId) {
            // Bloqueo pesimista del profesional para serializar concurrencia
            CitaProfesional::where('profesional_id', $profesional->profesional_id)
                ->lockForUpdate()
                ->first();

            $disp = CitaDisponibilidad::where('disponibilidad_id', $disponibilidadId)
                ->where('profesional_id', $profesional->profesional_id)
                ->lockForUpdate()
                ->first();

            if (!$disp) {
                return [
                    'success' => false,
                    'bloqueado' => false,
                    'mensaje' => 'El horario de atención no fue encontrado o no pertenece a tu cuenta.',
                ];
            }

            // Comprobar citas activas (SOLICITADA o CONFIRMADA) que quedarían desprotegidas
            $citasAfectadas = $this->contarCitasAfectadasPorDisponibilidad($disp);

            if ($citasAfectadas > 0) {
                $horarioStr = substr($disp->hora_inicio, 0, 5) . ' a ' . substr($disp->hora_fin, 0, 5);
                return [
                    'success' => false,
                    'bloqueado' => true,
                    'citas_afectadas' => $citasAfectadas,
                    'mensaje' => "No es posible eliminar el horario del {$disp->nombre_dia} ({$horarioStr} hrs) porque existen {$citasAfectadas} cita(s) activa(s) (solicitadas o confirmadas) programadas en ese intervalo. Para resguardar la atención de los estudiantes, debes resolver o concluir esas citas antes de retirar el bloque.",
                ];
            }

            $disp->delete();

            return [
                'success' => true,
                'bloqueado' => false,
                'mensaje' => 'Regla de horario eliminada correctamente.',
            ];
        });
    }

    /**
     * Guarda un bloque de disponibilidad validando solapamientos y protegiendo citas existentes.
     *
     * @return array{success: bool, conflicto?: bool, mensaje?: string, disponibilidad?: CitaDisponibilidad, bloque_existente?: string, reemplazar_id?: int}
     */
    public function guardarDisponibilidad(
        CitaProfesional $profesional,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        int $duracionMin,
        string $modalidad,
        bool $confirmarReemplazo = false,
        ?int $reemplazarId = null
    ): array {
        $hIniNorm = strlen($horaInicio) === 5 ? $horaInicio . ':00' : $horaInicio;
        $hFinNorm = strlen($horaFin) === 5 ? $horaFin . ':00' : $horaFin;

        return DB::transaction(function () use (
            $profesional, $diaSemana, $hIniNorm, $hFinNorm, $duracionMin, $modalidad, $confirmarReemplazo, $reemplazarId
        ) {
            // Detección de solapamiento
            $conflicto = $this->detectarSolapamientoDisponibilidad(
                $profesional->profesional_id,
                $diaSemana,
                $hIniNorm,
                $hFinNorm,
                null
            );

            if ($conflicto) {
                // Verificar si el bloque en conflicto tiene citas activas
                $citasAfectadas = $this->contarCitasAfectadasPorDisponibilidad($conflicto);

                if ($citasAfectadas > 0) {
                    return [
                        'success' => false,
                        'conflicto' => true,
                        'bloqueado' => true,
                        'mensaje' => "El nuevo horario se superpone con un bloque existente ({$conflicto->nombre_dia} de " . substr($conflicto->hora_inicio, 0, 5) . " a " . substr($conflicto->hora_fin, 0, 5) . " hrs) que ya cuenta con {$citasAfectadas} cita(s) activa(s) programada(s). Por protección a los estudiantes, no es posible reemplazarlo automáticamente.",
                    ];
                }

                if (!$confirmarReemplazo) {
                    $nombreDia = $conflicto->nombre_dia;
                    $horarioExistente = substr($conflicto->hora_inicio, 0, 5) . " a " . substr($conflicto->hora_fin, 0, 5);
                    $horarioNuevo = substr($hIniNorm, 0, 5) . " a " . substr($hFinNorm, 0, 5);

                    return [
                        'success' => false,
                        'conflicto' => true,
                        'bloqueado' => false,
                        'reemplazar_id' => $conflicto->disponibilidad_id,
                        'bloque_existente' => "{$nombreDia} de {$horarioExistente} hrs",
                        'mensaje' => "Ya tienes registrado un horario para el {$nombreDia} de {$horarioExistente}. El nuevo horario de {$horarioNuevo} se superpone. ¿Deseas reemplazar el horario anterior?",
                    ];
                }

                // Si se confirmó el reemplazo y no hay citas en riesgo, eliminar el bloque anterior
                $conflicto->delete();
            }

            // Crear el nuevo bloque de horario
            $nueva = CitaDisponibilidad::create([
                'profesional_id' => $profesional->profesional_id,
                'dia_semana' => $diaSemana,
                'hora_inicio' => $hIniNorm,
                'hora_fin' => $hFinNorm,
                'duracion_cita_min' => $duracionMin,
                'modalidad' => $modalidad,
                'activo' => true,
            ]);

            return [
                'success' => true,
                'conflicto' => false,
                'disponibilidad' => $nueva,
                'mensaje' => 'Horario de atención guardado correctamente.',
            ];
        });
    }

    /**
     * Registra una nueva solicitud de cita con control estricto de concurrencia y transacciones atómicas.
     *
     * @throws \DomainException|\InvalidArgumentException
     */
    public function solicitarCita(
        User $alumnoUser,
        int $profesionalId,
        string $fecha,
        string $horaInicio,
        string $modalidad,
        ?string $motivo = null,
        ?string $ip = null
    ): CitaOrientacion {
        return DB::transaction(function () use ($alumnoUser, $profesionalId, $fecha, $horaInicio, $modalidad, $motivo, $ip) {
            // Exclusión mutua garantizada: Bloqueo pesimista del registro del profesional
            // Esto serializa las solicitudes concurrentes para el mismo profesional.
            $profesional = CitaProfesional::where('profesional_id', $profesionalId)
                ->where('activo', true)
                ->where('permite_citas', true)
                ->lockForUpdate()
                ->first();

            if (!$profesional) {
                throw new \DomainException('El profesional seleccionado no está habilitado para recibir citas de orientación.');
            }

            // Validar modalidad
            if ($profesional->modalidades_permitidas !== 'AMBAS' && $profesional->modalidades_permitidas !== $modalidad) {
                throw new \DomainException("El profesional únicamente atiende en modalidad {$profesional->modalidades_permitidas}.");
            }

            $fechaObj = Carbon::parse($fecha);

            if ($fechaObj->startOfDay()->lt(now()->startOfDay())) {
                throw new \DomainException('No puedes agendar una cita en una fecha pasada.');
            }

            if ($fechaObj->diffInDays(now()) > 30) {
                throw new \DomainException('Solo es posible solicitar citas con un máximo de 30 días de anticipación.');
            }

            $diaSemana = $fechaObj->dayOfWeekIso;

            $horaInicioCarbon = Carbon::createFromTimeString($horaInicio);
            $horaInicioNorm = $horaInicioCarbon->format('H:i:s');

            // Verificar que exista una regla de disponibilidad que cubra este slot
            $disponibilidad = CitaDisponibilidad::where('profesional_id', $profesionalId)
                ->where('dia_semana', $diaSemana)
                ->where('activo', true)
                ->where('hora_inicio', '<=', $horaInicioNorm)
                ->where('hora_fin', '>=', $horaInicioNorm)
                ->first();

            if (!$disponibilidad) {
                throw new \DomainException('El horario seleccionado no corresponde a la disponibilidad del orientador.');
            }

            $duracion = $disponibilidad->duracion_cita_min ?: 45;
            $horaFinStr = $horaInicioCarbon->clone()->addMinutes($duracion)->format('H:i:s');

            // Si es hoy, validar que falten al menos 15 minutos
            if ($fechaObj->isToday()) {
                $momentoCita = Carbon::createFromFormat('Y-m-d H:i:s', $fechaObj->format('Y-m-d') . ' ' . $horaInicioNorm);
                if ($momentoCita->lte(now()->addMinutes(15))) {
                    throw new \DomainException('El horario solicitado ya no está disponible por margen de tiempo mínimo.');
                }
            }

            // 1. Detección exhaustiva de citas activas superpuestas en el intervalo solicitado
            $conflicto = CitaOrientacion::where('profesional_id', $profesionalId)
                ->whereDate('fecha_cita', $fechaObj->toDateString())
                ->whereIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
                ->where(function ($q) use ($horaInicioNorm, $horaFinStr) {
                    $q->where('hora_inicio', '<', $horaFinStr)
                      ->where('hora_fin', '>', $horaInicioNorm);
                })
                ->exists();

            if ($conflicto) {
                throw new \DomainException('Lo sentimos, este horario acaba de ser reservado. Por favor selecciona otro horario disponible.');
            }

            // 2. Validar que el estudiante no tenga otra cita simultánea o superpuesta
            $citaEstudianteConflicto = CitaOrientacion::where('estudiante_id', $alumnoUser->usuario_id)
                ->whereDate('fecha_cita', $fechaObj->toDateString())
                ->whereIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
                ->where(function ($q) use ($horaInicioNorm, $horaFinStr) {
                    $q->where('hora_inicio', '<', $horaFinStr)
                      ->where('hora_fin', '>', $horaInicioNorm);
                })
                ->exists();

            if ($citaEstudianteConflicto) {
                throw new \DomainException('Ya cuentas con una cita activa registrada en este mismo horario.');
            }

            // 3. Limitar citas pendientes simultáneas (máximo 2 pendientes para evitar acaparamiento)
            $citasPendientes = CitaOrientacion::where('estudiante_id', $alumnoUser->usuario_id)
                ->where('estado', CitaOrientacion::ESTADO_SOLICITADA)
                ->count();

            if ($citasPendientes >= 2) {
                throw new \DomainException('Tienes 2 citas en espera de confirmación. Espera la resolución de alguna antes de solicitar otra.');
            }

            // Determinar ubicación preliminar
            $lugarOEnlace = $modalidad === CitaOrientacion::MODALIDAD_PRESENCIAL
                ? ($profesional->lugar_atencion ?: 'Cubículo del Departamento de Orientación y Tutorías')
                : 'Enlace virtual institucional (se enviará al confirmar)';

            $cita = CitaOrientacion::create([
                'estudiante_id' => $alumnoUser->usuario_id,
                'profesional_id' => $profesionalId,
                'fecha_cita' => $fechaObj->toDateString(),
                'hora_inicio' => $horaInicioNorm,
                'hora_fin' => $horaFinStr,
                'modalidad' => $modalidad,
                'lugar_o_enlace' => $lugarOEnlace,
                'estado' => CitaOrientacion::ESTADO_SOLICITADA,
                'motivo_consulta' => $motivo ? trim($motivo) : null,
                'fecha_solicitud' => now(),
                'ip_registro' => $ip,
            ]);

            // Auditoría segura sin registrar información clínica o privada
            Log::info("Cita solicitada exitosamente", [
                'cita_id' => $cita->cita_id,
                'estudiante_id' => $alumnoUser->usuario_id,
                'profesional_id' => $profesionalId,
                'fecha' => $fechaObj->toDateString(),
                'hora' => $horaInicioNorm,
            ]);

            return $cita;
        });
    }

    /**
     * Confirma una cita solicitada asignando lugar o enlace definitivo si aplica.
     */
    public function confirmarCita(CitaOrientacion $cita, User $profesionalUser, ?string $lugarOEnlace = null): CitaOrientacion
    {
        if ($cita->estado !== CitaOrientacion::ESTADO_SOLICITADA) {
            throw new \DomainException("Solo se pueden confirmar citas en estado SOLICITADA. Estado actual: {$cita->estado}");
        }

        $cita->estado = CitaOrientacion::ESTADO_CONFIRMADA;
        $cita->fecha_resolucion = now();
        $cita->resuelto_por = $profesionalUser->usuario_id;

        if ($lugarOEnlace !== null && trim($lugarOEnlace) !== '') {
            $cita->lugar_o_enlace = trim($lugarOEnlace);
        }

        $cita->save();

        Log::info("Cita #{$cita->cita_id} confirmada por orientador #{$profesionalUser->usuario_id}");

        return $cita;
    }

    /**
     * Rechaza una cita con motivo visible para el estudiante y libera el horario.
     */
    public function rechazarCita(CitaOrientacion $cita, User $profesionalUser, string $motivo): CitaOrientacion
    {
        if ($cita->estado !== CitaOrientacion::ESTADO_SOLICITADA) {
            throw new \DomainException("Solo se pueden rechazar citas en estado SOLICITADA.");
        }

        $cita->estado = CitaOrientacion::ESTADO_RECHAZADA;
        $cita->motivo_cancelacion_rechazo = trim($motivo);
        $cita->fecha_resolucion = now();
        $cita->resuelto_por = $profesionalUser->usuario_id;
        $cita->save();

        Log::info("Cita #{$cita->cita_id} rechazada por orientador #{$profesionalUser->usuario_id}");

        return $cita;
    }

    /**
     * Cancela la cita a petición del estudiante y libera el horario de inmediato.
     */
    public function cancelarPorAlumno(CitaOrientacion $cita, User $alumnoUser, ?string $motivo = null): CitaOrientacion
    {
        if ($cita->estudiante_id !== $alumnoUser->usuario_id) {
            throw new \DomainException('No tienes autorización para cancelar esta cita.');
        }

        if (!$cita->puedeSerCanceladaPorAlumno()) {
            throw new \DomainException('Esta cita no puede cancelarse en su estado o fecha actual.');
        }

        $cita->estado = CitaOrientacion::ESTADO_CANCELADA_ALUMNO;
        $cita->motivo_cancelacion_rechazo = $motivo ? trim($motivo) : 'Cancelada voluntariamente por el estudiante';
        $cita->fecha_resolucion = now();
        $cita->resuelto_por = $alumnoUser->usuario_id;
        $cita->save();

        Log::info("Cita #{$cita->cita_id} cancelada voluntariamente por estudiante #{$alumnoUser->usuario_id}");

        return $cita;
    }

    /**
     * Cancela la cita por parte del orientador (imprevisto, reprogramación necesaria).
     */
    public function cancelarPorProfesional(CitaOrientacion $cita, User $profesionalUser, string $motivo): CitaOrientacion
    {
        if (!$cita->isActiva()) {
            throw new \DomainException('Solo es posible cancelar citas que se encuentren activas.');
        }

        $cita->estado = CitaOrientacion::ESTADO_CANCELADA_PROFESIONAL;
        $cita->motivo_cancelacion_rechazo = trim($motivo);
        $cita->fecha_resolucion = now();
        $cita->resuelto_por = $profesionalUser->usuario_id;
        $cita->save();

        Log::info("Cita #{$cita->cita_id} cancelada por el profesional #{$profesionalUser->usuario_id}");

        return $cita;
    }

    /**
     * Marca la cita como finalizada / atendida exitosamente.
     */
    public function finalizarCita(CitaOrientacion $cita, User $profesionalUser): CitaOrientacion
    {
        if ($cita->estado !== CitaOrientacion::ESTADO_CONFIRMADA) {
            throw new \DomainException('Solo es posible marcar como finalizada una cita previamente confirmada.');
        }

        $cita->estado = CitaOrientacion::ESTADO_FINALIZADA;
        $cita->fecha_resolucion = now();
        $cita->resuelto_por = $profesionalUser->usuario_id;
        $cita->save();

        Log::info("Cita #{$cita->cita_id} finalizada por el orientador #{$profesionalUser->usuario_id}");

        return $cita;
    }

    /**
     * Registra que el estudiante no se presentó a la cita confirmada.
     */
    public function marcarNoAsistio(CitaOrientacion $cita, User $profesionalUser): CitaOrientacion
    {
        if ($cita->estado !== CitaOrientacion::ESTADO_CONFIRMADA) {
            throw new \DomainException('Solo es posible registrar inasistencia en citas previamente confirmadas.');
        }

        $cita->estado = CitaOrientacion::ESTADO_NO_ASISTIO;
        $cita->fecha_resolucion = now();
        $cita->resuelto_por = $profesionalUser->usuario_id;
        $cita->save();

        Log::info("Cita #{$cita->cita_id} marcada como NO ASISTIÓ por orientador #{$profesionalUser->usuario_id}");

        return $cita;
    }

    /**
     * Registra una solicitud de reprogramación por parte del alumno sin alterar la fecha confirmada.
     */
    public function solicitarReprogramacion(
        CitaOrientacion $cita,
        User $alumnoUser,
        Carbon $nuevaFecha,
        string $horaInicio,
        ?string $motivo = null
    ): CitaOrientacion {
        if ($cita->estudiante_id !== $alumnoUser->usuario_id) {
            throw new \DomainException('No tienes autorización para reprogramar esta cita.');
        }

        if (!$cita->puedeSolicitarReprogramacion()) {
            throw new \DomainException('Esta cita no es elegible para reprogramación en su estado o fecha actual.');
        }

        if ($nuevaFecha->clone()->startOfDay()->lt(now()->startOfDay())) {
            throw new \DomainException('No puedes solicitar reprogramación para una fecha pasada.');
        }

        $profesional = $cita->profesional;
        if (!$profesional || !$profesional->activo || !$profesional->permite_citas) {
            throw new \DomainException('El orientador seleccionado no se encuentra activo para recibir citas.');
        }

        $horaInicioNorm = strlen($horaInicio) === 5 ? $horaInicio . ':00' : $horaInicio;
        $horaInicioHM = substr($horaInicioNorm, 0, 5);

        // Validar que el bloque exista en los horarios configurados del profesional
        $horariosDisponibles = $this->obtenerHorariosDisponibles($profesional, $nuevaFecha);
        $slotElegido = collect($horariosDisponibles)->firstWhere('hora_inicio', $horaInicioHM);

        if (!$slotElegido) {
            throw new \DomainException("El orientador no ofrece atención el {$nuevaFecha->format('d/m/Y')} a las {$horaInicioHM} hrs.");
        }

        if (!$slotElegido['disponible']) {
            $motivoNoDisp = $slotElegido['motivo_no_disponible'] ?? 'Horario no disponible';
            throw new \DomainException("El horario solicitado no se encuentra disponible ({$motivoNoDisp}).");
        }

        $horaFinNorm = strlen($slotElegido['hora_fin']) === 5 ? $slotElegido['hora_fin'] . ':00' : $slotElegido['hora_fin'];

        // Validar que el estudiante no tenga otra cita activa ese mismo día y hora
        $otraCitaAlumno = CitaOrientacion::where('estudiante_id', $alumnoUser->usuario_id)
            ->where('cita_id', '!=', $cita->cita_id)
            ->whereDate('fecha_cita', $nuevaFecha->toDateString())
            ->whereIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
            ->where(function ($q) use ($horaInicioNorm, $horaFinNorm) {
                $q->where('hora_inicio', $horaInicioNorm)
                  ->orWhereBetween('hora_inicio', [$horaInicioNorm, $horaFinNorm]);
            })
            ->exists();

        if ($otraCitaAlumno) {
            throw new \DomainException('Ya tienes otra cita activa programada en ese mismo día y horario.');
        }

        return DB::transaction(function () use ($cita, $alumnoUser, $nuevaFecha, $horaInicioNorm, $horaFinNorm, $motivo) {
            $citaBloqueada = CitaOrientacion::where('cita_id', $cita->cita_id)->lockForUpdate()->firstOrFail();

            if ($citaBloqueada->tieneReprogramacionPendiente()) {
                throw new \DomainException('Ya existe una solicitud de reprogramación pendiente para esta cita.');
            }

            $citaBloqueada->reprogramacion_estado = CitaOrientacion::REPROGRAMACION_PENDIENTE;
            $citaBloqueada->reprogramacion_fecha = $nuevaFecha->toDateString();
            $citaBloqueada->reprogramacion_hora_inicio = $horaInicioNorm;
            $citaBloqueada->reprogramacion_hora_fin = $horaFinNorm;
            $citaBloqueada->reprogramacion_motivo = $motivo ? trim($motivo) : null;
            $citaBloqueada->reprogramacion_respuesta_motivo = null;
            $citaBloqueada->reprogramacion_fecha_solicitud = now();
            $citaBloqueada->save();

            if (Schema::hasTable('citas_reprogramaciones_historial')) {
                try {
                    CitaReprogramacionHistorial::create([
                        'cita_id' => $citaBloqueada->cita_id,
                        'estudiante_id' => $alumnoUser->usuario_id,
                        'profesional_id' => $citaBloqueada->profesional_id,
                        'fecha_original' => $citaBloqueada->fecha_cita,
                        'hora_inicio_original' => $citaBloqueada->hora_inicio,
                        'hora_fin_original' => $citaBloqueada->hora_fin,
                        'fecha_propuesta' => $nuevaFecha->toDateString(),
                        'hora_inicio_propuesta' => $horaInicioNorm,
                        'hora_fin_propuesta' => $horaFinNorm,
                        'motivo_solicitud' => $motivo ? trim($motivo) : null,
                        'estado' => CitaOrientacion::REPROGRAMACION_PENDIENTE,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("No se pudo registrar historial de reprogramación: {$e->getMessage()}");
                }
            }

            // Notificar al orientador
            if ($citaBloqueada->profesional?->usuario) {
                try {
                    $citaBloqueada->profesional->usuario->notify(new SolicitudReprogramacionNotification(
                        alumnoNombre: $alumnoUser->nombre . ' ' . $alumnoUser->apellido_paterno,
                        fechaOriginal: Carbon::parse($citaBloqueada->fecha_cita)->format('d/m/Y') . ' ' . substr($citaBloqueada->hora_inicio, 0, 5) . ' hrs',
                        fechaPropuesta: $nuevaFecha->format('d/m/Y'),
                        horaPropuesta: substr($horaInicioNorm, 0, 5),
                        motivo: $motivo
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo notificar la reprogramación al orientador: {$e->getMessage()}");
                }
            }

            return $citaBloqueada;
        });
    }

    /**
     * Aprueba la solicitud de reprogramación re-validando la disponibilidad en tiempo real.
     */
    public function aprobarReprogramacion(CitaOrientacion $cita, User $orientadorUser): CitaOrientacion
    {
        return DB::transaction(function () use ($cita, $orientadorUser) {
            $citaBloqueada = CitaOrientacion::with(['estudianteUsuario', 'profesional.usuario'])
                ->where('cita_id', $cita->cita_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($citaBloqueada->profesional?->usuario_id !== $orientadorUser->usuario_id && !$orientadorUser->rol?->es_admin && $orientadorUser->rol_id !== 1) {
                throw new \DomainException('No tienes autorización para resolver la reprogramación de esta cita.');
            }

            if ($citaBloqueada->reprogramacion_estado !== CitaOrientacion::REPROGRAMACION_PENDIENTE) {
                throw new \DomainException('Esta solicitud de reprogramación ya no se encuentra pendiente.');
            }

            $nuevaFechaStr = Carbon::parse($citaBloqueada->reprogramacion_fecha)->toDateString();
            $nuevaHoraInicio = $citaBloqueada->reprogramacion_hora_inicio;
            $nuevaHoraFin = $citaBloqueada->reprogramacion_hora_fin;

            // RE-VALIDACIÓN DE DISPONIBILIDAD ESTRICTA DENTRO DE LA TRANSACCIÓN
            $conflicto = CitaOrientacion::where('profesional_id', $citaBloqueada->profesional_id)
                ->where('cita_id', '!=', $citaBloqueada->cita_id)
                ->whereDate('fecha_cita', $nuevaFechaStr)
                ->whereIn('estado', [CitaOrientacion::ESTADO_SOLICITADA, CitaOrientacion::ESTADO_CONFIRMADA])
                ->where(function ($q) use ($nuevaHoraInicio, $nuevaHoraFin) {
                    $q->whereBetween('hora_inicio', [$nuevaHoraInicio, $nuevaHoraFin])
                      ->orWhereBetween('hora_fin', [$nuevaHoraInicio, $nuevaHoraFin])
                      ->orWhere(function ($sub) use ($nuevaHoraInicio, $nuevaHoraFin) {
                          $sub->where('hora_inicio', '<=', $nuevaHoraInicio)
                              ->where('hora_fin', '>=', $nuevaHoraFin);
                      });
                })
                ->lockForUpdate()
                ->first();

            if ($conflicto) {
                throw new \DomainException("El horario solicitado ({$nuevaFechaStr} a las " . substr($nuevaHoraInicio, 0, 5) . " hrs) ya fue reservado por otra cita activa. Se debe rechazar la solicitud o requerir al estudiante un nuevo horario.");
            }

            // Preservar trazabilidad histórica del horario previo
            $citaBloqueada->fecha_cita_anterior = $citaBloqueada->fecha_cita;
            $citaBloqueada->hora_inicio_anterior = $citaBloqueada->hora_inicio;
            $citaBloqueada->hora_fin_anterior = $citaBloqueada->hora_fin;

            // Actualizar a la nueva fecha y hora confirmada
            $citaBloqueada->fecha_cita = $nuevaFechaStr;
            $citaBloqueada->hora_inicio = $nuevaHoraInicio;
            $citaBloqueada->hora_fin = $nuevaHoraFin;
            $citaBloqueada->estado = CitaOrientacion::ESTADO_CONFIRMADA;
            $citaBloqueada->reprogramacion_estado = CitaOrientacion::REPROGRAMACION_APROBADA;
            $citaBloqueada->reprogramacion_fecha_resolucion = now();
            $citaBloqueada->reprogramacion_resuelto_por = $orientadorUser->usuario_id;
            $citaBloqueada->conteo_reprogramaciones = (int) $citaBloqueada->conteo_reprogramaciones + 1;
            $citaBloqueada->save();

            if (Schema::hasTable('citas_reprogramaciones_historial')) {
                try {
                    CitaReprogramacionHistorial::where('cita_id', $citaBloqueada->cita_id)
                        ->where('estado', CitaOrientacion::REPROGRAMACION_PENDIENTE)
                        ->update([
                            'estado' => CitaOrientacion::REPROGRAMACION_APROBADA,
                            'resuelto_por' => $orientadorUser->usuario_id,
                            'fecha_resolucion' => now(),
                        ]);
                } catch (\Throwable $e) {
                    Log::warning("No se pudo actualizar historial relacional de reprogramación: {$e->getMessage()}");
                }
            }

            // Notificar al estudiante
            if ($citaBloqueada->estudianteUsuario) {
                try {
                    $citaBloqueada->estudianteUsuario->notify(new ReprogramacionAprobadaNotification(
                        nuevaFecha: Carbon::parse($nuevaFechaStr)->format('d/m/Y'),
                        nuevaHora: substr($nuevaHoraInicio, 0, 5),
                        orientadorNombre: $orientadorUser->nombre . ' ' . $orientadorUser->apellido_paterno
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo notificar aprobación de reprogramación al estudiante: {$e->getMessage()}");
                }
            }

            return $citaBloqueada;
        });
    }

    /**
     * Rechaza la solicitud de reprogramación conservando íntegro el horario original de la cita.
     */
    public function rechazarReprogramacion(CitaOrientacion $cita, User $orientadorUser, string $motivo): CitaOrientacion
    {
        return DB::transaction(function () use ($cita, $orientadorUser, $motivo) {
            $citaBloqueada = CitaOrientacion::with(['estudianteUsuario', 'profesional.usuario'])
                ->where('cita_id', $cita->cita_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($citaBloqueada->profesional?->usuario_id !== $orientadorUser->usuario_id && !$orientadorUser->rol?->es_admin && $orientadorUser->rol_id !== 1) {
                throw new \DomainException('No tienes autorización para resolver la reprogramación de esta cita.');
            }

            if ($citaBloqueada->reprogramacion_estado !== CitaOrientacion::REPROGRAMACION_PENDIENTE) {
                throw new \DomainException('Esta solicitud de reprogramación ya no se encuentra pendiente.');
            }

            $motivoLimpio = trim($motivo);
            $citaBloqueada->reprogramacion_estado = CitaOrientacion::REPROGRAMACION_RECHAZADA;
            $citaBloqueada->reprogramacion_respuesta_motivo = $motivoLimpio;
            $citaBloqueada->reprogramacion_fecha_resolucion = now();
            $citaBloqueada->reprogramacion_resuelto_por = $orientadorUser->usuario_id;
            $citaBloqueada->save();

            if (Schema::hasTable('citas_reprogramaciones_historial')) {
                try {
                    CitaReprogramacionHistorial::where('cita_id', $citaBloqueada->cita_id)
                        ->where('estado', CitaOrientacion::REPROGRAMACION_PENDIENTE)
                        ->update([
                            'estado' => CitaOrientacion::REPROGRAMACION_RECHAZADA,
                            'motivo_respuesta' => $motivoLimpio,
                            'resuelto_por' => $orientadorUser->usuario_id,
                            'fecha_resolucion' => now(),
                        ]);
                } catch (\Throwable $e) {
                    Log::warning("No se pudo actualizar historial relacional de rechazo de reprogramación: {$e->getMessage()}");
                }
            }

            // Notificar al estudiante
            if ($citaBloqueada->estudianteUsuario) {
                try {
                    $citaBloqueada->estudianteUsuario->notify(new ReprogramacionRechazadaNotification(
                        fechaCita: Carbon::parse($citaBloqueada->fecha_cita)->format('d/m/Y'),
                        horaCita: substr($citaBloqueada->hora_inicio, 0, 5),
                        motivo: $motivoLimpio,
                        orientadorNombre: $orientadorUser->nombre . ' ' . $orientadorUser->apellido_paterno
                    ));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo notificar rechazo de reprogramación al estudiante: {$e->getMessage()}");
                }
            }

            return $citaBloqueada;
        });
    }

    /**
     * Permite al alumno cancelar voluntariamente su solicitud de reprogramación pendiente.
     */
    public function cancelarReprogramacion(CitaOrientacion $cita, User $alumnoUser): CitaOrientacion
    {
        return DB::transaction(function () use ($cita, $alumnoUser) {
            $citaBloqueada = CitaOrientacion::where('cita_id', $cita->cita_id)->lockForUpdate()->firstOrFail();

            if ($citaBloqueada->estudiante_id !== $alumnoUser->usuario_id) {
                throw new \DomainException('No tienes autorización para cancelar la solicitud de reprogramación.');
            }

            if ($citaBloqueada->reprogramacion_estado !== CitaOrientacion::REPROGRAMACION_PENDIENTE) {
                throw new \DomainException('No hay una solicitud de reprogramación pendiente para cancelar.');
            }

            $citaBloqueada->reprogramacion_estado = CitaOrientacion::REPROGRAMACION_CANCELADA;
            $citaBloqueada->reprogramacion_fecha_resolucion = now();
            $citaBloqueada->save();

            if (Schema::hasTable('citas_reprogramaciones_historial')) {
                try {
                    CitaReprogramacionHistorial::where('cita_id', $citaBloqueada->cita_id)
                        ->where('estado', CitaOrientacion::REPROGRAMACION_PENDIENTE)
                        ->update([
                            'estado' => CitaOrientacion::REPROGRAMACION_CANCELADA,
                            'fecha_resolucion' => now(),
                        ]);
                } catch (\Throwable $e) {
                    Log::warning("No se pudo actualizar historial relacional de cancelación de reprogramación: {$e->getMessage()}");
                }
            }

            return $citaBloqueada;
        });
    }
}
