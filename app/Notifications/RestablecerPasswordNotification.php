<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación por correo electrónico con enlace seguro para el restablecimiento de contraseña.
 */
class RestablecerPasswordNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa el token criptográfico generado por Laravel.
     */
    public function __construct(public string $token)
    {
    }

        /**
     * Canal de transmisión (exclusivamente correo electrónico).
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

        /**
     * Construye el correo de restablecimiento de contraseña con plantilla UPEMOR.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Restablecer Contraseña — Bienestar UPEMOR')
            ->view(
                ['html' => 'emails.auth.restablecer-password', 'text' => 'emails.auth.restablecer-password-text'],
                [
                    'url' => $url,
                    'nombre' => $notifiable->nombre ?? 'Usuario',
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]
            );
    }
}

