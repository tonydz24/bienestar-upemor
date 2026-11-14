<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\Meditacion;
use App\Models\MeditacionSesion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MeditacionController extends Controller
{
    /**
     * Catálogo de ejercicios de meditación y relajación guiada (FN.6).
     */
    public function index(Request $request): View
    {
        $usuario = Auth::user();

        $query = Meditacion::query()->publicadas();

        if ($request->filled('categoria') && in_array($request->categoria, array_keys(Meditacion::categoriasCatalogo()))) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('nivel') && in_array($request->nivel, [Meditacion::NIVEL_PRINCIPIANTE, Meditacion::NIVEL_INTERMEDIO, Meditacion::NIVEL_AVANZADO])) {
            $query->where('nivel', $request->nivel);
        }

        if ($request->filled('q')) {
            $busqueda = trim($request->q);
            $query->where(function ($q) use ($busqueda) {
                $q->where('titulo', 'LIKE', "%{$busqueda}%")
                  ->orWhere('descripcion', 'LIKE', "%{$busqueda}%");
            });
        }

        $meditaciones = $query->orderBy('vistas_count', 'desc')
            ->orderBy('titulo', 'asc')
            ->paginate(9)
            ->withQueryString();

        // Métricas personales confidenciales del alumno
        $totalSesiones = MeditacionSesion::where('usuario_id', $usuario->usuario_id)
            ->where('completada', true)
            ->count();

        $segundosTotales = MeditacionSesion::where('usuario_id', $usuario->usuario_id)
            ->where('completada', true)
            ->sum('duracion_segundos');
        $minutosTotales = (int) round($segundosTotales / 60);

        $favoritoRow = MeditacionSesion::where('usuario_id', $usuario->usuario_id)
            ->where('completada', true)
            ->select('meditacion_id', DB::raw('count(*) as total'))
            ->groupBy('meditacion_id')
            ->orderByDesc('total')
            ->first();

        $meditacionFavorita = $favoritoRow ? Meditacion::find($favoritoRow->meditacion_id) : null;

        $categorias = Meditacion::categoriasCatalogo();

        // Conteo de ejercicios publicados por categoría para las píldoras de navegación
        $conteoPorCategoria = Meditacion::query()->publicadas()
            ->select('categoria', DB::raw('count(*) as total'))
            ->groupBy('categoria')
            ->pluck('total', 'categoria')
            ->all();
        $totalPublicadas = Meditacion::query()->publicadas()->count();

        return view('alumno.meditaciones.index', compact(
            'meditaciones',
            'categorias',
            'conteoPorCategoria',
            'totalPublicadas',
            'totalSesiones',
            'minutosTotales',
            'meditacionFavorita'
        ));
    }

    /**
     * Vista de detalle del ejercicio.
     */
    public function show(Meditacion $meditacion): View
    {
        abort_unless($meditacion->es_publicado, 404, 'La meditación solicitada no está disponible actualmente.');

        $usuario = Auth::user();
        $vecesCompletada = MeditacionSesion::where('usuario_id', $usuario->usuario_id)
            ->where('meditacion_id', $meditacion->meditacion_id)
            ->where('completada', true)
            ->count();

        $relacionadas = Meditacion::publicadas()
            ->where('meditacion_id', '!=', $meditacion->meditacion_id)
            ->where('categoria', $meditacion->categoria)
            ->take(3)
            ->get();

        return view('alumno.meditaciones.show', compact('meditacion', 'vecesCompletada', 'relacionadas'));
    }

    /**
     * Sala interactiva de relajación guiada (FN.6).
     */
    public function sesion(Meditacion $meditacion): View
    {
        abort_unless($meditacion->es_publicado, 404, 'La meditación solicitada no está disponible.');

        // Incrementar contador de visualizaciones/usos
        $meditacion->increment('vistas_count');

        return view('alumno.meditaciones.sesion', compact('meditacion'));
    }

    /**
     * Registro de sesión finalizada por el alumno (estrictamente confidencial).
     */
    public function guardarSesion(Request $request, Meditacion $meditacion): JsonResponse|RedirectResponse
    {
        abort_unless($meditacion->es_publicado, 404, 'La meditación solicitada no está disponible o ha sido retirada.');

        $validated = $request->validate([
            'duracion_segundos' => 'required|integer|min:1|max:7200',
            'completada' => 'required|boolean',
        ]);

        $usuario = Auth::user();

        $sesion = MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $usuario->usuario_id,
            'duracion_segundos' => $validated['duracion_segundos'],
            'completada' => (bool) $validated['completada'],
            'fecha_sesion' => now(),
        ]);

        $puntosMovimiento = null;
        if ($sesion->completada) {
            try {
                $puntosMovimiento = app(\App\Services\Puntos\PuntoService::class)->otorgarPorMeditacion($usuario, $sesion);
            } catch (\Throwable $e) {
                // Silencioso para tolerancia a fallos
            }
        }

        $puntosAviso = $puntosMovimiento ? " (+{$puntosMovimiento->puntos} pts bienestar)" : '';
        $mensaje = "¡Excelente trabajo! Has completado tu práctica de bienestar{$puntosAviso}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'sesion_id' => $sesion->sesion_id,
            ]);
        }

        return redirect()->route('alumno.meditaciones.show', $meditacion)
            ->with('status', $mensaje);
    }

    /**
     * Completa la reproducción interactiva de video y acredita puntos de bienestar (FN.6 y FN.11).
     */
    public function completar(Request $request, Meditacion $meditacion): JsonResponse
    {
        abort_unless($meditacion->es_publicado, 404, 'La meditación solicitada no está disponible o ha sido retirada.');

        $usuario = Auth::user();

        // Validar que sea un estudiante registrado
        $estudiante = $usuario->estudiante;
        if (! $estudiante) {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo los alumnos registrados pueden completar actividades de bienestar y recibir puntos.',
            ], 403);
        }

        $hoy = now()->toDateString();

        // Idempotencia: Verificar si el estudiante ya registró una sesión de esta meditación el día de hoy
        $yaCompletadaHoy = MeditacionSesion::where('usuario_id', $usuario->usuario_id)
            ->where('meditacion_id', $meditacion->meditacion_id)
            ->where('completada', true)
            ->whereDate('fecha_sesion', $hoy)
            ->exists();

        if ($yaCompletadaHoy) {
            return response()->json([
                'status' => 'already_completed',
                'message' => 'Ya has registrado tu sesión de hoy para esta actividad.',
                'puntos_otorgados' => 0,
                'nuevo_saldo' => (int) $estudiante->saldo_puntos,
                'saldo_actual' => (int) $estudiante->saldo_puntos,
            ]);
        }

        $duracionSegundos = (int) ($request->input('duracion_segundos') ?: ($meditacion->duracion_minutos * 60));
        if ($duracionSegundos <= 0) {
            $duracionSegundos = 300;
        }

        $sesion = MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $usuario->usuario_id,
            'duracion_segundos' => $duracionSegundos,
            'completada' => true,
            'fecha_sesion' => now(),
        ]);

        $puntosAOtorgar = $meditacion->puntos ?: 2;

        try {
            app(\App\Services\Puntos\PuntoService::class)->otorgarPorMeditacion($usuario, $sesion);
        } catch (\Throwable $e) {
            // Silencioso para tolerancia a fallos
        }

        $estudiante->refresh();
        $nuevoSaldo = (int) $estudiante->saldo_puntos;

        return response()->json([
            'status' => 'success',
            'message' => '¡Has ganado 2 puntos de bienestar!',
            'puntos_otorgados' => $puntosAOtorgar,
            'nuevo_saldo' => $nuevoSaldo,
        ]);
    }

    /**
     * Historial confidencial de sesiones del estudiante.
     */
    public function historial(): View
    {
        $usuario = Auth::user();

        $sesiones = MeditacionSesion::with('meditacion')
            ->where('usuario_id', $usuario->usuario_id)
            ->orderBy('fecha_sesion', 'desc')
            ->paginate(12);

        $totalSesiones = MeditacionSesion::where('usuario_id', $usuario->usuario_id)->where('completada', true)->count();
        $segundosTotales = MeditacionSesion::where('usuario_id', $usuario->usuario_id)->where('completada', true)->sum('duracion_segundos');
        $minutosTotales = (int) round($segundosTotales / 60);

        return view('alumno.meditaciones.historial', compact('sesiones', 'totalSesiones', 'minutosTotales'));
    }
}

