<?php

namespace Database\Seeders;

use App\Models\LoginAcceso;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SandboxLoginAccesosSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::with('rol')->get();
        if ($users->isEmpty()) {
            return;
        }

        $now = Carbon::now();

        // Generar accesos simulados de los últimos 14 días
        for ($dia = 14; $dia >= 0; $dia--) {
            $fechaDia = $now->copy()->subDays($dia);
            $accesosDelDia = rand(4, 12);

            for ($i = 0; $i < $accesosDelDia; $i++) {
                $user = $users->random();
                $hora = rand(7, 21);
                $min = rand(0, 59);
                $fechaAcceso = $fechaDia->copy()->setTime($hora, $min);

                LoginAcceso::create([
                    'usuario_id' => $user->usuario_id,
                    'rol_id' => $user->rol_id,
                    'nombre_usuario' => $user->nombre . ' ' . $user->primer_apellido,
                    'email' => $user->email,
                    'rol_nombre' => $user->rol?->nombre ?? 'Alumno',
                    'fecha_acceso' => $fechaAcceso,
                    'ip_address' => '127.0.0.' . rand(1, 50),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0',
                ]);
            }
        }
    }
}

