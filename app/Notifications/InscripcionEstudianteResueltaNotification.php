<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación al alumno sobre la resolución (aprobada o rechazada) de su inscripción en una asignatura.
 */
class InscripcionEstudianteResueltaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa la información de la materia, grupo y dictamen docente.
     */
    public function __construct(
        public string $asignaturaNombre,
        public string $grupoNombre,
        public string $estado, // 'APROBADA' o 'RECHAZADA'
        public ?string $motivoResolucion = null
    ) {
    }

        /**
     * Canales de notificación habilitados (base de datos y correo).
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

        /**
     * Estructura de datos persistida en la tabla notifications.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        $estadoLabel = $this->estado === 'APROBADA' ? 'APROBADA' : 'RECHAZADA';
        $mensaje = "Tu solicitud de inscripción a {$this->asignaturaNombre} ({$this->grupoNombre}) fue {$estadoLabel}.";
        if ($this->motivoResolucion) {
            $mensaje .= " Observaciones: {$this->motivoResolucion}";
        }

        return [
            'tipo' => 'resolucion_inscripcion_estudiante',
            'titulo' => "Inscripción a Materia {$estadoLabel}",
            'mensaje' => $mensaje,
            'url' => route('alumno.academico.index'),
            'icono' => $this->estado === 'APROBADA' ? 'bi-check2-circle' : 'bi-x-circle',
        ];
    }

        /**
     * Cuerpo del correo electrónico institucional.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $estadoLabel = $this->estado === 'APROBADA' ? 'APROBADA' : 'RECHAZADA';

        return (new MailMessage)
            ->subject("Resolución de Inscripción a Asignatura: {$estadoLabel} — Bienestar UPEMOR")
            ->view('emails.solicitud-materia-aprobada', [
                'estudianteNombre' => $notifiable->nombre ?? 'Estudiante',
                'asignaturaNombre' => $this->asignaturaNombre,
                'grupoNombre' => $this->grupoNombre,
                'estado' => $this->estado,
                'motivoResolucion' => $this->motivoResolucion,
                'urlAction' => route('alumno.academico.index'),
            ]);
    }
}
