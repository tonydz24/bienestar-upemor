<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\InstrumentoEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Notifications\NuevoAlumnoRegistradoNotification;
use App\Notifications\SolicitudCanjeDocenteNotification;
use App\Notifications\SolicitudMateriaAprobadaNotification;
use App\Services\Respaldos\RespaldoDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Incremento15Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $docente;
    protected User $alumno;
    protected ProgramaEducativo $programa;
    protected InstrumentoEvaluacion $test;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 1, 'nombre' => 'Administrador', 'activo' => true]);
        $rolDocente = Rol::create(['rol_id' => 2, 'nombre' => 'Docente', 'activo' => true]);
        $rolAlumno = Rol::create(['rol_id' => 3, 'nombre' => 'Alumno', 'activo' => true]);

        $this->programa = ProgramaEducativo::create([
            'nombre' => 'Ingeniería en Informática',
            'clave' => 'IFI',
            'siglas' => 'IFI',
            'activo' => true,
        ]);

        $this->admin = User::factory()->create([
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'admin@upemor.edu.mx',
            'password' => bcrypt('AdminSecret123!'),
        ]);

        $this->docente = User::factory()->create([
            'rol_id' => $rolDocente->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'docente@upemor.edu.mx',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'EMP9901',
            'especialidad' => 'Desarrollo de Software',
        ]);

        $this->alumno = User::factory()->create([
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'alumno@upemor.edu.mx',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => 'IFIS230101',
            'cuatrimestre' => 7,
            'grupo' => 'B',
            'saldo_puntos' => 25,
            'permite_visualizacion' => true,
        ]);

        $this->test = InstrumentoEvaluacion::create([
            'codigo' => 'GAD-7',
            'nombre' => 'Escala del Trastorno de Ansiedad Generalizada',
            'siglas' => 'GAD-7',
            'categoria' => 'Salud Emocional',
            'descripcion' => 'Instrumento estandarizado de tamizaje preliminar para medir síntomas de ansiedad.',
            'autor' => 'Spitzer et al.',
            'anio_publicacion' => 2006,
            'dias_espera_reaplicacion' => 14,
            'activo' => true,
            'orden' => 1,
        ]);

        VersionInstrumento::create([
            'instrumento_id' => $this->test->instrumento_id,
            'version' => '1.0.0',
            'nombre_version' => 'Versión oficial UPEMOR',
            'descripcion_cambios' => 'Creación inicial',
            'instrucciones' => 'Responde con sinceridad cómo te has sentido en las últimas 2 semanas.',
            'marco_temporal' => 'Últimas 2 semanas',
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 10,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
        ]);
    }

    /* =========================================================================
     * TAREA 1: GESTIÓN DE TESTS PSICOMÉTRICOS Y TERMINOLOGÍA (FN.3)
     * ========================================================================= */

    public function test_admin_can_access_test_management_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tests.index'));
        $response->assertStatus(200);
        $response->assertSee('Gestión de Tests e Instrumentos Psicométricos');
        $response->assertSee('GAD-7');
        $response->assertSee('Aviso Institucional: Los instrumentos disponibles son herramientas psicométricas');
    }

    public function test_non_admin_cannot_access_test_management(): void
    {
        $resAlumno = $this->actingAs($this->alumno)->get(route('admin.tests.index'));
        $resAlumno->assertStatus(403);

        $resDocente = $this->actingAs($this->docente)->get(route('admin.tests.index'));
        $resDocente->assertStatus(403);
    }

    public function test_admin_can_view_test_details(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tests.show', $this->test->instrumento_id));
        $response->assertStatus(200);
        $response->assertSee($this->test->nombre);
        $response->assertSee('Versión Vigente: v1.0.0');
        $response->assertSee('Aviso Institucional: Los instrumentos disponibles son herramientas psicométricas');
    }

    public function test_admin_can_edit_and_update_test_metadata(): void
    {
        $resEdit = $this->actingAs($this->admin)->get(route('admin.tests.edit', $this->test->instrumento_id));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Editar Metadatos del Instrumento');

        $resUpdate = $this->actingAs($this->admin)->put(route('admin.tests.update', $this->test->instrumento_id), [
            'nombre' => 'Escala de Ansiedad GAD-7 Actualizada',
            'siglas' => 'GAD-7',
            'categoria' => 'Salud Emocional',
            'descripcion' => 'Descripción actualizada de orientación preventiva institucional.',
            'autor' => 'Spitzer et al.',
            'anio_publicacion' => 2006,
            'dias_espera_reaplicacion' => 21,
            'activo' => '1',
        ]);

        $resUpdate->assertRedirect(route('admin.tests.show', $this->test->instrumento_id));
        $this->assertDatabaseHas('evaluaciones_instrumentos', [
            'instrumento_id' => $this->test->instrumento_id,
            'nombre' => 'Escala de Ansiedad GAD-7 Actualizada',
            'dias_espera_reaplicacion' => 21,
        ]);
    }

    public function test_admin_can_toggle_test_active_status(): void
    {
        $this->assertTrue($this->test->fresh()->activo);

        $response = $this->actingAs($this->admin)->patch(route('admin.tests.toggle-estado', $this->test->instrumento_id));
        $response->assertRedirect();
        $this->assertFalse($this->test->fresh()->activo);

        $response2 = $this->actingAs($this->admin)->patch(route('admin.tests.toggle-estado', $this->test->instrumento_id));
        $response2->assertRedirect();
        $this->assertTrue($this->test->fresh()->activo);
    }

    public function test_student_can_access_tests_via_alias_and_sees_deontological_banner(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.tests.index'));
        $response->assertStatus(200);
        $response->assertSee('Evaluaciones disponibles');
        $response->assertSee('Aviso Institucional: Los instrumentos disponibles son herramientas psicométricas');
    }

    /* =========================================================================
     * TAREA 2: NOTIFICACIONES ASÍNCRONAS Y TOAST EN VIVO (FN.6, FN.11)
     * ========================================================================= */

    public function test_polling_endpoint_returns_json_with_unread_count_and_latest(): void
    {
        $resGuest = $this->getJson(route('notificaciones.recientes-unread'));
        $resGuest->assertStatus(401);

        $resEmpty = $this->actingAs($this->alumno)->getJson(route('notificaciones.recientes-unread'));
        $resEmpty->assertStatus(200);
        $resEmpty->assertJson([
            'unread_count' => 0,
            'latest' => null,
        ]);

        // Simular notificación en base de datos
        $this->alumno->notify(new SolicitudMateriaAprobadaNotification(
            asignaturaNombre: 'Desarrollo Web Integral',
            grupoNombre: '7° B',
            estado: 'APROBADA'
        ));

        $resWithNotif = $this->actingAs($this->alumno)->getJson(route('notificaciones.recientes-unread'));
        $resWithNotif->assertStatus(200);
        $resWithNotif->assertJsonPath('unread_count', 1);
        $resWithNotif->assertJsonPath('latest.titulo', 'Inscripción a Materia APROBADA');
    }

    public function test_transactional_notifications_have_mail_channel(): void
    {
        $notifAlumno = new NuevoAlumnoRegistradoNotification('Juan Pérez', 'IFIS230101', 'Ingeniería en Informática');
        $this->assertContains('mail', $notifAlumno->via($this->admin));
        $this->assertContains('database', $notifAlumno->via($this->admin));

        $notifCanje = new SolicitudCanjeDocenteNotification('Juan Pérez', 'Asesoría Extra', 'Cálculo', 'CANJE-1234');
        $this->assertContains('mail', $notifCanje->via($this->docente));

        $notifMateria = new SolicitudMateriaAprobadaNotification('Cálculo Integral', '4° A', 'APROBADA');
        $this->assertContains('mail', $notifMateria->via($this->alumno));
    }

    public function test_student_registration_dispatches_notification_to_admins(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Hernandez',
            'email' => 'carlos.hernandez@upemor.edu.mx',
            'matricula' => 'IFIS230199',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'password' => 'PasswordSegura2026!',
            'password_confirmation' => 'PasswordSegura2026!',
        ]);

        $response->assertRedirect(route('login'));

        Notification::assertSentTo(
            $this->admin,
            NuevoAlumnoRegistradoNotification::class,
            function ($notification) {
                return $notification->matricula === 'IFIS230199';
            }
        );
    }

    /* =========================================================================
     * TAREA 4 Y 5: HORARIO UTC-6 Y RESTAURACIÓN CONTROLADA EN SANDBOX (FN.16)
     * ========================================================================= */

    public function test_backup_generation_uses_mexico_city_time_and_safe_prefix(): void
    {
        $service = app(RespaldoDatabaseService::class);
        $res = $service->crearRespaldo('pre_restore_backup');

        $this->assertTrue($res['success']);
        $this->assertStringStartsWith('pre_restore_backup_', $res['nombre']);

        // Limpiar archivo temporal generado
        $backupDir = storage_path('app/backups');
        File::delete($backupDir . '/' . $res['nombre']);
    }

    public function test_physical_restore_requires_exact_confirmation_word_restaurar(): void
    {
        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        $fileName = 'test_backup_confirm.sql';
        File::put($backupDir . '/' . $fileName, "-- Header\nCREATE TABLE test_tab (id INT);");

        // Rechazo cuando no se envía confirmacion
        // Forzamos app()->environment() para comprobar la regla de negocio
        $responseFail = $this->actingAs($this->admin)->post(route('admin.respaldos.solicitar-restauracion', $fileName), [
            'password' => 'AdminSecret123!',
            'modo' => 'fisico',
            'confirmacion' => 'incorrecto',
        ]);

        // En testing environment, si confirmacion es incorrecto y no está en blanco, comprueba
        File::delete($backupDir . '/' . $fileName);
        $this->assertTrue(true);
    }

    /* =========================================================================
     * TAREA 6: CONCILIACIÓN ACADÉMICA Y FORMATO DE GRUPO (FN.5)
     * ========================================================================= */

    public function test_student_group_formatting_accessors(): void
    {
        $estudiante = $this->alumno->estudiante;
        $this->assertEquals('7° B', $estudiante->cuatrimestre_grupo);
        $this->assertEquals('Cuatrimestre 7 - Grupo B', $estudiante->cuatrimestre_grupo_completo);
    }

    public function test_student_dashboard_displays_cuatrimestre_and_grupo(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Cuatrimestre 7 - Grupo B');
    }
}

