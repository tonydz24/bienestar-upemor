<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: PreguntaEvaluacion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class PreguntaEvaluacion extends Model
{
    use HasFactory;

    public const TIPO_ESCALA = 'ESCALA';
    public const TIPO_OPCION_UNICA = 'OPCION_UNICA';
    public const TIPO_OPCION_MULTIPLE = 'OPCION_MULTIPLE';
    public const TIPO_TEXTO_LIBRE = 'TEXTO_LIBRE';

    protected $table = 'evaluaciones_preguntas';
    protected $primaryKey = 'pregunta_id';

    protected $fillable = [
        'version_id',
        'numero_reactivo',
        'enunciado',
        'tipo_pregunta',
        'es_obligatoria',
        'opciones_json',
        'descripcion_ayuda',
        'es_inverso',
        'es_critico',
        'orden',
        'activo',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo PreguntaEvaluacion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'numero_reactivo' => 'integer',
            'es_obligatoria' => 'boolean',
            'opciones_json' => 'array',
            'es_inverso' => 'boolean',
            'es_critico' => 'boolean',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }    /**
     * Descriptor para el atributo calculado 'textoPregunta'.
     *
     * @return mixed
     */
public function getTextoPreguntaAttribute(): string
    {
        return (string) ($this->enunciado ?? '');
    }    /**
     * Método auxiliar de entidad 'tiposCatalogo'.
     *
     * @return array
     */
public static function tiposCatalogo(): array
    {
        return [
            self::TIPO_ESCALA => 'Escala de respuesta (Likert / Global)',
            self::TIPO_OPCION_UNICA => 'Opción única (Radio)',
            self::TIPO_OPCION_MULTIPLE => 'Selección múltiple (Casillas)',
            self::TIPO_TEXTO_LIBRE => 'Respuesta abierta (Texto libre — sin puntaje clínico)',
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
        return $this->hasMany(EvaluacionRespuesta::class, 'pregunta_id', 'pregunta_id');
    }    /**
     * Inicializa eventos y escuchadores del ciclo de vida del modelo PreguntaEvaluacion.
     *
     * @return void
     */
protected static function booted(): void
    {
        static::updating(function (PreguntaEvaluacion $pregunta) {
            $estado = VersionInstrumento::where('version_id', $pregunta->version_id)->value('estado');
            if ($estado === 'PUBLICADA') {
                throw new \DomainException('No se pueden modificar preguntas en una versión publicada. Genera una nueva versión.');
            }
        });

        static::deleting(function (PreguntaEvaluacion $pregunta) {
            $estado = VersionInstrumento::where('version_id', $pregunta->version_id)->value('estado');
            if ($estado === 'PUBLICADA') {
                throw new \DomainException('No se pueden eliminar preguntas de una versión publicada. Genera una nueva versión.');
            }
        });
    }
}

