<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: CitaDisponibilidad.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class CitaDisponibilidad extends Model
{
    use HasFactory;

    protected $table = 'citas_disponibilidades';
    protected $primaryKey = 'disponibilidad_id';

    protected $fillable = [
        'profesional_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'duracion_cita_min',
        'modalidad',
        'activo',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo CitaDisponibilidad.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'duracion_cita_min' => 'integer',
            'activo' => 'boolean',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function profesional()
    {
        return $this->belongsTo(CitaProfesional::class, 'profesional_id', 'profesional_id');
    }    /**
     * Descriptor para el atributo calculado 'nombreDia'.
     *
     * @return mixed
     */
public function getNombreDiaAttribute(): string
    {
        return match ($this->dia_semana) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
            default => 'Día desconocido',
        };
    }
}

