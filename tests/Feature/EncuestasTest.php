<?php

namespace Tests\Feature;

use App\Models\CitaOrientacion;
use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use App\Models\EncuestaRespuesta;
use App\Models\EncuestaRespuestaDetalle;
use App\Models\Estudiante;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncuestasTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $alumno;
    protected Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 1, 'nombre' => 'Administrador']);
        $rolDocente = Rol::create(['rol_id' => 2, 'nombre' => 'Docente']);
        $rolAlumno = Rol::create(['rol_id' => 3, 'nombre' => 'Alumno']);
        $rolOrientador = Rol::create(['rol_id' => 4, 'nombre' => 'Orientador']);

        $pe = ProgramaEducativo::create([
            'programa_id' => 1,
            'clave' => 'ITI',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);

        $this->admin = User::factory()->create([
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'admin@upemor.edu.mx',
        ]);

        $this->alumno = User::factory()->create([
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'alumno@upemor.edu.mx',
        ]);

        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $pe->programa_id,
            'matricula' => 'ITI2026001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 100,
        ]);
    }

    public function test_admin_can_view_encuestas_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.encuestas.index'));
        $response->assertStatus(200);
        $response->assertSee('Encuestas de Satisfacción');
    }

    public function test_admin_can_create_survey_with_questions(): void
    {
        $payload = [
            'titulo' => 'Evaluación de Servicios 2026',
            'descripcion' => 'Encuesta para conocer la satisfacción general.',
            'objetivo' => 'Detectar áreas de mejora institucional.',
            'tipo' => 'GENERAL',
            'es_anonima' => 1,
            'permite_multiples_respuestas' => 0,
            'estado' => 'PUBLICADA',
            'preguntas' => [
                [
                    'texto' => '¿Qué tan satisfecho estás con la plataforma?',
                    'tipo' => 'LIKERT',
                    'es_obligatoria' => 1,
                ],
                [
                    'texto' => '¿Qué sugerencias de mejora tienes?',
                    'tipo' => 'TEXTO_LIBRE',
                    'es_obligatoria' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.encuestas.store'), $payload);
        $response->assertRedirect(route('admin.encuestas.index'));

        $this->assertDatabaseHas('encuestas', [
            'titulo' => 'Evaluación de Servicios 2026',
            'tipo' => 'GENERAL',
            'estado' => 'PUBLICADA',
        ]);

        $this->assertDatabaseCount('encuestas_preguntas', 2);
    }

    public function test_admin_can_toggle_survey_status(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta Test Toggle',
            'slug' => 'encuesta-test-toggle',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.encuestas.toggle-estado', $encuesta));
        $response->assertRedirect();

        $encuesta->refresh();
        $this->assertEquals(Encuesta::ESTADO_CERRADA, $encuesta->estado);
    }

    public function test_admin_cannot_delete_survey_with_responses(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta Con Respuestas',
            'slug' => 'encuesta-con-respuestas',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
        ]);

        EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'fecha_respuesta' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.encuestas.destroy', $encuesta));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('encuestas', [
            'encuesta_id' => $encuesta->encuesta_id,
        ]);
    }

    public function test_alumno_can_view_catalogue_and_respond_survey(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Satisfacción Estudiantil',
            'slug' => 'satisfaccion-estudiantil',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
        ]);

        $p1 = $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'La atención fue oportuna',
            'tipo_pregunta' => 'LIKERT',
            'es_obligatoria' => true,
            'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
        ]);

        $p2 = $encuesta->preguntas()->create([
            'orden' => 2,
            'texto_pregunta' => 'Comentarios finales',
            'tipo_pregunta' => 'TEXTO_LIBRE',
            'es_obligatoria' => false,
        ]);

        // Ver catálogo
        $resCat = $this->actingAs($this->alumno)->get(route('alumno.encuestas.index'));
        $resCat->assertStatus(200);
        $resCat->assertSee('Satisfacción Estudiantil');

        // Ver formulario
        $resForm = $this->actingAs($this->alumno)->get(route('alumno.encuestas.responder', $encuesta));
        $resForm->assertStatus(200);
        $resForm->assertSee('La atención fue oportuna');

        // Enviar respuesta
        $resSubmit = $this->actingAs($this->alumno)->post(route('alumno.encuestas.guardar', $encuesta), [
            'respuestas' => [
                $p1->pregunta_id => 5,
                $p2->pregunta_id => 'Excelente servicio y atención.',
            ],
        ]);

        $resSubmit->assertRedirect(route('alumno.encuestas.index'));
        $this->assertDatabaseCount('encuestas_respuestas', 1);
        $this->assertDatabaseCount('encuestas_respuestas_detalles', 2);
    }

    public function test_alumno_cannot_respond_same_survey_twice(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta Unica',
            'slug' => 'encuesta-unica',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => false,
            'permite_multiples_respuestas' => false,
        ]);

        $p = $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'Pregunta de prueba',
            'tipo_pregunta' => 'LIKERT',
            'es_obligatoria' => true,
        ]);

        // Primera respuesta
        $this->actingAs($this->alumno)->post(route('alumno.encuestas.guardar', $encuesta), [
            'respuestas' => [$p->pregunta_id => 4],
        ]);

        // Intento de segunda respuesta
        $response = $this->actingAs($this->alumno)->post(route('alumno.encuestas.guardar', $encuesta), [
            'respuestas' => [$p->pregunta_id => 5],
        ]);

        $response->assertRedirect(route('alumno.encuestas.index'));
        $response->assertSessionHas('info');
        $this->assertDatabaseCount('encuestas_respuestas', 1);
    }

    public function test_admin_can_view_survey_results_statistics(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Resultados Test',
            'slug' => 'resultados-test',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
        ]);

        $p = $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'Calidad percibida',
            'tipo_pregunta' => 'LIKERT',
            'es_obligatoria' => true,
            'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
        ]);

        $r = EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'fecha_respuesta' => now(),
        ]);

        EncuestaRespuestaDetalle::create([
            'respuesta_id' => $r->respuesta_id,
            'pregunta_id' => $p->pregunta_id,
            'valor_numerico' => 5,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.encuestas.resultados', $encuesta));
        $response->assertStatus(200);
        $response->assertSee('Resultados de Encuesta');
        $response->assertSee('Calidad percibida');
    }

    public function test_admin_can_edit_survey_with_hydrated_questions(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta Original',
            'slug' => 'encuesta-original',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_BORRADOR,
            'es_anonima' => true,
        ]);

        $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'Pregunta previa',
            'tipo_pregunta' => 'LIKERT',
            'es_obligatoria' => true,
            'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
        ]);

        // Ver formulario de edición
        $resEdit = $this->actingAs($this->admin)->get(route('admin.encuestas.edit', $encuesta));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Editar Encuesta: Encuesta Original');

        // Enviar actualización con preguntas
        $payload = [
            'titulo' => 'Encuesta Modificada',
            'tipo' => 'GENERAL',
            'estado' => 'PUBLICADA',
            'preguntas' => [
                [
                    'texto' => 'Pregunta actualizada 1',
                    'tipo' => 'LIKERT',
                    'es_obligatoria' => 1,
                ],
                [
                    'texto' => 'Pregunta agregada 2',
                    'tipo' => 'OPCION_MULTIPLE',
                    'es_obligatoria' => 0,
                    'opciones' => "Opción A\nOpción B",
                ],
            ],
        ];

        $resUpdate = $this->actingAs($this->admin)->put(route('admin.encuestas.update', $encuesta), $payload);
        $resUpdate->assertRedirect(route('admin.encuestas.index'));
        $resUpdate->assertSessionHas('status');

        $encuesta->refresh();
        $this->assertEquals('Encuesta Modificada', $encuesta->titulo);
        $this->assertEquals(Encuesta::ESTADO_PUBLICADA, $encuesta->estado);
        $this->assertDatabaseCount('encuestas_preguntas', 2);
        $this->assertDatabaseHas('encuestas_preguntas', [
            'texto_pregunta' => 'Pregunta actualizada 1',
        ]);
    }

    public function test_admin_can_view_multiple_choice_survey_results_properly_aggregated(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta Múltiple Opción',
            'slug' => 'encuesta-multiple-opcion',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
        ]);

        $opciones = [
            ['valor' => 'opt_1', 'etiqueta' => 'Taller de Gestión del Tiempo'],
            ['valor' => 'opt_2', 'etiqueta' => 'Taller de Manejo del Estrés'],
            ['valor' => 'opt_3', 'etiqueta' => 'Club de Meditación'],
        ];

        $p = $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => '¿Qué actividades te gustaría que se ofrecieran?',
            'tipo_pregunta' => EncuestaPregunta::TIPO_OPCION_MULTIPLE,
            'es_obligatoria' => true,
            'opciones' => $opciones,
        ]);

        // Participante 1 selecciona Taller de Gestión y Taller de Manejo
        $r1 = EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'fecha_respuesta' => now(),
        ]);
        EncuestaRespuestaDetalle::create([
            'respuesta_id' => $r1->respuesta_id,
            'pregunta_id' => $p->pregunta_id,
            'opciones_seleccionadas' => ['Taller de Gestión del Tiempo', 'Taller de Manejo del Estrés'],
        ]);

        // Participante 2 selecciona solo Taller de Manejo del Estrés
        $r2 = EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'fecha_respuesta' => now(),
        ]);
        EncuestaRespuestaDetalle::create([
            'respuesta_id' => $r2->respuesta_id,
            'pregunta_id' => $p->pregunta_id,
            'opciones_seleccionadas' => ['Taller de Manejo del Estrés'],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.encuestas.resultados', $encuesta));
        $response->assertStatus(200);
        $response->assertDontSee('Sin respuestas para esta pregunta');
        $response->assertSee('Taller de Gestión del Tiempo');
        $response->assertSee('Taller de Manejo del Estrés');
        $response->assertSee('Pregunta de opción múltiple');
    }

    public function test_admin_can_change_survey_state_via_status_transition(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta para Cerrar',
            'slug' => 'encuesta-para-cerrar',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.encuestas.toggle-estado', $encuesta), [
            'estado' => Encuesta::ESTADO_CERRADA,
        ]);

        $response->assertRedirect();
        $encuesta->refresh();
        $this->assertEquals(Encuesta::ESTADO_CERRADA, $encuesta->estado);
    }

    public function test_anonymous_survey_preserves_anonymity_while_blocking_duplicates(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta 100% Anónima',
            'slug' => 'encuesta-100-anonima',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
            'permite_multiples_respuestas' => false,
        ]);

        $p = $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'Opinión anónima',
            'tipo_pregunta' => 'LIKERT',
            'es_obligatoria' => true,
            'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
        ]);

        // Enviar respuesta por alumno
        $resSubmit = $this->actingAs($this->alumno)->post(route('alumno.encuestas.guardar', $encuesta), [
            'respuestas' => [$p->pregunta_id => 5],
        ]);
        $resSubmit->assertRedirect(route('alumno.encuestas.index'));

        // Verificar que en base de datos usuario_id es estrictamente NULL para garantizar anonimato
        $respuesta = EncuestaRespuesta::where('encuesta_id', $encuesta->encuesta_id)->first();
        $this->assertNotNull($respuesta);
        $this->assertNull($respuesta->usuario_id);
        $this->assertNotNull($respuesta->ip_hash);

        // Intentar responder nuevamente como el mismo alumno debe ser rechazado
        $resDuplicate = $this->actingAs($this->alumno)->post(route('alumno.encuestas.guardar', $encuesta), [
            'respuestas' => [$p->pregunta_id => 4],
        ]);
        $resDuplicate->assertRedirect(route('alumno.encuestas.index'));
        $resDuplicate->assertSessionHas('info');

        // La cantidad de respuestas debe seguir siendo exactamente 1
        $this->assertEquals(1, EncuestaRespuesta::where('encuesta_id', $encuesta->encuesta_id)->count());
    }

    public function test_identified_survey_shows_tab_for_student_responses_with_modals(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta Identificada con Trazabilidad',
            'slug' => 'encuesta-identificada-trazabilidad',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => false,
        ]);

        $p = $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => '¿Cómo valoras el equipamiento del aula?',
            'tipo_pregunta' => 'LIKERT',
            'es_obligatoria' => true,
            'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
        ]);

        // Registrar respuesta nominal del alumno
        $r = EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'usuario_id' => $this->alumno->usuario_id,
            'fecha_respuesta' => now(),
        ]);

        EncuestaRespuestaDetalle::create([
            'respuesta_id' => $r->respuesta_id,
            'pregunta_id' => $p->pregunta_id,
            'valor_numerico' => 5,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.encuestas.resultados', $encuesta));
        $response->assertStatus(200);

        // Debe contener las pestañas
        $response->assertSee('Resumen General y Métricas');
        $response->assertSee('Respuestas por Estudiante');

        // Debe mostrar datos del alumno y su matrícula
        $response->assertSee($this->alumno->nombre);
        $response->assertSee($this->estudiante->matricula);
        $response->assertSee('modal-resp-' . $r->respuesta_id);
    }

    public function test_anonymous_survey_does_not_show_student_tab(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta Completamente Anónima',
            'slug' => 'encuesta-completamente-anonima',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
        ]);

        $p = $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'Pregunta anónima',
            'tipo_pregunta' => 'LIKERT',
            'es_obligatoria' => true,
        ]);

        EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'usuario_id' => null,
            'fecha_respuesta' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.encuestas.resultados', $encuesta));
        $response->assertStatus(200);

        // No debe mostrar la pestaña nominal de estudiantes
        $response->assertDontSee('Respuestas por Estudiante');
    }

    public function test_privacy_notices_displayed_clearly_based_on_anonymity(): void
    {
        // 1. Encuesta Anónima
        $encuestaAnonima = Encuesta::create([
            'titulo' => 'Encuesta de Satisfacción Anónima',
            'slug' => 'encuesta-satisfaccion-anonima',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
        ]);
        $encuestaAnonima->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'P1',
            'tipo_pregunta' => 'TEXTO_LIBRE',
            'es_obligatoria' => false,
        ]);

        $resAnon = $this->actingAs($this->alumno)->get(route('alumno.encuestas.responder', $encuestaAnonima));
        $resAnon->assertStatus(200);
        $resAnon->assertSee('Participación Confidencial y 100% Anónima');
        $resAnon->assertSee('Tus respuestas en esta encuesta se guardan de forma totalmente disociada');

        // 2. Encuesta Identificada
        $encuestaIdentificada = Encuesta::create([
            'titulo' => 'Encuesta de Seguimiento Individual',
            'slug' => 'encuesta-seguimiento-individual',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => false,
        ]);
        $encuestaIdentificada->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'P1',
            'tipo_pregunta' => 'TEXTO_LIBRE',
            'es_obligatoria' => false,
        ]);

        $resIdent = $this->actingAs($this->alumno)->get(route('alumno.encuestas.responder', $encuestaIdentificada));
        $resIdent->assertStatus(200);
        $resIdent->assertSee('Aviso de Privacidad y Transparencia Institucional');
        $resIdent->assertSee('Tus respuestas se guardarán asociadas a tu cuenta de estudiante');
    }

    public function test_cannot_modify_structural_fields_if_survey_has_responses(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta con Respuestas Previas',
            'slug' => 'encuesta-con-respuestas-previas',
            'tipo' => 'GENERAL',
            'estado' => Encuesta::ESTADO_PUBLICADA,
            'es_anonima' => true,
            'permite_multiples_respuestas' => false,
        ]);

        $p = $encuesta->preguntas()->create([
            'orden' => 1,
            'texto_pregunta' => 'Pregunta inicial fija',
            'tipo_pregunta' => 'LIKERT',
            'es_obligatoria' => true,
        ]);

        EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'fecha_respuesta' => now(),
        ]);

        // Intentar cambiar titulo (permitido) pero cambiar tipo o es_anonima o preguntas
        $response = $this->actingAs($this->admin)->put(route('admin.encuestas.update', $encuesta), [
            'titulo' => 'Título Actualizado Permitido',
            'estado' => 'PUBLICADA',
            'tipo' => 'POST_CITA', // Intentar alterar
            'es_anonima' => 0,      // Intentar alterar
            'preguntas' => [        // Intentar borrar y reestructurar
                ['texto' => 'Pregunta intrusiva nueva', 'tipo' => 'TEXTO_LIBRE'],
            ],
        ]);

        $response->assertRedirect(route('admin.encuestas.index'));

        $encuesta->refresh();
        $this->assertEquals('Título Actualizado Permitido', $encuesta->titulo);
        $this->assertEquals('GENERAL', $encuesta->tipo); // Preservado
        $this->assertTrue($encuesta->es_anonima);         // Preservado
        $this->assertEquals(1, $encuesta->preguntas()->count());
        $this->assertEquals('Pregunta inicial fija', $encuesta->preguntas()->first()->texto_pregunta); // Pregunta intacta
    }
}

