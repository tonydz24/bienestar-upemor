<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: EstudianteAsignaturaInscripcion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EstudianteAsignaturaInscripcion extends Model
{
    use HasFactory;

    protected $table = 'estudiante_asignatura_inscripciones';
    protected $primaryKey = 'inscripcion_materia_id';

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_APROBADA = 'APROBADA';
    public const ESTADO_RECHAZADA = 'RECHAZADA';
    public const ESTADO_CANCELADA = 'CANCELADA';

    protected $fillable = [
        'estudiante_id',
        'asignacion_id',
        'grupo_id',
        'asignatura_id',
        'periodo_id',
        'estado',
        'solicitado_en',
        'motivo_solicitud',
        'resuelto_por',
        'resuelto_en',
        'motivo_resolucion',
        'historial_transiciones',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EstudianteAsignaturaInscripcion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'solicitado_en' => 'datetime',
            'resuelto_en' => 'datetime',
            'historial_transiciones' => 'array',
        ];
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function historial()
    {
        return $this->hasMany(EstudianteAsignaturaInscripcionHistorial::class, 'inscripcion_materia_id', 'inscripcion_materia_id')->orderByDesc('id');
    }    /**
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
public function asignacion()
    {
        return $this->belongsTo(DocenteAsignacion::class, 'asignacion_id', 'asignacion_id');
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
public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id', 'asignatura_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id', 'periodo_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function resolutor()
    {
        return $this->belongsTo(User::class, 'resuelto_por', 'usuario_id');
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
     * Scope local para filtrar registros por aprobada.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeAprobada($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }    /**
     * Descriptor para el atributo calculado 'estadoBadge'.
     *
     * @return mixed
     */
public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_APROBADA => 'badge-estado-activo',
            self::ESTADO_PENDIENTE => 'badge-estado-pendiente',
            self::ESTADO_RECHAZADA => 'badge-estado-inactivo',
            self::ESTADO_CANCELADA => 'bg-secondary-subtle text-secondary border',
            default => 'bg-light text-muted border',
        };
    }
}

