<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use App\Models\EncuestaRespuesta;
use App\Models\EncuestaRespuestaDetalle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EncuestaAdminController extends Controller
{
    /**
     * Listado administrativo de encuestas de satisfacción (FN.8).
     */
    public function index(Request $request): View
    {
        $query = Encuesta::withCount(['preguntas', 'respuestas'])->with('creador');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('titulo', 'LIKE', "%{$term}%")
                  ->orWhere('descripcion', 'LIKE', "%{$term}%");
            });
        }

        $encuestas = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $metricas = [
            'total' => Encuesta::count(),
            'publicadas' => Encuesta::where('estado', Encuesta::ESTADO_PUBLICADA)->count(),
            'borradores' => Encuesta::where('estado', Encuesta::ESTADO_BORRADOR)->count(),
            'total_respuestas' => EncuestaRespuesta::count(),
        ];

        return view('admin.encuestas.index', compact('encuestas', 'metricas'));
    }

    /**
     * Formulario para crear una nueva encuesta.
     */
    public function create(): View
    {
        return view('admin.encuestas.create');
    }

    /**
     * Almacena una nueva encuesta y sus preguntas asociadas.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'titulo' => 'required|string|max:150|unique:encuestas,titulo',
            'descripcion' => 'nullable|string|max:1000',
            'objetivo' => 'nullable|string|max:1000',
            'tipo' => 'required|in:GENERAL,POST_CITA',
            'disponible_desde' => 'nullable|date',
            'disponible_hasta' => 'nullable|date|after_or_equal:disponible_desde',
            'es_anonima' => 'nullable|boolean',
            'permite_multiples_respuestas' => 'nullable|boolean',
            'estado' => 'required|in:BORRADOR,PUBLICADA,CERRADA',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string|max:500',
            'preguntas.*.tipo' => 'required|in:LIKERT,OPCION_UNICA,OPCION_MULTIPLE,VALORACION,TEXTO_LIBRE',
            'preguntas.*.es_obligatoria' => 'nullable|boolean',
            'preguntas.*.opciones' => 'nullable|string',
        ], [
            'preguntas.required' => 'Debes configurar al menos una pregunta para la encuesta.',
            'preguntas.*.texto.required' => 'El texto de la pregunta es obligatorio.',
        ]);

        $encuesta = DB::transaction(function () use ($request) {
            $encuesta = Encuesta::create([
                'titulo' => trim($request->input('titulo')),
                'slug' => Str::slug($request->input('titulo')),
                'descripcion' => trim($request->input('descripcion')),
                'objetivo' => trim($request->input('objetivo')),
                'tipo' => $request->input('tipo'),
                'disponible_desde' => $request->input('disponible_desde'),
                'disponible_hasta' => $request->input('disponible_hasta'),
                'es_anonima' => $request->boolean('es_anonima'),
                'permite_multiples_respuestas' => $request->boolean('permite_multiples_respuestas'),
                'estado' => $request->input('estado'),
                'creado_por' => Auth::id(),
            ]);

            foreach ($request->input('preguntas') as $idx => $p) {
                $opciones = null;
                $tipo = $p['tipo'];

                if ($tipo === EncuestaPregunta::TIPO_LIKERT) {
                    $opciones = EncuestaPregunta::opcionesLikertEstandar();
                } elseif (in_array($tipo, [EncuestaPregunta::TIPO_OPCION_UNICA, EncuestaPregunta::TIPO_OPCION_MULTIPLE])) {
                    $lineas = array_filter(array_map('trim', explode("\n", $p['opciones'] ?? '')));
                    $opciones = [];
                    foreach ($lineas as $i => $linea) {
                        $opciones[] = ['valor' => 'opt_' . ($i + 1), 'etiqueta' => $linea];
                    }
                } elseif ($tipo === EncuestaPregunta::TIPO_VALORACION) {
                    $opciones = ['min' => 1, 'max' => 10];
                }

                $encuesta->preguntas()->create([
                    'orden' => $idx + 1,
                    'texto_pregunta' => trim($p['texto']),
                    'tipo_pregunta' => $tipo,
                    'es_obligatoria' => isset($p['es_obligatoria']) ? (bool) $p['es_obligatoria'] : true,
                    'opciones' => $opciones,
                ]);
            }

            return $encuesta;
        });

        if ($encuesta->estado === Encuesta::ESTADO_PUBLICADA) {
            $alumnos = User::whereHas('rol', fn($q) => $q->where('nombre', 'Alumno'))
                ->where('estado', 'ACTIVO')
                ->take(50)
                ->get();

            foreach ($alumnos as $alumno) {
                try {
                    $alumno->notify(new \App\Notifications\NuevaEncuestaNotification(
                        $encuesta->titulo,
                        $encuesta->encuesta_id,
                        (bool) $encuesta->es_anonima
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return redirect()->route('admin.encuestas.index')
            ->with('status', 'Encuesta de satisfacción creada exitosamente.');
    }

    /**
     * Formulario de edición con hidratación completa de preguntas.
     */
    public function edit(Encuesta $encuesta): View
    {
        $encuesta->load('preguntas');
        $tieneRespuestas = $encuesta->respuestas()->exists();

        $preguntasJson = $encuesta->preguntas->map(function ($p) {
            $opcionesTexto = '';
            if (is_array($p->opciones)) {
                $lineas = [];
                foreach ($p->opciones as $opt) {
                    if (is_array($opt) && isset($opt['etiqueta'])) {
                        $lineas[] = $opt['etiqueta'];
                    } elseif (is_string($opt)) {
                        $lineas[] = $opt;
                    }
                }
                $opcionesTexto = implode("\n", $lineas);
            }
            return [
                'texto' => $p->texto_pregunta,
                'tipo' => $p->tipo_pregunta,
                'es_obligatoria' => (bool) $p->es_obligatoria,
                'opciones' => $opcionesTexto,
            ];
        })->values()->toArray();

        return view('admin.encuestas.edit', compact('encuesta', 'tieneRespuestas', 'preguntasJson'));
    }

    /**
     * Actualiza la encuesta y sus preguntas.
     */
    public function update(Request $request, Encuesta $encuesta): RedirectResponse
    {
        $tieneRespuestas = $encuesta->respuestas()->exists();

        $rules = [
            'titulo' => 'required|string|max:150|unique:encuestas,titulo,' . $encuesta->encuesta_id . ',encuesta_id',
            'descripcion' => 'nullable|string|max:1000',
            'objetivo' => 'nullable|string|max:1000',
            'disponible_desde' => 'nullable|date',
            'disponible_hasta' => 'nullable|date|after_or_equal:disponible_desde',
            'estado' => 'required|in:BORRADOR,PUBLICADA,CERRADA',
        ];

        if (!$tieneRespuestas) {
            $rules['tipo'] = 'required|in:GENERAL,POST_CITA';
            $rules['es_anonima'] = 'nullable|boolean';
            $rules['permite_multiples_respuestas'] = 'nullable|boolean';
            $rules['preguntas'] = 'required|array|min:1';
            $rules['preguntas.*.texto'] = 'required|string|max:500';
            $rules['preguntas.*.tipo'] = 'required|in:LIKERT,OPCION_UNICA,OPCION_MULTIPLE,VALORACION,TEXTO_LIBRE';
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $encuesta, $tieneRespuestas) {
            $datosUpdate = [
                'titulo' => trim($request->input('titulo')),
                'descripcion' => trim($request->input('descripcion')),
                'objetivo' => trim($request->input('objetivo')),
                'disponible_desde' => $request->input('disponible_desde'),
                'disponible_hasta' => $request->input('disponible_hasta'),
                'estado' => $request->input('estado'),
            ];

            // Si no tiene respuestas aún, se permite modificar atributos estructurales
            if (!$tieneRespuestas) {
                $datosUpdate['tipo'] = $request->input('tipo');
                $datosUpdate['es_anonima'] = $request->boolean('es_anonima');
                $datosUpdate['permite_multiples_respuestas'] = $request->boolean('permite_multiples_respuestas');
            }

            $encuesta->update($datosUpdate);

            // Si aún no tiene respuestas, permite reestructurar preguntas
            if (!$tieneRespuestas && $request->has('preguntas')) {
                $encuesta->preguntas()->delete();
                foreach ($request->input('preguntas') as $idx => $p) {
                    $opciones = null;
                    $tipo = $p['tipo'];

                    if ($tipo === EncuestaPregunta::TIPO_LIKERT) {
                        $opciones = EncuestaPregunta::opcionesLikertEstandar();
                    } elseif (in_array($tipo, [EncuestaPregunta::TIPO_OPCION_UNICA, EncuestaPregunta::TIPO_OPCION_MULTIPLE])) {
                        $lineas = array_filter(array_map('trim', explode("\n", $p['opciones'] ?? '')));
                        $opciones = [];
                        foreach ($lineas as $i => $linea) {
                            $opciones[] = ['valor' => 'opt_' . ($i + 1), 'etiqueta' => $linea];
                        }
                    } elseif ($tipo === EncuestaPregunta::TIPO_VALORACION) {
                        $opciones = ['min' => 1, 'max' => 10];
                    }

                    $encuesta->preguntas()->create([
                        'orden' => $idx + 1,
                        'texto_pregunta' => trim($p['texto']),
                        'tipo_pregunta' => $tipo,
                        'es_obligatoria' => isset($p['es_obligatoria']) ? (bool) $p['es_obligatoria'] : true,
                        'opciones' => $opciones,
                    ]);
                }
            }
        });

        return redirect()->route('admin.encuestas.index')
            ->with('status', 'Encuesta actualizada correctamente.');
    }

    /**
     * Alternar o asignar estado de encuesta (Borrador / Publicada / Cerrada).
     */
    public function toggleEstado(Request $request, Encuesta $encuesta): RedirectResponse
    {
        if ($request->has('estado')) {
            $request->validate([
                'estado' => 'required|in:BORRADOR,PUBLICADA,CERRADA',
            ]);
            $nuevoEstado = $request->input('estado');
        } else {
            $nuevoEstado = match ($encuesta->estado) {
                Encuesta::ESTADO_BORRADOR => Encuesta::ESTADO_PUBLICADA,
                Encuesta::ESTADO_PUBLICADA => Encuesta::ESTADO_CERRADA,
                Encuesta::ESTADO_CERRADA => Encuesta::ESTADO_PUBLICADA,
                default => Encuesta::ESTADO_BORRADOR,
            };
        }

        $encuesta->update(['estado' => $nuevoEstado]);

        if ($nuevoEstado === Encuesta::ESTADO_PUBLICADA) {
            $alumnos = User::whereHas('rol', fn($q) => $q->where('nombre', 'Alumno'))
                ->where('estado', 'ACTIVO')
                ->take(50)
                ->get();

            foreach ($alumnos as $alumno) {
                try {
                    $alumno->notify(new \App\Notifications\NuevaEncuestaNotification(
                        $encuesta->titulo,
                        $encuesta->encuesta_id,
                        (bool) $encuesta->es_anonima
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return back()->with('status', "El estado de la encuesta '{$encuesta->titulo}' cambió exitosamente a {$nuevoEstado}.");
    }

    /**
     * Vista estadística de resultados (FN.8).
     */
    public function resultados(Encuesta $encuesta): View
    {
        $encuesta->load(['preguntas.detalles', 'respuestas']);
        $totalRespuestas = $encuesta->respuestas()->count();

        $analisisPreguntas = [];

        foreach ($encuesta->preguntas as $pregunta) {
            $detalles = $pregunta->detalles;
            $conteo = $detalles->count();

            $resultado = [
                'pregunta' => $pregunta,
                'total_respuestas' => $conteo,
                'promedio' => null,
                'distribucion' => [],
                'textos' => [],
            ];

            if ($pregunta->tipo_pregunta === EncuestaPregunta::TIPO_LIKERT) {
                $promedio = $conteo > 0 ? round($detalles->avg('valor_numerico'), 2) : 0;
                $resultado['promedio'] = $promedio;

                // Distribución por escala 1 al 5
                $agrupados = $detalles->groupBy('valor_numerico');
                $dist = [];
                foreach (EncuestaPregunta::opcionesLikertEstandar() as $opt) {
                    $val = $opt['valor'];
                    $qty = isset($agrupados[$val]) ? $agrupados[$val]->count() : 0;
                    $pct = $conteo > 0 ? round(($qty / $conteo) * 100, 1) : 0;
                    $dist[] = [
                        'etiqueta' => $opt['etiqueta'],
                        'valor' => $val,
                        'cantidad' => $qty,
                        'porcentaje' => $pct,
                    ];
                }
                $resultado['distribucion'] = $dist;
            } elseif ($pregunta->tipo_pregunta === EncuestaPregunta::TIPO_VALORACION) {
                $promedio = $conteo > 0 ? round($detalles->avg('valor_numerico'), 2) : 0;
                $resultado['promedio'] = $promedio;
            } elseif ($pregunta->tipo_pregunta === EncuestaPregunta::TIPO_OPCION_UNICA) {
                $agrupados = $detalles->groupBy('texto_respuesta');
                $dist = [];
                foreach ($pregunta->opciones ?? [] as $opt) {
                    $label = is_array($opt) ? ($opt['etiqueta'] ?? '') : (string) $opt;
                    $qty = isset($agrupados[$label]) ? $agrupados[$label]->count() : 0;
                    $pct = $conteo > 0 ? round(($qty / $conteo) * 100, 1) : 0;
                    $dist[] = [
                        'etiqueta' => $label,
                        'cantidad' => $qty,
                        'porcentaje' => $pct,
                    ];
                }
                $resultado['distribucion'] = $dist;
            } elseif ($pregunta->tipo_pregunta === EncuestaPregunta::TIPO_OPCION_MULTIPLE) {
                // Opción Múltiple: contabilizar participantes y selecciones
                $totalParticipantes = $detalles->count();
                $resultado['total_participantes'] = $totalParticipantes;

                $conteoOpciones = [];
                $totalSelecciones = 0;

                foreach ($detalles as $det) {
                    $seleccionadas = $det->opciones_seleccionadas ?? [];
                    if (is_string($seleccionadas)) {
                        $seleccionadas = json_decode($seleccionadas, true) ?? [];
                    }
                    if (is_array($seleccionadas)) {
                        foreach ($seleccionadas as $sel) {
                            $selTrim = trim((string) $sel);
                            if ($selTrim !== '') {
                                $conteoOpciones[$selTrim] = ($conteoOpciones[$selTrim] ?? 0) + 1;
                                $totalSelecciones++;
                            }
                        }
                    }
                }

                $resultado['total_selecciones'] = $totalSelecciones;

                $dist = [];
                foreach ($pregunta->opciones ?? [] as $opt) {
                    $label = is_array($opt) ? ($opt['etiqueta'] ?? '') : (string) $opt;
                    $qty = $conteoOpciones[$label] ?? 0;
                    // Porcentaje calculado respecto al total de participantes que contestaron
                    $pct = $totalParticipantes > 0 ? round(($qty / $totalParticipantes) * 100, 1) : 0;
                    $dist[] = [
                        'etiqueta' => $label,
                        'cantidad' => $qty,
                        'porcentaje' => $pct,
                    ];
                }
                $resultado['distribucion'] = $dist;
            } elseif ($pregunta->tipo_pregunta === EncuestaPregunta::TIPO_TEXTO_LIBRE) {
                $resultado['textos'] = $detalles->whereNotNull('texto_respuesta')
                    ->where('texto_respuesta', '!=', '')
                    ->pluck('texto_respuesta')
                    ->take(50)
                    ->toArray();
            }

            $analisisPreguntas[] = $resultado;
        }

        // Cargar respuestas identificadas nominalmente cuando la encuesta no es anónima
        $respuestasIdentificadas = collect();
        if (!$encuesta->es_anonima) {
            $respuestasIdentificadas = $encuesta->respuestas()
                ->with([
                    'usuario.estudiante.programaEducativo',
                    'detalles.pregunta',
                    'cita.profesional',
                ])
                ->orderBy('fecha_respuesta', 'desc')
                ->get();
        }

        return view('admin.encuestas.resultados', compact('encuesta', 'totalRespuestas', 'analisisPreguntas', 'respuestasIdentificadas'));
    }

    /**
     * Eliminación definitiva y autorizada de una encuesta y sus dependencias (preguntas, respuestas y detalles).
     * Si cuenta con respuestas registradas, exige confirmación reforzada mediante la palabra clave 'ELIMINAR'.
     */
    public function destroy(Request $request, Encuesta $encuesta): RedirectResponse
    {
        $respuestasCount = $encuesta->respuestas()->count();
        $preguntasCount = $encuesta->preguntas()->count();
        $titulo = $encuesta->titulo;

        if ($respuestasCount > 0) {
            $confirmacion = strtoupper(trim((string) $request->input('confirmacion_palabra', $request->input('confirmacion_eliminar', ''))));
            if ($confirmacion !== 'ELIMINAR') {
                return back()->with('error', "Para eliminar definitivamente la encuesta '{$titulo}' con sus {$respuestasCount} respuesta(s) histórica(s), debes escribir la palabra de seguridad ELIMINAR.");
            }
        }

        DB::transaction(function () use ($encuesta) {
            // 1. Eliminar detalles de respuestas asociadas a esta encuesta
            $respuestaIds = $encuesta->respuestas()->pluck('respuesta_id');
            if ($respuestaIds->isNotEmpty()) {
                \App\Models\EncuestaRespuestaDetalle::whereIn('respuesta_id', $respuestaIds)->delete();
                $encuesta->respuestas()->delete();
            }

            // 2. Eliminar preguntas
            $encuesta->preguntas()->delete();

            // 3. Eliminar la encuesta
            $encuesta->delete();
        });

        $mensaje = $respuestasCount > 0
            ? "La encuesta '{$titulo}', sus {$preguntasCount} preguntas y sus {$respuestasCount} respuestas asociadas han sido eliminadas definitivamente."
            : "La encuesta '{$titulo}' ha sido eliminada exitosamente.";

        return redirect()->route('admin.encuestas.index')
            ->with('success', $mensaje)
            ->with('status', $mensaje);
    }
}

