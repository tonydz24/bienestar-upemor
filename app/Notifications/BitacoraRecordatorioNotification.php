<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación de recordatorio diario al alumno para registrar su estado emocional en la bitácora.
 */
class BitacoraRecordatorioNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa la notificación de recordatorio preventivo.
     */
    public function __construct(public ?string $mensajePersonalizado = null)
    {
    }

        /**
     * Canales de notificación utilizados (base de datos).
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

        /**
     * Datos para el centro de notificaciones del estudiante.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'bitacora',
            'titulo' => 'Recordatorio de Bienestar Diario',
            'mensaje' => $this->mensajePersonalizado ?? 'Recuerda registrar tu estado de ánimo en la bitácora emocional para dar seguimiento a tus metas y bienestar.',
            'url' => route('alumno.bitacora.index'),
            'icono' => 'bitacora',
        ];
    }
}

