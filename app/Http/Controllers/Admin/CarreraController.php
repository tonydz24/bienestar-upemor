<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador de Gestión de Carreras y Programas Educativos Oficiales.
 *
 * Administra el catálogo de programas académicos de la UPEMOR, incluyendo
 * su estado operativo (Activo, En Liquidación, Histórico), vinculación con
 * planes de estudio y control de dependencias para bajas lógicas o físicas.
 */
class CarreraController extends Controller
{
    /**
     * Muestra el listado de programas educativos con métricas de adscripción.
     *
     * @param Request $request Petición HTTP con posibles filtros por pestaña ('oficiales' o 'historicos').
     * @return View Vista con la colección paginada o listada de programas educativos.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'oficiales');

        // Conteo institucional de carreras oficiales vigentes
        $totalOficiales = ProgramaEducativo::where('activo', true)
            ->where(function ($q) {
                $q->whereNull('estado_operativo')->orWhere('estado_operativo', ProgramaEducativo::OPERATIVO_ACTIVO);
            })->count();

        // Conteo de carreras en liquidación o históricas
        $totalHistoricos = ProgramaEducativo::where(function ($q) {
            $q->where('activo', false)
              ->orWhereIn('estado_operativo', [ProgramaEducativo::OPERATIVO_LIQUIDACION, ProgramaEducativo::OPERATIVO_INACTIVO]);
        })->count();

        $query = ProgramaEducativo::withCount(['estudiantes', 'asignaturas', 'planes', 'grupos'])
            ->with(['planVigente']);

        if ($tab === 'historicos') {
            $query->where(function ($q) {
                $q->where('activo', false)
                  ->orWhereIn('estado_operativo', [ProgramaEducativo::OPERATIVO_LIQUIDACION, ProgramaEducativo::OPERATIVO_INACTIVO]);
            });
        } else {
            // 6 Programas oficiales activos por defecto
            $query->where('activo', true)
                ->where(function ($q) {
                    $q->whereNull('estado_operativo')->orWhere('estado_operativo', ProgramaEducativo::OPERATIVO_ACTIVO);
                });
        }

        $carreras = $query->orderBy('nombre')->get();

        return view('admin.carreras.index', compact('carreras', 'tab', 'totalOficiales', 'totalHistoricos'));
    }

    /**
     * Muestra el formulario para registrar un nuevo programa educativo.
     *
     * @return View Formulario de captura de datos de la carrera.
     */
    public function create(): View
    {
        return view('admin.carreras.create');
    }

    /**
     * Almacena un nuevo programa educativo y genera su plan de estudio inicial.
     *
     * @param Request $request Petición HTTP con datos validados de la carrera.
     * @return RedirectResponse Redirección a la gestión de planes de estudio del programa.
     * @throws \Illuminate\Validation\ValidationException Si los datos no superan las reglas.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clave' => 'required|string|max:20|unique:programas_educativos,clave',
            'nombre' => 'required|string|max:150',
            'clave_historica' => 'nullable|string|max:20',
            'nombre_historico' => 'nullable|string|max:150',
            'descripcion' => 'nullable|string|max:1000',
            'estado_operativo' => 'nullable|in:ACTIVO,EN_LIQUIDACION,INACTIVO',
            'activo' => 'nullable|boolean',
        ]);

        $estadoOperativo = $validated['estado_operativo'] ?? ($request->boolean('activo', true) ? 'ACTIVO' : 'INACTIVO');
        $esActivo = ($estadoOperativo === 'ACTIVO' || $estadoOperativo === 'EN_LIQUIDACION');

        $carrera = ProgramaEducativo::create([
            'clave' => strtoupper(trim($validated['clave'])),
            'nombre' => trim($validated['nombre']),
            'clave_historica' => !empty($validated['clave_historica']) ? strtoupper(trim($validated['clave_historica'])) : null,
            'nombre_historico' => !empty($validated['nombre_historico']) ? trim($validated['nombre_historico']) : null,
            'descripcion' => $validated['descripcion'] ?? null,
            'estado_operativo' => $estadoOperativo,
            'activo' => $esActivo,
            'archivado' => ($estadoOperativo === 'INACTIVO'),
        ]);

        // Crear automáticamente la primera versión borrador del plan de estudios institucional
        PlanEstudio::create([
            'programa_id' => $carrera->programa_id,
            'codigo' => $carrera->clave . '-' . date('Y'),
            'nombre' => 'Plan Inicial ' . $carrera->clave,
            'version' => '1.0',
            'anio_aprobacion' => (int) date('Y'),
            'vigencia_inicio' => now()->toDateString(),
            'estado' => PlanEstudio::ESTADO_VIGENTE,
            'es_vigente' => true,
            'total_cuatrimestres' => 10,
            'descripcion' => 'Plan de estudios inicial creado por administración.',
        ]);

        return redirect()->route('admin.planes.index', $carrera->programa_id)
            ->with('success', "Programa educativo '{$carrera->nombre}' registrado correctamente. Ahora puedes agregar las asignaturas de sus 10 cuatrimestres.");
    }

    /**
     * Muestra el formulario para editar un programa educativo existente.
     *
     * @param int $carreraId Identificador primario del programa educativo.
     * @return View Formulario de edición con los datos del programa.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la carrera no existe.
     */
    public function edit(int $carreraId): View
    {
        $carrera = ProgramaEducativo::with(['planes'])->findOrFail($carreraId);

        return view('admin.carreras.edit', compact('carrera'));
    }

