<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: Docente.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class Docente extends Model
{
    use HasFactory;

    protected $table = 'docentes';
    protected $primaryKey = 'usuario_id';
    public $incrementing = false;

    protected $fillable = [
        'usuario_id',
        'numero_empleado',
        'puesto',
        'especialidad',
        'cedula_profesional',
        'activo',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo Docente.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'activo' => 'boolean',
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
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function asignaciones()
    {
        return $this->hasMany(DocenteAsignacion::class, 'docente_id', 'usuario_id');
    }
}
