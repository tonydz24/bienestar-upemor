<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: EvaluacionConsentimiento.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class EvaluacionConsentimiento extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_consentimientos';
    protected $primaryKey = 'consentimiento_id';

    protected $fillable = [
        'usuario_id',
        'version_id',
        'intento_id',
        'consentimiento_version',
        'texto_consentimiento',
        'fecha_consentimiento',
        'ip_registro',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo EvaluacionConsentimiento.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'fecha_consentimiento' => 'datetime',
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
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'usuario_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function version()
    {
        return $this->belongsTo(VersionInstrumento::class, 'version_id', 'version_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function intento()
    {
        return $this->belongsTo(EvaluacionIntento::class, 'intento_id', 'intento_id');
    }
}

