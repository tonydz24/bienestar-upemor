<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: LoginAcceso.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class LoginAcceso extends Model
{
    use HasFactory;

    protected $table = 'login_accesos';

    protected $fillable = [
        'usuario_id',
        'rol_id',
        'nombre_usuario',
        'email',
        'rol_nombre',
        'fecha_acceso',
        'ip_address',
        'user_agent',
        'exitoso',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo LoginAcceso.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'fecha_acceso' => 'datetime',
            'exitoso' => 'boolean',
        ];
    }    /**
     * Descriptor para el atributo calculado 'exitoso'.
     *
     * @return mixed
     */
public function getExitosoAttribute(): bool
    {
        return array_key_exists('exitoso', $this->attributes) && $this->attributes['exitoso'] !== null
            ? (bool) $this->attributes['exitoso']
            : true;
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'rol_id');
    }
}

