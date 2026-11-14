<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: PlanEstudio.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class PlanEstudio extends Model
{
    use HasFactory;

    protected $table = 'planes_estudio';
    protected $primaryKey = 'plan_id';

    public const ESTADO_BORRADOR = 'BORRADOR';
    public const ESTADO_VIGENTE = 'VIGENTE';
    public const ESTADO_HISTORICO = 'HISTORICO';
    public const ESTADO_ARCHIVADO = 'ARCHIVADO';

    protected $fillable = [
        'programa_id',
        'codigo',
        'nombre',
        'version',
        'anio_aprobacion',
        'vigencia_inicio',
        'vigencia_fin',
        'estado',
        'es_vigente',
        'total_cuatrimestres',
        'descripcion',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo PlanEstudio.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'es_vigente' => 'boolean',
            'anio_aprobacion' => 'integer',
            'total_cuatrimestres' => 'integer',
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
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
public function asignaturas()
    {
        return $this->hasMany(Asignatura::class, 'plan_id', 'plan_id')->orderBy('cuatrimestre')->orderBy('orden');
    }    /**
     * Scope local para filtrar registros por vigente.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeVigente($query)
    {
        return $query->where('es_vigente', true)->where('estado', self::ESTADO_VIGENTE);
    }    /**
     * Scope local para filtrar registros por porPrograma.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopePorPrograma($query, int $programaId)
    {
        return $query->where('programa_id', $programaId);
    }

    /**
     * Comprueba si existen alumnos inscritos en asignaturas vinculadas a este plan.
     */
    public function tieneEstudiantesInscritos(): bool
    {
        $asignaturasIds = $this->asignaturas()->pluck('asignatura_id');

        return DocenteAsignacion::whereIn('asignatura_id', $asignaturasIds)
            ->whereHas('grupo.inscripciones')
            ->exists();
    }
}

