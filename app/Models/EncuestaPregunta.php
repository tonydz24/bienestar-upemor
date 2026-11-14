<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Eloquent institucional: EncuestaPregunta.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EncuestaPregunta extends Model
{
    use HasFactory;

    protected $table = 'encuestas_preguntas';
    protected $primaryKey = 'pregunta_id';

    public const TIPO_LIKERT = 'LIKERT';
    public const TIPO_OPCION_UNICA = 'OPCION_UNICA';
    public const TIPO_OPCION_MULTIPLE = 'OPCION_MULTIPLE';
    public const TIPO_VALORACION = 'VALORACION';
    public const TIPO_TEXTO_LIBRE = 'TEXTO_LIBRE';

    protected $fillable = [
        'encuesta_id',
        'orden',
        'texto_pregunta',
        'tipo_pregunta',
        'es_obligatoria',
        'opciones',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EncuestaPregunta.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'es_obligatoria' => 'boolean',
            'opciones' => 'array',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function encuesta(): BelongsTo
    {
        return $this->belongsTo(Encuesta::class, 'encuesta_id', 'encuesta_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function detalles(): HasMany
    {
        return $this->hasMany(EncuestaRespuestaDetalle::class, 'pregunta_id', 'pregunta_id');
    }    /**
     * Método auxiliar de entidad 'opcionesLikertEstandar'.
     *
     * @return array
     */
public static function opcionesLikertEstandar(): array
    {
        return [
            ['valor' => 1, 'etiqueta' => 'Totalmente en desacuerdo'],
            ['valor' => 2, 'etiqueta' => 'En desacuerdo'],
            ['valor' => 3, 'etiqueta' => 'Neutral / Indiferente'],
            ['valor' => 4, 'etiqueta' => 'De acuerdo'],
            ['valor' => 5, 'etiqueta' => 'Totalmente de acuerdo'],
        ];
    }
}

