<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación al orientador cuando un estudiante solicita una nueva cita de atención.
 */
class SolicitudCitaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa el nombre del estudiante, fecha tentativa y modalidad.
     */
    public function __construct(
        public string $alumnoNombre,
        public string $fechaCita,
        public string $horaInicio,
        public string $modalidad,
        public int $citaId
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
     * Carga útil en base de datos para la bandeja del profesional.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'cita',
            'titulo' => 'Nueva Solicitud de Cita de Orientación',
            'mensaje' => "El estudiante {$this->alumnoNombre} ha solicitado una cita para el {$this->fechaCita} a las {$this->horaInicio} hrs ({$this->modalidad}).",
            'url' => route('docente.citas.show', $this->citaId),
            'icono' => 'cita',
        ];
    }
}

