<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\Notificacion;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\AsignacionDocenteResueltaNotification;
use App\Notifications\InscripcionEstudianteResueltaNotification;
use App\Notifications\SolicitudDocenteCreadaNotification;
use App\Notifications\SolicitudInscripcionEstudianteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Incremento10Test extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $docenteUser;
    protected User $alumnoUser;
    protected PeriodoAcademico $periodo;
    protected ProgramaEducativo $programa;
    protected Grupo $grupoA;
    protected Grupo $grupoB;
    protected Asignatura $asignatura;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 1, 'nombre' => 'Administrador']);
        $rolDocente = Rol::create(['rol_id' => 2, 'nombre' => 'Docente']);
        $rolAlumno = Rol::create(['rol_id' => 3, 'nombre' => 'Alumno']);
        $rolOrientador = Rol::create(['rol_id' => 4, 'nombre' => 'Orientador']);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-18',
            'activo' => true,
        ]);

        $this->programa = ProgramaEducativo::create([
            'clave' => 'ITI',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);

        $this->grupoA = Grupo::create([
            'clave' => 'ITI-9A',
            'nombre' => 'Grupo 9A ITI',
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 9,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupoB = Grupo::create([
            'clave' => 'ITI-9B',
            'nombre' => 'Grupo 9B ITI',
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'cuatrimestre' => 9,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->asignatura = Asignatura::create([
            'clave' => 'ITI-SEG',
            'nombre' => 'Seguridad Informática Avanzada',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 9,
            'creditos' => 6,
            'horas_semana' => 4,
            'activo' => true,
        ]);

        $this->adminUser = User::create([
            'nombre' => 'Arantxa',
            'apellido_paterno' => 'Hernandez',
            'email' => 'arantxa.admin@upemor.edu.mx',
            'password' => bcrypt('AdminPass123!'),
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
        ]);

        $this->docenteUser = User::create([
            'nombre' => 'Elizabeth',
            'apellido_paterno' => 'Odette',
            'email' => 'elizabeth.docente@upemor.edu.mx',
            'password' => bcrypt('DocentePass123!'),
            'rol_id' => $rolDocente->rol_id,
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docenteUser->usuario_id,
            'numero_empleado' => 'EMP-001',
            'area_adscripcion' => 'Informática',
            'cargo' => 'Profesor de Tiempo Completo',
            'telefono_contacto' => '7770001122',
        ]);

        $this->alumnoUser = User::create([
            'nombre' => 'Manuel',
            'apellido_paterno' => 'Antonio',
            'email' => 'manuel.alumno@upemor.edu.mx',
            'password' => bcrypt('AlumnoPass123!'),
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'matricula' => '2026ITI001',
            'programa_id' => $this->programa->programa_id,
            'grupo' => 'Grupo 9A ITI',
            'cuatrimestre' => 9,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);
    }

    public function test_full_academic_flow_docente_request_and_admin_approval(): void
    {
        Notification::fake();

        // 1. Docente solicita asignación a Grupo A
        $response = $this->actingAs($this->docenteUser)->post(route('docente.academico.solicitar'), [
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'motivo' => 'Cátedra titular de seguridad informática.',
        ]);

        $response->assertRedirect(route('docente.academico.index'));
        $this->assertDatabaseHas('docente_asignaciones', [
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'estado' => 'PENDIENTE',
        ]);

        Notification::assertSentTo($this->adminUser, SolicitudDocenteCreadaNotification::class);

        $asignacion = DocenteAsignacion::where('docente_id', $this->docenteUser->usuario_id)->first();

        // 2. Admin aprueba la solicitud
        $responseAdmin = $this->actingAs($this->adminUser)->post(route('admin.docente-asignaciones.aprobar', $asignacion->asignacion_id), [
            'motivo_resolucion' => 'Aprobado conforme al perfil curricular.',
        ]);

        $responseAdmin->assertRedirect(route('admin.docente-asignaciones.index'));
        $this->assertDatabaseHas('docente_asignaciones', [
            'asignacion_id' => $asignacion->asignacion_id,
            'estado' => 'APROBADA',
            'motivo_resolucion' => 'Aprobado conforme al perfil curricular.',
            'activo' => 1,
        ]);

        Notification::assertSentTo($this->docenteUser, AsignacionDocenteResueltaNotification::class);
    }

    public function test_student_cannot_enroll_in_subjects_of_different_group(): void
    {
        // Crear asignación docente en Grupo B
        $asignacionGrupoB = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'APROBADA',
            'activo' => true,
        ]);

        // Alumno está en Grupo A. Debe ser rechazado al intentar inscribirse en Grupo B
        $response = $this->actingAs($this->alumnoUser)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asignacionGrupoB->asignacion_id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('estudiante_asignatura_inscripciones', [
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'asignacion_id' => $asignacionGrupoB->asignacion_id,
        ]);
    }

    public function test_student_can_enroll_in_own_group_subject_and_teacher_approves(): void
    {
        Notification::fake();

        // Asignación aprobada en Grupo A (el grupo del alumno)
        $asignacionGrupoA = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'APROBADA',
            'activo' => true,
        ]);

        // Alumno solicita inscripción en su grupo
        $response = $this->actingAs($this->alumnoUser)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asignacionGrupoA->asignacion_id,
        ]);

        $response->assertRedirect(route('alumno.academico.index'));
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'asignacion_id' => $asignacionGrupoA->asignacion_id,
            'estado' => 'PENDIENTE',
        ]);

        Notification::assertSentTo($this->docenteUser, SolicitudInscripcionEstudianteNotification::class);

        $inscripcion = EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumnoUser->usuario_id)->first();

        // Docente aprueba la inscripción del alumno
        $responseDocente = $this->actingAs($this->docenteUser)->post(route('docente.academico.inscripciones.aprobar', $inscripcion), [
            'motivo_resolucion' => 'Aceptado en el grupo 9A.',
        ]);

        $responseDocente->assertRedirect(route('docente.academico.index'));
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones', [
            'inscripcion_materia_id' => $inscripcion->inscripcion_materia_id,
            'estado' => 'APROBADA',
            'motivo_resolucion' => 'Aceptado en el grupo 9A.',
        ]);

        Notification::assertSentTo($this->alumnoUser, InscripcionEstudianteResueltaNotification::class);
    }

    public function test_notifications_center_shows_notifications_and_marks_as_read(): void
    {
        // Enviar notificación a la base de datos real
        $this->adminUser->notify(new SolicitudDocenteCreadaNotification(
            docenteNombre: 'Elizabeth Odette',
            asignaturaNombre: 'Seguridad Informática Avanzada',
            grupoNombre: 'ITI-9A'
        ));

        $this->assertEquals(1, $this->adminUser->unreadNotifications()->count());

        // Ver centro de notificaciones
        $response = $this->actingAs($this->adminUser)->get(route('notificaciones.index'));
        $response->assertStatus(200);
        $response->assertSee('Seguridad Informática Avanzada');

        // Marcar todas como leídas
        $responseMark = $this->actingAs($this->adminUser)->post(route('notificaciones.marcar-todas'));
        $responseMark->assertRedirect();
        $this->assertEquals(0, $this->adminUser->fresh()->unreadNotifications()->count());
    }

    public function test_dashboards_render_academic_indicators(): void
    {
        // 1. Docente con solicitud de inscripción pendiente
        $asignacion = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'APROBADA',
            'activo' => true,
        ]);

        EstudianteAsignaturaInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'asignacion_id' => $asignacion->asignacion_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'PENDIENTE',
            'solicitado_en' => now(),
        ]);

        $responseDocente = $this->actingAs($this->docenteUser)->get(route('docente.dashboard'));
        $responseDocente->assertStatus(200);
        $responseDocente->assertSee('Solicitudes de inscripción pendientes');

        // 2. Alumno con materia inscrita aprobada
        EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumnoUser->usuario_id)
            ->update(['estado' => 'APROBADA']);

        $responseAlumno = $this->actingAs($this->alumnoUser)->get(route('alumno.dashboard'));
        $responseAlumno->assertStatus(200);
        $responseAlumno->assertSee('Carga Académica y Horarios');
        $responseAlumno->assertSee('1 materia(s)');

        // 3. Admin con solicitud de cátedra pendiente
        DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'PENDIENTE',
            'activo' => false,
        ]);

        $responseAdmin = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Cátedras docentes por autorizar');
    }
}
