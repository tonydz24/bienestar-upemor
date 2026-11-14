<?php

namespace Tests\Feature;

use App\Models\EscalaOpcion;
use App\Models\Estudiante;
use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\InstrumentoEvaluacion;
use App\Models\PreguntaEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use Database\Seeders\EvaluacionDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluacionCuestionarioTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;
    private Estudiante $estudiante;
    private User $otroAlumno;
    private Estudiante $otroEstudiante;
    private InstrumentoEvaluacion $instrumentoDemo;
    private VersionInstrumento $versionDemo;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);

        $programa = ProgramaEducativo::factory()->create([
            'clave' => 'ITI',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);

        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'estudiante.demo@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023101111',
            'cuatrimestre' => 5,
            'grupo' => 'A',
            'saldo_puntos' => 0,
            'permite_visualizacion' => false,
        ]);

        $this->otroAlumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'otro.estudiante@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->otroEstudiante = Estudiante::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023102222',
            'cuatrimestre' => 5,
            'grupo' => 'B',
            'saldo_puntos' => 0,
            'permite_visualizacion' => false,
        ]);

        // Cargar el instrumento ficticio demostrativo de hábitos de estudio
        $this->seed(EvaluacionDemoSeeder::class);

        $this->instrumentoDemo = InstrumentoEvaluacion::where('codigo', 'DEMO-HABITOS')->first();
        $this->versionDemo = $this->instrumentoDemo->versiones()->where('version', '1.0.0')->first();
    }

    public function test_active_student_can_start_attempt_with_explicit_consent(): void
    {
        $response = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.iniciar', $this->instrumentoDemo->instrumento_id), [
            'consentimiento' => '1',
            'consentimiento_version' => 'v1.0-provisional',
        ]);

        $intento = EvaluacionIntento::where('usuario_id', $this->alumno->usuario_id)->first();

        $this->assertNotNull($intento);
        $this->assertEquals('EN_PROGRESO', $intento->estado);
        $this->assertEquals($this->versionDemo->version_id, $intento->version_id);

        // Verificar registro de consentimiento informado
        $this->assertDatabaseHas('evaluaciones_consentimientos', [
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'intento_id' => $intento->intento_id,
            'consentimiento_version' => 'v1.0-provisional',
        ]);

        $response->assertRedirect(route('alumno.evaluaciones.cuestionario', $intento->intento_id));
    }

    public function test_starting_attempt_without_consent_is_rejected(): void
    {
        $response = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.iniciar', $this->instrumentoDemo->instrumento_id), [
            'consentimiento_version' => 'v1.0-provisional',
            // Sin campo consentimiento
        ]);

        $response->assertSessionHasErrors('consentimiento');
        $this->assertDatabaseCount('evaluaciones_intentos', 0);
        $this->assertDatabaseCount('evaluaciones_consentimientos', 0);
    }

    public function test_duplicate_start_reuses_existing_in_progress_attempt_without_duplication(): void
    {
        // Primer inicio de intento
        $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.iniciar', $this->instrumentoDemo->instrumento_id), [
            'consentimiento' => '1',
        ]);

        $intentoOriginal = EvaluacionIntento::where('usuario_id', $this->alumno->usuario_id)->first();
        $this->assertNotNull($intentoOriginal);

        // Segundo intento de inicio (doble click o reenvío)
        $response2 = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.iniciar', $this->instrumentoDemo->instrumento_id), [
            'consentimiento' => '1',
        ]);

        // Debe redirigir al intento existente sin crear un segundo registro
        $this->assertDatabaseCount('evaluaciones_intentos', 1);
        $response2->assertRedirect(route('alumno.evaluaciones.cuestionario', $intentoOriginal->intento_id));
    }

    public function test_active_student_can_view_interactive_questionnaire(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.cuestionario', $intento->intento_id));

        $response->assertOk();
        $response->assertSee($this->instrumentoDemo->nombre);
        $response->assertSee('v1.0.0');
        $response->assertSee('¿Planificas un horario fijo');
        $response->assertSee('Casi nunca');
        $response->assertSee('Casi siempre');
        $response->assertSee('Autoguardado activo');
    }

    public function test_student_can_save_individual_answer_via_json_endpoint(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();
        $escala = $this->versionDemo->escalas()->where('valor', 2)->first();

        $response = $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'total_preguntas' => 5,
            'total_respondidas' => 1,
            'porcentaje' => 20,
            'todas_respondidas' => false,
        ]);

        // Puntos obtenidos provienen del valor del modelo en base de datos (2 puntos)
        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => 2,
        ]);
    }

    public function test_saving_answer_is_idempotent_and_updates_previous_selection(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();
        $escala1 = $this->versionDemo->escalas()->where('valor', 1)->first();
        $escala3 = $this->versionDemo->escalas()->where('valor', 3)->first();

        // Primera selección
        $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala1->escala_id,
        ]);

        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala1->escala_id,
            'puntos_obtenidos' => 1,
        ]);

        // Rectificación a la opción de 3 puntos
        $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala3->escala_id,
        ]);

        $this->assertDatabaseCount('evaluaciones_respuestas', 1);
        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala3->escala_id,
            'puntos_obtenidos' => 3,
        ]);
    }

    public function test_saving_option_from_another_version_is_rejected_with_422(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();

        // Crear una versión externa con sus propias opciones
        $otraVersion = VersionInstrumento::factory()->create();
        $otraEscala = EscalaOpcion::create([
            'version_id' => $otraVersion->version_id,
            'valor' => 99,
            'etiqueta' => 'Opción ajena',
        ]);

        $response = $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $otraEscala->escala_id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('evaluaciones_respuestas', 0);
    }

    public function test_student_cannot_save_answer_for_another_students_attempt(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();
        $escala = $this->versionDemo->escalas()->first();

        // El alumno ajeno intenta responder en el intento de $this->alumno
        $response = $this->actingAs($this->otroAlumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('evaluaciones_respuestas', 0);
    }

    public function test_student_can_resume_in_progress_attempt_with_preloaded_answers(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $preguntas = $this->versionDemo->preguntas()->orderBy('numero_reactivo')->get();
        $escala = $this->versionDemo->escalas()->first();

        // Responder preguntas 1 y 2
        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $preguntas[0]->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => $escala->valor,
        ]);
        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $preguntas[1]->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => $escala->valor,
        ]);

        // Cargar vista de cuestionario
        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.cuestionario', $intento->intento_id));

        $response->assertOk();
        // Progreso 2 de 5 = 40%
        $response->assertSee('40% completado');
        // Debe indicar paso 3 (primera pregunta pendiente)
        $response->assertSee('Pregunta <span id="numPasoDisplay">3</span> de 5', false);
    }

    public function test_progress_endpoint_returns_json_metrics(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();
        $escala = $this->versionDemo->escalas()->first();

        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => $escala->valor,
        ]);

        $response = $this->actingAs($this->alumno)->getJson(route('alumno.evaluaciones.progreso', $intento->intento_id));

        $response->assertOk();
        $response->assertJson([
            'intento_id' => $intento->intento_id,
            'estado' => 'EN_PROGRESO',
            'total_preguntas' => 5,
            'total_respondidas' => 1,
            'porcentaje' => 20,
            'todas_respondidas' => false,
        ]);
    }

    public function test_cannot_finalize_attempt_with_incomplete_answers(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        // Solo responder 1 de las 5 preguntas
        $pregunta = $this->versionDemo->preguntas()->first();
        $escala = $this->versionDemo->escalas()->first();
        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => $escala->valor,
        ]);

        $response = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.finalizar', $intento->intento_id));

        // Debe rechazar la finalización y mantener el intento en progreso
        $intentoRefrescado = EvaluacionIntento::find($intento->intento_id);
        $this->assertEquals('EN_PROGRESO', $intentoRefrescado->estado);
        $this->assertNull($intentoRefrescado->fecha_finalizacion);
    }

    public function test_can_finalize_attempt_when_all_questions_are_answered(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $preguntas = $this->versionDemo->preguntas()->orderBy('numero_reactivo')->get();
        $escala = $this->versionDemo->escalas()->where('valor', 2)->first();

        // Responder todas las 5 preguntas con valor 2 (total = 10 puntos)
        foreach ($preguntas as $preg) {
            EvaluacionRespuesta::create([
                'intento_id' => $intento->intento_id,
                'pregunta_id' => $preg->pregunta_id,
                'escala_id' => $escala->escala_id,
                'puntos_obtenidos' => 2,
            ]);
        }

        $response = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.finalizar', $intento->intento_id));

        $intentoFinalizado = EvaluacionIntento::find($intento->intento_id);
        $this->assertEquals('FINALIZADO', $intentoFinalizado->estado);
        $this->assertNotNull($intentoFinalizado->fecha_finalizacion);
        $this->assertEquals(10, $intentoFinalizado->puntuacion_total);
        $this->assertEquals('Hábitos intermedios', $intentoFinalizado->nivel_resultado);
        $this->assertFalse($intentoFinalizado->tiene_alerta_critica);

        $response->assertRedirect(route('alumno.evaluaciones.finalizado', $intento->intento_id));
    }

    public function test_cannot_modify_or_add_answers_to_finalized_attempt(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now()->subMinutes(15),
            'fecha_finalizacion' => now(),
            'puntuacion_total' => 10,
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();
        $escala = $this->versionDemo->escalas()->first();

        // Intentar guardar una respuesta en un intento finalizado debe ser denegado por política (403)
        $response = $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
        ]);

        $response->assertForbidden();
    }

    public function test_finalized_screen_displays_secure_storage_notice(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now()->subMinutes(10),
            'fecha_finalizacion' => now(),
            'puntuacion_total' => 12,
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.finalizado', $intento->intento_id));

        $response->assertOk();
        $response->assertSee('¡Evaluación completada con éxito!');
        $response->assertSee('Detalle del registro');
        $response->assertSee($this->instrumentoDemo->nombre);
        $response->assertSee('Volver al catálogo de evaluaciones');
    }

    public function test_guest_and_unauthorized_roles_are_blocked_from_cuestionario(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        // Invitado no autenticado
        $guestResponse = $this->get(route('alumno.evaluaciones.cuestionario', $intento->intento_id));
        $guestResponse->assertRedirect(route('login'));

        // Docente
        $rolDocente = Rol::where('rol_id', 2)->first();
        $docente = User::factory()->withRole($rolDocente)->create(['estado' => 'ACTIVO']);
        $docenteResponse = $this->actingAs($docente)->get(route('alumno.evaluaciones.cuestionario', $intento->intento_id));
        $docenteResponse->assertForbidden();
    }

    public function test_rapid_successive_answers_on_same_question_are_safely_serialized(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();
        $escalas = $this->versionDemo->escalas()->orderBy('valor')->get();

        // Envío sucesivo rápido de 3 opciones distintas
        foreach ($escalas->take(3) as $escala) {
            $response = $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
                'pregunta_id' => $pregunta->pregunta_id,
                'escala_id' => $escala->escala_id,
            ]);
            $response->assertOk();
        }

        // Debe mantenerse exactamente un solo registro para la combinación intento + pregunta
        $this->assertDatabaseCount('evaluaciones_respuestas', 1);
        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escalas[2]->escala_id,
            'puntos_obtenidos' => $escalas[2]->valor,
        ]);
    }

    public function test_repeated_finalization_call_is_handled_gracefully_without_altering_original_score(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now()->subMinutes(5),
        ]);

        $preguntas = $this->versionDemo->preguntas()->orderBy('numero_reactivo')->get();
        $escala = $this->versionDemo->escalas()->where('valor', 1)->first();

        foreach ($preguntas as $preg) {
            EvaluacionRespuesta::create([
                'intento_id' => $intento->intento_id,
                'pregunta_id' => $preg->pregunta_id,
                'escala_id' => $escala->escala_id,
                'puntos_obtenidos' => 1,
            ]);
        }

        // Primera finalización
        $resp1 = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.finalizar', $intento->intento_id));
        $resp1->assertRedirect(route('alumno.evaluaciones.finalizado', $intento->intento_id));

        $intento1 = EvaluacionIntento::find($intento->intento_id);
        $fechaFinalizacion = $intento1->fecha_finalizacion;
        $this->assertEquals(5, $intento1->puntuacion_total);

        // Segunda llamada repetida a finalizar (doble clic o reenvío tardío)
        $resp2 = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.finalizar', $intento->intento_id));
        $resp2->assertRedirect(route('alumno.evaluaciones.finalizado', $intento->intento_id));

        $intento2 = EvaluacionIntento::find($intento->intento_id);
        $this->assertEquals(5, $intento2->puntuacion_total);
        $this->assertEquals($fechaFinalizacion->toDateTimeString(), $intento2->fecha_finalizacion->toDateTimeString());
    }

    public function test_option_selection_saves_immediately_to_server_without_clicking_next(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();
        $escala = $this->versionDemo->escalas()->where('valor', 2)->first();

        // 1. Simular la acción disparada por el cambio en el input radio (sin acción de navegación)
        $response = $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
        ]);

        // 2. Comprobar que responde de inmediato con éxito
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
        ]);

        // 3. Comprobar que la respuesta ya se encuentra registrada en la base de datos de manera persistente
        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => 2,
        ]);

        // 4. El intento debe permanecer en estado EN_PROGRESO sin depender de clic en Siguiente
        $intentoRefrescado = EvaluacionIntento::find($intento->intento_id);
        $this->assertEquals('EN_PROGRESO', $intentoRefrescado->estado);
        $this->assertNull($intentoRefrescado->fecha_finalizacion);
    }

    public function test_resuming_incomplete_attempt_preloads_previous_answers_and_positions_at_first_unanswered_question(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now()->subMinutes(15),
        ]);

        $preguntas = $this->versionDemo->preguntas()->orderBy('numero_reactivo')->get();
        $escala = $this->versionDemo->escalas()->first();

        // El alumno respondió solo las dos primeras preguntas (reactivo 1 y 2)
        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $preguntas[0]->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => $escala->valor,
        ]);
        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $preguntas[1]->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => $escala->valor,
        ]);

        // El alumno abandona la sesión y vuelve a ingresar
        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.cuestionario', $intento->intento_id));

        $response->assertOk();

        // Verifica que la vista reciba el paso 2 (índice 0-based, correspondiente a la pregunta 3)
        $response->assertViewHas('primerPasoPendiente', 2);

        // Verifica que las respuestas previas estén precargadas en los datos compartidos
        $response->assertViewHas('respuestasGuardadas', function ($respuestas) use ($preguntas, $escala) {
            return isset($respuestas[$preguntas[0]->pregunta_id]) &&
                   $respuestas[$preguntas[0]->pregunta_id] === $escala->escala_id &&
                   isset($respuestas[$preguntas[1]->pregunta_id]) &&
                   $respuestas[$preguntas[1]->pregunta_id] === $escala->escala_id;
        });

        // Verifica que en el HTML renderizado el contador indique '3 de 5'
        $response->assertSee('Pregunta <span id="numPasoDisplay">3</span> de 5', false);

        // Comprueba que la tarjeta del paso 3 no esté oculta con d-none inicial,
        // mientras que las preguntas 1 y 2 sí tienen clase d-none
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/class="cuestionario-step\s*"\s*id="step-card-2"/', $content);
        $this->assertMatchesRegularExpression('/class="cuestionario-step\s+d-none"\s*id="step-card-0"/', $content);
        $this->assertMatchesRegularExpression('/class="cuestionario-step\s+d-none"\s*id="step-card-1"/', $content);
    }

    public function test_out_of_order_stale_answer_with_older_client_timestamp_is_safely_ignored_by_backend(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $pregunta = $this->versionDemo->preguntas()->first();
        $escalas = $this->versionDemo->escalas()->orderBy('valor')->get();

        // 1. El estudiante seleccionó una opción más reciente en t = 2000 (Escala 2)
        $resp1 = $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escalas[2]->escala_id,
            'cliente_timestamp' => 2000,
        ]);
        $resp1->assertOk();

        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escalas[2]->escala_id,
            'cliente_timestamp' => 2000,
        ]);

        // 2. Llega con retraso por jitter de red una petición anterior de t = 1000 (Escala 0)
        $respStale = $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escalas[0]->escala_id,
            'cliente_timestamp' => 1000,
        ]);

        $respStale->assertOk();
        $respStale->assertJson([
            'success' => true,
            'stale' => true,
        ]);

        // 3. Comprobar que en base de datos SIGUE persistiendo la Escala 2 (no fue sobreescrita por la obsoleta)
        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escalas[2]->escala_id,
            'cliente_timestamp' => 2000,
        ]);

        // 4. Llega una nueva selección válida en t = 3000 (Escala 3)
        $respNew = $this->actingAs($this->alumno)->postJson(route('alumno.evaluaciones.guardar-respuesta', $intento->intento_id), [
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escalas[3]->escala_id,
            'cliente_timestamp' => 3000,
        ]);

        $respNew->assertOk();
        $this->assertNull($respNew->json('stale'));

        // 5. Comprobar que la base de datos se actualizó a la Escala 3
        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escalas[3]->escala_id,
            'cliente_timestamp' => 3000,
        ]);
    }
}

