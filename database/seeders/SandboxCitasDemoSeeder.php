<?php

namespace Database\Seeders;

use App\Models\CitaDisponibilidad;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SandboxCitasDemoSeeder extends Seeder
{
    /**
     * Seeder exclusivo para el entorno de demostración Sandbox.
     * Carga orientadores institucionales, horarios semanales y citas de prueba.
     */
    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();

        if ($dbName !== 'bienestar_upemor_sandbox') {
            throw new \RuntimeException("Este seeder es de uso exclusivo para el entorno sandbox. Base detectada: {$dbName}");
        }

        $rolDocente = Rol::where('nombre', 'Docente')->first();
        $rolDocenteId = $rolDocente?->rol_id ?? 2;

        // 1. Orientadora 1: Lic. Ana Karen Martínez Rojas
        $userAna = User::updateOrCreate(
            ['email' => 'orientacion.psicologica@upemor.edu.mx'],
            [
                'rol_id' => $rolDocenteId,
                'nombre' => 'Ana Karen',
                'apellido_paterno' => 'Martínez',
                'apellido_materno' => 'Rojas',
                'password' => Hash::make('Orientador2026!'),
                'telefono' => '7773297001',
                'estado' => 'ACTIVO',
            ]
        );

        Docente::updateOrCreate(
            ['usuario_id' => $userAna->usuario_id],
            [
                'numero_empleado' => 'DOC-OR-01',
                'puesto' => 'Orientadora Psicológica y Psicopedagoga',
                'especialidad' => 'Psicología Educativa y Acompañamiento',
                'cedula_profesional' => 'PSI-7890123-MOR',
                'activo' => true,
            ]
        );

        $profAna = CitaProfesional::updateOrCreate(
            ['usuario_id' => $userAna->usuario_id],
            [
                'cargo' => 'Orientadora Psicológica',
                'cedula_profesional' => 'PSI-7890123-MOR',
                'lugar_atencion' => 'Cubículo 3, Edificio de Tutorías y Orientación',
                'modalidades_permitidas' => 'AMBAS',
                'permite_citas' => true,
                'activo' => true,
            ]
        );

        // Disponibilidad de Ana: Lunes, Miércoles y Viernes de 09:00 a 13:00 (bloques de 45 min)
        foreach ([1, 3, 5] as $dia) {
            CitaDisponibilidad::updateOrCreate(
                ['profesional_id' => $profAna->profesional_id, 'dia_semana' => $dia],
                [
                    'hora_inicio' => '09:00:00',
                    'hora_fin' => '13:00:00',
                    'duracion_cita_min' => 45,
                    'modalidad' => 'AMBAS',
                    'activo' => true,
                ]
            );
        }

        // 2. Orientador 2: Mtro. Carlos Eduardo Gómez Solís
        $userCarlos = User::updateOrCreate(
            ['email' => 'orientacion.vocacional@upemor.edu.mx'],
            [
                'rol_id' => $rolDocenteId,
                'nombre' => 'Carlos Eduardo',
                'apellido_paterno' => 'Gómez',
                'apellido_materno' => 'Solís',
                'password' => Hash::make('Orientador2026!'),
                'telefono' => '7773297002',
                'estado' => 'ACTIVO',
            ]
        );

        Docente::updateOrCreate(
            ['usuario_id' => $userCarlos->usuario_id],
            [
                'numero_empleado' => 'DOC-OR-02',
                'puesto' => 'Orientador Vocacional y Tutor Institucional',
                'especialidad' => 'Orientación Educativa y Desarrollo Humano',
                'cedula_profesional' => 'PED-4567891-MOR',
                'activo' => true,
            ]
        );

        $profCarlos = CitaProfesional::updateOrCreate(
            ['usuario_id' => $userCarlos->usuario_id],
            [
                'cargo' => 'Orientador Vocacional y de Bienestar',
                'cedula_profesional' => 'PED-4567891-MOR',
                'lugar_atencion' => 'Cubículo 5, Edificio UD-2',
                'modalidades_permitidas' => 'AMBAS',
                'permite_citas' => true,
                'activo' => true,
            ]
        );

        // Disponibilidad de Carlos: Martes y Jueves de 10:00 a 14:00 (bloques de 45 min)
        foreach ([2, 4] as $dia) {
            CitaDisponibilidad::updateOrCreate(
                ['profesional_id' => $profCarlos->profesional_id, 'dia_semana' => $dia],
                [
                    'hora_inicio' => '10:00:00',
                    'hora_fin' => '14:00:00',
                    'duracion_cita_min' => 45,
                    'modalidad' => 'AMBAS',
                    'activo' => true,
                ]
            );
        }

        // Estudiantes demo
        $alumno1 = User::where('email', 'alumno.demo@upemor.edu.mx')->first();
        $alumno2 = User::where('email', 'alumno.evaluaciones@upemor.edu.mx')->first();

        if ($alumno1) {
            // Cita 1: Historial completada (hace 7 días)
            $fechaPasada = Carbon::now()->subDays(7)->toDateString();
            CitaOrientacion::firstOrCreate(
                [
                    'estudiante_id' => $alumno1->usuario_id,
                    'profesional_id' => $profAna->profesional_id,
                    'fecha_cita' => $fechaPasada,
                ],
                [
                    'hora_inicio' => '09:45:00',
                    'hora_fin' => '10:30:00',
                    'modalidad' => 'PRESENCIAL',
                    'lugar_o_enlace' => 'Cubículo 3, Edificio de Tutorías y Orientación',
                    'estado' => CitaOrientacion::ESTADO_FINALIZADA,
                    'motivo_consulta' => 'Técnicas de organización del tiempo y manejo de ansiedad ante exámenes de fin de cuatrimestre.',
                    'fecha_solicitud' => Carbon::now()->subDays(10),
                    'fecha_resolucion' => Carbon::now()->subDays(7),
                    'resuelto_por' => $userAna->usuario_id,
                    'ip_registro' => '127.0.0.1',
                ]
            );

            // Cita 2: Próxima CONFIRMADA (próximo lunes)
            $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->toDateString();
            CitaOrientacion::firstOrCreate(
                [
                    'estudiante_id' => $alumno1->usuario_id,
                    'profesional_id' => $profAna->profesional_id,
                    'fecha_cita' => $proximoLunes,
                ],
                [
                    'hora_inicio' => '10:30:00',
                    'hora_fin' => '11:15:00',
                    'modalidad' => 'PRESENCIAL',
                    'lugar_o_enlace' => 'Cubículo 3, Edificio de Tutorías y Orientación',
                    'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
                    'motivo_consulta' => 'Seguimiento a las metas de bienestar y hábitos de sueño.',
                    'fecha_solicitud' => Carbon::now()->subDays(1),
                    'fecha_resolucion' => Carbon::now(),
                    'resuelto_por' => $userAna->usuario_id,
                    'ip_registro' => '127.0.0.1',
                ]
            );
        }

        if ($alumno2) {
            // Cita 3: SOLICITADA pendiente para que el orientador la pueda confirmar/rechazar
            $proximoMiercoles = Carbon::now()->next(Carbon::WEDNESDAY)->toDateString();
            CitaOrientacion::firstOrCreate(
                [
                    'estudiante_id' => $alumno2->usuario_id,
                    'profesional_id' => $profAna->profesional_id,
                    'fecha_cita' => $proximoMiercoles,
                ],
                [
                    'hora_inicio' => '11:15:00',
                    'hora_fin' => '12:00:00',
                    'modalidad' => 'VIRTUAL',
                    'lugar_o_enlace' => 'Enlace virtual institucional (se enviará al confirmar)',
                    'estado' => CitaOrientacion::ESTADO_SOLICITADA,
                    'motivo_consulta' => 'Orientación vocacional sobre especialidades y desarrollo personal.',
                    'fecha_solicitud' => Carbon::now(),
                    'ip_registro' => '127.0.0.1',
                ]
            );
        }
    }
}

