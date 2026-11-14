<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación al administrador sobre una nueva solicitud de registro de estudiante pendiente de revisión.
 */
class NuevoAlumnoRegistradoNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los datos del aspirante (nombre, matrícula, carrera).
     */
    public function __construct(
        public string $alumnoNombre,
        public string $matricula,
        public string $programaNombre
    ) {
    }

        /**
     * Canales de alerta para el equipo administrativo.
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
     * Notificación interna en la bandeja administrativa.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'registro_alumno',
            'titulo' => 'Nueva Solicitud de Registro de Alumno',
            'mensaje' => "El estudiante {$this->alumnoNombre} (Matrícula: {$this->matricula}) del programa {$this->programaNombre} ha solicitado su registro.",
            'url' => route('admin.solicitudes.index'),
            'icono' => 'bi-person-plus',
        ];
    }

        /**
     * Alerta vía correo electrónico para validación de cuenta.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage Mensaje de correo formateado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Solicitud de Registro de Alumno — Bienestar UPEMOR')
            ->view('emails.nuevo-alumno-registrado', [
                'alumnoNombre' => $this->alumnoNombre,
                'matricula' => $this->matricula,
                'programaNombre' => $this->programaNombre,
                'urlAction' => route('admin.solicitudes.index'),
            ]);
    }
}

