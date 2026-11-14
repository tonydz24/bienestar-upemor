<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: VersionInstrumento.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class VersionInstrumento extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_versiones';
    protected $primaryKey = 'version_id';

    protected $fillable = [
        'instrumento_id',
        'version',
        'nombre_version',
        'descripcion_cambios',
        'instrucciones',
        'marco_temporal',
        'duracion_estimada_min',
        'puntos_recompensa',
        'estado',
        'es_vigente',
        'vigente_desde',
        'vigente_hasta',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo VersionInstrumento.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'duracion_estimada_min' => 'integer',
            'puntos_recompensa' => 'integer',
            'es_vigente' => 'boolean',
            'vigente_desde' => 'datetime',
            'vigente_hasta' => 'datetime',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function instrumento()
    {
        return $this->belongsTo(InstrumentoEvaluacion::class, 'instrumento_id', 'instrumento_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function preguntas()
    {
        return $this->hasMany(PreguntaEvaluacion::class, 'version_id', 'version_id')->orderBy('orden');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function preguntasActivas()
    {
        return $this->hasMany(PreguntaEvaluacion::class, 'version_id', 'version_id')
            ->where('activo', true)
            ->orderBy('orden');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function escalas()
    {
        return $this->hasMany(EscalaOpcion::class, 'version_id', 'version_id')->orderBy('orden');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function baremos()
    {
        return $this->hasMany(BaremoRegla::class, 'version_id', 'version_id')->orderBy('orden');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function intentos()
    {
        return $this->hasMany(EvaluacionIntento::class, 'version_id', 'version_id');
    }    /**
     * Scope local para filtrar registros por vigentes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeVigentes($query)
    {
        return $query->where('es_vigente', true)->where('estado', 'PUBLICADA');
    }    /**
     * Inicializa eventos y escuchadores del ciclo de vida del modelo VersionInstrumento.
     *
     * @return void
     */
protected static function booted(): void
    {
        // Regla de inmutabilidad: Una versión PUBLICADA no permite modificar su contenido sustantivo
        static::updating(function (VersionInstrumento $version) {
            if ($version->getOriginal('estado') === 'PUBLICADA') {
                $dirty = array_keys($version->getDirty());
                $allowedChanges = ['estado', 'es_vigente', 'vigente_hasta', 'updated_at'];
                $invalidChanges = array_diff($dirty, $allowedChanges);

                if (! empty($invalidChanges)) {
                    throw new \DomainException('Una versión publicada es inmutable. Para modificar instrucciones o parámetros, crea una nueva versión.');
                }
            }
        });

        static::deleting(function (VersionInstrumento $version) {
            if ($version->estado === 'PUBLICADA') {
                throw new \DomainException('No se puede eliminar una versión publicada del instrumento.');
            }
        });
    }
}

