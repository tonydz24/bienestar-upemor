<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SandboxAcademicoRecompensasDemoSeeder extends Seeder
{
    /**
     * Seeder exclusivo para el entorno sandbox.
     * Estructura académica (periodo, materias, grupos, inscripciones) y catálogo de recompensas.
     */
    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();

        if ($dbName !== 'bienestar_upemor_sandbox') {
            throw new \RuntimeException("Este seeder es de uso exclusivo para el entorno sandbox. Base detectada: {$dbName}");
        }

        $rolDocente = Rol::where('nombre', 'Docente')->first();
        $rolDocenteId = $rolDocente?->rol_id ?? 2;

        // 1. Periodo Académico Activo
        $periodo = PeriodoAcademico::updateOrCreate(
            ['clave' => '2026-1'],
            [
                'nombre' => 'Enero - Abril 2026',
                'fecha_inicio' => '2026-01-05',
                'fecha_fin' => '2026-04-30',
                'activo' => true,
            ]
        );

        // 2. Programa Educativo
        $programa = ProgramaEducativo::where('clave', 'IID')->orWhere('clave', 'ITI')->first();
        $programaId = $programa?->programa_id ?? 1;

        // 3. Asignaturas
        $asig1 = Asignatura::updateOrCreate(
            ['programa_id' => $programaId, 'clave' => 'IID-501'],
            [
                'nombre' => 'Desarrollo de Aplicaciones Web',
                'cuatrimestre' => 5,
                'activo' => true,
            ]
        );

        $asig2 = Asignatura::updateOrCreate(
            ['programa_id' => $programaId, 'clave' => 'IID-502'],
            [
                'nombre' => 'Ingeniería de Software y Calidad',
                'cuatrimestre' => 5,
                'activo' => true,
            ]
        );

        // 4. Grupo 5° "A"
        $grupo = Grupo::updateOrCreate(
            [
                'programa_id' => $programaId,
                'periodo_id' => $periodo->periodo_id,
                'cuatrimestre' => 5,
                'nombre' => 'A',
            ],
            [
                'turno' => 'MATUTINO',
                'activo' => true,
            ]
        );

        // 5. Docente Académico: Ing. Roberto Pérez Domínguez
        $userRoberto = User::updateOrCreate(
            ['email' => 'docente.academico@upemor.edu.mx'],
            [
                'rol_id' => $rolDocenteId,
                'nombre' => 'Roberto',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => 'Domínguez',
                'password' => Hash::make('Docente2026!'),
                'telefono' => '7773297010',
                'estado' => 'ACTIVO',
            ]
        );

        Docente::updateOrCreate(
            ['usuario_id' => $userRoberto->usuario_id],
            [
                'numero_empleado' => 'DOC-AC-10',
                'puesto' => 'Profesor de Tiempo Completo',
                'especialidad' => 'Tecnologías de la Información',
                'cedula_profesional' => 'ING-9988771-MOR',
                'activo' => true,
            ]
        );

        // Asignación de Roberto a IID-501 en Grupo 5° "A"
        $asigRoberto = DocenteAsignacion::updateOrCreate(
            [
                'docente_id' => $userRoberto->usuario_id,
                'asignatura_id' => $asig1->asignatura_id,
                'grupo_id' => $grupo->grupo_id,
                'periodo_id' => $periodo->periodo_id,
            ],
            [
                'activo' => true,
            ]
        );

        // Asignación de Ana Karen a IID-502 en Grupo 5° "A" (Multi-función: Orientadora y Docente de asignatura)
        $userAna = User::where('email', 'orientacion.psicologica@upemor.edu.mx')->first();
        if ($userAna) {
            DocenteAsignacion::updateOrCreate(
                [
                    'docente_id' => $userAna->usuario_id,
                    'asignatura_id' => $asig2->asignatura_id,
                    'grupo_id' => $grupo->grupo_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'activo' => true,
                ]
            );
        }

        // 6. Inscripciones de Estudiantes
        $alumno1 = User::where('email', 'alumno.demo@upemor.edu.mx')->first();
        $alumno2 = User::where('email', 'alumno.evaluaciones@upemor.edu.mx')->first();

        if ($alumno1) {
            EstudianteInscripcion::updateOrCreate(
                [
                    'estudiante_id' => $alumno1->usuario_id,
                    'grupo_id' => $grupo->grupo_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
                ]
            );

            // Asegurar puntos suficientes para pruebas de canje
            Estudiante::where('usuario_id', $alumno1->usuario_id)->update(['saldo_puntos' => 35]);
        }

        if ($alumno2) {
            EstudianteInscripcion::updateOrCreate(
                [
                    'estudiante_id' => $alumno2->usuario_id,
                    'grupo_id' => $grupo->grupo_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
                ]
            );
        }

        // 7. Catálogo Institucional de Recompensas
        $cat1 = RecompensaCatalogo::updateOrCreate(
            ['nombre' => 'Tolerancia de 15 minutos en entrada'],
            [
                'descripcion' => 'Permite ingresar con hasta 15 minutos de margen de tolerancia a una sesión de clase acordada previamente con el profesor.',
                'categoria' => RecompensaCatalogo::CATEGORIA_TIEMPO,
                'costo_puntos_base' => 10,
                'condiciones_generales' => 'No acumulable en días de evaluación parcial o departamental.',
                'activo' => true,
            ]
        );

        $cat2 = RecompensaCatalogo::updateOrCreate(
            ['nombre' => 'Actividad académica complementaria'],
            [
                'descripcion' => 'Oportunidad de realizar una actividad o práctica adicional para reforzar y afianzar los temas vistos en clase.',
                'categoria' => RecompensaCatalogo::CATEGORIA_ACADEMICA,
                'costo_puntos_base' => 15,
                'condiciones_generales' => 'Sujeta a los lineamientos del docente titular de la asignatura.',
                'activo' => true,
            ]
        );

        RecompensaCatalogo::updateOrCreate(
            ['nombre' => 'Participación destacada de bienestar'],
            [
                'descripcion' => 'Reconocimiento a la participación proactiva y colaboración positiva en dinámicas grupales.',
                'categoria' => RecompensaCatalogo::CATEGORIA_PARTICIPACION,
                'costo_puntos_base' => 10,
                'condiciones_generales' => 'Válido durante el periodo académico en curso.',
                'activo' => true,
            ]
        );

        RecompensaCatalogo::updateOrCreate(
            ['nombre' => 'Insignia de constancia y esfuerzo'],
            [
                'descripcion' => 'Distinción institucional por constancia en el seguimiento de hábitos de bienestar integral.',
                'categoria' => RecompensaCatalogo::CATEGORIA_RECONOCIMIENTO,
                'costo_puntos_base' => 20,
                'condiciones_generales' => 'Reconocimiento simbólico sin afectación a actas de calificación.',
                'activo' => true,
            ]
        );

        // 8. Recompensas Activadas por Roberto para IID-501
        RecompensaActivada::updateOrCreate(
            [
                'recompensa_id' => $cat1->recompensa_id,
                'asignacion_id' => $asigRoberto->asignacion_id,
            ],
            [
                'cupo_maximo' => 5,
                'cupo_disponible' => 5,
                'costo_puntos' => 10,
                'vigencia_inicio' => '2026-01-05',
                'vigencia_fin' => '2026-04-30',
                'condiciones_docente' => 'Aplica exclusivamente en sesiones de teoría los días lunes y miércoles.',
                'estado' => RecompensaActivada::ESTADO_ACTIVA,
            ]
        );

        RecompensaActivada::updateOrCreate(
            [
                'recompensa_id' => $cat2->recompensa_id,
                'asignacion_id' => $asigRoberto->asignacion_id,
            ],
            [
                'cupo_maximo' => 3,
                'cupo_disponible' => 3,
                'costo_puntos' => 15,
                'vigencia_inicio' => '2026-01-05',
                'vigencia_fin' => '2026-04-30',
                'condiciones_docente' => 'Entrega de práctica complementaria en plataforma institucional.',
                'estado' => RecompensaActivada::ESTADO_ACTIVA,
            ]
        );
    }
}

