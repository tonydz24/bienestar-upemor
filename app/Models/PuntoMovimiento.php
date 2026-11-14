<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent institucional: PuntoMovimiento.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class PuntoMovimiento extends Model
{
    use HasFactory;

    protected $table = 'estudiante_puntos_movimientos';
    protected $primaryKey = 'movimiento_id';

    public const TIPO_EMOCION_DIARIA = 'EMOCION_DIARIA';
    public const TIPO_MEDITACION = 'MEDITACION';
    public const TIPO_EVALUACION = 'EVALUACION';
    public const TIPO_ENCUESTA = 'ENCUESTA';
    public const TIPO_RUTINA_SEMANAL = 'RUTINA_SEMANAL';
    public const TIPO_CANJE_RECOMPENSA = 'CANJE_RECOMPENSA';
    public const TIPO_REEMBOLSO_CANJE = 'REEMBOLSO_CANJE';
    public const TIPO_AJUSTE_ADMINISTRATIVO = 'AJUSTE_ADMINISTRATIVO';
    public const TIPO_SALDO_INICIAL = 'SALDO_INICIAL';

    protected $fillable = [
        'estudiante_id',
        'tipo',
        'puntos',
        'saldo_anterior',
        'saldo_nuevo',
        'referencia_tipo',
        'referencia_id',
        'fecha',
        'descripcion',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo PuntoMovimiento.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'puntos' => 'integer',
            'saldo_anterior' => 'integer',
            'saldo_nuevo' => 'integer',
            'fecha' => 'date',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudianteUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id', 'usuario_id');
    }
}

