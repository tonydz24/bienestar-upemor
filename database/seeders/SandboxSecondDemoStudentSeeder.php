<?php

namespace Database\Seeders;

use App\Models\Estudiante;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SandboxSecondDemoStudentSeeder extends Seeder
{
    /**
     * Crea un segundo estudiante demostrativo exclusivo para el entorno sandbox.
     * Diseñado para permitir pruebas manuales completas de inicio de cuestionario,
     * autoguardado, reanudación y finalización de DEMO-HABITOS sin alterar el
     * historial ni las restricciones de reaplicación del primer estudiante.
     */
    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();

        if ($dbName !== 'bienestar_upemor_sandbox') {
            throw new \RuntimeException("Este seeder es de uso exclusivo para el entorno sandbox. Base detectada: {$dbName}");
        }

        $rolAlumno = Rol::where('nombre', 'Alumno')->first();
        $rolId = $rolAlumno?->rol_id ?? 3;

        $programa = ProgramaEducativo::where('activo', true)->first();
        $programaId = $programa?->programa_id ?? 1;

        $user = User::updateOrCreate(
            ['email' => 'alumno.evaluaciones@upemor.edu.mx'],
            [
                'rol_id' => $rolId,
                'nombre' => 'Estudiante',
                'apellido_paterno' => 'Evaluación',
                'apellido_materno' => 'Sandbox',
                'password' => Hash::make('DemoEvaluaciones2026!'),
                'telefono' => '7771238888',
                'estado' => 'ACTIVO',
            ]
        );

        Estudiante::updateOrCreate(
            ['usuario_id' => $user->usuario_id],
            [
                'programa_id' => $programaId,
                'matricula' => 'EVAL202602',
                'cuatrimestre' => 3,
                'grupo' => 'B',
                'saldo_puntos' => 20,
                'permite_visualizacion' => false,
            ]
        );
    }
}

