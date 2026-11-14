<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\DocenteAsignacion;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controlador de Gestión de Planes de Estudio y Malla Curricular.
 *
 * Administra las versiones de planes de estudio por programa educativo,
 * estados de vigencia (BORRADOR, VIGENTE, HISTORICO, ARCHIVADO), captura
 * y ordenamiento de asignaturas por cuatrimestre y verificación de dependencias
 * antes de bajas físicas.
 */
class PlanEstudioController extends Controller
{
    /**
     * Muestra la lista de planes de estudio pertenecientes a un programa educativo.
     *
     * @param int $carreraId Identificador del programa educativo.
     * @return View Vista con los planes de estudio y sus asignaturas asociadas.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el programa no existe.
     */
    public function index(int $carreraId): View
    {
        $carrera = ProgramaEducativo::with(['planes.asignaturas'])->findOrFail($carreraId);

        return view('admin.planes.index', compact('carrera'));
    }

    /**
     * Muestra el formulario para crear una nueva versión de plan de estudio.
     *
     * @param int $carreraId Identificador del programa educativo.
     * @return View Formulario de registro de plan de estudio.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el programa no existe.
     */
    public function create(int $carreraId): View
    {
        $carrera = ProgramaEducativo::findOrFail($carreraId);

        return view('admin.planes.create', compact('carrera'));
    }

