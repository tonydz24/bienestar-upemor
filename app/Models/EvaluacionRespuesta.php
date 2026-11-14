<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: EvaluacionRespuesta.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EvaluacionRespuesta extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_respuestas';
    protected $primaryKey = 'respuesta_id';

    protected $fillable = [
        'intento_id',
        'pregunta_id',
        'escala_id',
        'respuesta_texto',
        'opciones_seleccionadas',
        'puntos_obtenidos',
        'cliente_timestamp',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EvaluacionRespuesta.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'opciones_seleccionadas' => 'array',
            'puntos_obtenidos' => 'integer',
            'cliente_timestamp' => 'integer',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function intento()
    {
        return $this->belongsTo(EvaluacionIntento::class, 'intento_id', 'intento_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function pregunta()
    {
        return $this->belongsTo(PreguntaEvaluacion::class, 'pregunta_id', 'pregunta_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function escala()
    {
        return $this->belongsTo(EscalaOpcion::class, 'escala_id', 'escala_id');
    }
}

