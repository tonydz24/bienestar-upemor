<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PeriodoParcial;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Controlador de Gestión de Periodos Académicos y Parciales Reglamentarios.
 *
 * Administra el calendario escolar institucional de la UPEMOR, la creación
 * y distribución temporal de parciales (3 parciales reglamentarios), prevención
 * de solapamiento de fechas y la promoción académica de estudiantes entre periodos.
 */
class PeriodoAcademicoController extends Controller
{
    /**
     * Muestra la lista de periodos académicos ordenados cronológicamente.
     *
     * @return View Vista con el catálogo de periodos y conteo de grupos/inscripciones.
     */
    public function index(): View
    {
        $periodos = PeriodoAcademico::withCount(['grupos', 'asignaciones', 'inscripciones'])
            ->with('parciales')
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        return view('admin.periodos.index', compact('periodos'));
    }

    /**
     * Muestra el formulario para registrar un nuevo periodo académico.
     *
     * @return View Formulario de captura de periodo.
     */
    public function create(): View
    {
        return view('admin.periodos.create');
    }

    /**
     * Registra un nuevo periodo académico y crea sus 3 parciales reglamentarios.
     *
     * @param Request $request Petición HTTP con clave, nombre, fecha_inicio, fecha_fin y activo.
     * @return RedirectResponse Redirección al índice o formulario con errores de solapamiento.
     * @throws \Illuminate\Validation\ValidationException Si los datos no superan las reglas.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'clave' => 'required|string|max:20|unique:periodos_academicos,clave',
            'nombre' => 'required|string|max:100',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'nullable|boolean',
        ]);

        $esActivo = $request->boolean('activo', true);
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Validar prevención de solapamiento con otro periodo activo
        if ($esActivo) {
            $solapado = PeriodoAcademico::where('activo', true)
                ->where(function ($q) use ($fechaInicio, $fechaFin) {
                    $q->where('fecha_inicio', '<=', $fechaFin)
                      ->where('fecha_fin', '>=', $fechaInicio);
                })
                ->first();

            if ($solapado) {
                return back()
                    ->withInput()
                    ->withErrors(['fecha_inicio' => "Las fechas seleccionadas entran en conflicto con el periodo '{$solapado->clave}' ({$solapado->nombre}), que abarca del {$solapado->fecha_inicio?->format('d/m/Y')} al {$solapado->fecha_fin?->format('d/m/Y')}."]);
            }
        }

        $periodo = DB::transaction(function () use ($request, $esActivo) {
            $nuevo = PeriodoAcademico::create([
                'clave' => strtoupper(trim($request->input('clave'))),
                'nombre' => trim($request->input('nombre')),
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_fin' => $request->input('fecha_fin'),
                'activo' => $esActivo,
            ]);

            // Crear automáticamente los 3 parciales reglamentarios dividiendo equitativamente los días
            $inicio = Carbon::parse($nuevo->fecha_inicio);
            $fin = Carbon::parse($nuevo->fecha_fin);
            $duracionDias = $inicio->diffInDays($fin);
            $tercio = (int) ($duracionDias / 3);

            PeriodoParcial::create([
                'periodo_id' => $nuevo->periodo_id,
                'numero_parcial' => 1,
                'nombre' => 'Primer Parcial',
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $inicio->copy()->addDays($tercio)->toDateString(),
                'activo' => true,
            ]);

            PeriodoParcial::create([
                'periodo_id' => $nuevo->periodo_id,
                'numero_parcial' => 2,
                'nombre' => 'Segundo Parcial',
                'fecha_inicio' => $inicio->copy()->addDays($tercio + 1)->toDateString(),
                'fecha_fin' => $inicio->copy()->addDays($tercio * 2)->toDateString(),
                'activo' => false,
            ]);

            PeriodoParcial::create([
                'periodo_id' => $nuevo->periodo_id,
                'numero_parcial' => 3,
                'nombre' => 'Tercer Parcial',
                'fecha_inicio' => $inicio->copy()->addDays(($tercio * 2) + 1)->toDateString(),
                'fecha_fin' => $fin->toDateString(),
                'activo' => false,
            ]);

            return $nuevo;
        });

        return redirect()->route('admin.periodos.index')
            ->with('success', "Periodo académico '{$periodo->clave}' y sus 3 parciales creados exitosamente.");
    }

    /**
     * Muestra el formulario para editar un periodo académico y sus parciales.
     *
     * @param int $periodoId Identificador primario del periodo.
     * @return View Formulario de edición con la relación de parciales cargada.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el periodo no existe.
     */
    public function edit(int $periodoId): View
    {
        $periodo = PeriodoAcademico::with('parciales')->findOrFail($periodoId);

        return view('admin.periodos.edit', compact('periodo'));
    }

