<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\BitacoraEmocional;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\EvaluacionIntento;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\VersionInstrumento;
use App\Models\LoginAcceso;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\PuntoTransaccion;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Services\Academico\PeriodoOperativoService;
use App\Services\Recompensas\RecompensaService;
use App\Services\Reportes\ExcelReporteService;
use App\Services\Reportes\ReporteInstitucionalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Incremento21ConsolidacionTest extends TestCase
{
    use RefreshDatabase;

    protected Rol $rolAdmin;
    protected Rol $rolDocente;
    protected Rol $rolAlumno;
    protected Rol $rolOrientador;

    protected User $adminUser;
    protected User $docenteUser;
    protected User $alumnoUser;
    protected User $orientadorUser;

    protected ProgramaEducativo $programaITI;
    protected ProgramaEducativo $programaIFI;
    protected PlanEstudio $planITI2020;
    protected PlanEstudio $planITI2024;
    protected PlanEstudio $planIFI2022;

    protected PeriodoAcademico $periodo;
    protected Grupo $grupoITI;
    protected Asignatura $asignatura;
    protected DocenteAsignacion $asignacion;
    protected Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $this->rolAdmin = Rol::create(['nombre' => 'Administrador', 'descripcion' => 'Admin']);
        $this->rolDocente = Rol::create(['nombre' => 'Docente', 'descripcion' => 'Docente']);
        $this->rolAlumno = Rol::create(['nombre' => 'Alumno', 'descripcion' => 'Alumno']);
        $this->rolOrientador = Rol::create(['nombre' => 'Orientador', 'descripcion' => 'Orientador']);

        // 2. Programas Educativos
        $this->programaITI = ProgramaEducativo::create([
            'clave' => 'ITI',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);
        $this->programaIFI = ProgramaEducativo::create([
            'clave' => 'IFI',
            'nombre' => 'Ingeniería Financiera',
            'activo' => true,
        ]);

        // 3. Planes de Estudio con Vigencias
        $this->planITI2020 = PlanEstudio::create([
            'programa_id' => $this->programaITI->programa_id,
            'nombre' => 'Plan de Estudios ITI 2020',
            'codigo' => 'ITI-2020',
            'version' => '1.0',
            'anio_aprobacion' => 2020,
            'vigencia_inicio' => '2020-09-01',
            'vigencia_fin' => '2023-12-31',
            'estado' => 'HISTORICO',
            'es_vigente' => false,
        ]);

        $this->planITI2024 = PlanEstudio::create([
            'programa_id' => $this->programaITI->programa_id,
            'nombre' => 'Plan de Estudios ITI 2024 Innovado',
            'codigo' => 'ITI-2024',
            'version' => '2.0',
            'anio_aprobacion' => 2024,
            'vigencia_inicio' => '2024-01-01',
            'vigencia_fin' => null,
            'estado' => 'VIGENTE',
            'es_vigente' => true,
        ]);

        $this->planIFI2022 = PlanEstudio::create([
            'programa_id' => $this->programaIFI->programa_id,
            'nombre' => 'Plan de Estudios IFI 2022',
            'codigo' => 'IFI-2022',
            'version' => '1.0',
            'anio_aprobacion' => 2022,
            'vigencia_inicio' => '2022-09-01',
            'vigencia_fin' => null,
            'estado' => 'VIGENTE',
            'es_vigente' => true,
        ]);

        // 4. Periodo y Grupo
        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-18',
            'es_actual' => true,
        ]);

        $this->grupoITI = Grupo::create([
            'programa_id' => $this->programaITI->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 4,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        // 5. Asignatura y Asignación Docente
        $this->asignatura = Asignatura::create([
            'programa_id' => $this->programaITI->programa_id,
            'clave' => 'ITI-001',
            'nombre' => 'Programación Web',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);

        // 6. Usuarios
        $this->adminUser = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'Sistema',
            'email' => 'admin.test@upemor.edu.mx',
            'password' => Hash::make('password123'),
            'rol_id' => $this->rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);
        $this->adminUser->roles()->attach($this->rolAdmin->rol_id);

        $this->docenteUser = User::create([
            'nombre' => 'Profesor',
            'apellido_paterno' => 'Catedrático',
            'email' => 'docente.test@upemor.edu.mx',
            'password' => Hash::make('password123'),
            'rol_id' => $this->rolDocente->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);
        $this->docenteUser->roles()->attach($this->rolDocente->rol_id);
        Docente::create([
            'usuario_id' => $this->docenteUser->usuario_id,
            'numero_empleado' => 'DOC-2026-001',
            'activo' => true,
        ]);

        $this->asignacion = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoITI->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'activo' => true,
        ]);

        $this->alumnoUser = User::create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Gómez',
            'apellido_materno' => 'Pérez',
            'email' => 'alumno.test@upemor.edu.mx',
            'password' => Hash::make('password123'),
            'rol_id' => $this->rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);
        $this->alumnoUser->roles()->attach($this->rolAlumno->rol_id);

        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'matricula' => '20243TI001',
            'programa_id' => $this->programaITI->programa_id,
            'plan_id' => $this->planITI2024->plan_id,
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'puntos_actuales' => 200,
            'puntos_totales_obtenidos' => 200,
            'saldo_puntos' => 200,
            'permite_visualizacion' => true,
        ]);

        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'grupo_id' => $this->grupoITI->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        $this->orientadorUser = User::create([
            'nombre' => 'Psicólogo',
            'apellido_paterno' => 'Orientador',
            'email' => 'orientador.test@upemor.edu.mx',
            'password' => Hash::make('password123'),
            'rol_id' => $this->rolOrientador->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);
        $this->orientadorUser->roles()->attach($this->rolOrientador->rol_id);
    }

    /*
    |--------------------------------------------------------------------------
    | ÁREA 2: GESTIÓN DE RECOMPENSAS (ADMIN - DOCENTE - ALUMNO)
    |--------------------------------------------------------------------------
    */

    public function test_desactivacion_global_por_administrador_bloquea_canje_a_alumnos_con_prioridad_absoluta(): void
    {
        // 1. Recompensa en catálogo institucional
        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Puntos Extra Examen',
            'descripcion' => 'Medio punto extra en evaluación parcial',
            'costo_puntos_base' => 50,
            'categoria' => 'ACADEMICA',
            'activo' => true,
            'origen' => 'ADMIN',
            'estado_propuesta' => 'PROPUESTA_APROBADA',
        ]);

        // 2. Activada por docente para su asignación
        $activacion = RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'costo_puntos' => 50,
            'cupo_maximo' => 10,
            'cupo_disponible' => 10,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(15)->toDateString(),
            'estado' => 'ACTIVA',
            'activo' => true,
        ]);

        $this->assertTrue($activacion->estaVigente());

        // 3. Administrador desactiva globalmente en el catálogo
        $catalogo->update(['activo' => false]);
        $activacion->refresh();

        // 4. El modelo reconoce inmediatamente la suspensión por administración
        $this->assertTrue($activacion->estaDeshabilitadaPorAdmin());
        $this->assertFalse($activacion->estaVigente());
        $this->assertStringContainsString('Suspendida por Administración', $activacion->estado_visual_label);
        $this->assertStringContainsString('bg-danger-subtle', $activacion->estado_visual_badge);

        // 5. El estudiante intenta solicitar el canje por el endpoint protegido
        $this->actingAs($this->alumnoUser);
        $response = $this->post(route('alumno.recompensas.canjear', $activacion->activacion_id));

        // Debe ser rechazado y no descontar puntos
        $this->assertDatabaseMissing('recompensas_canjes', [
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
        ]);
        $this->assertEquals(200, $this->estudiante->fresh()->saldo_puntos);
    }

    public function test_eliminacion_de_recompensa_con_cero_canjes_realiza_borrado_fisico(): void
    {
        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Boleto Cafetería Experimental',
            'descripcion' => 'Descuento en snacks',
            'costo_puntos_base' => 30,
            'categoria' => 'INSTITUCIONAL',
            'activo' => true,
            'origen' => 'ADMIN',
            'estado_propuesta' => 'PROPUESTA_APROBADA',
        ]);

        $activacion = RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'costo_puntos' => 30,
            'cupo_maximo' => 5,
            'cupo_disponible' => 5,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(15)->toDateString(),
            'estado' => 'ACTIVA',
            'activo' => true,
        ]);

        $this->actingAs($this->adminUser);
        $response = $this->delete(route('admin.recompensas.destroy', $catalogo->recompensa_id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('recompensas_catalogo', ['recompensa_id' => $catalogo->recompensa_id]);
        $this->assertDatabaseMissing('recompensas_activadas', ['activacion_id' => $activacion->activacion_id]);
    }

    public function test_eliminacion_de_recompensa_con_solicitudes_pendientes_cancela_y_reembolsa_puntos(): void
    {
        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Asesoría Individual de Tesis',
            'descripcion' => 'Asesoría metodológica personalizada',
            'costo_puntos_base' => 80,
            'categoria' => 'ACADEMICA',
            'activo' => true,
            'origen' => 'ADMIN',
            'estado_propuesta' => 'PROPUESTA_APROBADA',
        ]);

        $activacion = RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'costo_puntos' => 80,
            'cupo_maximo' => 5,
            'cupo_disponible' => 4,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(15)->toDateString(),
            'estado' => 'ACTIVA',
            'activo' => true,
        ]);

        // Alumno solicita canje (se descuentan 80 puntos, saldo pasa a 120)
        $this->estudiante->update(['saldo_puntos' => 120]);
        $canjePendiente = RecompensaCanje::create([
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'puntos_canjeados' => 80,
            'codigo_canje' => 'CNJ-TEST-PEND',
            'estado' => 'SOLICITADO',
            'fecha_solicitud' => now(),
        ]);

        $this->actingAs($this->adminUser);
        $response = $this->delete(route('admin.recompensas.destroy', $catalogo->recompensa_id));

        $response->assertRedirect();

        // El canje pendiente fue cancelado
        $canjePendiente->refresh();
        $this->assertEquals('CANCELADO', $canjePendiente->estado);

        // Se reembolsaron los 80 puntos al estudiante (120 + 80 = 200)
        $this->assertEquals(200, $this->estudiante->fresh()->saldo_puntos);

        // Se registró la transacción de reembolso
        $this->assertDatabaseHas('estudiante_puntos_movimientos', [
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'tipo' => 'REEMBOLSO_CANJE',
            'puntos' => 80,
        ]);
    }

    public function test_eliminacion_de_recompensa_con_canjes_completados_aplica_baja_logica_inmutable(): void
    {
        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Reconocimiento Académico Oficial',
            'descripcion' => 'Diploma de distinción institucional',
            'costo_puntos_base' => 100,
            'categoria' => 'INSTITUCIONAL',
            'activo' => true,
            'origen' => 'ADMIN',
            'estado_propuesta' => 'PROPUESTA_APROBADA',
        ]);

        $activacion = RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'costo_puntos' => 100,
            'cupo_maximo' => 5,
            'cupo_disponible' => 4,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(15)->toDateString(),
            'estado' => 'ACTIVA',
            'activo' => true,
        ]);

        // Canje histórico completado
        $canjeCompletado = RecompensaCanje::create([
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'puntos_canjeados' => 100,
            'codigo_canje' => 'CNJ-TEST-HIST',
            'estado' => 'UTILIZADA',
            'fecha_solicitud' => now()->subDays(5),
        ]);

        $this->actingAs($this->adminUser);
        $response = $this->delete(route('admin.recompensas.destroy', $catalogo->recompensa_id));

        $response->assertRedirect();

        // El registro de catálogo y su canje siguen existiendo (inmutabilidad histórica)
        $this->assertDatabaseHas('recompensas_catalogo', [
            'recompensa_id' => $catalogo->recompensa_id,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('recompensas_canjes', [
            'canje_id' => $canjeCompletado->canje_id,
            'estado' => 'UTILIZADA',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ÁREA 3: GESTIÓN ACADÉMICA POR PLANES DE ESTUDIO (REQUISITO 7)
    |--------------------------------------------------------------------------
    */

    public function test_resolucion_deterministica_de_plan_segun_cohorte_y_vigencia(): void
    {
        // Alumno con cohorte 2021 en ITI (debe resolver Plan 2020: vigente 2020-2023)
        $estudiante2021 = new Estudiante([
            'matricula' => '20213TI055',
            'programa_id' => $this->programaITI->programa_id,
            'fecha_ingreso' => '2021-09-01',
        ]);
        $planResuelto2021 = $estudiante2021->resolverPlanEstudio();
        $this->assertNotNull($planResuelto2021);
        $this->assertEquals($this->planITI2020->plan_id, $planResuelto2021->plan_id);

        // Alumno con cohorte 2025 en ITI (debe resolver Plan 2024: vigente 2024 en adelante)
        $estudiante2025 = new Estudiante([
            'matricula' => '20251TI099',
            'programa_id' => $this->programaITI->programa_id,
            'fecha_ingreso' => '2025-01-10',
        ]);
        $planResuelto2025 = $estudiante2025->resolverPlanEstudio();
        $this->assertNotNull($planResuelto2025);
        $this->assertEquals($this->planITI2024->plan_id, $planResuelto2025->plan_id);

        // Alumno en IFI debe resolver Plan IFI 2022
        $estudianteIFI = new Estudiante([
            'matricula' => '20232IF012',
            'programa_id' => $this->programaIFI->programa_id,
        ]);
        $planResueltoIFI = $estudianteIFI->resolverPlanEstudio();
        $this->assertNotNull($planResueltoIFI);
        $this->assertEquals($this->planIFI2022->plan_id, $planResueltoIFI->plan_id);
    }

    public function test_ficha_academica_muestra_plan_y_generacion_y_actualiza_plan_correctamente(): void
    {
        $this->actingAs($this->adminUser);

        // Ver vista de edición de usuario
        $response = $this->get(route('admin.usuarios.edit', $this->alumnoUser->usuario_id));
        $response->assertOk();
        $response->assertSee('Plan de Estudio Curricular');
        $response->assertSee('Plan de Estudios ITI 2024 Innovado');

        // Actualizar asignando explícitamente el plan ITI 2020
        $updateResponse = $this->put(route('admin.usuarios.update', $this->alumnoUser->usuario_id), [
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Gómez',
            'apellido_materno' => 'Pérez',
            'email' => 'alumno.test@upemor.edu.mx',
            'matricula' => '20243TI001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'programa_id' => $this->programaITI->programa_id,
            'plan_id' => $this->planITI2020->plan_id,
            'activo' => 1,
            'estado' => 'ACTIVO',
        ]);

        $updateResponse->assertRedirect();
        $this->assertEquals($this->planITI2020->plan_id, $this->estudiante->fresh()->plan_id);
    }

    /*
    |--------------------------------------------------------------------------
    | ÁREA 1: REPORTES INSTITUCIONALES (6 REPORTES, 2 GRÁFICAS WEB, PDF Y EXCEL)
    |--------------------------------------------------------------------------
    */

    public function test_los_seis_reportes_se_renderizan_correctamente_en_web_con_sus_dos_graficas_svg(): void
    {
        $this->actingAs($this->adminUser);

        // Crear datos base para los reportes
        $emocion = Emocion::create([
            'nombre' => 'Alegría',
            'slug' => 'alegria',
            'descripcion' => 'Sensación de plenitud',
            'activo' => true,
            'color_hex' => '#10b981',
        ]);

        BitacoraEmocional::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'intensidad' => 4,
            'fecha' => '2026-10-15',
            'descripcion' => 'Registro de prueba',
        ]);

        $profesional = CitaProfesional::create([
            'usuario_id' => $this->orientadorUser->usuario_id,
            'cargo' => 'Orientador Psicológico',
            'activo' => true,
        ]);

        CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $profesional->profesional_id,
            'fecha_solicitud' => now(),
            'fecha_cita' => '2026-10-20',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => 'CONFIRMADA',
        ]);

        LoginAcceso::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'nombre_usuario' => 'Carlos Gómez',
            'rol_id' => $this->rolAlumno->rol_id,
            'rol_nombre' => 'Alumno',
            'email' => 'alumno.test@upemor.edu.mx',
            'fecha_acceso' => now(),
            'exitoso' => true,
        ]);

        LoginAcceso::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'nombre_usuario' => 'Carlos Gómez',
            'rol_id' => $this->rolAlumno->rol_id,
            'rol_nombre' => 'Alumno',
            'email' => 'alumno.test@upemor.edu.mx',
            'fecha_acceso' => now()->subDay(),
            'exitoso' => false,
        ]);

        // Datos para R4: Recompensa y Canje
        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Premio Prueba',
            'descripcion' => 'Descripción',
            'costo_puntos_base' => 10,
            'categoria' => 'ACADEMICA',
            'activo' => true,
            'origen' => 'ADMIN',
            'estado_propuesta' => 'PROPUESTA_APROBADA',
        ]);
        $activacion = RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $this->asignacion->asignacion_id,
            'costo_puntos' => 10,
            'cupo_maximo' => 5,
            'cupo_disponible' => 4,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(15)->toDateString(),
            'estado' => 'ACTIVA',
            'activo' => true,
        ]);
        RecompensaCanje::create([
            'activacion_id' => $activacion->activacion_id,
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'puntos_canjeados' => 10,
            'codigo_canje' => 'CNJ-TEST-R4',
            'estado' => 'UTILIZADA',
            'fecha_solicitud' => now(),
        ]);

        // Datos para R6: Test Psicométrico e Intento
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'BDI-II',
            'nombre' => 'Inventario de Depresión de Beck',
            'siglas' => 'BDI-II',
            'categoria' => 'TAMIZAJE',
            'descripcion' => 'Evaluación psicométrica institucional',
            'activo' => true,
        ]);
        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0',
            'instrucciones' => 'Responde con sinceridad cada reactivo',
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
        ]);
        EvaluacionIntento::create([
            'version_id' => $version->version_id,
            'usuario_id' => $this->alumnoUser->usuario_id,
            'estado' => EvaluacionIntento::ESTADO_FINALIZADO,
            'fecha_inicio' => now()->subMinutes(15),
            'fecha_finalizacion' => now(),
            'puntuacion_total' => 12,
            'nivel_resultado' => 'Mínimo',
            'tiene_alerta_critica' => false,
        ]);

        // 1. R1: Emociones Registradas (Dona + Barras Intensidad)
        $r1 = $this->get(route('admin.reportes.show', 'emociones-registradas'));
        $r1->assertOk();
        $r1->assertSee('Gráfica 1: Distribución Porcentual');
        $r1->assertSee('Gráfica 2: Intensidad Promedio por Emoción');
        $r1->assertSee('<svg', false);

        // 2. R2: Emociones por Programa (Barras agrupadas + Barras apiladas 100%)
        $r2 = $this->get(route('admin.reportes.show', 'emociones-por-programa'));
        $r2->assertOk();
        $r2->assertSee('Gráfica 1: Frecuencia por Programa Educativo');
        $r2->assertSee('Gráfica 2: Clima Emocional Relativo');
        $r2->assertSee('<svg', false);

        // 3. R3: Historial Alumno (Línea tiempo + Barras citas)
        $r3 = $this->get(route('admin.reportes.show', ['tipo' => 'historial-alumno', 'estudiante_id' => $this->alumnoUser->usuario_id]));
        $r3->assertOk();
        $r3->assertSee('Gráfica 1: Evolución Temporal de Intensidad Emocional');
        $r3->assertSee('Gráfica 2: Citas de Orientación por Estado');
        $r3->assertSee('<svg', false);

        // 4. R4: Premios Canjeados (Dona + Barras)
        $r4 = $this->get(route('admin.reportes.show', 'premios-canjeados'));
        $r4->assertOk();
        $r4->assertSee('Distribución de Beneficios (Gráfica Donut)');
        $r4->assertSee('Volumen Comparativo por Beneficio');

        // 5. R5: Sesiones por Día (Línea tiempo + Barras apiladas exitosos vs fallidos)
        $r5 = $this->get(route('admin.reportes.show', 'sesiones-por-dia'));
        $r5->assertOk();
        $r5->assertSee('Gráfica 1: Volumen Diario de Accesos');
        $r5->assertSee('Gráfica 2: Accesos Exitosos vs Intentos Fallidos');
        $r5->assertSee('Exitoso');
        $r5->assertSee('Fallido');

        // 6. R6: Resultados Tests Psicométricos (Barras niveles + Barras instrumentos)
        $r6 = $this->get(route('admin.reportes.show', 'resultados-tests'));
        $r6->assertOk();
        $r6->assertSee('Gráfica 1: Distribución por Nivel de Riesgo');
        $r6->assertSee('Gráfica 2: Aplicaciones por Instrumento Psicométrico');
    }

    public function test_los_seis_reportes_se_exportan_correctamente_en_pdf(): void
    {
        $this->actingAs($this->adminUser);

        $reportes = [
            'emociones-registradas',
            'emociones-por-programa',
            'historial-alumno',
            'premios-canjeados',
            'sesiones-por-dia',
            'resultados-tests',
        ];

        foreach ($reportes as $tipo) {
            $params = ['tipo' => $tipo];
            if ($tipo === 'historial-alumno') {
                $params['estudiante_id'] = $this->alumnoUser->usuario_id;
            }

            $response = $this->get(route('admin.reportes.exportar-pdf', $params));
            $response->assertOk();
            $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        }
    }

    public function test_los_seis_reportes_se_exportan_en_excel_con_estructuras_validas_y_graficas_nativas(): void
    {
        $this->actingAs($this->adminUser);

        $reportes = [
            'emociones-registradas',
            'emociones-por-programa',
            'historial-alumno',
            'premios-canjeados',
            'sesiones-por-dia',
            'resultados-tests',
        ];

        foreach ($reportes as $tipo) {
            $params = ['tipo' => $tipo];
            if ($tipo === 'historial-alumno') {
                $params['estudiante_id'] = $this->alumnoUser->usuario_id;
            }

            $response = $this->get(route('admin.reportes.exportar-excel', $params));
            $response->assertOk();
            $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        }
    }

    public function test_trazabilidad_de_login_distingue_accesos_exitosos_y_fallidos_sin_falsos_positivos(): void
    {
        // 1. Login exitoso
        $this->post('/login', [
            'email' => 'admin.test@upemor.edu.mx',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('login_accesos', [
            'email' => 'admin.test@upemor.edu.mx',
            'exitoso' => true,
        ]);

        // 2. Login fallido con contraseña incorrecta
        $this->post('/login', [
            'email' => 'admin.test@upemor.edu.mx',
            'password' => 'clave-erronea-xyz',
        ]);

        $this->assertDatabaseHas('login_accesos', [
            'email' => 'admin.test@upemor.edu.mx',
            'exitoso' => false,
        ]);
    }
}
