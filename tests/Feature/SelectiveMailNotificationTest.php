<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Notifications\NuevaEncuestaNotification;
use App\Notifications\NuevaEvaluacionNotification;
use App\Notifications\RecompensaHabilitadaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SelectiveMailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::create(['rol_id' => 1, 'nombre' => 'Alumno']);
        $this->alumno = User::create([
            'nombre' => 'Estudiante',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => 'Morelos',
            'email' => 'estudiante@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolAlumno->rol_id,
            'activo' => true,
        ]);
    }

    public function test_recompensa_habilitada_notification_includes_mail_channel(): void
    {
        $notif = new RecompensaHabilitadaNotification(
            nombreRecompensa: 'Tutoría Personalizada',
            materiaNombre: 'Cálculo Diferencial',
            costoPuntos: 25
        );

        $channels = $notif->via($this->alumno);
        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);

        $mailMessage = $notif->toMail($this->alumno);
        $this->assertStringContainsString('Nueva Recompensa Académica Habilitada', $mailMessage->subject);
        $this->assertStringContainsString('Tutoría Personalizada', implode(' ', $mailMessage->introLines));
        $this->assertStringContainsString('Cálculo Diferencial', implode(' ', $mailMessage->introLines));
    }

    public function test_nueva_encuesta_notification_includes_mail_channel(): void
    {
        $notif = new NuevaEncuestaNotification(
            tituloEncuesta: 'Evaluación Docente Cuatrimestral',
            encuestaId: 1,
            esAnonima: true
        );

        $channels = $notif->via($this->alumno);
        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);

        $mailMessage = $notif->toMail($this->alumno);
        $this->assertStringContainsString('Evaluación Docente Cuatrimestral', $mailMessage->subject);
        $this->assertStringContainsString('confidencial y 100% anónima', implode(' ', $mailMessage->introLines));
    }

    public function test_nueva_evaluacion_notification_includes_mail_channel_without_clinical_details(): void
    {
        $notif = new NuevaEvaluacionNotification(
            tituloInstrumento: 'Cuestionario de Hábitos de Estudio'
        );

        $channels = $notif->via($this->alumno);
        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);

        $mailMessage = $notif->toMail($this->alumno);
        $this->assertStringContainsString('Nueva Evaluación Psicoeducativa', $mailMessage->subject);
        $this->assertStringContainsString('Cuestionario de Hábitos de Estudio', implode(' ', $mailMessage->introLines));
    }
}