    /**
     * Actualiza la información y estado de un programa educativo.
     *
     * @param Request $request Petición HTTP con los campos a actualizar.
     * @param int $carreraId Identificador del programa educativo.
     * @return RedirectResponse Redirección al índice con confirmación de éxito.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el modelo no existe.
     * @throws \Illuminate\Validation\ValidationException Si la validación falla.
     */
    public function update(Request $request, int $carreraId): RedirectResponse
    {
        $carrera = ProgramaEducativo::findOrFail($carreraId);

        $validated = $request->validate([
            'clave' => ['required', 'string', 'max:20', Rule::unique('programas_educativos', 'clave')->ignore($carrera->programa_id, 'programa_id')],
            'nombre' => 'required|string|max:150',
            'clave_historica' => 'nullable|string|max:20',
            'nombre_historico' => 'nullable|string|max:150',
            'descripcion' => 'nullable|string|max:1000',
            'estado_operativo' => 'nullable|in:ACTIVO,EN_LIQUIDACION,INACTIVO',
            'activo' => 'nullable|boolean',
            'archivado' => 'nullable|boolean',
        ]);

        $estadoOperativo = $validated['estado_operativo'] ?? ($request->boolean('activo') ? 'ACTIVO' : 'INACTIVO');
        $esActivo = ($estadoOperativo === 'ACTIVO' || $estadoOperativo === 'EN_LIQUIDACION');

        $carrera->update([
            'clave' => strtoupper(trim($validated['clave'])),
            'nombre' => trim($validated['nombre']),
            'clave_historica' => $validated['clave_historica'] ? strtoupper(trim($validated['clave_historica'])) : null,
            'nombre_historico' => $validated['nombre_historico'] ? trim($validated['nombre_historico']) : null,
            'descripcion' => $validated['descripcion'] ?? null,
            'estado_operativo' => $estadoOperativo,
            'activo' => $esActivo,
            'archivado' => $request->boolean('archivado') || ($estadoOperativo === 'INACTIVO'),
        ]);

        return redirect()->route('admin.carreras.index')
            ->with('success', "Programa educativo '{$carrera->nombre}' actualizado con éxito.");
    }

    /**
     * Elimina físicamente un programa educativo solo si no cuenta con dependencias históricas.
     *
     * Si la carrera contiene alumnos adscritos, grupos oficiales o cátedras impartidas,
     * se bloquea la eliminación física para garantizar la integridad referencial.
     *
     * @param int $carreraId Identificador del programa educativo a retirar.
     * @return RedirectResponse Redirección con mensaje de resultado.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la carrera no existe.
     */
    public function destroy(int $carreraId): RedirectResponse
    {
        $carrera = ProgramaEducativo::with(['planes.asignaturas'])->findOrFail($carreraId);

        // Bloqueo preventivo de integridad referencial
        if ($carrera->tieneDependenciasActivas()) {
            return back()->with('error', "No es posible eliminar físicamente la carrera '{$carrera->nombre}' porque cuenta con historial académico activo (alumnos adscritos, grupos oficiales o cátedras impartidas). Puedes desactivarla o marcarla como archivada para suspender nuevos ingresos sin dañar los datos históricos.");
        }

        DB::transaction(function () use ($carrera) {
            // Eliminar asignaturas y planes sin dependencias históricas
            foreach ($carrera->planes as $plan) {
                $plan->asignaturas()->delete();
            }
            $carrera->asignaturas()->delete();
            $carrera->planes()->delete();
            $carrera->delete();
        });

        return redirect()->route('admin.carreras.index')
            ->with('success', 'Programa educativo eliminado correctamente.');
    }
}
