<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: EscalaOpcion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EscalaOpcion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_escalas_opciones';
    protected $primaryKey = 'escala_id';

    protected $fillable = [
        'version_id',
        'valor',
        'etiqueta',
        'descripcion',
        'orden',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EscalaOpcion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'valor' => 'integer',
            'orden' => 'integer',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function version()
    {
        return $this->belongsTo(VersionInstrumento::class, 'version_id', 'version_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function respuestas()
    {
        return $this->hasMany(EvaluacionRespuesta::class, 'escala_id', 'escala_id');
    }    /**
     * Inicializa eventos y escuchadores del ciclo de vida del modelo EscalaOpcion.
     *
     * @return void
     */
protected static function booted(): void
    {
        static::updating(function (EscalaOpcion $escala) {
            $estado = VersionInstrumento::where('version_id', $escala->version_id)->value('estado');
            if ($estado === 'PUBLICADA') {
                throw new \DomainException('No se pueden modificar opciones de escala en una versión publicada. Genera una nueva versión.');
            }
        });

        static::deleting(function (EscalaOpcion $escala) {
            $estado = VersionInstrumento::where('version_id', $escala->version_id)->value('estado');
            if ($estado === 'PUBLICADA') {
                throw new \DomainException('No se pueden eliminar opciones de escala de una versión publicada. Genera una nueva versión.');
            }
        });
    }
}

