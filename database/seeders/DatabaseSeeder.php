<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = $this->requiredInitialPassword('INITIAL_ADMIN_PASSWORD');
        $docentePassword = $this->requiredInitialPassword('INITIAL_DOCENTE_PASSWORD');

        // 1. Insertar Roles iniciales
        DB::table('roles')->insert([
            ['rol_id' => 1, 'nombre' => 'Administrador', 'descripcion' => 'Administrador general del sistema', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['rol_id' => 2, 'nombre' => 'Docente', 'descripcion' => 'Personal de acompañamiento psicológico', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['rol_id' => 3, 'nombre' => 'Alumno', 'descripcion' => 'Estudiante de la universidad', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Insertar Programas Educativos (Carreras UPEMOR)
        DB::table('programas_educativos')->insert([
            ['programa_id' => 1, 'clave' => 'ITI', 'nombre' => 'Ingeniería en Tecnologías de la Información', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['programa_id' => 2, 'clave' => 'IFI', 'nombre' => 'Ingeniería Financiera', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['programa_id' => 3, 'clave' => 'IET', 'nombre' => 'Ingeniería en Electrónica y Telecomunicaciones', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['programa_id' => 4, 'clave' => 'IBT', 'nombre' => 'Ingeniería en Biotecnología', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['programa_id' => 5, 'clave' => 'ITE', 'nombre' => 'Ingeniería en Tecnología Ambiental', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['programa_id' => 6, 'clave' => 'INDUSTRIAL', 'nombre' => 'Ingeniería Industrial', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Insertar Usuario Administrador Inicial
        $adminId = DB::table('users')->insertGetId([
            'rol_id' => 1,
            'nombre' => 'Administrador',
            'apellido_paterno' => 'Sistema',
            'apellido_materno' => 'UPEMOR',
            'email' => 'admin@upemor.edu.mx',
            'password' => Hash::make($adminPassword),
            'telefono' => '7771234567',
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Insertar Usuario Docente de prueba
        $docenteId = DB::table('users')->insertGetId([
            'rol_id' => 2,
            'nombre' => 'Sandra',
            'apellido_paterno' => 'León',
            'apellido_materno' => 'Sosa',
            'email' => 'sandra.leon@upemor.edu.mx',
            'password' => Hash::make($docentePassword),
            'telefono' => '7779876543',
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asociar detalle en la tabla docentes
        DB::table('docentes')->insert([
            'usuario_id' => $docenteId,
            'numero_empleado' => 'EMP-1001',
            'puesto' => 'Psicóloga / Asesora',
            'especialidad' => 'Psicología Clínica',
            'cedula_profesional' => 'CED-123456',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function requiredInitialPassword(string $variable): string
    {
        $password = env($variable);

        if (! is_string($password) || trim($password) === '') {
            throw new \RuntimeException("La variable {$variable} debe configurarse antes de ejecutar el seeder.");
        }

        if (strlen($password) < 12) {
            throw new \RuntimeException("La variable {$variable} debe contener al menos 12 caracteres.");
        }

        return $password;
    }
}