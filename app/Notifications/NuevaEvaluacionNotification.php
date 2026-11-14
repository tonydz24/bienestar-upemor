<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación a los estudiantes sobre la publicación de un nuevo instrumento psicométrico.
 */
class NuevaEvaluacionNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los datos del instrumento disponible.
     */
    public function __construct(
        public string $tituloInstrumento
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
        $channels = ['database'];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

        /**
     * Notificación interna para la campana de avisos.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'evaluacion',
            'titulo' => 'Nueva Evaluación Psicoeducativa Disponible',
            'mensaje' => "El cuestionario institucional [{$this->tituloInstrumento}] ya se encuentra habilitado para responder.",
            'url' => route('alumno.evaluaciones'),
            'icono' => 'evaluacion',
        ];
    }

        /**
     * Correo institucional notificando la evaluación psicométrica.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Evaluación Psicoeducativa Disponible — Bienestar UPEMOR')
            ->greeting('¡Hola, ' . ($notifiable->nombre ?? 'Estudiante') . '!')
            ->line("Se ha puesto a tu disposición un nuevo instrumento orientador de autoconocimiento en Bienestar UPEMOR:")
            ->line("**Instrumento:** {$this->tituloInstrumento}")
            ->line('Esta herramienta tiene fines formativos, de orientación preventiva y confidencial.')
            ->action('Consultar Evaluaciones', route('alumno.evaluaciones'))
            ->line('Recuerda que tus respuestas son privadas y están resguardadas conforme a las políticas institucionales.')
            ->salutation('Atentamente, Coordinación de Orientación y Bienestar UPEMOR');
    }
}
