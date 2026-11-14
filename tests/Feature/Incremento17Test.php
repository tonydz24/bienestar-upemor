<?php

namespace Tests\Feature;

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
use App\Services\Recompensas\RecompensaService;
use App\Services\Reportes\ReporteInstitucionalService;
use App\Services\Respaldos\RespaldoDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class Incremento17Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $docente1;
    protected User $docente2;
    protected User $alumno;
    protected Estudiante $estudiante;
    protected ProgramaEducativo $programaIFI;
    protected PeriodoAcademico $periodo;
    protected Grupo $grupoA;
    protected Grupo $grupoB;
    protected Asignatura $asignatura;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 1, 'nombre' => 'Administrador', 'activo' => true]);
        $rolDocente = Rol::create(['rol_id' => 2, 'nombre' => 'Docente', 'activo' => true]);
        $rolAlumno = Rol::create(['rol_id' => 3, 'nombre' => 'Alumno', 'activo' => true]);

        $this->programaIFI = ProgramaEducativo::create([
            'nombre' => 'Ingeniería en Informática',
            'clave' => 'IFI',
            'siglas' => 'IFI',
            'activo' => true,
        ]);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-1',
            'nombre' => 'Enero - Abril 2026',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-30',
            'activo' => true,
        ]);

        $this->grupoA = Grupo::create([
            'programa_id' => $this->programaIFI->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 7,
            'nombre' => 'A',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupoB = Grupo::create([
            'programa_id' => $this->programaIFI->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 7,
            'nombre' => 'B',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->asignatura = Asignatura::create([
            'programa_id' => $this->programaIFI->programa_id,
            'cuatrimestre' => 7,
            'clave' => 'IFI-701',
            'nombre' => 'Arquitectura de Software Distribuido',
            'creditos' => 8,
            'horas_semana' => 5,
            'tipo' => 'ORDINARIA',
            'activo' => true,
        ]);

        $this->admin = User::factory()->create([
            'rol_id' => 1,
            'estado' => 'ACTIVO',
            'nombre' => 'Administrador',
            'apellido_paterno' => 'Sistema',
            'email' => 'admin@upemor.edu.mx',
        ]);

        $this->docente1 = User::factory()->create([
            'rol_id' => 2,
            'estado' => 'ACTIVO',
            'nombre' => 'Profesor',
            'apellido_paterno' => 'Uno',
            'email' => 'profesor1@upemor.edu.mx',
        ]);
        Docente::create([
            'usuario_id' => $this->docente1->usuario_id,
            'numero_empleado' => 'DOC-001',
            'tipo_contrato' => 'TIEMPO_COMPLETO',
        ]);

        $this->docente2 = User::factory()->create([
            'rol_id' => 2,
            'estado' => 'ACTIVO',
            'nombre' => 'Profesor',
            'apellido_paterno' => 'Dos',
            'email' => 'profesor2@upemor.edu.mx',
        ]);
        Docente::create([
            'usuario_id' => $this->docente2->usuario_id,
            'numero_empleado' => 'DOC-002',
            'tipo_contrato' => 'TIEMPO_COMPLETO',
        ]);

        $this->alumno = User::factory()->create([
            'rol_id' => 3,
            'estado' => 'ACTIVO',
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
            'email' => 'carlos.ramirez@upemor.edu.mx',
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'matricula' => '20261IFI001',
            'programa_id' => $this->programaIFI->programa_id,
            'cuatrimestre' => 7,
            'grupo' => 'A',
            'turno' => 'MATUTINO',
            'saldo_puntos' => 150,
            'activo' => true,
        ]);
    }

    /**
     * 1. FF.4: Sesión inactiva por más de 15 minutos se invalida.
     * Retorna 401 JSON para llamadas AJAX/Background y redirect para llamadas estándar.
     */
    public function test_sesion_expira_a_los_15_minutos_de_inactividad_real(): void
    {
        // Petición estándar con inactividad > 900s
        $response = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => time() - 905])
            ->get(route('alumno.dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // Petición AJAX/JSON con inactividad > 900s
        $responseJson = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => time() - 905])
            ->getJson(route('notificaciones.recientes-unread'), [
                'X-Background-Polling' => 'true',
            ]);

        $responseJson->assertStatus(401);
        $responseJson->assertJson([
            'session_expired' => true,
            'redirect' => route('login'),
        ]);
    }

    /**
     * 2. FF.4 / FN.14: El sondeo en segundo plano (X-Background-Polling) NO resetea el timeout de sesión.
     */
    public function test_sondeo_notificaciones_lleva_header_y_no_resetea_timeout_de_sesion(): void
    {
        $horaActividadPrevia = time() - 300; // 5 minutos de inactividad

        $response = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => $horaActividadPrevia])
            ->getJson(route('notificaciones.recientes-unread'), [
                'X-Background-Polling' => 'true',
            ]);

        $response->assertOk();
        // Verificar que la hora de inactividad previa NO fue actualizada al tiempo actual
        $this->assertEquals($horaActividadPrevia, session('last_activity_time'));
    }

    /**
     * 3. Navegación Alumno: El menú lateral del Alumno contiene el enlace visible a "Mis asignaturas".
     */
    public function test_menu_alumno_contiene_enlace_mis_asignaturas(): void
    {
        $response = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => time()])
            ->get(route('alumno.dashboard'));

        $response->assertOk();
        $response->assertSee(route('alumno.academico.index'));
        $response->assertSee('Mis Asignaturas');
    }

    /**
     * 4. Integración Académica: Evitar doble inscripción de una misma asignatura entre diferentes docentes.
     */
    public function test_alumno_no_puede_solicitar_doble_inscripcion_de_misma_materia_con_diferentes_docentes(): void
    {
        // Docente 1 ofrece la materia para el Grupo A
        $asigDocente1 = DocenteAsignacion::create([
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
            'activo' => true,
        ]);

        // Docente 2 ofrece la MISMA materia para el Grupo A (ej. sección compartida o suplencia)
        $asigDocente2 = DocenteAsignacion::create([
            'docente_id' => $this->docente2->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
            'activo' => true,
        ]);

        // El alumno solicita primero con Docente 1
        $solicitud1 = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => time()])
            ->post(route('alumno.academico.solicitar'), [
                'asignacion_id' => $asigDocente1->asignacion_id,
                'motivo_solicitud' => 'Inscripción regular con profesor 1',
            ]);
        $solicitud1->assertRedirect(route('alumno.academico.index'));
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'estudiante_id' => $this->alumno->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'asignacion_id' => $asigDocente1->asignacion_id,
            'estado' => EstudianteAsignaturaInscripcion::ESTADO_PENDIENTE,
        ]);

        // Al consultar la oferta, la materia ya NO debe aparecer disponible para solicitar con Docente 2
        $indexResponse = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => time()])
            ->get(route('alumno.academico.index'));
        $indexResponse->assertOk();
        $this->assertFalse(
            $indexResponse->viewData('ofertaDisponible')->contains('asignacion_id', $asigDocente2->asignacion_id),
            'La materia ya solicitada no debe mostrarse como disponible con otro docente'
        );

        // Si intenta forzar la solicitud con Docente 2 vía POST, debe ser bloqueada
        $solicitud2 = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => time()])
            ->post(route('alumno.academico.solicitar'), [
                'asignacion_id' => $asigDocente2->asignacion_id,
                'motivo_solicitud' => 'Intento de doble inscripción con profesor 2',
            ]);
        $solicitud2->assertSessionHas('error');
    }

    /**
     * 5. Solicitud Docente: Soporta apertura general para todos los subgrupos del cuatrimestre.
     */
    public function test_docente_puede_solicitar_apertura_general_para_todos_los_grupos_del_cuatrimestre(): void
    {
        $response = $this->actingAs($this->docente1)
            ->withSession(['last_activity_time' => time()])
            ->post(route('docente.academico.solicitar.store'), [
                'asignatura_id' => $this->asignatura->asignatura_id,
                'periodo_id' => $this->periodo->periodo_id,
                'modalidad_apertura' => 'todos',
                'motivo_solicitud' => 'Apertura integral para los grupos A y B del 7° cuatrimestre',
            ]);

        $response->assertRedirect(route('docente.academico.index'));
        $response->assertSessionHas('status');

        // Se deben haber generado solicitudes para ambos grupos (A y B)
        $this->assertDatabaseHas('docente_asignaciones', [
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => DocenteAsignacion::ESTADO_PENDIENTE,
        ]);

        $this->assertDatabaseHas('docente_asignaciones', [
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => DocenteAsignacion::ESTADO_PENDIENTE,
        ]);
    }

    /**
     * 6. Recompensas: Aislamiento estricto de canje por inscripción aprobada.
     */
    public function test_recompensas_exigen_inscripcion_formal_en_materia_y_grupo(): void
    {
        $catRecompensa = RecompensaCatalogo::create([
            'nombre' => 'Punto extra en práctica',
            'descripcion' => 'Acreditación adicional en entregable de laboratorio.',
            'costo_puntos_base' => 50,
            'categoria' => 'ACADEMICA',
            'activo' => true,
        ]);

        $asignacion = DocenteAsignacion::create([
            'docente_id' => $this->docente1->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
            'activo' => true,
        ]);

        $activacion = RecompensaActivada::create([
            'recompensa_id' => $catRecompensa->recompensa_id,
            'asignacion_id' => $asignacion->asignacion_id,
            'cupo_maximo' => 10,
            'cupo_disponible' => 10,
            'costo_puntos' => 50,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addMonth()->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        $recompensaService = app(RecompensaService::class);

        // Sin inscripción en grupo o materia: Debe lanzar excepción de dominio
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Solo los estudiantes');

        $recompensaService->solicitarCanje(
            alumnoUser: $this->alumno,
            activacionId: $activacion->activacion_id
        );
    }

    /**
     * 7. Notificaciones: Marcar como leída permanece en /notificaciones o retorna JSON.
     */
    public function test_notificacion_marcar_leida_permanece_en_index_o_retorna_json(): void
    {
        $notif = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\AvisoGeneralNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->alumno->usuario_id,
            'data' => [
                'titulo' => 'Aviso Institucional',
                'mensaje' => 'Aviso de prueba',
                'url' => '/alumno/dashboard',
            ],
            'read_at' => null,
        ]);

        // Con stay=1, redirige a notificaciones.index
        $response = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => time()])
            ->post(route('notificaciones.leer', $notif->id), ['stay' => 1]);

        $response->assertRedirect(route('notificaciones.index'));
        $this->assertNotNull($notif->fresh()->read_at);

        // Como JSON
        $notif2 = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\AvisoGeneralNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->alumno->usuario_id,
            'data' => [
                'titulo' => 'Aviso 2',
                'mensaje' => 'Aviso de prueba 2',
            ],
            'read_at' => null,
        ]);

        $responseJson = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => time()])
            ->postJson(route('notificaciones.leer', $notif2->id));

        $responseJson->assertOk();
        $responseJson->assertJson(['success' => true]);
        $this->assertNotNull($notif2->fresh()->read_at);
    }

    /**
     * 8. Reportes Dinámicos: Historial individual de alumno opera de forma segura sin estudiante seleccionado.
     */
    public function test_reporte_historial_alumno_opera_seguro_con_o_sin_estudiante_seleccionado(): void
    {
        $reporteService = app(ReporteInstitucionalService::class);

        // Sin estudiante seleccionado
        $datosSinEst = $reporteService->reporteHistorialAlumno([]);
        $this->assertNull($datosSinEst['estudiante']);
        $this->assertCount(0, $datosSinEst['historial']);

        // Con estudiante seleccionado
        $datosConEst = $reporteService->reporteHistorialAlumno([
            'estudiante_id' => $this->estudiante->usuario_id,
        ]);
        $this->assertNotNull($datosConEst['estudiante']);
        $this->assertEquals($this->estudiante->matricula, $datosConEst['estudiante']->matricula);
    }

    /**
     * 9. Respaldos y Restauración: Política inquebrantable de seguridad (ALLOW_WEB_RESTORE=false).
     * No se permite ejecución destructiva directa en sandbox.
     */
    public function test_politica_segura_de_restauracion_no_permite_ejecucion_destructiva_en_sandbox(): void
    {
        Config::set('database.allow_web_restore', false);

        $respaldoService = app(RespaldoDatabaseService::class);

        // Verificación técnica estática es 100% segura
        $archivos = $respaldoService->listarRespaldos();
        $this->assertIsArray($archivos);

        // Enviar contraseña incorrecta en solicitud de restauración
        $response = $this->actingAs($this->admin)
            ->withSession(['last_activity_time' => time()])
            ->post(route('admin.respaldos.solicitar-restauracion', 'backup_ficticio.sql'), [
                'password' => 'clave_incorrecta',
                'modo' => 'simulacion',
            ]);

        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('error');
    }
}
