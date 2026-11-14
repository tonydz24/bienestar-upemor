<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: Asignatura.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class Asignatura extends Model
{
    use HasFactory;

    protected $table = 'asignaturas';
    protected $primaryKey = 'asignatura_id';

    public const TIPO_ORDINARIA = 'ORDINARIA';
    public const TIPO_OPTATIVA = 'OPTATIVA';
    public const TIPO_ESTADIA = 'ESTADIA';
    public const TIPO_PROYECTO_INTEGRADOR = 'PROYECTO_INTEGRADOR';

    protected $fillable = [
        'programa_id',
        'plan_id',
        'clave',
        'nombre',
        'tipo',
        'es_estadia',
        'es_optativa',
        'cuatrimestre',
        'horas_semana',
        'creditos',
        'orden',
        'activo',
    ];    /**
     * Casteo de tipos para los atributos de la asignatura.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'cuatrimestre' => 'integer',
            'horas_semana' => 'integer',
            'creditos' => 'integer',
            'orden' => 'integer',
            'es_estadia' => 'boolean',
            'es_optativa' => 'boolean',
            'activo' => 'boolean',
        ];
    }    /**
     * Programa educativo al que pertenece la asignatura.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function programa()
    {
        return $this->belongsTo(ProgramaEducativo::class, 'programa_id', 'programa_id');
    }    /**
     * Plan de estudio en el que está adscrita la asignatura.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function plan()
    {
        return $this->belongsTo(PlanEstudio::class, 'plan_id', 'plan_id');
    }    /**
     * Asignaciones y cátedras docentes impartidas de esta materia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function asignaciones()
    {
        return $this->hasMany(DocenteAsignacion::class, 'asignatura_id', 'asignatura_id');
    }    /**
     * Filtra asignaturas por su número de cuatrimestre.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $cuatrimestre
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopePorCuatrimestre($query, int $cuatrimestre)
    {
        return $query->where('cuatrimestre', $cuatrimestre);
    }    /**
     * Filtra asignaturas correspondientes a estancias o estadías profesionales.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeEstadias($query)
    {
        return $query->where('es_estadia', true)->orWhere('tipo', self::TIPO_ESTADIA);
    }    /**
     * Filtra asignaturas optativas o electivas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeOptativas($query)
    {
        return $query->where('es_optativa', true)->orWhere('tipo', self::TIPO_OPTATIVA);
    }    /**
     * Filtra materias ordinarias del mapa curricular.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeOrdinarias($query)
    {
        return $query->where('tipo', self::TIPO_ORDINARIA);
    }    /**
     * Determina si la asignatura tiene cátedras docentes asignadas.
     *
     * @return bool
     */
public function tieneDependenciasActivas(): bool
    {
        return $this->asignaciones()->exists();
    }
}
