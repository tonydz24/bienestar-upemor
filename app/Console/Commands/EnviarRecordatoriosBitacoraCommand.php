<?php

namespace App\Console\Commands;

use App\Models\BitacoraEmocional;
use App\Models\User;
use App\Notifications\BitacoraRecordatorioNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando Artisan para el Envío Automático de Recordatorios Diarios de Bitácora Emocional.
 *
 * Localiza alumnos activos que aún no hayan capturado su estado de ánimo en el día en curso,
 * previniendo envíos duplicados en la misma jornada y registrando métricas en bitácora de log.
 */
class EnviarRecordatoriosBitacoraCommand extends Command
{
    /**
     * Firma del comando en la consola de comandos de Artisan.
     *
     * @var string
     */
    protected $signature = 'bienestar:recordatorios-bitacora';

    /**
     * Descripción textual del comando en la lista de ayuda de Artisan.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios de registro en bitácora emocional a alumnos activos que no hayan registrado su estado de ánimo hoy';

    /**
     * Ejecuta la lógica del comando emitiendo las notificaciones institucionales correspondientes.
     *
     * @return int Código de salida del comando (Command::SUCCESS).
     */
    public function handle(): int
    {
        $hoy = now()->toDateString();
        $this->info("Iniciando envío de recordatorios de bitácora para la fecha: {$hoy}");

        $alumnos = User::whereHas('rol', fn ($r) => $r->where('nombre', 'Alumno'))
            ->where('estado', 'ACTIVO')
            ->get();

        $enviados = 0;
        $omitidos = 0;

        foreach ($alumnos as $alumno) {
            // 1. Verificar si ya registró hoy
            $yaRegistroHoy = BitacoraEmocional::where('usuario_id', $alumno->usuario_id)
                ->whereDate('fecha', $hoy)
                ->exists();

            if ($yaRegistroHoy) {
                $omitidos++;
                continue;
            }

            // 2. Prevenir notificaciones duplicadas en el mismo día
            $yaNotificadoHoy = $alumno->notifications()
                ->where('type', BitacoraRecordatorioNotification::class)
                ->whereDate('created_at', $hoy)
                ->exists();

            if ($yaNotificadoHoy) {
                $omitidos++;
                continue;
            }

            $alumno->notify(new BitacoraRecordatorioNotification());
            $enviados++;
        }

        $this->info("Recordatorios enviados: {$enviados}. Omitidos (ya registrados o ya notificados): {$omitidos}.");
        Log::info("Comando bienestar:recordatorios-bitacora ejecutado exitosamente", [
            'fecha' => $hoy,
            'enviados' => $enviados,
            'omitidos' => $omitidos,
        ]);

        return Command::SUCCESS;
    }
}
