<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaremoRegla;
use App\Models\EscalaOpcion;
use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\PreguntaEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Notifications\NuevaEvaluacionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TestAdminController extends Controller
{
    /**
     * Catálogo institucional y administración de instrumentos psicométricos (FN.3 / A1-A9).
     */
    public function index(Request $request): View
    {
        $query = InstrumentoEvaluacion::with(['versionVigente', 'versionActual', 'versiones'])
            ->withCount('intentos');

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('estado')) {
            $hasRetirado = Schema::hasColumn('evaluaciones_instrumentos', 'retirado');
            if ($request->estado === 'activo') {
                $query->where('activo', true);
                if ($hasRetirado) {
                    $query->where('retirado', false);
                }
            } elseif ($request->estado === 'inactivo') {
                $query->where('activo', false);
            } elseif ($request->estado === 'retirado' && $hasRetirado) {
                $query->where('retirado', true);
            } elseif ($request->estado === 'borrador') {
                $query->whereHas('versiones', fn ($q) => $q->where('estado', 'BORRADOR'));
            }
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'LIKE', "%{$term}%")
                  ->orWhere('codigo', 'LIKE', "%{$term}%")
                  ->orWhere('siglas', 'LIKE', "%{$term}%")
                  ->orWhere('descripcion', 'LIKE', "%{$term}%");
            });
        }

        $tests = $query->orderBy('orden')->orderBy('nombre')->paginate(10)->withQueryString();

        $metricas = [
            'total' => InstrumentoEvaluacion::count(),
            'activos' => InstrumentoEvaluacion::where('activo', true)->count(),
            'inactivos' => InstrumentoEvaluacion::where('activo', false)->count(),
            'total_intentos' => Schema::hasTable('evaluaciones_intentos') ? DB::table('evaluaciones_intentos')->count() : 0,
        ];

        $categorias = InstrumentoEvaluacion::distinct()
            ->whereNotNull('categoria')
            ->pluck('categoria')
            ->toArray();

        return view('admin.tests.index', compact('tests', 'metricas', 'categorias'));
    }

    /**
     * Formulario para registrar un nuevo instrumento psicométrico estandarizado con editor de reactivos (A2, A3, A8).
     */
    public function create(): View
    {
        $categoriasSugeridas = [
            'Salud Emocional',
            'Salud Psicosocial',
            'Estrés y Bienestar',
            'Bienestar Psicológico General',
            'Orientación y Bienestar General',
            'Hábitos de Estudio y Autoconocimiento',
        ];

        $tiposPregunta = PreguntaEvaluacion::tiposCatalogo();

        return view('admin.tests.create', compact('categoriasSugeridas', 'tiposPregunta'));
    }

    /**
     * Almacenar un nuevo instrumento, su versión inicial y sus reactivos/escalas/baremos (A2, A3, A5).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:evaluaciones_instrumentos,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'siglas' => ['nullable', 'string', 'max:20'],
            'categoria' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'autor' => ['nullable', 'string', 'max:150'],
            'anio_publicacion' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'dias_espera_reaplicacion' => ['required', 'integer', 'min:0', 'max:365'],
            'activo' => ['nullable', 'boolean'],
            'estado_version' => ['nullable', 'string', 'in:BORRADOR,PUBLICADA'],
            'instrucciones' => ['required', 'string', 'max:2000'],
            'marco_temporal' => ['nullable', 'string', 'max:100'],
            'duracion_estimada_min' => ['nullable', 'integer', 'min:1', 'max:120'],
            'puntos_recompensa' => ['nullable', 'integer', 'min:0', 'max:500'],
            'notificar_alumnos' => ['nullable', 'boolean'],
            'preguntas' => ['nullable', 'array'],
            'escalas' => ['nullable', 'array'],
            'baremos' => ['nullable', 'array'],
        ], [
            'codigo.unique' => 'Ya existe un instrumento psicométrico registrado con esta clave.',
            'codigo.alpha_dash' => 'El código únicamente puede contener letras, números, guiones y guiones bajos.',
            'instrucciones.required' => 'Las instrucciones metodológicas para el estudiante son obligatorias.',
            'dias_espera_reaplicacion.min' => 'El intervalo de repetición debe ser 0 (inmediato) o un número entero positivo de días.',
        ]);

        $usaEditorCompleto = $request->has('modo_editor')
            || $request->has('estado_version')
            || $request->has('accion_publicacion')
            || $request->has('preguntas');
        $preguntasLimpias = $this->normalizarPreguntasInput($request->input('preguntas', []));
        $escalasLimpias = $this->normalizarEscalasInput($request->input('escalas', []));
        $baremosLimpios = $this->normalizarBaremosInput($request->input('baremos', []));

        if ($usaEditorCompleto) {
            $estadoVersion = $request->input('estado_version');
            if (! $estadoVersion && $request->filled('accion_publicacion')) {
                $estadoVersion = strtolower((string) $request->input('accion_publicacion')) === 'publicar'
                    ? 'PUBLICADA'
                    : 'BORRADOR';
            }
            if (! $estadoVersion) {
                $estadoVersion = $request->boolean('activo', false) ? 'PUBLICADA' : 'BORRADOR';
            }

            // A3: Impedir publicar tests sin preguntas válidas o con configuraciones inconsistentes
            if ($estadoVersion === 'PUBLICADA') {
                $erroresPublicacion = $this->validarConfiguracionParaPublicar($preguntasLimpias, $escalasLimpias, $baremosLimpios);
                if (! empty($erroresPublicacion)) {
                    return back()->withInput()->withErrors($erroresPublicacion);
                }
            }

            $activo = ($estadoVersion === 'PUBLICADA')
                ? ($request->has('activo') ? (bool) $request->activo : true)
                : false;
        } else {
            // Retrocompatibilidad con pruebas previas que envían solo metadatos básicos
            $activo = $request->has('activo') ? (bool) $request->activo : true;
            $estadoVersion = 'PUBLICADA';
        }

        $test = DB::transaction(function () use ($validated, $activo, $estadoVersion, $preguntasLimpias, $escalasLimpias, $baremosLimpios) {
            $instrumentoData = [
                'codigo' => strtoupper(trim($validated['codigo'])),
                'nombre' => trim($validated['nombre']),
                'siglas' => ! empty($validated['siglas']) ? strtoupper(trim($validated['siglas'])) : null,
                'categoria' => trim($validated['categoria']),
                'descripcion' => trim($validated['descripcion']),
                'autor' => ! empty($validated['autor']) ? trim($validated['autor']) : null,
                'anio_publicacion' => $validated['anio_publicacion'] ?? null,
                'dias_espera_reaplicacion' => (int) $validated['dias_espera_reaplicacion'],
                'activo' => $activo,
                'orden' => (InstrumentoEvaluacion::max('orden') ?? 0) + 1,
            ];

            if (Schema::hasColumn('evaluaciones_instrumentos', 'retirado')) {
                $instrumentoData['retirado'] = false;
            }

            $instrumento = InstrumentoEvaluacion::create($instrumentoData);

            // Crear primero en estado BORRADOR para poblar preguntas, escalas y baremos limpiamente
            $version = VersionInstrumento::create([
                'instrumento_id' => $instrumento->instrumento_id,
                'version' => '1.0',
                'nombre_version' => 'Versión Inicial Estandarizada',
                'descripcion_cambios' => 'Alta inicial del instrumento en la plataforma institucional.',
                'instrucciones' => trim($validated['instrucciones']),
                'marco_temporal' => ! empty($validated['marco_temporal']) ? trim($validated['marco_temporal']) : 'Últimas 2 semanas',
                'duracion_estimada_min' => $validated['duracion_estimada_min'] ?? 10,
                'puntos_recompensa' => $validated['puntos_recompensa'] ?? 20,
                'estado' => 'BORRADOR',
                'es_vigente' => true,
                'vigente_desde' => now(),
            ]);

            $this->persistirContenidoVersion($version, $preguntasLimpias, $escalasLimpias, $baremosLimpios);

            if ($estadoVersion === 'PUBLICADA') {
                $version->update([
                    'estado' => 'PUBLICADA',
                    'es_vigente' => true,
                    'vigente_desde' => now(),
                ]);
            }

            return $instrumento;
        });

        // B5: Si el instrumento se publica activo, notificar a los estudiantes activos
        if ($test->activo && $estadoVersion === 'PUBLICADA' && $request->boolean('notificar_alumnos', true)) {
            $this->notificarNuevaEvaluacionAlumnos($test);
        }

        $msgEstado = $estadoVersion === 'PUBLICADA' ? 'con su versión inicial publicada' : 'en modo borrador editable';

        return redirect()
            ->route('admin.tests.show', $test->instrumento_id)
            ->with('status', "El instrumento '{$test->nombre}' ({$test->codigo}) ha sido registrado exitosamente {$msgEstado}.");
    }

    /**
     * Ficha técnica detallada de un instrumento psicométrico (A2, A4).
     */
    public function show(Request $request, int $id): View
    {
        $test = InstrumentoEvaluacion::with([
            'versiones' => fn ($q) => $q->withCount(['preguntas', 'intentos'])->orderByDesc('version_id'),
            'versiones.preguntas' => fn ($q) => $q->orderBy('orden')->orderBy('numero_reactivo'),
            'versiones.baremos' => fn ($q) => $q->orderBy('orden'),
            'versiones.escalas' => fn ($q) => $q->orderBy('orden'),
            'versionVigente.preguntas' => fn ($q) => $q->orderBy('orden')->orderBy('numero_reactivo'),
            'versionVigente.baremos' => fn ($q) => $q->orderBy('orden'),
            'versionVigente.escalas' => fn ($q) => $q->orderBy('orden'),
        ])->findOrFail($id);

        $versionSeleccionadaId = $request->query('version_id');
        if ($versionSeleccionadaId) {
            $versionActual = $test->versiones->firstWhere('version_id', (int) $versionSeleccionadaId);
        }
        $versionActual = $versionActual ?? $test->versionVigente ?? $test->versiones->first();

        $totalRespuestas = Schema::hasTable('evaluaciones_intentos')
            ? DB::table('evaluaciones_intentos')
                ->when($versionActual, fn ($q) => $q->where('version_id', $versionActual->version_id))
                ->where('estado', 'FINALIZADO')
                ->count()
            : 0;

        $totalIntentosInstrumento = Schema::hasTable('evaluaciones_intentos')
            ? $test->intentos()->count()
            : 0;

        $totalIntentosVersion = $versionActual && Schema::hasTable('evaluaciones_intentos')
            ? DB::table('evaluaciones_intentos')->where('version_id', $versionActual->version_id)->count()
            : 0;

        return view('admin.tests.show', compact(
            'test',
            'versionActual',
            'totalRespuestas',
            'totalIntentosInstrumento',
            'totalIntentosVersion'
        ));
    }

    /**
     * Formulario de edición de metadatos, reactivos, escalas y baremos del test (A2, A3, A4, A5).
     */
    public function edit(Request $request, int $id): View
    {
        $test = InstrumentoEvaluacion::with([
            'versiones' => fn ($q) => $q->withCount('intentos')->orderByDesc('version_id'),
            'versiones.preguntas' => fn ($q) => $q->orderBy('orden')->orderBy('numero_reactivo'),
            'versiones.escalas' => fn ($q) => $q->orderBy('orden'),
            'versiones.baremos' => fn ($q) => $q->orderBy('orden'),
            'versionVigente',
        ])->findOrFail($id);

        // Priorizar versión en borrador si existe para permitir editar la nueva versión creada, o la vigente
        $versionIdReq = $request->query('version_id');
        $versionEditable = null;
        if ($versionIdReq) {
            $versionEditable = $test->versiones->firstWhere('version_id', (int) $versionIdReq);
        }
        if (! $versionEditable) {
            $versionEditable = $test->versiones->firstWhere('estado', 'BORRADOR')
                ?? $test->versionVigente
                ?? $test->versiones->first();
        }

        $intentosVersionCount = $versionEditable
            ? DB::table('evaluaciones_intentos')->where('version_id', $versionEditable->version_id)->count()
            : 0;

        $totalIntentosInstrumento = $test->intentos()->count();

        // A4: Las preguntas y opciones pueden editarse directamente solo si la versión no tiene respuestas registradas
        $puedeEditarReactivos = ($intentosVersionCount === 0);

        $categoriasSugeridas = [
            'Salud Emocional',
            'Salud Psicosocial',
            'Estrés y Bienestar',
            'Bienestar Psicológico General',
            'Orientación y Bienestar General',
            'Hábitos de Estudio y Autoconocimiento',
        ];

        $tiposPregunta = PreguntaEvaluacion::tiposCatalogo();

        return view('admin.tests.edit', compact(
            'test',
            'versionEditable',
            'intentosVersionCount',
            'totalIntentosInstrumento',
            'puedeEditarReactivos',
            'categoriasSugeridas',
            'tiposPregunta'
        ));
    }

    /**
     * Actualizar parámetros, metadatos y (cuando la versión tiene 0 intentos) reactivos, escalas y baremos (A2, A3, A4, A5).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $test = InstrumentoEvaluacion::with(['versiones', 'versionVigente'])->findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'siglas' => ['nullable', 'string', 'max:20'],
            'categoria' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'autor' => ['nullable', 'string', 'max:150'],
            'anio_publicacion' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'dias_espera_reaplicacion' => ['required', 'integer', 'min:0', 'max:365'],
            'activo' => ['nullable', 'boolean'],
            'version_id' => ['nullable', 'integer'],
            'estado_version' => ['nullable', 'string', 'in:BORRADOR,PUBLICADA'],
            'instrucciones' => ['nullable', 'string', 'max:2000'],
            'marco_temporal' => ['nullable', 'string', 'max:100'],
            'duracion_estimada_min' => ['nullable', 'integer', 'min:1', 'max:120'],
            'puntos_recompensa' => ['nullable', 'integer', 'min:0', 'max:500'],
            'preguntas' => ['nullable', 'array'],
            'escalas' => ['nullable', 'array'],
            'baremos' => ['nullable', 'array'],
        ]);

        $versionTarget = null;
        $requestedVersionId = $validated['version_id'] ?? $request->input('version_id_edicion');
        if (! empty($requestedVersionId)) {
            $versionTarget = $test->versiones->firstWhere('version_id', (int) $requestedVersionId);
        }
        $versionTarget = $versionTarget
            ?? $test->versiones->firstWhere('estado', 'BORRADOR')
            ?? $test->versionVigente
            ?? $test->versiones->first();

        $intentosVersionCount = $versionTarget
            ? DB::table('evaluaciones_intentos')->where('version_id', $versionTarget->version_id)->count()
            : 0;

        $enviaReactivos = $request->has('preguntas') || $request->has('escalas') || $request->has('baremos');

        // A4: Protección estricta cuando la versión ya tiene respuestas registradas
        if ($enviaReactivos && $intentosVersionCount > 0) {
            return back()->withInput()->withErrors([
                'version_protegida' => "La versión v{$versionTarget->version} ya cuenta con {$intentosVersionCount} intento(s) registrado(s) y no permite modificar ni eliminar directamente sus reactivos, escalas o baremos históricos. Utiliza la opción 'Crear nueva versión' para editar sin alterar el historial.",
            ]);
        }

        $preguntasLimpias = $enviaReactivos ? $this->normalizarPreguntasInput($request->input('preguntas', [])) : [];
        $escalasLimpias = $enviaReactivos ? $this->normalizarEscalasInput($request->input('escalas', [])) : [];
        $baremosLimpios = $enviaReactivos ? $this->normalizarBaremosInput($request->input('baremos', [])) : [];

        $estadoVersionDeseado = $request->input('estado_version');
        if (! $estadoVersionDeseado && $request->filled('accion_version')) {
            $estadoVersionDeseado = strtolower((string) $request->input('accion_version')) === 'publicar'
                ? 'PUBLICADA'
                : 'BORRADOR';
        }
        $estadoVersionDeseado = $estadoVersionDeseado ?: ($versionTarget?->estado ?? 'PUBLICADA');

        // Si se solicita publicar la versión mediante el editor, validar que tenga preguntas válidas
        if ($versionTarget && $estadoVersionDeseado === 'PUBLICADA' && ($enviaReactivos || $versionTarget->estado === 'BORRADOR')) {
            $preguntasAValidar = $enviaReactivos
                ? $preguntasLimpias
                : $versionTarget->preguntas()->where('activo', true)->get()->toArray();
            $escalasAValidar = $enviaReactivos
                ? $escalasLimpias
                : $versionTarget->escalas()->get()->toArray();
            $baremosAValidar = $enviaReactivos
                ? $baremosLimpios
                : $versionTarget->baremos()->get()->toArray();

            $erroresPublicacion = $this->validarConfiguracionParaPublicar($preguntasAValidar, $escalasAValidar, $baremosAValidar);
            if (! empty($erroresPublicacion)) {
                return back()->withInput()->withErrors($erroresPublicacion);
            }
        }

        $estabaActivo = (bool) $test->activo;
        $nuevoActivo = $request->has('activo') ? (bool) $request->activo : false;
        if ($estadoVersionDeseado === 'BORRADOR' && $test->versiones()->where('estado', 'PUBLICADA')->where('version_id', '!=', $versionTarget?->version_id)->count() === 0) {
            // Si no hay ninguna versión publicada disponible, el instrumento no puede figurar como activo para alumnos
            if ($enviaReactivos) {
                $nuevoActivo = false;
            }
        }

        DB::transaction(function () use (
            $request,
            $test,
            $validated,
            $nuevoActivo,
            $versionTarget,
            $intentosVersionCount,
            $enviaReactivos,
            $estadoVersionDeseado,
            $preguntasLimpias,
            $escalasLimpias,
            $baremosLimpios
        ) {
            $updateInstrumento = [
                'nombre' => trim($validated['nombre']),
                'siglas' => ! empty($validated['siglas']) ? strtoupper(trim($validated['siglas'])) : null,
                'categoria' => trim($validated['categoria']),
                'descripcion' => trim($validated['descripcion']),
                'autor' => ! empty($validated['autor']) ? trim($validated['autor']) : null,
                'anio_publicacion' => $validated['anio_publicacion'] ?? null,
                'dias_espera_reaplicacion' => (int) $validated['dias_espera_reaplicacion'],
                'activo' => $nuevoActivo,
            ];

            if ($nuevoActivo && Schema::hasColumn('evaluaciones_instrumentos', 'retirado')) {
                $updateInstrumento['retirado'] = false;
            }

            $test->update($updateInstrumento);

            // A4: Si la versión tiene 0 intentos registrados, permitir editar sus parámetros y reactivos
            if ($versionTarget && $intentosVersionCount === 0 && ($enviaReactivos || $request->filled('instrucciones') || $request->filled('estado_version'))) {
                $estadoOriginal = $versionTarget->estado;

                // Transicionar temporalmente a BORRADOR para respetar los hooks de inmutabilidad de modelos cuando tiene 0 intentos
                if ($estadoOriginal === 'PUBLICADA') {
                    $versionTarget->update(['estado' => 'BORRADOR']);
                }

                $versionTarget->update([
                    'instrucciones' => ! empty($validated['instrucciones']) ? trim($validated['instrucciones']) : $versionTarget->instrucciones,
                    'marco_temporal' => ! empty($validated['marco_temporal']) ? trim($validated['marco_temporal']) : $versionTarget->marco_temporal,
                    'duracion_estimada_min' => $validated['duracion_estimada_min'] ?? $versionTarget->duracion_estimada_min,
                    'puntos_recompensa' => $validated['puntos_recompensa'] ?? $versionTarget->puntos_recompensa,
                ]);

                if ($enviaReactivos) {
                    $versionTarget->preguntas()->delete();
                    $versionTarget->escalas()->delete();
                    $versionTarget->baremos()->delete();
                    $this->persistirContenidoVersion($versionTarget, $preguntasLimpias, $escalasLimpias, $baremosLimpios);
                }

                if ($estadoVersionDeseado === 'PUBLICADA') {
                    // Retirar como OBSOLETA cualquier otra versión publicada previa del mismo instrumento
                    VersionInstrumento::where('instrumento_id', $test->instrumento_id)
                        ->where('version_id', '!=', $versionTarget->version_id)
                        ->where('estado', 'PUBLICADA')
                        ->update([
                            'estado' => 'OBSOLETA',
                            'es_vigente' => false,
                            'vigente_hasta' => now(),
                        ]);

                    $versionTarget->update([
                        'estado' => 'PUBLICADA',
                        'es_vigente' => true,
                        'vigente_desde' => $versionTarget->vigente_desde ?? now(),
                        'vigente_hasta' => null,
                    ]);
                } else {
                    $versionTarget->update([
                        'estado' => 'BORRADOR',
                    ]);
                }
            }
        });

        if (! $estabaActivo && $nuevoActivo && $test->fresh()->versionVigente) {
            $this->notificarNuevaEvaluacionAlumnos($test->fresh());
        }

        return redirect()
            ->route('admin.tests.show', $test->instrumento_id)
            ->with('status', "El test '{$test->nombre}' ha sido actualizado correctamente.");
    }

    /**
     * Alternar estado activo/pausado del test psicométrico (A2).
     * Impide publicar/activar si no cuenta con al menos una versión publicada con preguntas válidas (A3).
     */
    public function toggleEstado(Request $request, int $id): RedirectResponse
    {
        $test = InstrumentoEvaluacion::with(['versionVigente.preguntas', 'versiones.preguntas'])->findOrFail($id);

        if (! $test->activo) {
            // Se intenta activar: verificar que exista versión publicada o publicable
            $versionVigente = $test->versionVigente ?? $test->versiones->first();
            if ($versionVigente && $versionVigente->estado === 'BORRADOR' && $versionVigente->preguntas->count() > 0) {
                $versionVigente->update([
                    'estado' => 'PUBLICADA',
                    'es_vigente' => true,
                    'vigente_desde' => now(),
                ]);
            } elseif (! $test->versionVigente) {
                return back()->with('error', "No se puede activar el instrumento '{$test->codigo}' porque no cuenta con una versión publicada válida.");
            }
        }

        $test->activo = ! $test->activo;
        if ($test->activo && Schema::hasColumn('evaluaciones_instrumentos', 'retirado')) {
            $test->retirado = false;
        }
        $test->save();

        if ($test->activo) {
            $this->notificarNuevaEvaluacionAlumnos($test);
        }

        $estadoTxt = $test->activo ? 'activado' : 'desactivado (pausado)';

        return back()->with('status', "El instrumento '{$test->codigo}' ha sido {$estadoTxt} correctamente.");
    }

    /**
     * Retirar un instrumento de circulación conservando intacto su historial clínico y estadístico (A2).
     */
    public function retirar(Request $request, int $id): RedirectResponse
    {
        $test = InstrumentoEvaluacion::findOrFail($id);
        $test->activo = false;
        if (Schema::hasColumn('evaluaciones_instrumentos', 'retirado')) {
            $test->retirado = true;
        }
        $test->save();

        return back()->with('status', "El instrumento '{$test->nombre}' ({$test->codigo}) ha sido retirado de circulación. Sus versiones e intentos históricos permanecen resguardados.");
    }

    /**
     * Eliminar definitivamente un test únicamente cuando no tiene intentos ni respuestas registradas (A2, A4).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $test = InstrumentoEvaluacion::with('versiones')->findOrFail($id);
        $intentosCount = Schema::hasTable('evaluaciones_intentos') ? $test->intentos()->count() : 0;

        $versionIds = $test->versiones->pluck('version_id')->toArray();
        $respuestasCount = 0;
        if (! empty($versionIds) && Schema::hasTable('evaluaciones_respuestas')) {
            $preguntaIds = PreguntaEvaluacion::whereIn('version_id', $versionIds)->pluck('pregunta_id')->toArray();
            if (! empty($preguntaIds)) {
                $respuestasCount = EvaluacionRespuesta::whereIn('pregunta_id', $preguntaIds)->count();
            }
        }

        if ($intentosCount > 0 || $respuestasCount > 0) {
            $confirmacion = strtoupper(trim((string) $request->input('confirmacion_palabra', $request->input('confirmacion_eliminar', ''))));
            if ($confirmacion !== 'ELIMINAR') {
                return back()->with('error', "Para autorizar la eliminación física del instrumento '{$test->nombre}' con {$intentosCount} intento(s) y {$respuestasCount} respuesta(s) históricas, debes escribir la palabra de seguridad ELIMINAR.");
            }
        }

        $nombre = $test->nombre;
        $codigo = $test->codigo;

        DB::transaction(function () use ($test, $versionIds) {
            // 1. Eliminar respuestas de intentos pertenecientes a este instrumento
            if (!empty($versionIds) && Schema::hasTable('evaluaciones_respuestas')) {
                $preguntaIds = PreguntaEvaluacion::whereIn('version_id', $versionIds)->pluck('pregunta_id')->toArray();
                if (!empty($preguntaIds)) {
                    EvaluacionRespuesta::whereIn('pregunta_id', $preguntaIds)->delete();
                }
            }

            // 2. Eliminar intentos de evaluación del instrumento
            if (Schema::hasTable('evaluaciones_intentos')) {
                $test->intentos()->delete();
            }

            // 3. Eliminar consentimientos, baremos, escalas y preguntas de cada versión
            foreach ($test->versiones as $version) {
                if ($version->estado === 'PUBLICADA') {
                    $version->update(['estado' => 'BORRADOR', 'es_vigente' => false]);
                }
                if (Schema::hasTable('evaluaciones_consentimientos')) {
                    EvaluacionConsentimiento::where('version_id', $version->version_id)->delete();
                }
                $version->baremos()->delete();
                $version->escalas()->delete();
                $version->preguntas()->delete();
                $version->delete();
            }

            // 4. Eliminar el instrumento
            $test->delete();
        });

        $mensaje = ($intentosCount > 0 || $respuestasCount > 0)
            ? "El instrumento '{$nombre}' ({$codigo}), sus {$intentosCount} intentos y {$respuestasCount} respuestas asociadas han sido eliminados definitivamente."
            : "El instrumento '{$nombre}' ({$codigo}) ha sido eliminado exitosamente.";

        return redirect()
            ->route('admin.tests.index')
            ->with('success', $mensaje)
            ->with('status', $mensaje);
    }

    /**
     * Duplicar un instrumento existente (metadatos, preguntas, escalas y baremos) en modo borrador sin copiar respuestas (A2).
     */
    public function duplicar(Request $request, int $id): RedirectResponse
    {
        $origen = InstrumentoEvaluacion::with([
            'versionVigente.preguntas',
            'versionVigente.escalas',
            'versionVigente.baremos',
            'versiones.preguntas',
            'versiones.escalas',
            'versiones.baremos',
        ])->findOrFail($id);

        $versionOrigen = $origen->versionVigente ?? $origen->versiones->sortByDesc('version_id')->first();

        // Generar código único de hasta 20 caracteres
        $baseCode = substr(preg_replace('/[^A-Z0-9_-]/', '', strtoupper($origen->codigo)), 0, 13);
        $sufijo = 1;
        do {
            $nuevoCodigo = $sufijo === 1 ? "{$baseCode}-COPIA" : "{$baseCode}-C{$sufijo}";
            $sufijo++;
        } while (InstrumentoEvaluacion::where('codigo', $nuevoCodigo)->exists());

        $copia = DB::transaction(function () use ($origen, $versionOrigen, $nuevoCodigo) {
            $datosNuevo = [
                'codigo' => $nuevoCodigo,
                'nombre' => substr($origen->nombre . ' (Copia)', 0, 150),
                'siglas' => $origen->siglas,
                'categoria' => $origen->categoria,
                'descripcion' => $origen->descripcion,
                'autor' => $origen->autor,
                'anio_publicacion' => $origen->anio_publicacion,
                'dias_espera_reaplicacion' => $origen->dias_espera_reaplicacion,
                'activo' => false,
                'orden' => (InstrumentoEvaluacion::max('orden') ?? 0) + 1,
            ];
            if (Schema::hasColumn('evaluaciones_instrumentos', 'retirado')) {
                $datosNuevo['retirado'] = false;
            }

            $nuevoInst = InstrumentoEvaluacion::create($datosNuevo);

            $nuevaVersion = VersionInstrumento::create([
                'instrumento_id' => $nuevoInst->instrumento_id,
                'version' => '1.0',
                'nombre_version' => 'Copia en Borrador de ' . ($versionOrigen?->nombre_version ?? $origen->codigo),
                'descripcion_cambios' => "Duplicado a partir de {$origen->codigo} (v" . ($versionOrigen?->version ?? '1.0') . ") sin respuestas históricas.",
                'instrucciones' => $versionOrigen?->instrucciones ?? 'Responde con sinceridad cada uno de los reactivos.',
                'marco_temporal' => $versionOrigen?->marco_temporal ?? 'Últimas 2 semanas',
                'duracion_estimada_min' => $versionOrigen?->duracion_estimada_min ?? 10,
                'puntos_recompensa' => $versionOrigen?->puntos_recompensa ?? 20,
                'estado' => 'BORRADOR',
                'es_vigente' => true,
                'vigente_desde' => now(),
            ]);

            if ($versionOrigen) {
                $this->clonarContenidoEntreVersiones($versionOrigen, $nuevaVersion);
            }

            return $nuevoInst;
        });

        return redirect()
            ->route('admin.tests.edit', $copia->instrumento_id)
            ->with('status', "Se ha creado una copia en borrador ('{$copia->codigo}') sin arrastrar respuestas previas. Puedes editar libremente sus reactivos antes de publicar.");
    }

    /**
     * Crear una nueva versión editable del instrumento clonando sus reactivos, escalas y baremos actuales,
     * protegiendo las respuestas históricas registradas en la versión anterior (A4).
     */
    public function nuevaVersion(Request $request, int $id): RedirectResponse
    {
        $test = InstrumentoEvaluacion::with([
            'versionVigente.preguntas',
            'versionVigente.escalas',
            'versionVigente.baremos',
            'versiones.preguntas',
            'versiones.escalas',
            'versiones.baremos',
        ])->findOrFail($id);

        // Si ya existe una versión en BORRADOR sin intentos, redirigir directamente a editarla
        $borradorExistente = $test->versiones->firstWhere('estado', 'BORRADOR');
        if ($borradorExistente && DB::table('evaluaciones_intentos')->where('version_id', $borradorExistente->version_id)->count() === 0) {
            return redirect()
                ->route('admin.tests.edit', ['test' => $test->instrumento_id, 'version_id' => $borradorExistente->version_id])
                ->with('status', "Ya cuentas con la versión en borrador v{$borradorExistente->version} lista para editar.");
        }

        $versionBase = $test->versionVigente ?? $test->versiones->sortByDesc('version_id')->first();
        $numeroNuevaVersion = $this->calcularSiguienteCodigoVersion($test);

        $nuevaVersion = DB::transaction(function () use ($test, $versionBase, $numeroNuevaVersion, $request) {
            $creada = VersionInstrumento::create([
                'instrumento_id' => $test->instrumento_id,
                'version' => $numeroNuevaVersion,
                'nombre_version' => $request->input('nombre_version') ?: "Versión {$numeroNuevaVersion} Actualizada",
                'descripcion_cambios' => $request->input('descripcion_cambios') ?: "Nueva versión generada a partir de v" . ($versionBase?->version ?? '1.0') . " para preservar historial previo.",
                'instrucciones' => $versionBase?->instrucciones ?? 'Responde con sinceridad cada uno de los reactivos.',
                'marco_temporal' => $versionBase?->marco_temporal ?? 'Últimas 2 semanas',
                'duracion_estimada_min' => $versionBase?->duracion_estimada_min ?? 10,
                'puntos_recompensa' => $versionBase?->puntos_recompensa ?? 20,
                'estado' => 'BORRADOR',
                'es_vigente' => false,
                'vigente_desde' => null,
            ]);

            if ($versionBase) {
                $this->clonarContenidoEntreVersiones($versionBase, $creada);
            }

            return $creada;
        });

        return redirect()
            ->route('admin.tests.edit', ['test' => $test->instrumento_id, 'version_id' => $nuevaVersion->version_id])
            ->with('status', "Se ha generado la nueva versión v{$nuevaVersion->version} en modo BORRADOR. Puedes modificar sus reactivos, escalas y baremos sin afectar los intentos históricos de la versión anterior.");
    }

    /**
     * Publica una versión en borrador, retirando automáticamente la versión anterior a estado OBSOLETA (A3, A4).
     */
    public function publicarVersion(Request $request, int $id): RedirectResponse
    {
        $test = InstrumentoEvaluacion::with(['versiones.preguntas', 'versiones.escalas', 'versiones.baremos'])->findOrFail($id);

        $versionId = $request->input('version_id');
        $version = $versionId
            ? $test->versiones->firstWhere('version_id', (int) $versionId)
            : $test->versiones->firstWhere('estado', 'BORRADOR');

        if (! $version) {
            return back()->with('error', 'No se encontró una versión en borrador para publicar.');
        }

        $preguntasArr = $version->preguntas->where('activo', true)->values()->toArray();
        $escalasArr = $version->escalas->values()->toArray();
        $baremosArr = $version->baremos->values()->toArray();

        $errores = $this->validarConfiguracionParaPublicar($preguntasArr, $escalasArr, $baremosArr);
        if (! empty($errores)) {
            return back()->withErrors($errores);
        }

        DB::transaction(function () use ($test, $version) {
            VersionInstrumento::where('instrumento_id', $test->instrumento_id)
                ->where('version_id', '!=', $version->version_id)
                ->where('estado', 'PUBLICADA')
                ->update([
                    'estado' => 'OBSOLETA',
                    'es_vigente' => false,
                    'vigente_hasta' => now(),
                ]);

            $version->update([
                'estado' => 'PUBLICADA',
                'es_vigente' => true,
                'vigente_desde' => now(),
                'vigente_hasta' => null,
            ]);

            $updateInst = ['activo' => true];
            if (Schema::hasColumn('evaluaciones_instrumentos', 'retirado')) {
                $updateInst['retirado'] = false;
            }
            $test->update($updateInst);
        });

        return redirect()
            ->route('admin.tests.show', $test->instrumento_id)
            ->with('status', "La versión v{$version->version} ha sido publicada como vigente y la versión previa fue retirada (OBSOLETA) conservando intacto su historial.");
    }

    /**
     * Panel de Resultados Generales, Estadísticas por Pregunta y Listado de Intentos (A6, A7).
     */
    public function resultados(Request $request, int $id): View
    {
        $test = InstrumentoEvaluacion::with([
            'versiones' => fn ($q) => $q->withCount('intentos')->orderByDesc('version_id'),
            'versionVigente',
        ])->findOrFail($id);

        // Seleccionar versión a analizar (sin mezclar versiones incompatibles)
        $versionIdReq = $request->query('version_id');
        $versionSeleccionada = null;
        if ($versionIdReq) {
            $versionSeleccionada = $test->versiones->firstWhere('version_id', (int) $versionIdReq);
        }
        if (! $versionSeleccionada) {
            // Priorizar la versión vigente si tiene intentos, o la versión con más intentos, o la vigente
            if ($test->versionVigente && ($test->versiones->firstWhere('version_id', $test->versionVigente->version_id)?->intentos_count ?? 0) > 0) {
                $versionSeleccionada = $test->versionVigente;
            } else {
                $versionConIntentos = $test->versiones->first(fn ($v) => ($v->intentos_count ?? 0) > 0);
                $versionSeleccionada = $versionConIntentos ?? $test->versionVigente ?? $test->versiones->first();
            }
        }

        if ($versionSeleccionada) {
            $versionSeleccionada->load([
                'preguntas' => fn ($q) => $q->where('activo', true)->orderBy('orden')->orderBy('numero_reactivo'),
                'escalas' => fn ($q) => $q->orderBy('orden'),
                'baremos' => fn ($q) => $q->orderBy('orden'),
            ]);
        }

        // Filtros opcionales
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');
        $programaId = $request->query('programa_id');
        $cuatrimestre = $request->query('cuatrimestre');
        $grupoId = $request->query('grupo_id');
        $qAbierta = trim((string) $request->query('q_abierta', ''));

        $intentosBaseQuery = EvaluacionIntento::query()
            ->with(['usuario.estudiante.programa', 'respuestas.escala', 'respuestas.pregunta'])
            ->when($versionSeleccionada, fn ($q) => $q->where('version_id', $versionSeleccionada->version_id));

        if ($fechaInicio) {
            $intentosBaseQuery->whereDate('created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $intentosBaseQuery->whereDate('created_at', '<=', $fechaFin);
        }
        if ($programaId) {
            $intentosBaseQuery->where(function ($q) use ($programaId) {
                $q->whereHas('estudiante', fn ($sub) => $sub->where('programa_id', (int) $programaId))
                  ->orWhereHas('usuario.estudiante', fn ($sub) => $sub->where('programa_id', (int) $programaId));
            });
        }
        if ($cuatrimestre) {
            $intentosBaseQuery->where(function ($q) use ($cuatrimestre) {
                $q->whereHas('estudiante', fn ($sub) => $sub->where('cuatrimestre', (int) $cuatrimestre))
                  ->orWhereHas('usuario.estudiante', fn ($sub) => $sub->where('cuatrimestre', (int) $cuatrimestre));
                if (Schema::hasTable('estudiante_inscripciones')) {
                    $q->orWhereHas('usuario.inscripcionesEstudiante.grupo', fn ($sub) => $sub->where('cuatrimestre', (int) $cuatrimestre));
                }
            });
        }
        if ($grupoId) {
            $grupoModel = Schema::hasTable('grupos') ? Grupo::find((int) $grupoId) : null;
            $intentosBaseQuery->where(function ($q) use ($grupoId, $grupoModel) {
                if (Schema::hasTable('estudiante_inscripciones')) {
                    $q->whereHas('usuario.inscripcionesEstudiante', fn ($sub) => $sub->where('grupo_id', (int) $grupoId));
                }
                if ($grupoModel) {
                    $q->orWhereHas('estudiante', fn ($sub) => $sub
                        ->where('programa_id', $grupoModel->programa_id)
                        ->where('cuatrimestre', $grupoModel->cuatrimestre)
                        ->where('grupo', $grupoModel->nombre))
                      ->orWhereHas('usuario.estudiante', fn ($sub) => $sub
                        ->where('programa_id', $grupoModel->programa_id)
                        ->where('cuatrimestre', $grupoModel->cuatrimestre)
                        ->where('grupo', $grupoModel->nombre));
                }
            });
        }

        $todosIntentosFiltrados = (clone $intentosBaseQuery)->orderByDesc('created_at')->get();
        $intentosFinalizados = $todosIntentosFiltrados->where('estado', 'FINALIZADO')->values();
        $intentosEnProgresoCount = $todosIntentosFiltrados->where('estado', 'EN_PROGRESO')->count();

        // Numeración cronológica de intentos por alumno dentro del instrumento
        $mapaNumeroIntento = [];
        $todosOrdenadosAsc = EvaluacionIntento::whereIn('version_id', $test->versiones->pluck('version_id'))
            ->orderBy('fecha_inicio')
            ->orderBy('intento_id')
            ->get(['intento_id', 'usuario_id']);
        $conteoPorAlumno = [];
        foreach ($todosOrdenadosAsc as $itemAsc) {
            $uid = $itemAsc->usuario_id;
            $conteoPorAlumno[$uid] = ($conteoPorAlumno[$uid] ?? 0) + 1;
            $mapaNumeroIntento[$itemAsc->intento_id] = $conteoPorAlumno[$uid];
        }

        // KPIs Generales (A6)
        $alumnosUnicos = $intentosFinalizados->pluck('usuario_id')->unique()->count();
        $totalIntentosFinalizados = $intentosFinalizados->count();
        $totalRespuestasRegistradas = $intentosFinalizados->sum(fn ($i) => $i->respuestas->count());
        $promedioPuntaje = $totalIntentosFinalizados > 0
            ? round((float) $intentosFinalizados->avg('puntuacion_total'), 1)
            : 0.0;
        $minPuntaje = $totalIntentosFinalizados > 0 ? (int) $intentosFinalizados->min('puntuacion_total') : 0;
        $maxPuntaje = $totalIntentosFinalizados > 0 ? (int) $intentosFinalizados->max('puntuacion_total') : 0;
        $ultimaRespuestaFecha = $intentosFinalizados->max('fecha_finalizacion') ?? $todosIntentosFiltrados->max('created_at');
        $muestraPequena = ($alumnosUnicos > 0 && $alumnosUnicos < 3);

        // Distribución por nivel interpretativo / baremo
        $distribucionNiveles = [];
        if ($versionSeleccionada && $versionSeleccionada->baremos->isNotEmpty()) {
            foreach ($versionSeleccionada->baremos as $baremo) {
                $conteoNivel = $intentosFinalizados->filter(function ($intento) use ($baremo) {
                    if ($intento->nivel_resultado && strcasecmp($intento->nivel_resultado, $baremo->nivel) === 0) {
                        return true;
                    }
                    $pts = (int) $intento->puntuacion_total;
                    return $pts >= $baremo->puntuacion_min && $pts <= $baremo->puntuacion_max;
                })->count();

                $distribucionNiveles[] = [
                    'nivel' => $baremo->nivel,
                    'rango' => "{$baremo->puntuacion_min} – {$baremo->puntuacion_max} pts",
                    'color_hex' => $baremo->color_hex ?: '#6366f1',
                    'sugiere_orientacion' => (bool) $baremo->sugiere_orientacion,
                    'conteo' => $conteoNivel,
                    'porcentaje' => $totalIntentosFinalizados > 0 ? round(($conteoNivel / $totalIntentosFinalizados) * 100, 1) : 0,
                ];
            }
        } else {
            $agrupados = $intentosFinalizados->groupBy(fn ($i) => $i->nivel_resultado ?: 'COMPLETADO');
            foreach ($agrupados as $nivelNombre => $grupoItems) {
                $cnt = $grupoItems->count();
                $distribucionNiveles[] = [
                    'nivel' => $nivelNombre,
                    'rango' => 'General',
                    'color_hex' => '#6366f1',
                    'sugiere_orientacion' => false,
                    'conteo' => $cnt,
                    'porcentaje' => $totalIntentosFinalizados > 0 ? round(($cnt / $totalIntentosFinalizados) * 100, 1) : 0,
                ];
            }
        }

        // Estadísticas detalladas por reactivo (A6)
        $estadisticasPreguntas = [];
        $escalasVersion = $versionSeleccionada ? $versionSeleccionada->escalas : collect();

        if ($versionSeleccionada) {
            foreach ($versionSeleccionada->preguntas as $pregunta) {
                $tipo = strtoupper((string) ($pregunta->tipo_pregunta ?: 'ESCALA'));
                $respuestasDePregunta = collect();

                foreach ($intentosFinalizados as $intentoFin) {
                    $r = $intentoFin->respuestas->firstWhere('pregunta_id', $pregunta->pregunta_id);
                    if ($r) {
                        $r->setRelation('intento', $intentoFin);
                        $respuestasDePregunta->push($r);
                    }
                }

                $totalValidas = $respuestasDePregunta->count();
                $totalOmitidas = max(0, $totalIntentosFinalizados - $totalValidas);
                $promedioItem = $totalValidas > 0 ? round((float) $respuestasDePregunta->avg('puntos_obtenidos'), 2) : 0.0;

                $opcionesStats = [];
                $respuestasAbiertas = [];
                $opcionesPersonalizadas = is_array($pregunta->opciones_json) ? $pregunta->opciones_json : [];

                if ($tipo === 'TEXTO_LIBRE') {
                    foreach ($respuestasDePregunta as $respAbierta) {
                        $textoResp = trim((string) ($respAbierta->respuesta_texto ?? ''));
                        if ($textoResp === '') {
                            continue;
                        }
                        if ($qAbierta !== '' && mb_stripos($textoResp, $qAbierta) === false) {
                            continue;
                        }
                        $respuestasAbiertas[] = [
                            'respuesta_id' => $respAbierta->respuesta_id,
                            'intento_id' => $respAbierta->intento_id,
                            'texto' => $textoResp,
                            'fecha' => $respAbierta->intento?->fecha_finalizacion ?? $respAbierta->created_at,
                            'alumno_nombre' => trim(($respAbierta->intento?->usuario?->nombre ?? '') . ' ' . ($respAbierta->intento?->usuario?->apellido_paterno ?? '')),
                            'matricula' => $respAbierta->intento?->usuario?->estudiante?->matricula ?? 'S/M',
                        ];
                    }
                } elseif (in_array($tipo, ['OPCION_UNICA', 'OPCION_MULTIPLE'], true) && ! empty($opcionesPersonalizadas)) {
                    foreach ($opcionesPersonalizadas as $opItem) {
                        $etiquetaOp = (string) ($opItem['etiqueta'] ?? $opItem['texto'] ?? '');
                        $valorOp = (int) ($opItem['valor'] ?? $opItem['puntos'] ?? 0);

                        $frecuencia = $respuestasDePregunta->filter(function ($r) use ($etiquetaOp) {
                            $sel = is_array($r->opciones_seleccionadas) ? $r->opciones_seleccionadas : [];
                            return in_array($etiquetaOp, $sel, true) || trim((string) $r->respuesta_texto) === $etiquetaOp;
                        })->count();

                        $porcentajeOp = $totalValidas > 0 ? round(($frecuencia / $totalValidas) * 100, 1) : 0.0;

                        $opcionesStats[] = [
                            'etiqueta' => $etiquetaOp,
                            'valor' => $valorOp,
                            'frecuencia' => $frecuencia,
                            'porcentaje' => $porcentajeOp,
                        ];
                    }
                } else {
                    // ESCALA (o OPCION_UNICA respaldada por escalas de la versión)
                    foreach ($escalasVersion as $escalaOp) {
                        $frecuencia = $respuestasDePregunta->where('escala_id', $escalaOp->escala_id)->count();
                        $porcentajeOp = $totalValidas > 0 ? round(($frecuencia / $totalValidas) * 100, 1) : 0.0;

                        $opcionesStats[] = [
                            'escala_id' => $escalaOp->escala_id,
                            'etiqueta' => $escalaOp->etiqueta,
                            'valor' => $pregunta->es_inverso
                                ? (($escalasVersion->min('valor') + $escalasVersion->max('valor')) - $escalaOp->valor)
                                : $escalaOp->valor,
                            'valor_nominal' => $escalaOp->valor,
                            'frecuencia' => $frecuencia,
                            'porcentaje' => $porcentajeOp,
                        ];
                    }
                }

                $estadisticasPreguntas[] = [
                    'pregunta_id' => $pregunta->pregunta_id,
                    'numero_reactivo' => $pregunta->numero_reactivo,
                    'enunciado' => $pregunta->enunciado,
                    'tipo_pregunta' => $tipo,
                    'es_obligatoria' => (bool) ($pregunta->es_obligatoria ?? true),
                    'es_inverso' => (bool) $pregunta->es_inverso,
                    'es_critico' => (bool) $pregunta->es_critico,
                    'total_validas' => $totalValidas,
                    'total_omitidas' => $totalOmitidas,
                    'promedio_puntos' => $promedioItem,
                    'suma_puede_superar_100' => ($tipo === 'OPCION_MULTIPLE'),
                    'opciones' => $opcionesStats,
                    'respuestas_abiertas' => $respuestasAbiertas,
                ];
            }
        }

        $intentosPaginados = (clone $intentosBaseQuery)
            ->orderByDesc('fecha_finalizacion')
            ->orderByDesc('intento_id')
            ->paginate(15)
            ->withQueryString();

        $programas = Schema::hasTable('programas_educativos')
            ? ProgramaEducativo::where('activo', true)->orderBy('nombre')->get()
            : collect();

        $grupos = Schema::hasTable('grupos')
            ? Grupo::with('programa')
                ->where('activo', true)
                ->orderBy('cuatrimestre')
                ->orderBy('nombre')
                ->get()
                ->sortBy(fn ($g) => sprintf('%s-%02d-%s', $g->programa->clave ?? $g->programa->nombre ?? 'PE', $g->cuatrimestre, $g->nombre))
                ->values()
            : collect();

        return view('admin.tests.resultados', compact(
            'test',
            'versionSeleccionada',
            'alumnosUnicos',
            'totalIntentosFinalizados',
            'intentosEnProgresoCount',
            'totalRespuestasRegistradas',
            'promedioPuntaje',
            'minPuntaje',
            'maxPuntaje',
            'ultimaRespuestaFecha',
            'muestraPequena',
            'distribucionNiveles',
            'estadisticasPreguntas',
            'intentosPaginados',
            'mapaNumeroIntento',
            'programas',
            'grupos',
            'fechaInicio',
            'fechaFin',
            'programaId',
            'cuatrimestre',
            'grupoId',
            'qAbierta'
        ));
    }

    /**
     * Consulta de respuestas individuales de un intento específico (Exclusivo para Administrador autorizado — A7).
     */
    public function intentoDetalle(Request $request, int $id, int $intentoId): View
    {
        $test = InstrumentoEvaluacion::with('versiones')->findOrFail($id);

        $intento = EvaluacionIntento::with([
            'usuario.estudiante.programa',
            'version.preguntas' => fn ($q) => $q->orderBy('orden')->orderBy('numero_reactivo'),
            'version.escalas' => fn ($q) => $q->orderBy('orden'),
            'version.baremos' => fn ($q) => $q->orderBy('orden'),
            'respuestas.escala',
            'respuestas.pregunta',
        ])->findOrFail($intentoId);

        if ((int) $intento->version?->instrumento_id !== (int) $test->instrumento_id) {
            abort(404, 'El intento especificado no corresponde a este instrumento psicométrico.');
        }

        // Calcular número de intento cronológico para este estudiante en este instrumento
        $intentosPreviosIds = EvaluacionIntento::where('usuario_id', $intento->usuario_id)
            ->whereIn('version_id', $test->versiones->pluck('version_id'))
            ->orderBy('fecha_inicio')
            ->orderBy('intento_id')
            ->pluck('intento_id')
            ->values()
            ->all();

        $posicion = array_search($intento->intento_id, $intentosPreviosIds, true);
        $numeroIntentoAlumno = $posicion !== false ? ($posicion + 1) : 1;
        $totalIntentosAlumno = count($intentosPreviosIds);

        $baremoAplicado = $intento->version->baremos->first(function ($b) use ($intento) {
            if ($intento->nivel_resultado && strcasecmp($intento->nivel_resultado, $b->nivel) === 0) {
                return true;
            }
            $pts = (int) $intento->puntuacion_total;
            return $pts >= $b->puntuacion_min && $pts <= $b->puntuacion_max;
        });

        $respuestasPorPregunta = $intento->respuestas->keyBy('pregunta_id');

        $detalleReactivos = $intento->version->preguntas->map(function ($pregunta) use ($respuestasPorPregunta) {
            $resp = $respuestasPorPregunta->get($pregunta->pregunta_id);
            $tipo = strtoupper((string) ($pregunta->tipo_pregunta ?: 'ESCALA'));

            $textoRespuesta = 'Sin respuesta (Omitida)';
            $respondida = false;
            $puntos = 0;

            if ($resp) {
                $respondida = true;
                $puntos = (int) $resp->puntos_obtenidos;
                if ($resp->escala) {
                    $textoRespuesta = $resp->escala->etiqueta;
                } elseif (! empty($resp->opciones_seleccionadas) && is_array($resp->opciones_seleccionadas)) {
                    $textoRespuesta = implode(', ', $resp->opciones_seleccionadas);
                } elseif (! empty($resp->respuesta_texto)) {
                    $textoRespuesta = $resp->respuesta_texto;
                } else {
                    $textoRespuesta = 'Respuesta registrada';
                }
            }

            return [
                'pregunta_id' => $pregunta->pregunta_id,
                'numero_reactivo' => $pregunta->numero_reactivo,
                'enunciado' => $pregunta->enunciado,
                'tipo_pregunta' => $tipo,
                'es_obligatoria' => (bool) ($pregunta->es_obligatoria ?? true),
                'es_inverso' => (bool) $pregunta->es_inverso,
                'es_critico' => (bool) $pregunta->es_critico,
                'respondida' => $respondida,
                'texto_respuesta' => $textoRespuesta,
                'valor_nominal_escala' => $resp?->escala?->valor,
                'puntos_obtenidos' => $puntos,
                'sin_puntaje_clinico' => ($tipo === 'TEXTO_LIBRE'),
            ];
        });

        return view('admin.tests.intento-detalle', compact(
            'test',
            'intento',
            'numeroIntentoAlumno',
            'totalIntentosAlumno',
            'baremoAplicado',
            'detalleReactivos'
        ));
    }

    /* =========================================================================
     * MÉTODOS AUXILIARES PRIVADOS DE VALIDACIÓN Y PERSISTENCIA
     * ========================================================================= */

    /**
     * Normaliza y valida la estructura de reactivos enviada por el formulario de captura.
     *
     * @param array<int, mixed> $preguntasRaw Arreglo crudo de preguntas enviado en la petición HTTP.
     * @return array<int, array<string, mixed>> Reactivos normalizados con tipos, obligatoriedad y opciones.
     */
    private function normalizarPreguntasInput(array $preguntasRaw): array
    {
        $limpias = [];
        $orden = 1;

        foreach ($preguntasRaw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $enunciado = trim((string) ($item['enunciado'] ?? $item['texto_pregunta'] ?? ''));
            if ($enunciado === '') {
                continue;
            }

            $tipo = strtoupper(trim((string) ($item['tipo_pregunta'] ?? 'ESCALA')));
            if (! in_array($tipo, ['ESCALA', 'OPCION_UNICA', 'OPCION_MULTIPLE', 'TEXTO_LIBRE'], true)) {
                $tipo = 'ESCALA';
            }

            $opcionesJson = null;
            if (in_array($tipo, ['OPCION_UNICA', 'OPCION_MULTIPLE'], true) && ! empty($item['opciones']) && is_array($item['opciones'])) {
                $opts = [];
                foreach ($item['opciones'] as $op) {
                    if (! is_array($op)) {
                        continue;
                    }
                    $etiqueta = trim((string) ($op['etiqueta'] ?? $op['texto'] ?? ''));
                    if ($etiqueta !== '') {
                        $opts[] = [
                            'etiqueta' => $etiqueta,
                            'valor' => (int) ($op['valor'] ?? $op['puntos'] ?? 0),
                        ];
                    }
                }
                if (! empty($opts)) {
                    $opcionesJson = $opts;
                }
            }

            $limpias[] = [
                'numero_reactivo' => $orden,
                'enunciado' => $enunciado,
                'tipo_pregunta' => $tipo,
                'es_obligatoria' => isset($item['es_obligatoria']) ? filter_var($item['es_obligatoria'], FILTER_VALIDATE_BOOLEAN) : true,
                'opciones_json' => $opcionesJson,
                'descripcion_ayuda' => ! empty($item['descripcion_ayuda']) ? trim((string) $item['descripcion_ayuda']) : null,
                'es_inverso' => ($tipo === 'TEXTO_LIBRE') ? false : (! empty($item['es_inverso']) && filter_var($item['es_inverso'], FILTER_VALIDATE_BOOLEAN)),
                'es_critico' => ($tipo === 'TEXTO_LIBRE') ? false : (! empty($item['es_critico']) && filter_var($item['es_critico'], FILTER_VALIDATE_BOOLEAN)),
                'orden' => $orden,
                'activo' => true,
            ];
            $orden++;
        }

        return $limpias;
    }

    /**
     * Normaliza y valida la escala de opciones de respuesta global para reactivos tipo ESCALA.
     *
     * @param array<int, mixed> $escalasRaw Arreglo de opciones de escala enviado por el formulario.
     * @return array<int, array<string, mixed>> Escalas ordenadas y normalizadas con valor y etiqueta.
     */
    private function normalizarEscalasInput(array $escalasRaw): array
    {
        $limpias = [];
        $orden = 1;

        foreach ($escalasRaw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
            if ($etiqueta === '') {
                continue;
            }

            $limpias[] = [
                'valor' => (int) ($item['valor'] ?? ($orden - 1)),
                'etiqueta' => $etiqueta,
                'descripcion' => ! empty($item['descripcion']) ? trim((string) $item['descripcion']) : null,
                'orden' => $orden,
            ];
            $orden++;
        }

        return $limpias;
    }

    /**
     * Normaliza los rangos de baremo para la interpretación diagnóstica de puntajes.
     *
     * @param array<int, mixed> $baremosRaw Arreglo de reglas de baremo con rangos y feedback.
     * @return array<int, array<string, mixed>> Baremos normalizados con mínimos, máximos y recomendaciones.
     */
    private function normalizarBaremosInput(array $baremosRaw): array
    {
        $limpios = [];
        $orden = 1;

        foreach ($baremosRaw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $nivel = trim((string) ($item['nivel'] ?? ''));
            if ($nivel === '') {
                continue;
            }

            $min = (int) ($item['puntuacion_min'] ?? 0);
            $max = (int) ($item['puntuacion_max'] ?? 0);

            $limpios[] = [
                'puntuacion_min' => $min,
                'puntuacion_max' => max($min, $max),
                'nivel' => $nivel,
                'color_hex' => ! empty($item['color_hex']) ? trim((string) $item['color_hex']) : '#6366f1',
                'mensaje_retroalimentacion' => ! empty($item['mensaje_retroalimentacion'])
                    ? trim((string) $item['mensaje_retroalimentacion'])
                    : 'Gracias por completar este instrumento orientador.',
                'sugiere_orientacion' => ! empty($item['sugiere_orientacion']) && filter_var($item['sugiere_orientacion'], FILTER_VALIDATE_BOOLEAN),
                'orden' => $orden,
            ];
            $orden++;
        }

        return $limpios;
    }

    /**
     * Valida exhaustivamente que una versión cuente con la estructura requerida para publicarse.
     *
     * @param array<int, array<string, mixed>> $preguntas Reactivos validados.
     * @param array<int, array<string, mixed>> $escalas Opciones de escala global.
     * @param array<int, array<string, mixed>> $baremos Reglas de interpretación diagnóstica.
     * @return array<string, string> Mapa de errores encontrados (vacío si es apto para publicación).
     */
    private function validarConfiguracionParaPublicar(array $preguntas, array $escalas, array $baremos): array
    {
        $errores = [];

        if (count($preguntas) === 0) {
            $errores['preguntas'] = 'No es posible publicar un instrumento sin al menos una pregunta válida. Agrega reactivos o guarda como borrador.';
            return $errores;
        }

        $requiereEscalaGlobal = false;
        foreach ($preguntas as $idx => $p) {
            $num = $idx + 1;
            $tipo = strtoupper((string) ($p['tipo_pregunta'] ?? 'ESCALA'));
            $opciones = $p['opciones_json'] ?? [];

            if ($tipo === 'ESCALA') {
                $requiereEscalaGlobal = true;
            } elseif (in_array($tipo, ['OPCION_UNICA', 'OPCION_MULTIPLE'], true)) {
                if (empty($opciones) && count($escalas) < 2) {
                    $errores["preguntas.{$idx}"] = "El reactivo #{$num} ({$tipo}) requiere al menos 2 opciones de respuesta configuradas.";
                } elseif (! empty($opciones) && count($opciones) < 2) {
                    $errores["preguntas.{$idx}"] = "El reactivo #{$num} ({$tipo}) debe tener al menos 2 opciones de respuesta.";
                }
            }
        }

        if ($requiereEscalaGlobal && count($escalas) > 0 && count($escalas) < 2) {
            $errores['escalas'] = 'La escala global de respuesta debe contar con al menos 2 opciones válidas.';
        }

        foreach ($baremos as $bIdx => $b) {
            if ((int) ($b['puntuacion_max'] ?? 0) < (int) ($b['puntuacion_min'] ?? 0)) {
                $errores["baremos.{$bIdx}"] = 'En las reglas de baremo, el puntaje máximo no puede ser menor que el puntaje mínimo.';
            }
        }

        return $errores;
    }

    /**
     * Persiste en base de datos las preguntas, escalas y baremos correspondientes a una versión.
     *
     * @param VersionInstrumento $version Instancia de la versión del instrumento.
     * @param array<int, array<string, mixed>> $preguntasLimpias Reactivos normalizados.
     * @param array<int, array<string, mixed>> $escalasLimpias Opciones de escala normalizadas.
     * @param array<int, array<string, mixed>> $baremosLimpios Reglas de baremo normalizadas.
     * @return void
     */
    private function persistirContenidoVersion(
        VersionInstrumento $version,
        array $preguntasLimpias,
        array $escalasLimpias,
        array $baremosLimpios
    ): void {
        // Si hay preguntas de tipo ESCALA y no se enviaron escalas explícitas, generar escala Likert estándar 0-3
        $tienePreguntaEscala = collect($preguntasLimpias)->contains(fn ($p) => ($p['tipo_pregunta'] ?? 'ESCALA') === 'ESCALA');
        if ($tienePreguntaEscala && empty($escalasLimpias)) {
            $escalasLimpias = [
                ['valor' => 0, 'etiqueta' => 'Nunca / Ningún día', 'descripcion' => null, 'orden' => 1],
                ['valor' => 1, 'etiqueta' => 'Varios días', 'descripcion' => null, 'orden' => 2],
                ['valor' => 2, 'etiqueta' => 'Más de la mitad de los días', 'descripcion' => null, 'orden' => 3],
                ['valor' => 3, 'etiqueta' => 'Casi todos los días', 'descripcion' => null, 'orden' => 4],
            ];
        }

        foreach ($escalasLimpias as $esc) {
            EscalaOpcion::create([
                'version_id' => $version->version_id,
                'valor' => $esc['valor'],
                'etiqueta' => $esc['etiqueta'],
                'descripcion' => $esc['descripcion'],
                'orden' => $esc['orden'],
            ]);
        }

        $hasTipoCol = Schema::hasColumn('evaluaciones_preguntas', 'tipo_pregunta');
        $hasObligCol = Schema::hasColumn('evaluaciones_preguntas', 'es_obligatoria');
        $hasOpcionesCol = Schema::hasColumn('evaluaciones_preguntas', 'opciones_json');
        $hasAyudaCol = Schema::hasColumn('evaluaciones_preguntas', 'descripcion_ayuda');

        foreach ($preguntasLimpias as $preg) {
            $dataPreg = [
                'version_id' => $version->version_id,
                'numero_reactivo' => $preg['numero_reactivo'],
                'enunciado' => $preg['enunciado'],
                'es_inverso' => $preg['es_inverso'],
                'es_critico' => $preg['es_critico'],
                'orden' => $preg['orden'],
                'activo' => true,
            ];
            if ($hasTipoCol) {
                $dataPreg['tipo_pregunta'] = $preg['tipo_pregunta'];
            }
            if ($hasObligCol) {
                $dataPreg['es_obligatoria'] = $preg['es_obligatoria'];
            }
            if ($hasOpcionesCol) {
                $dataPreg['opciones_json'] = $preg['opciones_json'];
            }
            if ($hasAyudaCol) {
                $dataPreg['descripcion_ayuda'] = $preg['descripcion_ayuda'];
            }

            PreguntaEvaluacion::create($dataPreg);
        }

        if (! empty($preguntasLimpias) && empty($baremosLimpios)) {
            $maxEscala = ! empty($escalasLimpias) ? max(array_column($escalasLimpias, 'valor')) : 3;
            $maxTotal = max(10, count($preguntasLimpias) * max(1, $maxEscala));
            $corte1 = (int) floor($maxTotal * 0.33);
            $corte2 = (int) floor($maxTotal * 0.66);

            $baremosLimpios = [
                [
                    'puntuacion_min' => 0,
                    'puntuacion_max' => $corte1,
                    'nivel' => 'Bajo / Adecuado',
                    'color_hex' => '#10b981',
                    'mensaje_retroalimentacion' => 'Tus respuestas reflejan indicadores favorables en esta dimensión de bienestar.',
                    'sugiere_orientacion' => false,
                    'orden' => 1,
                ],
                [
                    'puntuacion_min' => $corte1 + 1,
                    'puntuacion_max' => $corte2,
                    'nivel' => 'Moderado / Preventivo',
                    'color_hex' => '#f59e0b',
                    'mensaje_retroalimentacion' => 'Se identifican áreas de oportunidad preventivas. Te recomendamos fortalecer tus hábitos de autocuidado.',
                    'sugiere_orientacion' => false,
                    'orden' => 2,
                ],
                [
                    'puntuacion_min' => $corte2 + 1,
                    'puntuacion_max' => max($corte2 + 1, $maxTotal * 2),
                    'nivel' => 'Prioritario / Orientación Sugerida',
                    'color_hex' => '#ef4444',
                    'mensaje_retroalimentacion' => 'Te invitamos cordialmente a agendar una sesión de acompañamiento con el área de orientación.',
                    'sugiere_orientacion' => true,
                    'orden' => 3,
                ],
            ];
        }

        foreach ($baremosLimpios as $bar) {
            BaremoRegla::create([
                'version_id' => $version->version_id,
                'puntuacion_min' => $bar['puntuacion_min'],
                'puntuacion_max' => $bar['puntuacion_max'],
                'nivel' => $bar['nivel'],
                'color_hex' => $bar['color_hex'],
                'mensaje_retroalimentacion' => $bar['mensaje_retroalimentacion'],
                'sugiere_orientacion' => $bar['sugiere_orientacion'],
                'orden' => $bar['orden'],
            ]);
        }
    }

    /**
     * Clona integralmente escalas, preguntas y baremos desde una versión origen hacia una nueva versión.
     *
     * @param VersionInstrumento $origen Versión base de la cual se duplicarán los elementos.
     * @param VersionInstrumento $destino Versión destino receptora de la clonación.
     * @return void
     */
    private function clonarContenidoEntreVersiones(VersionInstrumento $origen, VersionInstrumento $destino): void
    {
        foreach ($origen->escalas as $esc) {
            EscalaOpcion::create([
                'version_id' => $destino->version_id,
                'valor' => $esc->valor,
                'etiqueta' => $esc->etiqueta,
                'descripcion' => $esc->descripcion,
                'orden' => $esc->orden,
            ]);
        }

        $hasTipoCol = Schema::hasColumn('evaluaciones_preguntas', 'tipo_pregunta');
        $hasObligCol = Schema::hasColumn('evaluaciones_preguntas', 'es_obligatoria');
        $hasOpcionesCol = Schema::hasColumn('evaluaciones_preguntas', 'opciones_json');
        $hasAyudaCol = Schema::hasColumn('evaluaciones_preguntas', 'descripcion_ayuda');

        foreach ($origen->preguntas as $preg) {
            $dataPreg = [
                'version_id' => $destino->version_id,
                'numero_reactivo' => $preg->numero_reactivo,
                'enunciado' => $preg->enunciado,
                'es_inverso' => $preg->es_inverso,
                'es_critico' => $preg->es_critico,
                'orden' => $preg->orden,
                'activo' => $preg->activo,
            ];
            if ($hasTipoCol) {
                $dataPreg['tipo_pregunta'] = $preg->tipo_pregunta ?? 'ESCALA';
            }
            if ($hasObligCol) {
                $dataPreg['es_obligatoria'] = $preg->es_obligatoria ?? true;
            }
            if ($hasOpcionesCol) {
                $dataPreg['opciones_json'] = $preg->opciones_json;
            }
            if ($hasAyudaCol) {
                $dataPreg['descripcion_ayuda'] = $preg->descripcion_ayuda;
            }

            PreguntaEvaluacion::create($dataPreg);
        }

        foreach ($origen->baremos as $bar) {
            BaremoRegla::create([
                'version_id' => $destino->version_id,
                'puntuacion_min' => $bar->puntuacion_min,
                'puntuacion_max' => $bar->puntuacion_max,
                'nivel' => $bar->nivel,
                'color_hex' => $bar->color_hex,
                'mensaje_retroalimentacion' => $bar->mensaje_retroalimentacion,
                'sugiere_orientacion' => $bar->sugiere_orientacion,
                'orden' => $bar->orden,
            ]);
        }
    }

    /**
     * Calcula determinísticamente la siguiente etiqueta de versión secuencial (e.g. '2.0', '3.0').
     *
     * @param InstrumentoEvaluacion $test Instancia del instrumento psicométrico.
     * @return string Siguiente código de versión no repetido.
     */
    private function calcularSiguienteCodigoVersion(InstrumentoEvaluacion $test): string
    {
        $maxMajor = 1;
        foreach ($test->versiones as $v) {
            if (preg_match('/^(\d+)/', (string) $v->version, $m)) {
                $maxMajor = max($maxMajor, (int) $m[1]);
            }
        }
        $nextMajor = $maxMajor + 1;
        $candidato = "{$nextMajor}.0";
        while ($test->versiones()->where('version', $candidato)->exists()) {
            $nextMajor++;
            $candidato = "{$nextMajor}.0";
        }
        return $candidato;
    }

    /**
     * Emite notificaciones internas en base de datos a los estudiantes activos al publicar un instrumento.
     *
     * @param InstrumentoEvaluacion $test Instrumento publicado.
     * @return void
     */
    private function notificarNuevaEvaluacionAlumnos(InstrumentoEvaluacion $test): void
    {
        try {
            $alumnos = User::whereHas('rol', fn ($q) => $q->where('nombre', 'Alumno'))
                ->where(function ($q) {
                    $q->where('estado', 'ACTIVO')->orWhereNull('estado');
                })
                ->take(50)
                ->get();

            foreach ($alumnos as $alumno) {
                try {
                    // Enviar solo por base de datos durante operaciones administrativas síncronas si mail no está configurado
                    $alumno->notifyNow(new NuevaEvaluacionNotification($test->nombre), ['database']);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
