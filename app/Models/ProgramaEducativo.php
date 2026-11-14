<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: ProgramaEducativo.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class ProgramaEducativo extends Model
{
    use HasFactory;

    protected $table = 'programas_educativos';
    protected $primaryKey = 'programa_id';

    public const OPERATIVO_ACTIVO = 'ACTIVO';
    public const OPERATIVO_LIQUIDACION = 'EN_LIQUIDACION';
    public const OPERATIVO_INACTIVO = 'INACTIVO';

    protected $fillable = [
        'clave',
        'clave_historica',
        'nombre',
        'nombre_historico',
        'descripcion',
        'estado_operativo',
        'activo',
        'archivado',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo ProgramaEducativo.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'archivado' => 'boolean',
        ];
    }    /**
     * Descriptor para el atributo calculado 'siglas'.
     *
     * @return mixed
     */
public function getSiglasAttribute(): string
    {
        return $this->clave ?? '';
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function planes()
    {
        return $this->hasMany(PlanEstudio::class, 'programa_id', 'programa_id');
    }    /**
     * Relación de uno a uno hacia el modelo asociado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
public function planVigente()
    {
        return $this->hasOne(PlanEstudio::class, 'programa_id', 'programa_id')
            ->where('es_vigente', true)
            ->where('estado', PlanEstudio::ESTADO_VIGENTE);
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function asignaturas()
    {
        return $this->hasMany(Asignatura::class, 'programa_id', 'programa_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function grupos()
    {
        return $this->hasMany(Grupo::class, 'programa_id', 'programa_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'programa_id', 'programa_id');
    }    /**
     * Scope local para filtrar registros por activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('archivado', false);
    }    /**
     * Scope local para filtrar registros por noArchivados.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeNoArchivados($query)
    {
        return $query->where('archivado', false);
    }

    /**
     * Nombre formal formateado con su sigla vigente para selector institucional.
     * Ejemplo: "Ingeniería en Tecnologías de la Información e Innovación Digital (IID)"
     */
    public function getNombreConSiglaAttribute(): string
    {
        return "{$this->nombre} ({$this->clave})";
    }

    /**
     * Verifica si el programa cuenta con estudiantes, asignaturas o grupos vinculados,
     * o planes con asignaturas cargadas antes de permitir una acción destructiva.
     */
    public function tieneDependenciasActivas(): bool
    {
        if ($this->estudiantes()->exists() || $this->grupos()->exists()) {
            return true;
        }

        $asignaturasIds = $this->asignaturas()->pluck('asignatura_id');

        if ($asignaturasIds->isNotEmpty()) {
            // Comprobar si hay cátedras docentes asignadas
            if (\App\Models\DocenteAsignacion::whereIn('asignatura_id', $asignaturasIds)->exists()) {
                return true;
            }

            // Comprobar si hay inscripciones estudiantiles
            if (\Illuminate\Support\Facades\Schema::hasTable('estudiante_asignatura_inscripciones')
                && \App\Models\EstudianteAsignaturaInscripcion::whereIn('asignatura_id', $asignaturasIds)->exists()) {
                return true;
            }
        }

        return false;
    }
}
