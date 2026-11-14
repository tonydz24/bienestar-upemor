<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent institucional: EstudianteAsignaturaInscripcionHistorial.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EstudianteAsignaturaInscripcionHistorial extends Model
{
    use HasFactory;

    protected $table = 'estudiante_asignatura_inscripciones_historial';

    protected $fillable = [
        'inscripcion_materia_id',
        'estudiante_id',
        'asignacion_id',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'resuelto_por',
        'resuelto_en',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EstudianteAsignaturaInscripcionHistorial.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'resuelto_en' => 'datetime',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(EstudianteAsignaturaInscripcion::class, 'inscripcion_materia_id', 'inscripcion_materia_id');
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
public function asignacion(): BelongsTo
    {
        return $this->belongsTo(DocenteAsignacion::class, 'asignacion_id', 'asignacion_id');
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

