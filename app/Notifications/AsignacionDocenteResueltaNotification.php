<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación emitida al docente cuando el administrador aprueba o rechaza una cátedra solicitada.
 */
class AsignacionDocenteResueltaNotification extends Notification
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

        /**
     * Inicializa los datos de la asignatura, grupo y resolución administrativa.
     */
    public function __construct(
        public string $asignaturaNombre,
        public string $grupoNombre,
        public string $estado, // 'APROBADA' o 'RECHAZADA'
        public ?string $motivoResolucion = null
    ) {
    }

        /**
     * Define los canales de notificación habilitados (base de datos).
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<int, string> Canales de entrega configurados.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

        /**
     * Estructura los datos almacenados en la base de datos para la campana de notificaciones.
     *
     * @param object $notifiable Destinatario de la notificación.
     * @return array<string, mixed> Carga útil almacenada en base de datos.
     */
    public function toDatabase(object $notifiable): array
    {
        $estadoLabel = $this->estado === 'APROBADA' ? 'APROBADA' : 'RECHAZADA';
        $mensaje = "Tu solicitud para impartir {$this->asignaturaNombre} ({$this->grupoNombre}) fue {$estadoLabel} por la Dirección.";
        if ($this->motivoResolucion) {
            $mensaje .= " Observaciones: {$this->motivoResolucion}";
        }

        return [
            'tipo' => 'resolucion_asignacion_docente',
            'titulo' => "Solicitud de Asignatura {$estadoLabel}",
            'mensaje' => $mensaje,
            'url' => route('docente.academico.index'),
            'icono' => $this->estado === 'APROBADA' ? 'bi-check2-circle' : 'bi-x-circle',
        ];
    }
}

