<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación al administrador sobre una nueva solicitud de cátedra postulada por un docente.
 */
class SolicitudDocenteCreadaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los datos de la asignatura, grupo y profesor.
     */
    public function __construct(
        public string $docenteNombre,
        public string $asignaturaNombre,
        public string $grupoNombre,
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
     * Notificación para la dirección académica.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'solicitud_docente_creada',
            'titulo' => 'Nueva Solicitud de Impartición de Asignatura',
            'mensaje' => "El docente {$this->docenteNombre} ha solicitado impartir {$this->asignaturaNombre} para el grupo {$this->grupoNombre}." . ($this->motivo ? " Justificación: {$this->motivo}" : ''),
            'url' => route('admin.docente-asignaciones.index'),
            'icono' => 'bi-journal-check',
        ];
    }
}

