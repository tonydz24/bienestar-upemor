<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación de actualización en el estado o programación de una cita de orientación.
 */
class CitaActualizadaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los datos de la cita y el nuevo estado o motivo.
     */
    public function __construct(
        public string $nuevoEstado,
        public string $fechaCita,
        public ?string $motivo = null
    ) {
    }

        /**
     * Canales de notificación habilitados (base de datos).
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

        /**
     * Estructura el mensaje de alerta para la interfaz del usuario.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'cita',
            'titulo' => 'Actualización de Cita de Orientación',
            'mensaje' => "El estado de tu cita programada para el {$this->fechaCita} ha cambiado a: {$this->nuevoEstado}." . ($this->motivo ? " Detalle: {$this->motivo}" : ''),
            'url' => route('alumno.citas.index'),
            'icono' => 'cita',
        ];
    }
}

