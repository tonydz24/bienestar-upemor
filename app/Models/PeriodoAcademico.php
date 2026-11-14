<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: PeriodoAcademico.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class PeriodoAcademico extends Model
{
    use HasFactory;

    const ESTADO_VIGENTE = 'VIGENTE';
    const ESTADO_CONCLUIDO = 'CONCLUIDO';
    const ESTADO_PROGRAMADO = 'PROGRAMADO';
    const ESTADO_ARCHIVADO = 'ARCHIVADO';
    const ESTADO_INACTIVO = 'INACTIVO';

    protected $table = 'periodos_academicos';
    protected $primaryKey = 'periodo_id';

    protected $fillable = [
        'clave',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo PeriodoAcademico.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activo' => 'boolean',
        ];
    }    /**
     * Descriptor para el atributo calculado 'estadoCalendario'.
     *
     * @return mixed
     */
public function getEstadoCalendarioAttribute(): string
    {
        $hoy = Carbon::today();

        if (! $this->activo) {
            if ($this->fecha_fin && $hoy->greaterThan($this->fecha_fin)) {
                return self::ESTADO_ARCHIVADO;
            }
            return self::ESTADO_INACTIVO;
        }

        if ($this->fecha_fin && $hoy->greaterThan($this->fecha_fin)) {
            return self::ESTADO_CONCLUIDO;
        }

        if ($this->fecha_inicio && $hoy->lessThan($this->fecha_inicio)) {
            return self::ESTADO_PROGRAMADO;
        }

        return self::ESTADO_VIGENTE;
    }    /**
     * Método auxiliar de entidad 'estaVigentePorCalendario'.
     *
     * @return bool
     */
public function estaVigentePorCalendario(?Carbon $fecha = null): bool
    {
        $fecha = $fecha ?? Carbon::today();
        return (bool) ($this->fecha_inicio && $this->fecha_fin && $fecha->betweenIncluded($this->fecha_inicio, $this->fecha_fin));
    }    /**
     * Descriptor para el atributo calculado 'estadoCalendarioLabel'.
     *
     * @return mixed
     */
public function getEstadoCalendarioLabelAttribute(): string
    {
        return match ($this->estado_calendario) {
            self::ESTADO_VIGENTE => 'Vigente por calendario',
            self::ESTADO_CONCLUIDO => 'Concluido por calendario',
            self::ESTADO_PROGRAMADO => 'Programado (futuro)',
            self::ESTADO_ARCHIVADO => 'Archivado',
            default => 'Inactivo',
        };
    }    /**
     * Descriptor para el atributo calculado 'estadoCalendarioBadge'.
     *
     * @return mixed
     */
public function getEstadoCalendarioBadgeAttribute(): string
    {
        return match ($this->estado_calendario) {
            self::ESTADO_VIGENTE => 'bg-success text-white',
            self::ESTADO_CONCLUIDO => 'bg-warning-subtle text-warning-emphasis border border-warning',
            self::ESTADO_PROGRAMADO => 'bg-info-subtle text-info-emphasis border border-info',
            self::ESTADO_ARCHIVADO => 'bg-secondary-subtle text-secondary-emphasis border border-secondary',
            default => 'bg-light text-muted border',
        };
    }    /**
     * Método auxiliar de entidad 'parcialActual'.
     *
     * @return ?PeriodoParcial
     */
public function parcialActual(?Carbon $fecha = null): ?PeriodoParcial
    {
        $fecha = $fecha ?? Carbon::today();
        return $this->parciales->first(function ($p) use ($fecha) {
            return $p->fecha_inicio && $p->fecha_fin && $fecha->betweenIncluded($p->fecha_inicio, $p->fecha_fin);
        });
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function grupos()
    {
        return $this->hasMany(Grupo::class, 'periodo_id', 'periodo_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function asignaciones()
    {
        return $this->hasMany(DocenteAsignacion::class, 'periodo_id', 'periodo_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function inscripciones()
    {
        return $this->hasMany(EstudianteInscripcion::class, 'periodo_id', 'periodo_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function parciales()
    {
        return $this->hasMany(PeriodoParcial::class, 'periodo_id', 'periodo_id')->orderBy('numero_parcial');
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
     * Scope para filtrar periodos académicos activos cuya vigencia por fechas abarque la fecha dada (por defecto hoy).
     */
    public function scopeVigente($query, ?Carbon $fecha = null)
    {
        $fecha = $fecha ?? Carbon::today();
        $fechaStr = $fecha->toDateString();

        return $query->where('activo', true)
            ->whereDate('fecha_inicio', '<=', $fechaStr)
            ->whereDate('fecha_fin', '>=', $fechaStr);
    }

    /**
     * Retorna el periodo académico actualmente vigente por calendario, o null si no hay ninguno.
     */
    public static function obtenerVigente(?Carbon $fecha = null): ?self
    {
        return static::query()->vigente($fecha)->first();
    }
}


