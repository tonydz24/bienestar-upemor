<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\CitaDisponibilidad;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SandboxEstabilizacionDemoSeeder extends Seeder
{
    /**
     * Seeder integral de estabilización exclusiva para el entorno sandbox.
     * Corrige credenciales, segrega roles Docente vs Orientador,
     * alinea la cohorte a 4° cuatrimestre IID y asegura consistencia total.
     */
    public function run(): void
    {
        $dbName = DB::connection()->getDatabaseName();

        if ($dbName !== 'bienestar_upemor_sandbox') {
            throw new \RuntimeException("SEGURIDAD: Este seeder solo puede ejecutarse en bienestar_upemor_sandbox. Conexión actual: {$dbName}");
        }

        // 1. ROLES INSTITUCIONALES
        $rolAdmin = Rol::updateOrCreate(['rol_id' => 1], ['nombre' => 'Administrador', 'descripcion' => 'Administrador general del sistema', 'activo' => true]);
        $rolDocente = Rol::updateOrCreate(['rol_id' => 2], ['nombre' => 'Docente', 'descripcion' => 'Personal docente y asesor académico', 'activo' => true]);
        $rolAlumno = Rol::updateOrCreate(['rol_id' => 3], ['nombre' => 'Alumno', 'descripcion' => 'Estudiante universitario', 'activo' => true]);
        $rolOrientador = Rol::updateOrCreate(['rol_id' => 4], ['nombre' => 'Orientador', 'descripcion' => 'Profesional de orientación y acompañamiento psicológico estudiantil', 'activo' => true]);

        // 2. PERIODO ACADÉMICO ACTIVO 2026-1
        $periodo = PeriodoAcademico::updateOrCreate(
            ['clave' => '2026-1'],
            [
                'nombre' => 'Enero - Abril 2026',
                'fecha_inicio' => '2026-01-05',
                'fecha_fin' => '2026-04-30',
                'activo' => true,
            ]
        );

        // 3. PROGRAMAS EDUCATIVOS OFICIALES (Activos) Y HISTÓRICOS (Inactivos)
        // Desactivar programas legados/duplicados
        ProgramaEducativo::whereIn('programa_id', [1, 3, 5, 6, 7])->update(['activo' => false]);

        // Activar y asegurar claves de los 6 programas oficiales
        $programaIID = ProgramaEducativo::find(8);
        if ($programaIID) {
            $programaIID->update(['clave' => 'IID', 'activo' => true]);
        }

        $programaIFI = ProgramaEducativo::find(2);
        if ($programaIFI) {
            $programaIFI->update(['clave' => 'IFI', 'activo' => true]);
        }

        $programaIBT = ProgramaEducativo::find(4);
        if ($programaIBT) {
            $programaIBT->update(['clave' => 'IBT', 'activo' => true]);
        }

        $programaIIN = ProgramaEducativo::find(11);
        if ($programaIIN) {
            $programaIIN->update(['clave' => 'IIN', 'activo' => true]);
        }

        $programaIAS = ProgramaEducativo::find(10);
        if ($programaIAS) {
            $programaIAS->update(['clave' => 'IAS', 'activo' => true]);
        }

        $programaISE = ProgramaEducativo::find(9);
        if ($programaISE) {
            $programaISE->update(['clave' => 'ISE', 'activo' => true]);
        }

        $iidId = $programaIID ? $programaIID->programa_id : 8;

        // 4. PLAN DE ESTUDIOS IID
        $planIID = PlanEstudio::where('programa_id', $iidId)->first();
        $planIIDId = $planIID?->plan_id ?? 1;

        // 5. GRUPO OFICIAL 4° "A" (IID)
        $grupo4A = Grupo::find(1);
        if ($grupo4A) {
            $grupo4A->update([
                'programa_id' => $iidId,
                'periodo_id' => $periodo->periodo_id,
                'nombre' => 'A',
                'cuatrimestre' => 4,
                'turno' => 'MATUTINO',
                'activo' => true,
            ]);
        } else {
            $grupo4A = Grupo::create([
                'grupo_id' => 1,
                'programa_id' => $iidId,
                'periodo_id' => $periodo->periodo_id,
                'nombre' => 'A',
                'cuatrimestre' => 4,
                'turno' => 'MATUTINO',
                'activo' => true,
            ]);
        }

        // 6. USUARIOS DEMO Y CREDENCIALES ESTANDARIZADAS

        // 6.1 Administrador (Admin2026!)
        $admin = User::updateOrCreate(
            ['email' => 'admin@upemor.edu.mx'],
            [
                'rol_id' => 1,
                'nombre' => 'Coordinación',
                'apellido_paterno' => 'Bienestar',
                'apellido_materno' => 'Institucional',
                'password' => Hash::make('Admin2026!'),
                'telefono' => '7771234567',
                'estado' => 'ACTIVO',
                'email_verified_at' => now(),
            ]
        );
        DB::table('usuario_roles')->where('usuario_id', $admin->usuario_id)->delete();
        DB::table('usuario_roles')->insert([
            'usuario_id' => $admin->usuario_id,
            'rol_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6.2 Docente Académico (Docente2026!) - Estricto: NO es orientador
        $docenteUser = User::updateOrCreate(
            ['email' => 'docente.academico@upemor.edu.mx'],
            [
                'rol_id' => 2,
                'nombre' => 'Roberto',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => 'Domínguez',
                'password' => Hash::make('Docente2026!'),
                'telefono' => '7773297010',
                'estado' => 'ACTIVO',
                'email_verified_at' => now(),
            ]
        );
        Docente::updateOrCreate(
            ['usuario_id' => $docenteUser->usuario_id],
            [
                'numero_empleado' => 'DOC-2026-001',
                'activo' => true,
            ]
        );
        // Exclusividad: Borrar cualquier perfil clínico o citas profesionales
        CitaProfesional::where('usuario_id', $docenteUser->usuario_id)->delete();
        DB::table('usuario_roles')->where('usuario_id', $docenteUser->usuario_id)->delete();
        DB::table('usuario_roles')->insert([
            'usuario_id' => $docenteUser->usuario_id,
            'rol_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6.3 Orientador Psicológico (Orientador2026!) - Estricto: NO es docente
        $orientadorUser = User::updateOrCreate(
            ['email' => 'orientador.psicologico@upemor.edu.mx'],
            [
                'rol_id' => 4,
                'nombre' => 'Valeria',
                'apellido_paterno' => 'Morales',
                'apellido_materno' => 'Salgado',
                'password' => Hash::make('Orientador2026!'),
                'telefono' => '7773297020',
                'estado' => 'ACTIVO',
                'email_verified_at' => now(),
            ]
        );
        // Exclusividad: Borrar cualquier registro docente
        Docente::where('usuario_id', $orientadorUser->usuario_id)->delete();
        DocenteAsignacion::where('docente_id', $orientadorUser->usuario_id)->delete();
        DB::table('usuario_roles')->where('usuario_id', $orientadorUser->usuario_id)->delete();
        DB::table('usuario_roles')->insert([
            'usuario_id' => $orientadorUser->usuario_id,
            'rol_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $citaProf = CitaProfesional::updateOrCreate(
            ['usuario_id' => $orientadorUser->usuario_id],
            [
                'cargo' => 'Orientadora Psicológica y Psicopedagógica',
                'cedula_profesional' => 'PSI-2026-001',
                'lugar_atencion' => 'Edificio de Acompañamiento Estudiantil, Cubículo 4',
                'modalidades_permitidas' => 'AMBAS',
                'permite_citas' => true,
                'activo' => true,
            ]
        );

        // Disponibilidad semanal para el orientador
        foreach ([1, 2, 3, 4, 5] as $dia) {
            CitaDisponibilidad::updateOrCreate(
                [
                    'profesional_id' => $citaProf->profesional_id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '09:00:00',
                ],
                [
                    'hora_fin' => '14:00:00',
                    'duracion_cita_min' => 50,
                    'modalidad' => 'PRESENCIAL',
                    'activo' => true,
                ]
            );
        }

        // 6.4 Sanitizar Usuario 7 (orientacion.psicologica@upemor.edu.mx) para rol Orientador puro
        $user7 = User::find(7);
        if ($user7) {
            $user7->update([
                'rol_id' => 4,
                'password' => Hash::make('Orientador2026!'),
                'estado' => 'ACTIVO',
                'email_verified_at' => now(),
            ]);
            Docente::where('usuario_id', 7)->delete();
            DocenteAsignacion::where('docente_id', 7)->delete();
            DB::table('usuario_roles')->where('usuario_id', 7)->delete();
            DB::table('usuario_roles')->insert([
                'usuario_id' => 7,
                'rol_id' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            CitaProfesional::updateOrCreate(
                ['usuario_id' => 7],
                [
                    'cargo' => 'Psicólogo de Orientación Estudiantil',
                    'cedula_profesional' => 'PSI-2026-002',
                    'lugar_atencion' => 'Edificio A, Cubículo 2',
                    'modalidades_permitidas' => 'AMBAS',
                    'permite_citas' => true,
                    'activo' => true,
                ]
            );
        }

        // 6.5 Alumno Demo (Alumno2026!)
        $alumnoUser = User::updateOrCreate(
            ['email' => 'alumno.demo@upemor.edu.mx'],
            [
                'rol_id' => 3,
                'nombre' => 'Ana Sofía',
                'apellido_paterno' => 'Martínez',
                'apellido_materno' => 'Hernández',
                'password' => Hash::make('Alumno2026!'),
                'telefono' => '7773297001',
                'estado' => 'ACTIVO',
                'email_verified_at' => now(),
            ]
        );
        DB::table('usuario_roles')->where('usuario_id', $alumnoUser->usuario_id)->delete();
        DB::table('usuario_roles')->insert([
            'usuario_id' => $alumnoUser->usuario_id,
            'rol_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estudiante5 = Estudiante::updateOrCreate(
            ['usuario_id' => $alumnoUser->usuario_id],
            [
                'programa_id' => $iidId,
                'matricula' => 'DEMO202601',
                'cuatrimestre' => 4,
                'grupo' => 'A',
                'saldo_puntos' => 25,
                'permite_visualizacion' => true,
            ]
        );

        EstudianteInscripcion::updateOrCreate(
            ['estudiante_id' => $alumnoUser->usuario_id, 'periodo_id' => $periodo->periodo_id],
            [
                'grupo_id' => $grupo4A->grupo_id,
                'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
            ]
        );

        // 6.6 Segundo Alumno (alumno.evaluaciones@upemor.edu.mx)
        $alumno2User = User::updateOrCreate(
            ['email' => 'alumno.evaluaciones@upemor.edu.mx'],
            [
                'rol_id' => 3,
                'nombre' => 'Carlos',
                'apellido_paterno' => 'Jiménez',
                'apellido_materno' => 'Ortega',
                'password' => Hash::make('Alumno2026!'),
                'telefono' => '7773297002',
                'estado' => 'ACTIVO',
                'email_verified_at' => now(),
            ]
        );
        DB::table('usuario_roles')->where('usuario_id', $alumno2User->usuario_id)->delete();
        DB::table('usuario_roles')->insert([
            'usuario_id' => $alumno2User->usuario_id,
            'rol_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estudiante6 = Estudiante::updateOrCreate(
            ['usuario_id' => $alumno2User->usuario_id],
            [
                'programa_id' => $iidId,
                'matricula' => 'EVAL202602',
                'cuatrimestre' => 4,
                'grupo' => 'A',
                'saldo_puntos' => 20,
                'permite_visualizacion' => true,
            ]
        );

        EstudianteInscripcion::updateOrCreate(
            ['estudiante_id' => $alumno2User->usuario_id, 'periodo_id' => $periodo->periodo_id],
            [
                'grupo_id' => $grupo4A->grupo_id,
                'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
            ]
        );

        // 7. ASIGNATURAS OFICIALES DE 4° CUATRIMESTRE Y ASIGNACIONES DOCENTE
        // Buscar o crear las materias oficiales de 4to cuatrimestre del plan IID
        $asigWeb = Asignatura::where('plan_id', $planIIDId)->where('nombre', 'Aplicaciones Web')->first();
        if (!$asigWeb) {
            $asigWeb = Asignatura::create([
                'programa_id' => $iidId,
                'plan_id' => $planIIDId,
                'clave' => 'IID-C4-04',
                'nombre' => 'Aplicaciones Web',
                'cuatrimestre' => 4,
                'tipo' => 'ORDINARIA',
                'activo' => true,
            ]);
        }

        $asigDatos = Asignatura::where('plan_id', $planIIDId)->where('nombre', 'Estructura de Datos')->first();
        if (!$asigDatos) {
            $asigDatos = Asignatura::create([
                'programa_id' => $iidId,
                'plan_id' => $planIIDId,
                'clave' => 'IID-C4-05',
                'nombre' => 'Estructura de Datos',
                'cuatrimestre' => 4,
                'tipo' => 'ORDINARIA',
                'activo' => true,
            ]);
        }

        // Asignación 1: Roberto Pérez -> Aplicaciones Web en 4° "A"
        $asigDoc1 = DocenteAsignacion::find(1);
        if ($asigDoc1) {
            $asigDoc1->update([
                'docente_id' => $docenteUser->usuario_id,
                'asignatura_id' => $asigWeb->asignatura_id,
                'grupo_id' => $grupo4A->grupo_id,
                'periodo_id' => $periodo->periodo_id,
                'activo' => true,
            ]);
        } else {
            $asigDoc1 = DocenteAsignacion::create([
                'asignacion_id' => 1,
                'docente_id' => $docenteUser->usuario_id,
                'asignatura_id' => $asigWeb->asignatura_id,
                'grupo_id' => $grupo4A->grupo_id,
                'periodo_id' => $periodo->periodo_id,
                'activo' => true,
            ]);
        }

        // Asignación 2: Roberto Pérez -> Estructura de Datos en 4° "A"
        $asigDoc2 = DocenteAsignacion::find(2);
        if ($asigDoc2) {
            $asigDoc2->update([
                'docente_id' => $docenteUser->usuario_id,
                'asignatura_id' => $asigDatos->asignatura_id,
                'grupo_id' => $grupo4A->grupo_id,
                'periodo_id' => $periodo->periodo_id,
                'activo' => true,
            ]);
        } else {
            $asigDoc2 = DocenteAsignacion::create([
                'asignacion_id' => 2,
                'docente_id' => $docenteUser->usuario_id,
                'asignatura_id' => $asigDatos->asignatura_id,
                'grupo_id' => $grupo4A->grupo_id,
                'periodo_id' => $periodo->periodo_id,
                'activo' => true,
            ]);
        }

        // Inactivar asignaturas huérfanas creadas en seeders antiguos sin plan
        Asignatura::whereIn('clave', ['IID-501', 'IID-502'])->update(['activo' => false]);

        // 8. CATÁLOGO DE RECOMPENSAS Y ACTIVACIONES FUNCIONALES
        $recompensa1 = RecompensaCatalogo::updateOrCreate(
            ['nombre' => 'Prórroga de 48 horas en entrega de práctica'],
            [
                'descripcion' => 'Permite extender dos días naturales el plazo de entrega de un entregable o reporte de laboratorio.',
                'categoria' => 'TIEMPO',
                'costo_puntos_base' => 12,
                'condiciones_generales' => 'Aplica una sola vez por periodo parcial. Requiere solicitud previa a la fecha límite.',
                'activo' => true,
            ]
        );

        $recompensa2 = RecompensaCatalogo::updateOrCreate(
            ['nombre' => 'Punto extra en evaluación continua'],
            [
                'descripcion' => 'Bonificación de una décima (0.5 a 1.0) en el rubro de tareas y actividades prácticas del parcial.',
                'categoria' => 'ACADEMICA',
                'costo_puntos_base' => 15,
                'condiciones_generales' => 'No transferible ni aplicable sobre exámenes departamentales finales.',
                'activo' => true,
            ]
        );

        // Habilitar Recompensa 1 en Aplicaciones Web
        RecompensaActivada::updateOrCreate(
            [
                'recompensa_id' => $recompensa1->recompensa_id,
                'asignacion_id' => $asigDoc1->asignacion_id,
            ],
            [
                'cupo_maximo' => 10,
                'cupo_disponible' => 8,
                'costo_puntos' => 12,
                'vigencia_inicio' => Carbon::now()->subDays(5),
                'vigencia_fin' => Carbon::now()->addMonths(2),
                'condiciones_docente' => 'Solicitar con 24 horas de antelación al vencimiento.',
                'estado' => 'ACTIVA',
            ]
        );

        // Habilitar Recompensa 2 en Estructura de Datos
        RecompensaActivada::updateOrCreate(
            [
                'recompensa_id' => $recompensa2->recompensa_id,
                'asignacion_id' => $asigDoc2->asignacion_id,
            ],
            [
                'cupo_maximo' => 8,
                'cupo_disponible' => 8,
                'costo_puntos' => 15,
                'vigencia_inicio' => Carbon::now()->subDays(5),
                'vigencia_fin' => Carbon::now()->addMonths(2),
                'condiciones_docente' => 'Válido para actividades individuales del parcial.',
                'estado' => 'ACTIVA',
            ]
        );
    }
}
