<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Services\Academico\PeriodoOperativoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GrupoAdminController extends Controller
{
    /**
     * Muestra el catálogo y gestión de grupos académicos por periodo y programa.
     */
    public function index(Request $request): View
    {
        $periodoOperativoService = app(PeriodoOperativoService::class);
        $periodos = $periodoOperativoService->getPeriodosConsultables();
        $programas = ProgramaEducativo::where('activo', true)->orderBy('nombre')->get();

        $periodoActivo = $periodoOperativoService->resolverPeriodoOperativo();
        $periodoSeleccionadoId = $request->input('periodo_id', $periodoActivo?->periodo_id);

        $query = Grupo::with(['programa', 'periodo'])
            ->withCount(['inscripciones', 'asignaciones']);

        if ($periodoSeleccionadoId) {
            $query->where('periodo_id', $periodoSeleccionadoId);
        }

        if ($request->filled('programa_id')) {
            $query->where('programa_id', $request->input('programa_id'));
        }

        if ($request->filled('cuatrimestre')) {
            $query->where('cuatrimestre', $request->input('cuatrimestre'));
        }

        $grupos = $query->orderBy('cuatrimestre')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        // Detectar si hay estudiantes con grupos nominales que aún no tienen registro formal en la tabla grupos
        $gruposNominalesFaltantes = $this->detectarGruposNominalesFaltantes($periodoSeleccionadoId);

        return view('admin.academico.grupos.index', compact(
            'grupos',
            'periodos',
            'programas',
            'periodoSeleccionadoId',
            'periodoActivo',
            'gruposNominalesFaltantes'
        ));
    }

    /**
     * Crea un nuevo grupo académico institucional.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'programa_id' => 'required|exists:programas_educativos,programa_id',
            'periodo_id' => 'required|exists:periodos_academicos,periodo_id',
            'cuatrimestre' => 'required|integer|min:1|max:10',
            'nombre' => [
                'required',
                'string',
                'max:10',
                Rule::unique('grupos')->where(function ($query) use ($request) {
                    return $query->where('programa_id', $request->input('programa_id'))
                        ->where('periodo_id', $request->input('periodo_id'))
                        ->where('cuatrimestre', $request->input('cuatrimestre'))
                        ->where('nombre', strtoupper(trim($request->input('nombre'))));
                }),
            ],
            'turno' => 'required|in:MATUTINO,VESPERTINO',
        ], [
            'nombre.unique' => 'Ya existe un grupo con esta denominación para la misma carrera, periodo y cuatrimestre.',
            'nombre.required' => 'El identificador del grupo (ej. A, B) es obligatorio.',
        ]);

        Grupo::create([
            'programa_id' => $request->input('programa_id'),
            'periodo_id' => $request->input('periodo_id'),
            'cuatrimestre' => (int) $request->input('cuatrimestre'),
            'nombre' => strtoupper(trim($request->input('nombre'))),
            'turno' => $request->input('turno'),
            'activo' => true,
        ]);

        return redirect()->route('admin.grupos.index', ['periodo_id' => $request->input('periodo_id')])
            ->with('status', "El grupo {$request->input('cuatrimestre')}° \"{$request->input('nombre')}\" ha sido registrado exitosamente.");
    }

    /**
     * Alterna el estado activo/inactivo de un grupo.
     */
    public function toggleActivo(Grupo $grupo): RedirectResponse
    {
        $grupo->activo = !$grupo->activo;
        $grupo->save();

        $estado = $grupo->activo ? 'activado' : 'desactivado';
        return redirect()->back()
            ->with('status', "El grupo {$grupo->nombre_completo} ha sido {$estado}.");
    }

    /**
     * Reconcilia y sincroniza automáticamente los grupos nominales de los estudiantes registrados (ej. Grupo B).
     */
    public function sincronizarNominales(Request $request): RedirectResponse
    {
        $periodoId = $request->input('periodo_id') ?? app(PeriodoOperativoService::class)->resolverPeriodoOperativo()?->periodo_id;

        if (!$periodoId) {
            return redirect()->back()->with('error', 'No hay un periodo académico activo para sincronizar.');
        }

        $faltantes = $this->detectarGruposNominalesFaltantes($periodoId);
        $creados = 0;

        foreach ($faltantes as $item) {
            Grupo::firstOrCreate([
                'programa_id' => $item->programa_id,
                'periodo_id' => $periodoId,
                'cuatrimestre' => $item->cuatrimestre,
                'nombre' => $item->grupo,
            ], [
                'turno' => 'MATUTINO',
                'activo' => true,
            ]);
            $creados++;
        }

        return redirect()->back()
            ->with('status', "Se reconciliaron {$creados} grupo(s) académicos faltantes a partir de la matrícula estudiantil.");
    }

    /**
     * Detecta combinaciones de [programa_id, cuatrimestre, grupo] en la tabla de estudiantes que no existen en grupos.
     */
    protected function detectarGruposNominalesFaltantes(?int $periodoId): array
    {
        if (!$periodoId) return [];

        $estudiantesGrupos = Estudiante::select('programa_id', 'cuatrimestre', 'grupo')
            ->whereNotNull('grupo')
            ->whereNotNull('programa_id')
            ->whereNotNull('cuatrimestre')
            ->distinct()
            ->get();

        $faltantes = [];
        foreach ($estudiantesGrupos as $eg) {
            $existe = Grupo::where('periodo_id', $periodoId)
                ->where('programa_id', $eg->programa_id)
                ->where('cuatrimestre', $eg->cuatrimestre)
                ->where('nombre', $eg->grupo)
                ->exists();

            if (!$existe) {
                $faltantes[] = $eg;
            }
        }

        return $faltantes;
    }
}

