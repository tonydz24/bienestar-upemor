<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\EstudiantePlanHistorial;
use App\Models\PlanEstudio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrayectoriaAcademicaAdminController extends Controller
{
    /**
     * Muestra la ficha de trayectoria académica individual del estudiante.
     *
     * Carga el perfil completo, diagnóstico de plan, asignaturas cursadas/inscritas,
     * historial de periodos y catálogo clasificado de planes de la misma carrera.
     *
     * @param Request $request Petición HTTP entrante
     * @param int $usuarioId Identificador del usuario estudiante
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, int $usuarioId)
    {
        $usuario = User::with([
            'estudiante.programa',
            'estudiante.planEstudio',
            'estudiante.historialPlanes.administrador',
            'estudiante.historialPlanes.planAnterior',
            'estudiante.historialPlanes.planNuevo'
        ])->findOrFail($usuarioId);

        if (!$usuario->estudiante) {
            return redirect()->route('admin.usuarios.index')
                ->with('error', 'El usuario seleccionado no cuenta con un expediente de estudiante activo.');
        }

        $estudiante = $usuario->estudiante;
        $diagnosticoPlan = $estudiante->diagnosticarPlanEstudio();

        // 1. Asignaturas inscritas actuales e históricas
        $asignaturasInscritas = collect();
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            $asignaturasInscritas = EstudianteAsignaturaInscripcion::where('estudiante_id', $usuarioId)
                ->with([
                    'asignacion.asignatura',
                    'asignacion.periodo',
                    'asignacion.docente.user',
                    'asignacion.grupo'
                ])
                ->orderByDesc('created_at')
                ->get();
        }

        // 2. Inscripciones a periodos/grupos institucionales
        $inscripcionesPeriodos = collect();
        if (Schema::hasTable('estudiante_inscripciones')) {
            $inscripcionesPeriodos = EstudianteInscripcion::where('estudiante_id', $usuarioId)
                ->with(['periodo', 'grupo'])
                ->orderByDesc('created_at')
                ->get();
        }

        // 3. Catálogo de planes compatibles de la misma carrera para posible cambio
        $planActualAsignadoId = (int) ($estudiante->plan_estudio_asignado?->plan_id ?? $estudiante->plan_id);

        $planesCarrera = PlanEstudio::where('programa_id', $estudiante->programa_id)
            ->orderByDesc('vigencia_inicio')
            ->orderByDesc('anio_aprobacion')
            ->get()
            ->map(function (PlanEstudio $plan) use ($estudiante, $planActualAsignadoId) {
                $esActual = ((int)$plan->plan_id === $planActualAsignadoId);
                $evaluacion = $estudiante->evaluarCompatibilidadPlan($plan);

                $clasificacion = 'Plan Vigente';
                $badgeClass = 'bg-success-subtle text-success border-success-subtle';

                if ($esActual) {
                    $clasificacion = 'Plan Actual del Estudiante';
                    $badgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                } elseif (strtoupper((string)$plan->estado) === 'HISTORICO') {
                    $clasificacion = 'Plan Histórico / En Liquidación';
                    $badgeClass = 'bg-secondary-subtle text-secondary border-secondary-subtle';
                } elseif (in_array(strtoupper((string)$plan->estado), ['CERRADO', 'ARCHIVADO', 'BORRADOR'])) {
                    $clasificacion = 'Plan Cerrado / No Ofertado';
                    $badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                } elseif (!empty($evaluacion['advertencias'])) {
                    $clasificacion = 'Requiere Excepción Administrativa';
                    $badgeClass = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                } elseif ($plan->es_vigente || strtoupper((string)$plan->estado) === 'VIGENTE') {
                    $clasificacion = 'Vigente para Nuevos Ingresos';
                    $badgeClass = 'bg-info-subtle text-info-emphasis border-info-subtle';
                }

                $plan->es_actual = $esActual;
                $plan->clasificacion = $clasificacion;
                $plan->badge_class = $badgeClass;
                $plan->evaluacion_compatibilidad = $evaluacion;

                return $plan;
            });

        $planesAlternativos = $planesCarrera->filter(fn ($p) => !$p->es_actual);

        // 4. Historial de cambios de plan y auditoría
        $historialCambios = collect();
        if (Schema::hasTable('estudiante_plan_historial')) {
            $historialCambios = EstudiantePlanHistorial::where('estudiante_id', $usuarioId)
                ->with(['planAnterior', 'planNuevo', 'administrador'])
                ->orderByDesc('created_at')
                ->get();
        }

        return view('admin.estudiantes.trayectoria', [
            'usuario' => $usuario,
            'estudiante' => $estudiante,
            'diagnosticoPlan' => $diagnosticoPlan,
            'asignaturasInscritas' => $asignaturasInscritas,
            'inscripcionesPeriodos' => $inscripcionesPeriodos,
            'planesCarrera' => $planesCarrera,
            'planesAlternativos' => $planesAlternativos,
            'historialCambios' => $historialCambios,
        ]);
    }

    /**
     * Procesa la solicitud administrativa de cambio justificado de plan de estudios.
     *
     * Valida pertenencia a la misma carrera, prohíbe cambio redundante al mismo plan,
     * ejecuta la actualización atómica en base de datos y crea el registro de auditoría.
     *
     * @param Request $request Petición HTTP con plan_id, motivo y confirmación
     * @param int $usuarioId Identificador del usuario estudiante
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function cambiarPlan(Request $request, int $usuarioId)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:planes_estudio,plan_id',
            'motivo' => 'required|string|min:10|max:1000',
            'confirmacion' => 'required|accepted',
        ], [
            'plan_id.required' => 'Debe seleccionar un plan de estudios de destino.',
            'plan_id.exists' => 'El plan de estudios seleccionado no existe.',
            'motivo.required' => 'El motivo o justificación del cambio es estrictamente obligatorio para fines de auditoría.',
            'motivo.min' => 'El motivo debe contener al menos 10 caracteres con la justificación técnica.',
            'confirmacion.accepted' => 'Debe confirmar expresamente que comprende los alcances y la conservación del historial académico.',
        ]);

        $usuario = User::with('estudiante')->findOrFail($usuarioId);
        if (!$usuario->estudiante) {
            return back()->with('error', 'El estudiante seleccionado no cuenta con expediente académico activo.');
        }

        $estudiante = $usuario->estudiante;
        $planDestino = PlanEstudio::findOrFail($request->plan_id);

        // Validar compatibilidad curricular (no asignar planes de otra carrera)
        if ((int)$planDestino->programa_id !== (int)$estudiante->programa_id) {
            return back()->with('error', "Operación rechazada: El plan '{$planDestino->codigo}' no pertenece al programa educativo oficial del estudiante.");
        }

        $evaluacion = $estudiante->evaluarCompatibilidadPlan($planDestino);
        if (!$evaluacion['compatible']) {
            return back()->with('error', $evaluacion['motivo_incompatibilidad'] ?? 'El plan destino no es compatible con el programa educativo.');
        }

        // Comprobar si es el mismo plan que ya tiene cursando (explícito o resuelto)
        $planActualId = (int) ($estudiante->plan_estudio_asignado?->plan_id ?? $estudiante->plan_id);
        if ($planActualId === (int)$planDestino->plan_id) {
            return back()->with('error', "El estudiante ya se encuentra cursando actualmente el plan '{$planDestino->codigo}'. Debe seleccionar un plan alternativo de la carrera.");
        }

        // Ejecución transaccional y registro de auditoría
        DB::transaction(function () use ($estudiante, $planDestino, $request, $usuarioId, $planActualId) {
            $planAnteriorId = $planActualId ?: null;

            // Actualizar plan sin alterar inscripciones de materias
            $estudiante->forceFill([
                'plan_id' => $planDestino->plan_id,
            ])->save();

            // Registrar en la bitácora de auditoría institucional
            if (Schema::hasTable('estudiante_plan_historial')) {
                EstudiantePlanHistorial::create([
                    'estudiante_id' => $usuarioId,
                    'plan_anterior_id' => $planAnteriorId,
                    'plan_nuevo_id' => $planDestino->plan_id,
                    'admin_usuario_id' => $request->user()->usuario_id,
                    'motivo' => trim((string)$request->motivo),
                    'resultado' => 'EXITOSO',
                ]);
            }
        });

        return redirect()->route('admin.estudiantes.trayectoria', $usuarioId)
            ->with('success', "Plan de estudios actualizado correctamente a '{$planDestino->codigo} — {$planDestino->nombre}'. Se preservaron íntegramente todas las asignaturas e inscripciones previas.");
    }
}

