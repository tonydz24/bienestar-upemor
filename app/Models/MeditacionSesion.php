<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent institucional: MeditacionSesion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class MeditacionSesion extends Model
{
    use HasFactory;

    protected $table = 'meditaciones_sesiones';
    protected $primaryKey = 'sesion_id';

    protected $fillable = [
        'meditacion_id',
        'usuario_id',
        'duracion_segundos',
        'completada',
        'fecha_sesion',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo MeditacionSesion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'duracion_segundos' => 'integer',
            'completada' => 'boolean',
            'fecha_sesion' => 'datetime',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function meditacion(): BelongsTo
    {
        return $this->belongsTo(Meditacion::class, 'meditacion_id', 'meditacion_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }
}

