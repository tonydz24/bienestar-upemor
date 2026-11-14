<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Eloquent institucional: EncuestaRespuesta.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EncuestaRespuesta extends Model
{
    use HasFactory;

    protected $table = 'encuestas_respuestas';
    protected $primaryKey = 'respuesta_id';

    protected $fillable = [
        'encuesta_id',
        'usuario_id',
        'cita_id',
        'fecha_respuesta',
        'ip_hash',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EncuestaRespuesta.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'fecha_respuesta' => 'datetime',
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
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function cita(): BelongsTo
    {
        return $this->belongsTo(CitaOrientacion::class, 'cita_id', 'cita_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function detalles(): HasMany
    {
        return $this->hasMany(EncuestaRespuestaDetalle::class, 'respuesta_id', 'respuesta_id');
    }
}

