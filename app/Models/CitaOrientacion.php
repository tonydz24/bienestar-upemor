<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent institucional: CitaOrientacion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class CitaOrientacion extends Model
{
    use HasFactory;

    protected $table = 'citas_orientacion';
    protected $primaryKey = 'cita_id';

    public const ESTADO_SOLICITADA = 'SOLICITADA';
    public const ESTADO_CONFIRMADA = 'CONFIRMADA';
    public const ESTADO_RECHAZADA = 'RECHAZADA';
    public const ESTADO_CANCELADA_ALUMNO = 'CANCELADA_ALUMNO';
    public const ESTADO_CANCELADA_PROFESIONAL = 'CANCELADA_PROFESIONAL';
    public const ESTADO_FINALIZADA = 'FINALIZADA';
    public const ESTADO_NO_ASISTIO = 'NO_ASISTIO';

    public const MODALIDAD_PRESENCIAL = 'PRESENCIAL';
    public const MODALIDAD_VIRTUAL = 'VIRTUAL';

    public const REPROGRAMACION_PENDIENTE = 'PENDIENTE';
    public const REPROGRAMACION_APROBADA = 'APROBADA';
    public const REPROGRAMACION_RECHAZADA = 'RECHAZADA';
    public const REPROGRAMACION_CANCELADA = 'CANCELADA';

    protected $fillable = [
        'estudiante_id',
        'profesional_id',
        'fecha_cita',
        'hora_inicio',
        'hora_fin',
        'modalidad',
        'lugar_o_enlace',
        'estado',
        'sesion_numero',
        'tipo_seguimiento',
        'motivo_consulta',
        'motivo_cancelacion_rechazo',
        'fecha_solicitud',
        'fecha_resolucion',
        'resuelto_por',
        'ip_registro',
        'reprogramacion_estado',
        'reprogramacion_fecha',
        'reprogramacion_hora_inicio',
        'reprogramacion_hora_fin',
        'reprogramacion_motivo',
        'reprogramacion_respuesta_motivo',
        'reprogramacion_fecha_solicitud',
        'reprogramacion_fecha_resolucion',
        'reprogramacion_resuelto_por',
        'fecha_cita_anterior',
        'hora_inicio_anterior',
        'hora_fin_anterior',
        'conteo_reprogramaciones',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo CitaOrientacion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'fecha_cita' => 'date',
            'fecha_solicitud' => 'datetime',
            'fecha_resolucion' => 'datetime',
            'reprogramacion_fecha' => 'date',
            'reprogramacion_fecha_solicitud' => 'datetime',
            'reprogramacion_fecha_resolucion' => 'datetime',
            'fecha_cita_anterior' => 'date',
            'conteo_reprogramaciones' => 'integer',
        ];
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
public function profesional()
    {
        return $this->belongsTo(CitaProfesional::class, 'profesional_id', 'profesional_id');
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
public function reprogramacionResueltoPorUsuario()
    {
        return $this->belongsTo(User::class, 'reprogramacion_resuelto_por', 'usuario_id');
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function reprogramacionesHistorial()
    {
        return $this->hasMany(CitaReprogramacionHistorial::class, 'cita_id', 'cita_id')->orderByDesc('id');
    }    /**
     * Método auxiliar de entidad 'isActiva'.
     *
     * @return bool
     */
public function isActiva(): bool
    {
        return in_array($this->estado, [self::ESTADO_SOLICITADA, self::ESTADO_CONFIRMADA], true);
    }    /**
     * Método auxiliar de entidad 'tieneReprogramacionPendiente'.
     *
     * @return bool
     */
public function tieneReprogramacionPendiente(): bool
    {
        return $this->reprogramacion_estado === self::REPROGRAMACION_PENDIENTE;
    }    /**
     * Método auxiliar de entidad 'puedeSolicitarReprogramacion'.
     *
     * @return bool
     */
public function puedeSolicitarReprogramacion(): bool
    {
        if (!in_array($this->estado, [self::ESTADO_SOLICITADA, self::ESTADO_CONFIRMADA], true)) {
            return false;
        }

        if ($this->tieneReprogramacionPendiente()) {
            return false;
        }

        return Carbon::parse($this->fecha_cita)->isToday() || Carbon::parse($this->fecha_cita)->isFuture();
    }    /**
     * Método auxiliar de entidad 'puedeSerCanceladaPorAlumno'.
     *
     * @return bool
     */
public function puedeSerCanceladaPorAlumno(): bool
    {
        if (!in_array($this->estado, [self::ESTADO_SOLICITADA, self::ESTADO_CONFIRMADA], true)) {
            return false;
        }

        // Si la fecha ya pasó, no se puede cancelar
        return Carbon::parse($this->fecha_cita)->isToday() || Carbon::parse($this->fecha_cita)->isFuture();
    }    /**
     * Descriptor para el atributo calculado 'estadoLabel'.
     *
     * @return mixed
     */
public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_SOLICITADA => 'Solicitada',
            self::ESTADO_CONFIRMADA => 'Confirmada',
            self::ESTADO_RECHAZADA => 'No aceptada',
            self::ESTADO_CANCELADA_ALUMNO => 'Cancelada por ti',
            self::ESTADO_CANCELADA_PROFESIONAL => 'Cancelada por el orientador',
            self::ESTADO_FINALIZADA => 'Completada',
            self::ESTADO_NO_ASISTIO => 'No asistió',
            default => $this->estado,
        };
    }    /**
     * Descriptor para el atributo calculado 'estadoBadgeClass'.
     *
     * @return mixed
     */
public function getEstadoBadgeClassAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_SOLICITADA => 'badge-cita-solicitada',
            self::ESTADO_CONFIRMADA => 'badge-cita-confirmada',
            self::ESTADO_FINALIZADA => 'badge-cita-finalizada',
            self::ESTADO_RECHAZADA,
            self::ESTADO_CANCELADA_ALUMNO,
            self::ESTADO_CANCELADA_PROFESIONAL,
            self::ESTADO_NO_ASISTIO => 'badge-cita-cancelada',
            default => 'badge-secondary',
        };
    }
}

