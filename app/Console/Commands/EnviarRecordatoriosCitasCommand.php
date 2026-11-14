<?php

namespace App\Console\Commands;

use App\Models\CitaOrientacion;
use App\Notifications\RecordatorioCitaNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando Artisan para el Envío de Recordatorios de Citas de Orientación Psicológica.
 *
 * Localiza citas confirmadas dentro de las próximas 24 a 48 horas y envía recordatorios
 * preventivos por notificación interna y correo electrónico con garantía de idempotencia.
 */
class EnviarRecordatoriosCitasCommand extends Command
{
    /**
     * Firma del comando en la consola de Artisan.
     *
     * @var string
     */
    protected $signature = 'bienestar:recordatorios-citas';

    /**
     * Descripción textual del comando para el listado de ayuda de Artisan.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios preventivos a los estudiantes para sus citas de orientación psicológica confirmadas para las próximas 24 a 48 horas';

    /**
     * Ejecuta el proceso de búsqueda y notificación de citas próximas.
     *
     * @return int Código de salida del comando (self::SUCCESS).
     */
    public function handle(): int
    {
        $hoy = now('America/Mexico_City')->toDateString();
        $manana = now('America/Mexico_City')->addDay()->toDateString();

        $this->info("Buscando citas de orientación confirmadas para recordatorio entre {$hoy} y {$manana}...");

        $citas = CitaOrientacion::with('estudianteUsuario')
            ->where('estado', CitaOrientacion::ESTADO_CONFIRMADA)
            ->whereDate('fecha_cita', '>=', $hoy)
            ->whereDate('fecha_cita', '<=', $manana)
            ->get();

        $enviados = 0;
        $omitidos = 0;

        foreach ($citas as $cita) {
            $estudianteUsuario = $cita->estudianteUsuario;

            if (!$estudianteUsuario || $estudianteUsuario->estado !== 'ACTIVO') {
                $omitidos++;
                continue;
            }

            // Idempotencia: Verificar si el usuario ya recibió recordatorio para esta cita específica
            $yaNotificado = $estudianteUsuario->notifications()
                ->where('type', RecordatorioCitaNotification::class)
                ->where('data->tipo', 'cita')
                ->get()
                ->contains(function ($n) use ($cita) {
                    $mensaje = $n->data['mensaje'] ?? '';
                    $fechaFormato = Carbon::parse($cita->fecha_cita)->format('d/m/Y');
                    return str_contains($mensaje, $fechaFormato) || str_contains($mensaje, (string) $cita->cita_id);
                });

            if ($yaNotificado) {
                $omitidos++;
                continue;
            }

            $fechaFormateada = Carbon::parse($cita->fecha_cita)->format('d/m/Y');
            $horaFormateada = substr((string) $cita->hora_inicio, 0, 5);

            $estudianteUsuario->notify(new RecordatorioCitaNotification(
                citaId: $cita->cita_id,
                fechaCita: $fechaFormateada,
                horaInicio: $horaFormateada,
                modalidad: $cita->modalidad,
                lugarOEnlace: $cita->lugar_o_enlace
            ));

            $enviados++;
        }

        $this->info("Recordatorios de citas procesados. Enviados: {$enviados}. Omitidos (ya notificados o inactivos): {$omitidos}.");
        Log::info("Comando bienestar:recordatorios-citas ejecutado", [
            'fecha_ejecucion' => $hoy,
            'enviados' => $enviados,
            'omitidos' => $omitidos,
        ]);

        return self::SUCCESS;
    }
}
