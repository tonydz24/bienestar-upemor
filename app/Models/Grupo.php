<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: Grupo.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';
    protected $primaryKey = 'grupo_id';

    protected $fillable = [
        'programa_id',
        'periodo_id',
        'nombre',
        'cuatrimestre',
        'turno',
        'activo',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo Grupo.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'cuatrimestre' => 'integer',
            'activo' => 'boolean',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function programa()
    {
        return $this->belongsTo(ProgramaEducativo::class, 'programa_id', 'programa_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id', 'periodo_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function asignaciones()
    {
        return $this->hasMany(DocenteAsignacion::class, 'grupo_id', 'grupo_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function inscripciones()
    {
        return $this->hasMany(EstudianteInscripcion::class, 'grupo_id', 'grupo_id');
    }    /**
     * Descriptor para el atributo calculado 'nombreCompleto'.
     *
     * @return mixed
     */
public function getNombreCompletoAttribute(): string
    {
        $progClave = $this->programa ? $this->programa->clave : 'UPEMOR';
        return "{$this->cuatrimestre}° \"{$this->nombre}\" ({$progClave})";
    }
}

