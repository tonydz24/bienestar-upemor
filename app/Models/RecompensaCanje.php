<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: RecompensaCanje.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class RecompensaCanje extends Model
{
    use HasFactory;

    protected $table = 'recompensas_canjes';
    protected $primaryKey = 'canje_id';

    // Flujo nuevo directo: CANJEADA (beneficio digital adquirido listo para usar) -> UTILIZADA (docente lo aplica)
    public const ESTADO_CANJEADA = 'CANJEADA';
    public const ESTADO_UTILIZADA = 'UTILIZADA';
    public const ESTADO_EXPIRADA = 'EXPIRADA';
    public const ESTADO_ANULADA = 'ANULADA';

    // Compatibilidad con registros históricos y pruebas anteriores
    public const ESTADO_SOLICITADO = 'SOLICITADO';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';
    public const ESTADO_CANCELADO = 'CANCELADO';

    protected $fillable = [
        'activacion_id',
        'estudiante_id',
        'codigo_beneficio',
        'puntos_canjeados',
        'estado',
        'motivo_solicitud',
        'motivo_rechazo',
        'fecha_solicitud',
        'fecha_canje',
        'fecha_resolucion',
        'fecha_utilizacion',
        'resuelto_por',
        'utilizado_por',
        'condiciones_snapshot',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo RecompensaCanje.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'puntos_canjeados' => 'integer',
            'fecha_solicitud' => 'datetime',
            'fecha_canje' => 'datetime',
            'fecha_resolucion' => 'datetime',
            'fecha_utilizacion' => 'datetime',
        ];
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function activacion()
    {
        return $this->belongsTo(RecompensaActivada::class, 'activacion_id', 'activacion_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudianteUsuario()
    {
        return $this->belongsTo(User::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function resueltoPorUsuario()
    {
        return $this->belongsTo(User::class, 'resuelto_por', 'usuario_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function utilizadoPorUsuario()
    {
        return $this->belongsTo(User::class, 'utilizado_por', 'usuario_id');
    }

    /**
     * Determina si el beneficio está adquirido y pendiente de ser utilizado ante el profesor.
     */
    public function esPendienteDeUso(): bool
    {
        return in_array($this->estado, [self::ESTADO_CANJEADA, self::ESTADO_APROBADO, self::ESTADO_SOLICITADO], true);
    }    /**
     * Método auxiliar de entidad 'esUtilizada'.
     *
     * @return bool
     */
public function esUtilizada(): bool
    {
        return $this->estado === self::ESTADO_UTILIZADA;
    }    /**
     * Descriptor para el atributo calculado 'estadoBadgeClass'.
     *
     * @return mixed
     */
public function getEstadoBadgeClassAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_CANJEADA, self::ESTADO_APROBADO => 'badge-recompensa-aprobada',
            self::ESTADO_SOLICITADO => 'badge-recompensa-solicitada',
            self::ESTADO_UTILIZADA => 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1',
            self::ESTADO_RECHAZADO => 'badge-recompensa-rechazada',
            self::ESTADO_CANCELADO => 'badge bg-light text-muted border rounded-pill px-3 py-1',
            self::ESTADO_EXPIRADA => 'badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1',
            default => 'badge bg-light text-dark border rounded-pill px-2 py-1',
        };
    }    /**
     * Descriptor para el atributo calculado 'estadoLabel'.
     *
     * @return mixed
     */
public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_CANJEADA => 'Canjeada (Listo para usar)',
            self::ESTADO_UTILIZADA => 'Utilizada ante el profesor',
            self::ESTADO_APROBADO => 'Aprobada (Listo para usar)',
            self::ESTADO_SOLICITADO => 'En revisión',
            self::ESTADO_RECHAZADO => 'Rechazada (Puntos reembolsados)',
            self::ESTADO_CANCELADO => 'Cancelada',
            self::ESTADO_EXPIRADA => 'Expirada',
            default => $this->estado,
        };
    }
}
