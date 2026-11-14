<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: BaremoRegla.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class BaremoRegla extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_baremos_reglas';
    protected $primaryKey = 'regla_id';

    protected $fillable = [
        'version_id',
        'puntuacion_min',
        'puntuacion_max',
        'nivel',
        'color_hex',
        'mensaje_retroalimentacion',
        'sugiere_orientacion',
        'orden',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo BaremoRegla.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'puntuacion_min' => 'integer',
            'puntuacion_max' => 'integer',
            'sugiere_orientacion' => 'boolean',
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
     * Inicializa eventos y escuchadores del ciclo de vida del modelo BaremoRegla.
     *
     * @return void
     */
protected static function booted(): void
    {
        static::updating(function (BaremoRegla $baremo) {
            $estado = VersionInstrumento::where('version_id', $baremo->version_id)->value('estado');
            if ($estado === 'PUBLICADA') {
                throw new \DomainException('No se pueden modificar baremos en una versión publicada. Genera una nueva versión.');
            }
        });

        static::deleting(function (BaremoRegla $baremo) {
            $estado = VersionInstrumento::where('version_id', $baremo->version_id)->value('estado');
            if ($estado === 'PUBLICADA') {
                throw new \DomainException('No se pueden eliminar baremos de una versión publicada. Genera una nueva versión.');
            }
        });
    }
}

