<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\CitaOrientacion;
use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use App\Models\EncuestaRespuesta;
use App\Models\EncuestaRespuestaDetalle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EncuestaAlumnoController extends Controller
{
    /**
     * Catálogo de encuestas disponibles para el estudiante (FN.8).
     */
    public function index(): View
    {
        $usuario = Auth::user();
        $hoy = now()->toDateString();

        $encuestas = Encuesta::whereIn('estado', [Encuesta::ESTADO_PUBLICADA, Encuesta::ESTADO_CERRADA])
            ->withCount('preguntas')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($encuesta) use ($usuario, $hoy) {
                $userHash = hash('sha256', $usuario->usuario_id . '|' . $encuesta->encuesta_id . '|' . config('app.key'));
                $algunavez = $encuesta->respuestas()
                    ->where(function ($q) use ($usuario, $userHash) {
                        $q->where('usuario_id', $usuario->usuario_id)
                          ->orWhere('ip_hash', $userHash);
                    })
                    ->exists();

                if ($encuesta->estado === Encuesta::ESTADO_CERRADA) {
                    $encuesta->estado_alumno = 'CERRADA';
                } elseif ($encuesta->disponible_hasta && $encuesta->disponible_hasta->toDateString() < $hoy) {
                    $encuesta->estado_alumno = 'EXPIRADA';
                } elseif ($encuesta->disponible_desde && $encuesta->disponible_desde->toDateString() > $hoy) {
                    $encuesta->estado_alumno = 'PROXIMAMENTE';
                } elseif (!$encuesta->estaDisponible()) {
                    $encuesta->estado_alumno = 'NO_DISPONIBLE';
                } elseif ($encuesta->permite_multiples_respuestas) {
                    $encuesta->estado_alumno = $algunavez ? 'RESPONDIDA_DISPONIBLE' : 'DISPONIBLE';
                } else {
                    $encuesta->estado_alumno = $algunavez ? 'RESPONDIDA' : 'DISPONIBLE';
                }

                $encuesta->ya_respondio = ($encuesta->estado_alumno === 'RESPONDIDA');
                return $encuesta;
            });

        return view('alumno.encuestas.index', compact('encuestas'));
    }

    /**
     * Formulario para responder una encuesta.
     */
    public function responder(Encuesta $encuesta): View|RedirectResponse
    {
        $hoy = now()->toDateString();

        if ($encuesta->estado === Encuesta::ESTADO_CERRADA) {
            return redirect()->route('alumno.encuestas.index')
                ->with('error', 'Esta encuesta ha concluido y se encuentra cerrada por la administración institucional.');
        }

        if ($encuesta->disponible_hasta && $encuesta->disponible_hasta->toDateString() < $hoy) {
            return redirect()->route('alumno.encuestas.index')
                ->with('error', 'El plazo para responder esta encuesta ha concluido (venció el ' . $encuesta->disponible_hasta->format('d/m/Y') . ').');
        }

        if ($encuesta->disponible_desde && $encuesta->disponible_desde->toDateString() > $hoy) {
            return redirect()->route('alumno.encuestas.index')
                ->with('info', 'Esta encuesta estará disponible a partir del ' . $encuesta->disponible_desde->format('d/m/Y') . '.');
        }

        if (!$encuesta->estaDisponible()) {
            return redirect()->route('alumno.encuestas.index')
                ->with('error', 'Esta encuesta no se encuentra disponible actualmente.');
        }

        $usuario = Auth::user();

        if ($encuesta->haRespondido($usuario)) {
            return redirect()->route('alumno.encuestas.index')
                ->with('info', 'Ya has completado esta encuesta anteriormente. ¡Gracias por tu participación!');
        }

        $encuesta->load('preguntas');

        return view('alumno.encuestas.responder', compact('encuesta'));
    }

    /**
     * Guarda las respuestas enviadas por el alumno con protección de concurrencia e idempotencia.
     */
    public function guardar(Request $request, Encuesta $encuesta): RedirectResponse
    {
        if (!$encuesta->estaDisponible()) {
            return redirect()->route('alumno.encuestas.index')
                ->with('error', 'La encuesta ya no se encuentra activa o su vigencia ha concluido.');
        }

        $usuario = Auth::user();
        $citaId = $request->input('cita_id');
        $userHash = hash('sha256', $usuario->usuario_id . '|' . $encuesta->encuesta_id . '|' . config('app.key'));

        // Idempotencia persistente en base de datos: prevenir doble envío aunque fallen locks en memoria
        if (!$encuesta->permite_multiples_respuestas) {
            $yaExiste = EncuestaRespuesta::where('encuesta_id', $encuesta->encuesta_id)
                ->where(function ($q) use ($usuario, $userHash) {
                    $q->where('usuario_id', $usuario->usuario_id)
                      ->orWhere('ip_hash', $userHash);
                })
                ->exists();

            if ($yaExiste) {
                return redirect()->route('alumno.encuestas.index')
                    ->with('info', 'Ya has enviado tus respuestas para esta encuesta previamente.');
            }
        }

        // Candado atómico para prevenir dobles envíos y condiciones de carrera concurrentes
        $lockKey = 'encuesta_lock_' . $usuario->usuario_id . '_' . $encuesta->encuesta_id . '_' . ($citaId ?? '0');
        $lock = Cache::lock($lockKey, 15);

        if (!$lock->get()) {
            return redirect()->route('alumno.encuestas.index')
                ->with('info', 'Tus respuestas ya se están procesando. Por favor espera un momento.');
        }

        try {
            if ($encuesta->haRespondido($usuario, $citaId)) {
                return redirect()->route('alumno.encuestas.index')
                    ->with('info', 'Ya has enviado tus respuestas para esta encuesta.');
            }

            // Si es post-cita, validar existencia y pertenencia de la cita
            if ($encuesta->tipo === 'POST_CITA' && $citaId) {
                $cita = CitaOrientacion::where('cita_id', $citaId)
                    ->where('estudiante_id', $usuario->usuario_id)
                    ->first();
                if (!$cita) {
                    return redirect()->route('alumno.encuestas.index')
                        ->with('error', 'La cita especificada no es válida para esta evaluación.');
                }
            }

            // Validación dinámica según las preguntas configuradas
            $rules = [];
            $messages = [];

            foreach ($encuesta->preguntas as $p) {
                $key = "respuestas.{$p->pregunta_id}";
                if ($p->es_obligatoria) {
                    $rules[$key] = 'required';
                    $messages["{$key}.required"] = "La pregunta '{$p->texto_pregunta}' es obligatoria.";
                } else {
                    $rules[$key] = 'nullable';
                }
            }

            $request->validate($rules, $messages);

            $userHash = hash('sha256', $usuario->usuario_id . '|' . $encuesta->encuesta_id . '|' . config('app.key'));

            DB::transaction(function () use ($request, $encuesta, $usuario, $userHash, $citaId) {
                $respuesta = EncuestaRespuesta::create([
                    'encuesta_id' => $encuesta->encuesta_id,
                    'usuario_id' => $encuesta->es_anonima ? null : $usuario->usuario_id,
                    'cita_id' => $citaId,
                    'fecha_respuesta' => now(),
                    'ip_hash' => $userHash,
                ]);

                $inputRespuestas = $request->input('respuestas', []);

                foreach ($encuesta->preguntas as $pregunta) {
                    $val = $inputRespuestas[$pregunta->pregunta_id] ?? null;
                    if ($val === null && !$pregunta->es_obligatoria) {
                        continue;
                    }

                    $valorNumerico = null;
                    $textoRespuesta = null;
                    $opcionesSeleccionadas = null;

                    if ($pregunta->tipo_pregunta === EncuestaPregunta::TIPO_LIKERT || $pregunta->tipo_pregunta === EncuestaPregunta::TIPO_VALORACION) {
                        $valorNumerico = is_numeric($val) ? (float) $val : null;
                    } elseif ($pregunta->tipo_pregunta === EncuestaPregunta::TIPO_OPCION_MULTIPLE) {
                        $opcionesSeleccionadas = is_array($val) ? array_values($val) : [$val];
                    } else {
                        $textoRespuesta = is_string($val) ? trim($val) : (string) $val;
                    }

                    EncuestaRespuestaDetalle::create([
                        'respuesta_id' => $respuesta->respuesta_id,
                        'pregunta_id' => $pregunta->pregunta_id,
                        'valor_numerico' => $valorNumerico,
                        'texto_respuesta' => $textoRespuesta,
                        'opciones_seleccionadas' => $opcionesSeleccionadas,
                    ]);
                }
            });

            try {
                app(\App\Services\Puntos\PuntoService::class)->otorgarPorEncuesta($usuario, $encuesta->encuesta_id);
            } catch (\Throwable $e) {
                // Silencioso para tolerancia a fallos
            }

            return redirect()->route('alumno.encuestas.index')
                ->with('status', '¡Muchas gracias! Tus respuestas han sido registradas con éxito para la mejora de Bienestar UPEMOR.');
        } finally {
            $lock->release();
        }
    }

    /**
     * Encuesta post-cita de orientación psicológica.
     */
    public function postCita(CitaOrientacion $cita): View|RedirectResponse
    {
        $usuario = Auth::user();

        // Validar pertenencia de la cita
        if ($cita->estudiante_id !== $usuario->usuario_id) {
            abort(403);
        }

        $encuesta = Encuesta::where('tipo', Encuesta::TIPO_POST_CITA)
            ->where('estado', Encuesta::ESTADO_PUBLICADA)
            ->first();

        if (!$encuesta) {
            return redirect()->route('alumno.citas.index')
                ->with('info', 'No hay encuesta de satisfacción activa para esta sesión.');
        }

        // Validar si ya respondió para esta cita específica
        $yaRespondio = EncuestaRespuesta::where('encuesta_id', $encuesta->encuesta_id)
            ->where('cita_id', $cita->cita_id)
            ->exists();

        if ($yaRespondio) {
            return redirect()->route('alumno.citas.show', $cita)
                ->with('info', 'Ya has evaluado esta sesión de orientación. ¡Gracias por tu retroalimentación!');
        }

        $encuesta->load('preguntas');

        return view('alumno.encuestas.responder', compact('encuesta', 'cita'));
    }
}

