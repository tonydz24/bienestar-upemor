<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent institucional: BitacoraEmocional.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class BitacoraEmocional extends Model
{
    use HasFactory;

    protected $table = 'bitacoras_emocionales';
    protected $primaryKey = 'bitacora_id';

    protected $fillable = [
        'usuario_id',
        'emocion_id',
        'fecha',
        'intensidad',
        'descripcion',
        'observaciones',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo BitacoraEmocional.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'intensidad' => 'integer',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function emocion(): BelongsTo
    {
        return $this->belongsTo(Emocion::class, 'emocion_id', 'emocion_id');
    }

    /**
     * Variable estática de control para pruebas automatizadas de la regla FN.4.
     */
    public static bool $simularSeguimientoActivoEnPruebas = false;

    /**
     * Regla FN.4: No se pueden eliminar registros vinculados a un seguimiento psicológico.
     *
     * Conforme a la Tercera Forma Normal (3NF), dicha vinculación no es una bandera booleana
     * desnormalizada en esta tabla, sino que provendrá de la relación con el módulo clínico
     * cuando este sea implementado (ej. return ! $this->seguimientos()->exists();).
     *
     * Al no existir entidades clínicas ficticias actualmente, los registros propios pueden
     * eliminarse libremente por el alumno, a menos que se simule la restricción en pruebas.
     */
    public function puedeSerEliminado(): bool
    {
        if (self::$simularSeguimientoActivoEnPruebas) {
            return false;
        }

        if (isset($this->attributes['asociado_seguimiento']) && $this->attributes['asociado_seguimiento']) {
            return false;
        }

        return true;
    }
}

