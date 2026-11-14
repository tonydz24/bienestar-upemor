<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación al docente cuando un estudiante realiza un canje de beneficio que requiere validación.
 */
class CanjePendienteValidacionNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa la información del canje, alumno y asignatura.
     */
    public function __construct(
        public string $alumnoNombre,
        public string $recompensaNombre,
        public string $materiaNombre,
        public string $codigoBeneficio
    ) {
    }

        /**
     * Determina los canales activos (base de datos y correo electrónico si está configurado).
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
     * Carga útil guardada en la base de datos.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'recompensa',
            'titulo' => 'Nuevo Canje de Recompensa por Validar',
            'mensaje' => "El estudiante {$this->alumnoNombre} ha canjeado el beneficio [{$this->recompensaNombre}] ({$this->codigoBeneficio}) en {$this->materiaNombre}.",
            'url' => route('docente.recompensas.index', ['tab' => 'solicitudes']),
            'icono' => 'recompensa',
        ];
    }

        /**
     * Construye el mensaje de correo electrónico con plantilla institucional.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Solicitud de Canje de Recompensa Académica — Bienestar UPEMOR')
            ->view('emails.solicitud-canje-docente', [
                'docenteNombre' => $notifiable->nombre ?? 'Docente',
                'alumnoNombre' => $this->alumnoNombre,
                'recompensaNombre' => $this->recompensaNombre,
                'materiaNombre' => $this->materiaNombre,
                'codigoBeneficio' => $this->codigoBeneficio,
                'urlAction' => route('docente.recompensas.index', ['tab' => 'solicitudes']),
            ]);
    }
}
