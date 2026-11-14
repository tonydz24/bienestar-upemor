<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: CitaProfesional.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class CitaProfesional extends Model
{
    use HasFactory;

    protected $table = 'citas_profesionales';
    protected $primaryKey = 'profesional_id';

    protected $fillable = [
        'usuario_id',
        'cargo',
        'cedula_profesional',
        'lugar_atencion',
        'modalidades_permitidas',
        'permite_citas',
        'activo',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo CitaProfesional.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'permite_citas' => 'boolean',
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
public function disponibilidades()
    {
        return $this->hasMany(CitaDisponibilidad::class, 'profesional_id', 'profesional_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function citas()
    {
        return $this->hasMany(CitaOrientacion::class, 'profesional_id', 'profesional_id');
    }    /**
     * Scope local para filtrar registros por activosParaCitas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeActivosParaCitas($query)
    {
        return $query->where('activo', true)->where('permite_citas', true);
    }    /**
     * Descriptor para el atributo calculado 'nombreCompleto'.
     *
     * @return mixed
     */
public function getNombreCompletoAttribute(): string
    {
        if ($this->usuario) {
            return trim("{$this->usuario->nombre} {$this->usuario->apellido_paterno} {$this->usuario->apellido_materno}");
        }

        return $this->cargo;
    }
}

