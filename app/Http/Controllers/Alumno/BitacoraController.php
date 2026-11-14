<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBitacoraRequest;
use App\Http\Requests\UpdateBitacoraRequest;
use App\Models\BitacoraEmocional;
use App\Models\Emocion;
use App\Services\Puntos\PuntoService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controlador de la Bitácora Emocional para Estudiantes.
 *
 * Permite a los alumnos registrar su estado emocional cotidiano, visualizar
 * su progreso en calendario interactivo (mensual, semanal, diario), consultar
 * historial y sincronizar transaccionalmente recompensas en puntos por registro continuo.
 */
class BitacoraController extends Controller
{
    /**
     * Muestra la vista principal de la bitácora emocional con calendario e historial cronológico.
     *
     * @param Request $request Petición HTTP con filtros opcionales de mes, año y rango de fechas.
     * @return View|JsonResponse|RedirectResponse Vista del calendario, JSON para AJAX o redirección en preparación.
     */
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        // Medida de protección durante el despliegue previo a la migración
        if (! Schema::hasTable('bitacoras_emocionales') || ! Schema::hasTable('emociones')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'disponible' => false,
                    'message' => 'El módulo de Bitácora Emocional se encuentra en preparación.',
                ], 503);
            }

            return redirect()->route('alumno.dashboard')->with('info', 'El módulo de Bitácora Emocional se encuentra temporalmente en preparación.');
        }

        $user = Auth::user();

        // Determinar mes y año para la vista del calendario (por defecto el mes actual)
        $mes = (int) $request->input('mes', Carbon::now()->month);
        $anio = (int) $request->input('anio', Carbon::now()->year);

        if ($mes < 1 || $mes > 12) {
            $mes = Carbon::now()->month;
        }
        if ($anio < 2020 || $anio > 2040) {
            $anio = Carbon::now()->year;
        }

        $fechaReferencia = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();

        // Catálogo de emociones activas
        $emociones = Emocion::activas()->get();

        // Registros del mes para indicadores del calendario
        $registrosMes = $user->bitacoras()
            ->with('emocion')
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->fecha)->format('Y-m-d'));

        // Consulta del historial cronológico de registros del alumno
        $queryHistorial = $user->bitacoras()->with('emocion');

        $filtroDesde = $request->input('filtro_desde');
        $filtroHasta = $request->input('filtro_hasta');

        // Validación preventiva de coherencia cronológica en el rango de fechas
        if ($request->filled('filtro_desde') && $request->filled('filtro_hasta')) {
            if ($filtroDesde > $filtroHasta) {
                return redirect()->route('alumno.bitacora.index', [
                    'mes' => $mes,
                    'anio' => $anio,
                ])->with('warning', 'La fecha inicial ("Desde") no puede ser posterior a la fecha final ("Hasta").');
            }
        }

        if ($request->filled('filtro_desde')) {
            $queryHistorial->whereDate('fecha', '>=', $filtroDesde);
        }
        if ($request->filled('filtro_hasta')) {
            $queryHistorial->whereDate('fecha', '<=', $filtroHasta);
        }

        $historial = $queryHistorial->orderBy('fecha', 'desc')
            ->orderBy('bitacora_id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Soporte para respuestas AJAX / JSON (para cambiar de mes de forma fluida)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'mes' => $mes,
                'anio' => $anio,
                'mes_nombre' => $fechaReferencia->translatedFormat('F Y'),
                'registros' => $registrosMes,
            ]);
        }

        return view('alumno.bitacora.index', [
            'mes' => $mes,
            'anio' => $anio,
            'fechaReferencia' => $fechaReferencia,
            'emociones' => $emociones,
            'registrosMes' => $registrosMes,
            'historial' => $historial,
        ]);
    }

    /**
     * Almacena una nueva entrada emocional diaria y bonifica puntos institucionales de bienestar.
     *
     * @param StoreBitacoraRequest $request Form Request con datos validados de emoción, fecha e intensidad.
     * @return RedirectResponse|JsonResponse Redirección con mensaje flash o respuesta JSON para API/AJAX.
     */
    public function store(StoreBitacoraRequest $request): RedirectResponse|JsonResponse
    {
        if (! Schema::hasTable('bitacoras_emocionales') || ! Schema::hasTable('emociones')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Módulo no disponible'], 503);
            }
            return redirect()->route('alumno.dashboard')->with('info', 'El módulo de Bitácora Emocional no está disponible actualmente.');
        }

        $validated = $request->validated();

        $bitacora = Auth::user()->bitacoras()->create([
            'emocion_id' => $validated['emocion_id'],
            'fecha' => $validated['fecha'],
            'intensidad' => $validated['intensidad'],
            'descripcion' => $validated['descripcion'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        try {
            app(PuntoService::class)->otorgarPorEmocionDiaria(Auth::user(), $bitacora);
        } catch (\Throwable $e) {
            // Silencioso para tolerancia a fallos
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tu estado emocional fue registrado correctamente.',
                'bitacora' => $bitacora->load('emocion'),
            ], 201);
        }

        return redirect()->route('alumno.bitacora.index')
            ->with('success', 'Tu estado emocional ha sido registrado con éxito.');
    }

    /**
     * Devuelve en formato JSON los detalles de un registro emocional previa autorización de policy.
     *
     * @param BitacoraEmocional $bitacora Modelo de la entrada emocional solicitada.
     * @return JsonResponse Representación en JSON con la relación de emoción cargada.
     * @throws \Illuminate\Auth\Access\AuthorizationException Si el usuario no es propietario del registro.
     */
    public function show(BitacoraEmocional $bitacora): JsonResponse
    {
        Gate::authorize('view', $bitacora);

        return response()->json($bitacora->load('emocion'));
    }

    /**
     * Actualiza los datos de un registro emocional existente.
     *
     * @param UpdateBitacoraRequest $request Form Request con datos validados de actualización.
     * @param BitacoraEmocional $bitacora Modelo a actualizar.
     * @return RedirectResponse|JsonResponse Redirección o JSON con el registro actualizado.
     * @throws \Illuminate\Auth\Access\AuthorizationException Si el usuario no tiene permisos sobre el registro.
     */
    public function update(UpdateBitacoraRequest $request, BitacoraEmocional $bitacora): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $bitacora);

        $validated = $request->validated();

        $bitacora->update([
            'emocion_id' => $validated['emocion_id'],
            'intensidad' => $validated['intensidad'],
            'descripcion' => $validated['descripcion'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Registro emocional actualizado correctamente.',
                'bitacora' => $bitacora->load('emocion'),
            ]);
        }

        return redirect()->route('alumno.bitacora.index')
            ->with('success', 'Registro emocional actualizado correctamente.');
    }

    /**
     * Elimina un registro emocional revirtiendo de forma transaccional los puntos bonificados.
     *
     * @param Request $request Petición HTTP.
     * @param BitacoraEmocional $bitacora Registro de bitácora a eliminar.
     * @return RedirectResponse|JsonResponse Redirección o JSON con información del saldo revertido.
     * @throws \Illuminate\Auth\Access\AuthorizationException Si el usuario no tiene permisos de eliminación.
     */
    public function destroy(Request $request, BitacoraEmocional $bitacora): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $bitacora);

        try {
            $resultado = DB::transaction(function () use ($bitacora) {
                $resultadoPuntos = app(PuntoService::class)->revertirPorEliminacionEmocion(Auth::user(), $bitacora);
                $bitacora->delete();
                return $resultadoPuntos;
            });
        } catch (DomainException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('alumno.bitacora.index')
                ->with('error', $e->getMessage());
        }

        $mensaje = (!empty($resultado['revertido']))
            ? "El registro emocional ha sido eliminado exitosamente y se ajustó tu saldo de puntos (-{$resultado['puntos']} pts)."
            : "El registro emocional ha sido eliminado exitosamente.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'puntos_revertidos' => $resultado['puntos'] ?? 0,
                'saldo_nuevo' => $resultado['saldo_nuevo'] ?? null,
            ]);
        }

        return redirect()->route('alumno.bitacora.index')
            ->with('success', $mensaje);
    }
}
