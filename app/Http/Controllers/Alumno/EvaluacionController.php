<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\EscalaOpcion;
use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\InstrumentoEvaluacion;
use App\Models\PreguntaEvaluacion;
use App\Services\Evaluaciones\EvaluacionScoringService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador de Aplicación y Calificación de Instrumentos Psicométricos (Portal Alumno).
 *
 * Administra el catálogo de tests para el alumno, recolección de consentimiento informado,
 * guardado progresivo de respuestas, cálculo normativo de baremos e historial de intentos.
 */
class EvaluacionController extends Controller
{
    /**
     * Inicializa el controlador con el servicio de calificación diagnóstica de instrumentos.
     *
     * @param EvaluacionScoringService $scoringService Servicio que evalúa reactivos y baremos psicométricos.
     */
    public function __construct(
        protected EvaluacionScoringService $scoringService
    ) {}

    /**
     * Muestra el catálogo dinámico de instrumentos de evaluación disponibles.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Manejo seguro de tolerancia si las tablas aún no se han creado en la BD principal
        if (! Schema::hasTable('evaluaciones_instrumentos') || ! Schema::hasTable('evaluaciones_versiones')) {
            $evaluacionesFallback = [
                [
                    'codigo' => 'GAD-7',
                    'nombre' => 'Escala de Ansiedad Generalizada',
                    'descripcion' => 'Instrumento breve de 7 reactivos para evaluar sintomatología de ansiedad generalizada en las últimas dos semanas.',
                    'duracion' => '3-5 min',
                    'preguntas' => 7,
                    'categoria' => 'Ansiedad',
                    'estado_disponibilidad' => 'EN_PREPARACION',
                ],
                [
                    'codigo' => 'PHQ-9',
                    'nombre' => 'Cuestionario de Salud del Paciente',
                    'descripcion' => 'Cuestionario de 9 reactivos orientado a reconocer el estado anímico y síntomas de depresión durante las últimas dos semanas.',
                    'duracion' => '4-6 min',
                    'preguntas' => 9,
                    'categoria' => 'Estado de ánimo',
                    'estado_disponibilidad' => 'EN_PREPARACION',
                ],
                [
                    'codigo' => 'PSS-10',
                    'nombre' => 'Escala de Estrés Percibido',
                    'descripcion' => 'Evaluación de 10 reactivos sobre el nivel de sobrecarga y control de situaciones cotidianas. Medida perceptual no diagnóstica.',
                    'duracion' => '5-7 min',
                    'preguntas' => 10,
                    'categoria' => 'Estrés',
                    'estado_disponibilidad' => 'EN_PREPARACION',
                ],
                [
                    'codigo' => 'WHO-5',
                    'nombre' => 'Índice de Bienestar General (OMS)',
                    'descripcion' => 'Medida positiva y respetuosa del bienestar psicológico subjetivo y la vitalidad general.',
                    'duracion' => '2-3 min',
                    'preguntas' => 5,
                    'categoria' => 'Bienestar general',
                    'estado_disponibilidad' => 'EN_PREPARACION',
                ],
            ];

            return view('alumno.evaluaciones.index', [
                'tablasDisponibles' => false,
                'instrumentos' => collect(),
                'evaluacionesFallback' => $evaluacionesFallback,
                'totalPublicados' => 0,
                'totalBorradores' => 4,
                'totalHistorial' => 0,
            ]);
        }

        // 2. Consulta de instrumentos activos con sus versiones y conteo de preguntas
        $instrumentos = InstrumentoEvaluacion::activos()
            ->with(['versiones' => function ($q) {
                $q->withCount('preguntas');
            }])
            ->get();

        // 3. Obtener últimos intentos finalizados y en progreso del alumno
        $ultimosIntentos = collect();
        $intentosEnProgreso = collect();
        $totalHistorial = 0;
        if (Schema::hasTable('evaluaciones_intentos')) {
            $totalHistorial = $user->evaluacionIntentos()->count();
            $ultimosIntentos = $user->evaluacionIntentos()
                ->where('estado', 'FINALIZADO')
                ->whereNotNull('fecha_finalizacion')
                ->latest('fecha_finalizacion')
                ->get()
                ->keyBy('version_id');

            $intentosEnProgreso = $user->evaluacionIntentos()
                ->where('estado', 'EN_PROGRESO')
                ->get()
                ->keyBy('version_id');
        }

        $totalPublicados = 0;
        $totalBorradores = 0;

        // 4. Mapeo del estado de disponibilidad de cada instrumento
        $instrumentos->each(function (InstrumentoEvaluacion $inst) use ($ultimosIntentos, $intentosEnProgreso, &$totalPublicados, &$totalBorradores) {
            $versionPublicada = $inst->versiones
                ->where('es_vigente', true)
                ->where('estado', 'PUBLICADA')
                ->first();

            $versionBorrador = $inst->versiones
                ->where('es_vigente', true)
                ->where('estado', 'BORRADOR')
                ->first();

            $versionActiva = $versionPublicada ?? $versionBorrador ?? $inst->versiones->first();

            $inst->version_mostrada = $versionActiva;
            $inst->preguntas_count = $versionActiva?->preguntas_count ?? 0;
            $inst->duracion_texto = $versionActiva
                ? "{$versionActiva->duracion_estimada_min} min"
                : '3-5 min';

            if ($versionPublicada) {
                $totalPublicados++;

                // Verificar primero si tiene un intento activo en progreso
                if ($intentoActivo = $intentosEnProgreso->get($versionPublicada->version_id)) {
                    $inst->estado_disponibilidad = 'EN_PROGRESO';
                    $inst->intento_activo_id = $intentoActivo->intento_id;
                    return;
                }

                $ultimoIntento = $ultimosIntentos->get($versionPublicada->version_id);

                if ($ultimoIntento && $ultimoIntento->fecha_finalizacion) {
                    $diasEspera = (int) $inst->dias_espera_reaplicacion;
                    $fechaDisponible = Carbon::parse($ultimoIntento->fecha_finalizacion)->addDays($diasEspera);

                    if ($diasEspera > 0 && now()->lessThan($fechaDisponible)) {
                        // Cálculo preciso en días enteros redondeando hacia arriba cualquier fracción restante
                        $segundosRestantes = max(0, now()->diffInSeconds($fechaDisponible, false));
                        $diasRestantes = (int) ceil($segundosRestantes / 86400);
                        $diasRestantes = max(1, $diasRestantes);

                        $inst->estado_disponibilidad = 'COMPLETADA_RECIENTE';
                        $inst->dias_restantes = $diasRestantes;
                        $inst->dias_restantes_texto = $diasRestantes === 1 ? '1 día' : "{$diasRestantes} días";
                        $inst->ultimo_intento_fecha = $ultimoIntento->fecha_finalizacion;
                        $inst->fecha_disponible = $fechaDisponible;
                    } else {
                        $inst->estado_disponibilidad = 'DISPONIBLE';
                    }
                } else {
                    $inst->estado_disponibilidad = 'DISPONIBLE';
                }
            } else {
                $totalBorradores++;
                $inst->estado_disponibilidad = 'EN_PREPARACION';
            }
        });

        return view('alumno.evaluaciones.index', [
            'tablasDisponibles' => true,
            'instrumentos' => $instrumentos,
            'evaluacionesFallback' => [],
            'totalPublicados' => $totalPublicados,
            'totalBorradores' => $totalBorradores,
            'totalHistorial' => $totalHistorial,
        ]);
    }

    /**
     * Muestra la vista de instrucciones previas y consentimiento informado para una prueba publicada.
     */
    public function instrucciones(Request $request, InstrumentoEvaluacion $instrumento)
    {
        // 1. Verificación de existencia de tablas
        if (! Schema::hasTable('evaluaciones_instrumentos') || ! Schema::hasTable('evaluaciones_versiones')) {
            return redirect()->route('alumno.evaluaciones')
                ->with('info', 'El módulo de evaluaciones se encuentra temporalmente en preparación.');
        }

        // 2. Autorización de acceso (Solo alumnos activos)
        Gate::authorize('viewAny', EvaluacionIntento::class);

        // 3. Búsqueda de la versión publicada y vigente
        $version = $instrumento->versiones()
            ->where('es_vigente', true)
            ->where('estado', 'PUBLICADA')
            ->first();

        // 4. Bloqueo estricto de acceso por URL a versiones no publicadas (BORRADOR / OBSOLETA)
        if (! $version) {
            abort(404, 'Este instrumento no cuenta con una versión publicada disponible actualmente.');
        }

        // Cargar metadatos asociados a la versión
        $version->loadCount('preguntas');
        $version->load(['escalas' => fn ($q) => $q->orderBy('orden')]);

        // 5. Verificar si el alumno tiene un intento en progreso para sugerir reanudación directa
        $intentoEnProgreso = null;
        $ultimoIntento = null;
        $diasRestantes = 0;

        if (Schema::hasTable('evaluaciones_intentos')) {
            $intentoEnProgreso = Auth::user()->evaluacionIntentos()
                ->where('version_id', $version->version_id)
                ->where('estado', 'EN_PROGRESO')
                ->first();

            $ultimoIntento = Auth::user()->evaluacionIntentos()
                ->where('version_id', $version->version_id)
                ->where('estado', 'FINALIZADO')
                ->latest('fecha_finalizacion')
                ->first();

            if ($ultimoIntento && $ultimoIntento->fecha_finalizacion) {
                $diasEspera = (int) $instrumento->dias_espera_reaplicacion;
                $fechaDisponible = Carbon::parse($ultimoIntento->fecha_finalizacion)->addDays($diasEspera);

                if ($diasEspera > 0 && now()->lessThan($fechaDisponible)) {
                    $segundosRestantes = max(0, now()->diffInSeconds($fechaDisponible, false));
                    $diasRestantes = (int) ceil($segundosRestantes / 86400);
                    $diasRestantes = max(1, $diasRestantes);
                }
            }
        }

        return view('alumno.evaluaciones.instrucciones', [
            'instrumento' => $instrumento,
            'version' => $version,
            'intentoEnProgreso' => $intentoEnProgreso,
            'ultimoIntento' => $ultimoIntento,
            'diasRestantes' => $diasRestantes,
        ]);
    }

