<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Notifications\RecompensaHabilitadaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::create(['rol_id' => 1, 'nombre' => 'Alumno']);
        $this->alumno = User::create([
            'nombre' => 'Manuel',
            'apellido_paterno' => 'Díaz',
            'apellido_materno' => 'Hernández',
            'email' => 'alumno.test@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);
    }

    public function test_it_marks_notification_as_read_and_redirects_via_ir_route(): void
    {
        $this->actingAs($this->alumno);

        $this->alumno->notify(new RecompensaHabilitadaNotification(
            nombreRecompensa: 'Asesoría Extra',
            materiaNombre: 'Programación Web',
            costoPuntos: 15
        ));

        $notification = $this->alumno->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $response = $this->get(route('notificaciones.ir', $notification->id));

        $response->assertRedirect(route('alumno.recompensas.index'));

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
        $this->assertEquals(0, $this->alumno->unreadNotifications()->count());
    }

    public function test_mark_as_read_post_redirects_to_notifications_index(): void
    {
        $this->actingAs($this->alumno);

        $this->alumno->notify(new RecompensaHabilitadaNotification(
            nombreRecompensa: 'Punto Extra',
            materiaNombre: 'Bases de Datos',
            costoPuntos: 10
        ));

        $notification = $this->alumno->unreadNotifications()->first();

        $response = $this->post(route('notificaciones.leer', $notification->id), [
            'filtro' => 'todas',
        ]);

        $response->assertRedirect(route('notificaciones.index', ['filtro' => 'todas']));
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_as_read_returns_json_when_requested_via_ajax(): void
    {
        $this->actingAs($this->alumno);

        $this->alumno->notify(new RecompensaHabilitadaNotification(
            nombreRecompensa: 'Punto Extra',
            materiaNombre: 'Redes',
            costoPuntos: 12
        ));

        $notification = $this->alumno->unreadNotifications()->first();

        $response = $this->postJson(route('notificaciones.leer', $notification->id));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'id' => $notification->id,
            'unread_count' => 0,
        ]);
    }

    public function test_mark_all_as_read_marks_all_user_notifications(): void
    {
        $this->actingAs($this->alumno);

        $this->alumno->notify(new RecompensaHabilitadaNotification('R1', 'M1', 5));
        $this->alumno->notify(new RecompensaHabilitadaNotification('R2', 'M2', 10));

        $this->assertEquals(2, $this->alumno->unreadNotifications()->count());

        $response = $this->post(route('notificaciones.marcar-todas'));
        $response->assertRedirect(route('notificaciones.index'));

        $this->assertEquals(0, $this->alumno->unreadNotifications()->count());
    }
}
