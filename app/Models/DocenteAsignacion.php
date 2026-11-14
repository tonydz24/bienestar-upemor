<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: DocenteAsignacion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class DocenteAsignacion extends Model
{
    use HasFactory;

    protected $table = 'docente_asignaciones';
    protected $primaryKey = 'asignacion_id';

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_APROBADA = 'APROBADA';
    public const ESTADO_RECHAZADA = 'RECHAZADA';

    protected $fillable = [
        'docente_id',
        'asignatura_id',
        'grupo_id',
        'periodo_id',
        'activo',
        'modalidad_alcance',
        'estado',
        'solicitado_en',
        'motivo_solicitud',
        'resuelto_por',
        'resuelto_en',
        'motivo_resolucion',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo DocenteAsignacion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'solicitado_en' => 'datetime',
            'resuelto_en' => 'datetime',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function resolutor()
    {
        return $this->belongsTo(User::class, 'resuelto_por', 'usuario_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function inscripcionesMateria()
    {
        return $this->hasMany(EstudianteAsignaturaInscripcion::class, 'asignacion_id', 'asignacion_id');
    }    /**
     * Scope local para filtrar registros por aprobada.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeAprobada($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }    /**
     * Scope local para filtrar registros por pendiente.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopePendiente($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }    /**
     * Scope local para filtrar registros por delDocente.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeDelDocente($query, int $docenteId)
    {
        return $query->where('docente_id', $docenteId);
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
        return $query->where('activo', true);
    }    /**
     * Scope local para filtrar registros por delGrupo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeDelGrupo($query, int $grupoId)
    {
        return $query->where('grupo_id', $grupoId);
    }    /**
     * Scope local para filtrar registros por deLaAsignatura.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeDeLaAsignatura($query, int $asignaturaId)
    {
        return $query->where('asignatura_id', $asignaturaId);
    }    /**
     * Descriptor para el atributo calculado 'estadoBadge'.
     *
     * @return mixed
     */
public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado ?? self::ESTADO_APROBADA) {
            self::ESTADO_APROBADA => 'badge-estado-activo',
            self::ESTADO_PENDIENTE => 'badge-estado-pendiente',
            self::ESTADO_RECHAZADA => 'badge-estado-inactivo',
            default => 'bg-light text-muted border',
        };
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function docenteUsuario()
    {
        return $this->belongsTo(User::class, 'docente_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id', 'asignatura_id');
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
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function recompensasActivadas()
    {
        return $this->hasMany(RecompensaActivada::class, 'asignacion_id', 'asignacion_id');
    }
}

