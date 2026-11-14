<?php

namespace Tests\Feature;

use App\Models\BitacoraEmocional;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\BitacoraRecordatorioNotification;
use App\Notifications\CitaActualizadaNotification;
use App\Notifications\PropuestaRecompensaResueltaNotification;
use App\Notifications\RecompensaHabilitadaNotification;
use App\Notifications\RestablecerPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class Incremento7Test extends TestCase
{
    use RefreshDatabase;

    protected Rol $rolAdmin;
    protected Rol $rolDocente;
    protected Rol $rolAlumno;
    protected Rol $rolOrientador;
    protected ProgramaEducativo $programa;
    protected User $admin;
    protected User $docente;
    protected User $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Admin']);
        $this->rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['descripcion' => 'Docente']);
        $this->rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Alumno']);
        $this->rolOrientador = Rol::firstOrCreate(['nombre' => 'Orientador'], ['descripcion' => 'Orientador']);

        $this->programa = ProgramaEducativo::firstOrCreate(
            ['clave' => 'ITI'],
            ['nombre' => 'Ingeniería en Tecnologías de la Información', 'activo' => true]
        );

        $this->admin = User::create([
            'rol_id' => $this->rolAdmin->rol_id,
            'nombre' => 'Admin',
            'apellido_paterno' => 'Principal',
            'apellido_materno' => 'Sistemas',
            'email' => 'admin.test@upemor.edu.mx',
            'password' => Hash::make('AdminPass123!'),
            'estado' => 'ACTIVO',
        ]);

        $this->docente = User::create([
            'rol_id' => $this->rolDocente->rol_id,
            'nombre' => 'Docente',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => 'Academico',
            'email' => 'docente.test@upemor.edu.mx',
            'password' => Hash::make('DocentePass123!'),
            'estado' => 'ACTIVO',
        ]);

        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'DOC-TEST-001',
            'puesto' => 'Profesor de Tiempo Completo',
            'activo' => true,
        ]);

        $this->alumno = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Alumno',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => 'Estudiante',
            'email' => 'alumno.test@upemor.edu.mx',
            'password' => Hash::make('AlumnoPass123!'),
            'estado' => 'ACTIVO',
        ]);

        Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => 'UP260001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'fecha_ingreso' => now()->toDateString(),
            'saldo_puntos' => 150,
            'permite_visualizacion' => true,
        ]);
    }

    /* =========================================================================
     * 1. GESTIÓN ADMINISTRATIVA DE USUARIOS (FN.2)
     * ========================================================================= */

    public function test_admin_puede_ver_listado_de_usuarios_con_metricas_y_filtros(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.usuarios.index'));

        $response->assertOk();
        $response->assertSee('Gestión Institucional de Usuarios');
        $response->assertSee('Total Cuentas');
        $response->assertSee('Alumnos');
        $response->assertSee('Docentes');
        $response->assertSee('Admin Principal');
        $response->assertSee('Docente Prueba');
        $response->assertSee('Alumno Prueba');
    }

    public function test_admin_puede_filtrar_usuarios_por_rol_y_por_texto(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.usuarios.index', [
            'rol' => 'Alumno',
            'buscar' => 'UP260001',
        ]));

        $response->assertOk();
        $response->assertSee('Alumno Prueba');
        $response->assertSee('UP260001');
    }

    public function test_admin_puede_crear_usuario_alumno_exitosamente(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.usuarios.store'), [
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => 'López',
            'email' => 'carlos.ramirez@upemor.edu.mx',
            'password' => 'Secreta2026!',
            'password_confirmation' => 'Secreta2026!',
            'telefono' => '7779876543',
            'rol_id' => $this->rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'matricula' => 'UP260099',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 3,
            'grupo' => 'B',
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $this->assertDatabaseHas('users', ['email' => 'carlos.ramirez@upemor.edu.mx']);
        $this->assertDatabaseHas('estudiantes', ['matricula' => 'UP260099', 'cuatrimestre' => 3]);
    }

    public function test_alta_usuario_requiere_apellido_materno_obligatorio(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.usuarios.store'), [
            'nombre' => 'Ana',
            'apellido_paterno' => 'Morales',
            'apellido_materno' => '', // Campo obligatorio omitido
            'email' => 'ana.morales@upemor.edu.mx',
            'password' => 'Secreta2026!',
            'password_confirmation' => 'Secreta2026!',
            'rol_id' => $this->rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'matricula' => 'UP260088',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 1,
        ]);

        $response->assertSessionHasErrors('apellido_materno');
        $this->assertDatabaseMissing('users', ['email' => 'ana.morales@upemor.edu.mx']);
    }

    public function test_admin_puede_crear_usuario_docente_puro(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.usuarios.store'), [
            'nombre' => 'Laura',
            'apellido_paterno' => 'González',
            'apellido_materno' => 'Pérez',
            'email' => 'laura.gonzalez@upemor.edu.mx',
            'password' => 'DocentePass2026!',
            'password_confirmation' => 'DocentePass2026!',
            'rol_id' => $this->rolDocente->rol_id,
            'estado' => 'ACTIVO',
            'numero_empleado' => 'DOC-2026-99',
            'puesto' => 'Profesor de Tiempo Completo',
            'especialidad' => 'Desarrollo de Software',
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $this->assertDatabaseHas('users', ['email' => 'laura.gonzalez@upemor.edu.mx']);
        $this->assertDatabaseHas('docentes', ['numero_empleado' => 'DOC-2026-99']);
        // Confirmar estricta separación: NO debe crearse cita_profesional para docente puro
        $nuevoDocente = User::where('email', 'laura.gonzalez@upemor.edu.mx')->first();
        $this->assertNull($nuevoDocente->citaProfesional);
    }

    public function test_admin_puede_crear_usuario_orientador_puro(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.usuarios.store'), [
            'nombre' => 'Mariana',
            'apellido_paterno' => 'Sánchez',
            'apellido_materno' => 'Vargas',
            'email' => 'mariana.sanchez@upemor.edu.mx',
            'password' => 'OrientadorPass2026!',
            'password_confirmation' => 'OrientadorPass2026!',
            'rol_id' => $this->rolOrientador->rol_id,
            'estado' => 'ACTIVO',
            'cargo' => 'Psicóloga Clínica y Acompañamiento',
            'cedula_profesional' => 'PSI-998877',
            'lugar_atencion' => 'Cubículo de Orientación 2',
            'modalidades_permitidas' => 'AMBAS',
            'permite_citas' => 1,
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $this->assertDatabaseHas('users', ['email' => 'mariana.sanchez@upemor.edu.mx']);
        $this->assertDatabaseHas('citas_profesionales', ['cedula_profesional' => 'PSI-998877', 'cargo' => 'Psicóloga Clínica y Acompañamiento']);
    }

    public function test_admin_puede_editar_usuario_sin_destruir_relaciones(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.usuarios.update', $this->alumno->usuario_id), [
            'nombre' => 'Alumno Actualizado',
            'apellido_paterno' => 'Prueba Editado',
            'apellido_materno' => 'Estudiante',
            'email' => 'alumno.actualizado@upemor.edu.mx',
            'telefono' => '7770001122',
            'estado' => 'ACTIVO',
            'matricula' => 'UP260001',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 5,
            'grupo' => 'A',
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $this->assertDatabaseHas('users', [
            'usuario_id' => $this->alumno->usuario_id,
            'nombre' => 'Alumno Actualizado',
            'email' => 'alumno.actualizado@upemor.edu.mx',
        ]);
        $this->assertDatabaseHas('estudiantes', [
            'usuario_id' => $this->alumno->usuario_id,
            'cuatrimestre' => 5,
            'saldo_puntos' => 150, // Preservado
        ]);
    }

    public function test_admin_no_puede_auto_desactivar_ni_auto_bloquear_su_propia_cuenta(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.usuarios.estado', $this->admin->usuario_id), [
            'estado' => 'BLOQUEADO',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'usuario_id' => $this->admin->usuario_id,
            'estado' => 'ACTIVO',
        ]);
    }

    public function test_admin_no_puede_desactivar_al_unico_administrador_activo(): void
    {
        // $this->admin es el único administrador activo
        $response = $this->actingAs($this->admin)->put(route('admin.usuarios.update', $this->admin->usuario_id), [
            'nombre' => 'Admin Intentando Desactivarse',
            'apellido_paterno' => 'Principal',
            'apellido_materno' => 'Sistemas',
            'email' => $this->admin->email,
            'estado' => 'INACTIVO',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'usuario_id' => $this->admin->usuario_id,
            'estado' => 'ACTIVO',
        ]);
    }

    public function test_admin_puede_cambiar_estado_de_otro_usuario(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.usuarios.estado', $this->alumno->usuario_id), [
            'estado' => 'BLOQUEADO',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'usuario_id' => $this->alumno->usuario_id,
            'estado' => 'BLOQUEADO',
        ]);
    }

    public function test_admin_puede_restablecer_password_de_usuario(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.usuarios.reset-password', $this->alumno->usuario_id), [
            'custom_password' => 'NuevaClaveSegura2026!',
        ]);

        $response->assertSessionHas('success_password_reset');
        $this->alumno->refresh();
        $this->assertTrue(Hash::check('NuevaClaveSegura2026!', $this->alumno->password));
    }

    public function test_admin_no_puede_eliminar_su_propia_cuenta(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('admin.usuarios.destroy', $this->admin->usuario_id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['usuario_id' => $this->admin->usuario_id]);
    }

    public function test_admin_puede_eliminar_usuario_sin_historial_seguramente(): void
    {
        // Crear un usuario temporal sin relaciones históricas
        $usuarioTemporal = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Temporal',
            'apellido_paterno' => 'SinHistorial',
            'apellido_materno' => 'Test',
            'email' => 'temporal.sinhistorial@upemor.edu.mx',
            'password' => Hash::make('Clave123!'),
            'estado' => 'PENDIENTE',
        ]);

        $estudianteTemporal = Estudiante::create([
            'usuario_id' => $usuarioTemporal->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => 'UP999999',
            'cuatrimestre' => 1,
            'fecha_ingreso' => now()->toDateString(),
            'saldo_puntos' => 0,
            'permite_visualizacion' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.usuarios.destroy', $usuarioTemporal->usuario_id));

        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['usuario_id' => $usuarioTemporal->usuario_id]);
        $this->assertDatabaseMissing('estudiantes', ['usuario_id' => $usuarioTemporal->usuario_id]);
    }

    public function test_admin_no_puede_eliminar_usuario_con_historial_clinico_o_academico(): void
    {
        // El alumno del fixture tiene una entrada en bitácora emocional (historial clínico)
        $emocion = \App\Models\Emocion::firstOrCreate(
            ['nombre' => 'Alegría'],
            ['slug' => 'alegria', 'categoria' => 'POSITIVA', 'activo' => true]
        );

        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 8,
            'descripcion' => 'Prueba de bitácora protegida',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.usuarios.destroy', $this->alumno->usuario_id));

        $response->assertSessionHas('error');
        $this->assertStringContainsString('registros históricos', session('error'));
        // La cuenta debe seguir existiendo en la base de datos
        $this->assertDatabaseHas('users', ['usuario_id' => $this->alumno->usuario_id]);
    }

    /* =========================================================================
     * 2. RECUPERACIÓN DE CONTRASEÑA (FN.1)
     * ========================================================================= */

    public function test_vista_forgot_password_es_accesible_publicamente(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertOk();
        $response->assertSee('¿Olvidaste tu contraseña?');
        $response->assertSee('Enviar enlace de recuperación');
    }

    public function test_solicitud_de_restablecimiento_con_correo_inexistente_no_revela_existencia(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'no_existe_cuenta@upemor.edu.mx',
        ]);

        $response->assertSessionHas('status');
        $this->assertStringContainsString('Si la dirección ingresada está registrada', session('status'));
    }

    public function test_solicitud_de_restablecimiento_con_correo_valido_envia_notificacion(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => $this->alumno->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo(
            $this->alumno,
            RestablecerPasswordNotification::class
        );
    }

    public function test_flujo_completo_de_restablecimiento_de_contrasena_con_token(): void
    {
        $token = Password::broker()->createToken($this->alumno);

        // Vista de ingreso de nueva contraseña
        $responseVista = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $this->alumno->email,
        ]));
        $responseVista->assertOk();
        $responseVista->assertSee('Nueva contraseña');

        // Envío de la nueva contraseña
        $responsePost = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->alumno->email,
            'password' => 'NuevoPasswordSeguro2026!',
            'password_confirmation' => 'NuevoPasswordSeguro2026!',
        ]);

        $responsePost->assertRedirect(route('login'));
        $responsePost->assertSessionHas('success');

        $this->alumno->refresh();
        $this->assertTrue(Hash::check('NuevoPasswordSeguro2026!', $this->alumno->password));
    }

    public function test_intento_de_restablecimiento_con_token_invalido_falla(): void
    {
        $response = $this->post(route('password.update'), [
            'token' => 'token_totalmente_invalido_y_falso',
            'email' => $this->alumno->email,
            'password' => 'NuevoPasswordSeguro2026!',
            'password_confirmation' => 'NuevoPasswordSeguro2026!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /* =========================================================================
     * 3. SISTEMA DE NOTIFICACIONES EN LA APLICACIÓN (FN.14 & FF.5)
     * ========================================================================= */

    public function test_usuario_puede_recibir_y_consultar_notificaciones_en_base_de_datos(): void
    {
        $this->alumno->notify(new BitacoraRecordatorioNotification());
        $this->alumno->notify(new RecompensaHabilitadaNotification('Puntos Extra Parcial', 'Estructura de Datos', 50));

        $this->assertEquals(2, $this->alumno->unreadNotifications()->count());

        $response = $this->actingAs($this->alumno)->get(route('notificaciones.index'));
        $response->assertOk();
        $response->assertSee('Centro de Notificaciones');
        $response->assertSee('Recordatorio de Bienestar Diario');
        $response->assertSee('Nueva Recompensa Académica Habilitada');
        $response->assertSee('Estructura de Datos');
    }

    public function test_usuario_puede_marcar_notificacion_como_leida(): void
    {
        $this->alumno->notify(new BitacoraRecordatorioNotification());
        $notificacion = $this->alumno->unreadNotifications()->first();

        $this->assertNotNull($notificacion);

        $response = $this->actingAs($this->alumno)->post(route('notificaciones.leer', $notificacion->id));

        $response->assertRedirect(route('alumno.bitacora.index'));
        $this->assertEquals(0, $this->alumno->unreadNotifications()->count());
    }

    public function test_usuario_puede_marcar_todas_las_notificaciones_como_leidas(): void
    {
        $this->alumno->notify(new BitacoraRecordatorioNotification());
        $this->alumno->notify(new CitaActualizadaNotification('CONFIRMADA', '15/11/2026'));

        $this->assertEquals(2, $this->alumno->unreadNotifications()->count());

        $response = $this->actingAs($this->alumno)->post(route('notificaciones.marcar-todas'));
        $response->assertSessionHas('success');

        $this->assertEquals(0, $this->alumno->unreadNotifications()->count());
    }

    public function test_notificaciones_incluyen_parametros_de_tolerancia_a_fallos_ff5(): void
    {
        $notif1 = new BitacoraRecordatorioNotification();
        $notif2 = new RecompensaHabilitadaNotification('Test', 'Mat', 10);
        $notif3 = new CitaActualizadaNotification('CONFIRMADA', '10/11/2026');
        $notif4 = new PropuestaRecompensaResueltaNotification('Test', 'aprobada');
        $notifMail = new RestablecerPasswordNotification('dummy-token');

        $this->assertEquals(3, $notif1->tries);
        $this->assertEquals(60, $notif1->backoff);
        $this->assertEquals(3, $notif2->tries);
        $this->assertEquals(60, $notif2->backoff);
        $this->assertEquals(3, $notif3->tries);
        $this->assertEquals(60, $notif3->backoff);
        $this->assertEquals(3, $notif4->tries);
        $this->assertEquals(60, $notif4->backoff);
        $this->assertEquals(3, $notifMail->tries);
        $this->assertEquals(60, $notifMail->backoff);
    }

    /* =========================================================================
     * 4. EXPERIENCIA DOCENTE Y NAVEGACIÓN DE KPIS
     * ========================================================================= */

    public function test_docente_dashboard_kpis_enlazan_a_vistas_reales(): void
    {
        $response = $this->actingAs($this->docente)->get(route('docente.dashboard'));

        $response->assertOk();
        $response->assertSee(route('docente.academico.index'));
        $response->assertSee(route('docente.recompensas.index', ['tab' => 'activadas']));
        $response->assertSee(route('docente.recompensas.index', ['tab' => 'solicitudes']));
        $response->assertSee(route('docente.recompensas.index', ['tab' => 'propuestas']));
    }

    public function test_admin_kpi_cards_filtran_listado_de_usuarios(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.usuarios.index', ['rol' => 'Docente']));
        $response->assertOk();
        $response->assertSee('active-filter');
        $response->assertSee('Filtro Activo');
    }
}
