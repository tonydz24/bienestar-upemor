<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Emocion;
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
use App\Notifications\AsignacionDocenteResueltaNotification;
use App\Notifications\CanjePendienteValidacionNotification;
use App\Notifications\InscripcionEstudianteResueltaNotification;
use App\Notifications\NuevoAlumnoRegistradoNotification;
use App\Notifications\RecompensaHabilitadaNotification;
use App\Services\Academico\CatedraAcademicaService;
use App\Services\Academico\PeriodoOperativoService;
use App\Services\Reportes\ExcelReporteService;
use App\Services\Reportes\ReporteInstitucionalService;
use App\Services\Respaldos\RespaldoDatabaseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class Incremento20CierreDefinitivoTest extends TestCase
{
    use RefreshDatabase;

    protected Rol $rolAdmin;
    protected Rol $rolDocente;
    protected Rol $rolAlumno;
    protected Rol $rolOrientador;

    protected User $adminUser;
    protected User $docenteUser1;
    protected User $docenteUser2;
    protected User $alumnoUser4B;
    protected User $alumnoUser4A;
    protected User $alumnoUserIFI;

    protected ProgramaEducativo $progIID;
    protected ProgramaEducativo $progIFI;

    protected PeriodoAcademico $periodo2026_1;
    protected PeriodoAcademico $periodo2026_3;

    protected Grupo $grupo4A_IID;
    protected Grupo $grupo4B_IID;
    protected Grupo $grupo4A_IFI;

    protected Asignatura $asigWeb;
    protected Asignatura $asigCalculo;
    protected Asignatura $asigEtica;
    protected Asignatura $asigFinanzas;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $this->rolAdmin = Rol::create(['nombre' => 'Administrador', 'descripcion' => 'Admin']);
        $this->rolDocente = Rol::create(['nombre' => 'Docente', 'descripcion' => 'Docente']);
        $this->rolAlumno = Rol::create(['nombre' => 'Alumno', 'descripcion' => 'Alumno']);
        $this->rolOrientador = Rol::create(['nombre' => 'Orientador', 'descripcion' => 'Orientador']);

        // 2. Programas
        $this->progIID = ProgramaEducativo::create(['clave' => 'IID', 'nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital', 'activo' => true]);
        $this->progIFI = ProgramaEducativo::create(['clave' => 'IFI', 'nombre' => 'Ingeniería Financiera', 'activo' => true]);

        // 3. Periodos
        $this->periodo2026_1 = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-30',
            'activo' => true,
        ]);

        $this->periodo2026_3 = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2027-01-04',
            'activo' => true,
        ]);

        // 4. Grupos en 2026-3
        $this->grupo4A_IID = Grupo::create([
            'programa_id' => $this->progIID->programa_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupo4B_IID = Grupo::create([
            'programa_id' => $this->progIID->programa_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'B',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupo4A_IFI = Grupo::create([
            'programa_id' => $this->progIFI->programa_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        // 5. Asignaturas de 4° cuatrimestre
        $this->asigWeb = Asignatura::create(['programa_id' => $this->progIID->programa_id, 'clave' => 'IID-C4-04', 'nombre' => 'Aplicaciones Web', 'cuatrimestre' => 4, 'activo' => true]);
        $this->asigCalculo = Asignatura::create(['programa_id' => $this->progIID->programa_id, 'clave' => 'IID-C4-03', 'nombre' => 'Cálculo de Varias Variables', 'cuatrimestre' => 4, 'activo' => true]);
        $this->asigEtica = Asignatura::create(['programa_id' => $this->progIID->programa_id, 'clave' => 'IID-C4-02', 'nombre' => 'Ética Profesional', 'cuatrimestre' => 4, 'activo' => true]);
        $this->asigFinanzas = Asignatura::create(['programa_id' => $this->progIFI->programa_id, 'clave' => 'IFI-C4-04', 'nombre' => 'Finanzas Corporativas', 'cuatrimestre' => 4, 'activo' => true]);

        // 6. Usuarios
        $this->adminUser = User::create([
            'rol_id' => $this->rolAdmin->rol_id,
            'nombre' => 'Admin',
            'apellido_paterno' => 'I20',
            'email' => 'admin.i20@test.com',
            'password' => Hash::make('AdminPass!123'),
            'estado' => 'ACTIVO',
        ]);

        $this->docenteUser1 = User::create([
            'rol_id' => $this->rolDocente->rol_id,
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Mendoza',
            'email' => 'carlos.mendoza@test.com',
            'password' => Hash::make('DocentePass!123'),
            'estado' => 'ACTIVO',
        ]);
        Docente::create(['usuario_id' => $this->docenteUser1->usuario_id, 'numero_empleado' => 'DOC-01', 'activo' => true]);

        $this->docenteUser2 = User::create([
            'rol_id' => $this->rolDocente->rol_id,
            'nombre' => 'Laura',
            'apellido_paterno' => 'Morales',
            'email' => 'laura.morales@test.com',
            'password' => Hash::make('DocentePass!123'),
            'estado' => 'ACTIVO',
        ]);
        Docente::create(['usuario_id' => $this->docenteUser2->usuario_id, 'numero_empleado' => 'DOC-02', 'activo' => true]);

        $this->alumnoUser4B = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Manuel',
            'apellido_paterno' => 'Arellano',
            'email' => 'manuel.arellano@test.com',
            'password' => Hash::make('AlumnoPass!123'),
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoUser4B->usuario_id,
            'programa_id' => $this->progIID->programa_id,
            'matricula' => '2025104001',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'fecha_ingreso' => '2025-09-01',
            'saldo_puntos' => 50,
            'permite_visualizacion' => true,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser4B->usuario_id,
            'grupo_id' => $this->grupo4B_IID->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        $this->alumnoUser4A = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Sofía',
            'apellido_paterno' => 'Navarro',
            'email' => 'sofia.navarro@test.com',
            'password' => Hash::make('AlumnoPass!123'),
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoUser4A->usuario_id,
            'programa_id' => $this->progIID->programa_id,
            'matricula' => '2025104002',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'fecha_ingreso' => '2025-09-01',
            'saldo_puntos' => 40,
            'permite_visualizacion' => true,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser4A->usuario_id,
            'grupo_id' => $this->grupo4A_IID->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        $this->alumnoUserIFI = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Mariana',
            'apellido_paterno' => 'Peña',
            'email' => 'mariana.pena@test.com',
            'password' => Hash::make('AlumnoPass!123'),
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoUserIFI->usuario_id,
            'programa_id' => $this->progIFI->programa_id,
            'matricula' => '2025204001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'fecha_ingreso' => '2025-09-01',
            'saldo_puntos' => 30,
            'permite_visualizacion' => true,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUserIFI->usuario_id,
            'grupo_id' => $this->grupo4A_IFI->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);
    }

    /**
     * TEST 1: Las 4 generaciones institucionales se deducen correctamente según cuatrimestre y fecha_ingreso.
     */
    public function test_cohortes_y_generaciones_institucionales_se_deducen_correctamente(): void
    {
        $est4 = $this->alumnoUser4B->estudiante;
        $this->assertEquals('2025–2028', $est4->generacion);

        // Crear alumno de 1er cuatri (ingreso 2026)
        $est1 = new Estudiante(['cuatrimestre' => 1, 'fecha_ingreso' => Carbon::parse('2026-09-01')]);
        $this->assertEquals('2026–2029', $est1->generacion);

        // Crear alumno de 7mo cuatri (ingreso 2024)
        $est7 = new Estudiante(['cuatrimestre' => 7, 'fecha_ingreso' => Carbon::parse('2024-09-01')]);
        $this->assertEquals('2024–2027', $est7->generacion);

        // Crear alumno de 10mo cuatri (ingreso 2023, egresando Dic 2026)
        $est10 = new Estudiante(['cuatrimestre' => 10, 'fecha_ingreso' => Carbon::parse('2023-09-01')]);
        $this->assertEquals('2023–2026', $est10->generacion);

        // Deducido exclusivamente por número de cuatrimestre sin fecha explícita
        $estSinFecha = new Estudiante(['cuatrimestre' => 4]);
        $this->assertEquals('2025–2028', $estSinFecha->generacion);
    }

    /**
     * TEST 2: PeriodoOperativoService prioriza el periodo vigente por calendario que tiene actividad académica.
     */
    public function test_periodo_operativo_service_prioritizes_calendar_vigente_with_activity(): void
    {
        $service = app(PeriodoOperativoService::class);

        // Ambos periodos (2026-1 y 2026-3) tienen activo = true, pero 2026-3 está vigente por calendario y tiene grupos
        $periodoResuelto = $service->resolverPeriodoOperativo();
        $this->assertNotNull($periodoResuelto);
        $this->assertEquals($this->periodo2026_3->periodo_id, $periodoResuelto->periodo_id);
        $this->assertEquals('2026-3', $periodoResuelto->clave);
    }

    /**
     * TEST 3: Alumno de 4° B visualiza ofertas aprobadas generales y de su grupo, pero no las exclusivas de 4° A.
     */
    public function test_alumno_visualiza_oferta_de_su_grupo_y_excluye_otras_secciones(): void
    {
        $catedraService = app(CatedraAcademicaService::class);

        // Asignación 1: Aplicaciones Web (Carlos Mendoza) - Alcance general (TODOS)
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser1->usuario_id,
            'asignatura_id' => $this->asigWeb->asignatura_id,
            'grupo_id' => $this->grupo4B_IID->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        // Asignación 2: Ética Profesional (Patricia Reyes) - Específica solo para Grupo 4° A
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser2->usuario_id,
            'asignatura_id' => $this->asigEtica->asignatura_id,
            'grupo_id' => $this->grupo4A_IID->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        // Consulta para Alumno de 4° B
        $oferta4B = $catedraService->obtenerOfertaDocenteParaEstudiante(
            $this->alumnoUser4B,
            $this->grupo4B_IID,
            $this->periodo2026_3
        );

        // Debe ver Aplicaciones Web (alcance TODOS)
        $this->assertTrue($oferta4B->contains('asignatura_id', $this->asigWeb->asignatura_id));

        // NO debe ver Ética Profesional (es específica de 4° A)
        $this->assertFalse($oferta4B->contains('asignatura_id', $this->asigEtica->asignatura_id));
    }

    /**
     * TEST 4: Alumno de otra carrera (IFI) no tiene visibilidad de las materias de IID.
     */
    public function test_alumno_otra_carrera_bloqueado_de_materias_ajenas(): void
    {
        $catedraService = app(CatedraAcademicaService::class);

        // Oferta para IID
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser1->usuario_id,
            'asignatura_id' => $this->asigWeb->asignatura_id,
            'grupo_id' => $this->grupo4B_IID->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'modalidad_alcance' => 'TODOS',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        $ofertaIFI = $catedraService->obtenerOfertaDocenteParaEstudiante(
            $this->alumnoUserIFI,
            $this->grupo4A_IFI,
            $this->periodo2026_3
        );

        $this->assertFalse($ofertaIFI->contains('asignatura_id', $this->asigWeb->asignatura_id));
        $this->assertCount(0, $ofertaIFI);
    }

    /**
     * TEST 5: CatedraAcademicaService detecta correctamente las materias curriculares sin oferta docente (Sección 6.3).
     */
    public function test_materias_sin_oferta_docente_se_detectan_como_informativas(): void
    {
        $catedraService = app(CatedraAcademicaService::class);

        // Se oferta Aplicaciones Web, pero Cálculo de Varias Variables NO tiene docente
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser1->usuario_id,
            'asignatura_id' => $this->asigWeb->asignatura_id,
            'grupo_id' => $this->grupo4B_IID->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        $sinOferta = $catedraService->obtenerMateriasSinOfertaDocente(
            $this->alumnoUser4B,
            $this->grupo4B_IID,
            $this->periodo2026_3
        );

        // Cálculo de Varias Variables debe estar en la lista de materias sin oferta
        $this->assertTrue($sinOferta->contains('asignatura_id', $this->asigCalculo->asignatura_id));

        // Aplicaciones Web NO debe estar en sin oferta porque ya tiene docente
        $this->assertFalse($sinOferta->contains('asignatura_id', $this->asigWeb->asignatura_id));
    }

    /**
     * TEST 6: Flujo completo de solicitud de inscripción del alumno y resolución del docente.
     */
    public function test_flujo_solicitud_estudiantil_y_resolucion_docente(): void
    {
        Notification::fake();

        $asigDocente = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser1->usuario_id,
            'asignatura_id' => $this->asigWeb->asignatura_id,
            'grupo_id' => $this->grupo4B_IID->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        // 1. Alumno solicita inscripción
        $response = $this->actingAs($this->alumnoUser4B)
            ->post(route('alumno.academico.solicitar'), [
                'asignacion_id' => $asigDocente->asignacion_id,
                'motivo_solicitud' => 'Solicitud regular de 4° B',
            ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'estudiante_id' => $this->alumnoUser4B->usuario_id,
            'asignacion_id' => $asigDocente->asignacion_id,
            'estado' => 'PENDIENTE',
        ]);

        $inscripcion = EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumnoUser4B->usuario_id)
            ->where('asignacion_id', $asigDocente->asignacion_id)
            ->first();

        // 2. Docente aprueba la solicitud
        $responseDocente = $this->actingAs($this->docenteUser1)
            ->post(route('docente.academico.inscripciones.aprobar', $inscripcion));

        $responseDocente->assertSessionHas('status');
        $this->assertEquals('APROBADA', $inscripcion->fresh()->estado);

        // Se verifica que se notificó al alumno
        Notification::assertSentTo($this->alumnoUser4B, InscripcionEstudianteResueltaNotification::class);
    }

    /**
     * TEST 7: Canje de recompensa con libro mayor auditado de puntos.
     */
    public function test_canje_de_recompensa_con_libro_mayor_de_puntos(): void
    {
        $asigDocente = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser1->usuario_id,
            'asignatura_id' => $this->asigWeb->asignatura_id,
            'grupo_id' => $this->grupo4B_IID->grupo_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'modalidad_alcance' => 'ESPECIFICO',
            'activo' => true,
            'estado' => 'APROBADA',
        ]);

        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Puntos Extra en Examen',
            'categoria' => 'ACADEMICA',
            'costo_puntos_base' => 20,
            'descripcion' => '1 punto extra',
            'estado_propuesta' => 'INSTITUCIONAL',
            'activo' => true,
        ]);

        $activada = RecompensaActivada::create([
            'asignacion_id' => $asigDocente->asignacion_id,
            'recompensa_id' => $catalogo->recompensa_id,
            'cupo_maximo' => 5,
            'cupo_disponible' => 5,
            'costo_puntos' => 20,
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(30)->toDateString(),
        ]);

        // Inscripción aprobada requerida
        EstudianteAsignaturaInscripcion::create([
            'estudiante_id' => $this->alumnoUser4B->usuario_id,
            'asignacion_id' => $asigDocente->asignacion_id,
            'grupo_id' => $this->grupo4B_IID->grupo_id,
            'asignatura_id' => $this->asigWeb->asignatura_id,
            'periodo_id' => $this->periodo2026_3->periodo_id,
            'estado' => 'APROBADA',
        ]);

        $this->assertEquals(50, $this->alumnoUser4B->estudiante->saldo_puntos);

        // Alumno canjea recompensa
        $response = $this->actingAs($this->alumnoUser4B)
            ->post(route('alumno.recompensas.canjear', $activada->activacion_id), [
                'motivo' => 'Prueba de canje con libro mayor',
            ]);

        $response->assertSessionHas('success');

        // Saldo descontado correctamente (50 - 20 = 30)
        $this->assertEquals(30, $this->alumnoUser4B->estudiante->fresh()->saldo_puntos);

        // Canje registrado
        $this->assertDatabaseHas('recompensas_canjes', [
            'estudiante_id' => $this->alumnoUser4B->usuario_id,
            'activacion_id' => $activada->activacion_id,
            'estado' => 'CANJEADA',
        ]);
    }

    /**
     * TEST 8: Controlador de restauración física web exige confirmación y contraseña de administrador.
     */
    public function test_controlador_restauracion_web_exige_candados_de_seguridad(): void
    {
        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $nombreArchivo = 'test_restore_i20.sql';
        $sql = "-- Header institucional UPEMOR\nCREATE TABLE test_table_i20 (id INT);\nINSERT INTO test_table_i20 VALUES (1);";
        File::put($backupDir . '/' . $nombreArchivo, $sql);

        // Intento 1: Sin contraseña -> error de validación
        $this->actingAs($this->adminUser)
            ->post(route('admin.respaldos.solicitar-restauracion', $nombreArchivo), [
                'password' => '',
                'modo' => 'fisico',
                'confirmacion' => 'RESTAURAR',
            ])
            ->assertSessionHasErrors('password');

        // Intento 2: Contraseña incorrecta -> rechazado
        $this->actingAs($this->adminUser)
            ->post(route('admin.respaldos.solicitar-restauracion', $nombreArchivo), [
                'password' => 'WrongPassword!123',
                'modo' => 'fisico',
                'confirmacion' => 'RESTAURAR',
            ])
            ->assertSessionHas('error');

        // Intento 3: Restauración física autorizada exitosa
        $responseExitosa = $this->actingAs($this->adminUser)
            ->post(route('admin.respaldos.solicitar-restauracion', $nombreArchivo), [
                'password' => 'AdminPass!123',
                'modo' => 'fisico',
                'confirmacion' => 'RESTAURAR',
            ]);

        $responseExitosa->assertSessionHas('status');

        File::delete($backupDir . '/' . $nombreArchivo);
    }

    /**
     * TEST 9: Las notificaciones selectivas respetan los canales estipulados en la matriz institucional.
     */
    public function test_matriz_notificaciones_canales_selectivos(): void
    {
        // 1. AsignacionDocenteResuelta: solo database
        $notif1 = new AsignacionDocenteResueltaNotification('Matemáticas', '4° B', 'APROBADA');
        $this->assertEquals(['database'], $notif1->via($this->docenteUser1));

        // 2. InscripcionEstudianteResuelta: database y mail
        $notif2 = new InscripcionEstudianteResueltaNotification('APROBADA', 'Web', 'Carlos');
        $this->assertEquals(['database', 'mail'], $notif2->via($this->alumnoUser4B));

        // 3. RecompensaHabilitada: database y mail
        $notif3 = new RecompensaHabilitadaNotification('Puntos Extra', 'Web', 20);
        $this->assertEquals(['database', 'mail'], $notif3->via($this->alumnoUser4B));

        // 4. CanjePendienteValidacion: database y mail
        $notif4 = new CanjePendienteValidacionNotification('Manuel Arellano', 'Puntos Extra', 'Web', 'CANJE-01');
        $this->assertEquals(['database', 'mail'], $notif4->via($this->docenteUser1));

        // 5. NuevoAlumnoRegistrado: database y mail
        $notif5 = new NuevoAlumnoRegistradoNotification('Manuel Arellano', '2025104001', 'IID');
        $this->assertEquals(['database', 'mail'], $notif5->via($this->adminUser));
    }

    /**
     * TEST 10: Los 6 reportes institucionales generan datos y exportación XLSX sin errores.
     */
    public function test_reportes_institucionales_y_exportaciones_xlsx(): void
    {
        // Precargar catálogo institucional de 6 emociones
        foreach (['Alegría', 'Calma', 'Ansiedad', 'Estrés', 'Tristeza', 'Enojo'] as $emo) {
            Emocion::create([
                'nombre' => $emo,
                'slug' => Str::slug($emo),
                'codigo_color' => '#10B981',
                'activo' => true,
            ]);
        }

        $reporteService = app(ReporteInstitucionalService::class);
        $excelService = app(ExcelReporteService::class);

        // 1. Emociones Registradas
        $rep1 = $reporteService->reporteEmocionesRegistradas([]);
        $this->assertArrayHasKey('datos', $rep1);
        $this->assertCount(6, $rep1['datos']);
        $xlsx1 = $excelService->exportarEmocionesRegistradas($rep1, 'rep1');
        $this->assertTrue(File::exists($xlsx1->getFile()->getPathname()));

        // 2. Emociones por Programa
        $rep2 = $reporteService->reporteEmocionesPorPrograma([]);
        $this->assertArrayHasKey('programas', $rep2);
        $xlsx2 = $excelService->exportarEmocionesPorPrograma($rep2, 'rep2');
        $this->assertTrue(File::exists($xlsx2->getFile()->getPathname()));

        // 3. Historial Alumno
        $rep3 = $reporteService->reporteHistorialAlumno(['estudiante_id' => $this->alumnoUser4B->usuario_id]);
        $this->assertArrayHasKey('historial', $rep3);
        $xlsx3 = $excelService->exportarHistorialAlumno($rep3, 'rep3');
        $this->assertTrue(File::exists($xlsx3->getFile()->getPathname()));

        // 4. Premios Canjeados
        $rep4 = $reporteService->reportePremiosCanjeados([]);
        $this->assertArrayHasKey('datos', $rep4);
        $xlsx4 = $excelService->exportarPremiosCanjeados($rep4, 'rep4');
        $this->assertTrue(File::exists($xlsx4->getFile()->getPathname()));

        // 5. Sesiones por Día
        $rep5 = $reporteService->reporteSesionesPorDia([]);
        $this->assertArrayHasKey('total_general', $rep5);
        $xlsx5 = $excelService->exportarSesionesPorDia($rep5, 'rep5');
        $this->assertTrue(File::exists($xlsx5->getFile()->getPathname()));

        // 6. Resultados de Tests
        $rep6 = $reporteService->reporteResultadosTests([]);
        $this->assertArrayHasKey('intentos', $rep6);
        $xlsx6 = $excelService->exportarResultadosTests($rep6, 'rep6');
        $this->assertTrue(File::exists($xlsx6->getFile()->getPathname()));
    }
}
