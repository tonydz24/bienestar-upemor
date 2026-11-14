<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SandboxAcademicoConciliacionSeeder extends Seeder
{
    /**
     * Precarga académica idempotente y conciliación curricular (FN.7 y FN.5).
     * Asegura carreras, planes, materias de cuatrimestres 7 y 10, grupos A/B,
     * asignaciones docentes, inscripción de estudiantes y recompensas activadas.
     */
    public function run(): void
    {
        // 1. Programa Educativo IID / ITI
        $programa = ProgramaEducativo::where('clave', 'IID')->first()
            ?? ProgramaEducativo::where('clave', 'ITI')->first();

        if (! $programa) {
            $programa = ProgramaEducativo::create([
                'clave' => 'IID',
                'nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital',
                'activo' => true,
            ]);
        }

        // 2. Plan de Estudio Vigente
        $plan = PlanEstudio::where('programa_id', $programa->programa_id)
            ->where('es_vigente', true)
            ->first();

        if (! $plan) {
            $plan = PlanEstudio::updateOrCreate(
                [
                    'programa_id' => $programa->programa_id,
                    'codigo' => 'IID-2024',
                ],
                [
                    'nombre' => 'Plan de Estudios Oficial (IID)',
                    'version' => '1.0',
                    'anio_aprobacion' => 2024,
                    'vigencia_inicio' => '2024-01-01',
                    'estado' => 'VIGENTE',
                    'es_vigente' => true,
                    'total_cuatrimestres' => 10,
                    'descripcion' => 'Catálogo curricular oficial suministrado para Ingeniería en Tecnologías de la Información e Innovación Digital.',
                ]
            );
        }

        // 3. Periodo Académico Activo
        $periodo = PeriodoAcademico::where('activo', true)->first();
        if (! $periodo) {
            $periodo = PeriodoAcademico::updateOrCreate(
                ['clave' => '2026-1'],
                [
                    'nombre' => 'Enero - Abril 2026',
                    'fecha_inicio' => '2026-01-05',
                    'fecha_fin' => '2026-04-30',
                    'activo' => true,
                ]
            );
        }

        // 4. Asignaturas de Cuatrimestres 7 y 10
        $asig7 = Asignatura::updateOrCreate(
            [
                'programa_id' => $programa->programa_id,
                'clave' => 'IID-701',
            ],
            [
                'plan_id' => $plan->plan_id,
                'nombre' => 'Desarrollo Web Integral',
                'cuatrimestre' => 7,
                'tipo' => 'ORDINARIA',
                'activo' => true,
            ]
        );

        $asig10 = Asignatura::updateOrCreate(
            [
                'programa_id' => $programa->programa_id,
                'clave' => 'IID-1001',
            ],
            [
                'plan_id' => $plan->plan_id,
                'nombre' => 'Gestión de Proyectos de Software',
                'cuatrimestre' => 10,
                'tipo' => 'ORDINARIA',
                'activo' => true,
            ]
        );

        // 5. Grupos "A" y "B" para Cuatrimestres 7 y 10
        $grupo7A = Grupo::updateOrCreate(
            [
                'programa_id' => $programa->programa_id,
                'periodo_id' => $periodo->periodo_id,
                'cuatrimestre' => 7,
                'nombre' => 'A',
            ],
            [
                'turno' => 'MATUTINO',
                'activo' => true,
            ]
        );

        $grupo7B = Grupo::updateOrCreate(
            [
                'programa_id' => $programa->programa_id,
                'periodo_id' => $periodo->periodo_id,
                'cuatrimestre' => 7,
                'nombre' => 'B',
            ],
            [
                'turno' => 'MATUTINO',
                'activo' => true,
            ]
        );

        $grupo10A = Grupo::updateOrCreate(
            [
                'programa_id' => $programa->programa_id,
                'periodo_id' => $periodo->periodo_id,
                'cuatrimestre' => 10,
                'nombre' => 'A',
            ],
            [
                'turno' => 'MATUTINO',
                'activo' => true,
            ]
        );

        $grupo10B = Grupo::updateOrCreate(
            [
                'programa_id' => $programa->programa_id,
                'periodo_id' => $periodo->periodo_id,
                'cuatrimestre' => 10,
                'nombre' => 'B',
            ],
            [
                'turno' => 'MATUTINO',
                'activo' => true,
            ]
        );

        // 6. Docente de Prueba
        $rolDocente = Rol::where('nombre', 'Docente')->first();
        $docenteUser = User::where('email', 'docente@upemor.edu.mx')->first()
            ?? User::where('email', 'docente.academico@upemor.edu.mx')->first()
            ?? User::where('rol_id', $rolDocente?->rol_id)->first();

        if ($docenteUser) {
            Docente::updateOrCreate(
                ['usuario_id' => $docenteUser->usuario_id],
                [
                    'numero_empleado' => 'DOC-UP-2026',
                    'puesto' => 'Profesor de Asignatura',
                    'especialidad' => 'Desarrollo de Software y Tecnologías Web',
                    'activo' => true,
                ]
            );

            // Asignación aprobada para Cuatrimestre 7 en Grupo B
            $asignacion7 = DocenteAsignacion::updateOrCreate(
                [
                    'docente_id' => $docenteUser->usuario_id,
                    'asignatura_id' => $asig7->asignatura_id,
                    'grupo_id' => $grupo7B->grupo_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'activo' => true,
                ]
            );

            // Asignación aprobada para Cuatrimestre 10 en Grupo B
            $asignacion10 = DocenteAsignacion::updateOrCreate(
                [
                    'docente_id' => $docenteUser->usuario_id,
                    'asignatura_id' => $asig10->asignatura_id,
                    'grupo_id' => $grupo10B->grupo_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'activo' => true,
                ]
            );

            // 7. Catálogo de Recompensas Institucionales
            $recompensa1 = RecompensaCatalogo::updateOrCreate(
                ['nombre' => 'Décima extra en práctica de laboratorio'],
                [
                    'descripcion' => 'Bonificación de una décima (0.1) sobre la calificación de una práctica o reporte de laboratorio entregado en tiempo.',
                    'categoria' => RecompensaCatalogo::CATEGORIA_ACADEMICA,
                    'costo_puntos_base' => 50,
                    'condiciones_generales' => 'Aplicable a máximo 2 prácticas por parcial. No aplica en exámenes.',
                    'activo' => true,
                ]
            );

            $recompensa2 = RecompensaCatalogo::updateOrCreate(
                ['nombre' => 'Pase de entrega tardía 24h'],
                [
                    'descripcion' => 'Prórroga de hasta 24 horas naturales para la entrega de una tarea o proyecto sin penalización de puntaje.',
                    'categoria' => RecompensaCatalogo::CATEGORIA_TIEMPO,
                    'costo_puntos_base' => 30,
                    'condiciones_generales' => 'Notificar al docente con al menos 2 horas de anticipación a la fecha límite original.',
                    'activo' => true,
                ]
            );

            // Activación docente de las 2 recompensas en la asignatura de Cuatrimestre 7
            RecompensaActivada::updateOrCreate(
                [
                    'recompensa_id' => $recompensa1->recompensa_id,
                    'asignacion_id' => $asignacion7->asignacion_id,
                ],
                [
                    'cupo_maximo' => 15,
                    'cupo_disponible' => 15,
                    'costo_puntos' => 50,
                    'vigencia_inicio' => now()->subDays(10)->toDateString(),
                    'vigencia_fin' => now()->addDays(60)->toDateString(),
                    'condiciones_docente' => 'Válido para prácticas de código individual.',
                    'estado' => 'ACTIVA',
                ]
            );

            RecompensaActivada::updateOrCreate(
                [
                    'recompensa_id' => $recompensa2->recompensa_id,
                    'asignacion_id' => $asignacion7->asignacion_id,
                ],
                [
                    'cupo_maximo' => 10,
                    'cupo_disponible' => 10,
                    'costo_puntos' => 30,
                    'vigencia_inicio' => now()->subDays(10)->toDateString(),
                    'vigencia_fin' => now()->addDays(60)->toDateString(),
                    'condiciones_docente' => 'Solicitar mediante mensaje en el portal antes del vencimiento.',
                    'estado' => 'ACTIVA',
                ]
            );
        }

        // 8. Inscripción del Estudiante de Pruebas (Manuel Antonio / Alumno)
        $rolAlumno = Rol::where('nombre', 'Alumno')->first();
        $alumnoUser = User::where('email', 'alumno@upemor.edu.mx')->first()
            ?? User::where('email', 'admo230179@upemor.edu.mx')->first()
            ?? User::where('rol_id', $rolAlumno?->rol_id)->first();

        if ($alumnoUser) {
            $estudiante = Estudiante::where('usuario_id', $alumnoUser->usuario_id)->first();
            if ($estudiante) {
                // Asegurar saldo de puntos para pruebas de canje de recompensas
                if ((int) $estudiante->saldo_puntos < 80) {
                    $estudiante->saldo_puntos = 85;
                    $estudiante->save();
                }

                // Inscripción en Grupo B del Cuatrimestre 7
                EstudianteInscripcion::updateOrCreate(
                    [
                        'estudiante_id' => $alumnoUser->usuario_id,
                        'grupo_id' => $grupo7B->grupo_id,
                        'periodo_id' => $periodo->periodo_id,
                    ],
                    [
                        'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
                    ]
                );

                // Inscripción en la materia si la tabla existe
                if (Schema::hasTable('estudiante_asignaturas_inscripciones')) {
                    EstudianteAsignaturaInscripcion::updateOrCreate(
                        [
                            'estudiante_id' => $alumnoUser->usuario_id,
                            'asignacion_id' => $asignacion7->asignacion_id ?? 1,
                        ],
                        [
                            'estado' => 'ACTIVA',
                            'inscrito_en' => now(),
                        ]
                    );
                }
            }
        }
    }
}

