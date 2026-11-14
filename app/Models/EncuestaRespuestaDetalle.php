<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent institucional: EncuestaRespuestaDetalle.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EncuestaRespuestaDetalle extends Model
{
    use HasFactory;

    protected $table = 'encuestas_respuestas_detalles';
    protected $primaryKey = 'detalle_id';

    protected $fillable = [
        'respuesta_id',
        'pregunta_id',
        'valor_numerico',
        'texto_respuesta',
        'opciones_seleccionadas',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EncuestaRespuestaDetalle.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'valor_numerico' => 'float',
            'opciones_seleccionadas' => 'array',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function respuesta(): BelongsTo
    {
        return $this->belongsTo(EncuestaRespuesta::class, 'respuesta_id', 'respuesta_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function pregunta(): BelongsTo
    {
        return $this->belongsTo(EncuestaPregunta::class, 'pregunta_id', 'pregunta_id');
    }
}

