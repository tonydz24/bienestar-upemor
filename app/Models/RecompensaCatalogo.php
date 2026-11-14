<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: RecompensaCatalogo.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class RecompensaCatalogo extends Model
{
    use HasFactory;

    protected $table = 'recompensas_catalogo';
    protected $primaryKey = 'recompensa_id';

    public const CATEGORIA_ACADEMICA = 'ACADEMICA';
    public const CATEGORIA_TIEMPO = 'TIEMPO';
    public const CATEGORIA_PARTICIPACION = 'PARTICIPACION';
    public const CATEGORIA_RECONOCIMIENTO = 'RECONOCIMIENTO';

    public const PROPUESTA_INSTITUCIONAL = 'INSTITUCIONAL';
    public const PROPUESTA_PENDIENTE = 'PENDIENTE';
    public const PROPUESTA_APROBADA = 'APROBADA';
    public const PROPUESTA_RECHAZADA = 'RECHAZADA';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'costo_puntos_base',
        'condiciones_generales',
        'docente_solicitante_id',
        'admin_resolutor_id',
        'estado_propuesta',
        'justificacion_docente',
        'motivo_resolucion_admin',
        'fecha_resolucion',
        'activo',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo RecompensaCatalogo.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'costo_puntos_base' => 'integer',
            'activo' => 'boolean',
            'fecha_resolucion' => 'datetime',
        ];
    }    /**
     * Descriptor para el atributo calculado 'titulo'.
     *
     * @return mixed
     */
public function getTituloAttribute(): string
    {
        return $this->nombre ?? '';
    }

    /**
     * Normalizar compatibilidad bidireccional de estados de propuesta
     */
    public function getEstadoPropuestaAttribute($value): string
    {
        if ($value === 'PROPUESTA_PENDIENTE') return self::PROPUESTA_PENDIENTE;
        if ($value === 'PROPUESTA_APROBADA') return self::PROPUESTA_APROBADA;
        if ($value === 'PROPUESTA_RECHAZADA') return self::PROPUESTA_RECHAZADA;
        return (string) $value;
    }    /**
     * Mutador para el atributo 'estadoPropuesta'.
     *
     * @param mixed $value Valor a procesar.
     * @return void
     */
public function setEstadoPropuestaAttribute($value): void
    {
        if ($value === 'PROPUESTA_PENDIENTE') $value = self::PROPUESTA_PENDIENTE;
        elseif ($value === 'PROPUESTA_APROBADA') $value = self::PROPUESTA_APROBADA;
        elseif ($value === 'PROPUESTA_RECHAZADA') $value = self::PROPUESTA_RECHAZADA;
        $this->attributes['estado_propuesta'] = $value;
    }    /**
     * Método auxiliar de entidad 'esPropuestaPendiente'.
     *
     * @return bool
     */
public function esPropuestaPendiente(): bool
    {
        return $this->estado_propuesta === self::PROPUESTA_PENDIENTE;
    }    /**
     * Método auxiliar de entidad 'esPropuestaAprobada'.
     *
     * @return bool
     */
public function esPropuestaAprobada(): bool
    {
        return $this->estado_propuesta === self::PROPUESTA_APROBADA;
    }    /**
     * Método auxiliar de entidad 'esPropuestaRechazada'.
     *
     * @return bool
     */
public function esPropuestaRechazada(): bool
    {
        return $this->estado_propuesta === self::PROPUESTA_RECHAZADA;
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function esInstitucional(): bool
    {
        return $this->estado_propuesta === self::PROPUESTA_INSTITUCIONAL;
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function activaciones()
    {
        return $this->hasMany(RecompensaActivada::class, 'recompensa_id', 'recompensa_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function docenteSolicitante()
    {
        return $this->belongsTo(User::class, 'docente_solicitante_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function adminResolutor()
    {
        return $this->belongsTo(User::class, 'admin_resolutor_id', 'usuario_id');
    }    /**
     * Scope local para filtrar registros por activo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Recompensas que este docente en particular puede activar:
     * - Las institucionales generales (estado_propuesta = INSTITUCIONAL y docente_solicitante_id IS NULL)
     * - O aquellas propuestas por él mismo que hayan sido expresamente APROBADAS por el administrador.
     */
    public function scopeDisponiblesParaDocente($query, int $docenteId)
    {
        return $query->where('activo', true)
            ->where(function ($q) use ($docenteId) {
                $q->where(function ($sub) {
                    $sub->whereNull('docente_solicitante_id')
                        ->whereIn('estado_propuesta', [self::PROPUESTA_INSTITUCIONAL, 'INSTITUCIONAL']);
                })->orWhere(function ($sub) use ($docenteId) {
                    $sub->where('docente_solicitante_id', $docenteId)
                        ->whereIn('estado_propuesta', [self::PROPUESTA_APROBADA, 'PROPUESTA_APROBADA']);
                });
            });
    }
}
