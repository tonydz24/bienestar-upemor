<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación confirmando la reprogramación de una cita de orientación psicológica.
 */
class ReprogramacionAprobadaNotification extends Notification
{
    use Queueable;

        /**
     * Inicializa la nueva fecha, horario y modalidad acordada.
     */
    public function __construct(
        public string $nuevaFecha,
        public string $nuevaHora,
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
     * Estructura para el centro de notificaciones.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'cita',
            'titulo' => 'Reprogramación de Cita Aprobada',
            'mensaje' => "Tu orientador ({$this->orientadorNombre}) aprobó la reprogramación de tu cita. La nueva fecha confirmada es el {$this->nuevaFecha} a las {$this->nuevaHora} hrs.",
            'url' => route('alumno.citas.index'),
            'icono' => 'cita',
        ];
    }
}

