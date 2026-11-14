<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: Estudiante.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';
    protected $primaryKey = 'usuario_id';
    public $incrementing = false;

    protected $fillable = [
        'usuario_id',
        'programa_id',
        'plan_id',
        'matricula',
        'cuatrimestre',
        'grupo',
        'fecha_ingreso',
        'saldo_puntos',
        'permite_visualizacion',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo Estudiante.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
            'fecha_ingreso' => 'date',
            'cuatrimestre' => 'integer',
            'saldo_puntos' => 'integer',
            'permite_visualizacion' => 'boolean',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function planEstudio()
    {
        return $this->belongsTo(PlanEstudio::class, 'plan_id', 'plan_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function historialPlanes()
    {
        return $this->hasMany(EstudiantePlanHistorial::class, 'estudiante_id', 'usuario_id')->latest();
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function programa()
    {
        return $this->belongsTo(ProgramaEducativo::class, 'programa_id', 'programa_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function programaEducativo()
    {
        return $this->programa();
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function bitacoras()
    {
        return $this->hasMany(BitacoraEmocional::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function notify($instance)
    {
        return $this->usuario?->notify($instance);
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function evaluacionIntentos()
    {
        return $this->hasMany(EvaluacionIntento::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function citas()
    {
        return $this->hasMany(CitaOrientacion::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function inscripciones()
    {
        return $this->hasMany(EstudianteInscripcion::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function canjesRecompensas()
    {
        return $this->hasMany(RecompensaCanje::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Descriptor para el atributo calculado 'cuatrimestreGrupo'.
     *
     * @return mixed
     */
public function getCuatrimestreGrupoAttribute(): string
    {
        $cuatrimestre = $this->cuatrimestre ? "{$this->cuatrimestre}°" : 'N/A';
        $grupo = $this->grupo ? " {$this->grupo}" : '';
        return trim("{$cuatrimestre}{$grupo}");
    }    /**
     * Descriptor para el atributo calculado 'cuatrimestreGrupoCompleto'.
     *
     * @return mixed
     */
public function getCuatrimestreGrupoCompletoAttribute(): string
    {
        $cuatrimestre = $this->cuatrimestre ? "Cuatrimestre {$this->cuatrimestre}" : 'Sin cuatrimestre';
        $grupo = $this->grupo ? " - Grupo {$this->grupo}" : '';
        return "{$cuatrimestre}{$grupo}";
    }

    /**
     * Resuelve la cohorte / generación institucional esperada del estudiante.
     * En el ciclo Septiembre-Diciembre 2026:
     * - Cuatrimestre 10: Generación 2023–2026
     * - Cuatrimestre 7: Generación 2024–2027
     * - Cuatrimestre 4: Generación 2025–2028
     * - Cuatrimestre 1: Generación 2026–2029
     */
    public function getGeneracionAttribute(): string
    {
        if ($this->fecha_ingreso) {
            $year = (int) $this->fecha_ingreso->format('Y');
            return "{$year}–" . ($year + 3);
        }

        if ($this->cuatrimestre && $this->cuatrimestre >= 1) {
            $yearsElapsed = intdiv($this->cuatrimestre - 1, 3);
            $entryYear = 2026 - $yearsElapsed;
            return "{$entryYear}–" . ($entryYear + 3);
        }

        return 'Sin generación';
    }

    /**
     * Resuelve el año de cohorte/ingreso institucional del estudiante a partir de:
     * 1) Fecha explícita de ingreso.
     * 2) Prefijo de cuatro dígitos de la matrícula (ej. 2024107001 -> 2024).
     * 3) Cuatrimestre cursado actual en el ciclo institucional.
     */
    public function resolverAnioIngreso(): int
    {
        if ($this->fecha_ingreso) {
            return (int) $this->fecha_ingreso->format('Y');
        }

        if ($this->matricula && preg_match('/^(20\d{2})/', $this->matricula, $matches)) {
            return (int) $matches[1];
        }

        if ($this->cuatrimestre && $this->cuatrimestre >= 1) {
            $yearsElapsed = intdiv($this->cuatrimestre - 1, 3);
            return 2026 - $yearsElapsed;
        }

        return (int) date('Y');
    }

    /**
     * Resuelve deterministamente el plan de estudio aplicable por programa y cohorte histórica,
     * evitando la asignación indiscriminada del más reciente.
     */
    public function resolverPlanEstudio(): ?PlanEstudio
    {
        if (!$this->programa_id) {
            return null;
        }

        $anioIngreso = $this->resolverAnioIngreso();

        // 1. Buscar plan del mismo programa cuya vigencia o año de aprobación corresponda a la cohorte
        $plan = PlanEstudio::where('programa_id', $this->programa_id)
            ->where(function ($q) use ($anioIngreso) {
                $q->where('anio_aprobacion', '<=', $anioIngreso)
                  ->orWhereYear('vigencia_inicio', '<=', $anioIngreso);
            })
            ->where(function ($q) use ($anioIngreso) {
                $q->whereNull('vigencia_fin')
                  ->orWhereYear('vigencia_fin', '>=', $anioIngreso);
            })
            ->orderByDesc('anio_aprobacion')
            ->orderByDesc('vigencia_inicio')
            ->first();

        // 2. Si no hay coincidencia histórica exacta, asignar el plan vigente oficial del programa
        if (!$plan) {
            $plan = PlanEstudio::where('programa_id', $this->programa_id)
                ->where('es_vigente', true)
                ->first();
        }

        // 3. Fallback defensivo a cualquier plan disponible del programa
        if (!$plan) {
            $plan = PlanEstudio::where('programa_id', $this->programa_id)->first();
        }

        return $plan;
    }

    /**
     * Diagnostica la resolución del plan de estudios, detectando ambigüedades o ausencias.
     */
    public function diagnosticarPlanEstudio(): array
    {
        if (!$this->programa_id) {
            return [
                'plan' => null,
                'estado' => 'SIN_PROGRAMA',
                'mensaje' => 'El estudiante no tiene asignado un programa educativo.',
                'es_ambiguo' => false,
                'candidatos' => collect(),
            ];
        }

        $anioIngreso = $this->resolverAnioIngreso();

        $candidatos = PlanEstudio::where('programa_id', $this->programa_id)
            ->where(function ($q) use ($anioIngreso) {
                $q->where('anio_aprobacion', '<=', $anioIngreso)
                  ->orWhereYear('vigencia_inicio', '<=', $anioIngreso);
            })
            ->where(function ($q) use ($anioIngreso) {
                $q->whereNull('vigencia_fin')
                  ->orWhereYear('vigencia_fin', '>=', $anioIngreso);
            })
            ->get();

        if ($candidatos->count() > 1) {
            $plan = $candidatos->sortByDesc('vigencia_inicio')->sortByDesc('anio_aprobacion')->first();
            return [
                'plan' => $plan,
                'estado' => 'AMBIGUEDAD_DETECTADA',
                'mensaje' => "Se detectaron {$candidatos->count()} planes de estudio candidatos para la cohorte {$anioIngreso}. Se requiere validación administrativa.",
                'es_ambiguo' => true,
                'candidatos' => $candidatos,
            ];
        }

        if ($candidatos->count() === 1) {
            return [
                'plan' => $candidatos->first(),
                'estado' => 'RESOLUCION_AUTOMATICA',
                'mensaje' => "Plan asignado unívocamente por cohorte {$anioIngreso}.",
                'es_ambiguo' => false,
                'candidatos' => $candidatos,
            ];
        }

        $vigente = PlanEstudio::where('programa_id', $this->programa_id)->where('es_vigente', true)->first();
        if ($vigente) {
            return [
                'plan' => $vigente,
                'estado' => 'PLAN_VIGENTE_FALLBACK',
                'mensaje' => "No se encontró plan histórico para cohorte {$anioIngreso}; asignando plan vigente oficial.",
                'es_ambiguo' => false,
                'candidatos' => collect([$vigente]),
            ];
        }

        $cualquiera = PlanEstudio::where('programa_id', $this->programa_id)->first();
        if ($cualquiera) {
            return [
                'plan' => $cualquiera,
                'estado' => 'UNICO_DISPONIBLE',
                'mensaje' => 'Asignado el único plan registrado para la carrera.',
                'es_ambiguo' => false,
                'candidatos' => collect([$cualquiera]),
            ];
        }

        return [
            'plan' => null,
            'estado' => 'SIN_PLAN_APLICABLE',
            'mensaje' => 'Incidencia administrativa: La carrera no tiene ningún plan de estudios registrado.',
            'es_ambiguo' => false,
            'candidatos' => collect(),
        ];
    }

    /**
     * Evalúa la compatibilidad de un plan destino antes de autorizar un cambio de plan de estudios.
     */
    public function evaluarCompatibilidadPlan(PlanEstudio $plan): array
    {
        $advertencias = [];
        $esCompatible = true;
        $motivoIncompatibilidad = null;

        if ((int)$plan->programa_id !== (int)$this->programa_id) {
            $esCompatible = false;
            $motivoIncompatibilidad = "El plan '{$plan->codigo}' pertenece a otro programa educativo (" . ($plan->programa?->nombre ?? 'Distinto') . ").";
            return [
                'compatible' => false,
                'advertencias' => [$motivoIncompatibilidad],
                'motivo_incompatibilidad' => $motivoIncompatibilidad,
            ];
        }

        if (in_array(strtoupper((string)$plan->estado), ['BORRADOR', 'ARCHIVADO'])) {
            $advertencias[] = "El plan destino se encuentra en estado '{$plan->estado}'. Requiere justificación institucional expresa.";
        }

        $anioIngreso = $this->resolverAnioIngreso();
        $inicio = $plan->vigencia_inicio ? (int)$plan->vigencia_inicio->format('Y') : (int)$plan->anio_aprobacion;
        $fin = $plan->vigencia_fin ? (int)$plan->vigencia_fin->format('Y') : null;

        if ($inicio && $anioIngreso < $inicio) {
            $advertencias[] = "La cohorte del alumno ({$anioIngreso}) es anterior al inicio de vigencia del plan ({$inicio}).";
        }
        if ($fin && $anioIngreso > $fin) {
            $advertencias[] = "La cohorte del alumno ({$anioIngreso}) es posterior al fin de vigencia del plan ({$fin}).";
        }

        return [
            'compatible' => $esCompatible,
            'advertencias' => $advertencias,
            'motivo_incompatibilidad' => $motivoIncompatibilidad,
        ];
    }

    /**
     * Accesor para obtener el plan de estudio asignado (explícito en plan_id o resuelto deterministamente).
     */
    public function getPlanEstudioAsignadoAttribute(): ?PlanEstudio
    {
        if ($this->relationLoaded('planEstudio') && $this->planEstudio) {
            return $this->planEstudio;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('estudiantes', 'plan_id') && !empty($this->plan_id)) {
            $cargado = $this->planEstudio()->first();
            if ($cargado) {
                return $cargado;
            }
        }

        return $this->resolverPlanEstudio();
    }
}
