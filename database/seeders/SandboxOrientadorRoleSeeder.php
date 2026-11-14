<?php

namespace Database\Seeders;

use App\Models\CitaDisponibilidad;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SandboxOrientadorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();
        if ($dbName !== 'bienestar_upemor_sandbox') {
            throw new \RuntimeException("Este seeder es de uso exclusivo para el entorno sandbox. Base detectada: {$dbName}");
        }

        // 1. Asegurar rol Orientador
        $rolOrientador = Rol::firstOrCreate(
            ['nombre' => 'Orientador'],
            ['descripcion' => 'Profesional de orientación y acompañamiento psicológico estudiantil']
        );

        $rolDocente = Rol::where('nombre', 'Docente')->firstOrFail();
        $rolAdmin = Rol::where('nombre', 'Administrador')->firstOrFail();

        // 2. Pure Orientador: orientador.psicologico@upemor.edu.mx
        $pureOrientador = User::updateOrCreate(
            ['email' => 'orientador.psicologico@upemor.edu.mx'],
            [
                'rol_id' => $rolOrientador->rol_id,
                'nombre' => 'David Alejandro',
                'apellido_paterno' => 'Vargas',
                'apellido_materno' => 'Mora',
                'password' => Hash::make('Orientador2026!'),
                'telefono' => '7773297003',
                'estado' => 'ACTIVO',
            ]
        );

        // Aseguramos que NO tenga registro en docentes
        Docente::where('usuario_id', $pureOrientador->usuario_id)->delete();

        // Perfil en CitaProfesional
        $profDavid = CitaProfesional::updateOrCreate(
            ['usuario_id' => $pureOrientador->usuario_id],
            [
                'cargo' => 'Orientador Psicológico de Campus',
                'cedula_profesional' => 'PSI-5544332-MOR',
                'lugar_atencion' => 'Módulo de Orientación - Edificio A, Planta Baja',
                'modalidades_permitidas' => 'AMBAS',
                'permite_citas' => true,
                'activo' => true,
            ]
        );

        // Disponibilidad semanal de David (Martes y Jueves 09:00 - 14:00)
        CitaDisponibilidad::firstOrCreate(
            [
                'profesional_id' => $profDavid->profesional_id,
                'dia_semana' => 2, // Martes
                'hora_inicio' => '09:00:00',
                'hora_fin' => '14:00:00',
            ],
            ['activo' => true]
        );

        CitaDisponibilidad::firstOrCreate(
            [
                'profesional_id' => $profDavid->profesional_id,
                'dia_semana' => 4, // Jueves
                'hora_inicio' => '09:00:00',
                'hora_fin' => '14:00:00',
            ],
            ['activo' => true]
        );

        // 3. Multi-role: orientacion.psicologica@upemor.edu.mx
        $multiUser = User::where('email', 'orientacion.psicologica@upemor.edu.mx')->first();
        if ($multiUser) {
            $multiUser->update(['rol_id' => $rolDocente->rol_id]);
            // Asociar también el rol secundario Orientador en usuario_roles
            DB::table('usuario_roles')->updateOrInsert(
                ['usuario_id' => $multiUser->usuario_id, 'rol_id' => $rolDocente->rol_id],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('usuario_roles')->updateOrInsert(
                ['usuario_id' => $multiUser->usuario_id, 'rol_id' => $rolOrientador->rol_id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // 4. Pure Docente: docente.academico@upemor.edu.mx
        $pureDocente = User::where('email', 'docente.academico@upemor.edu.mx')->first();
        if ($pureDocente) {
            $pureDocente->update(['rol_id' => $rolDocente->rol_id]);
            CitaProfesional::where('usuario_id', $pureDocente->usuario_id)->delete();
        }

        // 5. Pure Admin: admin@upemor.edu.mx
        $admin = User::where('email', 'admin@upemor.edu.mx')->first();
        if ($admin) {
            $admin->update(['rol_id' => $rolAdmin->rol_id]);
            CitaProfesional::where('usuario_id', $admin->usuario_id)->delete();
            Docente::where('usuario_id', $admin->usuario_id)->delete();
        }
    }
}
