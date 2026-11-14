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
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder de Banco Compacto de Datos Sintéticos para el Cierre del Incremento 20.1.
 * 
 * ESCENARIO DE 10 CUENTAS CONTROLADAS:
 * - 4 Docentes: cubren asignaturas únicas, múltiples, compartidas y de distintos cuatrimestres.
 * - 6 Estudiantes: cubren las 4 cohortes institucionales (1°, 4°, 7°, 10°), grupos A y B, y otra carrera (IFI).
 * 
 * SALVAGUARDA:
 * - Solo se ejecuta si no detecta bases protegidas directamente sin flag de validación.
 * - Genera manifiesto exacto de IDs para limpieza dirigida sin depender de cascadas.
 */
class ValidacionTemporalCompactaI20Seeder extends Seeder
{
    public const PASSWORD_PRUEBAS = 'PruebaCompactaI20!2026';
    public const DOMINIO_PRUEBAS = 'sintetico.upemor.test';

    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();
        $protected = ['bienestar_upemor', 'bienestar_upemor_sandbox'];

        if (in_array($dbName, $protected) && !env('FORCE_SEED_I20_COMPACTO', false) && !config('app.force_seed_i20_compacto', false)) {
            throw new \RuntimeException(
                "ALERTA CRÍTICA DE SEGURIDAD: Intento de ejecutar ValidacionTemporalCompactaI20Seeder " .
                "sobre base protegida '{$dbName}'. Operación rechazada."
            );
        }

        $now = Carbon::create(2026, 11, 10, 10, 0, 0);

