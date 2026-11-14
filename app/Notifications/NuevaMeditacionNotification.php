<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación a los usuarios sobre la incorporación de una nueva sesión de meditación o mindfulness.
 */
class NuevaMeditacionNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public string $tituloMeditacion;
    public string $categoria;
    public int $duracionMinutos;
    public int $meditacionId;

        /**
     * Inicializa el título y categoría de la sesión de meditación.
     */
    public function __construct(
        \App\Models\Meditacion|string $tituloOrMeditacion,
        string $categoria = 'REL',
        int $duracionMinutos = 5,
        int $meditacionId = 0
    ) {
        if ($tituloOrMeditacion instanceof \App\Models\Meditacion) {
            $this->tituloMeditacion = (string) $tituloOrMeditacion->titulo;
            $this->categoria = (string) ($tituloOrMeditacion->categoria ?? 'REL');
            $this->duracionMinutos = (int) ($tituloOrMeditacion->duracion_minutos ?? 5);
            $this->meditacionId = (int) $tituloOrMeditacion->meditacion_id;
        } else {
            $this->tituloMeditacion = $tituloOrMeditacion;
            $this->categoria = $categoria;
            $this->duracionMinutos = $duracionMinutos;
            $this->meditacionId = $meditacionId;
        }
    }

        /**
     * Canales de notificación configurados.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

        /**
     * Datos para el panel de notificaciones del portal.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'meditacion',
            'titulo' => 'Nueva Actividad de Bienestar y Meditación',
            'mensaje' => "Se ha publicado un nuevo ejercicio: [{$this->tituloMeditacion}] ({$this->categoria}, {$this->duracionMinutos} min).",
            'url' => route('alumno.meditaciones.show', $this->meditacionId),
            'icono' => 'meditacion',
        ];
    }
}

