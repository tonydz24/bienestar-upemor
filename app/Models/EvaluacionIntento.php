<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: EvaluacionIntento.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EvaluacionIntento extends Model
{
    use HasFactory;

    const ESTADO_EN_PROGRESO = 'EN_PROGRESO';
    const ESTADO_FINALIZADO = 'FINALIZADO';
    const ESTADO_CANCELADO = 'CANCELADO';

    protected $table = 'evaluaciones_intentos';
    protected $primaryKey = 'intento_id';

    protected $fillable = [
        'usuario_id',
        'version_id',
        'estado',
        'puntuacion_total',
        'nivel_resultado',
        'tiene_alerta_critica',
        'fecha_inicio',
        'fecha_finalizacion',
        'ip_registro',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EvaluacionIntento.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'puntuacion_total' => 'integer',
            'tiene_alerta_critica' => 'boolean',
            'fecha_inicio' => 'datetime',
            'fecha_finalizacion' => 'datetime',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function version()
    {
        return $this->belongsTo(VersionInstrumento::class, 'version_id', 'version_id');
    }    /**
     * Relación hasOneThrough a través del modelo intermedio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
public function instrumento()
    {
        return $this->hasOneThrough(
            InstrumentoEvaluacion::class,
            VersionInstrumento::class,
            'version_id',
            'instrumento_id',
            'version_id',
            'instrumento_id'
        );
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function respuestas()
    {
        return $this->hasMany(EvaluacionRespuesta::class, 'intento_id', 'intento_id');
    }    /**
     * Relación de uno a uno hacia el modelo asociado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
public function consentimiento()
    {
        return $this->hasOne(EvaluacionConsentimiento::class, 'intento_id', 'intento_id');
    }    /**
     * Método auxiliar de entidad 'estaFinalizado'.
     *
     * @return bool
     */
public function estaFinalizado(): bool
    {
        return $this->estado === 'FINALIZADO';
    }    /**
     * Método auxiliar de entidad 'estaEnProgreso'.
     *
     * @return bool
     */
public function estaEnProgreso(): bool
    {
        return $this->estado === 'EN_PROGRESO';
    }
}