    /**
     * Inicia un nuevo intento de evaluación tras validar el consentimiento informado explícito.
     * Si ya existe un intento en progreso, redirige a él de forma segura para evitar duplicados.
     */
    public function iniciar(Request $request, InstrumentoEvaluacion $instrumento)
    {
        if (! Schema::hasTable('evaluaciones_intentos') || ! Schema::hasTable('evaluaciones_versiones')) {
            return redirect()->route('alumno.evaluaciones')
                ->with('info', 'El módulo de evaluaciones se encuentra temporalmente en preparación.');
        }

        // 1. Autorización: solo alumnos activos pueden iniciar intentos
        Gate::authorize('create', EvaluacionIntento::class);

        // 2. Validación estricta del consentimiento informado
        $request->validate([
            'consentimiento' => ['required', 'accepted'],
            'consentimiento_version' => ['nullable', 'string', 'max:30'],
        ], [
            'consentimiento.accepted' => 'Debes otorgar tu consentimiento explícito antes de comenzar la evaluación.',
            'consentimiento.required' => 'Debes otorgar tu consentimiento explícito antes de comenzar la evaluación.',
        ]);

        // 3. Obtener versión vigente y publicada
        $version = $instrumento->versiones()
            ->where('es_vigente', true)
            ->where('estado', 'PUBLICADA')
            ->first();

        if (! $version) {
            abort(404, 'Este instrumento no cuenta con una versión publicada disponible actualmente.');
        }

        // 4. Verificar existencia de reactivos activos
        if ($version->preguntas()->where('activo', true)->count() === 0) {
            return redirect()->route('alumno.evaluaciones')
                ->with('error', 'Este instrumento se encuentra en validación colegiada y aún no cuenta con reactivos activos.');
        }

        $user = Auth::user();

        // 5. Verificar re-aplicación reciente si ya finalizó previamente
        $ultimoFinalizado = $user->evaluacionIntentos()
            ->where('version_id', $version->version_id)
            ->where('estado', 'FINALIZADO')
            ->latest('fecha_finalizacion')
            ->first();

        if ($ultimoFinalizado && $ultimoFinalizado->fecha_finalizacion) {
            $diasEspera = (int) $instrumento->dias_espera_reaplicacion;
            $fechaDisponible = Carbon::parse($ultimoFinalizado->fecha_finalizacion)->addDays($diasEspera);

            if ($diasEspera > 0 && now()->lessThan($fechaDisponible)) {
                $segundosRestantes = max(0, now()->diffInSeconds($fechaDisponible, false));
                $restantes = (int) ceil($segundosRestantes / 86400);
                $restantes = max(1, $restantes);
                $textoDias = $restantes === 1 ? '1 día' : "{$restantes} días";

                return redirect()->route('alumno.evaluaciones.instrucciones', $instrumento->instrumento_id)
                    ->with('error', "Debes esperar {$textoDias} para responder nuevamente este instrumento.");
            }
        }

        // 6. Evitar duplicados por doble clic: Reutilizar intento en progreso si ya existe
        $intentoExistente = $user->evaluacionIntentos()
            ->where('version_id', $version->version_id)
            ->where('estado', 'EN_PROGRESO')
            ->first();

        if ($intentoExistente) {
            return redirect()->route('alumno.evaluaciones.cuestionario', $intentoExistente->intento_id)
                ->with('info', 'Tienes un intento en progreso para este instrumento. Puedes continuar donde lo dejaste.');
        }

        // 7. Creación atómica del Intento y Registro de Consentimiento con bloqueo de concurrencia
        $textoConsentimiento = 'Este instrumento es una herramienta de tamizaje preventivo y formativo de la UPEMOR. Sus resultados no constituyen un diagnóstico médico o psicológico definitivo y están protegidos por el Código Ético del Psicólogo y la normativa de privacidad institucional.';

        $intento = DB::transaction(function () use ($user, $version, $request, $textoConsentimiento) {
            // Verificación defensiva con bloqueo por si una solicitud concurrente ya creó el intento
            $existenteEnTransaccion = EvaluacionIntento::where('usuario_id', $user->usuario_id)
                ->where('version_id', $version->version_id)
                ->where('estado', 'EN_PROGRESO')
                ->lockForUpdate()
                ->first();

            if ($existenteEnTransaccion) {
                return $existenteEnTransaccion;
            }

            $nuevoIntento = EvaluacionIntento::create([
                'usuario_id' => $user->usuario_id,
                'version_id' => $version->version_id,
                'estado' => 'EN_PROGRESO',
                'fecha_inicio' => now(),
                'ip_registro' => $request->ip(),
            ]);

            if (Schema::hasTable('evaluaciones_consentimientos')) {
                EvaluacionConsentimiento::create([
                    'usuario_id' => $user->usuario_id,
                    'version_id' => $version->version_id,
                    'intento_id' => $nuevoIntento->intento_id,
                    'consentimiento_version' => $request->input('consentimiento_version', 'v1.0-provisional'),
                    'texto_consentimiento' => $textoConsentimiento,
                    'fecha_consentimiento' => now(),
                    'ip_registro' => $request->ip(),
                ]);
            }

            return $nuevoIntento;
        });

        return redirect()->route('alumno.evaluaciones.cuestionario', $intento->intento_id);
    }

