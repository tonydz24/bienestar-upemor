<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación al profesor sobre el dictamen administrativo de su propuesta de recompensa.
 */
class PropuestaRecompensaResueltaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los datos de la propuesta y resolución (aprobada o rechazada).
     */
    public function __construct(
        public string $tituloRecompensa,
        public string $resolucion,
        public ?string $observaciones = null
    ) {
    }

        /**
     * Canales de notificación interna.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

        /**
     * Datos para la campana de notificaciones del docente.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'propuesta',
            'titulo' => 'Resolución de Propuesta de Recompensa',
            'mensaje' => "Tu propuesta [{$this->tituloRecompensa}] fue {$this->resolucion} por la Dirección." . ($this->observaciones ? " Observaciones: {$this->observaciones}" : ''),
            'url' => route('docente.recompensas.index'),
            'icono' => 'propuesta',
        ];
    }
}