    /**
     * Almacena una nueva versión de plan de estudio en estado BORRADOR.
     *
     * @param Request $request Petición HTTP con metadatos del plan.
     * @param int $carreraId Identificador del programa educativo.
     * @return RedirectResponse Redirección al detalle del plan creado para capturar materias.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el programa no existe.
     * @throws \Illuminate\Validation\ValidationException Si las reglas de validación no se cumplen.
     */
    public function store(Request $request, int $carreraId): RedirectResponse
    {
        $carrera = ProgramaEducativo::findOrFail($carreraId);

        $request->validate([
            'codigo' => 'required|string|max:30',
            'nombre' => 'required|string|max:150',
            'version' => 'required|string|max:20',
            'anio_aprobacion' => 'required|integer|min:2000|max:2050',
            'vigencia_inicio' => 'nullable|date',
            'total_cuatrimestres' => 'required|integer|between:1,12',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $plan = PlanEstudio::create([
            'programa_id' => $carrera->programa_id,
            'codigo' => strtoupper(trim($request->input('codigo'))),
            'nombre' => trim($request->input('nombre')),
            'version' => trim($request->input('version')),
            'anio_aprobacion' => (int) $request->input('anio_aprobacion'),
            'vigencia_inicio' => $request->input('vigencia_inicio') ?: now()->toDateString(),
            'estado' => PlanEstudio::ESTADO_BORRADOR,
            'es_vigente' => false,
            'total_cuatrimestres' => (int) $request->input('total_cuatrimestres', 10),
            'descripcion' => $request->input('descripcion'),
        ]);

        return redirect()->route('admin.planes.show', $plan->plan_id)
            ->with('success', "Nueva versión de plan '{$plan->codigo} v{$plan->version}' creada en borrador. Agrega o ajusta las asignaturas.");
    }

    /**
     * Muestra la malla curricular completa de un plan de estudio agrupada por cuatrimestre.
     *
     * @param int $planId Identificador del plan de estudio.
     * @return View Vista con la distribución de asignaturas por cuatrimestre.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el plan no existe.
     */
    public function show(int $planId): View
    {
        $plan = PlanEstudio::with(['programa', 'asignaturas' => function ($q) {
            $q->orderBy('cuatrimestre')->orderBy('orden');
        }])->findOrFail($planId);

        $asignaturasPorCuatrimestre = $plan->asignaturas->groupBy('cuatrimestre');

        return view('admin.planes.show', compact('plan', 'asignaturasPorCuatrimestre'));
    }

    /**
     * Agrega una nueva asignatura a la malla curricular del plan.
     *
     * @param Request $request Petición con nombre, cuatrimestre, tipo, horas y créditos.
     * @param int $planId Identificador del plan de estudio.
     * @return RedirectResponse Redirección a la vista del plan con mensaje de confirmación.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el plan no existe.
     * @throws \Illuminate\Validation\ValidationException Si los datos de la asignatura fallan validación.
     */
    public function agregarMateria(Request $request, int $planId): RedirectResponse
    {
        $plan = PlanEstudio::with('programa')->findOrFail($planId);

        $request->validate([
            'cuatrimestre' => 'required|integer|between:1,' . $plan->total_cuatrimestres,
            'nombre' => 'required|string|max:150',
            'clave' => 'nullable|string|max:30',
            'tipo' => 'required|in:ORDINARIA,OPTATIVA,ESTADIA,PROYECTO_INTEGRADOR',
            'horas_semana' => 'nullable|integer|min:1|max:40',
            'creditos' => 'nullable|integer|min:1|max:20',
        ]);

        $nombre = trim($request->input('nombre'));
        $tipo = $request->input('tipo');
        $cuatrimestre = (int) $request->input('cuatrimestre');
        $esEstadia = ($tipo === Asignatura::TIPO_ESTADIA) || str_contains(mb_strtolower($nombre), 'estadía') || str_contains(mb_strtolower($nombre), 'estadia');
        $esOptativa = ($tipo === Asignatura::TIPO_OPTATIVA) || str_starts_with($nombre, 'Optativa');

        $ordenSiguiente = $plan->asignaturas()->where('cuatrimestre', $cuatrimestre)->count() + 1;

        $clave = $request->input('clave') ? strtoupper(trim($request->input('clave'))) : null;
        if (!$clave) {
            $clave = "{$plan->programa->clave}-C{$cuatrimestre}-" . str_pad((string) $ordenSiguiente, 2, '0', STR_PAD_LEFT);
        }

        Asignatura::create([
            'programa_id' => $plan->programa_id,
            'plan_id' => $plan->plan_id,
            'clave' => $clave,
            'nombre' => $nombre,
            'tipo' => $tipo,
            'es_estadia' => $esEstadia,
            'es_optativa' => $esOptativa,
            'cuatrimestre' => $cuatrimestre,
            'horas_semana' => $request->input('horas_semana'),
            'creditos' => $request->input('creditos'),
            'orden' => $ordenSiguiente,
            'activo' => true,
        ]);

        return back()->with('success', "Materia '{$nombre}' añadida al {$cuatrimestre}° cuatrimestre del plan.");
    }

    /**
     * Actualiza la información de una asignatura existente.
     *
     * @param Request $request Petición HTTP con datos actualizados de la materia.
     * @param int $asignaturaId Identificador de la asignatura.
     * @return RedirectResponse Redirección con mensaje de confirmación.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la asignatura no existe.
     * @throws \Illuminate\Validation\ValidationException Si los datos fallan validación.
     */
    public function editarMateria(Request $request, int $asignaturaId): RedirectResponse
    {
        $asignatura = Asignatura::findOrFail($asignaturaId);

        $request->validate([
            'nombre' => 'required|string|max:150',
            'cuatrimestre' => 'required|integer|between:1,12',
            'tipo' => 'required|in:ORDINARIA,OPTATIVA,ESTADIA,PROYECTO_INTEGRADOR',
            'horas_semana' => 'nullable|integer|min:1|max:40',
            'creditos' => 'nullable|integer|min:1|max:20',
            'activo' => 'nullable|boolean',
        ]);

        $tipo = $request->input('tipo');
        $nombre = trim($request->input('nombre'));

        $asignatura->update([
            'nombre' => $nombre,
            'cuatrimestre' => (int) $request->input('cuatrimestre'),
            'tipo' => $tipo,
            'es_estadia' => ($tipo === Asignatura::TIPO_ESTADIA) || str_contains(mb_strtolower($nombre), 'estadía') || str_contains(mb_strtolower($nombre), 'estadia'),
            'es_optativa' => ($tipo === Asignatura::TIPO_OPTATIVA) || str_starts_with($nombre, 'Optativa'),
            'horas_semana' => $request->input('horas_semana'),
            'creditos' => $request->input('creditos'),
            'activo' => $request->boolean('activo', true),
        ]);

        return back()->with('success', "Asignatura '{$asignatura->nombre}' actualizada.");
    }

    /**
     * Modifica el estado de vigencia del plan de estudio de manera transaccional.
     *
     * Si el plan pasa a VIGENTE, automáticamente desmarca y pasa a HISTORICO
     * cualquier otro plan que estuviera marcado como vigente en el mismo programa.
     *
     * @param Request $request Petición HTTP con el nuevo estado (BORRADOR, VIGENTE, HISTORICO, ARCHIVADO).
     * @param int $planId Identificador del plan de estudio.
     * @return RedirectResponse Redirección con mensaje de confirmación.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el plan no existe.
     * @throws \Illuminate\Validation\ValidationException Si el estado no es válido.
     */
    public function cambiarEstado(Request $request, int $planId): RedirectResponse
    {
        $plan = PlanEstudio::findOrFail($planId);

        $request->validate([
            'estado' => 'required|in:BORRADOR,VIGENTE,HISTORICO,ARCHIVADO',
        ]);

        $nuevoEstado = $request->input('estado');

        DB::transaction(function () use ($plan, $nuevoEstado) {
            if ($nuevoEstado === PlanEstudio::ESTADO_VIGENTE) {
                // Desmarcar como vigentes los otros planes de este programa
                PlanEstudio::where('programa_id', $plan->programa_id)
                    ->where('plan_id', '!=', $plan->plan_id)
                    ->where('es_vigente', true)
                    ->update([
                        'es_vigente' => false,
                        'estado' => PlanEstudio::ESTADO_HISTORICO,
                    ]);

                $plan->update([
                    'estado' => PlanEstudio::ESTADO_VIGENTE,
                    'es_vigente' => true,
                ]);
            } else {
                $plan->update([
                    'estado' => $nuevoEstado,
                    'es_vigente' => false,
                ]);
            }
        });

        return back()->with('success', "Estado del plan '{$plan->codigo} v{$plan->version}' modificado a {$nuevoEstado}.");
    }

    /**
     * Elimina un plan de estudio si no cuenta con dependencias históricas u operativas.
     *
     * @param int $planId Identificador del plan de estudio a retirar.
     * @return RedirectResponse Redirección con resultado de la operación.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el plan no existe.
     */
    public function destroy(int $planId): RedirectResponse
    {
        $plan = PlanEstudio::with(['asignaturas', 'programa'])->findOrFail($planId);

        // Verificar si las asignaturas del plan tienen asignaciones docentes o inscripciones
        $asignaturasIds = $plan->asignaturas->pluck('asignatura_id');

        $tieneAsignaciones = DocenteAsignacion::whereIn('asignatura_id', $asignaturasIds)->exists();
        $tieneInscripciones = Schema::hasTable('estudiante_asignatura_inscripciones')
            && EstudianteAsignaturaInscripcion::whereIn('asignatura_id', $asignaturasIds)->exists();

        if ($tieneAsignaciones || $tieneInscripciones) {
            return back()->with('error', "No es posible eliminar físicamente el plan '{$plan->codigo}' porque cuenta con asignaturas impartidas o inscripciones estudiantiles históricas. Para retirarlo de operación, cambia su estado a 'ARCHIVADO' o 'HISTORICO'.");
        }

        $programaId = $plan->programa_id;
        $codigoPlan = $plan->codigo;

        DB::transaction(function () use ($plan) {
            // Eliminar asignaturas que pertenezcan a este plan y no tengan dependencias
            $plan->asignaturas()->delete();
            $plan->delete();
        });

        return redirect()->route('admin.planes.index', $programaId)
            ->with('success', "El plan de estudio '{$codigoPlan}' y sus asignaturas sin historial han sido eliminados correctamente.");
    }

    /**
     * Actualiza los metadatos de un plan de estudio.
     *
     * @param Request $request Petición con nombre, versión, año de aprobación y cuatrimestres.
     * @param int $planId Identificador del plan de estudio.
     * @return RedirectResponse Redirección con mensaje de confirmación.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el plan no existe.
     * @throws \Illuminate\Validation\ValidationException Si los metadatos fallan validación.
     */
    public function update(Request $request, int $planId): RedirectResponse
    {
        $plan = PlanEstudio::findOrFail($planId);

        $request->validate([
            'nombre' => 'required|string|max:150',
            'version' => 'required|string|max:20',
            'anio_aprobacion' => 'required|integer|min:2000|max:2050',
            'total_cuatrimestres' => 'required|integer|min:1|max:15',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $plan->update([
            'nombre' => trim($request->input('nombre')),
            'version' => trim($request->input('version')),
            'anio_aprobacion' => (int) $request->input('anio_aprobacion'),
            'total_cuatrimestres' => (int) $request->input('total_cuatrimestres'),
            'descripcion' => $request->input('descripcion'),
        ]);

        return back()->with('success', "Plan de estudio '{$plan->codigo} v{$plan->version}' actualizado correctamente.");
    }

    /**
     * Elimina una asignatura de un plan si no cuenta con cátedras impartidas ni inscripciones de estudiantes.
     *
     * @param int $asignaturaId Identificador primario de la asignatura.
     * @return RedirectResponse Redirección con confirmación o error de dependencia.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si la asignatura no existe.
     */
    public function eliminarMateria(int $asignaturaId): RedirectResponse
    {
        $asignatura = Asignatura::with('plan')->findOrFail($asignaturaId);

        $tieneAsignaciones = DocenteAsignacion::where('asignatura_id', $asignaturaId)->exists();
        $tieneInscripciones = Schema::hasTable('estudiante_asignatura_inscripciones')
            && EstudianteAsignaturaInscripcion::where('asignatura_id', $asignaturaId)->exists();

        if ($tieneAsignaciones || $tieneInscripciones) {
            return back()->with('error', "No es posible eliminar la asignatura '{$asignatura->nombre}' porque cuenta con cátedras impartidas o alumnos inscritos históricamente.");
        }

        $nombre = $asignatura->nombre;
        $asignatura->delete();

        return back()->with('success', "Asignatura '{$nombre}' eliminada correctamente del plan.");
    }
}
