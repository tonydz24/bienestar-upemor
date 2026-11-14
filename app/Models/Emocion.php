<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Eloquent institucional: Emocion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class Emocion extends Model
{
    use HasFactory;

    protected $table = 'emociones';
    protected $primaryKey = 'emocion_id';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'icono',
        'color_hex',
        'activo',
        'orden',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo Emocion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function bitacoras(): HasMany
    {
        return $this->hasMany(BitacoraEmocional::class, 'emocion_id', 'emocion_id');
    }    /**
     * Scope local para filtrar registros por activas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeActivas($query)
    {
        return $query->where('activo', true)->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Paleta oficial institucional de colores de emociones (Incremento 8C.2).
     */
    public static function colorInstitucional(string $nombre): string
    {
        return match (mb_strtolower(trim($nombre))) {
            'alegría', 'alegria' => '#f59e0b', // Ámbar / Dorado institucional
            'calma' => '#10b981',             // Verde institucional pacífico
            'ansiedad' => '#f97316',          // Naranja
            'estrés', 'estres' => '#8b5cf6',  // Violeta
            'tristeza' => '#3b82f6',          // Azul
            'enojo' => '#ef4444',             // Rojo
            default => '#6b7280',
        };
    }    /**
     * Descriptor para el atributo calculado 'colorInstitucional'.
     *
     * @return mixed
     */
public function getColorInstitucionalAttribute(): string
    {
        return self::colorInstitucional($this->nombre);
    }
}

