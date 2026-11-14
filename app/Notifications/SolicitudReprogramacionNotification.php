<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación al profesional informando que el alumno ha solicitado reprogramar una cita.
 */
class SolicitudReprogramacionNotification extends Notification
{
    use Queueable;

        /**
     * Inicializa los datos de la cita, motivo y fechas propuestas.
     */
    public function __construct(
        public string $alumnoNombre,
        public string $fechaOriginal,
        public string $fechaPropuesta,
        public string $horaPropuesta,
        public ?string $motivo = null
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
     * Notificación en la bandeja de orientación.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'cita',
            'titulo' => 'Solicitud de Reprogramación de Cita',
            'mensaje' => "El estudiante {$this->alumnoNombre} solicitó reprogramar su cita del {$this->fechaOriginal} para el {$this->fechaPropuesta} a las {$this->horaPropuesta} hrs." . ($this->motivo ? " Motivo: {$this->motivo}" : ''),
            'url' => route('docente.citas.index'),
            'icono' => 'cita',
        ];
    }
}

