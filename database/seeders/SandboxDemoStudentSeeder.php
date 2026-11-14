<?php

namespace Database\Seeders;

use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SandboxDemoStudentSeeder extends Seeder
{
    /**
     * Crea un usuario alumno demostrativo exclusivamente en la base de datos sandbox.
     * Incluye salvaguarda de seguridad para impedir ejecución accidental en la base principal.
     */
    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();

        if ($dbName !== 'bienestar_upemor_sandbox') {
            throw new \RuntimeException("Este seeder es de uso exclusivo para el entorno sandbox. Base detectada: {$dbName}");
        }

        $user = User::updateOrCreate(
            ['email' => 'alumno.demo@upemor.edu.mx'],
            [
                'rol_id' => 3, // Alumno
                'nombre' => 'Alumno',
                'apellido_paterno' => 'Demostración',
                'apellido_materno' => 'UPEMOR',
                'password' => Hash::make('DemoBienestar2026!'),
                'telefono' => '7771239999',
                'estado' => 'ACTIVO',
            ]
        );

        Estudiante::updateOrCreate(
            ['usuario_id' => $user->usuario_id],
            [
                'programa_id' => 1, // ITI
                'matricula' => 'DEMO202601',
                'cuatrimestre' => 4,
                'grupo' => 'A',
                'saldo_puntos' => 50,
                'permite_visualizacion' => false,
            ]
        );

        // Asegurar la presencia del instrumento ficticio de demostración
        $this->call(EvaluacionDemoSeeder::class);
    }
}

