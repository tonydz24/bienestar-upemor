<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meditacion;
use App\Models\User;
use App\Notifications\NuevaMeditacionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MeditacionAdminController extends Controller
{
    /**
     * Listado administrativo de ejercicios de relajación y meditación (FN.6).
     */
    public function index(Request $request): View
    {
        $query = Meditacion::withCount('sesiones')->with('creador');

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'publicado') {
                $query->where('es_publicado', true);
            } elseif ($request->estado === 'borrador') {
                $query->where('es_publicado', false);
            }
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('titulo', 'LIKE', "%{$term}%")
                  ->orWhere('descripcion', 'LIKE', "%{$term}%");
            });
        }

        $meditaciones = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $metricas = [
            'total' => Meditacion::count(),
            'publicadas' => Meditacion::where('es_publicado', true)->count(),
            'borradores' => Meditacion::where('es_publicado', false)->count(),
            'total_sesiones' => \App\Models\MeditacionSesion::where('completada', true)->count(),
        ];

        $categorias = Meditacion::categoriasCatalogo();

        return view('admin.meditaciones.index', compact('meditaciones', 'metricas', 'categorias'));
    }

    /**
     * Formulario de creación.
     */
    public function create(): View
    {
        $categorias = Meditacion::categoriasCatalogo();
        $tiposContenido = Meditacion::tiposContenidoCatalogo();
        return view('admin.meditaciones.create', compact('categorias', 'tiposContenido'));
    }

    /**
     * Guarda una nueva meditación.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'titulo' => 'required|string|max:150|unique:meditaciones,titulo',
            'categoria' => 'required|in:' . implode(',', array_keys(Meditacion::categoriasCatalogo())),
            'tipo_contenido' => 'nullable|in:' . implode(',', array_keys(Meditacion::tiposContenidoCatalogo())),
            'nivel' => 'required|in:PRINCIPIANTE,INTERMEDIO,AVANZADO',
            'duracion_minutos' => 'required|integer|min:1|max:60',
            'descripcion' => 'required|string|max:1500',
            'media_url' => 'nullable|url|max:500',
            'transcripcion' => 'nullable|string|max:5000',
            'icono' => 'nullable|string|max:80',
            'es_publicado' => 'nullable|boolean',
            'notificar_alumnos' => 'nullable|boolean',
            'pasos' => 'nullable|array',
            'pasos.*.titulo' => 'required_with:pasos|string|max:100',
            'pasos.*.duracion_segundos' => 'required_with:pasos|integer|min:2|max:600',
            'pasos.*.fase' => 'required_with:pasos|string|max:50',
            'pasos.*.indicacion' => 'required_with:pasos|string|max:500',
        ], [
            'tipo_contenido.required' => 'Debes seleccionar el tipo de recurso multimedia.',
            'media_url.url' => 'La URL del recurso audiovisual debe ser una dirección web válida.',
        ]);

        if (in_array($request->input('tipo_contenido'), [Meditacion::TIPO_AUDIO, Meditacion::TIPO_VIDEO]) && empty($request->input('media_url'))) {
            return back()->withInput()->withErrors(['media_url' => 'La URL del recurso multimedia es obligatoria para contenidos de audio o video.']);
        }

        if ($request->input('tipo_contenido') === Meditacion::TIPO_INTERACTIVO && (empty($request->input('pasos')) || !is_array($request->input('pasos')))) {
            return back()->withInput()->withErrors(['pasos' => 'Los ejercicios interactivos requieren al menos un paso o fase de respiración guiada.']);
        }

        $pasos = [];
        if ($request->filled('pasos') && is_array($request->input('pasos'))) {
            foreach ($request->input('pasos') as $index => $paso) {
                if (!empty($paso['titulo'])) {
                    $pasos[] = [
                        'paso' => $index + 1,
                        'titulo' => trim($paso['titulo']),
                        'duracion_segundos' => (int) ($paso['duracion_segundos'] ?? 4),
                        'fase' => trim($paso['fase'] ?? 'respira'),
                        'indicacion' => trim($paso['indicacion'] ?? ''),
                    ];
                }
            }
        }

        $instrucciones = [
            'tipo_contenido' => $request->input('tipo_contenido', Meditacion::TIPO_INTERACTIVO),
            'media_url' => $request->input('media_url') ? trim($request->input('media_url')) : null,
            'transcripcion' => $request->input('transcripcion') ? trim($request->input('transcripcion')) : null,
            'pasos' => $pasos,
        ];

        $esPublicado = $request->boolean('es_publicado');

        $meditacion = Meditacion::create([
            'titulo' => trim($request->input('titulo')),
            'slug' => Str::slug($request->input('titulo')),
            'categoria' => $request->input('categoria'),
            'nivel' => $request->input('nivel'),
            'duracion_minutos' => (int) $request->input('duracion_minutos'),
            'descripcion' => trim($request->input('descripcion')),
            'icono' => $request->input('icono') ?: 'bi-flower1',
            'es_publicado' => $esPublicado,
            'instrucciones' => $instrucciones,
            'creado_por' => Auth::id(),
        ]);

        // Si se solicitó notificar a alumnos (por defecto sí al publicar) y está publicada
        $debeNotificar = $request->has('notificar_alumnos') ? $request->boolean('notificar_alumnos') : true;
        if ($esPublicado && $debeNotificar) {
            $alumnos = User::whereHas('rol', fn($q) => $q->where('nombre', 'Alumno'))
                ->where(function ($q) {
                    $q->where('estado', 'ACTIVO')->orWhereNull('estado');
                })
                ->take(50) // límite de seguridad para evitar sobrecarga en sandbox
                ->get();

            foreach ($alumnos as $alumno) {
                try {
                    $alumno->notify(new NuevaMeditacionNotification($meditacion));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return redirect()->route('admin.meditaciones.index')
            ->with('status', 'Ejercicio de relajación "' . $meditacion->titulo . '" creado exitosamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(Meditacion $meditacion): View
    {
        $categorias = Meditacion::categoriasCatalogo();
        $tiposContenido = Meditacion::tiposContenidoCatalogo();
        return view('admin.meditaciones.edit', compact('meditacion', 'categorias', 'tiposContenido'));
    }

    /**
     * Actualiza el ejercicio.
     */
    public function update(Request $request, Meditacion $meditacion): RedirectResponse
    {
        $request->validate([
            'titulo' => 'required|string|max:150|unique:meditaciones,titulo,' . $meditacion->meditacion_id . ',meditacion_id',
            'categoria' => 'required|in:' . implode(',', array_keys(Meditacion::categoriasCatalogo())),
            'tipo_contenido' => 'nullable|in:' . implode(',', array_keys(Meditacion::tiposContenidoCatalogo())),
            'nivel' => 'required|in:PRINCIPIANTE,INTERMEDIO,AVANZADO',
            'duracion_minutos' => 'required|integer|min:1|max:60',
            'descripcion' => 'required|string|max:1500',
            'media_url' => 'nullable|url|max:500',
            'transcripcion' => 'nullable|string|max:5000',
            'icono' => 'nullable|string|max:80',
            'es_publicado' => 'nullable|boolean',
            'pasos' => 'nullable|array',
            'pasos.*.titulo' => 'required_with:pasos|string|max:100',
            'pasos.*.duracion_segundos' => 'required_with:pasos|integer|min:2|max:600',
            'pasos.*.fase' => 'required_with:pasos|string|max:50',
            'pasos.*.indicacion' => 'required_with:pasos|string|max:500',
        ], [
            'tipo_contenido.required' => 'Debes seleccionar el tipo de recurso multimedia.',
            'media_url.url' => 'La URL del recurso audiovisual debe ser una dirección web válida.',
        ]);

        if (in_array($request->input('tipo_contenido'), [Meditacion::TIPO_AUDIO, Meditacion::TIPO_VIDEO]) && empty($request->input('media_url'))) {
            return back()->withInput()->withErrors(['media_url' => 'La URL del recurso multimedia es obligatoria para contenidos de audio o video.']);
        }

        if ($request->input('tipo_contenido') === Meditacion::TIPO_INTERACTIVO && (empty($request->input('pasos')) || !is_array($request->input('pasos')))) {
            return back()->withInput()->withErrors(['pasos' => 'Los ejercicios interactivos requieren al menos un paso o fase de respiración guiada.']);
        }

        $pasos = [];
        if ($request->filled('pasos') && is_array($request->input('pasos'))) {
            foreach ($request->input('pasos') as $index => $paso) {
                if (!empty($paso['titulo'])) {
                    $pasos[] = [
                        'paso' => $index + 1,
                        'titulo' => trim($paso['titulo']),
                        'duracion_segundos' => (int) ($paso['duracion_segundos'] ?? 4),
                        'fase' => trim($paso['fase'] ?? 'respira'),
                        'indicacion' => trim($paso['indicacion'] ?? ''),
                    ];
                }
            }
        }

        $instrucciones = [
            'tipo_contenido' => $request->input('tipo_contenido', Meditacion::TIPO_INTERACTIVO),
            'media_url' => $request->input('media_url') ? trim($request->input('media_url')) : null,
            'transcripcion' => $request->input('transcripcion') ? trim($request->input('transcripcion')) : null,
            'pasos' => $pasos,
        ];

        $meditacion->update([
            'titulo' => trim($request->input('titulo')),
            'categoria' => $request->input('categoria'),
            'nivel' => $request->input('nivel'),
            'duracion_minutos' => (int) $request->input('duracion_minutos'),
            'descripcion' => trim($request->input('descripcion')),
            'icono' => $request->input('icono') ?: 'bi-flower1',
            'es_publicado' => $request->boolean('es_publicado'),
            'instrucciones' => $instrucciones,
        ]);

        return redirect()->route('admin.meditaciones.index')
            ->with('status', 'Ejercicio "' . $meditacion->titulo . '" actualizado con éxito.');
    }

    /**
     * Alternar estado de publicación (borrador/retirado <-> publicado).
     */
    public function togglePublicado(Meditacion $meditacion): RedirectResponse
    {
        return $meditacion->es_publicado ? $this->retirar($meditacion) : $this->publicar($meditacion);
    }

    /**
     * Retira formalmente el ejercicio del catálogo protegiendo las sesiones históricas.
     */
    public function retirar(Meditacion $meditacion): RedirectResponse
    {
        if (! $meditacion->es_publicado) {
            return back()->with('status', "El ejercicio '{$meditacion->titulo}' ya se encontraba retirado del catálogo.");
        }

        $meditacion->update(['es_publicado' => false]);

        return back()->with('status', "El ejercicio '{$meditacion->titulo}' ha sido retirado del catálogo institucional. El historial de sesiones de los alumnos permanece intacto.");
    }

    /**
     * Vuelve a publicar o activa un ejercicio previamente retirado o en borrador.
     */
    public function publicar(Meditacion $meditacion): RedirectResponse
    {
        if ($meditacion->es_publicado) {
            return back()->with('status', "El ejercicio '{$meditacion->titulo}' ya se encuentra publicado en el catálogo activo.");
        }

        if (empty($meditacion->titulo) || empty($meditacion->categoria) || empty($meditacion->duracion_minutos)) {
            return back()->with('error', "No se puede publicar el ejercicio porque carece de información requerida.");
        }

        $meditacion->update(['es_publicado' => true]);

        try {
            $alumnos = User::whereHas('rol', fn($q) => $q->where('nombre', 'Alumno'))
                ->where(function ($q) {
                    $q->where('estado', 'ACTIVO')->orWhereNull('estado');
                })
                ->take(50)
                ->get();

            foreach ($alumnos as $alumno) {
                $alumno->notify(new NuevaMeditacionNotification($meditacion));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', "El ejercicio '{$meditacion->titulo}' ha sido publicado exitosamente en el catálogo de los alumnos.");
    }

    /**
     * Eliminación definitiva y autorizada de un ejercicio guiado y sus sesiones vinculadas.
     * Si cuenta con sesiones registradas, exige confirmación reforzada mediante la palabra clave 'ELIMINAR'.
     */
    public function destroy(Request $request, Meditacion $meditacion): RedirectResponse
    {
        $sesionesCount = $meditacion->sesiones()->count();
        $titulo = $meditacion->titulo;

        if ($sesionesCount > 0) {
            $confirmacion = strtoupper(trim((string) $request->input('confirmacion_palabra', $request->input('confirmacion_eliminar', ''))));
            if ($confirmacion !== 'ELIMINAR') {
                return back()->with('error', "Para eliminar definitivamente el ejercicio '{$titulo}' con sus {$sesionesCount} sesiones registradas, debes escribir la palabra de seguridad ELIMINAR.");
            }
        }

        DB::transaction(function () use ($meditacion) {
            // Eliminar atómicamente únicamente las sesiones vinculadas a este ejercicio
            $meditacion->sesiones()->delete();
            $meditacion->delete();
        });

        $mensaje = $sesionesCount > 0
            ? "El ejercicio '{$titulo}' y sus {$sesionesCount} sesiones de práctica registradas han sido eliminados definitivamente."
            : "El ejercicio '{$titulo}' ha sido eliminado exitosamente.";

        return redirect()->route('admin.meditaciones.index')
            ->with('success', $mensaje)
            ->with('status', $mensaje);
    }
}

