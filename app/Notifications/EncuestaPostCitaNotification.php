<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitación al estudiante a responder una encuesta de satisfacción tras concluir una cita de orientación.
 */
class EncuestaPostCitaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa la referencia a la cita concluida y el identificador de la encuesta.
     */
    public function __construct(
        public int $citaId,
        public string $fechaCita,
        public string $profesionalNombre
    ) {
    }

        /**
     * Canales de notificación (base de datos y correo electrónico).
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

        /**
     * Datos de la notificación en base de datos.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'cita',
            'titulo' => 'Evaluación de Sesión de Orientación',
            'mensaje' => "Tu sesión del {$this->fechaCita} con {$this->profesionalNombre} ha concluido. Te invitamos a compartir tu experiencia para seguir fortaleciendo el programa de Bienestar.",
            'url' => route('alumno.citas.index'),
            'icono' => 'cita',
        ];
    }

        /**
     * Mensaje de correo electrónico invitando a evaluar la sesión de orientación.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Evaluación de Sesión de Orientación - Bienestar UPEMOR')
            ->line("Tu cita del {$this->fechaCita} con {$this->profesionalNombre} ha sido registrada como completada.")
            ->action('Ver Detalle de Cita', route('alumno.citas.index'))
            ->line('Agradecemos tu confianza en el servicio institucional de orientación.');
    }
}