    /**
     * Actualiza los datos de un periodo académico y las fechas de sus parciales.
     *
     * @param Request $request Petición HTTP con datos validados de fechas y parciales.
     * @param int $periodoId Identificador del periodo académico.
     * @return RedirectResponse Redirección al índice o con errores de validación.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el periodo no existe.
     * @throws \Illuminate\Validation\ValidationException Si las reglas de validación fallan.
     */
    public function update(Request $request, int $periodoId): RedirectResponse
    {
        $periodo = PeriodoAcademico::findOrFail($periodoId);

        $request->validate([
            'nombre' => 'required|string|max:100',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'nullable|boolean',
            'parciales' => 'nullable|array',
            'parciales.*.fecha_inicio' => 'required|date',
            'parciales.*.fecha_fin' => 'required|date|after_or_equal:parciales.*.fecha_inicio',
        ]);

        $esActivo = $request->boolean('activo');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Validar prevención de solapamiento con otro periodo activo
        if ($esActivo) {
            $solapado = PeriodoAcademico::where('activo', true)
                ->where('periodo_id', '!=', $periodo->periodo_id)
                ->where(function ($q) use ($fechaInicio, $fechaFin) {
                    $q->where('fecha_inicio', '<=', $fechaFin)
                      ->where('fecha_fin', '>=', $fechaInicio);
                })
                ->first();

            if ($solapado) {
                return back()
                    ->withInput()
                    ->withErrors(['fecha_inicio' => "Las fechas seleccionadas entran en conflicto con el periodo '{$solapado->clave}' ({$solapado->nombre}), que abarca del {$solapado->fecha_inicio?->format('d/m/Y')} al {$solapado->fecha_fin?->format('d/m/Y')}."]);
            }
        }

        DB::transaction(function () use ($periodo, $request, $esActivo) {
            $periodo->update([
                'nombre' => trim($request->input('nombre')),
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_fin' => $request->input('fecha_fin'),
                'activo' => $esActivo,
            ]);

            if ($request->has('parciales')) {
                foreach ($request->input('parciales') as $parcialId => $datos) {
                    PeriodoParcial::where('parcial_id', $parcialId)
                        ->where('periodo_id', $periodo->periodo_id)
                        ->update([
                            'fecha_inicio' => $datos['fecha_inicio'],
                            'fecha_fin' => $datos['fecha_fin'],
                            'activo' => !empty($datos['activo']),
                        ]);
                }
            }
        });

        return redirect()->route('admin.periodos.index')
            ->with('success', "Periodo académico '{$periodo->clave}' actualizado correctamente.");
    }

