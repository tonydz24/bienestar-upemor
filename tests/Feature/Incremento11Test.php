<?php

namespace Tests\Feature;

use App\Models\BitacoraEmocional;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Emocion;
use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\Meditacion;
use App\Models\MeditacionSesion;
use App\Models\PeriodoAcademico;
use App\Models\ProgramaEducativo;
use App\Models\PuntoMovimiento;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Notifications\EncuestaPostCitaNotification;
use App\Notifications\NuevaEncuestaNotification;
use App\Notifications\RecordatorioCitaNotification;
use App\Services\Puntos\PuntoService;
use App\Services\Recompensas\RecompensaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Incremento11Test extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $docenteUser;
    protected User $orientadorUser;
    protected User $alumnoUser;
    protected Estudiante $estudiante;
    protected CitaProfesional $profesional;
    protected PeriodoAcademico $periodo;
    protected ProgramaEducativo $programa;
    protected Emocion $emocionAlegria;
    protected Emocion $emocionCalma;

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

        $this->adminUser = User::create([
            'rol_id' => $rolAdmin->rol_id,
            'nombre' => 'Arantxa',
            'apellido_paterno' => 'Admin',
            'email' => 'admin.test@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'estado' => 'ACTIVO',
            'email_verified_at' => now(),
        ]);

        $this->docenteUser = User::create([
            'rol_id' => $rolDocente->rol_id,
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Docente',
            'email' => 'docente.test@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'estado' => 'ACTIVO',
            'email_verified_at' => now(),
        ]);
        Docente::create([
            'usuario_id' => $this->docenteUser->usuario_id,
            'numero_empleado' => 'DOC-1100',
            'cubiculo' => 'Docencia 1',
        ]);

        $this->orientadorUser = User::create([
            'rol_id' => $rolOrientador->rol_id,
            'nombre' => 'Odette',
            'apellido_paterno' => 'Orientadora',
            'email' => 'orientadora.test@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'estado' => 'ACTIVO',
            'email_verified_at' => now(),
        ]);
        $this->profesional = CitaProfesional::create([
            'usuario_id' => $this->orientadorUser->usuario_id,
            'cargo' => 'Orientadora Psicológica',
            'especialidad' => 'Psicología Clínica',
            'lugar_atencion' => 'Cubículo de Orientación A',
            'activo' => true,
        ]);

        $this->alumnoUser = User::create([
            'rol_id' => $rolAlumno->rol_id,
            'nombre' => 'Manuel',
            'apellido_paterno' => 'Alumno',
            'apellido_materno' => 'Díaz',
            'email' => 'alumno.test@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'estado' => 'ACTIVO',
            'email_verified_at' => now(),
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'matricula' => 'UPEMOR-2026-11',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 9,
            'grupo' => 'A',
            'saldo_puntos' => 10,
        ]);

        $this->emocionAlegria = Emocion::create([
            'nombre' => 'Alegría',
            'slug' => 'alegria',
            'color_hex' => '#f59e0b',
            'activo' => true,
            'orden' => 1,
        ]);

        $this->emocionCalma = Emocion::create([
            'nombre' => 'Calma',
            'slug' => 'calma',
            'color_hex' => '#10b981',
            'activo' => true,
            'orden' => 2,
        ]);
    }

    /* -------------------------------------------------------------------------
     | TESTS DE SISTEMA DE PUNTOS Y RECOMPENSAS (FN.5 / FN.11)
     | ------------------------------------------------------------------------- */

    public function test_otorgar_por_emocion_diaria_otorga_un_punto_y_es_neutral(): void
    {
        $puntoService = app(PuntoService::class);

        $bitacora1 = BitacoraEmocional::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 4,
            'motivo' => 'Día productivo',
        ]);

        $mov = $puntoService->otorgarPorEmocionDiaria($this->alumnoUser, $bitacora1);
        $this->assertNotNull($mov);
        $this->assertEquals(1, $mov->puntos);
        $this->assertEquals(11, $this->estudiante->fresh()->saldo_puntos);

        // Intento de re-otorgamiento el mismo día (neutralidad ética e idempotencia: no duplica puntos)
        $mov2 = $puntoService->otorgarPorEmocionDiaria($this->alumnoUser, $bitacora1);
        $this->assertNull($mov2);
        $this->assertEquals(11, $this->estudiante->fresh()->saldo_puntos);
    }

    public function test_otorgar_por_meditacion_completada_otorga_dos_puntos_y_respeta_tope_diario(): void
    {
        $puntoService = app(PuntoService::class);

        $meditacion = Meditacion::create([
            'titulo' => 'Respiración de Calma',
            'slug' => 'respiracion-de-calma',
            'categoria' => Meditacion::CATEGORIA_RESPIRACION,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'duracion_minutos' => 4,
            'descripcion' => 'Ejercicio de relajación.',
            'es_publicado' => true,
            'creado_por' => $this->adminUser->usuario_id,
            'instrucciones' => ['tipo_contenido' => 'INTERACTIVO', 'pasos' => []],
        ]);

        // Sesión 1 completada
        $sesion1 = MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $this->alumnoUser->usuario_id,
            'duracion_segundos' => 240,
            'completada' => true,
            'fecha_sesion' => now(),
        ]);

        $mov1 = $puntoService->otorgarPorMeditacion($this->alumnoUser, $sesion1);
        $this->assertNotNull($mov1);
        $this->assertEquals(2, $mov1->puntos);
        $this->assertEquals(12, $this->estudiante->fresh()->saldo_puntos);

        // Sesión 2 completada (máximo 2 por día)
        $sesion2 = MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $this->alumnoUser->usuario_id,
            'duracion_segundos' => 240,
            'completada' => true,
            'fecha_sesion' => now(),
        ]);
        $mov2 = $puntoService->otorgarPorMeditacion($this->alumnoUser, $sesion2);
        $this->assertNotNull($mov2);
        $this->assertEquals(2, $mov2->puntos);
        $this->assertEquals(14, $this->estudiante->fresh()->saldo_puntos);

        // Sesión 3 el mismo día: rechazada por política (máximo 2 meditaciones bonificadas/día)
        $sesion3 = MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $this->alumnoUser->usuario_id,
            'duracion_segundos' => 240,
            'completada' => true,
            'fecha_sesion' => now(),
        ]);
        $mov3 = $puntoService->otorgarPorMeditacion($this->alumnoUser, $sesion3);
        $this->assertNull($mov3);
        $this->assertEquals(14, $this->estudiante->fresh()->saldo_puntos);
    }

    public function test_guardar_sesion_meditacion_incompleta_no_otorga_puntos(): void
    {
        $puntoService = app(PuntoService::class);

        $meditacion = Meditacion::create([
            'titulo' => 'Relajación Diafragmática',
            'slug' => 'relajacion-diafragmatica',
            'categoria' => Meditacion::CATEGORIA_RESPIRACION,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'duracion_minutos' => 5,
            'descripcion' => 'Ejercicio de prueba.',
            'es_publicado' => true,
            'creado_por' => $this->adminUser->usuario_id,
            'instrucciones' => ['tipo_contenido' => 'INTERACTIVO', 'pasos' => []],
        ]);

        $sesionIncompleta = MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $this->alumnoUser->usuario_id,
            'duracion_segundos' => 30,
            'completada' => false,
            'fecha_sesion' => now(),
        ]);

        $mov = $puntoService->otorgarPorMeditacion($this->alumnoUser, $sesionIncompleta);
        $this->assertNull($mov);
        $this->assertEquals(10, $this->estudiante->fresh()->saldo_puntos);
    }

    public function test_evaluacion_psicologica_otorga_cinco_puntos_sin_sesgo_de_resultado(): void
    {
        $puntoService = app(PuntoService::class);

        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'PHQ-9',
            'nombre' => 'Cuestionario de Salud del Paciente PHQ-9',
            'descripcion' => 'Tamizaje de sintomatología depresiva',
            'categoria' => 'DEPRESION',
            'activo' => true,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => 1,
            'instrucciones' => 'Responde cada pregunta.',
            'activo' => true,
        ]);

        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'version_id' => $version->version_id,
            'estado' => EvaluacionIntento::ESTADO_FINALIZADO,
            'puntuacion_total' => 18,
            'nivel_resultado' => 'Moderadamente Severo',
            'tiene_alerta_critica' => true,
            'fecha_inicio' => now()->subMinutes(10),
            'fecha_finalizacion' => now(),
        ]);

        $mov = $puntoService->otorgarPorEvaluacion($this->alumnoUser, $intento);
        $this->assertNotNull($mov);
        $this->assertEquals(5, $mov->puntos);
        $this->assertEquals(15, $this->estudiante->fresh()->saldo_puntos);
        $this->assertEquals(PuntoMovimiento::TIPO_EVALUACION, $mov->tipo);
    }

    public function test_encuesta_institucional_otorga_un_punto_preservando_anonimato(): void
    {
        $puntoService = app(PuntoService::class);

        $encuesta = Encuesta::create([
            'titulo' => 'Evaluación de Instalaciones y Servicios 2026',
            'slug' => 'evaluacion-instalaciones-2026',
            'tipo' => Encuesta::TIPO_GENERAL,
            'descripcion' => 'Encuesta institucional anónima.',
            'es_anonima' => true,
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'creado_por' => $this->adminUser->usuario_id,
        ]);

        $mov = $puntoService->otorgarPorEncuesta($this->alumnoUser, $encuesta->encuesta_id);
        $this->assertNotNull($mov);
        $this->assertEquals(1, $mov->puntos);
        $this->assertEquals(11, $this->estudiante->fresh()->saldo_puntos);
        $this->assertEquals(PuntoMovimiento::TIPO_ENCUESTA, $mov->tipo);
        $this->assertEquals($encuesta->encuesta_id, $mov->referencia_id);
        // Garantiza que la referencia sea la Encuesta y no una respuesta nominal
        $this->assertEquals(Encuesta::class, $mov->referencia_tipo);
    }

    public function test_canje_de_recompensa_deduce_puntos_y_cancelacion_reembolsa(): void
    {
        $grupo = Grupo::create([
            'programa_id' => $this->programa->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'Grupo A',
            'cuatrimestre' => 9,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $asignatura = \App\Models\Asignatura::create([
            'clave' => 'ITI-901',
            'nombre' => 'Arquitectura de Software',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 9,
            'creditos' => 6,
            'activo' => true,
        ]);

        $asignacion = DocenteAsignacion::create([
            'docente_id' => $this->docenteUser->usuario_id,
            'asignatura_id' => $asignatura->asignatura_id,
            'grupo_id' => $grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
        ]);

        // Inscribir al estudiante
        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'grupo_id' => $grupo->grupo_id,
            'periodo_id' => $this->periodo->periodo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);

        $catalogo = RecompensaCatalogo::create([
            'nombre' => 'Punto extra en práctica',
            'descripcion' => 'Reconocimiento académico',
            'categoria' => RecompensaCatalogo::CATEGORIA_ACADEMICA,
            'costo_puntos_base' => 5,
            'condiciones_generales' => 'Aplicable a prácticas de laboratorio',
            'activo' => true,
        ]);

        $activacion = RecompensaActivada::create([
            'recompensa_id' => $catalogo->recompensa_id,
            'asignacion_id' => $asignacion->asignacion_id,
            'cupo_maximo' => 5,
            'cupo_disponible' => 5,
            'costo_puntos' => 5,
            'vigencia_inicio' => now()->subDay()->toDateString(),
            'vigencia_fin' => now()->addDays(10)->toDateString(),
            'estado' => RecompensaActivada::ESTADO_ACTIVA,
        ]);

        $recompensaService = app(RecompensaService::class);

        // 1. Canjear recompensa (gasto: 5 puntos)
        $this->assertEquals(10, $this->estudiante->fresh()->saldo_puntos);
        $canje = $recompensaService->solicitarCanje($this->alumnoUser, $activacion->activacion_id, 'Motivo de prueba');

        $this->assertEquals(5, $this->estudiante->fresh()->saldo_puntos);
        $this->assertDatabaseHas('estudiante_puntos_movimientos', [
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'tipo' => PuntoMovimiento::TIPO_CANJE_RECOMPENSA,
            'puntos' => -5,
            'saldo_nuevo' => 5,
        ]);

        // 2. Cancelar canje por el propio alumno (reembolso: +5 puntos)
        $recompensaService->cancelarCanjePorAlumno($canje, $this->alumnoUser);
        $this->assertEquals(10, $this->estudiante->fresh()->saldo_puntos);
        $this->assertDatabaseHas('estudiante_puntos_movimientos', [
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'tipo' => PuntoMovimiento::TIPO_REEMBOLSO_CANJE,
            'puntos' => 5,
            'saldo_nuevo' => 10,
        ]);
    }

    /* -------------------------------------------------------------------------
     | TESTS DE MEDITACIONES MULTIMEDIA (FN.6)
     | ------------------------------------------------------------------------- */

    public function test_admin_puede_crear_meditacion_video_con_url_y_transcripcion(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.meditaciones.store'), [
            'titulo' => 'Meditación Guiada en Video 1080p',
            'categoria' => Meditacion::CATEGORIA_MINDFULNESS,
            'tipo_contenido' => Meditacion::TIPO_VIDEO,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'duracion_minutos' => 10,
            'descripcion' => 'Sesión audiovisual completa para reducir el estrés.',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'transcripcion' => 'Inicia cerrando tus ojos suavemente y escuchando el ritmo de tu respiración...',
            'es_publicado' => 1,
        ]);

        $response->assertRedirect(route('admin.meditaciones.index'));
        $this->assertDatabaseHas('meditaciones', [
            'titulo' => 'Meditación Guiada en Video 1080p',
        ]);

        $meditacion = Meditacion::where('titulo', 'Meditación Guiada en Video 1080p')->first();
        $this->assertEquals(Meditacion::TIPO_VIDEO, $meditacion->tipo_contenido);
        $this->assertEquals('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $meditacion->embed_video_url);
        $this->assertStringContainsString('Inicia cerrando tus ojos', $meditacion->transcripcion);
    }

    public function test_admin_validacion_falla_si_falta_url_en_audio_o_video(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.meditaciones.store'), [
            'titulo' => 'Audio Podcast Invalido',
            'categoria' => Meditacion::CATEGORIA_MINDFULNESS,
            'tipo_contenido' => Meditacion::TIPO_AUDIO,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'duracion_minutos' => 5,
            'descripcion' => 'Descripción corta',
            'media_url' => '', // Faltante
        ]);

        $response->assertSessionHasErrors(['media_url']);
    }

    /* -------------------------------------------------------------------------
     | TESTS DE ACCESO CONFIDENCIAL DEL ORIENTADOR A TAMIZAJES (FN.3)
     | ------------------------------------------------------------------------- */

    public function test_orientador_asignado_puede_ver_tamizajes_con_consentimiento(): void
    {
        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => now()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'modalidad' => CitaOrientacion::MODALIDAD_PRESENCIAL,
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'motivo_consulta' => 'Dificultad de concentración',
            'fecha_solicitud' => now(),
        ]);

        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'GAD-7',
            'nombre' => 'Escala de Ansiedad Generalizada GAD-7',
            'descripcion' => 'Tamizaje de síntomas de ansiedad',
            'categoria' => 'ANSIEDAD',
            'activo' => true,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => 1,
            'instrucciones' => 'Responde cada reactivo con sinceridad.',
            'activo' => true,
        ]);

        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'version_id' => $version->version_id,
            'estado' => EvaluacionIntento::ESTADO_FINALIZADO,
            'puntuacion_total' => 12,
            'nivel_resultado' => 'Ansiedad Moderada',
            'tiene_alerta_critica' => false,
            'fecha_inicio' => now()->subMinutes(15),
            'fecha_finalizacion' => now(),
        ]);

        EvaluacionConsentimiento::create([
            'usuario_id' => $this->alumnoUser->usuario_id,
            'version_id' => $version->version_id,
            'intento_id' => $intento->intento_id,
            'consentimiento_version' => '1.0',
            'texto_consentimiento' => 'Acepto compartir resultados con orientador.',
            'fecha_consentimiento' => now(),
        ]);

        // Orientador asignado ve la vista
        $response = $this->actingAs($this->orientadorUser)->get(route('docente.citas.show', $cita->cita_id));
        $response->assertOk();
        $response->assertSee('Tamizajes Psicoemocionales del Estudiante');
        $response->assertSee('GAD-7');
        $response->assertSee('Ansiedad Moderada');
        $response->assertSee('12 pts');
    }

    public function test_docente_no_orientador_no_puede_acceder_a_gestion_de_citas(): void
    {
        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => now()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'modalidad' => CitaOrientacion::MODALIDAD_PRESENCIAL,
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now(),
        ]);

        // Docente sin rol ni asignación como orientador
        $response = $this->actingAs($this->docenteUser)->get(route('docente.citas.show', $cita->cita_id));
        $response->assertStatus(403);
    }

    /* -------------------------------------------------------------------------
     | TESTS DE NOTIFICACIONES (FN.14)
     | ------------------------------------------------------------------------- */

    public function test_publicar_encuesta_dispara_nueva_encuesta_notification(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->adminUser)->post(route('admin.encuestas.store'), [
            'titulo' => 'Encuesta Clima Escolar 2026',
            'tipo' => Encuesta::TIPO_GENERAL,
            'descripcion' => 'Evaluación de clima y convivencia universitaria.',
            'es_anonima' => 1,
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'preguntas' => [
                [
                    'texto' => '¿Cómo calificarías el ambiente de estudio en UPEMOR?',
                    'tipo' => EncuestaPregunta::TIPO_VALORACION,
                    'es_obligatoria' => 1,
                ]
            ]
        ]);

        $response->assertRedirect(route('admin.encuestas.index'));
        Notification::assertSentTo($this->alumnoUser, NuevaEncuestaNotification::class);
    }

    public function test_finalizar_cita_dispara_encuesta_post_cita_notification(): void
    {
        Notification::fake();

        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => now()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'modalidad' => CitaOrientacion::MODALIDAD_PRESENCIAL,
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now(),
        ]);

        $response = $this->actingAs($this->orientadorUser)->patch(route('docente.citas.finalizar', $cita->cita_id));
        $response->assertRedirect(route('docente.citas.show', $cita->cita_id));

        Notification::assertSentTo($this->alumnoUser, EncuestaPostCitaNotification::class);
    }

    public function test_recordatorios_citas_command_dispatches_notification_for_confirmed_citas(): void
    {
        Notification::fake();

        $cita = CitaOrientacion::create([
            'estudiante_id' => $this->alumnoUser->usuario_id,
            'profesional_id' => $this->profesional->profesional_id,
            'fecha_cita' => now()->addDay()->toDateString(),
            'hora_inicio' => '11:00:00',
            'hora_fin' => '12:00:00',
            'modalidad' => CitaOrientacion::MODALIDAD_PRESENCIAL,
            'lugar_o_enlace' => 'Cubículo de Orientación A',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now(),
        ]);

        $exitCode = Artisan::call('bienestar:recordatorios-citas');
        $this->assertEquals(0, $exitCode);

        Notification::assertSentTo($this->alumnoUser, RecordatorioCitaNotification::class, function ($n) use ($cita) {
            return $n->citaId === $cita->cita_id;
        });
    }

    public function test_session_keep_alive_endpoint_refreshes_activity(): void
    {
        $response = $this->actingAs($this->alumnoUser)->postJson(route('session.keep-alive'));

        $response->assertOk();
        $response->assertJson([
            'status' => 'active',
        ]);
        $this->assertNotEmpty(session('last_activity_time'));
    }

    public function test_puntos_movimientos_opening_balance_constant(): void
    {
        $this->assertEquals('SALDO_INICIAL', PuntoMovimiento::TIPO_SALDO_INICIAL);
    }
}
