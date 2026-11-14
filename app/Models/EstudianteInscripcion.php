<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: EstudianteInscripcion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EstudianteInscripcion extends Model
{
    use HasFactory;

    protected $table = 'estudiante_inscripciones';
    protected $primaryKey = 'inscripcion_id';

    public const ESTADO_ACTIVO = 'ACTIVO';
    public const ESTADO_BAJA = 'BAJA';
    public const ESTADO_CONCLUIDO = 'CONCLUIDO';

    protected $fillable = [
        'estudiante_id',
        'grupo_id',
        'periodo_id',
        'estado',
    ];    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudianteUsuario()
    {
        return $this->belongsTo(User::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id', 'grupo_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id', 'periodo_id');
    }    /**
     * Scope local para filtrar registros por delEstudiante.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeDelEstudiante($query, int $estudianteId)
    {
        return $query->where('estudiante_id', $estudianteId);
    }    /**
     * Scope local para filtrar registros por delPeriodo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeDelPeriodo($query, int $periodoId)
    {
        return $query->where('periodo_id', $periodoId);
    }    /**
     * Scope local para filtrar registros por activo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeActivo($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }    /**
     * Scope local para filtrar registros por delGrupo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeDelGrupo($query, int $grupoId)
    {
        return $query->where('grupo_id', $grupoId);
    }
}

