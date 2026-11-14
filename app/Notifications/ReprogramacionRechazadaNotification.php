<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación informando el rechazo de una solicitud de reprogramación de cita.
 */
class ReprogramacionRechazadaNotification extends Notification
{
    use Queueable;

        /**
     * Inicializa la referencia a la cita y el motivo de declinación.
     */
    public function __construct(
        public string $fechaCita,
        public string $horaCita,
        public string $motivo,
        public string $orientadorNombre
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
     * Notificación interna en base de datos.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'cita',
            'titulo' => 'Solicitud de Reprogramación No Aceptada',
            'mensaje' => "Tu solicitud de reprogramación fue rechazada por el orientador ({$this->orientadorNombre}). Tu cita original sigue programada para el {$this->fechaCita} a las {$this->horaCita} hrs. Motivo: {$this->motivo}",
            'url' => route('alumno.citas.index'),
            'icono' => 'cita',
        ];
    }
}