    /**
     * Muestra la pantalla interactiva del cuestionario (una pregunta por paso).
     */
    public function cuestionario(Request $request, EvaluacionIntento $intento)
    {
        // 1. Autorización: verificar pertenencia del intento al alumno autenticado
        Gate::authorize('view', $intento);

        // 2. Si el intento ya fue finalizado, redirigir a la pantalla de confirmación
        if ($intento->estaFinalizado()) {
            return redirect()->route('alumno.evaluaciones.finalizado', $intento->intento_id)
                ->with('info', 'Esta evaluación ya ha sido completada y sus respuestas no pueden modificarse.');
        }

        // 3. Cargar datos de la versión, preguntas ordenadas, opciones de escala y respuestas previas
        $intento->load([
            'version.instrumento',
            'version.preguntas' => fn ($q) => $q->where('activo', true)->orderBy('numero_reactivo'),
            'version.escalas' => fn ($q) => $q->orderBy('orden'),
            'respuestas',
        ]);

        $preguntas = $intento->version->preguntas;
        $escalas = $intento->version->escalas;
        $respuestasGuardadas = [];
        $respuestasDetalle = [];

        foreach ($intento->respuestas as $resp) {
            $respuestasGuardadas[$resp->pregunta_id] = $resp->escala_id ?? ($resp->opciones_seleccionadas ?: $resp->respuesta_texto);
            $respuestasDetalle[$resp->pregunta_id] = [
                'escala_id' => $resp->escala_id,
                'respuesta_texto' => $resp->respuesta_texto,
                'opciones_seleccionadas' => $resp->opciones_seleccionadas ?? [],
            ];
        }

        $totalPreguntas = $preguntas->count();
        $totalObligatorias = $preguntas->filter(fn ($p) => ($p->es_obligatoria ?? true) === true)->count();
        $totalRespondidas = count($respuestasGuardadas);

        // Determinar el índice inicial (0-based) de la primera pregunta pendiente de responder
        $primerPasoPendiente = 0;
        foreach ($preguntas as $idx => $preg) {
            if (! array_key_exists($preg->pregunta_id, $respuestasGuardadas)) {
                $primerPasoPendiente = $idx;
                break;
            }
        }

        return view('alumno.evaluaciones.cuestionario', [
            'intento' => $intento,
            'version' => $intento->version,
            'instrumento' => $intento->version->instrumento,
            'preguntas' => $preguntas,
            'escalas' => $escalas,
            'respuestasGuardadas' => $respuestasGuardadas,
            'respuestasDetalle' => $respuestasDetalle,
            'totalPreguntas' => $totalPreguntas,
            'totalObligatorias' => $totalObligatorias,
            'totalRespondidas' => $totalRespondidas,
            'primerPasoPendiente' => $primerPasoPendiente,
        ]);
    }

