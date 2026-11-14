<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio preventivo al estudiante 24 a 48 horas previas a su cita de orientación confirmada.
 */
class RecordatorioCitaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa fecha, hora, modalidad y lugar o enlace de la cita.
     */
    public function __construct(
        public int $citaId,
        public string $fechaCita,
        public string $horaInicio,
        public string $modalidad,
        public ?string $lugarOEnlace = null
    ) {
    }

        /**
     * Canales de alerta (base de datos y correo electrónico).
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

        /**
     * Notificación interna con instrucciones para el estudiante.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        $detalleLugar = $this->lugarOEnlace ? " ({$this->lugarOEnlace})" : '';

        return [
            'tipo' => 'cita',
            'titulo' => 'Recordatorio de Cita de Orientación Próxima',
            'mensaje' => "Te recordamos que tienes una cita programada para el {$this->fechaCita} a las {$this->horaInicio} hrs en modalidad {$this->modalidad}{$detalleLugar}.",
            'url' => route('alumno.citas.index'),
            'icono' => 'cita',
        ];
    }

        /**
     * Correo recordatorio con datos de acceso y fecha de la sesión.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Recordatorio de Cita de Orientación - Bienestar UPEMOR')
            ->line("Tienes una cita de orientación programada:")
            ->line("Fecha: {$this->fechaCita}")
            ->line("Horario: {$this->horaInicio} hrs")
            ->line("Modalidad: {$this->modalidad}");

        if ($this->lugarOEnlace) {
            $mail->line("Ubicación / Enlace: {$this->lugarOEnlace}");
        }

        return $mail->action('Consultar Agenda', route('alumno.citas.index'))
            ->line('Por favor asiste puntualmente.');
    }
}

