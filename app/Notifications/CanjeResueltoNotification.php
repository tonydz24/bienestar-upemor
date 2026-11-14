<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación al estudiante cuando su solicitud de canje es aprobada, utilizada o rechazada.
 */
class CanjeResueltoNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los detalles del beneficio y estado de resolución.
     */
    public function __construct(
        public string $recompensaNombre,
        public string $materiaNombre,
        public string $estado,
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
     * Estructura los datos de notificación interna.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        $detalle = ($this->estado === 'UTILIZADA')
            ? "Tu beneficio digital [{$this->recompensaNombre}] en {$this->materiaNombre} fue marcado como aplicado por tu docente."
            : "Tu canje del beneficio [{$this->recompensaNombre}] en {$this->materiaNombre} fue resuelto: {$this->estado}." . ($this->motivo ? " Motivo: {$this->motivo}" : '');

        return [
            'tipo' => 'recompensa',
            'titulo' => 'Actualización de Recompensa Académica',
            'mensaje' => $detalle,
            'url' => route('alumno.recompensas.index'),
            'icono' => 'recompensa',
        ];
    }
}

