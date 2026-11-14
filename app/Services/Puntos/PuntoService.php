<?php

namespace App\Services\Puntos;

use App\Models\BitacoraEmocional;
use App\Models\Encuesta;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\MeditacionSesion;
use App\Models\PuntoMovimiento;
use App\Models\RecompensaCanje;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PuntoService
{
    public const LIMITE_ORDINARIO_DIARIO = 5;
    public const PUNTOS_EMOCION_DIARIA = 1;
    public const PUNTOS_MEDITACION = 2;
    public const MAX_MEDITACIONES_DIARIAS_BONIFICADAS = 2;
    public const PUNTOS_EVALUACION = 5;
    public const PUNTOS_ENCUESTA = 1;
    public const PUNTOS_RUTINA_SEMANAL = 3;

    /**
     * Otorga 1 punto de bienestar al estudiante por registrar su bitácora emocional diaria.
     * Regla: Máximo 1 punto por día de registro. Idéntico para cualquier emoción (neutralidad ética).
     * Si el registro anterior fue eliminado y su bonificación revertida, permite recuperar el bono diario.
     */
    public function otorgarPorEmocionDiaria(User $usuario, BitacoraEmocional $bitacora): ?PuntoMovimiento
    {
        $fecha = Carbon::parse($bitacora->fecha)->toDateString();

        return DB::transaction(function () use ($usuario, $bitacora, $fecha) {
            $estudiante = Estudiante::where('usuario_id', $usuario->usuario_id)
                ->lockForUpdate()
                ->first();

            if (!$estudiante) {
                return null;
            }

            // Verificar si ya existe una bonificación emocional activa (no revertida) en esta fecha
            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                if ($this->tieneBonificacionEmocionActiva($usuario->usuario_id, $fecha)) {
                    return null;
                }

                // Verificar si se alcanzó el límite diario ordinario considerando bonificaciones netas
                if ($this->haAlcanzadoLimiteDiario($usuario->usuario_id, $fecha, self::PUNTOS_EMOCION_DIARIA)) {
                    return null;
                }
            }

            $saldoAnterior = (int) $estudiante->saldo_puntos;
            $puntosOtorgados = self::PUNTOS_EMOCION_DIARIA;
            $saldoNuevo = $saldoAnterior + $puntosOtorgados;

            $estudiante->saldo_puntos = $saldoNuevo;
            $estudiante->save();

            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                return PuntoMovimiento::create([
                    'estudiante_id' => $usuario->usuario_id,
                    'tipo' => PuntoMovimiento::TIPO_EMOCION_DIARIA,
                    'puntos' => $puntosOtorgados,
                    'saldo_anterior' => $saldoAnterior,
                    'saldo_nuevo' => $saldoNuevo,
                    'referencia_tipo' => BitacoraEmocional::class,
                    'referencia_id' => $bitacora->bitacora_id,
                    'fecha' => $fecha,
                    'descripcion' => 'Registro diario en bitácora emocional',
                ]);
            }

            return null;
        });
    }

    /**
     * Determina si el estudiante tiene actualmente una bonificación activa de emoción diaria
     * para una fecha específica (es decir, otorgada y no compensada por una reversión).
     */
    public function tieneBonificacionEmocionActiva(int $estudianteId, string $fecha): bool
    {
        if (!Schema::hasTable('estudiante_puntos_movimientos')) {
            return false;
        }

        // Buscar movimientos de emoción diaria para la fecha que aún no tengan reversión compensatoria
        $otorgamientos = PuntoMovimiento::where('estudiante_id', $estudianteId)
            ->where('tipo', PuntoMovimiento::TIPO_EMOCION_DIARIA)
            ->whereDate('fecha', $fecha)
            ->get();

        foreach ($otorgamientos as $otorgamiento) {
            $haSidoRevertido = PuntoMovimiento::where('estudiante_id', $estudianteId)
                ->where('tipo', PuntoMovimiento::TIPO_AJUSTE_ADMINISTRATIVO)
                ->where('puntos', '<', 0)
                ->where(function ($q) use ($otorgamiento) {
                    if ($otorgamiento->referencia_id) {
                        $q->where('referencia_id', $otorgamiento->referencia_id);
                    }
                    $q->orWhere('referencia_id', $otorgamiento->movimiento_id);
                })
                ->exists();

            if (!$haSidoRevertido) {
                return true;
            }
        }

        return false;
    }

    /**
     * Revierte de forma trazable e idempotente los puntos de bienestar otorgados por un registro de bitácora emocional.
     * Si los puntos otorgados ya fueron utilizados en canjes de recompensas y el saldo actual es insuficiente,
     * arroja una DomainException para impedir la eliminación y proteger la integridad contable.
     *
     * @throws \DomainException Si el saldo actual es insuficiente para cubrir la reversión contable.
     * @return array{revertido: bool, puntos: int, saldo_anterior: int, saldo_nuevo: int}
     */
    public function revertirPorEliminacionEmocion(User $usuario, BitacoraEmocional $bitacora): array
    {
        $fecha = Carbon::parse($bitacora->fecha)->toDateString();

        return DB::transaction(function () use ($usuario, $bitacora, $fecha) {
            $estudiante = Estudiante::where('usuario_id', $usuario->usuario_id)
                ->lockForUpdate()
                ->first();

            if (!$estudiante) {
                return ['revertido' => false, 'puntos' => 0, 'saldo_anterior' => 0, 'saldo_nuevo' => 0];
            }

            $saldoAnterior = (int) $estudiante->saldo_puntos;

            if (!Schema::hasTable('estudiante_puntos_movimientos')) {
                return ['revertido' => false, 'puntos' => 0, 'saldo_anterior' => $saldoAnterior, 'saldo_nuevo' => $saldoAnterior];
            }

            // Identificar el movimiento original exacto que otorgó puntos a este registro específico
            // o a la fecha que aún no haya sido revertido
            $movimientoPrevio = PuntoMovimiento::where('estudiante_id', $usuario->usuario_id)
                ->where('tipo', PuntoMovimiento::TIPO_EMOCION_DIARIA)
                ->where(function ($q) use ($bitacora, $fecha) {
                    $q->where(function ($sq) use ($bitacora) {
                        $sq->where('referencia_tipo', BitacoraEmocional::class)
                           ->where('referencia_id', $bitacora->bitacora_id);
                    })->orWhere(function ($sq) use ($fecha) {
                        $sq->whereDate('fecha', $fecha);
                    });
                })
                ->orderBy('movimiento_id', 'desc')
                ->get()
                ->first(function ($mov) use ($usuario, $bitacora) {
                    // Solo considerar un movimiento que no tenga ya una reversión registrada
                    return !PuntoMovimiento::where('estudiante_id', $usuario->usuario_id)
                        ->where('tipo', PuntoMovimiento::TIPO_AJUSTE_ADMINISTRATIVO)
                        ->where('puntos', '<', 0)
                        ->where(function ($q) use ($bitacora, $mov) {
                            $q->where('referencia_id', $bitacora->bitacora_id)
                              ->orWhere('referencia_id', $mov->movimiento_id);
                        })
                        ->exists();
                });

            if (!$movimientoPrevio) {
                // Este registro no otorgó puntos o ya fue compensado
                return ['revertido' => false, 'puntos' => 0, 'saldo_anterior' => $saldoAnterior, 'saldo_nuevo' => $saldoAnterior];
            }

            $puntosOtorgados = abs((int) $movimientoPrevio->puntos);

            // Regla contable: Verificar si el saldo actual es suficiente para revertir
            if ($saldoAnterior < $puntosOtorgados) {
                throw new \DomainException("No es posible eliminar este registro emocional porque los {$puntosOtorgados} puntos otorgados ya fueron utilizados en canjes de recompensas y tu saldo disponible actual ({$saldoAnterior} pts) es insuficiente para cubrir la reversión contable.");
            }

            $saldoNuevo = $saldoAnterior - $puntosOtorgados;
            $estudiante->saldo_puntos = $saldoNuevo;
            $estudiante->save();

            PuntoMovimiento::create([
                'estudiante_id' => $usuario->usuario_id,
                'tipo' => PuntoMovimiento::TIPO_AJUSTE_ADMINISTRATIVO,
                'puntos' => -$puntosOtorgados,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'referencia_tipo' => BitacoraEmocional::class,
                'referencia_id' => $bitacora->bitacora_id,
                'fecha' => $fecha,
                'descripcion' => "Reversión contable por eliminación de registro en bitácora emocional (" . Carbon::parse($bitacora->fecha)->format('d/m/Y') . ")",
            ]);

            return [
                'revertido' => true,
                'puntos' => $puntosOtorgados,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
            ];
        });
    }

    /**
     * Otorga 2 puntos de bienestar al estudiante por completar un ejercicio de relajación o meditación guiada.
     * Regla: Máximo 2 actividades de meditación bonificadas al día (4 pts). No otorga puntos por solo abrir.
     */
    public function otorgarPorMeditacion(User $usuario, MeditacionSesion $sesion): ?PuntoMovimiento
    {
        if (!$sesion->completada) {
            return null;
        }

        $fecha = Carbon::parse($sesion->fecha_sesion ?? now())->toDateString();

        return DB::transaction(function () use ($usuario, $sesion, $fecha) {
            $estudiante = Estudiante::where('usuario_id', $usuario->usuario_id)
                ->lockForUpdate()
                ->first();

            if (!$estudiante) {
                return null;
            }

            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                // Idempotencia: Verificar si esta sesión específica ya fue bonificada
                $yaBonificada = PuntoMovimiento::where('estudiante_id', $usuario->usuario_id)
                    ->where('referencia_tipo', MeditacionSesion::class)
                    ->where('referencia_id', $sesion->sesion_id)
                    ->exists();

                if ($yaBonificada) {
                    return null;
                }

                // Verificar si ya completó el máximo de meditaciones bonificadas en este día
                $conteoMeditacionesDia = PuntoMovimiento::where('estudiante_id', $usuario->usuario_id)
                    ->where('tipo', PuntoMovimiento::TIPO_MEDITACION)
                    ->whereDate('fecha', $fecha)
                    ->count();

                if ($conteoMeditacionesDia >= self::MAX_MEDITACIONES_DIARIAS_BONIFICADAS) {
                    return null;
                }

                if ($this->haAlcanzadoLimiteDiario($usuario->usuario_id, $fecha, self::PUNTOS_MEDITACION)) {
                    return null;
                }
            }

            $saldoAnterior = (int) $estudiante->saldo_puntos;
            $puntosOtorgados = self::PUNTOS_MEDITACION;
            $saldoNuevo = $saldoAnterior + $puntosOtorgados;

            $estudiante->saldo_puntos = $saldoNuevo;
            $estudiante->save();

            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                $tituloMed = $sesion->meditacion?->titulo ?? 'Ejercicio de relajación';
                return PuntoMovimiento::create([
                    'estudiante_id' => $usuario->usuario_id,
                    'tipo' => PuntoMovimiento::TIPO_MEDITACION,
                    'puntos' => $puntosOtorgados,
                    'saldo_anterior' => $saldoAnterior,
                    'saldo_nuevo' => $saldoNuevo,
                    'referencia_tipo' => MeditacionSesion::class,
                    'referencia_id' => $sesion->sesion_id,
                    'fecha' => $fecha,
                    'descripcion' => "Práctica de bienestar: {$tituloMed}",
                ]);
            }

            return null;
        });
    }

    /**
     * Otorga 5 puntos de bienestar por completar un cuestionario psicométrico o instrumento de tamizaje autorizado.
     * Regla: Una vez por instrumento y periodo de reaplicación elegible. No condiciona el puntaje al resultado obtenido.
     */
    public function otorgarPorEvaluacion(User $usuario, EvaluacionIntento $intento): ?PuntoMovimiento
    {
        if (!$intento->estaFinalizado()) {
            return null;
        }

        $fecha = Carbon::parse($intento->fecha_finalizacion ?? now())->toDateString();

        return DB::transaction(function () use ($usuario, $intento, $fecha) {
            $estudiante = Estudiante::where('usuario_id', $usuario->usuario_id)
                ->lockForUpdate()
                ->first();

            if (!$estudiante) {
                return null;
            }

            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                $yaBonificado = PuntoMovimiento::where('estudiante_id', $usuario->usuario_id)
                    ->where('referencia_tipo', EvaluacionIntento::class)
                    ->where('referencia_id', $intento->intento_id)
                    ->exists();

                if ($yaBonificado) {
                    return null;
                }
            }

            $saldoAnterior = (int) $estudiante->saldo_puntos;
            $puntosOtorgados = self::PUNTOS_EVALUACION;
            $saldoNuevo = $saldoAnterior + $puntosOtorgados;

            $estudiante->saldo_puntos = $saldoNuevo;
            $estudiante->save();

            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                $nombreInst = $intento->version?->instrumento?->nombre ?? 'Evaluación psicoemocional';
                return PuntoMovimiento::create([
                    'estudiante_id' => $usuario->usuario_id,
                    'tipo' => PuntoMovimiento::TIPO_EVALUACION,
                    'puntos' => $puntosOtorgados,
                    'saldo_anterior' => $saldoAnterior,
                    'saldo_nuevo' => $saldoNuevo,
                    'referencia_tipo' => EvaluacionIntento::class,
                    'referencia_id' => $intento->intento_id,
                    'fecha' => $fecha,
                    'descripcion' => "Tamizaje completado: {$nombreInst}",
                ]);
            }

            return null;
        });
    }

    /**
     * Otorga 1 punto de bienestar por responder una encuesta institucional.
     * Para encuestas anónimas, resguarda el secreto vinculando el movimiento solo al ID de la encuesta, no a la respuesta nominal.
     */
    public function otorgarPorEncuesta(User $usuario, int $encuestaId): ?PuntoMovimiento
    {
        $fecha = now()->toDateString();

        return DB::transaction(function () use ($usuario, $encuestaId, $fecha) {
            $estudiante = Estudiante::where('usuario_id', $usuario->usuario_id)
                ->lockForUpdate()
                ->first();

            if (!$estudiante) {
                return null;
            }

            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                $yaBonificada = PuntoMovimiento::where('estudiante_id', $usuario->usuario_id)
                    ->where('tipo', PuntoMovimiento::TIPO_ENCUESTA)
                    ->where('referencia_id', $encuestaId)
                    ->exists();

                if ($yaBonificada) {
                    return null;
                }
            }

            $saldoAnterior = (int) $estudiante->saldo_puntos;
            $puntosOtorgados = self::PUNTOS_ENCUESTA;
            $saldoNuevo = $saldoAnterior + $puntosOtorgados;

            $estudiante->saldo_puntos = $saldoNuevo;
            $estudiante->save();

            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                $encuesta = Encuesta::find($encuestaId);
                $tituloEnc = $encuesta?->titulo ?? 'Encuesta institucional';
                return PuntoMovimiento::create([
                    'estudiante_id' => $usuario->usuario_id,
                    'tipo' => PuntoMovimiento::TIPO_ENCUESTA,
                    'puntos' => $puntosOtorgados,
                    'saldo_anterior' => $saldoAnterior,
                    'saldo_nuevo' => $saldoNuevo,
                    'referencia_tipo' => Encuesta::class,
                    'referencia_id' => $encuestaId,
                    'fecha' => $fecha,
                    'descripcion' => "Participación en encuesta: {$tituloEnc}",
                ]);
            }

            return null;
        });
    }

    /**
     * Registra en el libro mayor la deducción de puntos por canje de beneficio académico digital.
     */
    public function registrarGastoCanje(User $usuario, RecompensaCanje $canje, int $costoPuntos): ?PuntoMovimiento
    {
        $fecha = now()->toDateString();

        if (Schema::hasTable('estudiante_puntos_movimientos')) {
            $estudiante = Estudiante::where('usuario_id', $usuario->usuario_id)->first();
            $saldoActual = (int) ($estudiante?->saldo_puntos ?? 0);
            $nombrePremio = $canje->activacion?->recompensaCatalogo?->titulo ?? 'Beneficio académico';

            return PuntoMovimiento::create([
                'estudiante_id' => $usuario->usuario_id,
                'tipo' => PuntoMovimiento::TIPO_CANJE_RECOMPENSA,
                'puntos' => -abs($costoPuntos),
                'saldo_anterior' => $saldoActual + $costoPuntos,
                'saldo_nuevo' => $saldoActual,
                'referencia_tipo' => RecompensaCanje::class,
                'referencia_id' => $canje->canje_id,
                'fecha' => $fecha,
                'descripcion' => "Canje de beneficio: {$nombrePremio} ({$canje->codigo_beneficio})",
            ]);
        }

        return null;
    }

    /**
     * Registra en el libro mayor el reembolso íntegro de puntos por cancelación o rechazo de un canje.
     */
    public function registrarReembolsoCanje(User $usuario, RecompensaCanje $canje, int $puntosReembolsados): ?PuntoMovimiento
    {
        $fecha = now()->toDateString();

        if (Schema::hasTable('estudiante_puntos_movimientos')) {
            $estudiante = Estudiante::where('usuario_id', $usuario->usuario_id)->first();
            $saldoActual = (int) ($estudiante?->saldo_puntos ?? 0);
            $nombrePremio = $canje->activacion?->recompensaCatalogo?->titulo ?? 'Beneficio';

            return PuntoMovimiento::create([
                'estudiante_id' => $usuario->usuario_id,
                'tipo' => PuntoMovimiento::TIPO_REEMBOLSO_CANJE,
                'puntos' => abs($puntosReembolsados),
                'saldo_anterior' => $saldoActual - $puntosReembolsados,
                'saldo_nuevo' => $saldoActual,
                'referencia_tipo' => RecompensaCanje::class,
                'referencia_id' => $canje->canje_id,
                'fecha' => $fecha,
                'descripcion' => "Reembolso por canje cancelado: {$nombrePremio}",
            ]);
        }

        return null;
    }

    /**
     * Verifica si el estudiante ya acumuló el tope ordinario diario de actividades rutinarias (5 puntos).
     * Considera las bonificaciones netas (descontando reversiones en caso de eliminación de bitácoras).
     */
    protected function haAlcanzadoLimiteDiario(int $estudianteId, string $fecha, int $puntosPorOtorgar): bool
    {
        $puntosGanadosHoy = PuntoMovimiento::where('estudiante_id', $estudianteId)
            ->whereDate('fecha', $fecha)
            ->whereIn('tipo', [PuntoMovimiento::TIPO_EMOCION_DIARIA, PuntoMovimiento::TIPO_MEDITACION])
            ->where('puntos', '>', 0)
            ->sum('puntos');

        $reversionesHoy = PuntoMovimiento::where('estudiante_id', $estudianteId)
            ->whereDate('fecha', $fecha)
            ->where('tipo', PuntoMovimiento::TIPO_AJUSTE_ADMINISTRATIVO)
            ->where('puntos', '<', 0)
            ->sum('puntos');

        $puntosNetos = max(0, $puntosGanadosHoy + $reversionesHoy);

        return ($puntosNetos + $puntosPorOtorgar) > self::LIMITE_ORDINARIO_DIARIO;
    }

    /**
     * Obtiene el historial paginado de movimientos de puntos del estudiante.
     */
    public function obtenerHistorial(User $usuario, int $porPagina = 10)
    {
        if (!Schema::hasTable('estudiante_puntos_movimientos')) {
            return collect();
        }

        return PuntoMovimiento::where('estudiante_id', $usuario->usuario_id)
            ->orderBy('created_at', 'desc')
            ->orderBy('movimiento_id', 'desc')
            ->paginate($porPagina);
    }
}

