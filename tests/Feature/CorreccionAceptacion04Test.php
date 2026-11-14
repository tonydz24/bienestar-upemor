<?php

namespace Tests\Feature;

use App\Models\BitacoraEmocional;
use App\Models\Emocion;
use App\Models\EscalaOpcion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\Meditacion;
use App\Models\MeditacionSesion;
use App\Models\PeriodoAcademico;
use App\Models\PreguntaEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Services\Puntos\PuntoService;
use App\Services\Respaldos\RespaldoDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorreccionAceptacion04Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $alumno1;
    private User $alumno2;
    private ProgramaEducativo $programaITI;
    private PeriodoAcademico $periodo;
    private Grupo $grupo4B;
    private Grupo $grupo2A;
    private Grupo $grupoVacio;
    private InstrumentoEvaluacion $instrumento;
    private VersionInstrumento $version;

    protected function setUp(): void
    {
        parent::setUp();

        // Configuración de roles institucionales
        $rolAlumno = Rol::firstOrCreate(['rol_id' => 1], ['nombre' => 'Alumno']);
        $rolDocente = Rol::firstOrCreate(['rol_id' => 2], ['nombre' => 'Docente']);
        $rolAdmin = Rol::firstOrCreate(['rol_id' => 3], ['nombre' => 'Administrador']);

        // Programa Educativo
        $this->programaITI = ProgramaEducativo::create([
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'clave' => 'ITI',
            'activo' => true,
        ]);

        // Periodo Académico
        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => '2026-3 — Septiembre a Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-18',
            'activo' => true,
        ]);

        // Grupos
        $this->grupo4B = Grupo::create([
            'programa_id' => $this->programaITI->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'B',
            'cuatrimestre' => 4,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupo2A = Grupo::create([
            'programa_id' => $this->programaITI->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'A',
            'cuatrimestre' => 2,
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->grupoVacio = Grupo::create([
            'programa_id' => $this->programaITI->programa_id,
            'periodo_id' => $this->periodo->periodo_id,
            'nombre' => 'C',
            'cuatrimestre' => 6,
            'turno' => 'VESPERTINO',
            'activo' => true,
        ]);

        // Administrador
        $this->admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'Bienestar',
            'apellido_materno' => 'UPEMOR',
            'email' => 'admin.test04@upemor.edu.mx',
            'password' => bcrypt('AdminSecret123!'),
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        // Alumno 1 (Grupo 4.º B)
        $this->alumno1 = User::create([
            'nombre' => 'Manuel Antonio',
            'apellido_paterno' => 'Arellano',
            'apellido_materno' => 'Pérez',
            'email' => 'alumno1.test04@upemor.edu.mx',
            'password' => bcrypt('AlumnoSecret123!'),
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        Estudiante::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'matricula' => 'ARPM240001',
            'programa_id' => $this->programaITI->programa_id,
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'puntos_bienestar' => 100,
        ]);

        // Alumno 2 (Grupo 2.º A)
        $this->alumno2 = User::create([
            'nombre' => 'Lucía',
            'apellido_paterno' => 'Gómez',
            'apellido_materno' => 'Ríos',
            'email' => 'alumno2.test04@upemor.edu.mx',
            'password' => bcrypt('AlumnoSecret123!'),
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        Estudiante::create([
            'usuario_id' => $this->alumno2->usuario_id,
            'matricula' => 'GRLO240002',
            'programa_id' => $this->programaITI->programa_id,
            'cuatrimestre' => 2,
            'grupo' => 'A',
            'puntos_bienestar' => 80,
        ]);

        // Test Psicométrico e Instrumento
        $this->instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'TAM-ESTRES-04',
            'nombre' => 'Inventario de Detección de Estrés Académico',
            'siglas' => 'IDEA-04',
            'categoria' => 'ESTRES',
            'descripcion' => 'Evaluación preventiva institucional.',
            'autor' => 'UPEMOR Bienestar',
            'anio_publicacion' => 2026,
            'activo' => true,
            'dias_espera_reaplicacion' => 14,
            'creado_por' => $this->admin->usuario_id,
        ]);

        $this->version = VersionInstrumento::create([
            'instrumento_id' => $this->instrumento->instrumento_id,
            'version' => '1.0',
            'nombre_version' => 'v1.0',
            'es_vigente' => true,
            'estado' => 'PUBLICADA',
            'instrucciones' => 'Responde con sinceridad las siguientes preguntas.',
            'marco_temporal' => 'Últimas dos semanas',
            'duracion_estimada_min' => 10,
            'puntos_recompensa' => 30,
            'fecha_publicacion' => now(),
            'publicado_por' => $this->admin->usuario_id,
        ]);

        $pregunta = PreguntaEvaluacion::create([
            'version_id' => $this->version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => '¿Te sientes abrumado por las entregas académicas?',
            'tipo_pregunta' => 'ESCALA',
            'es_obligatoria' => true,
            'orden' => 1,
            'activo' => true,
        ]);

        $escala = EscalaOpcion::create([
            'version_id' => $this->version->version_id,
            'valor' => 3,
            'etiqueta' => 'Frecuentemente',
            'orden' => 1,
        ]);

        // Intento Alumno 1 (Grupo 4.º B)
        $intento1 = EvaluacionIntento::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'version_id' => $this->version->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 3,
            'nivel_resultado' => 'Moderado',
            'fecha_inicio' => now()->subMinutes(10),
            'fecha_finalizacion' => now()->subMinutes(2),
        ]);

        EvaluacionRespuesta::create([
            'intento_id' => $intento1->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => 3,
        ]);

        // Intento Alumno 2 (Grupo 2.º A)
        $intento2 = EvaluacionIntento::create([
            'usuario_id' => $this->alumno2->usuario_id,
            'version_id' => $this->version->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 3,
            'nivel_resultado' => 'Moderado',
            'fecha_inicio' => now()->subMinutes(15),
            'fecha_finalizacion' => now()->subMinutes(5),
        ]);

        EvaluacionRespuesta::create([
            'intento_id' => $intento2->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => 3,
        ]);
    }

    private function createMeditacion(array $attributes = []): Meditacion
    {
        return Meditacion::create(array_merge([
            'titulo' => 'Ejercicio de Prueba ' . uniqid(),
            'slug' => 'ejercicio-de-prueba-' . uniqid(),
            'categoria' => Meditacion::CATEGORIA_RESPIRACION,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'duracion_minutos' => 5,
            'descripcion' => 'Descripción institucional del ejercicio de bienestar.',
            'instrucciones' => ['tipo_contenido' => Meditacion::TIPO_INTERACTIVO, 'pasos' => []],
            'es_publicado' => true,
            'creado_por' => $this->admin->usuario_id,
        ], $attributes));
    }

    // =========================================================================
    // FASE 2: Pruebas de Resultados Psicométricos y Corrección SQL de Grupos
    // =========================================================================

    public function test_resultados_psicometricos_renders_without_sql_1054_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.tests.resultados', $this->instrumento->instrumento_id));

        $response->assertOk();
        // Verificar que no arroja Unknown column 'nombre_grupo'
        $response->assertSee('Inventario de Detección de Estrés Académico');
        $response->assertSee('Manuel Antonio');
        $response->assertSee('Lucía');

        // Comprobar formato institucional legible del grupo: '4.º B — ITI'
        $response->assertSee('4.º B — ITI');
        $response->assertSee('2.º A — ITI');
    }

    public function test_resultados_psicometricos_filters_by_cuatrimestre_and_grupo(): void
    {
        // 1. Filtrar exclusivamente por cuatrimestre 4 (Manuel Antonio)
        $resCuatri = $this->actingAs($this->admin)
            ->get(route('admin.tests.resultados', [
                'test' => $this->instrumento->instrumento_id,
                'cuatrimestre' => 4,
            ]));

        $resCuatri->assertOk();
        $resCuatri->assertSee('Manuel Antonio');
        $resCuatri->assertDontSee('Lucía');

        // 2. Filtrar exclusivamente por grupo 'A' (Lucía)
        $resGrupo = $this->actingAs($this->admin)
            ->get(route('admin.tests.resultados', [
                'test' => $this->instrumento->instrumento_id,
                'grupo_id' => $this->grupo2A->grupo_id,
            ]));

        $resGrupo->assertOk();
        $resGrupo->assertSee('Lucía');
        $resGrupo->assertDontSee('Manuel Antonio');

        // 3. Filtrar de forma combinada (programa + cuatrimestre + grupo)
        $resCombinado = $this->actingAs($this->admin)
            ->get(route('admin.tests.resultados', [
                'test' => $this->instrumento->instrumento_id,
                'programa_id' => $this->programaITI->programa_id,
                'cuatrimestre' => 4,
                'grupo_id' => $this->grupo4B->grupo_id,
            ]));

        $resCombinado->assertOk();
        $resCombinado->assertSee('Manuel Antonio');
        $resCombinado->assertDontSee('Lucía');

        // 4. Filtrar por grupo sin respuestas registradas (debe responder 200 y mostrar mensaje vacío institucional)
        $resVacio = $this->actingAs($this->admin)
            ->get(route('admin.tests.resultados', [
                'test' => $this->instrumento->instrumento_id,
                'cuatrimestre' => 6,
                'grupo_id' => $this->grupoVacio->grupo_id,
            ]));

        $resVacio->assertOk();
        $resVacio->assertDontSee('Manuel Antonio');
        $resVacio->assertDontSee('Lucía');
        $resVacio->assertSee('No se encontraron intentos registrados');
    }

    // =========================================================================
    // FASE 3: Pruebas de Meditaciones (Retirar, Publicar, Eliminar e Idempotencia)
    // =========================================================================

    public function test_admin_can_retirar_publicada_meditacion_with_sessions_preserving_them(): void
    {
        $meditacion = $this->createMeditacion([
            'titulo' => 'Respiración Profunda 4-7-8',
            'slug' => 'respiracion-profunda-4-7-8',
            'es_publicado' => true,
        ]);

        // Registrar una sesión previa completada por el alumno
        $sesion = MeditacionSesion::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'meditacion_id' => $meditacion->meditacion_id,
            'completada' => true,
            'duracion_segundos' => 300,
            'fecha_sesion' => now(),
        ]);

        // Retirar la meditación como admin
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.meditaciones.retirar', $meditacion));

        $response->assertRedirect();
        $meditacion->refresh();

        // Verificar que pasa a no publicado pero no se borra físicamente
        $this->assertFalse($meditacion->es_publicado);
        $this->assertDatabaseHas('meditaciones', ['meditacion_id' => $meditacion->meditacion_id]);

        // Verificar que las sesiones históricas se conservan íntegras
        $this->assertDatabaseHas('meditaciones_sesiones', [
            'sesion_id' => $sesion->sesion_id,
            'meditacion_id' => $meditacion->meditacion_id,
        ]);
        $this->assertEquals(1, $meditacion->sesiones()->count());
    }

    public function test_admin_can_retirar_publicada_meditacion_without_sessions(): void
    {
        $meditacion = $this->createMeditacion([
            'titulo' => 'Pausa Consciente Breve',
            'slug' => 'pausa-consciente-breve',
            'es_publicado' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.meditaciones.retirar', $meditacion));

        $response->assertRedirect();
        $meditacion->refresh();

        $this->assertFalse($meditacion->es_publicado);
    }

    public function test_retirar_and_publicar_are_idempotent(): void
    {
        $meditacion = $this->createMeditacion([
            'titulo' => 'Escaneo Corporal Nocturno',
            'slug' => 'escaneo-corporal-nocturno',
            'categoria' => Meditacion::CATEGORIA_DESCANSO,
            'es_publicado' => false,
        ]);

        // 1. Retirar dos veces seguidas un ejercicio que ya está retirado
        $r1 = $this->actingAs($this->admin)->patch(route('admin.meditaciones.retirar', $meditacion));
        $r1->assertRedirect();
        $r1->assertSessionHas('status', "El ejercicio '{$meditacion->titulo}' ya se encontraba retirado del catálogo.");

        $r2 = $this->actingAs($this->admin)->patch(route('admin.meditaciones.retirar', $meditacion));
        $r2->assertRedirect();
        $this->assertFalse($meditacion->fresh()->es_publicado);

        // 2. Publicar por primera vez
        $p1 = $this->actingAs($this->admin)->patch(route('admin.meditaciones.publicar', $meditacion));
        $p1->assertRedirect();
        $this->assertTrue($meditacion->fresh()->es_publicado);

        // 3. Publicar por segunda vez consecutiva (debe ser idempotente)
        $p2 = $this->actingAs($this->admin)->patch(route('admin.meditaciones.publicar', $meditacion));
        $p2->assertRedirect();
        $p2->assertSessionHas('status', "El ejercicio '{$meditacion->titulo}' ya se encuentra publicado en el catálogo activo.");
        $this->assertTrue($meditacion->fresh()->es_publicado);
    }

    public function test_alumno_strictly_forbidden_404_from_accessing_withdrawn_meditation(): void
    {
        $meditacion = $this->createMeditacion([
            'titulo' => 'Relajación Guiada Privada',
            'slug' => 'relajacion-guiada-privada',
            'categoria' => Meditacion::CATEGORIA_MINDFULNESS,
            'es_publicado' => false, // RETIRADO / BORRADOR
        ]);

        // 1. Alumno intenta acceder al detalle -> 404
        $this->actingAs($this->alumno1)
            ->get(route('alumno.meditaciones.show', $meditacion))
            ->assertNotFound();

        // 2. Alumno intenta iniciar sesión -> 404
        $this->actingAs($this->alumno1)
            ->get(route('alumno.meditaciones.sesion', $meditacion))
            ->assertNotFound();

        // 3. Alumno intenta guardar progreso -> 404
        $this->actingAs($this->alumno1)
            ->post(route('alumno.meditaciones.guardar-sesion', $meditacion), ['segundos_reproducidos' => 60])
            ->assertNotFound();

        // 4. Alumno intenta completar sesión -> 404
        $this->actingAs($this->alumno1)
            ->post(route('alumno.meditaciones.completar', $meditacion), ['duracion_segundos' => 300])
            ->assertNotFound();

        // 5. Tras ser publicada por el admin, el alumno puede acceder correctamente
        $this->actingAs($this->admin)->patch(route('admin.meditaciones.publicar', $meditacion));

        $this->actingAs($this->alumno1)
            ->get(route('alumno.meditaciones.show', $meditacion))
            ->assertOk();
    }

    public function test_eliminar_meditacion_with_sessions_requires_keyword_ELIMINAR(): void
    {
        $meditacion = $this->createMeditacion([
            'titulo' => 'Meditación para Exámenes',
            'slug' => 'meditacion-para-examenes',
            'categoria' => Meditacion::CATEGORIA_PAUSA_ESTUDIO,
            'es_publicado' => false,
        ]);

        $sesion = MeditacionSesion::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'meditacion_id' => $meditacion->meditacion_id,
            'completada' => true,
            'duracion_segundos' => 480,
            'fecha_sesion' => now(),
        ]);

        // Intento de eliminación sin palabra clave
        $fail1 = $this->actingAs($this->admin)
            ->delete(route('admin.meditaciones.destroy', $meditacion));

        $fail1->assertRedirect();
        $fail1->assertSessionHas('error');
        $this->assertDatabaseHas('meditaciones', ['meditacion_id' => $meditacion->meditacion_id]);
        $this->assertDatabaseHas('meditaciones_sesiones', ['sesion_id' => $sesion->sesion_id]);

        // Intento con palabra incorrecta
        $fail2 = $this->actingAs($this->admin)
            ->delete(route('admin.meditaciones.destroy', $meditacion), [
                'confirmacion_palabra' => 'BORRAR',
            ]);

        $fail2->assertRedirect();
        $fail2->assertSessionHas('error');
        $this->assertDatabaseHas('meditaciones', ['meditacion_id' => $meditacion->meditacion_id]);

        // Eliminación autorizada con la palabra 'ELIMINAR'
        $success = $this->actingAs($this->admin)
            ->delete(route('admin.meditaciones.destroy', $meditacion), [
                'confirmacion_palabra' => 'ELIMINAR',
            ]);

        $success->assertRedirect(route('admin.meditaciones.index'));
        $success->assertSessionHas('success');
        $this->assertDatabaseMissing('meditaciones', ['meditacion_id' => $meditacion->meditacion_id]);
        $this->assertDatabaseMissing('meditaciones_sesiones', ['sesion_id' => $sesion->sesion_id]);
    }

    public function test_eliminar_meditacion_without_sessions_allows_direct_deletion(): void
    {
        $meditacion = $this->createMeditacion([
            'titulo' => 'Borrador sin Sesiones',
            'slug' => 'borrador-sin-sesiones',
            'es_publicado' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.meditaciones.destroy', $meditacion));

        $response->assertRedirect(route('admin.meditaciones.index'));
        $this->assertDatabaseMissing('meditaciones', ['meditacion_id' => $meditacion->meditacion_id]);
    }

    public function test_meditaciones_index_displays_homogeneous_actions_for_all_types(): void
    {
        // 1. Ejercicio publicado con sesiones
        $m1 = $this->createMeditacion([
            'titulo' => 'Ejercicio Publicado Con Sesiones',
            'slug' => 'ej-pub-con-sesiones',
            'es_publicado' => true,
        ]);
        MeditacionSesion::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'meditacion_id' => $m1->meditacion_id,
            'completada' => true,
            'duracion_segundos' => 300,
            'fecha_sesion' => now(),
        ]);

        // 2. Ejercicio publicado sin sesiones
        $m2 = $this->createMeditacion([
            'titulo' => 'Ejercicio Publicado Sin Sesiones',
            'slug' => 'ej-pub-sin-sesiones',
            'es_publicado' => true,
        ]);

        // 3. Ejercicio retirado con sesiones
        $m3 = $this->createMeditacion([
            'titulo' => 'Ejercicio Retirado Con Sesiones',
            'slug' => 'ej-ret-con-sesiones',
            'es_publicado' => false,
        ]);
        MeditacionSesion::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'meditacion_id' => $m3->meditacion_id,
            'completada' => true,
            'duracion_segundos' => 600,
            'fecha_sesion' => now(),
        ]);

        // 4. Ejercicio borrador sin sesiones
        $m4 = $this->createMeditacion([
            'titulo' => 'Ejercicio Borrador Sin Sesiones',
            'slug' => 'ej-borr-sin-sesiones',
            'es_publicado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.meditaciones.index'));
        $response->assertOk();

        // Todos muestran 'Editar' y 'Eliminar'
        $response->assertSee(route('admin.meditaciones.edit', $m1));
        $response->assertSee(route('admin.meditaciones.edit', $m2));
        $response->assertSee(route('admin.meditaciones.edit', $m3));
        $response->assertSee(route('admin.meditaciones.edit', $m4));

        // Los publicados muestran botón/modal 'Retirar' (incluso el que no tiene sesiones)
        $response->assertSee("#modalRetirarMeditacion{$m1->meditacion_id}", false);
        $response->assertSee("#modalRetirarMeditacion{$m2->meditacion_id}", false);

        // Los no publicados muestran botón/modal 'Volver a publicar' o 'Publicar'
        $response->assertSee("#modalPublicarMeditacion{$m3->meditacion_id}", false);
        $response->assertSee("#modalPublicarMeditacion{$m4->meditacion_id}", false);

        // Comprobar presencia de badges
        $response->assertSee('Publicado');
        $response->assertSee('Retirado');
        $response->assertSee('Borrador');
    }

    public function test_bitacora_puntos_reversion_and_relog_cycle(): void
    {
        $emocion = Emocion::firstOrCreate(['slug' => 'calma-test'], [
            'nombre' => 'Calma Test',
            'slug' => 'calma-test',
            'color_hex' => '#10b981',
            'activo' => true,
        ]);

        $bitacora1 = BitacoraEmocional::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'intensidad' => 3,
            'descripcion' => 'Registro inicial',
            'fecha' => now()->toDateString(),
        ]);

        $puntoService = app(PuntoService::class);
        $fecha = now()->toDateString();

        // 1. Otorgar bonificación diaria
        $transaccion1 = $puntoService->otorgarPorEmocionDiaria($this->alumno1, $bitacora1);
        $this->assertNotNull($transaccion1);
        $this->assertEquals(1, $this->alumno1->fresh()->estudiante->saldo_puntos);
        $this->assertTrue($puntoService->tieneBonificacionEmocionActiva($this->alumno1->usuario_id, $fecha));

        // 2. Revertir bonificación por eliminación del registro emocional
        $reversion = $puntoService->revertirPorEliminacionEmocion($this->alumno1, $bitacora1);
        $this->assertTrue($reversion['revertido']);
        $this->assertEquals(0, $this->alumno1->fresh()->estudiante->saldo_puntos);
        $this->assertFalse($puntoService->tieneBonificacionEmocionActiva($this->alumno1->usuario_id, $fecha));

        // Eliminar físicamente el registro previo para simular el ciclo de vida del usuario
        $bitacora1->delete();

        // 3. El estudiante registra nuevamente una emoción el mismo día: debe recibir su bonificación legítima
        $bitacora2 = BitacoraEmocional::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'intensidad' => 4,
            'descripcion' => 'Segundo registro legítimo',
            'fecha' => now()->toDateString(),
        ]);
        $transaccion2 = $puntoService->otorgarPorEmocionDiaria($this->alumno1, $bitacora2);
        $this->assertNotNull($transaccion2);
        $this->assertEquals(1, $this->alumno1->fresh()->estudiante->saldo_puntos);
        $this->assertTrue($puntoService->tieneBonificacionEmocionActiva($this->alumno1->usuario_id, $fecha));
    }

    public function test_bitacora_deletion_blocked_if_points_already_spent(): void
    {
        $emocion = Emocion::firstOrCreate(['slug' => 'calma-test'], [
            'nombre' => 'Calma Test',
            'slug' => 'calma-test',
            'color_hex' => '#10b981',
            'activo' => true,
        ]);

        $bitacora = BitacoraEmocional::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'intensidad' => 3,
            'descripcion' => 'Registro previo',
            'fecha' => now()->toDateString(),
        ]);

        $puntoService = app(PuntoService::class);
        $puntoService->otorgarPorEmocionDiaria($this->alumno1, $bitacora);
        $this->assertEquals(1, $this->alumno1->fresh()->estudiante->saldo_puntos);

        // Simular que el estudiante canjeó recompensas y su saldo actual es 0 (menor a los puntos a revertir)
        $this->alumno1->estudiante->update(['saldo_puntos' => 0]);

        // La reversión debe arrojar DomainException por saldo insuficiente impidiendo saldo negativo
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No es posible eliminar este registro emocional');

        $puntoService->revertirPorEliminacionEmocion($this->alumno1, $bitacora);
    }

    public function test_recompensa_catalogo_update_toggle_and_safe_delete(): void
    {
        $recompensa = RecompensaCatalogo::create([
            'nombre' => 'Asesoría Académica Especial',
            'descripcion' => 'Sesión de asesoría personalizada.',
            'costo_puntos_base' => 50,
            'categoria' => 'ACADEMICA',
            'activo' => true,
        ]);

        // 1. Edición administrativa
        $updateResp = $this->actingAs($this->admin)->put(route('admin.recompensas.update', $recompensa), [
            'nombre' => 'Asesoría Académica Premium',
            'descripcion' => 'Descripción actualizada.',
            'costo_puntos_base' => 75,
            'categoria' => 'ACADEMICA',
        ]);
        $updateResp->assertRedirect();
        $this->assertDatabaseHas('recompensas_catalogo', [
            'recompensa_id' => $recompensa->recompensa_id,
            'nombre' => 'Asesoría Académica Premium',
            'costo_puntos_base' => 75,
        ]);

        // 2. Conmutación de estado (Toggle)
        $toggleResp = $this->actingAs($this->admin)->patch(route('admin.recompensas.toggle', $recompensa));
        $toggleResp->assertRedirect();
        $this->assertDatabaseHas('recompensas_catalogo', [
            'recompensa_id' => $recompensa->recompensa_id,
            'activo' => false,
        ]);

        // 3. Eliminación segura sin canjes: eliminación física atómica
        $deleteResp = $this->actingAs($this->admin)
            ->from(route('admin.recompensas.catalogo'))
            ->delete(route('admin.recompensas.destroy', $recompensa));
        $deleteResp->assertRedirect(route('admin.recompensas.catalogo'));
        $this->assertDatabaseMissing('recompensas_catalogo', [
            'recompensa_id' => $recompensa->recompensa_id,
        ]);
    }

    public function test_alumno_dashboard_counts_evaluaciones_and_saldo_puntos(): void
    {
        // Crear un segundo instrumento sin intentos previos que esté publicado
        $instrumentoDisponible = InstrumentoEvaluacion::create([
            'codigo' => 'TAM-ANSIEDAD-04',
            'nombre' => 'Escala de Ansiedad',
            'siglas' => 'EA-04',
            'categoria' => 'ANSIEDAD',
            'descripcion' => 'Evaluación de ansiedad.',
            'activo' => true,
            'dias_espera_reaplicacion' => 0,
        ]);

        VersionInstrumento::create([
            'instrumento_id' => $instrumentoDisponible->instrumento_id,
            'version' => '1.0',
            'nombre_version' => 'v1.0',
            'es_vigente' => true,
            'estado' => 'PUBLICADA',
            'instrucciones' => 'Instrucciones para responder cuestionario.',
            'duracion_estimada_min' => 5,
        ]);

        $this->alumno1->estudiante->update(['saldo_puntos' => 125]);

        $response = $this->actingAs($this->alumno1)->get(route('alumno.dashboard'));
        $response->assertOk();
        $response->assertSee('125 pts');
        $this->assertEquals(1, $response->viewData('evaluacionesDisponiblesConteo'));
        $this->assertEquals(125, $response->viewData('saldoPuntos'));
    }

    public function test_reporte_historial_alumno_defaults_cleanly_and_exports(): void
    {
        // Crear una bitácora para el alumno1
        $emocion = Emocion::firstOrCreate(['slug' => 'tranquilidad'], [
            'nombre' => 'Tranquilidad',
            'slug' => 'tranquilidad',
            'color_hex' => '#059669',
            'activo' => true,
        ]);

        BitacoraEmocional::create([
            'usuario_id' => $this->alumno1->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'intensidad' => 4,
            'descripcion' => 'Día de estudio productivo',
            'fecha' => now()->toDateString(),
        ]);

        // Visitar reporte de historial sin enviar estudiante_id explícito
        $response = $this->actingAs($this->admin)->get(route('admin.reportes.show', 'historial-alumno'));
        $response->assertOk();
        $response->assertSee('Historial Individual de Bitácoras y Citas');
        $response->assertSee($this->alumno1->matricula);

        // Exportar a Excel
        $excelResp = $this->actingAs($this->admin)->get(route('admin.reportes.exportar-excel', 'historial-alumno'));
        $excelResp->assertOk();
    }

    public function test_respaldo_restauracion_requires_valid_admin_password(): void
    {
        $respaldoService = app(RespaldoDatabaseService::class);
        $res = $respaldoService->crearRespaldo('test_unit');
        $this->assertTrue($res['success']);
        $archivo = $res['nombre'];

        // 1. Intento con contraseña errónea
        $fail = $this->actingAs($this->admin)->post(route('admin.respaldos.solicitar-restauracion', $archivo), [
            'password' => 'WrongPassword999!',
        ]);
        $fail->assertRedirect(route('admin.respaldos.index'));
        $fail->assertSessionHas('error');

        // 2. Intento con contraseña correcta de administrador
        $success = $this->actingAs($this->admin)->post(route('admin.respaldos.solicitar-restauracion', $archivo), [
            'password' => 'AdminSecret123!',
        ]);
        $success->assertRedirect(route('admin.respaldos.index'));
        $success->assertSessionHasNoErrors();

        // Limpiar archivo creado
        $respaldoService->eliminarRespaldo($archivo);
    }
}
