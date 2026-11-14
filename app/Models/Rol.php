<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: Rol.
 *
 * Representa los roles de acceso del sistema (Administrador, Docente, Alumno, Orientador).
 */
class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';
    protected $primaryKey = 'rol_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    /**
     * Define los casteos de tipos nativos para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Relación de uno a muchos: Usuarios que poseen este rol principal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'rol_id', 'rol_id');
    }
}
