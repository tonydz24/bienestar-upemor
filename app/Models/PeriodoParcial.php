<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: PeriodoParcial.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class PeriodoParcial extends Model
{
    use HasFactory;

    protected $table = 'periodo_parciales';
    protected $primaryKey = 'parcial_id';

    protected $fillable = [
        'periodo_id',
        'numero_parcial',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo PeriodoParcial.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'numero_parcial' => 'integer',
            'activo' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id', 'periodo_id');
    }
}

