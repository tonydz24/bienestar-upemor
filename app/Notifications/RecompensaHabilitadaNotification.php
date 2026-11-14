<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación a los alumnos de un grupo cuando su docente habilita una nueva recompensa académica.
 */
class RecompensaHabilitadaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa el nombre del beneficio, asignatura y costo en puntos.
     */
    public function __construct(
        public string $nombreRecompensa,
        public string $materiaNombre,
        public int $costoPuntos
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
     * Notificación interna para los estudiantes del grupo.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'recompensa',
            'titulo' => 'Nueva Recompensa Académica Habilitada',
            'mensaje' => "Se ha activado el beneficio [{$this->nombreRecompensa}] en la materia {$this->materiaNombre} ({$this->costoPuntos} pts).",
            'url' => route('alumno.recompensas.index'),
            'icono' => 'recompensa',
        ];
    }

        /**
     * Correo electrónico anunciando el beneficio disponible.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Recompensa Académica Habilitada — Bienestar UPEMOR')
            ->greeting('¡Hola, ' . ($notifiable->nombre ?? 'Estudiante') . '!')
            ->line('Te informamos que tu docente ha activado un nuevo beneficio académico para tu grupo:')
            ->line("**Recompensa:** {$this->nombreRecompensa}")
            ->line("**Asignatura:** {$this->materiaNombre}")
            ->line("**Costo en Puntos:** {$this->costoPuntos} pts de bienestar")
            ->action('Ver Catálogo de Recompensas', route('alumno.recompensas.index'))
            ->line('Puedes canjear tus puntos acumulados por hábitos de estudio, pausas activas y participación en actividades formativas.')
            ->salutation('Atentamente, Coordinación de Bienestar UPEMOR');
    }
}
