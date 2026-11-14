<?php

namespace Tests\Feature;

use App\Models\BaremoRegla;
use App\Models\EscalaOpcion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\InstrumentoEvaluacion;
use App\Models\PreguntaEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorreccionAceptacion01TestsPsicometricosTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $docente;
    protected User $alumno;
    protected ProgramaEducativo $programa;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::firstOrCreate(['rol_id' => 1], ['nombre' => 'Alumno']);
        $rolDocente = Rol::firstOrCreate(['rol_id' => 2], ['nombre' => 'Docente']);
        $rolAdmin = Rol::firstOrCreate(['rol_id' => 3], ['nombre' => 'Administrador']);
        Rol::firstOrCreate(['rol_id' => 4], ['nombre' => 'Orientador']);

        $this->programa = ProgramaEducativo::create([
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'clave' => 'ITI',
            'activo' => true,
        ]);

        $this->admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'Bienestar',
            'apellido_materno' => 'UPEMOR',
            'email' => 'admin.tests@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->docente = User::create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Mendoza',
            'apellido_materno' => 'Silva',
            'email' => 'docente.tests@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolDocente->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->alumno = User::create([
            'nombre' => 'Lucía',
            'apellido_paterno' => 'Gómez',
            'apellido_materno' => 'Ríos',
            'email' => 'GRLO240001@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'matricula' => 'GRLO240001',
            'programa_id' => $this->programa->programa_id,
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'puntos_bienestar' => 50,
        ]);
    }

    public function test_a8_create_view_displays_institutional_responsibility_notice(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tests.create'));

        $response->assertOk();
        $response->assertSee('Uso responsable de instrumentos de evaluación');
        $response->assertSee('Los tests psicométricos registrados en Bienestar UPEMOR tienen carácter orientativo, formativo y de detección preventiva.');
        $response->assertSee('Sus resultados no constituyen por sí mismos un diagnóstico clínico o psicológico definitivo, ni sustituyen la valoración de un profesional especializado.');
        $response->assertSee('Todo instrumento que se publique debe contar con sustento metodológico claro, instrucciones comprensibles, escalas consistentes y manejo confidencial de las respuestas.');
    }

    public function test_a2_a3_admin_can_create_test_with_all_four_question_types_scales_and_baremos(): void
    {
        $payload = [
            'codigo' => 'TAM-INTEGRAL-01',
            'nombre' => 'Cuestionario Integral de Hábitos y Bienestar',
            'siglas' => 'CIHB',
            'categoria' => 'BIENESTAR',
            'descripcion' => 'Instrumento estructurado con múltiples tipos de reactivos.',
            'autor' => 'Comité Institucional UPEMOR',
            'anio_publicacion' => 2026,
            'dias_espera_reaplicacion' => 7,
            'instrucciones' => 'Responde cada reactivo según tu experiencia en las últimas dos semanas.',
            'marco_temporal' => 'Últimas 2 semanas',
            'duracion_estimada_min' => 8,
            'puntos_recompensa' => 25,
            'accion_publicacion' => 'publicar',
            'escalas' => [
                ['valor' => 0, 'etiqueta' => 'Nunca', 'descripcion' => '0 días'],
                ['valor' => 1, 'etiqueta' => 'Varios días', 'descripcion' => '1 a 6 días'],
                ['valor' => 2, 'etiqueta' => 'Frecuentemente', 'descripcion' => 'Más de la mitad'],
                ['valor' => 3, 'etiqueta' => 'Casi siempre', 'descripcion' => 'Casi todos los días'],
            ],
            'preguntas' => [
                [
                    'enunciado' => '¿Con qué frecuencia descansas al menos 7 horas por noche?',
                    'tipo_pregunta' => 'ESCALA',
                    'es_obligatoria' => '1',
                    'es_inverso' => '0',
                    'es_critico' => '0',
                ],
                [
                    'enunciado' => '¿Cuál es tu principal modalidad de estudio fuera del aula?',
                    'tipo_pregunta' => 'OPCION_UNICA',
                    'es_obligatoria' => '1',
                    'opciones' => [
                        ['etiqueta' => 'Estudio individual planificado', 'valor' => 3],
                        ['etiqueta' => 'Estudio en equipo ocasional', 'valor' => 2],
                        ['etiqueta' => 'Solo antes de exámenes', 'valor' => 0],
                    ],
                ],
                [
                    'enunciado' => '¿Qué actividades de autocuidado realizas durante la semana?',
                    'tipo_pregunta' => 'OPCION_MULTIPLE',
                    'es_obligatoria' => '1',
                    'opciones' => [
                        ['etiqueta' => 'Actividad física o deporte', 'valor' => 2],
                        ['etiqueta' => 'Meditación o respiración consciente', 'valor' => 2],
                        ['etiqueta' => 'Organización de agenda académica', 'valor' => 1],
                    ],
                ],
                [
                    'enunciado' => '¿Deseas agregar algún comentario sobre tus hábitos de estudio?',
                    'tipo_pregunta' => 'TEXTO_LIBRE',
                    'es_obligatoria' => '0',
                ],
            ],
            'baremos' => [
                [
                    'nivel' => 'Hábitos por fortalecer',
                    'puntuacion_min' => 0,
                    'puntuacion_max' => 4,
                    'color_hex' => '#f59e0b',
                    'interpretacion' => 'Se recomienda reforzar rutinas de descanso y estudio.',
                    'recomendacion' => 'Explora las sesiones de organización y bienestar.',
                    'sugiere_orientacion' => '1',
                ],
                [
                    'nivel' => 'Hábitos saludables',
                    'puntuacion_min' => 5,
                    'puntuacion_max' => 15,
                    'color_hex' => '#10b981',
                    'interpretacion' => 'Mantienes prácticas saludables de bienestar.',
                    'recomendacion' => 'Continúa con tus hábitos actuales.',
                    'sugiere_orientacion' => '0',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.tests.store'), $payload);

        $instrumento = InstrumentoEvaluacion::where('codigo', 'TAM-INTEGRAL-01')->first();
        $this->assertNotNull($instrumento);
        $this->assertTrue((bool) $instrumento->activo);
        $response->assertRedirect(route('admin.tests.show', $instrumento->instrumento_id));

        $version = $instrumento->versionVigente;
        $this->assertNotNull($version);
        $this->assertEquals('PUBLICADA', $version->estado);
        $this->assertEquals(4, $version->preguntas()->count());
        $this->assertEquals(4, $version->escalas()->count());
        $this->assertEquals(2, $version->baremos()->count());

        $preguntaAbierta = $version->preguntas()->where('tipo_pregunta', 'TEXTO_LIBRE')->first();
        $this->assertNotNull($preguntaAbierta);
        $this->assertFalse((bool) $preguntaAbierta->es_obligatoria);
    }

    public function test_a3_draft_without_questions_is_saved_as_borrador_but_cannot_be_published(): void
    {
        // 1. Intentar publicar sin preguntas debe fallar con errores de validación
        $responseFail = $this->actingAs($this->admin)->post(route('admin.tests.store'), [
            'codigo' => 'TEST-VACIO-PUB',
            'nombre' => 'Test Incompleto',
            'categoria' => 'OTRO',
            'descripcion' => 'Intento de publicar sin preguntas.',
            'dias_espera_reaplicacion' => 0,
            'instrucciones' => 'Instrucciones base.',
            'marco_temporal' => 'Actual',
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 10,
            'accion_publicacion' => 'publicar',
            'preguntas' => [],
        ]);

        $responseFail->assertSessionHasErrors('preguntas');
        $this->assertDatabaseMissing('evaluaciones_instrumentos', ['codigo' => 'TEST-VACIO-PUB']);

        // 2. Guardar como borrador sin preguntas sí se permite en estado BORRADOR e inactivo
        $responseDraft = $this->actingAs($this->admin)->post(route('admin.tests.store'), [
            'codigo' => 'TEST-BORRADOR-01',
            'nombre' => 'Test en Borrador Inicial',
            'categoria' => 'OTRO',
            'descripcion' => 'Borrador guardado para completar después.',
            'dias_espera_reaplicacion' => 0,
            'instrucciones' => 'Instrucciones base.',
            'marco_temporal' => 'Actual',
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 10,
            'accion_publicacion' => 'borrador',
            'preguntas' => [],
        ]);

        $borrador = InstrumentoEvaluacion::where('codigo', 'TEST-BORRADOR-01')->first();
        $this->assertNotNull($borrador);
        $this->assertFalse((bool) $borrador->activo);
        $this->assertEquals('BORRADOR', $borrador->versiones()->first()->estado);
    }

    public function test_a4_protection_of_historical_responses_and_versioning_workflow(): void
    {
        // Crear instrumento con versión 1.0 publicada
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'VER-TEST-01',
            'nombre' => 'Instrumento con Versionado',
            'siglas' => 'VT01',
            'categoria' => 'ANSIEDAD',
            'descripcion' => 'Prueba de versionado.',
            'dias_espera_reaplicacion' => 0,
            'activo' => true,
            'orden' => 1,
        ]);

        $v1 = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0',
            'nombre_version' => 'Versión Inicial 1.0',
            'instrucciones' => 'Responde cada ítem.',
            'marco_temporal' => 'Últimas 2 semanas',
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 10,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
            'vigente_desde' => now(),
        ]);

        $escala0 = EscalaOpcion::create(['version_id' => $v1->version_id, 'valor' => 0, 'etiqueta' => 'Nunca', 'orden' => 1]);
        $escala1 = EscalaOpcion::create(['version_id' => $v1->version_id, 'valor' => 1, 'etiqueta' => 'A veces', 'orden' => 2]);

        $preguntaV1 = PreguntaEvaluacion::create([
            'version_id' => $v1->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Reactivo original de la versión 1.0',
            'tipo_pregunta' => 'ESCALA',
            'es_obligatoria' => true,
            'orden' => 1,
            'activo' => true,
        ]);

        BaremoRegla::create([
            'version_id' => $v1->version_id,
            'nivel' => 'Normal',
            'puntuacion_min' => 0,
            'puntuacion_max' => 5,
            'color_hex' => '#10b981',
            'mensaje_retroalimentacion' => 'Nivel normal',
            'sugiere_orientacion' => false,
            'orden' => 1,
        ]);

        // Registrar un intento finalizado en v1.0
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $v1->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 1,
            'nivel_resultado' => 'Normal',
            'fecha_inicio' => now()->subMinutes(5),
            'fecha_finalizacion' => now(),
        ]);

        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $preguntaV1->pregunta_id,
            'escala_id' => $escala1->escala_id,
            'puntos_obtenidos' => 1,
        ]);

        // Intentar actualizar reactivos de v1.0 directamente: el controlador debe preservar el reactivo original de v1.0
        $this->actingAs($this->admin)->put(route('admin.tests.update', $instrumento->instrumento_id), [
            'codigo' => 'VER-TEST-01',
            'nombre' => 'Instrumento con Versionado Editado',
            'categoria' => 'ANSIEDAD',
            'descripcion' => 'Metadatos actualizados.',
            'dias_espera_reaplicacion' => 0,
            'version_id_edicion' => $v1->version_id,
            'preguntas' => [
                ['enunciado' => 'Intento de sobrescribir reactivo con respuestas', 'tipo_pregunta' => 'ESCALA'],
            ],
        ]);

        $this->assertEquals('Reactivo original de la versión 1.0', $preguntaV1->fresh()->enunciado);

        // Tampoco se debe permitir eliminar el instrumento porque tiene intentos registrados
        $deleteResp = $this->actingAs($this->admin)->delete(route('admin.tests.destroy', $instrumento->instrumento_id));
        $deleteResp->assertSessionHas('error');
        $this->assertDatabaseHas('evaluaciones_instrumentos', ['instrumento_id' => $instrumento->instrumento_id]);

        // Crear nueva versión v2.0 mediante el endpoint nuevaVersion
        $nuevaVerResp = $this->actingAs($this->admin)->post(route('admin.tests.nueva-version', $instrumento->instrumento_id));
        $v2 = VersionInstrumento::where('instrumento_id', $instrumento->instrumento_id)
            ->where('version', '2.0')
            ->first();

        $this->assertNotNull($v2);
        $this->assertEquals('BORRADOR', $v2->estado);
        $nuevaVerResp->assertRedirect(route('admin.tests.edit', ['test' => $instrumento->instrumento_id, 'version_id' => $v2->version_id]));

        // Editar reactivos en v2.0 (que tiene 0 intentos) y publicarla
        $this->actingAs($this->admin)->put(route('admin.tests.update', $instrumento->instrumento_id), [
            'codigo' => 'VER-TEST-01',
            'nombre' => 'Instrumento con Versionado Editado',
            'categoria' => 'ANSIEDAD',
            'descripcion' => 'Metadatos actualizados.',
            'dias_espera_reaplicacion' => 0,
            'version_id_edicion' => $v2->version_id,
            'instrucciones' => 'Instrucciones v2.0',
            'marco_temporal' => 'Última semana',
            'duracion_estimada_min' => 6,
            'puntos_recompensa' => 15,
            'accion_version' => 'publicar',
            'activo' => '1',
            'escalas' => [
                ['valor' => 0, 'etiqueta' => 'Nunca'],
                ['valor' => 1, 'etiqueta' => 'Siempre'],
            ],
            'preguntas' => [
                ['enunciado' => 'Reactivo renovado en v2.0', 'tipo_pregunta' => 'ESCALA', 'es_obligatoria' => '1'],
                ['enunciado' => 'Segundo reactivo agregado en v2.0', 'tipo_pregunta' => 'ESCALA', 'es_obligatoria' => '1'],
            ],
            'baremos' => [
                ['nivel' => 'Bajo', 'puntuacion_min' => 0, 'puntuacion_max' => 2, 'color_hex' => '#10b981', 'interpretacion' => 'OK'],
            ],
        ]);

        // Verificar que v2.0 quedó PUBLICADA y vigente, mientras v1.0 pasó a OBSOLETA conservando su intento intacto
        $this->assertEquals('PUBLICADA', $v2->fresh()->estado);
        $this->assertTrue((bool) $v2->fresh()->es_vigente);
        $this->assertEquals(2, $v2->fresh()->preguntas()->count());

        $this->assertEquals('OBSOLETA', $v1->fresh()->estado);
        $this->assertFalse((bool) $v1->fresh()->es_vigente);
        $this->assertEquals(1, EvaluacionIntento::where('version_id', $v1->version_id)->count());
        $this->assertEquals('Reactivo original de la versión 1.0', $preguntaV1->fresh()->enunciado);
    }

    public function test_a5_repeat_interval_allows_immediate_when_zero_and_blocks_when_positive_days(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'REP-TEST-01',
            'nombre' => 'Test de Repetición Configurable',
            'categoria' => 'ESTRES',
            'descripcion' => 'Prueba de intervalo.',
            'dias_espera_reaplicacion' => 0,
            'activo' => true,
            'orden' => 1,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0',
            'nombre_version' => 'v1.0',
            'instrucciones' => 'Instrucciones.',
            'marco_temporal' => 'Hoy',
            'duracion_estimada_min' => 3,
            'puntos_recompensa' => 5,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
            'vigente_desde' => now(),
        ]);

        EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 2,
            'nivel_resultado' => 'Leve',
            'fecha_inicio' => now()->subMinutes(15),
            'fecha_finalizacion' => now()->subMinutes(10),
        ]);

        // Con dias_espera_reaplicacion = 0, puede repetir inmediatamente (0 días restantes)
        $this->assertEquals(0, $instrumento->diasRestantesParaUsuario($this->alumno->usuario_id));

        // Con dias_espera_reaplicacion = 1 (24 horas), debe esperar 1 día
        $instrumento->update(['dias_espera_reaplicacion' => 1]);
        $this->assertGreaterThan(0, $instrumento->fresh()->diasRestantesParaUsuario($this->alumno->usuario_id));

        // Con dias_espera_reaplicacion = 7 (1 semana), debe esperar ~7 días
        $instrumento->update(['dias_espera_reaplicacion' => 7]);
        $this->assertGreaterThanOrEqual(6, $instrumento->fresh()->diasRestantesParaUsuario($this->alumno->usuario_id));
    }

    public function test_a6_a7_results_statistics_and_individual_attempt_detail_and_role_security(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'RES-STATS-01',
            'nombre' => 'Test de Estadísticas y Detalle',
            'categoria' => 'BIENESTAR',
            'descripcion' => 'Prueba de A6 y A7.',
            'dias_espera_reaplicacion' => 0,
            'activo' => true,
            'orden' => 1,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0',
            'nombre_version' => 'v1.0',
            'instrucciones' => 'Contesta todo.',
            'marco_temporal' => 'Últimas 2 semanas',
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 10,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
            'vigente_desde' => now(),
        ]);

        $esc0 = EscalaOpcion::create(['version_id' => $version->version_id, 'valor' => 0, 'etiqueta' => 'Nunca', 'orden' => 1]);
        $esc2 = EscalaOpcion::create(['version_id' => $version->version_id, 'valor' => 2, 'etiqueta' => 'Siempre', 'orden' => 2]);

        $p1 = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => '¿Duermes bien?',
            'tipo_pregunta' => 'ESCALA',
            'es_obligatoria' => true,
            'orden' => 1,
            'activo' => true,
        ]);

        $p2 = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 2,
            'enunciado' => 'Comentario abierto de bienestar',
            'tipo_pregunta' => 'TEXTO_LIBRE',
            'es_obligatoria' => false,
            'orden' => 2,
            'activo' => true,
        ]);

        BaremoRegla::create([
            'version_id' => $version->version_id,
            'nivel' => 'Óptimo',
            'puntuacion_min' => 0,
            'puntuacion_max' => 5,
            'color_hex' => '#10b981',
            'mensaje_retroalimentacion' => 'Descanso adecuado.',
            'sugiere_orientacion' => false,
            'orden' => 1,
        ]);

        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 2,
            'nivel_resultado' => 'Óptimo',
            'fecha_inicio' => now()->subMinutes(4),
            'fecha_finalizacion' => now()->subMinutes(1),
        ]);

        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $p1->pregunta_id,
            'escala_id' => $esc2->escala_id,
            'puntos_obtenidos' => 2,
        ]);

        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $p2->pregunta_id,
            'escala_id' => null,
            'respuesta_texto' => 'Me siento más descansada este cuatrimestre.',
            'puntos_obtenidos' => 0,
        ]);

        // 1. Consulta de resultados generales (A6)
        $resView = $this->actingAs($this->admin)->get(route('admin.tests.resultados', $instrumento->instrumento_id));
        $resView->assertOk();
        $resView->assertSee('Test de Estadísticas y Detalle');
        $resView->assertSee('¿Duermes bien?');
        $resView->assertSee('Me siento más descansada este cuatrimestre.');
        $resView->assertSee('GRLO240001');
        $resView->assertSee('Intento #1');

        // 2. Consulta de intento individual (A7)
        $detView = $this->actingAs($this->admin)->get(route('admin.tests.intentos.show', [
            'test' => $instrumento->instrumento_id,
            'intento' => $intento->intento_id,
        ]));
        $detView->assertOk();
        $detView->assertSee('Consulta Individual de Respuestas');
        $detView->assertSee('Lucía Gómez Ríos');
        $detView->assertSee('GRLO240001');
        $detView->assertSee('Intento #1 de 1');
        $detView->assertSee('Siempre');
        $detView->assertSee('Me siento más descansada este cuatrimestre.');
        $detView->assertSee('0 pts (Abierta)');

        // 3. Restricción de seguridad: Docente y Alumno reciben 403 en rutas administrativas de tests
        $this->actingAs($this->docente)
            ->get(route('admin.tests.resultados', $instrumento->instrumento_id))
            ->assertForbidden();

        $this->actingAs($this->docente)
            ->get(route('admin.tests.intentos.show', [$instrumento->instrumento_id, $intento->intento_id]))
            ->assertForbidden();

        $this->actingAs($this->alumno)
            ->get(route('admin.tests.intentos.show', [$instrumento->instrumento_id, $intento->intento_id]))
            ->assertForbidden();
    }

    public function test_a2_duplicate_retire_and_delete_without_responses(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'BASE-DUP-01',
            'nombre' => 'Instrumento Base para Duplicar',
            'categoria' => 'ESTRES',
            'descripcion' => 'Base.',
            'dias_espera_reaplicacion' => 7,
            'activo' => true,
            'orden' => 1,
        ]);

        $v1 = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0',
            'nombre_version' => 'v1.0',
            'instrucciones' => 'Instrucciones.',
            'marco_temporal' => 'Actual',
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 10,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
        ]);

        PreguntaEvaluacion::create([
            'version_id' => $v1->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Pregunta base a duplicar',
            'tipo_pregunta' => 'ESCALA',
            'es_obligatoria' => true,
            'orden' => 1,
            'activo' => true,
        ]);

        // Duplicar instrumento
        $dupResp = $this->actingAs($this->admin)->post(route('admin.tests.duplicar', $instrumento->instrumento_id));
        $copia = InstrumentoEvaluacion::where('codigo', '!=', 'BASE-DUP-01')->first();
        $this->assertNotNull($copia);
        $this->assertFalse((bool) $copia->activo);
        $this->assertEquals(1, $copia->versiones()->first()->preguntas()->count());
        $dupResp->assertRedirect(route('admin.tests.edit', $copia->instrumento_id));

        // Retirar instrumento original
        $retResp = $this->actingAs($this->admin)->patch(route('admin.tests.retirar', $instrumento->instrumento_id));
        $retResp->assertSessionHas('status');
        $this->assertFalse((bool) $instrumento->fresh()->activo);
        $this->assertTrue((bool) $instrumento->fresh()->retirado);

        // Eliminar la copia (que tiene 0 intentos)
        $delResp = $this->actingAs($this->admin)->delete(route('admin.tests.destroy', $copia->instrumento_id));
        $delResp->assertRedirect(route('admin.tests.index'));
        $this->assertDatabaseMissing('evaluaciones_instrumentos', ['instrumento_id' => $copia->instrumento_id]);
    }

    public function test_a3_student_can_answer_all_four_question_types_and_complete_evaluation(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'ALU-MULTI-01',
            'nombre' => 'Test Estudiantil Multirreactivo',
            'categoria' => 'BIENESTAR',
            'descripcion' => 'Prueba integral para el alumno.',
            'dias_espera_reaplicacion' => 0,
            'activo' => true,
            'orden' => 1,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0',
            'nombre_version' => 'v1.0',
            'instrucciones' => 'Responde el cuestionario.',
            'marco_temporal' => 'Últimas 2 semanas',
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 15,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
            'vigente_desde' => now(),
        ]);

        $esc0 = EscalaOpcion::create(['version_id' => $version->version_id, 'valor' => 0, 'etiqueta' => 'Nunca', 'orden' => 1]);
        $esc3 = EscalaOpcion::create(['version_id' => $version->version_id, 'valor' => 3, 'etiqueta' => 'Siempre', 'orden' => 2]);

        $pEscala = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Reactivo de escala Likert',
            'tipo_pregunta' => 'ESCALA',
            'es_obligatoria' => true,
            'orden' => 1,
            'activo' => true,
        ]);

        $pUnica = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 2,
            'enunciado' => 'Reactivo de opción única personalizada',
            'tipo_pregunta' => 'OPCION_UNICA',
            'es_obligatoria' => true,
            'opciones_json' => [
                ['etiqueta' => 'Alternativa A', 'valor' => 2],
                ['etiqueta' => 'Alternativa B', 'valor' => 0],
            ],
            'orden' => 2,
            'activo' => true,
        ]);

        $pMultiple = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 3,
            'enunciado' => 'Reactivo de selección múltiple',
            'tipo_pregunta' => 'OPCION_MULTIPLE',
            'es_obligatoria' => true,
            'opciones_json' => [
                ['etiqueta' => 'Deporte', 'valor' => 2],
                ['etiqueta' => 'Lectura', 'valor' => 1],
                ['etiqueta' => 'Música', 'valor' => 1],
            ],
            'orden' => 3,
            'activo' => true,
        ]);

        $pTexto = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 4,
            'enunciado' => 'Reactivo abierto complementario (0 pts)',
            'tipo_pregunta' => 'TEXTO_LIBRE',
            'es_obligatoria' => true,
            'orden' => 4,
            'activo' => true,
        ]);

        BaremoRegla::create([
            'version_id' => $version->version_id,
            'nivel' => 'Bienestar Alto',
            'puntuacion_min' => 0,
            'puntuacion_max' => 20,
            'color_hex' => '#10b981',
            'mensaje_retroalimentacion' => 'Excelente participación.',
            'sugiere_orientacion' => false,
            'orden' => 1,
        ]);

        // Iniciar intento como alumno
        $startResp = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.iniciar', $instrumento->instrumento_id), [
            'consentimiento' => '1',
        ]);

        $intento = EvaluacionIntento::where('usuario_id', $this->alumno->usuario_id)
            ->where('version_id', $version->version_id)
            ->first();
        $this->assertNotNull($intento);
        $startResp->assertRedirect(route('alumno.evaluaciones.cuestionario', $intento->intento_id));

        // 1. Responder ESCALA (3 pts)
        $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pEscala->pregunta_id,
            'escala_id' => $esc3->escala_id,
        ])->assertOk()->assertJsonPath('success', true);

        // 2. Responder OPCION_UNICA (2 pts)
        $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pUnica->pregunta_id,
            'opciones_seleccionadas' => ['Alternativa A'],
        ])->assertOk()->assertJsonPath('success', true);

        // 3. Responder OPCION_MULTIPLE (2 + 1 = 3 pts)
        $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pMultiple->pregunta_id,
            'opciones_seleccionadas' => ['Deporte', 'Lectura'],
        ])->assertOk()->assertJsonPath('success', true);

        // 4. Responder TEXTO_LIBRE (0 pts automáticos)
        $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pTexto->pregunta_id,
            'respuesta_texto' => 'Practico respiración profunda antes de cada examen.',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertEquals(0, EvaluacionRespuesta::where('intento_id', $intento->intento_id)->where('pregunta_id', $pTexto->pregunta_id)->value('puntos_obtenidos'));

        // Finalizar intento: puntaje total esperado = 3 + 2 + 3 + 0 = 8 pts
        $finResp = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.finalizar', $intento->intento_id));
        $finResp->assertRedirect(route('alumno.evaluaciones.finalizado', $intento->intento_id));

        $intento->refresh();
        $this->assertEquals('FINALIZADO', $intento->estado);
        $this->assertEquals(8, $intento->puntuacion_total);
        $this->assertEquals('Bienestar Alto', $intento->nivel_resultado);
    }
}