    /**
     * Endpoint interno JSON para guardar de manera persistente una respuesta individual.
     * Valida pertenencia de pregunta y opción a la misma versión del intento y recalcula puntos del servidor.
     * Incorpora protección contra escrituras desordenadas por latencia de red mediante cliente_timestamp.
     */
    public function guardarRespuesta(Request $request, EvaluacionIntento $intento)
    {
        // 1. Autorización: sólo el propietario puede responder y solo si está EN_PROGRESO
        Gate::authorize('update', $intento);

        if ($intento->estaFinalizado()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta evaluación ya ha sido finalizada y no acepta modificaciones.',
            ], 422);
        }

        // 2. Validación de entrada (soporta ESCALA, OPCION_UNICA, OPCION_MULTIPLE y TEXTO_LIBRE)
        $validated = $request->validate([
            'pregunta_id' => ['required', 'integer'],
            'escala_id' => ['nullable', 'integer'],
            'respuesta_texto' => ['nullable', 'string', 'max:2000'],
            'opciones_seleccionadas' => ['nullable', 'array'],
            'opciones_seleccionadas.*' => ['string', 'max:500'],
            'cliente_timestamp' => ['nullable', 'integer'],
        ]);

        // 3. Verificar que la pregunta pertenezca exactamente a la versión del intento
        $pregunta = PreguntaEvaluacion::where('pregunta_id', $validated['pregunta_id'])
            ->where('version_id', $intento->version_id)
            ->first();

        if (! $pregunta) {
            return response()->json([
                'success' => false,
                'message' => 'El reactivo especificado no pertenece a la versión de este cuestionario.',
            ], 422);
        }

        $tipoPregunta = strtoupper((string) ($pregunta->tipo_pregunta ?: 'ESCALA'));
        $escala = null;
        $respuestaTexto = isset($validated['respuesta_texto']) ? trim((string) $validated['respuesta_texto']) : null;
        $opcionesSeleccionadas = $validated['opciones_seleccionadas'] ?? null;

        // 4. Validar según tipo de reactivo
        if (! empty($validated['escala_id'])) {
            $escala = EscalaOpcion::where('escala_id', $validated['escala_id'])
                ->where('version_id', $intento->version_id)
                ->first();

            if (! $escala) {
                return response()->json([
                    'success' => false,
                    'message' => 'La opción de escala seleccionada no pertenece a la versión evaluada.',
                ], 422);
            }
        } elseif ($tipoPregunta === 'TEXTO_LIBRE') {
            if ($respuestaTexto === null || $respuestaTexto === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Escribe tu respuesta antes de guardar.',
                ], 422);
            }
        } elseif (in_array($tipoPregunta, ['OPCION_UNICA', 'OPCION_MULTIPLE'], true)) {
            if (empty($opcionesSeleccionadas) && ($respuestaTexto === null || $respuestaTexto === '')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selecciona al menos una opción válida.',
                ], 422);
            }
            if (empty($opcionesSeleccionadas) && $respuestaTexto !== null && $respuestaTexto !== '') {
                $opcionesSeleccionadas = [$respuestaTexto];
            }
            if ($respuestaTexto === null && ! empty($opcionesSeleccionadas)) {
                $respuestaTexto = implode(', ', $opcionesSeleccionadas);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar una opción de escala válida.',
            ], 422);
        }

        $clienteTimestamp = $validated['cliente_timestamp'] ?? null;
        $totalPreguntas = $intento->version->preguntas()->where('activo', true)->count();

        // 5. Estrategia de consistencia en backend contra escrituras desordenadas por latencia de red
        $respuestaExistente = EvaluacionRespuesta::where('intento_id', $intento->intento_id)
            ->where('pregunta_id', $pregunta->pregunta_id)
            ->first();

        if ($respuestaExistente && $clienteTimestamp && $respuestaExistente->cliente_timestamp && $respuestaExistente->cliente_timestamp > $clienteTimestamp) {
            $totalRespondidas = $intento->respuestas()->count();
            return response()->json([
                'success' => true,
                'stale' => true,
                'message' => 'Respuesta anterior descartada por ser más antigua que la registrada en el servidor.',
                'respuesta_id' => $respuestaExistente->respuesta_id,
                'pregunta_id' => $pregunta->pregunta_id,
                'escala_id' => $respuestaExistente->escala_id,
                'total_preguntas' => $totalPreguntas,
                'total_respondidas' => $totalRespondidas,
                'porcentaje' => $totalPreguntas > 0 ? round(($totalRespondidas / $totalPreguntas) * 100) : 0,
                'todas_respondidas' => ($totalRespondidas >= $totalPreguntas && $totalPreguntas > 0),
            ]);
        }

        // 6. El puntaje se calcula exclusivamente en el servidor mediante el Scoring Service
        $tempRespuesta = new EvaluacionRespuesta([
            'escala_id' => $escala?->escala_id,
            'respuesta_texto' => $respuestaTexto,
            'opciones_seleccionadas' => $opcionesSeleccionadas,
        ]);
        $puntos = $this->scoringService->calcularPuntosReactivo($pregunta, $escala, $intento->version, $tempRespuesta);

        // 7. Persistencia idempotente con updateOrCreate
        $payloadRespuesta = [
            'escala_id' => $escala?->escala_id,
            'puntos_obtenidos' => $puntos,
            'cliente_timestamp' => $clienteTimestamp,
        ];
        if (Schema::hasColumn('evaluaciones_respuestas', 'respuesta_texto')) {
            $payloadRespuesta['respuesta_texto'] = $respuestaTexto ?? $escala?->etiqueta;
        }
        if (Schema::hasColumn('evaluaciones_respuestas', 'opciones_seleccionadas')) {
            $payloadRespuesta['opciones_seleccionadas'] = $opcionesSeleccionadas;
        }

        $respuesta = EvaluacionRespuesta::updateOrCreate(
            [
                'intento_id' => $intento->intento_id,
                'pregunta_id' => $pregunta->pregunta_id,
            ],
            $payloadRespuesta
        );

        $totalRespondidas = $intento->respuestas()->count();
        $todasRespondidas = ($totalRespondidas >= $totalPreguntas && $totalPreguntas > 0);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta registrada correctamente.',
            'respuesta_id' => $respuesta->respuesta_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala?->escala_id,
            'respuesta_texto' => $respuesta->respuesta_texto ?? null,
            'opciones_seleccionadas' => $respuesta->opciones_seleccionadas ?? null,
            'total_preguntas' => $totalPreguntas,
            'total_respondidas' => $totalRespondidas,
            'porcentaje' => $totalPreguntas > 0 ? round(($totalRespondidas / $totalPreguntas) * 100) : 0,
            'todas_respondidas' => $todasRespondidas,
        ]);
    }

    /**
     * Endpoint interno JSON para consultar el progreso actual del intento.
     */
    public function progreso(Request $request, EvaluacionIntento $intento)
    {
        Gate::authorize('view', $intento);

        $totalPreguntas = $intento->version->preguntas()->where('activo', true)->count();
        $respuestas = $intento->respuestas->pluck('escala_id', 'pregunta_id')->all();
        $totalRespondidas = count($respuestas);

        return response()->json([
            'intento_id' => $intento->intento_id,
            'estado' => $intento->estado,
            'total_preguntas' => $totalPreguntas,
            'total_respondidas' => $totalRespondidas,
            'porcentaje' => $totalPreguntas > 0 ? round(($totalRespondidas / $totalPreguntas) * 100) : 0,
            'respuestas' => $respuestas,
            'todas_respondidas' => ($totalRespondidas >= $totalPreguntas && $totalPreguntas > 0),
        ]);
    }

    /**
     * Finaliza de forma segura el intento tras verificar que todas las preguntas obligatorias estén contestadas.
     * Utiliza el EvaluacionScoringService para calcular la puntuación determinista en el servidor.
     */
    public function finalizar(Request $request, EvaluacionIntento $intento)
    {
        // Si el intento ya fue finalizado (ej. por doble clic o reenvío), verificar que el alumno sea el propietario y redirigir
        if ($intento->estaFinalizado()) {
            Gate::authorize('view', $intento);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('alumno.evaluaciones.finalizado', $intento->intento_id),
                ]);
            }
            return redirect()->route('alumno.evaluaciones.finalizado', $intento->intento_id);
        }

        Gate::authorize('update', $intento);

        // 1. Verificar obligatoriedad de las preguntas obligatorias
        $queryObligatorias = $intento->version->preguntas()->where('activo', true);
        if (Schema::hasColumn('evaluaciones_preguntas', 'es_obligatoria')) {
            $queryObligatorias->where(function ($q) {
                $q->where('es_obligatoria', true)->orWhereNull('es_obligatoria');
            });
        }
        $preguntasObligatorias = $queryObligatorias->pluck('pregunta_id');

        $respuestasIds = $intento->respuestas()->pluck('pregunta_id');
        $pendientes = $preguntasObligatorias->diff($respuestasIds);

        if ($pendientes->isNotEmpty()) {
            $faltantes = $pendientes->count();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Aún tienes {$faltantes} pregunta(s) obligatoria(s) sin contestar.",
                    'faltantes' => $faltantes,
                ], 422);
            }

            return redirect()->route('alumno.evaluaciones.cuestionario', $intento->intento_id)
                ->with('error', "No puedes finalizar la evaluación porque aún tienes {$faltantes} pregunta(s) sin contestar.");
        }

        // 2. Finalización atómica y cálculo determinista mediante Scoring Service
        try {
            $this->scoringService->finalizarYCalificarIntento($intento, $request->ip());
            try {
                app(\App\Services\Puntos\PuntoService::class)->otorgarPorEvaluacion(Auth::user(), $intento);
            } catch (\Throwable $e) {
                // Silencioso para tolerancia a fallos
            }
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('alumno.evaluaciones.cuestionario', $intento->intento_id)
                ->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('alumno.evaluaciones.finalizado', $intento->intento_id),
            ]);
        }

        return redirect()->route('alumno.evaluaciones.finalizado', $intento->intento_id)
            ->with('success', '¡Has completado la evaluación satisfactoriamente!');
    }

    /**
     * Muestra la pantalla de resultados detallados de la evaluación completada.
     */
    public function finalizado(Request $request, EvaluacionIntento $intento)
    {
        Gate::authorize('view', $intento);

        if (! $intento->estaFinalizado()) {
            return redirect()->route('alumno.evaluaciones.cuestionario', $intento->intento_id);
        }

        $intento->load(['version.instrumento', 'version.baremos', 'respuestas.pregunta', 'respuestas.escala']);

        try {
            $scoreData = $this->scoringService->calcularPuntuacionIntento($intento);
        } catch (\DomainException $e) {
            // Fallback defensivo si el intento ya tiene puntuación histórica persistida
            $totalPreguntas = $intento->version->preguntas()->where('activo', true)->count();
            $maxValor = (int) ($intento->version->escalas()->max('valor') ?? 0);
            $maxPuntos = $totalPreguntas * $maxValor;
            $scoreData = [
                'puntuacion_total' => (int) $intento->puntuacion_total,
                'puntuacion_maxima' => $maxPuntos,
                'porcentaje' => $maxPuntos > 0 ? (int) round(((int) $intento->puntuacion_total / $maxPuntos) * 100) : 0,
                'nivel_resultado' => $intento->nivel_resultado ?? 'COMPLETADO',
                'color_hex' => '#7c3aed',
                'mensaje_retroalimentacion' => null,
                'sugiere_orientacion' => false,
                'tiene_alerta_critica' => (bool) $intento->tiene_alerta_critica,
                'total_preguntas' => $totalPreguntas,
                'baremo' => null,
            ];
        }

        return view('alumno.evaluaciones.finalizado', [
            'intento' => $intento,
            'version' => $intento->version,
            'instrumento' => $intento->version->instrumento,
            'scoreData' => $scoreData,
            'baremo' => $scoreData['baremo'] ?? null,
        ]);
    }

    /**
     * Muestra el historial personal y confidencial de evaluaciones del estudiante.
     */
    public function historial(Request $request)
    {
        Gate::authorize('viewAny', EvaluacionIntento::class);

        $user = Auth::user();

        if (! Schema::hasTable('evaluaciones_intentos')) {
            return view('alumno.evaluaciones.historial', [
                'intentos' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                'totalFinalizados' => 0,
                'totalEnProgreso' => 0,
            ]);
        }

        $intentos = $user->evaluacionIntentos()
            ->with(['version.instrumento'])
            ->orderByDesc('created_at')
            ->paginate(10);

        // Decorar con estado de disponibilidad de reaplicación
        $intentos->getCollection()->transform(function (EvaluacionIntento $intento) {
            $instrumento = $intento->version?->instrumento;

            if ($intento->estaFinalizado() && $instrumento && $intento->fecha_finalizacion) {
                $diasEspera = (int) $instrumento->dias_espera_reaplicacion;
                $fechaDisponible = Carbon::parse($intento->fecha_finalizacion)->addDays($diasEspera);

                if ($diasEspera > 0 && now()->lessThan($fechaDisponible)) {
                    $segundos = max(0, now()->diffInSeconds($fechaDisponible, false));
                    $dias = max(1, (int) ceil($segundos / 86400));
                    $intento->reaplicacion_disponible = false;
                    $intento->dias_restantes = $dias;
                    $intento->dias_restantes_texto = $dias === 1 ? '1 día' : "{$dias} días";
                } else {
                    $intento->reaplicacion_disponible = true;
                    $intento->dias_restantes = 0;
                    $intento->dias_restantes_texto = 'Disponible ahora';
                }
            } else {
                $intento->reaplicacion_disponible = false;
                $intento->dias_restantes = 0;
                $intento->dias_restantes_texto = null;
            }

            return $intento;
        });

        $totalFinalizados = $user->evaluacionIntentos()->where('estado', 'FINALIZADO')->count();
        $totalEnProgreso = $user->evaluacionIntentos()->where('estado', 'EN_PROGRESO')->count();

        return view('alumno.evaluaciones.historial', [
            'intentos' => $intentos,
            'totalFinalizados' => $totalFinalizados,
            'totalEnProgreso' => $totalEnProgreso,
        ]);
    }
}
