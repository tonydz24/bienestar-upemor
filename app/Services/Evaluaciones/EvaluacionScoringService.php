<?php

namespace App\Services\Evaluaciones;

use App\Models\EscalaOpcion;
use App\Models\EvaluacionIntento;
use App\Models\PreguntaEvaluacion;
use App\Models\VersionInstrumento;
use DomainException;
use Illuminate\Support\Facades\DB;

class EvaluacionScoringService
{
    /**
     * Calcula de forma determinista la puntuación de un reactivo individual,
     * aplicando inversión de escala si la pregunta está marcada como inversa.
     * Soporta ESCALA, OPCION_UNICA, OPCION_MULTIPLE y TEXTO_LIBRE (0 puntos).
     */
    public function calcularPuntosReactivo(
        PreguntaEvaluacion $pregunta,
        ?EscalaOpcion $escala = null,
        ?VersionInstrumento $version = null,
        ?\App\Models\EvaluacionRespuesta $respuesta = null
    ): int {
        $tipo = strtoupper((string) ($pregunta->tipo_pregunta ?: 'ESCALA'));

        // A3: Las preguntas abiertas no reciben puntaje clínico automático
        if ($tipo === 'TEXTO_LIBRE') {
            return 0;
        }

        if ($escala !== null) {
            $valorBase = (int) $escala->valor;

            if (! $pregunta->es_inverso) {
                return $valorBase;
            }

            // Si el reactivo es inverso, se invierte respecto a los extremos de la versión
            $version = $version ?? $pregunta->version ?? VersionInstrumento::find($pregunta->version_id);

            if (! $version) {
                return $valorBase;
            }

            $min = (int) ($version->escalas->min('valor') ?? 0);
            $max = (int) ($version->escalas->max('valor') ?? 0);

            return ($min + $max) - $valorBase;
        }

        $opciones = is_array($pregunta->opciones_json) ? $pregunta->opciones_json : [];
        $seleccionadas = is_array($respuesta?->opciones_seleccionadas) ? $respuesta->opciones_seleccionadas : [];

        if (! empty($opciones) && ! empty($seleccionadas)) {
            $valoresCatalogo = array_map(fn ($o) => (int) ($o['valor'] ?? $o['puntos'] ?? 0), $opciones);
            $puntos = 0;

            foreach ($opciones as $idx => $op) {
                $etiqueta = (string) ($op['etiqueta'] ?? $op['texto'] ?? '');
                $valorOp = (int) ($op['valor'] ?? $op['puntos'] ?? 0);
                if (in_array($etiqueta, $seleccionadas, true) || in_array((string) $idx, array_map('strval', $seleccionadas), true)) {
                    $puntos += $valorOp;
                    if ($tipo === 'OPCION_UNICA') {
                        if ($pregunta->es_inverso && count($valoresCatalogo) > 0) {
                            $puntos = (min($valoresCatalogo) + max($valoresCatalogo)) - $valorOp;
                        }
                        break;
                    }
                }
            }

            return $puntos;
        }

        return (int) ($respuesta?->puntos_obtenidos ?? 0);
    }

