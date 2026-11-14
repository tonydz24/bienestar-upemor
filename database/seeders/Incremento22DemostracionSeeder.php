<?php

namespace Database\Seeders;

use App\Models\BitacoraEmocional;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\DocenteAsignacion;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\LoginAcceso;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\Rol;
use App\Models\User;
use App\Services\DatosSinteticos\LimpiezaIncremento22Service;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Incremento22DemostracionSeeder extends Seeder
{
    /**
     * Poblado sintético moderado, idempotente y perfectamente trazable para demostración.
     */
    public function run(): void
    {
        // 1. Limpieza preventiva dirigida para garantizar idempotencia total
        app(LimpiezaIncremento22Service::class)->ejecutarLimpieza();

        DB::transaction(function () {
            $rolAlumno = Rol::where('nombre', 'Alumno')->firstOrFail();
            $periodo = PeriodoAcademico::where('activo', true)->first() ?? PeriodoAcademico::first();
            $grupoDefault = Grupo::where('periodo_id', $periodo?->periodo_id)->first() ?? Grupo::first();
            $profesional = CitaProfesional::first();

            $programas = ProgramaEducativo::take(4)->get();
            if ($programas->isEmpty()) {
                return;
            }

            // Programas específicos coherentes
            $progIID = ProgramaEducativo::where('clave', 'IID')->first() ?? $programas[0];
            $progIFI = ProgramaEducativo::where('clave', 'IFI')->first() ?? ($programas[1] ?? $programas[0]);
            $progIBT = ProgramaEducativo::where('clave', 'IBT')->first() ?? ($programas[2] ?? $programas[0]);

            // Datos de los 4 alumnos demostrativos con programa, generación, cuatrimestre y grupo coherentes
            $alumnosDemo = [
                [
                    'email' => 'demo.i22.alumno1@upemor.test',
                    'nombre' => 'Sofía',
                    'apellido_paterno' => 'Ramírez',
                    'apellido_materno' => 'Castillo',
                    'matricula' => 'SYNTH22_01',
                    'cuatrimestre' => 1,
                    'grupo' => 'A',
                    'programa' => $progIID,
                    'fecha_ingreso' => '2026-09-01',
                ],
                [
                    'email' => 'demo.i22.alumno2@upemor.test',
                    'nombre' => 'Mateo',
                    'apellido_paterno' => 'Vázquez',
                    'apellido_materno' => 'Delgado',
                    'matricula' => 'SYNTH22_02',
                    'cuatrimestre' => 4,
                    'grupo' => 'A',
                    'programa' => $progIID,
                    'fecha_ingreso' => '2025-09-01',
                ],
                [
                    'email' => 'demo.i22.alumno3@upemor.test',
                    'nombre' => 'Valentina',
                    'apellido_paterno' => 'Morales',
                    'apellido_materno' => 'Soto',
                    'matricula' => 'SYNTH22_03',
                    'cuatrimestre' => 4,
                    'grupo' => 'A',
                    'programa' => $progIFI,
                    'fecha_ingreso' => '2025-09-01',
                ],
                [
                    'email' => 'demo.i22.alumno4@upemor.test',
                    'nombre' => 'Leonardo',
                    'apellido_paterno' => 'Navarro',
                    'apellido_materno' => 'Guzmán',
                    'matricula' => 'SYNTH22_04',
                    'cuatrimestre' => 4,
                    'grupo' => 'B',
                    'programa' => $progIBT,
                    'fecha_ingreso' => '2025-09-01',
                ],
            ];

            $creadosEstudiantes = [];

            foreach ($alumnosDemo as $idx => $d) {
                $user = User::create([
                    'rol_id' => $rolAlumno->rol_id,
                    'nombre' => $d['nombre'],
                    'apellido_paterno' => $d['apellido_paterno'],
                    'apellido_materno' => $d['apellido_materno'],
                    'email' => $d['email'],
                    'password' => Hash::make('DemoSegura#2026'),
                    'estado' => 'ACTIVO',
                ]);

                $estudiante = new Estudiante([
                    'usuario_id' => $user->usuario_id,
                    'programa_id' => $d['programa']?->programa_id,
                    'matricula' => $d['matricula'],
                    'cuatrimestre' => $d['cuatrimestre'],
                    'grupo' => $d['grupo'],
                    'saldo_puntos' => 250,
                    'permite_visualizacion' => true,
                    'fecha_ingreso' => $d['fecha_ingreso'],
                ]);

                // Asignar plan de estudio coherente deterministamente
                $plan = $estudiante->resolverPlanEstudio();
                if ($plan) {
                    $estudiante->plan_id = $plan->plan_id;
                }
                $estudiante->save();

                // Buscar grupo específico coherente con el programa, periodo, cuatrimestre y letra
                $grupoCoherente = Grupo::where('periodo_id', $periodo?->periodo_id)
                    ->where('programa_id', $d['programa']?->programa_id)
                    ->where('cuatrimestre', $d['cuatrimestre'])
                    ->where(function ($q) use ($d) {
                        $q->where('nombre', $d['grupo'])
                          ->orWhere('nombre', 'LIKE', "%{$d['grupo']}");
                    })
                    ->first();

                if (!$grupoCoherente) {
                    $grupoCoherente = Grupo::where('periodo_id', $periodo?->periodo_id)
                        ->where('programa_id', $d['programa']?->programa_id)
                        ->first() ?? $grupoDefault;
                }

                // Inscripción formal de periodo en grupo coherente
                if ($periodo && $grupoCoherente) {
                    EstudianteInscripcion::create([
                        'estudiante_id' => $user->usuario_id,
                        'grupo_id' => $grupoCoherente->grupo_id,
                        'periodo_id' => $periodo->periodo_id,
                        'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
                    ]);
                }

                $creadosEstudiantes[] = ['user' => $user, 'estudiante' => $estudiante];
            }

            // 2. Poblado moderado de Bitácoras Emocionales (16 registros)
            $emociones = Emocion::all();
            if ($emociones->isNotEmpty()) {
                $emocionesList = $emociones->values();
                $textos = [
                    'Me sentí muy concentrado durante la preparación del proyecto de arquitectura.',
                    'Un poco de fatiga al final del laboratorio de circuitos.',
                    'Excelente trabajo en equipo en el taller práctico.',
                    'Día tranquilo con buen avance en las lecturas de investigación.',
                    'Preocupación temporal por entrega de tareas, pero logré organizarme.',
                ];

                $baseDate = Carbon::now()->subDays(20);
                for ($i = 0; $i < 16; $i++) {
                    $est = $creadosEstudiantes[$i % count($creadosEstudiantes)];
                    $emo = $emocionesList[$i % $emocionesList->count()];
                    $intensidad = ($i % 5) + 1; // escala 1-5
                    $esPrivado = ($i % 4 === 0); // 1 de cada 4 es privado

                    BitacoraEmocional::create([
                        'usuario_id' => $est['user']->usuario_id,
                        'emocion_id' => $emo->emocion_id,
                        'intensidad' => $intensidad,
                        'descripcion' => $textos[$i % count($textos)],
                        'observaciones' => $esPrivado ? 'Registro privado del alumno' : 'Actividad académica regular',
                        'fecha' => $baseDate->copy()->addDays($i)->toDateString(),
                    ]);
                }
            }

            // 3. Poblado de Citas de Orientación (6 registros)
            if ($profesional) {
                $estadosCitas = [
                    CitaOrientacion::ESTADO_FINALIZADA,
                    CitaOrientacion::ESTADO_SOLICITADA,
                    CitaOrientacion::ESTADO_CONFIRMADA,
                    CitaOrientacion::ESTADO_CANCELADA_ALUMNO,
                    CitaOrientacion::ESTADO_SOLICITADA,
                    CitaOrientacion::ESTADO_FINALIZADA,
                ];
                for ($c = 0; $c < 6; $c++) {
                    $est = $creadosEstudiantes[$c % count($creadosEstudiantes)];
                    CitaOrientacion::create([
                        'estudiante_id' => $est['user']->usuario_id,
                        'profesional_id' => $profesional->profesional_id,
                        'fecha_cita' => Carbon::now()->subDays(10 - $c)->toDateString(),
                        'hora_inicio' => sprintf('%02d:00:00', 10 + ($c % 4)),
                        'hora_fin' => sprintf('%02d:50:00', 10 + ($c % 4)),
                        'modalidad' => ($c % 2 === 0) ? CitaOrientacion::MODALIDAD_PRESENCIAL : CitaOrientacion::MODALIDAD_VIRTUAL,
                        'motivo_consulta' => 'Sesión de seguimiento académico y manejo del estrés ' . ($c + 1),
                        'estado' => $estadosCitas[$c],
                        'fecha_solicitud' => Carbon::now()->subDays(15 - $c),
                    ]);
                }
            }

            // 4. Poblado de Canjes de Recompensas (6 registros)
            $activaciones = RecompensaActivada::where('estado', RecompensaActivada::ESTADO_ACTIVA)->get();
            if ($activaciones->isNotEmpty()) {
                $estadosCanje = [
                    RecompensaCanje::ESTADO_CANJEADA,
                    RecompensaCanje::ESTADO_SOLICITADO,
                    RecompensaCanje::ESTADO_APROBADO,
                    RecompensaCanje::ESTADO_CANJEADA,
                    RecompensaCanje::ESTADO_CANCELADO,
                    RecompensaCanje::ESTADO_CANJEADA,
                ];

                for ($k = 0; $k < 6; $k++) {
                    $est = $creadosEstudiantes[$k % count($creadosEstudiantes)];
                    $act = $activaciones[$k % $activaciones->count()];

                    RecompensaCanje::create([
                        'activacion_id' => $act->activacion_id,
                        'estudiante_id' => $est['user']->usuario_id,
                        'puntos_canjeados' => $act->costo_puntos,
                        'codigo_beneficio' => 'DEMO-I22-' . strtoupper(Str::random(6)),
                        'estado' => $estadosCanje[$k],
                        'fecha_canje' => Carbon::now()->subDays(12 - $k),
                        'fecha_solicitud' => Carbon::now()->subDays(14 - $k),
                    ]);
                }
            }

            // 5. Poblado de Accesos de Auditoría (10 registros: 7 exitosos, 3 fallidos)
            $ipsDemo = LimpiezaIncremento22Service::IP_DEMO;
            for ($a = 0; $a < 10; $a++) {
                $est = $creadosEstudiantes[$a % count($creadosEstudiantes)];
                $esExitoso = ($a % 3 !== 0); // 7 exitosos, 3 fallidos

                LoginAcceso::create([
                    'usuario_id' => $esExitoso ? $est['user']->usuario_id : null,
                    'rol_id' => $esExitoso ? $rolAlumno->rol_id : null,
                    'nombre_usuario' => $esExitoso ? "{$est['user']->nombre} {$est['user']->apellido_paterno}" : 'Intento Fallido Demo',
                    'email' => $est['user']->email,
                    'rol_nombre' => 'Alumno',
                    'fecha_acceso' => Carbon::now()->subDays(8 - ($a % 7))->setTime(8 + $a, 15),
                    'ip_address' => $ipsDemo,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) BienestarDemo/22.0',
                    'exitoso' => $esExitoso,
                ]);
            }

            // 6. Poblado de Intentos Psicométricos (4 evaluaciones completas coherentes)
            $instrumento = InstrumentoEvaluacion::with(['versiones.preguntas', 'versiones.escalas', 'versiones.baremos'])->first();
            if ($instrumento && $instrumento->versiones->isNotEmpty()) {
                $version = $instrumento->versiones->first();
                $preguntas = $version->preguntas;
                $escalas = $version->escalas;
                $nivelesDemo = ['Leve', 'Moderado', 'Bajo', 'Moderado'];

                foreach ($creadosEstudiantes as $idx => $estData) {
                    EvaluacionConsentimiento::create([
                        'usuario_id' => $estData['user']->usuario_id,
                        'version_id' => $version->version_id,
                        'consentimiento_version' => '1.0',
                        'texto_consentimiento' => 'Acepto participar voluntariamente en la evaluación psicométrica diagnóstica institucional.',
                        'fecha_consentimiento' => Carbon::now()->subDays(6 - $idx),
                        'ip_registro' => $ipsDemo,
                    ]);

                    $intento = EvaluacionIntento::create([
                        'usuario_id' => $estData['user']->usuario_id,
                        'version_id' => $version->version_id,
                        'fecha_inicio' => Carbon::now()->subDays(5 - $idx)->setTime(9, 0),
                        'fecha_finalizacion' => Carbon::now()->subDays(5 - $idx)->setTime(9, 25),
                        'estado' => 'FINALIZADO',
                        'puntuacion_total' => 10 + ($idx * 4),
                        'nivel_resultado' => $nivelesDemo[$idx % 4],
                        'tiene_alerta_critica' => ($idx === 1),
                        'ip_registro' => $ipsDemo,
                    ]);

                    // Respuestas coherentes
                    foreach ($preguntas->take(5) as $pIdx => $pregunta) {
                        $opcion = $escalas->first();
                        EvaluacionRespuesta::create([
                            'intento_id' => $intento->intento_id,
                            'pregunta_id' => $pregunta->pregunta_id,
                            'escala_id' => $opcion?->escala_id,
                            'puntos_obtenidos' => $opcion?->valor_numerico ?? 2,
                        ]);
                    }
                }
            }
        });
    }
}
