<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent institucional: EstudiantePlanHistorial.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EstudiantePlanHistorial extends Model
{
    use HasFactory;

    protected $table = 'estudiante_plan_historial';

    protected $fillable = [
        'estudiante_id',
        'plan_anterior_id',
        'plan_nuevo_id',
        'admin_usuario_id',
        'motivo',
        'resultado',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EstudiantePlanHistorial.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'estudiante_id' => 'integer',
            'plan_anterior_id' => 'integer',
            'plan_nuevo_id' => 'integer',
            'admin_usuario_id' => 'integer',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function planAnterior(): BelongsTo
    {
        return $this->belongsTo(PlanEstudio::class, 'plan_anterior_id', 'plan_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function planNuevo(): BelongsTo
    {
        return $this->belongsTo(PlanEstudio::class, 'plan_nuevo_id', 'plan_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function administrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_usuario_id', 'usuario_id');
    }
}