    /**
     * Calcula la puntuación total y determina el baremo aplicable de un intento.
     * Valida la integridad estricta de preguntas y opciones contra la versión evaluada.
     *
     * @throws DomainException si el intento está incompleto o contiene reactivos/escalas inválidas.
     */
    public function calcularPuntuacionIntento(EvaluacionIntento $intento): array
    {
        $version = $intento->version()->with(['preguntasActivas', 'escalas', 'baremos'])->first();

        if (! $version) {
            throw new DomainException('No se encontró la versión asociada al intento de evaluación.');
        }

        $preguntasActivas = $version->preguntasActivas;
        $totalPreguntas = $preguntasActivas->count();

        if ($totalPreguntas === 0) {
            throw new DomainException('El instrumento no cuenta con reactivos activos para calificar.');
        }

        $respuestas = $intento->respuestas()->with(['pregunta', 'escala'])->get()->keyBy('pregunta_id');

        // 1. Verificación de obligatoriedad de los reactivos obligatorios
        $faltantes = [];
        foreach ($preguntasActivas as $pregunta) {
            $esObligatoria = $pregunta->es_obligatoria ?? true;
            if ($esObligatoria && ! $respuestas->has($pregunta->pregunta_id)) {
                $faltantes[] = $pregunta->numero_reactivo;
            }
        }

        if (! empty($faltantes)) {
            $conteoFaltantes = count($faltantes);
            throw new DomainException("No se puede calificar un intento incompleto. Faltan {$conteoFaltantes} reactivo(s) por responder.");
        }

        // 2. Cálculo estricto de puntos por reactivo y verificación de integridad
        $puntosTotales = 0;
        $tieneAlertaCritica = false;
        $maxValorEscala = (int) ($version->escalas->max('valor') ?? 0);
        $puntuacionMaximaPosible = 0;

        foreach ($preguntasActivas as $pregunta) {
            $tipo = strtoupper((string) ($pregunta->tipo_pregunta ?: 'ESCALA'));
            $opciones = is_array($pregunta->opciones_json) ? $pregunta->opciones_json : [];

            if ($tipo === 'TEXTO_LIBRE') {
                $maxPregunta = 0;
            } elseif (! empty($opciones)) {
                $vals = array_map(fn ($o) => max(0, (int) ($o['valor'] ?? $o['puntos'] ?? 0)), $opciones);
                $maxPregunta = $tipo === 'OPCION_MULTIPLE' ? array_sum($vals) : (count($vals) > 0 ? max($vals) : $maxValorEscala);
            } else {
                $maxPregunta = $maxValorEscala;
            }
            $puntuacionMaximaPosible += $maxPregunta;

            if (! $respuestas->has($pregunta->pregunta_id)) {
                continue;
            }

            $respuesta = $respuestas->get($pregunta->pregunta_id);
            $escala = $respuesta->escala;

            if ($pregunta->version_id !== $version->version_id) {
                throw new DomainException('Se detectaron respuestas u opciones inconsistentes con la versión del instrumento.');
            }

            if ($respuesta->escala_id !== null) {
                if (! $escala || $escala->version_id !== $version->version_id) {
                    throw new DomainException('Se detectaron respuestas u opciones inconsistentes con la versión del instrumento.');
                }
                $puntosItem = $this->calcularPuntosReactivo($pregunta, $escala, $version, $respuesta);
            } else {
                $puntosItem = $this->calcularPuntosReactivo($pregunta, null, $version, $respuesta);
            }

            $puntosTotales += $puntosItem;

            if ($pregunta->es_critico && $puntosItem > 0) {
                $tieneAlertaCritica = true;
            }
        }

        // 3. Porcentaje relativo
        $porcentaje = $puntuacionMaximaPosible > 0
            ? (int) round(($puntosTotales / $puntuacionMaximaPosible) * 100)
            : 0;

        // 4. Búsqueda determinista de regla de baremo
        $baremo = $version->baremos->first(function ($regla) use ($puntosTotales) {
            return $puntosTotales >= $regla->puntuacion_min && $puntosTotales <= $regla->puntuacion_max;
        });

        return [
            'puntuacion_total' => $puntosTotales,
            'puntuacion_maxima' => $puntuacionMaximaPosible,
            'porcentaje' => $porcentaje,
            'nivel_resultado' => $baremo?->nivel ?? 'COMPLETADO',
            'color_hex' => $baremo?->color_hex ?? '#7c3aed',
            'mensaje_retroalimentacion' => $baremo?->mensaje_retroalimentacion,
            'sugiere_orientacion' => (bool) ($baremo?->sugiere_orientacion ?? false),
            'tiene_alerta_critica' => $tieneAlertaCritica,
            'total_preguntas' => $totalPreguntas,
            'total_respondidas' => $respuestas->count(),
            'baremo' => $baremo,
        ];
    }

    /**
     * Finaliza de manera atómica e idempotente el intento de evaluación,
     * persistiendo la puntuación calculada en el servidor y protegiendo
     * contra ejecuciones concurrentes o modificaciones posteriores.
     */
    public function finalizarYCalificarIntento(EvaluacionIntento $intento, ?string $ip = null): EvaluacionIntento
    {
        // Si el intento ya está finalizado, retornar sin alterar el registro (idempotencia)
        if ($intento->estaFinalizado()) {
            return $intento;
        }

        return DB::transaction(function () use ($intento, $ip) {
            // Bloqueo pesimista de fila para evitar finalizaciones concurrentes
            $intentoBloqueado = EvaluacionIntento::where('intento_id', $intento->intento_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($intentoBloqueado->estaFinalizado()) {
                return $intentoBloqueado;
            }

            // Calcular puntuación y validar integridad
            $scoreData = $this->calcularPuntuacionIntento($intentoBloqueado);

            // Actualizar puntos calculados en cada respuesta para consistencia absoluta
            $version = $intentoBloqueado->version()->with('escalas')->first();
            foreach ($intentoBloqueado->respuestas()->with(['pregunta', 'escala'])->get() as $resp) {
                $puntosItem = $this->calcularPuntosReactivo($resp->pregunta, $resp->escala, $version, $resp);
                if ($resp->puntos_obtenidos !== $puntosItem) {
                    $resp->update(['puntos_obtenidos' => $puntosItem]);
                }
            }

            // Persistir estado finalizado definitivo
            $intentoBloqueado->update([
                'estado' => 'FINALIZADO',
                'fecha_finalizacion' => now(),
                'puntuacion_total' => $scoreData['puntuacion_total'],
                'nivel_resultado' => $scoreData['nivel_resultado'],
                'tiene_alerta_critica' => $scoreData['tiene_alerta_critica'],
                'ip_registro' => $ip ?? request()->ip(),
            ]);

            return $intentoBloqueado->fresh(['version.instrumento', 'respuestas']);
        });
    }
}

