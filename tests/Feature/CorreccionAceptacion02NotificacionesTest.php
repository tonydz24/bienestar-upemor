<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Notifications\NuevaEvaluacionNotification;
use App\Notifications\NuevaMeditacionNotification;
use App\Notifications\PropuestaRecompensaPendienteNotification;
use App\Notifications\RecompensaHabilitadaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorreccionAceptacion02NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    protected User $alumno;
    protected User $docente;
    protected User $admin;
    protected User $orientador;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::firstOrCreate(['rol_id' => 1], ['nombre' => 'Alumno']);
        $rolDocente = Rol::firstOrCreate(['rol_id' => 2], ['nombre' => 'Docente']);
        $rolAdmin = Rol::firstOrCreate(['rol_id' => 3], ['nombre' => 'Administrador']);
        $rolOrientador = Rol::firstOrCreate(['rol_id' => 4], ['nombre' => 'Orientador']);

        $this->alumno = User::create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => 'Soto',
            'email' => 'alumno.notif@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->docente = User::create([
            'nombre' => 'Elena',
            'apellido_paterno' => 'Vargas',
            'apellido_materno' => 'Cruz',
            'email' => 'docente.notif@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolDocente->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'General',
            'apellido_materno' => 'UPEMOR',
            'email' => 'admin.notif@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->orientador = User::create([
            'nombre' => 'Laura',
            'apellido_paterno' => 'Méndez',
            'apellido_materno' => 'Paz',
            'email' => 'orientador.notif@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolOrientador->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);
    }

    public function test_b2_b3_polling_endpoint_returns_unread_items_and_dropdown_items_without_marking_as_read(): void
    {
        $this->alumno->notify(new NuevaMeditacionNotification(
            'Respiración Diafragmática 4-7-8',
            'Control de Ansiedad',
            8,
            10
        ));

        $this->alumno->notify(new NuevaEvaluacionNotification(
            'Escala de Bienestar OMS',
            'WHO-5',
            20,
            4
        ));

        $response = $this->actingAs($this->alumno)->getJson(route('notificaciones.recientes'));

        $response->assertOk();
        $response->assertJsonStructure([
            'unread_count',
            'latest' => ['id', 'titulo', 'mensaje', 'toast_mensaje', 'tipo', 'is_unread', 'url', 'leer_url', 'created_at'],
            'unread_items',
            'dropdown_items',
        ]);

        $this->assertEquals(2, $response->json('unread_count'));
        $this->assertCount(2, $response->json('unread_items'));
        $this->assertTrue($response->json('dropdown_items.0.is_unread'));
        $this->assertTrue($response->json('dropdown_items.1.is_unread'));

        // Confirmar que consultar el endpoint de polling NO marca las notificaciones como leídas
        $this->assertEquals(2, $this->alumno->unreadNotifications()->count());
    }

    public function test_b2_b3_b4_bell_dropdown_and_5_second_toast_and_unread_highlight_render_correctly(): void
    {
        // Enviar dos notificaciones (la más reciente y una anterior) y dejar ambas sin leer
        $this->alumno->notify(new RecompensaHabilitadaNotification('Punto Extra Parcial', 'Programación', 15));
        $this->alumno->notify(new NuevaMeditacionNotification('Atención Plena Matutina', 'Enfoque', 10));

        // Verificar en la vista de notificaciones y su layout (campanita + toast de 5 segundos)
        $response = $this->actingAs($this->alumno)->get(route('notificaciones.index'));

        $response->assertOk();
        // Verificar configuración del toast emergente de 5 segundos (5000 ms)
        $response->assertSee('id="liveNotificationToast"', false);
        $response->assertSee('data-bs-delay="5000"', false);
        $response->assertSee('TOAST_DURATION_MS = 5000', false);
        $response->assertSee('bienestar_notif_session_init_', false);

        // Verificar que las notificaciones no leídas (incluida la más reciente) tengan clases is-unread es-no-leida y badge "No leída"
        $response->assertSee('is-unread es-no-leida', false);
        $response->assertSee('notif-unread-dot', false);
        $response->assertSee('No leída');
        $response->assertSee('Atención Plena Matutina');
        $response->assertSee('Punto Extra Parcial');

        // Confirmar que abrir la página o el dropdown NO marca ninguna notificación como leída
        $this->assertEquals(2, $this->alumno->fresh()->unreadNotifications()->count());
    }

    public function test_b4_all_four_roles_have_access_to_notifications_module_and_polling(): void
    {
        foreach ([$this->alumno, $this->docente, $this->admin, $this->orientador] as $user) {
            $user->notify(new PropuestaRecompensaPendienteNotification(
                'Exención de Tarea',
                'Prof. Prueba',
                25
            ));

            $indexResp = $this->actingAs($user)->get(route('notificaciones.index'));
            $indexResp->assertOk();
            $indexResp->assertSee('id="notifDropdownList"', false);
            $indexResp->assertSee('id="liveNotificationToast"', false);
            $indexResp->assertSee('is-unread es-no-leida', false);

            $pollResp = $this->actingAs($user)->getJson(route('notificaciones.recientes'));
            $pollResp->assertOk();
            $this->assertEquals(1, $pollResp->json('unread_count'));
        }
    }

    public function test_b7_background_polling_header_does_not_reset_session_inactivity_timer(): void
    {
        $initialTimestamp = now()->subMinutes(10)->timestamp;

        $response = $this->actingAs($this->alumno)
            ->withSession(['last_activity_time' => $initialTimestamp])
            ->withHeaders(['X-Background-Polling' => 'true'])
            ->getJson(route('notificaciones.recientes'));

        $response->assertOk();
        $this->assertEquals($initialTimestamp, session('last_activity_time'));
    }

    public function test_b8_user_cannot_read_or_modify_another_users_notifications(): void
    {
        $this->docente->notify(new PropuestaRecompensaPendienteNotification(
            'Recompensa Privada',
            'Prof. Elena',
            30
        ));

        $notifDocente = $this->docente->unreadNotifications()->first();
        $this->assertNotNull($notifDocente);

        // El alumno intenta marcar como leída o redirigir a la notificación del docente -> 404
        $this->actingAs($this->alumno)
            ->postJson(route('notificaciones.leer', $notifDocente->id))
            ->assertNotFound();

        $this->actingAs($this->alumno)
            ->get(route('notificaciones.ir', $notifDocente->id))
            ->assertNotFound();

        // La notificación del docente permanece intacta y no leída
        $this->assertNull($notifDocente->fresh()->read_at);
    }
}
