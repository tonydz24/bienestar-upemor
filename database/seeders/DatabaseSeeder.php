<?php

namespace Database\Seeders;

use App\Models\CitaDisponibilidad;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Poblado base institucional seguro para instalación inicial.
     */
    public function run(): void
    {
        $adminPassword = $this->resolveInitialPassword('INITIAL_ADMIN_PASSWORD', 'Admin2026!#Upemor');
        $docentePassword = $this->resolveInitialPassword('INITIAL_DOCENTE_PASSWORD', 'Docente2026!#Upemor');
        $alumnoPassword = $this->resolveInitialPassword('TEST_USER_PASSWORD', 'Alumno2026!#Upemor');

        // 1. Roles Institucionales (4 Roles)
        $roles = [
            ['rol_id' => 1, 'nombre' => 'Administrador', 'descripcion' => 'Administrador general del sistema', 'activo' => true],
            ['rol_id' => 2, 'nombre' => 'Docente', 'descripcion' => 'Personal docente de acompañamiento académico', 'activo' => true],
            ['rol_id' => 3, 'nombre' => 'Alumno', 'descripcion' => 'Estudiante de la universidad', 'activo' => true],
            ['rol_id' => 4, 'nombre' => 'Orientador', 'descripcion' => 'Personal de orientación y acompañamiento psicológico', 'activo' => true],
        ];

        foreach ($roles as $r) {
            DB::table('roles')->updateOrInsert(
                ['rol_id' => $r['rol_id']],
                array_merge($r, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // 2. Programas Educativos Básicos
        $this->call(ProgramasEducativosRegistroSeeder::class);

        // 3. Catálogos Base
        $this->call(EmocionesSeeder::class);
        $this->call(EvaluacionesInstrumentosSeeder::class);
        $this->call(MeditacionesSeeder::class);
        $this->call(EncuestasSeeder::class);

        // 4. Usuario Administrador Inicial
        $admin = User::updateOrCreate(
            ['email' => 'admin@upemor.edu.mx'],
            [
                'rol_id' => 1,
                'nombre' => 'Administrador',
                'apellido_paterno' => 'Sistema',
                'apellido_materno' => 'UPEMOR',
                'password' => Hash::make($adminPassword),
                'telefono' => '7771234567',
                'estado' => 'ACTIVO',
            ]
        );

        // 5. Usuario Docente Inicial
        $docente = User::updateOrCreate(
            ['email' => 'docente@upemor.edu.mx'],
            [
                'rol_id' => 2,
                'nombre' => 'Profesor',
                'apellido_paterno' => 'Titular',
                'apellido_materno' => 'UPEMOR',
                'password' => Hash::make($docentePassword),
                'telefono' => '7779876543',
                'estado' => 'ACTIVO',
            ]
        );

        Docente::updateOrCreate(
            ['usuario_id' => $docente->usuario_id],
            [
                'numero_empleado' => 'EMP-1001',
                'puesto' => 'Profesor de Asignatura',
                'especialidad' => 'Ingeniería en Software',
                'cedula_profesional' => 'CED-123456',
                'activo' => true,
            ]
        );

        // 6. Usuario Orientador / Psicólogo Inicial
        $orientador = User::updateOrCreate(
            ['email' => 'psicologa@upemor.edu.mx'],
            [
                'rol_id' => 4,
                'nombre' => 'Psicóloga',
                'apellido_paterno' => 'Orientación',
                'apellido_materno' => 'UPEMOR',
                'password' => Hash::make($docentePassword),
                'telefono' => '7773297003',
                'estado' => 'ACTIVO',
            ]
        );

        $profesional = CitaProfesional::updateOrCreate(
            ['usuario_id' => $orientador->usuario_id],
            [
                'cargo' => 'Orientadora Psicológica Institucional',
                'cedula_profesional' => 'PSI-789012-MOR',
                'lugar_atencion' => 'Cubículo de Orientación Estudiantil - Edificio A',
                'modalidades_permitidas' => 'AMBAS',
                'permite_citas' => true,
                'activo' => true,
            ]
        );

        // Bloques de disponibilidad para citas (Lunes a Jueves)
        for ($dia = 1; $dia <= 4; $dia++) {
            CitaDisponibilidad::firstOrCreate(
                [
                    'profesional_id' => $profesional->profesional_id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '09:00:00',
                    'hora_fin' => '14:00:00',
                ],
                ['activo' => true]
            );
        }

        // 7. Usuario Alumno Inicial
        $programa = DB::table('programas_educativos')->first();
        $alumno = User::updateOrCreate(
            ['email' => 'alumno@upemor.edu.mx'],
            [
                'rol_id' => 3,
                'nombre' => 'Estudiante',
                'apellido_paterno' => 'Demostrativo',
                'apellido_materno' => 'UPEMOR',
                'password' => Hash::make($alumnoPassword),
                'telefono' => '7775551234',
                'estado' => 'ACTIVO',
            ]
        );

        Estudiante::updateOrCreate(
            ['usuario_id' => $alumno->usuario_id],
            [
                'programa_id' => $programa?->programa_id ?? 1,
                'matricula' => 'ALU2026001',
                'cuatrimestre' => 1,
                'grupo' => 'A',
                'fecha_ingreso' => '2026-09-01',
                'saldo_puntos' => 50,
                'permite_visualizacion' => true,
            ]
        );
    }

    /**
     * Resuelve la contraseña inicial desde variable de entorno o usa una por defecto en ambiente local.
     */
    private function resolveInitialPassword(string $variable, string $fallback): string
    {
        $password = env($variable);

        if (is_string($password) && trim($password) !== '') {
            if (strlen($password) < 12) {
                throw new \RuntimeException("La variable {$variable} debe contener al menos 12 caracteres.");
            }
            return $password;
        }

        if (app()->environment('local', 'testing')) {
            return $fallback;
        }

        throw new \RuntimeException("La variable {$variable} debe configurarse antes de ejecutar el seeder en este entorno.");
    }
}
