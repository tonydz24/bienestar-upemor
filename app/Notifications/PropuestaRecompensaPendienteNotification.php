<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación al administrador sobre una nueva propuesta de beneficio enviada por un profesor.
 */
class PropuestaRecompensaPendienteNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los detalles de la recompensa y docente solicitante.
     */
    public function __construct(
        public string $nombreRecompensa,
        public string $docenteNombre,
        public int $costoPuntos = 50
    ) {
    }

        /**
     * Canales de notificación habilitados.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

        /**
     * Estructura del aviso para la administración institucional.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'recompensa',
            'titulo' => 'Nueva Propuesta de Beneficio Docente',
            'mensaje' => "El docente {$this->docenteNombre} propuso el beneficio [{$this->nombreRecompensa}] ({$this->costoPuntos} pts) para validación institucional.",
            'url' => route('admin.recompensas.catalogo'),
            'icono' => 'recompensa',
        ];
    }
}

