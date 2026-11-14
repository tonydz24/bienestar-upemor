<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación institucional sobre una nueva encuesta de bienestar disponible para responder.
 */
class NuevaEncuestaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los metadatos de la encuesta publicada.
     */
    public function __construct(
        public string $tituloEncuesta,
        public int $encuestaId,
        public bool $esAnonima = true
    ) {
    }

        /**
     * Canales activos de difusión.
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
     * Datos para la bandeja de notificaciones.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        $tipoAnonimato = $this->esAnonima ? 'confidencial y 100% anónima' : 'nominal';

        return [
            'tipo' => 'encuesta',
            'titulo' => 'Nueva Encuesta Institucional UPEMOR',
            'mensaje' => "Se ha habilitado la encuesta: [{$this->tituloEncuesta}]. Tu participación es {$tipoAnonimato} y suma 1 punto de bienestar.",
            'url' => route('alumno.encuestas.responder', $this->encuestaId),
            'icono' => 'encuesta',
        ];
    }

        /**
     * Correo electrónico con enlace directo a la encuesta.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tipoAnonimato = $this->esAnonima ? 'confidencial y 100% anónima' : 'nominal con fines de seguimiento';

        return (new MailMessage)
            ->subject('Nueva Encuesta Institucional UPEMOR: ' . $this->tituloEncuesta)
            ->greeting('¡Hola, ' . ($notifiable->nombre ?? 'Estudiante') . '!')
            ->line("Se encuentra disponible una nueva encuesta institucional en la plataforma Bienestar UPEMOR:")
            ->line("**Encuesta:** {$this->tituloEncuesta}")
            ->line("**Modalidad:** {$tipoAnonimato}")
            ->line("**Puntos otorgados:** 1 punto de bienestar por tu participación reflexiva")
            ->action('Responder Encuesta', route('alumno.encuestas.responder', $this->encuestaId))
            ->line('Tu opinión y retroalimentación nos permiten fortalecer los servicios académicos y el bienestar universitario.')
            ->salutation('Atentamente, Coordinación Institucional de Bienestar UPEMOR');
    }
}
