<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: InstrumentoEvaluacion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class InstrumentoEvaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_instrumentos';
    protected $primaryKey = 'instrumento_id';

    protected $fillable = [
        'codigo',
        'nombre',
        'siglas',
        'categoria',
        'descripcion',
        'autor',
        'anio_publicacion',
        'dias_espera_reaplicacion',
        'activo',
        'retirado',
        'orden',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo InstrumentoEvaluacion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'anio_publicacion' => 'integer',
            'dias_espera_reaplicacion' => 'integer',
            'activo' => 'boolean',
            'retirado' => 'boolean',
            'orden' => 'integer',
        ];
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function versiones()
    {
        return $this->hasMany(VersionInstrumento::class, 'instrumento_id', 'instrumento_id');
    }    /**
     * Relación de uno a uno hacia el modelo asociado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
public function versionVigente()
    {
        return $this->hasOne(VersionInstrumento::class, 'instrumento_id', 'instrumento_id')
            ->where('es_vigente', true)
            ->where('estado', 'PUBLICADA');
    }    /**
     * Relación de uno a uno hacia el modelo asociado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
public function versionActual()
    {
        return $this->hasOne(VersionInstrumento::class, 'instrumento_id', 'instrumento_id')
            ->orderByDesc('es_vigente')
            ->orderByDesc('version_id');
    }    /**
     * Relación hasManyThrough a través del modelo intermedio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
public function intentos()
    {
        return $this->hasManyThrough(
            EvaluacionIntento::class,
            VersionInstrumento::class,
            'instrumento_id',
            'version_id',
            'instrumento_id',
            'version_id'
        );
    }    /**
     * Scope local para filtrar registros por activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    /**
     * Calcula los días enteros restantes para que un estudiante pueda volver a contestar este instrumento (A5).
     * Retorna 0 si puede contestarlo de inmediato (dias_espera_reaplicacion = 0 o plazo cumplido).
     */
    public function diasRestantesParaUsuario(int $usuarioId): int
    {
        $diasEspera = max(0, (int) $this->dias_espera_reaplicacion);
        if ($diasEspera === 0) {
            return 0;
        }

        $ultimoIntento = $this->intentos()
            ->where('evaluaciones_intentos.usuario_id', $usuarioId)
            ->where('evaluaciones_intentos.estado', 'FINALIZADO')
            ->whereNotNull('evaluaciones_intentos.fecha_finalizacion')
            ->orderByDesc('evaluaciones_intentos.fecha_finalizacion')
            ->first();

        if (! $ultimoIntento || ! $ultimoIntento->fecha_finalizacion) {
            return 0;
        }

        $fechaDisponible = \Carbon\Carbon::parse($ultimoIntento->fecha_finalizacion)->addDays($diasEspera);
        if (now()->greaterThanOrEqualTo($fechaDisponible)) {
            return 0;
        }

        $segundosRestantes = max(0, now()->diffInSeconds($fechaDisponible, false));

        return max(1, (int) ceil($segundosRestantes / 86400));
    }
}

