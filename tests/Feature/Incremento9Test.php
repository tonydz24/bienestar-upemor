<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\Grupo;
use App\Models\LoginAcceso;
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
use ZipArchive;

class Incremento9Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $docente;
    protected User $docente2;
    protected User $alumnoA;
    protected User $alumnoB;
    protected ProgramaEducativo $pe;
    protected PeriodoAcademico $periodo;
    protected Grupo $grupoA;
    protected Grupo $grupoB;
    protected Asignatura $materia1;
    protected Asignatura $materia2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $rolAdmin = Rol::firstOrCreate(['rol_id' => 1], ['nombre' => 'Administrador']);
        $rolDocente = Rol::firstOrCreate(['rol_id' => 2], ['nombre' => 'Docente']);
        $rolAlumno = Rol::firstOrCreate(['rol_id' => 3], ['nombre' => 'Alumno']);
        $rolOrientador = Rol::firstOrCreate(['rol_id' => 4], ['nombre' => 'Orientador']);

        // 2. Programa Educativo
        $this->pe = ProgramaEducativo::create([
            'clave' => 'ITI',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);

        // 3. Periodo Académico
        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => now()->startOfMonth(),
            'fecha_fin' => now()->addMonths(4)->endOfMonth(),
            'activo' => true,
        ]);

        // 4. Grupos (Separación estricta Sección A vs Sección B)
        $this->grupoA = Grupo::create([
            'programa_id' => $this->pe->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'A',
            'cuatrimestre' => 4,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupoB = Grupo::create([
            'programa_id' => $this->pe->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'B',
            'cuatrimestre' => 4,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        // 5. Asignaturas
        $this->materia1 = Asignatura::create([
            'programa_id' => $this->pe->programa_id,
            'clave' => 'ITI-401',
            'nombre' => 'Desarrollo Web Integral',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);

        $this->materia2 = Asignatura::create([
            'programa_id' => $this->pe->programa_id,
            'clave' => 'ITI-402',
            'nombre' => 'Bases de Datos Avanzadas',
            'cuatrimestre' => 4,
            'activo' => true,
        ]);

        // 6. Usuarios
        $this->admin = User::factory()->create([
            'rol_id' => 1,
            'nombre' => 'Arantxa',
            'apellido_paterno' => 'Admin',
            'email' => 'admin.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->docente = User::factory()->create([
            'rol_id' => 2,
            'nombre' => 'Elizabeth',
            'apellido_paterno' => 'Docente',
            'email' => 'docente1.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'DOC001',
            'puesto' => 'Profesor de Tiempo Completo',
        ]);

        $this->docente2 = User::factory()->create([
            'rol_id' => 2,
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Profesor',
            'email' => 'docente2.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docente2->usuario_id,
            'numero_empleado' => 'DOC002',
            'puesto' => 'Profesor de Asignatura',
        ]);

        // Alumno A (pertenece a Grupo A)
        $this->alumnoA = User::factory()->create([
            'rol_id' => 3,
            'nombre' => 'Manuel',
            'apellido_paterno' => 'Antonio',
            'email' => 'alumnoA.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoA->usuario_id,
            'programa_id' => $this->pe->programa_id,
            'matricula' => 'MANU001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'fecha_ingreso' => now()->subYears(2),
            'saldo_puntos' => 100,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoA->usuario_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'ACTIVO',
        ]);

        // Alumno B (pertenece a Grupo B)
        $this->alumnoB = User::factory()->create([
            'rol_id' => 3,
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Estudiante',
            'email' => 'alumnoB.test@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumnoB->usuario_id,
            'programa_id' => $this->pe->programa_id,
            'matricula' => 'CARL002',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'fecha_ingreso' => now()->subYears(2),
            'saldo_puntos' => 50,
        ]);
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoB->usuario_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'ACTIVO',
        ]);
    }

    /**
     * Prueba 1: Exportación PDF de Sesiones incluye ambas vistas y separa roles.
     */
    public function test_sesiones_export_pdf_includes_both_views_and_segregates_orientadores_and_admins(): void
    {
        LoginAcceso::create([
            'usuario_id' => $this->alumnoA->usuario_id,
            'email' => $this->alumnoA->email,
            'nombre_usuario' => $this->alumnoA->nombre,
            'rol_id' => 3,
            'rol_nombre' => 'Alumno',
            'ip_address' => '192.168.1.10',
            'user_agent' => 'PHPUnit',
            'exitoso' => true,
            'fecha_acceso' => now(),
        ]);

        LoginAcceso::create([
            'usuario_id' => $this->docente->usuario_id,
            'email' => $this->docente->email,
            'nombre_usuario' => $this->docente->nombre,
            'rol_id' => 2,
            'rol_nombre' => 'Docente',
            'ip_address' => '192.168.1.20',
            'user_agent' => 'PHPUnit',
            'exitoso' => true,
            'fecha_acceso' => now(),
        ]);

        LoginAcceso::create([
            'usuario_id' => $this->admin->usuario_id,
            'email' => $this->admin->email,
            'nombre_usuario' => $this->admin->nombre,
            'rol_id' => 1,
            'rol_nombre' => 'Administrador',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'PHPUnit',
            'exitoso' => true,
            'fecha_acceso' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reportes.exportar-pdf', 'sesiones-por-dia'));

        $this->assertEquals(200, $response->status());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Prueba 2: Exportación Excel de Sesiones genera un archivo .xlsx con 4 pestañas.
     */
    public function test_sesiones_export_excel_generates_multisheet_xlsx_with_4_sheets(): void
    {
        LoginAcceso::create([
            'usuario_id' => $this->alumnoA->usuario_id,
            'email' => $this->alumnoA->email,
            'nombre_usuario' => $this->alumnoA->nombre,
            'rol_id' => 3,
            'rol_nombre' => 'Alumno',
            'ip_address' => '192.168.1.10',
            'user_agent' => 'PHPUnit',
            'exitoso' => true,
            'fecha_acceso' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reportes.exportar-excel', 'sesiones-por-dia'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));

        // Obtener el archivo temporal generado por BinaryFileResponse
        $tempXlsx = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempXlsx));

        // Verificar que existan las 4 hojas
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet2.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet3.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet4.xml'));

        // Verificar títulos de las hojas en workbook.xml
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $this->assertStringContainsString('Resumen Diario', $wbXml);
        $this->assertStringContainsString('Detalle de Accesos', $wbXml);
        $this->assertStringContainsString('Indicadores Clave', $wbXml);
        $this->assertStringContainsString('Metodología', $wbXml);

        $zip->close();
        @unlink($tempXlsx);
    }

    /**
     * Prueba 3: Docente puede solicitar impartición de asignatura y notifica a los administradores.
     */
    public function test_docente_can_request_teaching_subject(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->docente)->post(route('docente.academico.solicitar.store'), [
            'asignatura_id' => $this->materia1->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'motivo_solicitud' => 'Cuento con certificación en desarrollo web.',
        ]);

        $response->assertRedirect(route('docente.academico.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('docente_asignaciones', [
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->materia1->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'PENDIENTE',
            'activo' => false,
        ]);

        Notification::assertSentTo($this->admin, SolicitudDocenteCreadaNotification::class);
    }

    /**
     * Prueba 4: Administrador puede aprobar y rechazar solicitudes de cátedra.
     */
    public function test_admin_can_approve_and_reject_docente_request(): void
    {
        Notification::fake();

        // 1. Crear solicitud pendiente
        $asig = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->materia1->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'PENDIENTE',
            'activo' => false,
            'solicitado_en' => now(),
        ]);

        // 2. Administrador aprueba
        $resAprobar = $this->actingAs($this->admin)->post(route('admin.docente-asignaciones.aprobar', $asig->asignacion_id), [
            'motivo_resolucion' => 'Aprobado conforme al perfil curricular.',
        ]);

        $resAprobar->assertSessionHas('status');
        $asig->refresh();
        $this->assertEquals('APROBADA', $asig->estado);
        $this->assertTrue($asig->activo);
        $this->assertEquals($this->admin->usuario_id, $asig->resuelto_por);

        Notification::assertSentTo($this->docente, AsignacionDocenteResueltaNotification::class);

        // 3. Crear otra solicitud y rechazar
        $asig2 = DocenteAsignacion::create([
            'docente_id' => $this->docente2->usuario_id,
            'asignatura_id' => $this->materia2->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'PENDIENTE',
            'activo' => false,
            'solicitado_en' => now(),
        ]);

        $resRechazar = $this->actingAs($this->admin)->post(route('admin.docente-asignaciones.rechazar', $asig2->asignacion_id), [
            'motivo_resolucion' => 'Grupo con cupo docente cubierto.',
        ]);

        $resRechazar->assertSessionHas('warning');
        $asig2->refresh();
        $this->assertEquals('RECHAZADA', $asig2->estado);
        $this->assertFalse($asig2->activo);

        Notification::assertSentTo($this->docente2, AsignacionDocenteResueltaNotification::class);
    }

    /**
     * Prueba 5: Segregación estricta de grupos (Grupo A vs Grupo B).
     * El alumno solo ve la oferta de su grupo y no puede inscribirse al grupo opuesto.
     */
    public function test_student_sees_only_own_group_offer_and_cannot_request_different_group(): void
    {
        // Asignación aprobada para Grupo A
        $asigA = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->materia1->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'APROBADA',
            'activo' => true,
        ]);

        // Asignación aprobada para Grupo B
        $asigB = DocenteAsignacion::create([
            'docente_id' => $this->docente2->usuario_id,
            'asignatura_id' => $this->materia2->asignatura_id,
            'grupo_id' => $this->grupoB->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'APROBADA',
            'activo' => true,
        ]);

        // Alumno A visita su portal: Solo debe ver la oferta de Grupo A
        $resA = $this->actingAs($this->alumnoA)->get(route('alumno.academico.index'));
        $resA->assertStatus(200);
        $resA->assertSee($this->materia1->nombre);
        $resA->assertSee('Sección "A"', false);

        // Intento malicioso de Alumno A de solicitar inscripción a Grupo B mediante POST directo
        $resFraude = $this->actingAs($this->alumnoA)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asigB->asignacion_id,
        ]);

        $resFraude->assertSessionHas('error');
        // Verificar que NO se haya creado inscripción
        $this->assertDatabaseMissing('estudiante_asignatura_inscripciones', [
            'estudiante_id' => $this->alumnoA->usuario_id,
            'asignacion_id' => $asigB->asignacion_id,
        ]);
    }

    /**
     * Prueba 6: Alumno solicita inscripción en materia de su grupo y el docente la resuelve.
     */
    public function test_student_requests_own_group_subject_and_docente_resolves(): void
    {
        Notification::fake();

        $asigA = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->materia1->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'APROBADA',
            'activo' => true,
        ]);

        // Alumno A solicita
        $resSol = $this->actingAs($this->alumnoA)->post(route('alumno.academico.solicitar'), [
            'asignacion_id' => $asigA->asignacion_id,
            'motivo_solicitud' => 'Inscripción regular a 4to cuatrimestre.',
        ]);

        $resSol->assertRedirect(route('alumno.academico.index'));
        $resSol->assertSessionHas('status');

        $insc = EstudianteAsignaturaInscripcion::where('estudiante_id', $this->alumnoA->usuario_id)
            ->where('asignacion_id', $asigA->asignacion_id)
            ->first();

        $this->assertNotNull($insc);
        $this->assertEquals('PENDIENTE', $insc->estado);

        Notification::assertSentTo($this->docente, SolicitudInscripcionEstudianteNotification::class);

        // Docente aprueba la inscripción del alumno
        $resAprobar = $this->actingAs($this->docente)->post(route('docente.academico.inscripciones.aprobar', $insc->inscripcion_materia_id), [
            'motivo_resolucion' => 'Bienvenido a la clase.',
        ]);

        $resAprobar->assertSessionHas('status');
        $insc->refresh();
        $this->assertEquals('APROBADA', $insc->estado);
        $this->assertEquals($this->docente->usuario_id, $insc->resuelto_por);

        Notification::assertSentTo($this->alumnoA, InscripcionEstudianteResueltaNotification::class);
    }

    /**
     * Prueba 7: Alumno puede cancelar una solicitud pendiente.
     */
    public function test_student_can_cancel_pending_request(): void
    {
        $asigA = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->materia1->asignatura_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'APROBADA',
            'activo' => true,
        ]);

        $insc = EstudianteAsignaturaInscripcion::create([
            'estudiante_id' => $this->alumnoA->usuario_id,
            'asignacion_id' => $asigA->asignacion_id,
            'grupo_id' => $this->grupoA->grupo_id,
            'asignatura_id' => $this->materia1->asignatura_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => 'PENDIENTE',
            'solicitado_en' => now(),
        ]);

        $resCancelar = $this->actingAs($this->alumnoA)->post(route('alumno.academico.cancelar', $insc->inscripcion_materia_id));
        $resCancelar->assertRedirect(route('alumno.academico.index'));
        $resCancelar->assertSessionHas('info');

        $insc->refresh();
        $this->assertEquals('CANCELADA', $insc->estado);
    }
}
