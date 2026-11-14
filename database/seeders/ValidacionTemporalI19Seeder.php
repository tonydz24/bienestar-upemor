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
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder de Pruebas Sintéticas para Validación del Incremento 19.
 * REGLA INNEGOCIABLE DE SEGURIDAD:
 * Solo puede ejecutarse sobre bases temporales de validación (ej. bienestar_upemor_validacion_i19).
 * Se aborta automáticamente si detecta las bases protegidas (bienestar_upemor o bienestar_upemor_sandbox).
 */
class ValidacionTemporalI19Seeder extends Seeder
{
    public const PASSWORD_PRUEBAS = 'PruebaI19!2026';
    public const DOMINIO_PRUEBAS = 'sintetico.upemor.test';

    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();
        $protected = ['bienestar_upemor', 'bienestar_upemor_sandbox'];

        if (in_array($dbName, $protected, true)) {
            throw new \RuntimeException(
                "ABORTADO POR SEGURIDAD: El seeder ValidacionTemporalI19Seeder no tiene permitido ejecutarse sobre la base de datos protegida '{$dbName}'."
            );
        }

        // 1. Roles
        $rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['descripcion' => 'Profesor']);
        $rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Estudiante']);
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Administrador del sistema']);

        // 2. Periodo Operativo
        $periodo = PeriodoAcademico::where('clave', '2026-1')->first()
            ?? PeriodoAcademico::firstOrCreate(
                ['clave' => '2026-1'],
                [
                    'nombre' => 'Enero - Abril 2026',
                    'fecha_inicio' => '2026-01-05',
                    'fecha_fin' => '2026-04-30',
                    'activo' => true,
                ]
            );

        // 3. Programas Educativos (IID, IFI, IAS)
        $progIID = ProgramaEducativo::where('clave', 'IID')->first()
            ?? ProgramaEducativo::firstOrCreate(['clave' => 'IID'], ['nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital', 'activo' => true]);

        $progIFI = ProgramaEducativo::where('clave', 'IFI')->first()
            ?? ProgramaEducativo::firstOrCreate(['clave' => 'IFI'], ['nombre' => 'Ingeniería Financiera', 'activo' => true]);

        $progIAS = ProgramaEducativo::where('clave', 'IAS')->first()
            ?? ProgramaEducativo::firstOrCreate(['clave' => 'IAS'], ['nombre' => 'Ingeniería Ambiental y Sustentabilidad', 'activo' => true]);

        // 4. Grupos en IID (4° A, 4° B, 4° C, 7° A, 7° B)
        $grupo4A = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );
        $grupo4B = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'B'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );
        $grupo4C = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'C'],
            ['turno' => 'VESPERTINO', 'activo' => true]
        );
        $grupo7A = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo->periodo_id, 'cuatrimestre' => 7, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );
        $grupo7B = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo->periodo_id, 'cuatrimestre' => 7, 'nombre' => 'B'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        // Grupos en IFI y IAS
        $grupoIFI4A = Grupo::firstOrCreate(
            ['programa_id' => $progIFI->programa_id, 'periodo_id' => $periodo->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );
        $grupoIAS4A = Grupo::firstOrCreate(
            ['programa_id' => $progIAS->programa_id, 'periodo_id' => $periodo->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        // 5. Asignaturas Curriculares Clave (IID 4to Cuatrimestre)
        $asigIngles = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-01'], ['nombre' => 'Inglés IV', 'cuatrimestre' => 4, 'activo' => true]);
        $asigEtica = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-02'], ['nombre' => 'Ética Profesional', 'cuatrimestre' => 4, 'activo' => true]);
        $asigCalculo = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-03'], ['nombre' => 'Cálculo de Varias Variables', 'cuatrimestre' => 4, 'activo' => true]); // Escenario 13: sin docente
        $asigWeb = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-04'], ['nombre' => 'Aplicaciones Web', 'cuatrimestre' => 4, 'activo' => true]);
        $asigEstructuras = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-05'], ['nombre' => 'Estructura de Datos', 'cuatrimestre' => 4, 'activo' => true]);
        $asigMovil = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-06'], ['nombre' => 'Desarrollo de Aplicaciones Móviles', 'cuatrimestre' => 4, 'activo' => true]); // Escenario 15: pendiente de autorizar
        $asigSoftware = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-07'], ['nombre' => 'Análisis y Diseño de Software', 'cuatrimestre' => 4, 'activo' => true]); // Escenario 3: impartida por 2 docentes

        // Asignatura IFI y IAS
        $asigFinanzas = Asignatura::firstOrCreate(['programa_id' => $progIFI->programa_id, 'clave' => 'IFI-C4-04'], ['nombre' => 'Finanzas Corporativas', 'cuatrimestre' => 4, 'activo' => true]);
        $asigAmbiental = Asignatura::firstOrCreate(['programa_id' => $progIAS->programa_id, 'clave' => 'IAS-C4-04'], ['nombre' => 'Gestión de Recursos Hídricos', 'cuatrimestre' => 4, 'activo' => true]);

        // ==========================================
        // 6. CREACIÓN DE DOCENTES SINTÉTICOS (8)
        // ==========================================
        $docentesData = [
            ['num' => 'DOC-SINT-01', 'nombre' => 'Carlos', 'paterno' => 'Mendoza', 'materno' => 'Vega', 'email' => 'carlos.mendoza@' . self::DOMINIO_PRUEBAS],
            ['num' => 'DOC-SINT-02', 'nombre' => 'Laura', 'paterno' => 'Morales', 'materno' => 'Soto', 'email' => 'laura.morales@' . self::DOMINIO_PRUEBAS],
            ['num' => 'DOC-SINT-03', 'nombre' => 'Fernando', 'paterno' => 'Castro', 'materno' => 'Luna', 'email' => 'fernando.castro@' . self::DOMINIO_PRUEBAS],
            ['num' => 'DOC-SINT-04', 'nombre' => 'Patricia', 'paterno' => 'Reyes', 'materno' => 'Díaz', 'email' => 'patricia.reyes@' . self::DOMINIO_PRUEBAS],
            ['num' => 'DOC-SINT-05', 'nombre' => 'Alejandro', 'paterno' => 'Silva', 'materno' => 'Romero', 'email' => 'alejandro.silva@' . self::DOMINIO_PRUEBAS],
            ['num' => 'DOC-SINT-06', 'nombre' => 'Sofía', 'paterno' => 'Domínguez', 'materno' => 'García', 'email' => 'sofia.dominguez@' . self::DOMINIO_PRUEBAS],
            ['num' => 'DOC-SINT-07', 'nombre' => 'Víctor Hugo', 'paterno' => 'Ramos', 'materno' => 'Flores', 'email' => 'victor.ramos@' . self::DOMINIO_PRUEBAS],
            ['num' => 'DOC-SINT-08', 'nombre' => 'Gabriela', 'paterno' => 'Ortiz', 'materno' => 'Navarro', 'email' => 'gabriela.ortiz@' . self::DOMINIO_PRUEBAS],
        ];

        $docentesCreated = [];
        foreach ($docentesData as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'rol_id' => $rolDocente->rol_id,
                    'nombre' => $d['nombre'],
                    'apellido_paterno' => $d['paterno'],
                    'apellido_materno' => $d['materno'],
                    'estado' => 'ACTIVO',
                    'password' => Hash::make(self::PASSWORD_PRUEBAS),
                    'email_verified_at' => now(),
                ]
            );

            Docente::firstOrCreate(
                ['usuario_id' => $user->usuario_id],
                [
                    'numero_empleado' => $d['num'],
                    'departamento' => 'Ingeniería',
                    'activo' => true,
                ]
            );

            $docentesCreated[$d['num']] = $user;
        }

        // ==========================================
        // 7. CÁTEDRAS Y OFERTAS DOCENTES (ESCENARIOS 1, 2, 3, 4, 14, 15)
        // ==========================================
        // ESCENARIO 3: Análisis y Diseño de Software impartida por 2 docentes
        // Docente 1 (Carlos Mendoza) - Cátedra aprobada para todos los grupos (A, B, C)
        $asigCarlos4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-01']->usuario_id, 'asignatura_id' => $asigSoftware->asignatura_id, 'grupo_id' => $grupo4B->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'TODOS', 'activo' => true, 'estado' => 'APROBADA']
        );
        DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-01']->usuario_id, 'asignatura_id' => $asigSoftware->asignatura_id, 'grupo_id' => $grupo4A->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'TODOS', 'activo' => true, 'estado' => 'APROBADA']
        );

        // Docente 2 (Laura Morales) - Segundo docente en Análisis y Diseño de Software para Grupo B
        $asigLaura4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-02']->usuario_id, 'asignatura_id' => $asigSoftware->asignatura_id, 'grupo_id' => $grupo4B->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // ESCENARIO 2: Docente con varias asignaturas (Fernando Castro: Estructuras de Datos y Aplicaciones Web)
        DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-03']->usuario_id, 'asignatura_id' => $asigEstructuras->asignatura_id, 'grupo_id' => $grupo4B->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );
        DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-03']->usuario_id, 'asignatura_id' => $asigWeb->asignatura_id, 'grupo_id' => $grupo4B->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // ESCENARIO 4: Docente con alumnos de dos grupos (Patricia Reyes: Ética a 4° A y 4° B)
        $asigPatricia4A = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-04']->usuario_id, 'asignatura_id' => $asigEtica->asignatura_id, 'grupo_id' => $grupo4A->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );
        $asigPatricia4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-04']->usuario_id, 'asignatura_id' => $asigEtica->asignatura_id, 'grupo_id' => $grupo4B->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // ESCENARIO 1: Un docente con una sola asignatura autorizada (Gabriela Ortiz: Inglés IV)
        DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-08']->usuario_id, 'asignatura_id' => $asigIngles->asignatura_id, 'grupo_id' => $grupo4B->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // Asignación IFI (Alejandro Silva: Finanzas Corporativas)
        DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-05']->usuario_id, 'asignatura_id' => $asigFinanzas->asignatura_id, 'grupo_id' => $grupoIFI4A->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // Asignación IAS (Víctor Hugo Ramos: Gestión Hídrica)
        DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-07']->usuario_id, 'asignatura_id' => $asigAmbiental->asignatura_id, 'grupo_id' => $grupoIAS4A->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // ESCENARIO 15: Oferta pendiente de autorización administrativa (Sofía Domínguez: Móvil)
        DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-SINT-06']->usuario_id, 'asignatura_id' => $asigMovil->asignatura_id, 'grupo_id' => $grupo4B->grupo_id, 'periodo_id' => $periodo->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => false, 'estado' => 'PENDIENTE', 'solicitado_en' => now(), 'motivo_solicitud' => 'Propuesta curricular para impartición de desarrollo móvil en 4° B']
        );

        // ==========================================
        // 8. CREACIÓN DE ESTUDIANTES SINTÉTICOS (12)
        // ==========================================
        $alumnosData = [
            ['mat' => '2026104001', 'nom' => 'Juan', 'pat' => 'Pérez', 'matn' => 'García', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'A', 'grupo_model' => $grupo4A, 'pts' => 50, 'esc' => 'Escenario 5: Alumno 4° A'],
            ['mat' => '2026104002', 'nom' => 'Ana', 'pat' => 'Torres', 'matn' => 'López', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'A', 'grupo_model' => $grupo4A, 'pts' => 60, 'esc' => 'Alumno 4° A con solicitud aprobada'],
            ['mat' => '2026104003', 'nom' => 'Daniel', 'pat' => 'Vargas', 'matn' => 'Cruz', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'B', 'grupo_model' => $grupo4B, 'pts' => 45, 'esc' => 'Escenario 6 & 9: Alumno 4° B con solicitud pendiente'],
            ['mat' => '2026104004', 'nom' => 'Karla', 'pat' => 'Gómez', 'matn' => 'Méndez', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'B', 'grupo_model' => $grupo4B, 'pts' => 30, 'esc' => 'Escenario 11: Alumno 4° B con solicitud rechazada'],
            ['mat' => '2026104005', 'nom' => 'Roberto', 'pat' => 'Flores', 'matn' => 'Hernández', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'B', 'grupo_model' => $grupo4B, 'pts' => 70, 'esc' => 'Escenario 10: Alumno 4° B con inscripción formal'],
            ['mat' => '2026104006', 'nom' => 'Luis', 'pat' => 'Morales', 'matn' => 'Álvarez', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'C', 'grupo_model' => $grupo4C, 'pts' => 40, 'esc' => 'Alumno 4° C (sección alterna)'],
            ['mat' => '2026104007', 'nom' => 'Elena', 'pat' => 'Ríos', 'matn' => 'Sánchez', 'prog' => $progIFI, 'cuatri' => 4, 'grp' => 'A', 'grupo_model' => $grupoIFI4A, 'pts' => 55, 'esc' => 'Escenario 7: Alumno de otro programa (IFI)'],
            ['mat' => '2026104008', 'nom' => 'Mario', 'pat' => 'Luna', 'matn' => 'Castillo', 'prog' => $progIFI, 'cuatri' => 4, 'grp' => 'A', 'grupo_model' => $grupoIFI4A, 'pts' => 35, 'esc' => 'Alumno IFI 4° A'],
            ['mat' => '2026104009', 'nom' => 'Valeria', 'pat' => 'Cruz', 'matn' => 'Ruiz', 'prog' => $progIID, 'cuatri' => 7, 'grp' => 'A', 'grupo_model' => $grupo7A, 'pts' => 80, 'esc' => 'Escenario 8: Alumno de otro cuatrimestre (7° A)'],
            ['mat' => '2026104010', 'nom' => 'Javier', 'pat' => 'Salazar', 'matn' => 'Paredes', 'prog' => $progIID, 'cuatri' => 7, 'grp' => 'B', 'grupo_model' => $grupo7B, 'pts' => 65, 'esc' => 'Alumno IID 7° B'],
            ['mat' => '2026104011', 'nom' => 'Diego', 'pat' => 'Navarro', 'matn' => 'Ortiz', 'prog' => $progIAS, 'cuatri' => 4, 'grp' => 'A', 'grupo_model' => $grupoIAS4A, 'pts' => 40, 'esc' => 'Alumno IAS 4° A'],
            ['mat' => '2026104012', 'nom' => 'Paola', 'pat' => 'Méndez', 'matn' => 'Campos', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'A', 'grupo_model' => $grupo4A, 'pts' => 50, 'esc' => 'Alumno IID 4° A disponible para pruebas libres'],
        ];

        $alumnosCreated = [];
        foreach ($alumnosData as $a) {
            $email = strtolower($a['nom'] . '.' . $a['pat']) . '@' . self::DOMINIO_PRUEBAS;
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'rol_id' => $rolAlumno->rol_id,
                    'nombre' => $a['nom'],
                    'apellido_paterno' => $a['pat'],
                    'apellido_materno' => $a['matn'],
                    'estado' => 'ACTIVO',
                    'password' => Hash::make(self::PASSWORD_PRUEBAS),
                    'email_verified_at' => now(),
                ]
            );

            $estudiante = Estudiante::firstOrCreate(
                ['usuario_id' => $user->usuario_id],
                [
                    'programa_id' => $a['prog']->programa_id,
                    'matricula' => $a['mat'],
                    'cuatrimestre' => $a['cuatri'],
                    'grupo' => $a['grp'],
                    'saldo_puntos' => $a['pts'],
                ]
            );

            // Inscripción institucional canónica al grupo
            EstudianteInscripcion::firstOrCreate(
                [
                    'estudiante_id' => $user->usuario_id,
                    'grupo_id' => $a['grupo_model']->grupo_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'estado' => 'ACTIVO',
                ]
            );

            // Registrar saldo inicial en libro mayor contable si existe la tabla
            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                DB::table('estudiante_puntos_movimientos')->updateOrInsert(
                    ['estudiante_id' => $user->usuario_id, 'tipo' => 'SALDO_INICIAL'],
                    [
                        'puntos' => $a['pts'],
                        'saldo_anterior' => 0,
                        'saldo_nuevo' => $a['pts'],
                        'fecha' => now()->toDateString(),
                        'descripcion' => 'Apertura contable sintética para validación I19',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $alumnosCreated[$a['mat']] = [
                'user' => $user,
                'estudiante' => $estudiante,
                'grupo' => $a['grupo_model'],
                'data' => $a,
            ];
        }

        // ==========================================
        // 9. INSCRIPCIONES Y SOLICITUDES ESPECÍFICAS (ESCENARIOS 9, 10, 11)
        // ==========================================
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            // ESCENARIO 9: Solicitud PENDIENTE (Daniel Vargas en Análisis y Diseño con Prof. Carlos Mendoza)
            $danielUser = $alumnosCreated['2026104003']['user'];
            EstudianteAsignaturaInscripcion::firstOrCreate(
                [
                    'estudiante_id' => $danielUser->usuario_id,
                    'asignatura_id' => $asigSoftware->asignatura_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'asignacion_id' => $asigCarlos4B->asignacion_id,
                    'grupo_id' => $grupo4B->grupo_id,
                    'estado' => 'PENDIENTE',
                    'solicitado_en' => now()->subHours(2),
                    'motivo_solicitud' => 'Solicito cursar la asignatura de software en mi sección oficial B',
                ]
            );

            // ESCENARIO 10: Solicitud APROBADA (Roberto Flores en Análisis y Diseño con Prof. Laura Morales)
            $robertoUser = $alumnosCreated['2026104005']['user'];
            EstudianteAsignaturaInscripcion::firstOrCreate(
                [
                    'estudiante_id' => $robertoUser->usuario_id,
                    'asignatura_id' => $asigSoftware->asignatura_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'asignacion_id' => $asigLaura4B->asignacion_id,
                    'grupo_id' => $grupo4B->grupo_id,
                    'estado' => 'APROBADA',
                    'solicitado_en' => now()->subDays(1),
                    'motivo_solicitud' => 'Inscripción regular confirmada',
                    'resuelto_por' => $docentesCreated['DOC-SINT-02']->usuario_id,
                    'resuelto_en' => now()->subHours(10),
                    'motivo_resolucion' => 'Aprobado formalmente al grupo 4° B',
                ]
            );

            // ESCENARIO 11: Solicitud RECHAZADA (Karla Gómez)
            $karlaUser = $alumnosCreated['2026104004']['user'];
            EstudianteAsignaturaInscripcion::firstOrCreate(
                [
                    'estudiante_id' => $karlaUser->usuario_id,
                    'asignatura_id' => $asigSoftware->asignatura_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'asignacion_id' => $asigLaura4B->asignacion_id,
                    'grupo_id' => $grupo4B->grupo_id,
                    'estado' => 'RECHAZADA',
                    'solicitado_en' => now()->subDays(2),
                    'motivo_solicitud' => 'Solicitud extemporánea',
                    'resuelto_por' => $docentesCreated['DOC-SINT-02']->usuario_id,
                    'resuelto_en' => now()->subDays(1),
                    'motivo_resolucion' => 'Cupo excedido en esta sección; favor de verificar grupo asignado',
                ]
            );

            // Alumno 4° A (Ana Torres) con Ética Profesional aprobada con Prof. Patricia Reyes
            $anaUser = $alumnosCreated['2026104002']['user'];
            EstudianteAsignaturaInscripcion::firstOrCreate(
                [
                    'estudiante_id' => $anaUser->usuario_id,
                    'asignatura_id' => $asigEtica->asignatura_id,
                    'periodo_id' => $periodo->periodo_id,
                ],
                [
                    'asignacion_id' => $asigPatricia4A->asignacion_id,
                    'grupo_id' => $grupo4A->grupo_id,
                    'estado' => 'APROBADA',
                    'solicitado_en' => now()->subDays(3),
                    'resuelto_por' => $docentesCreated['DOC-SINT-04']->usuario_id,
                    'resuelto_en' => now()->subDays(2),
                    'motivo_resolucion' => 'Aceptada en Grupo 4° A',
                ]
            );
        }

        // ==========================================
        // 10. RECOMPENSAS VINCULADAS (ESCENARIOS 16 Y 17)
        // ==========================================
        $catalogo = RecompensaCatalogo::firstOrCreate(
            ['nombre' => 'Asesoría Personalizada en Modelado UML'],
            [
                'descripcion' => 'Sesión intensiva de 1 hora para revisión y refinamiento de diagramas de arquitectura de software',
                'categoria' => 'ACADEMICA',
                'costo_puntos' => 25,
                'activo' => true,
            ]
        );

        // ESCENARIO 16: Recompensa activada vinculada a la materia de Análisis y Diseño de Software para el grupo 4° B
        RecompensaActivada::firstOrCreate(
            [
                'recompensa_id' => $catalogo->recompensa_id,
                'asignacion_id' => $asigLaura4B->asignacion_id,
            ],
            [
                'cupo_maximo' => 5,
                'cupo_disponible' => 5,
                'costo_puntos' => 25,
                'vigencia_inicio' => now()->toDateString(),
                'vigencia_fin' => now()->addDays(30)->toDateString(),
                'condiciones_docente' => 'Exclusivo para alumnos con inscripción formal aprobada en Análisis y Diseño de Software (Grupo 4° B)',
                'estado' => 'ACTIVA',
            ]
        );

        // ==========================================
        // 11. GENERACIÓN DEL INVENTARIO PRIVADO DE CREDENCIALES
        // ==========================================
        $privateDir = storage_path('app/private');
        if (!File::isDirectory($privateDir)) {
            File::makeDirectory($privateDir, 0700, true);
        }

        $inventario = [
            'meta' => [
                'entorno' => 'Base MariaDB Temporal (' . $dbName . ')',
                'fecha_generacion' => now()->toIso8601String(),
                'total_docentes' => count($docentesData),
                'total_estudiantes' => count($alumnosData),
                'password_comun_seguro' => self::PASSWORD_PRUEBAS,
            ],
            'docentes' => array_map(function ($d) {
                return [
                    'rol' => 'Docente',
                    'numero_empleado' => $d['num'],
                    'nombre_completo' => $d['nombre'] . ' ' . $d['paterno'] . ' ' . $d['materno'],
                    'email' => $d['email'],
                    'password' => self::PASSWORD_PRUEBAS,
                ];
            }, $docentesData),
            'estudiantes' => array_map(function ($a) {
                $email = strtolower($a['nom'] . '.' . $a['pat']) . '@' . self::DOMINIO_PRUEBAS;
                return [
                    'rol' => 'Alumno',
                    'matricula' => $a['mat'],
                    'nombre_completo' => $a['nom'] . ' ' . $a['pat'] . ' ' . $a['matn'],
                    'email' => $email,
                    'password' => self::PASSWORD_PRUEBAS,
                    'programa' => $a['prog']->clave,
                    'cuatrimestre' => $a['cuatri'],
                    'grupo' => $a['grp'],
                    'saldo_puntos' => $a['pts'],
                    'escenario' => $a['esc'],
                ];
            }, $alumnosData),
        ];

        File::put($privateDir . '/credenciales_sinteticas_i19.json', json_encode($inventario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Versión TXT tabular para fácil lectura
        $txtOutput = "========================================================================================\n";
        $txtOutput .= "BIENESTAR UPEMOR — INVENTARIO PRIVADO DE CUENTAS SINTÉTICAS (INCREMENTO 19)\n";
        $txtOutput .= "ENTORNO TEMPORAL EXCLUSIVO: {$dbName} | FECHA: " . now('America/Mexico_City')->format('d/m/Y H:i:s') . "\n";
        $txtOutput .= "CONTRASEÑA GENERAL PARA TODAS LAS CUENTAS SINTÉTICAS: " . self::PASSWORD_PRUEBAS . "\n";
        $txtOutput .= "========================================================================================\n\n";

        $txtOutput .= "--- DOCENTES SINTÉTICOS (8) ---\n";
        $txtOutput .= sprintf("%-15s %-30s %-40s\n", "No. EMPLEADO", "NOMBRE", "CORREO ELECTRÓNICO");
        $txtOutput .= str_repeat("-", 88) . "\n";
        foreach ($inventario['docentes'] as $d) {
            $txtOutput .= sprintf("%-15s %-30s %-40s\n", $d['numero_empleado'], $d['nombre_completo'], $d['email']);
        }

        $txtOutput .= "\n--- ESTUDIANTES SINTÉTICOS (12) ---\n";
        $txtOutput .= sprintf("%-12s %-25s %-35s %-6s %-4s %-4s %-6s %-30s\n", "MATRÍCULA", "NOMBRE", "CORREO", "PROG", "CUAT", "GRP", "PTS", "ESCENARIO");
        $txtOutput .= str_repeat("-", 125) . "\n";
        foreach ($inventario['estudiantes'] as $e) {
            $txtOutput .= sprintf(
                "%-12s %-25s %-35s %-6s %-4d %-4s %-6d %-30s\n",
                $e['matricula'],
                $e['nombre_completo'],
                $e['email'],
                $e['programa'],
                $e['cuatrimestre'],
                $e['grupo'],
                $e['saldo_puntos'],
                $e['escenario']
            );
        }

        File::put($privateDir . '/credenciales_sinteticas_i19.txt', $txtOutput);
    }
}

