<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación al docente cuando un estudiante solicita inscribirse a una de sus materias.
 */
class SolicitudInscripcionEstudianteNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa el nombre del estudiante, matrícula y asignatura solicitada.
     */
    public function __construct(
        public string $estudianteNombre,
        public string $matricula,
        public string $asignaturaNombre,
        public string $grupoNombre,
        public int $asignacionId
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
     * Notificación en la bandeja del profesor.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'solicitud_inscripcion_estudiante',
            'titulo' => 'Nueva Solicitud de Inscripción a Materia',
            'mensaje' => "El estudiante {$this->estudianteNombre} ({$this->matricula}) solicitó inscribirse a {$this->asignaturaNombre} ({$this->grupoNombre}).",
            'url' => route('docente.academico.grupo', $this->asignacionId),
            'icono' => 'bi-person-plus',
        ];
    }
}