    /**
     * Alterna el estado activo de un periodo (archivar o activar).
     *
     * @param int $periodoId Identificador primario del periodo.
     * @return RedirectResponse Redirección con mensaje de confirmación o error de solapamiento.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el periodo no existe.
     */
    public function toggleActivo(int $periodoId): RedirectResponse
    {
        $periodo = PeriodoAcademico::findOrFail($periodoId);
        $nuevoEstado = ! $periodo->activo;

        if ($nuevoEstado) {
            $solapado = PeriodoAcademico::where('activo', true)
                ->where('periodo_id', '!=', $periodo->periodo_id)
                ->where(function ($q) use ($periodo) {
                    $q->where('fecha_inicio', '<=', $periodo->fecha_fin)
                      ->where('fecha_fin', '>=', $periodo->fecha_inicio);
                })
                ->first();

            if ($solapado) {
                return back()->with('error', "No se puede activar el periodo '{$periodo->clave}' porque entra en conflicto con el periodo activo '{$solapado->clave}' ({$solapado->nombre}).");
            }
        }

        $periodo->update(['activo' => $nuevoEstado]);
        $accion = $nuevoEstado ? 'activado' : 'archivado / desactivado';

        return back()->with('success', "El periodo '{$periodo->clave}' ha sido {$accion} correctamente.");
    }

    /**
     * Muestra la propuesta administrativa de promoción de estudiantes hacia el siguiente periodo.
     * No efectúa promoción automática; requiere validación y confirmación explícita.
     *
     * @param int $periodoId Identificador del periodo origen.
     * @return View Vista con alumnos activos elegibles y lista de periodos destino.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el periodo no existe.
     */
    public function promociones(int $periodoId): View
    {
        $periodo = PeriodoAcademico::withCount('inscripciones')->findOrFail($periodoId);

        // Estudiantes inscritos activos en el periodo actual
        $inscripcionesActivas = EstudianteInscripcion::with(['estudiante.programa', 'grupo', 'estudianteUsuario'])
            ->where('periodo_id', $periodo->periodo_id)
            ->where('estado', EstudianteInscripcion::ESTADO_ACTIVO)
            ->paginate(25);

        // Periodos posteriores potenciales
        $periodosSiguientes = PeriodoAcademico::where('periodo_id', '!=', $periodo->periodo_id)
            ->where('fecha_inicio', '>=', $periodo->fecha_fin)
            ->get();

        return view('admin.periodos.promociones', compact('periodo', 'inscripcionesActivas', 'periodosSiguientes'));
    }

    /**
     * Ejecuta la promoción de estudiantes confirmada por el administrador.
     *
     * @param Request $request Petición con el ID de periodo destino y lista de estudiantes.
     * @param int $periodoId Identificador del periodo de origen.
     * @return RedirectResponse Redirección con el total de estudiantes promovidos.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el periodo no existe.
     * @throws \Illuminate\Validation\ValidationException Si los datos no superan la validación.
     */
    public function ejecutarPromocion(Request $request, int $periodoId): RedirectResponse
    {
        $periodo = PeriodoAcademico::findOrFail($periodoId);

        $request->validate([
            'periodo_destino_id' => 'required|integer|exists:periodos_academicos,periodo_id',
            'estudiantes_ids' => 'required|array|min:1',
            'estudiantes_ids.*' => 'integer|exists:users,usuario_id',
        ]);

        $periodoDestino = PeriodoAcademico::findOrFail($request->input('periodo_destino_id'));
        $estudiantesIds = $request->input('estudiantes_ids');

        $promovidos = DB::transaction(function () use ($periodo, $periodoDestino, $estudiantesIds) {
            $contador = 0;

            foreach ($estudiantesIds as $usuarioId) {
                // Marcar inscripción previa como CONCLUIDA
                EstudianteInscripcion::where('estudiante_id', $usuarioId)
                    ->where('periodo_id', $periodo->periodo_id)
                    ->update(['estado' => EstudianteInscripcion::ESTADO_CONCLUIDO]);

                $estudiante = Estudiante::where('usuario_id', $usuarioId)->first();
                if ($estudiante && $estudiante->cuatrimestre < 10) {
                    $estudiante->increment('cuatrimestre');
                    $contador++;
                }
            }

            return $contador;
        });

        return back()->with('success', "Se procesó la promoción administrativa de {$promovidos} estudiante(s) hacia el periodo {$periodoDestino->clave}.");
    }
}
