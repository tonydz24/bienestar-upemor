<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: RecompensaActivada.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class RecompensaActivada extends Model
{
    use HasFactory;

    protected $table = 'recompensas_activadas';
    protected $primaryKey = 'activacion_id';

    public const ESTADO_ACTIVA = 'ACTIVA';
    public const ESTADO_SUSPENDIDA = 'SUSPENDIDA';
    public const ESTADO_FINALIZADA = 'FINALIZADA';

    protected $fillable = [
        'recompensa_id',
        'asignacion_id',
        'cupo_maximo',
        'cupo_disponible',
        'costo_puntos',
        'vigencia_inicio',
        'vigencia_fin',
        'condiciones_docente',
        'estado',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo RecompensaActivada.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'cupo_maximo' => 'integer',
            'cupo_disponible' => 'integer',
            'costo_puntos' => 'integer',
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function recompensaCatalogo()
    {
        return $this->belongsTo(RecompensaCatalogo::class, 'recompensa_id', 'recompensa_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function catalogo()
    {
        return $this->recompensaCatalogo();
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function asignacion()
    {
        return $this->belongsTo(DocenteAsignacion::class, 'asignacion_id', 'asignacion_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function canjes()
    {
        return $this->hasMany(RecompensaCanje::class, 'activacion_id', 'activacion_id');
    }    /**
     * Método auxiliar de entidad 'estaVigente'.
     *
     * @return bool
     */
public function estaVigente(): bool
    {
        if ($this->estado !== self::ESTADO_ACTIVA) {
            return false;
        }

        if ($this->estaDeshabilitadaPorAdmin()) {
            return false;
        }

        $hoy = now()->toDateString();
        return $hoy >= $this->vigencia_inicio->toDateString() && $hoy <= $this->vigencia_fin->toDateString();
    }    /**
     * Método auxiliar de entidad 'estaDeshabilitadaPorAdmin'.
     *
     * @return bool
     */
public function estaDeshabilitadaPorAdmin(): bool
    {
        if ($this->relationLoaded('recompensaCatalogo')) {
            return $this->recompensaCatalogo ? !$this->recompensaCatalogo->activo : false;
        }

        $catalogo = $this->recompensaCatalogo;
        return $catalogo ? !$catalogo->activo : false;
    }    /**
     * Método auxiliar de entidad 'estaVencidaPorCalendario'.
     *
     * @return bool
     */
public function estaVencidaPorCalendario(): bool
    {
        return $this->vigencia_fin && now()->startOfDay()->greaterThan($this->vigencia_fin);
    }    /**
     * Descriptor para el atributo calculado 'estadoVisual'.
     *
     * @return mixed
     */
public function getEstadoVisualAttribute(): string
    {
        if ($this->estaDeshabilitadaPorAdmin()) {
            return 'DESACTIVADA_ADMIN';
        }
        if ($this->estaVencidaPorCalendario()) {
            return 'VENCIDA';
        }
        return $this->estado;
    }    /**
     * Descriptor para el atributo calculado 'estadoVisualBadge'.
     *
     * @return mixed
     */
public function getEstadoVisualBadgeAttribute(): string
    {
        if ($this->estaDeshabilitadaPorAdmin()) {
            return 'bg-danger-subtle text-danger border border-danger-subtle';
        }
        if ($this->estaVencidaPorCalendario()) {
            return 'bg-warning-subtle text-warning-emphasis border border-warning';
        }
        return match ($this->estado) {
            self::ESTADO_ACTIVA => 'bg-success-subtle text-success border border-success-subtle',
            self::ESTADO_SUSPENDIDA => 'bg-secondary-subtle text-secondary-emphasis border border-secondary',
            self::ESTADO_FINALIZADA => 'bg-dark-subtle text-dark border',
            default => 'bg-light text-muted border',
        };
    }    /**
     * Descriptor para el atributo calculado 'estadoVisualLabel'.
     *
     * @return mixed
     */
public function getEstadoVisualLabelAttribute(): string
    {
        if ($this->estaDeshabilitadaPorAdmin()) {
            return 'Suspendida por Administración';
        }
        if ($this->estaVencidaPorCalendario()) {
            return 'Vencida por calendario';
        }
        return match ($this->estado) {
            self::ESTADO_ACTIVA => 'Activa',
            self::ESTADO_SUSPENDIDA => 'Pausada',
            self::ESTADO_FINALIZADA => 'Finalizada',
            default => $this->estado,
        };
    }    /**
     * Método auxiliar de entidad 'tieneCupo'.
     *
     * @return bool
     */
public function tieneCupo(): bool
    {
        return $this->cupo_disponible > 0;
    }
}

