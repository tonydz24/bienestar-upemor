<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Rutas y Tareas Programadas de Consola (Console Kernel)
|--------------------------------------------------------------------------
|
| Define los comandos de consola basados en clausuras y la programación
| automatizada de tareas en segundo plano del sistema Bienestar UPEMOR.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programación automática de recordatorios de bienestar diario (FN.14 & FF.5)
Schedule::command('bienestar:recordatorios-bitacora')
    ->dailyAt('20:00')
    ->timezone('America/Mexico_City');

// Programación automática de recordatorios de citas de orientación próximas (FN.14 & FF.5)
Schedule::command('bienestar:recordatorios-citas')
    ->dailyAt('08:00')
    ->timezone('America/Mexico_City');
