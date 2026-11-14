<?php

namespace App\Models;

use App\Notifications\RestablecerPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo Eloquent institucional: User.
 *
 * Representa a los usuarios del sistema (Administradores, Docentes, Alumnos y Orientadores),
 * gestiona la autenticación, roles institucionales simples y múltiples, y relaciones con
 * entidades de expedientes académicos, clínicos y de acompañamiento emocional.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'usuario_id';

    protected $fillable = [
        'rol_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'password',
        'telefono',
        'estado',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casteo de tipos para los atributos del usuario institucional.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Envía la notificación de restablecimiento de contraseña con plantilla institucional.
     *
     * @param string $token Token criptográfico de restablecimiento.
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerPasswordNotification($token));
    }

    /**
     * Obtiene el nombre completo del usuario concatenando nombre y apellidos.
     *
     * @return string
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre . ' ' . ($this->apellido_paterno ?? '') . ' ' . ($this->apellido_materno ?? ''));
    }

    /**
     * Descriptor para el apellido paterno del usuario.
     *
     * @return string
     */
    public function getPrimerApellidoAttribute(): string
    {
        return (string) ($this->apellido_paterno ?? '');
    }

    /**
     * Descriptor para el apellido materno del usuario.
     *
     * @return string|null
     */
    public function getSegundoApellidoAttribute(): string
    {
        return (string) ($this->apellido_materno ?? '');
    }

    /**
     * Rol principal asignado al usuario.
     *
     * @return BelongsTo
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'rol_id');
    }

    /**
     * Perfil de estudiante asociado si el usuario es alumno.
     *
     * @return HasOne
     */
    public function estudiante(): HasOne
    {
        return $this->hasOne(Estudiante::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Perfil docente asociado si el usuario es profesor.
     *
     * @return HasOne
     */
    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Sesiones de meditación completadas por el usuario.
     *
     * @return HasMany
     */
    public function meditacionesSesiones(): HasMany
    {
        return $this->hasMany(MeditacionSesion::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Entradas de bitácora emocional registradas por el alumno.
     *
     * @return HasMany
     */
    public function bitacoras(): HasMany
    {
        return $this->hasMany(BitacoraEmocional::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Intentos y resultados de evaluaciones psicométricas del estudiante.
     *
     * @return HasMany
     */
    public function evaluacionIntentos(): HasMany
    {
        return $this->hasMany(EvaluacionIntento::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Perfil profesional de orientador o psicólogo si aplica.
     *
     * @return HasOne
     */
    public function citaProfesional(): HasOne
    {
        return $this->hasOne(CitaProfesional::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Citas de orientación psicológica solicitadas por el estudiante.
     *
     * @return HasMany
     */
    public function citasEstudiante(): HasMany
    {
        return $this->hasMany(CitaOrientacion::class, 'estudiante_id', 'usuario_id');
    }

    /**
     * Relación de roles múltiples asociados al usuario.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'usuario_roles', 'usuario_id', 'rol_id')->withTimestamps();
    }

    /**
     * Asignaciones de cátedra activas si el usuario es docente.
     *
     * @return HasMany
     */
    public function asignacionesDocente(): HasMany
    {
        return $this->hasMany(DocenteAsignacion::class, 'docente_id', 'usuario_id');
    }

    /**
     * Inscripciones académicas del estudiante en grupos y periodos.
     *
     * @return HasMany
     */
    public function inscripcionesEstudiante(): HasMany
    {
        return $this->hasMany(EstudianteInscripcion::class, 'estudiante_id', 'usuario_id');
    }

    /**
     * Canjes de recompensas solicitados por el estudiante.
     *
     * @return HasMany
     */
    public function canjesRecompensas(): HasMany
    {
        return $this->hasMany(RecompensaCanje::class, 'estudiante_id', 'usuario_id');
    }

    /**
     * Verifica si el usuario cuenta con alguno de los roles indicados.
     *
     * @param string ...$roleNames Nombres de los roles a verificar.
     * @return bool
     */
    public function hasRole(string ...$roleNames): bool
    {
        // 1. Exclusión estricta para Orientador (rol_id = 4 / nombre = Orientador): no puede acceder a rutas exclusivas de docente
        if (in_array('Docente', $roleNames, true) && !in_array('Orientador', $roleNames, true) && $this->rol?->nombre === 'Orientador') {
            return false;
        }

        // 2. Exclusión estricta para Docente puro: no puede acceder a rutas exclusivas de orientación clínica
        if (in_array('Orientador', $roleNames, true) && !in_array('Docente', $roleNames, true) && $this->rol?->nombre === 'Docente' && !$this->citaProfesional?->activo) {
            return false;
        }

        // 3. Verificar rol primario
        $rolPrimario = $this->rol?->nombre;
        if ($rolPrimario && in_array($rolPrimario, $roleNames, true)) {
            return true;
        }

        // 4. Capacidad especializada: Orientador
        if (in_array('Orientador', $roleNames, true) && $this->esOrientador()) {
            return true;
        }

        // 5. Roles secundarios asignados en la tabla pivote
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('nombre')->intersect($roleNames)->isNotEmpty();
        }

        return $this->roles()->whereIn('nombre', $roleNames)->exists();
    }

    /**
     * Comprueba si el usuario tiene al menos uno de los roles provistos en un arreglo.
     *
     * @param array<int, string> $roleNames Lista de roles.
     * @return bool
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->hasRole(...$roleNames);
    }

    /**
     * Comprueba si el usuario tiene rol de Alumno.
     *
     * @return bool
     */
    public function esAlumno(): bool
    {
        return $this->hasRole('Alumno');
    }

    /**
     * Comprueba si el usuario tiene rol de Docente.
     *
     * @return bool
     */
    public function esDocente(): bool
    {
        return $this->hasRole('Docente');
    }

    /**
     * Comprueba si el usuario tiene rol de Administrador.
     *
     * @return bool
     */
    public function esAdministrador(): bool
    {
        return $this->hasRole('Administrador');
    }

    /**
     * Comprueba si el usuario tiene rol o perfil activo de Orientador.
     *
     * @return bool
     */
    public function esOrientador(): bool
    {
        if ($this->rol?->nombre === 'Orientador') {
            return true;
        }

        if ($this->citaProfesional !== null && $this->citaProfesional->activo) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('nombre', 'Orientador');
        }

        return $this->roles()->where('nombre', 'Orientador')->exists();
    }
}