        // 1. Roles institucionales
        $rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['descripcion' => 'Profesor']);
        $rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Estudiante']);

        // 2. Programas educativos oficiales
        $programaIID = ProgramaEducativo::where('clave', 'IID')->first()
            ?? ProgramaEducativo::firstOrCreate(['clave' => 'IID'], ['nombre' => 'Ingeniería en Informática', 'activo' => true]);
        $programaIFI = ProgramaEducativo::where('clave', 'IFI')->first()
            ?? ProgramaEducativo::firstOrCreate(['clave' => 'IFI'], ['nombre' => 'Ingeniería Financiera', 'activo' => true]);

        // 3. Periodo Académico Operativo Actual (Respeta el periodo operativo sin cambiarlo)
        $periodoOperativo = app(\App\Services\Academico\PeriodoOperativoService::class)->resolverPeriodoOperativo()
            ?? PeriodoAcademico::where('activo', true)->orderBy('periodo_id')->first();

        if (!$periodoOperativo) {
            $periodoOperativo = PeriodoAcademico::create([
                'clave' => '2026-3',
                'nombre' => 'Septiembre - Diciembre 2026',
                'fecha_inicio' => '2026-09-01',
                'fecha_fin' => '2026-12-18',
                'activo' => true,
            ]);
        }
        $periodo2026_3 = $periodoOperativo;

        // Cargar manifiesto previo si existe (para conservar grupos sintéticos creados en primera ejecución)
        $manifestPath = storage_path('app/private/manifiesto_datos_sinteticos_i20.json');
        $manifiestoPrevio = File::exists($manifestPath) ? (json_decode(File::get($manifestPath), true) ?: []) : [];

        // Manifiesto de rastreo para limpieza determinista (exclusivamente registros sintéticos)
        $manifiesto = [
            'fecha_generacion' => $now->toIso8601String(),
            'database' => $dbName,
            'periodo_operativo_id' => $periodo2026_3->periodo_id,
            'periodo_operativo_clave' => $periodo2026_3->clave,
            'usuarios_ids' => [],
            'docentes_ids' => [],
            'estudiantes_ids' => [],
            'grupos_ids' => $manifiestoPrevio['grupos_ids'] ?? [],
            'asignaciones_ids' => [],
            'inscripciones_grupo_ids' => [],
            'inscripciones_materia_ids' => [],
            'movimientos_puntos_ids' => [],
            'recompensas_activadas_ids' => [],
            'canjes_ids' => [],
        ];

        // 4. Obtener o crear Grupos Oficiales en el periodo operativo actual
        $gruposConfig = [
            ['prog' => $programaIID->programa_id, 'cuatri' => 1, 'nombre' => 'A', 'turno' => 'MATUTINO'],
            ['prog' => $programaIID->programa_id, 'cuatri' => 4, 'nombre' => 'A', 'turno' => 'MATUTINO'],
            ['prog' => $programaIID->programa_id, 'cuatri' => 4, 'nombre' => 'B', 'turno' => 'MATUTINO'],
            ['prog' => $programaIFI->programa_id, 'cuatri' => 4, 'nombre' => 'A', 'turno' => 'MATUTINO'],
            ['prog' => $programaIID->programa_id, 'cuatri' => 7, 'nombre' => 'A', 'turno' => 'MATUTINO'],
            ['prog' => $programaIID->programa_id, 'cuatri' => 10, 'nombre' => 'A', 'turno' => 'MATUTINO'],
        ];

        $gruposMap = [];
        foreach ($gruposConfig as $gc) {
            $g = Grupo::firstOrCreate([
                'programa_id' => $gc['prog'],
                'periodo_id' => $periodo2026_3->periodo_id,
                'cuatrimestre' => $gc['cuatri'],
                'nombre' => $gc['nombre'],
            ], [
                'turno' => $gc['turno'],
                'activo' => true,
            ]);
            $gruposMap["{$gc['prog']}_{$gc['cuatri']}_{$gc['nombre']}"] = $g;
            if ($g->wasRecentlyCreated) {
                $manifiesto['grupos_ids'][] = $g->grupo_id;
            }
        }
        $manifiesto['grupos_ids'] = array_values(array_unique($manifiesto['grupos_ids']));

        // 5. Asignaturas del plan curricular oficial (Garantizando coincidencia de cuatrimestre con alumnos)
        $asigProg1 = Asignatura::where('programa_id', $programaIID->programa_id)->where('cuatrimestre', 1)->where('nombre', 'LIKE', '%Programación%')->first()
            ?? Asignatura::where('clave', 'IID-C1-06')->first()
            ?? Asignatura::firstOrCreate(['clave' => 'IID-C1-06'], ['nombre' => 'Fundamentos de Programación', 'programa_id' => $programaIID->programa_id, 'cuatrimestre' => 1, 'activo' => true]);

        $asigWeb = Asignatura::where('programa_id', $programaIID->programa_id)->where('cuatrimestre', 4)->where('nombre', 'LIKE', '%Web%')->first()
            ?? Asignatura::where('clave', 'IID-C4-04')->first()
            ?? Asignatura::firstOrCreate(['clave' => 'IID-C4-04'], ['nombre' => 'Aplicaciones Web', 'programa_id' => $programaIID->programa_id, 'cuatrimestre' => 4, 'activo' => true]);

        $asigEstructuras = Asignatura::where('programa_id', $programaIID->programa_id)->where('cuatrimestre', 4)->where('nombre', 'LIKE', '%Estructura%')->first()
            ?? Asignatura::where('clave', 'IID-C4-05')->first()
            ?? Asignatura::firstOrCreate(['clave' => 'IID-C4-05'], ['nombre' => 'Estructura de Datos', 'programa_id' => $programaIID->programa_id, 'cuatrimestre' => 4, 'activo' => true]);

        $asigSeguridad = Asignatura::where('programa_id', $programaIID->programa_id)->where('cuatrimestre', 7)->where('nombre', 'LIKE', '%Seguridad%')->first()
            ?? Asignatura::where('clave', 'IID-C7-07')->first()
            ?? Asignatura::firstOrCreate(['clave' => 'IID-C7-07'], ['nombre' => 'Seguridad Informática', 'programa_id' => $programaIID->programa_id, 'cuatrimestre' => 7, 'activo' => true]);

        // 6. CREACIÓN DE 4 DOCENTES SINTÉTICOS (Correspondencia exacta con cuatrimestres y alcances ESPECIFICO / TODOS)
        $docentesData = [
            [
                'email' => 'docente.compacto.01@sintetico.upemor.test',
                'nombre' => 'Gabriela',
                'apellido_paterno' => 'Ortiz',
                'apellido_materno' => 'Mendoza',
                'numero_empleado' => 'DOC-CPT-01',
                // Cátedra: 1° Cuatrimestre (Fundamentos de Programación en 1° A, Alcance ESPECIFICO) -> Asociado a Alumno 1° A
                'catedras' => [
                    ['asig' => $asigProg1, 'grupo' => $gruposMap["{$programaIID->programa_id}_1_A"], 'alcance' => 'ESPECIFICO', 'estado' => 'APROBADA']
                ]
            ],
            [
                'email' => 'docente.compacto.02@sintetico.upemor.test',
                'nombre' => 'Fernando',
                'apellido_paterno' => 'Castro',
                'apellido_materno' => 'Rivas',
                'numero_empleado' => 'DOC-CPT-02',
                // Cátedra: 4° Cuatrimestre (Estructura de Datos, Alcance TODOS para 4° A y 4° B de IID)
                'catedras' => [
                    ['asig' => $asigEstructuras, 'grupo' => $gruposMap["{$programaIID->programa_id}_4_A"], 'alcance' => 'TODOS', 'estado' => 'APROBADA'],
                ]
            ],
            [
                'email' => 'docente.compacto.03@sintetico.upemor.test',
                'nombre' => 'Carlos',
                'apellido_paterno' => 'Mendoza',
                'apellido_materno' => 'Duarte',
                'numero_empleado' => 'DOC-CPT-03',
                // Cátedra: 4° Cuatrimestre (Aplicaciones Web en 4° B, Alcance ESPECIFICO) -> Asociado a Alumnos 4° B y Manuel Arellano
                'catedras' => [
                    ['asig' => $asigWeb, 'grupo' => $gruposMap["{$programaIID->programa_id}_4_B"], 'alcance' => 'ESPECIFICO', 'estado' => 'APROBADA'],
                ]
            ],
            [
                'email' => 'docente.compacto.04@sintetico.upemor.test',
                'nombre' => 'Patricia',
                'apellido_paterno' => 'Reyes',
                'apellido_materno' => 'Salgado',
                'numero_empleado' => 'DOC-CPT-04',
                // Cátedra: 7° Cuatrimestre (Seguridad Informática en 7° A, Alcance ESPECIFICO) -> Asociado a Alumno 7° A
                'catedras' => [
                    ['asig' => $asigSeguridad, 'grupo' => $gruposMap["{$programaIID->programa_id}_7_A"], 'alcance' => 'ESPECIFICO', 'estado' => 'APROBADA'],
                ]
            ],
        ];

        $asignacionesMap = [];

        foreach ($docentesData as $dd) {
            $user = User::firstOrCreate(['email' => $dd['email']], [
                'nombre' => $dd['nombre'],
                'apellido_paterno' => $dd['apellido_paterno'],
                'apellido_materno' => $dd['apellido_materno'],
                'password' => Hash::make(self::PASSWORD_PRUEBAS),
                'rol_id' => $rolDocente->rol_id,
                'estado' => 'ACTIVO',
                'email_verified_at' => $now,
            ]);
            $manifiesto['usuarios_ids'][] = $user->usuario_id;

            $docente = Docente::firstOrCreate(['usuario_id' => $user->usuario_id], [
                'numero_empleado' => $dd['numero_empleado'],
                'activo' => true,
            ]);
            $manifiesto['docentes_ids'][] = $docente->docente_id ?? $user->usuario_id;

            foreach ($dd['catedras'] as $cat) {
                $asigData = [
                    'docente_id' => $user->usuario_id,
                    'asignatura_id' => $cat['asig']->asignatura_id,
                    'grupo_id' => $cat['grupo']->grupo_id,
                    'periodo_id' => $periodo2026_3->periodo_id,
                ];

                $asigAttr = [
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                    $asigAttr['estado'] = $cat['estado'];
                }
                if (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance')) {
                    $asigAttr['modalidad_alcance'] = $cat['alcance'];
                }

                $asigDoc = DocenteAsignacion::firstOrCreate($asigData, $asigAttr);
                $manifiesto['asignaciones_ids'][] = $asigDoc->asignacion_id;
                $asignacionesMap["{$user->usuario_id}_{$cat['asig']->asignatura_id}"] = $asigDoc;
            }
        }

        // 7. CREACIÓN DE 6 ESTUDIANTES SINTÉTICOS (4 Cohortes Institucionales)
        $estudiantesData = [
            // 1° Cuatrimestre (Cohorte 2026–2029: Nuevo Ingreso)
            [
                'email' => 'alumno.compacto.1a@sintetico.upemor.test',
                'nombre' => 'Mateo',
                'apellido_paterno' => 'Ramos',
                'apellido_materno' => 'Silva',
                'matricula' => '2026101001',
                'programa_id' => $programaIID->programa_id,
                'cuatrimestre' => 1,
                'grupo' => 'A',
                'puntos' => 20,
                'grupo_obj' => $gruposMap["{$programaIID->programa_id}_1_A"],
            ],
            // 4° Cuatrimestre - Grupo A (Cohorte 2025–2028: Segregación Sección A)
            [
                'email' => 'alumno.compacto.4a@sintetico.upemor.test',
                'nombre' => 'Daniel',
                'apellido_paterno' => 'Vargas',
                'apellido_materno' => 'Cruz',
                'matricula' => '2025104001',
                'programa_id' => $programaIID->programa_id,
                'cuatrimestre' => 4,
                'grupo' => 'A',
                'puntos' => 45,
                'grupo_obj' => $gruposMap["{$programaIID->programa_id}_4_A"],
            ],
            // 4° Cuatrimestre - Grupo B (Cohorte 2025–2028: Grupo de Manuel Arellano)
            [
                'email' => 'alumno.compacto.4b@sintetico.upemor.test',
                'nombre' => 'Roberto',
                'apellido_paterno' => 'Flores',
                'apellido_materno' => 'Nava',
                'matricula' => '2025104003',
                'programa_id' => $programaIID->programa_id,
                'cuatrimestre' => 4,
                'grupo' => 'B',
                'puntos' => 60,
                'grupo_obj' => $gruposMap["{$programaIID->programa_id}_4_B"],
            ],
            // 4° Cuatrimestre - Otra Carrera (Ingeniería Financiera IFI 4° A)
            [
                'email' => 'alumno.compacto.ifi4@sintetico.upemor.test',
                'nombre' => 'Elena',
                'apellido_paterno' => 'Ríos',
                'apellido_materno' => 'Sánchez',
                'matricula' => '2025104005',
                'programa_id' => $programaIFI->programa_id,
                'cuatrimestre' => 4,
                'grupo' => 'A',
                'puntos' => 30,
                'grupo_obj' => $gruposMap["{$programaIFI->programa_id}_4_A"],
            ],
            // 7° Cuatrimestre (Cohorte 2024–2027: Cuatrimestre Avanzado)
            [
                'email' => 'alumno.compacto.7a@sintetico.upemor.test',
                'nombre' => 'Valeria',
                'apellido_paterno' => 'Cruz',
                'apellido_materno' => 'Salinas',
                'matricula' => '2024107001',
                'programa_id' => $programaIID->programa_id,
                'cuatrimestre' => 7,
                'grupo' => 'A',
                'puntos' => 70,
                'grupo_obj' => $gruposMap["{$programaIID->programa_id}_7_A"],
            ],
            // 10° Cuatrimestre (Cohorte 2023–2026: Egreso y Estadía Profesional)
            [
                'email' => 'alumno.compacto.10a@sintetico.upemor.test',
                'nombre' => 'Emiliano',
                'apellido_paterno' => 'Castro',
                'apellido_materno' => 'Parra',
                'matricula' => '2023110001',
                'programa_id' => $programaIID->programa_id,
                'cuatrimestre' => 10,
                'grupo' => 'A',
                'puntos' => 90,
                'grupo_obj' => $gruposMap["{$programaIID->programa_id}_10_A"],
            ],
        ];

        $alumnosMap = [];

        foreach ($estudiantesData as $ed) {
            $user = User::firstOrCreate(['email' => $ed['email']], [
                'nombre' => $ed['nombre'],
                'apellido_paterno' => $ed['apellido_paterno'],
                'apellido_materno' => $ed['apellido_materno'],
                'password' => Hash::make(self::PASSWORD_PRUEBAS),
                'rol_id' => $rolAlumno->rol_id,
                'estado' => 'ACTIVO',
                'email_verified_at' => $now,
            ]);
            $manifiesto['usuarios_ids'][] = $user->usuario_id;
            $alumnosMap[$ed['matricula']] = $user;

            $estudiante = Estudiante::firstOrCreate(['usuario_id' => $user->usuario_id], [
                'programa_id' => $ed['programa_id'],
                'matricula' => $ed['matricula'],
                'cuatrimestre' => $ed['cuatrimestre'],
                'grupo' => $ed['grupo'],
                'saldo_puntos' => $ed['puntos'],
            ]);
            $manifiesto['estudiantes_ids'][] = $estudiante->estudiante_id ?? $user->usuario_id;

            // Adscripción oficial de grupo (Regla de oro: 1 sola activa)
            $inscripcion = EstudianteInscripcion::firstOrCreate([
                'estudiante_id' => $user->usuario_id,
                'periodo_id' => $periodo2026_3->periodo_id,
            ], [
                'grupo_id' => $ed['grupo_obj']->grupo_id,
                'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $manifiesto['inscripciones_grupo_ids'][] = $inscripcion->inscripcion_id;

            // Apertura contable en libro mayor si la tabla existe
            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                $mov = DB::table('estudiante_puntos_movimientos')->where([
                    'estudiante_id' => $user->usuario_id,
                    'tipo' => 'SALDO_INICIAL',
                ])->first();

                if (!$mov) {
                    $movId = DB::table('estudiante_puntos_movimientos')->insertGetId([
                        'estudiante_id' => $user->usuario_id,
                        'tipo' => 'SALDO_INICIAL',
                        'puntos' => $ed['puntos'],
                        'saldo_anterior' => 0,
                        'saldo_nuevo' => $ed['puntos'],
                        'referencia_tipo' => 'SINTETICO_I20_COMPACTO',
                        'referencia_id' => null,
                        'fecha' => $now->toDateString(),
                        'descripcion' => 'Apertura contable: saldo inicial sintetico I20 compacto',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $manifiesto['movimientos_puntos_ids'][] = $movId;
                } else {
                    $manifiesto['movimientos_puntos_ids'][] = $mov->movimiento_id;
                }
            }
        }

        // 8. Solicitudes de Materia de Ejemplo (estados PENDIENTE y APROBADA)
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            $alumno4B = $alumnosMap['2025104003']; // Roberto Flores
            $asigWebDoc3 = $asignacionesMap["{$manifiesto['usuarios_ids'][2]}_{$asigWeb->asignatura_id}"] ?? null; // Prof Carlos Mendoza

            if ($asigWebDoc3) {
                $solAprobada = EstudianteAsignaturaInscripcion::firstOrCreate([
                    'estudiante_id' => $alumno4B->usuario_id,
                    'asignacion_id' => $asigWebDoc3->asignacion_id,
                ], [
                    'grupo_id' => $gruposMap["{$programaIID->programa_id}_4_B"]->grupo_id,
                    'asignatura_id' => $asigWeb->asignatura_id,
                    'periodo_id' => $periodo2026_3->periodo_id,
                    'estado' => EstudianteAsignaturaInscripcion::ESTADO_APROBADA,
                    'solicitado_en' => $now->copy()->subHours(2),
                    'resuelto_en' => $now->copy()->subHour(),
                    'resuelto_por' => $asigWebDoc3->docente_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $manifiesto['inscripciones_materia_ids'][] = $solAprobada->inscripcion_materia_id;
            }
        }

        // 9. Guardar Manifiesto y Credenciales en Almacenamiento Privado (Fuera de Git)
        $privateDir = storage_path('app/private');
        if (!File::isDirectory($privateDir)) {
            File::makeDirectory($privateDir, 0700, true);
        }

        File::put($privateDir . '/manifiesto_datos_sinteticos_i20.json', json_encode($manifiesto, JSON_PRETTY_PRINT));

        $credsText = "BIENESTAR UPEMOR — CREDENCIALES BANCO COMPACTO I20.1\n";
        $credsText .= "Fecha: " . $now->toDateTimeString() . "\n";
        $credsText .= "Contraseña única: " . self::PASSWORD_PRUEBAS . "\n\n";
        $credsText .= "DOCENTES (4):\n";
        foreach ($docentesData as $d) {
            $credsText .= "- {$d['nombre']} {$d['apellido_paterno']}: {$d['email']}\n";
        }
        $credsText .= "\nESTUDIANTES (6):\n";
        foreach ($estudiantesData as $e) {
            $credsText .= "- {$e['nombre']} {$e['apellido_paterno']} ({$e['cuatrimestre']}° {$e['grupo']} - Matrícula: {$e['matricula']}): {$e['email']}\n";
        }

        File::put($privateDir . '/credenciales_sinteticas_compactas_i20.txt', $credsText);
    }

    /**
     * Limpieza Atómica y Determinista del Banco Sintético (Sin depender de borrado en cascada ni patrones de correo).
     */
    public static function purgarBancoSintetico(): array
    {
        $manifestPath = storage_path('app/private/manifiesto_datos_sinteticos_i20.json');
        if (!File::exists($manifestPath)) {
            return ['success' => false, 'error' => 'No se encontró el manifiesto de datos sintéticos.'];
        }

        $m = json_decode(File::get($manifestPath), true);
        $resumen = [];

        DB::transaction(function () use ($m, &$resumen) {
            // 1. Canjes y Recompensas
            if (Schema::hasTable('recompensas_canjes') && !empty($m['usuarios_ids'])) {
                $resumen['canjes'] = DB::table('recompensas_canjes')->whereIn('estudiante_id', $m['usuarios_ids'])->delete();
            }
            if (Schema::hasTable('recompensas_activadas')) {
                if (!empty($m['recompensas_activadas_ids'])) {
                    $resumen['recompensas_activadas'] = DB::table('recompensas_activadas')->whereIn('activacion_id', $m['recompensas_activadas_ids'])->delete();
                } elseif (!empty($m['asignaciones_ids'])) {
                    $resumen['recompensas_activadas'] = DB::table('recompensas_activadas')->whereIn('asignacion_id', $m['asignaciones_ids'])->delete();
                }
            }

            // 2. Movimientos de Puntos
            if (Schema::hasTable('estudiante_puntos_movimientos') && !empty($m['usuarios_ids'])) {
                $resumen['puntos_movimientos'] = DB::table('estudiante_puntos_movimientos')->whereIn('estudiante_id', $m['usuarios_ids'])->delete();
            }

            // 3. Inscripciones de Materia y de Grupo
            if (Schema::hasTable('estudiante_asignatura_inscripciones') && !empty($m['usuarios_ids'])) {
                $resumen['inscripciones_materia'] = DB::table('estudiante_asignatura_inscripciones')->whereIn('estudiante_id', $m['usuarios_ids'])->delete();
            }
            if (Schema::hasTable('estudiante_inscripciones') && !empty($m['usuarios_ids'])) {
                $resumen['inscripciones_grupo'] = DB::table('estudiante_inscripciones')->whereIn('estudiante_id', $m['usuarios_ids'])->delete();
            }

            // 4. Asignaciones Docentes
            if (Schema::hasTable('docente_asignaciones') && !empty($m['usuarios_ids'])) {
                $resumen['docente_asignaciones'] = DB::table('docente_asignaciones')->whereIn('docente_id', $m['usuarios_ids'])->delete();
            }

            // 5. Perfiles Estudiante y Docente
            if (Schema::hasTable('estudiantes') && !empty($m['usuarios_ids'])) {
                $resumen['estudiantes'] = DB::table('estudiantes')->whereIn('usuario_id', $m['usuarios_ids'])->delete();
            }
            if (Schema::hasTable('docentes') && !empty($m['usuarios_ids'])) {
                $resumen['docentes'] = DB::table('docentes')->whereIn('usuario_id', $m['usuarios_ids'])->delete();
            }

            // 6. Grupos sintéticos creados si están vacíos
            if (Schema::hasTable('grupos') && !empty($m['grupos_ids'])) {
                $resumen['grupos'] = DB::table('grupos')->whereIn('grupo_id', $m['grupos_ids'])
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('estudiante_inscripciones')->whereColumn('estudiante_inscripciones.grupo_id', 'grupos.grupo_id');
                    })
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('docente_asignaciones')->whereColumn('docente_asignaciones.grupo_id', 'grupos.grupo_id');
                    })
                    ->delete();
            }

            // 7. Usuarios de la tabla users
            if (!empty($m['usuarios_ids'])) {
                $resumen['users'] = DB::table('users')->whereIn('usuario_id', $m['usuarios_ids'])->delete();
            }
        });

        File::delete($manifestPath);
        File::delete(storage_path('app/private/credenciales_sinteticas_compactas_i20.txt'));

        return ['success' => true, 'resumen' => $resumen];
    }
}
