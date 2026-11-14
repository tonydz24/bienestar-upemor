<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\BitacoraEmocional;
use App\Models\CitaOrientacion;
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
 * Seeder de Pruebas Sintéticas para Cierre y Validación del Incremento 20.
 *
 * REGLA INNEGOCIABLE DE PRESERVACIÓN Y SEGURIDAD:
 * Solo puede ejecutarse sobre bases temporales aisladas de validación (ej. bienestar_upemor_validacion_i20).
 * Se aborta automáticamente si detecta las bases protegidas (bienestar_upemor o bienestar_upemor_sandbox).
 */
class ValidacionTemporalI20Seeder extends Seeder
{
    public const PASSWORD_PRUEBAS = 'PruebaI20!2026';
    public const DOMINIO_PRUEBAS = 'sintetico.upemor.test';

    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();
        $protected = ['bienestar_upemor', 'bienestar_upemor_sandbox'];

        if (in_array($dbName, $protected, true)) {
            throw new \RuntimeException(
                "ABORTADO POR SEGURIDAD: El seeder ValidacionTemporalI20Seeder no tiene permitido ejecutarse sobre la base de datos protegida '{$dbName}'."
            );
        }

        // 1. Roles Institucionales
        $rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['descripcion' => 'Profesor']);
        $rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Estudiante']);
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Administrador del sistema']);
        $rolOrientador = Rol::firstOrCreate(['nombre' => 'Orientador'], ['descripcion' => 'Orientador / Psicólogo']);

        // 2. Periodo Operativo 2026-3 (Septiembre - Diciembre 2026, vigencia por calendario a Nov 2026)
        $periodo2026_3 = PeriodoAcademico::where('clave', '2026-3')->first()
            ?? PeriodoAcademico::firstOrCreate(
                ['clave' => '2026-3'],
                [
                    'nombre' => 'Septiembre - Diciembre 2026',
                    'fecha_inicio' => '2026-09-01',
                    'fecha_fin' => '2027-01-04',
                    'activo' => true,
                ]
            );

        // 3. Programas Educativos (IID, IFI, IAS)
        $progIID = ProgramaEducativo::where('clave', 'IID')->first()
            ?? ProgramaEducativo::firstOrCreate(
                ['clave' => 'IID'],
                ['nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital', 'activo' => true]
            );

        $progIFI = ProgramaEducativo::where('clave', 'IFI')->first()
            ?? ProgramaEducativo::firstOrCreate(
                ['clave' => 'IFI'],
                ['nombre' => 'Ingeniería Financiera', 'activo' => true]
            );

        $progIAS = ProgramaEducativo::where('clave', 'IAS')->first()
            ?? ProgramaEducativo::firstOrCreate(
                ['clave' => 'IAS'],
                ['nombre' => 'Ingeniería Ambiental y Sustentabilidad', 'activo' => true]
            );

        // 4. Grupos en Periodo 2026-3 cubriendo las 4 cohortes
        // COHORTE 2026–2029: 1° Cuatrimestre
        $grupo1A_IID = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 1, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        // COHORTE 2025–2028: 4° Cuatrimestre (Regular, caso Manuel Antonio)
        $grupo4A_IID = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );
        $grupo4B_IID = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'B'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );
        $grupo4C_IID = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'C'],
            ['turno' => 'VESPERTINO', 'activo' => true]
        );
        $grupo4A_IFI = Grupo::firstOrCreate(
            ['programa_id' => $progIFI->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );
        $grupo4A_IAS = Grupo::firstOrCreate(
            ['programa_id' => $progIAS->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 4, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        // COHORTE 2024–2027: 7° Cuatrimestre
        $grupo7A_IID = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 7, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );
        $grupo7B_IID = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 7, 'nombre' => 'B'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        // COHORTE 2023–2026: 10° Cuatrimestre (Egresando Diciembre 2026)
        $grupo10A_IID = Grupo::firstOrCreate(
            ['programa_id' => $progIID->programa_id, 'periodo_id' => $periodo2026_3->periodo_id, 'cuatrimestre' => 10, 'nombre' => 'A'],
            ['turno' => 'MATUTINO', 'activo' => true]
        );

        // 5. Asignaturas Curriculares
        // 1° Cuatri
        $asigProg1 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C1-01'], ['nombre' => 'Fundamentos de Programación', 'cuatrimestre' => 1, 'activo' => true]);
        $asigAlgebra1 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C1-02'], ['nombre' => 'Álgebra Lineal', 'cuatrimestre' => 1, 'activo' => true]);

        // 4° Cuatri
        $asigIngles4 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-01'], ['nombre' => 'Inglés IV', 'cuatrimestre' => 4, 'activo' => true]);
        $asigEtica4 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-02'], ['nombre' => 'Ética Profesional', 'cuatrimestre' => 4, 'activo' => true]);
        $asigCalculo4 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-03'], ['nombre' => 'Cálculo de Varias Variables', 'cuatrimestre' => 4, 'activo' => true]); // Sin docente (Sección 6.3)
        $asigWeb4 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-04'], ['nombre' => 'Aplicaciones Web', 'cuatrimestre' => 4, 'activo' => true]); // 2 docentes
        $asigEstructuras4 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-05'], ['nombre' => 'Estructura de Datos', 'cuatrimestre' => 4, 'activo' => true]);
        $asigMovil4 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-06'], ['nombre' => 'Desarrollo de Aplicaciones Móviles', 'cuatrimestre' => 4, 'activo' => true]); // Solicitud pendiente
        $asigSoftware4 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C4-07'], ['nombre' => 'Análisis y Diseño de Software', 'cuatrimestre' => 4, 'activo' => true]);

        // 7° Cuatri
        $asigIA7 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C7-01'], ['nombre' => 'Inteligencia Artificial', 'cuatrimestre' => 7, 'activo' => true]);
        $asigSeguridad7 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C7-02'], ['nombre' => 'Seguridad Informática', 'cuatrimestre' => 7, 'activo' => true]);

        // 10° Cuatri
        $asigEstadia10 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C10-01'], ['nombre' => 'Estadía Profesional y Titulación', 'cuatrimestre' => 10, 'activo' => true]);
        $asigProyectos10 = Asignatura::firstOrCreate(['programa_id' => $progIID->programa_id, 'clave' => 'IID-C10-02'], ['nombre' => 'Dirección y Gestión de Proyectos Tecnológicos', 'cuatrimestre' => 10, 'activo' => true]);

        // Otras Carreras (4°)
        $asigFinanzasIFI = Asignatura::firstOrCreate(['programa_id' => $progIFI->programa_id, 'clave' => 'IFI-C4-04'], ['nombre' => 'Finanzas Corporativas', 'cuatrimestre' => 4, 'activo' => true]);
        $asigAmbientalIAS = Asignatura::firstOrCreate(['programa_id' => $progIAS->programa_id, 'clave' => 'IAS-C4-04'], ['nombre' => 'Gestión de Recursos Hídricos', 'cuatrimestre' => 4, 'activo' => true]);

        // ==========================================
        // 6. CREACIÓN DE DOCENTES SINTÉTICOS (8)
        // ==========================================
        $docentesData = [
            ['num' => 'DOC-I20-01', 'nombre' => 'Carlos', 'paterno' => 'Mendoza', 'materno' => 'Vega', 'email' => 'carlos.mendoza@' . self::DOMINIO_PRUEBAS, 'especialidad' => 'Aplicaciones Web & Software'],
            ['num' => 'DOC-I20-02', 'nombre' => 'Laura', 'paterno' => 'Morales', 'materno' => 'Soto', 'email' => 'laura.morales@' . self::DOMINIO_PRUEBAS, 'especialidad' => 'Aplicaciones Web (2do Docente)'],
            ['num' => 'DOC-I20-03', 'nombre' => 'Fernando', 'paterno' => 'Castro', 'materno' => 'Luna', 'email' => 'fernando.castro@' . self::DOMINIO_PRUEBAS, 'especialidad' => 'Estructura de Datos & Inglés IV'],
            ['num' => 'DOC-I20-04', 'nombre' => 'Patricia', 'paterno' => 'Reyes', 'materno' => 'Díaz', 'email' => 'patricia.reyes@' . self::DOMINIO_PRUEBAS, 'especialidad' => 'Ética Profesional (4° A y 4° B)'],
            ['num' => 'DOC-I20-05', 'nombre' => 'Alejandro', 'paterno' => 'Silva', 'materno' => 'Romero', 'email' => 'alejandro.silva@' . self::DOMINIO_PRUEBAS, 'especialidad' => 'Inteligencia Artificial (7° Cuatri)'],
            ['num' => 'DOC-I20-06', 'nombre' => 'Sofía', 'paterno' => 'Domínguez', 'materno' => 'García', 'email' => 'sofia.dominguez@' . self::DOMINIO_PRUEBAS, 'especialidad' => 'Desarrollo Móvil (Solicitud Pendiente)'],
            ['num' => 'DOC-I20-07', 'nombre' => 'Víctor Hugo', 'paterno' => 'Ramos', 'materno' => 'Flores', 'email' => 'victor.ramos@' . self::DOMINIO_PRUEBAS, 'especialidad' => 'Fundamentos de Programación (1° Cuatri)'],
            ['num' => 'DOC-I20-08', 'nombre' => 'Gabriela', 'paterno' => 'Ortiz', 'materno' => 'Navarro', 'email' => 'gabriela.ortiz@' . self::DOMINIO_PRUEBAS, 'especialidad' => 'Estadía Profesional (10° Cuatri)'],
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
        // 7. OFERTAS Y CÁTEDRAS EN PERIODO 2026-3
        // ==========================================
        // 1er Cuatrimestre (Docente 7: Víctor Ramos -> Fundamentos de Programación para 1° A)
        $asigVictor1A = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-07']->usuario_id, 'asignatura_id' => $asigProg1->asignatura_id, 'grupo_id' => $grupo1A_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // 4to Cuatrimestre:
        // Cátedra con 2 docentes en Aplicaciones Web:
        // Docente 1 (Carlos Mendoza): Aplicaciones Web con alcance TODOS (A, B, C)
        $asigCarlosWeb4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-01']->usuario_id, 'asignatura_id' => $asigWeb4->asignatura_id, 'grupo_id' => $grupo4B_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'TODOS', 'activo' => true, 'estado' => 'APROBADA']
        );
        // Docente 2 (Laura Morales): Aplicaciones Web específica para Grupo 4° B
        $asigLauraWeb4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-02']->usuario_id, 'asignatura_id' => $asigWeb4->asignatura_id, 'grupo_id' => $grupo4B_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // Docente 1 (Carlos Mendoza): Análisis y Diseño de Software para 4° B
        $asigCarlosSoftware4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-01']->usuario_id, 'asignatura_id' => $asigSoftware4->asignatura_id, 'grupo_id' => $grupo4B_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // Docente 3 (Fernando Castro): Estructuras de Datos e Inglés IV para 4° B
        $asigFernandoEstructuras4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-03']->usuario_id, 'asignatura_id' => $asigEstructuras4->asignatura_id, 'grupo_id' => $grupo4B_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );
        $asigFernandoIngles4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-03']->usuario_id, 'asignatura_id' => $asigIngles4->asignatura_id, 'grupo_id' => $grupo4B_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // Docente 4 (Patricia Reyes): Ética Profesional para 4° A y 4° B
        $asigPatriciaEtica4A = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-04']->usuario_id, 'asignatura_id' => $asigEtica4->asignatura_id, 'grupo_id' => $grupo4A_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );
        $asigPatriciaEtica4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-04']->usuario_id, 'asignatura_id' => $asigEtica4->asignatura_id, 'grupo_id' => $grupo4B_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // Docente 6 (Sofía Domínguez): Solicitud PENDIENTE para Móvil 4° B
        $asigSofiaMovil4B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-06']->usuario_id, 'asignatura_id' => $asigMovil4->asignatura_id, 'grupo_id' => $grupo4B_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => false, 'estado' => 'PENDIENTE', 'solicitado_en' => now(), 'motivo_solicitud' => 'Propuesta docente para desarrollo móvil en 4° B']
        );

        // 7mo Cuatrimestre (Docente 5: Alejandro Silva -> Inteligencia Artificial para 7° A y 7° B)
        $asigAlejandroIA7A = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-05']->usuario_id, 'asignatura_id' => $asigIA7->asignatura_id, 'grupo_id' => $grupo7A_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );
        $asigAlejandroIA7B = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-05']->usuario_id, 'asignatura_id' => $asigIA7->asignatura_id, 'grupo_id' => $grupo7B_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // 10mo Cuatrimestre (Docente 8: Gabriela Ortiz -> Estadía Profesional para 10° A)
        $asigGabrielaEstadia10A = DocenteAsignacion::firstOrCreate(
            ['docente_id' => $docentesCreated['DOC-I20-08']->usuario_id, 'asignatura_id' => $asigEstadia10->asignatura_id, 'grupo_id' => $grupo10A_IID->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
            ['modalidad_alcance' => 'ESPECIFICO', 'activo' => true, 'estado' => 'APROBADA']
        );

        // ==========================================
        // 8. CREACIÓN DE ESTUDIANTES SINTÉTICOS (12)
        // Cubriendo las 4 Cohortes Institucionales
        // ==========================================
        $alumnosData = [
            // COHORTE 2026–2029 (1° Cuatrimestre)
            ['mat' => '2026101001', 'nom' => 'Mateo', 'pat' => 'Luna', 'matn' => 'García', 'prog' => $progIID, 'cuatri' => 1, 'grp' => 'A', 'grupo_model' => $grupo1A_IID, 'pts' => 20, 'ingreso' => '2026-09-01', 'esc' => 'Generación 2026-2029: 1° Cuatri IID'],
            ['mat' => '2026101002', 'nom' => 'Valeria', 'pat' => 'Soto', 'matn' => 'Pérez', 'prog' => $progIID, 'cuatri' => 1, 'grp' => 'A', 'grupo_model' => $grupo1A_IID, 'pts' => 25, 'ingreso' => '2026-09-01', 'esc' => 'Generación 2026-2029: 1° Cuatri IID'],

            // COHORTE 2025–2028 (4° Cuatrimestre - Regular, Homólogo Manuel Antonio)
            ['mat' => '2025104001', 'nom' => 'Manuel', 'pat' => 'Arellano', 'matn' => 'Sintético', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'B', 'grupo_model' => $grupo4B_IID, 'pts' => 50, 'ingreso' => '2025-09-01', 'esc' => 'Generación 2025-2028: Homólogo Manuel Antonio (4° B IID)'],
            ['mat' => '2025104002', 'nom' => 'Sofía', 'pat' => 'Navarro', 'matn' => 'Torres', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'A', 'grupo_model' => $grupo4A_IID, 'pts' => 45, 'ingreso' => '2025-09-01', 'esc' => 'Generación 2025-2028: Alumna 4° A IID'],
            ['mat' => '2025104003', 'nom' => 'Diego', 'pat' => 'Vargas', 'matn' => 'Cruz', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'B', 'grupo_model' => $grupo4B_IID, 'pts' => 65, 'ingreso' => '2025-09-01', 'esc' => 'Generación 2025-2028: Alumno 4° B con materias aprobadas y recompensas'],
            ['mat' => '2025104004', 'nom' => 'Camila', 'pat' => 'Ramos', 'matn' => 'Méndez', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'C', 'grupo_model' => $grupo4C_IID, 'pts' => 35, 'ingreso' => '2025-09-01', 'esc' => 'Generación 2025-2028: Alumna 4° C Vespertino'],
            ['mat' => '2025104005', 'nom' => 'Rodrigo', 'pat' => 'Morales', 'matn' => 'Álvarez', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'B', 'grupo_model' => $grupo4B_IID, 'pts' => 30, 'ingreso' => '2025-09-01', 'esc' => 'Generación 2025-2028: Alumno 4° B con solicitud de inscripción PENDIENTE'],
            ['mat' => '2025104006', 'nom' => 'Lucía', 'pat' => 'Méndez', 'matn' => 'Hernández', 'prog' => $progIID, 'cuatri' => 4, 'grp' => 'B', 'grupo_model' => $grupo4B_IID, 'pts' => 20, 'ingreso' => '2025-09-01', 'esc' => 'Generación 2025-2028: Alumna 4° B con solicitud RECHAZADA'],

            // COHORTE 2024–2027 (7° Cuatrimestre)
            ['mat' => '2024107001', 'nom' => 'Emiliano', 'pat' => 'Castro', 'matn' => 'Ruiz', 'prog' => $progIID, 'cuatri' => 7, 'grp' => 'A', 'grupo_model' => $grupo7A_IID, 'pts' => 75, 'ingreso' => '2024-09-01', 'esc' => 'Generación 2024-2027: 7° A IID'],
            ['mat' => '2024107002', 'nom' => 'Daniela', 'pat' => 'Fuentes', 'matn' => 'Paredes', 'prog' => $progIID, 'cuatri' => 7, 'grp' => 'B', 'grupo_model' => $grupo7B_IID, 'pts' => 80, 'ingreso' => '2024-09-01', 'esc' => 'Generación 2024-2027: 7° B IID'],

            // COHORTE 2023–2026 (10° Cuatrimestre - Egresando Diciembre 2026)
            ['mat' => '2023110001', 'nom' => 'Andrés', 'pat' => 'Guadarrama', 'matn' => 'Ortiz', 'prog' => $progIID, 'cuatri' => 10, 'grp' => 'A', 'grupo_model' => $grupo10A_IID, 'pts' => 95, 'ingreso' => '2023-09-01', 'esc' => 'Generación 2023-2026: 10° A IID (Egresando en Diciembre 2026)'],

            // OTRA CARRERA (Restricción curricular cruzada)
            ['mat' => '2025204001', 'nom' => 'Mariana', 'pat' => 'Peña', 'matn' => 'Campos', 'prog' => $progIFI, 'cuatri' => 4, 'grp' => 'A', 'grupo_model' => $grupo4A_IFI, 'pts' => 40, 'ingreso' => '2025-09-01', 'esc' => 'Carrera Alterna: Ingeniería Financiera (IFI) - Bloqueada para IID'],
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
                    'fecha_ingreso' => $a['ingreso'],
                    'saldo_puntos' => $a['pts'],
                    'permite_visualizacion' => true,
                ]
            );

            // Inscripción oficial al grupo
            EstudianteInscripcion::firstOrCreate(
                ['estudiante_id' => $user->usuario_id, 'grupo_id' => $a['grupo_model']->grupo_id, 'periodo_id' => $periodo2026_3->periodo_id],
                ['estado' => EstudianteInscripcion::ESTADO_ACTIVO]
            );

            // Movimiento de apertura contable de puntos en el libro mayor
            if (Schema::hasTable('estudiante_puntos_movimientos')) {
                DB::table('estudiante_puntos_movimientos')->updateOrInsert(
                    ['estudiante_id' => $user->usuario_id, 'tipo' => 'SALDO_INICIAL'],
                    [
                        'puntos' => $a['pts'],
                        'saldo_anterior' => 0,
                        'saldo_nuevo' => $a['pts'],
                        'fecha' => now()->toDateString(),
                        'descripcion' => 'Apertura contable de puntos para pruebas sintéticas I20',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $alumnosCreated[$a['mat']] = [
                'user' => $user,
                'estudiante' => $estudiante,
                'data' => $a,
            ];
        }

        // ==========================================
        // 9. INSCRIPCIONES A MATERIAS
        // ==========================================
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            // Caso Diego Vargas (2025104003) - 4° B IID: Inscrito en Aplicaciones Web (con Carlos) y Análisis y Diseño de Software
            EstudianteAsignaturaInscripcion::updateOrInsert(
                ['estudiante_id' => $alumnosCreated['2025104003']['user']->usuario_id, 'asignacion_id' => $asigCarlosWeb4B->asignacion_id],
                [
                    'grupo_id' => $grupo4B_IID->grupo_id,
                    'asignatura_id' => $asigWeb4->asignatura_id,
                    'periodo_id' => $periodo2026_3->periodo_id,
                    'estado' => EstudianteAsignaturaInscripcion::ESTADO_APROBADA,
                    'solicitado_en' => now()->subDays(3),
                    'resuelto_en' => now()->subDays(2),
                    'resuelto_por' => $docentesCreated['DOC-I20-01']->usuario_id,
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(2),
                ]
            );

            // Caso Rodrigo Morales (2025104005) - 4° B IID: Solicitud PENDIENTE para Estructura de Datos
            EstudianteAsignaturaInscripcion::updateOrInsert(
                ['estudiante_id' => $alumnosCreated['2025104005']['user']->usuario_id, 'asignacion_id' => $asigFernandoEstructuras4B->asignacion_id],
                [
                    'grupo_id' => $grupo4B_IID->grupo_id,
                    'asignatura_id' => $asigEstructuras4->asignatura_id,
                    'periodo_id' => $periodo2026_3->periodo_id,
                    'estado' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
                    'solicitado_en' => now()->subHours(5),
                    'motivo_solicitud' => 'Solicitud de incorporación al curso de estructuras de datos',
                    'created_at' => now()->subHours(5),
                    'updated_at' => now()->subHours(5),
                ]
            );

            // Caso Lucía Méndez (2025104006) - 4° B IID: Solicitud RECHAZADA para Aplicaciones Web
            EstudianteAsignaturaInscripcion::updateOrInsert(
                ['estudiante_id' => $alumnosCreated['2025104006']['user']->usuario_id, 'asignacion_id' => $asigLauraWeb4B->asignacion_id],
                [
                    'grupo_id' => $grupo4B_IID->grupo_id,
                    'asignatura_id' => $asigWeb4->asignatura_id,
                    'periodo_id' => $periodo2026_3->periodo_id,
                    'estado' => EstudianteAsignaturaInscripcion::ESTADO_RECHAZADA,
                    'solicitado_en' => now()->subDays(4),
                    'resuelto_en' => now()->subDays(3),
                    'resuelto_por' => $docentesCreated['DOC-I20-02']->usuario_id,
                    'motivo_resolucion' => 'Cupo límite del grupo alcanzado en esta sección.',
                    'created_at' => now()->subDays(4),
                    'updated_at' => now()->subDays(3),
                ]
            );
        }

        // ==========================================
        // 10. RECOMPENSAS Y CANJES AUDITADOS
        // ==========================================
        $rec1 = RecompensaCatalogo::firstOrCreate(
            ['nombre' => 'Puntos Extra en Examen Parcial'],
            [
                'descripcion' => 'Otorga hasta 1 punto extra sobre la calificación del examen parcial correspondiente.',
                'categoria' => RecompensaCatalogo::CATEGORIA_ACADEMICA,
                'costo_puntos_base' => 25,
                'estado_propuesta' => RecompensaCatalogo::PROPUESTA_INSTITUCIONAL,
                'activo' => true,
            ]
        );
        $rec2 = RecompensaCatalogo::firstOrCreate(
            ['nombre' => 'Asesoría Académica 1 a 1'],
            [
                'descripcion' => 'Sesión exclusiva de tutoría de 45 minutos con el docente titular.',
                'categoria' => RecompensaCatalogo::CATEGORIA_ACADEMICA,
                'costo_puntos_base' => 15,
                'estado_propuesta' => RecompensaCatalogo::PROPUESTA_INSTITUCIONAL,
                'activo' => true,
            ]
        );

        // Docente 1 activa recompensa en 4° B
        $activada1 = RecompensaActivada::firstOrCreate(
            ['asignacion_id' => $asigCarlosWeb4B->asignacion_id, 'recompensa_id' => $rec1->recompensa_id],
            [
                'cupo_maximo' => 5,
                'cupo_disponible' => 4,
                'costo_puntos' => 25,
                'estado' => RecompensaActivada::ESTADO_ACTIVA,
                'vigencia_inicio' => now()->subDays(1),
                'vigencia_fin' => now()->addDays(30),
            ]
        );

        // Canje para Diego Vargas
        RecompensaCanje::firstOrCreate(
            ['estudiante_id' => $alumnosCreated['2025104003']['user']->usuario_id, 'activacion_id' => $activada1->activacion_id],
            [
                'codigo_beneficio' => 'CANJE-I20-001',
                'puntos_canjeados' => 25,
                'estado' => RecompensaCanje::ESTADO_CANJEADA,
                'fecha_solicitud' => now()->subDay(),
                'fecha_canje' => now()->subDay(),
            ]
        );

        // ==========================================
        // 11. USUARIO ADMINISTRADOR Y ORIENTADOR
        // ==========================================
        $adminUser = User::firstOrCreate(
            ['email' => 'admin.i20@' . self::DOMINIO_PRUEBAS],
            [
                'rol_id' => $rolAdmin->rol_id,
                'nombre' => 'Administrador',
                'apellido_paterno' => 'Pruebas',
                'apellido_materno' => 'I20',
                'estado' => 'ACTIVO',
                'password' => Hash::make(self::PASSWORD_PRUEBAS),
                'email_verified_at' => now(),
            ]
        );

        $orientadorUser = User::firstOrCreate(
            ['email' => 'orientador.i20@' . self::DOMINIO_PRUEBAS],
            [
                'rol_id' => $rolOrientador->rol_id,
                'nombre' => 'Roberto',
                'apellido_paterno' => 'Gómez',
                'apellido_materno' => 'Bolaños',
                'estado' => 'ACTIVO',
                'password' => Hash::make(self::PASSWORD_PRUEBAS),
                'email_verified_at' => now(),
            ]
        );

        // ==========================================
        // 12. GENERACIÓN DE ARCHIVO PRIVADO DE CREDENCIALES
        // ==========================================
        $dirPrivado = storage_path('app/private');
        if (!File::isDirectory($dirPrivado)) {
            File::makeDirectory($dirPrivado, 0700, true);
        }

        $lineasCredenciales = [];
        $lineasCredenciales[] = "================================================================================";
        $lineasCredenciales[] = "BANCO DE DATOS SINTÉTICOS Y CREDENCIALES — INCREMENTO 20 (CIERRE INTEGRAL)";
        $lineasCredenciales[] = "ENTORNO VÁLIDO: MariaDB Temporal (bienestar_upemor_validacion_i20)";
        $lineasCredenciales[] = "CONTRASEÑA MAESTRA DE TODAS LAS CUENTAS SINTÉTICAS: " . self::PASSWORD_PRUEBAS;
        $lineasCredenciales[] = "FECHA DE GENERACIÓN: " . now('America/Mexico_City')->format('Y-m-d H:i:s');
        $lineasCredenciales[] = "================================================================================\n";

        $lineasCredenciales[] = "--- ROL ADMINISTRADOR ---";
        $lineasCredenciales[] = "Email: {$adminUser->email} | Password: " . self::PASSWORD_PRUEBAS . " | Administrador de Pruebas I20\n";

        $lineasCredenciales[] = "--- ROL ORIENTADOR PSICOLÓGICO ---";
        $lineasCredenciales[] = "Email: {$orientadorUser->email} | Password: " . self::PASSWORD_PRUEBAS . " | Psicólogo Institucional\n";

        $lineasCredenciales[] = "--- ROL DOCENTES (8 CUENTAS) ---";
        foreach ($docentesData as $d) {
            $lineasCredenciales[] = sprintf(
                "ID: %-12s | Email: %-38s | Especialidad: %s",
                $d['num'],
                $d['email'],
                $d['especialidad']
            );
        }
        $lineasCredenciales[] = "";

        $lineasCredenciales[] = "--- ROL ESTUDIANTES (12 CUENTAS POR COHORTES) ---";
        foreach ($alumnosData as $a) {
            $email = strtolower($a['nom'] . '.' . $a['pat']) . '@' . self::DOMINIO_PRUEBAS;
            $lineasCredenciales[] = sprintf(
                "Matrícula: %-10s | Email: %-32s | Gen: %-9s | Cuatri: %-2s°%s %-3s | Puntos: %-3d | %s",
                $a['mat'],
                $email,
                ($a['cuatri'] === 1 ? '2026–2029' : ($a['cuatri'] === 4 ? '2025–2028' : ($a['cuatri'] === 7 ? '2024–2027' : '2023–2026'))),
                $a['cuatri'],
                $a['grp'],
                $a['prog']->clave,
                $a['pts'],
                $a['esc']
            );
        }

        File::put("{$dirPrivado}/credenciales_sinteticas_i20.txt", implode("\n", $lineasCredenciales));
    }
}
