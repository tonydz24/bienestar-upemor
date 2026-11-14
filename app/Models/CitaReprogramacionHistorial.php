<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent institucional: CitaReprogramacionHistorial.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class CitaReprogramacionHistorial extends Model
{
    use HasFactory;

    protected $table = 'citas_reprogramaciones_historial';

    protected $fillable = [
        'cita_id',
        'estudiante_id',
        'profesional_id',
        'fecha_original',
        'hora_inicio_original',
        'hora_fin_original',
        'fecha_propuesta',
        'hora_inicio_propuesta',
        'hora_fin_propuesta',
        'motivo_solicitud',
        'estado',
        'motivo_respuesta',
        'resuelto_por',
        'fecha_resolucion',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo CitaReprogramacionHistorial.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'fecha_original' => 'date',
            'fecha_propuesta' => 'date',
            'fecha_resolucion' => 'datetime',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function cita(): BelongsTo
    {
        return $this->belongsTo(CitaOrientacion::class, 'cita_id', 'cita_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function profesional(): BelongsTo
    {
        return $this->belongsTo(CitaProfesional::class, 'profesional_id', 'profesional_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por', 'usuario_id');
    }
}

