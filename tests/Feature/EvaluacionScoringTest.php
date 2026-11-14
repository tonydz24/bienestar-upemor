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
use App\Services\Evaluaciones\EvaluacionScoringService;
use Database\Seeders\EvaluacionDemoSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluacionScoringTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;
    private Estudiante $estudiante;
    private InstrumentoEvaluacion $instrumentoDemo;
    private VersionInstrumento $versionDemo;
    private EvaluacionScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);
        $programa = ProgramaEducativo::factory()->create(['clave' => 'ITI', 'activo' => true]);

        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'estudiante.scoring@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023108888',
            'cuatrimestre' => 5,
            'grupo' => 'A',
            'saldo_puntos' => 0,
            'permite_visualizacion' => false,
        ]);

        $this->seed(EvaluacionDemoSeeder::class);

        $this->instrumentoDemo = InstrumentoEvaluacion::where('codigo', 'DEMO-HABITOS')->first();
        $this->versionDemo = $this->instrumentoDemo->versiones()->where('version', '1.0.0')->first();

        $this->scoringService = app(EvaluacionScoringService::class);
    }

    public function test_standard_item_score_equals_scale_value(): void
    {
        $pregunta = $this->versionDemo->preguntas()->first();
        $escala = $this->versionDemo->escalas()->where('valor', 2)->first();

        $puntos = $this->scoringService->calcularPuntosReactivo($pregunta, $escala, $this->versionDemo);

        $this->assertEquals(2, $puntos);
    }

    public function test_inverted_item_score_is_inverted_based_on_scale_extremes(): void
    {
        // Crear un fixture técnico con un reactivo inverso (sin activar instrumentos clínicos)
        $preguntaInversa = PreguntaEvaluacion::create([
            'version_id' => $this->versionDemo->version_id,
            'numero_reactivo' => 99,
            'enunciado' => 'Fixture técnico reactivo inverso',
            'es_inverso' => true,
            'orden' => 99,
            'activo' => true,
        ]);

        $escalaMin = $this->versionDemo->escalas()->where('valor', 0)->first();
        $escalaMedia = $this->versionDemo->escalas()->where('valor', 1)->first();
        $escalaMax = $this->versionDemo->escalas()->where('valor', 3)->first();

        // Escala 0 a 3:
        // Opción 0 debe transformarse en 3
        // Opción 1 debe transformarse en 2
        // Opción 3 debe transformarse en 0
        $this->assertEquals(3, $this->scoringService->calcularPuntosReactivo($preguntaInversa, $escalaMin, $this->versionDemo));
        $this->assertEquals(2, $this->scoringService->calcularPuntosReactivo($preguntaInversa, $escalaMedia, $this->versionDemo));
        $this->assertEquals(0, $this->scoringService->calcularPuntosReactivo($preguntaInversa, $escalaMax, $this->versionDemo));
    }

    public function test_scoring_complete_demo_habitos_attempt_matches_correct_baremo_levels(): void
    {
        // 1. Caso de prueba: Puntuación baja (todas las opciones con valor 0 = 0 puntos -> Hábitos en desarrollo)
        $intentoBajo = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $preguntas = $this->versionDemo->preguntas()->orderBy('numero_reactivo')->get();
        $escala0 = $this->versionDemo->escalas()->where('valor', 0)->first();
        foreach ($preguntas as $preg) {
            EvaluacionRespuesta::create([
                'intento_id' => $intentoBajo->intento_id,
                'pregunta_id' => $preg->pregunta_id,
                'escala_id' => $escala0->escala_id,
                'puntos_obtenidos' => 0,
            ]);
        }

        $resultadoBajo = $this->scoringService->calcularPuntuacionIntento($intentoBajo);
        $this->assertEquals(0, $resultadoBajo['puntuacion_total']);
        $this->assertEquals(15, $resultadoBajo['puntuacion_maxima']);
        $this->assertEquals(0, $resultadoBajo['porcentaje']);
        $this->assertEquals('Hábitos en desarrollo', $resultadoBajo['nivel_resultado']);
        $this->assertFalse($resultadoBajo['tiene_alerta_critica']);

        // 2. Caso de prueba: Puntuación intermedia (opción con valor 2 en 4 preguntas = 8 puntos -> Hábitos intermedios)
        $intentoMedio = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $escala2 = $this->versionDemo->escalas()->where('valor', 2)->first();
        foreach ($preguntas->take(4) as $preg) {
            EvaluacionRespuesta::create([
                'intento_id' => $intentoMedio->intento_id,
                'pregunta_id' => $preg->pregunta_id,
                'escala_id' => $escala2->escala_id,
                'puntos_obtenidos' => 2,
            ]);
        }
        // Última pregunta con valor 0
        EvaluacionRespuesta::create([
            'intento_id' => $intentoMedio->intento_id,
            'pregunta_id' => $preguntas[4]->pregunta_id,
            'escala_id' => $escala0->escala_id,
            'puntos_obtenidos' => 0,
        ]);

        $resultadoMedio = $this->scoringService->calcularPuntuacionIntento($intentoMedio);
        $this->assertEquals(8, $resultadoMedio['puntuacion_total']);
        $this->assertEquals('Hábitos intermedios', $resultadoMedio['nivel_resultado']);

        // 3. Caso de prueba: Puntuación alta (todas las opciones con valor 3 = 15 puntos -> Hábitos consolidados)
        $intentoAlto = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $escala3 = $this->versionDemo->escalas()->where('valor', 3)->first();
        foreach ($preguntas as $preg) {
            EvaluacionRespuesta::create([
                'intento_id' => $intentoAlto->intento_id,
                'pregunta_id' => $preg->pregunta_id,
                'escala_id' => $escala3->escala_id,
                'puntos_obtenidos' => 3,
            ]);
        }

        $resultadoAlto = $this->scoringService->calcularPuntuacionIntento($intentoAlto);
        $this->assertEquals(15, $resultadoAlto['puntuacion_total']);
        $this->assertEquals(100, $resultadoAlto['porcentaje']);
        $this->assertEquals('Hábitos consolidados', $resultadoAlto['nivel_resultado']);
    }

    public function test_incomplete_attempt_throws_domain_exception(): void
    {
        $intentoIncompleto = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $preguntas = $this->versionDemo->preguntas()->orderBy('numero_reactivo')->get();
        $escala = $this->versionDemo->escalas()->first();

        // Responder solo 2 de 5 preguntas
        EvaluacionRespuesta::create([
            'intento_id' => $intentoIncompleto->intento_id,
            'pregunta_id' => $preguntas[0]->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => 0,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('No se puede calificar un intento incompleto');

        $this->scoringService->calcularPuntuacionIntento($intentoIncompleto);
    }

    public function test_finalizar_y_calificar_is_idempotent_and_preserves_original_finalization(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now()->subMinutes(10),
        ]);

        $preguntas = $this->versionDemo->preguntas()->orderBy('numero_reactivo')->get();
        $escala = $this->versionDemo->escalas()->where('valor', 2)->first();

        foreach ($preguntas as $preg) {
            EvaluacionRespuesta::create([
                'intento_id' => $intento->intento_id,
                'pregunta_id' => $preg->pregunta_id,
                'escala_id' => $escala->escala_id,
                'puntos_obtenidos' => 2,
            ]);
        }

        // Primera llamada
        $finalizado1 = $this->scoringService->finalizarYCalificarIntento($intento, '127.0.0.1');
        $this->assertEquals('FINALIZADO', $finalizado1->estado);
        $this->assertEquals(10, $finalizado1->puntuacion_total);
        $fechaOrig = $finalizado1->fecha_finalizacion;

        // Segunda llamada repetida
        $finalizado2 = $this->scoringService->finalizarYCalificarIntento($finalizado1, '127.0.0.1');
        $this->assertEquals('FINALIZADO', $finalizado2->estado);
        $this->assertEquals(10, $finalizado2->puntuacion_total);
        $this->assertEquals($fechaOrig->toDateTimeString(), $finalizado2->fecha_finalizacion->toDateTimeString());
    }

    public function test_critical_item_triggers_alert_flag_correctly(): void
    {
        // Fixture técnico de pregunta crítica (para evaluar mecanismo sin publicar instrumentos clínicos)
        $preguntaCritica = PreguntaEvaluacion::create([
            'version_id' => $this->versionDemo->version_id,
            'numero_reactivo' => 98,
            'enunciado' => 'Fixture técnico pregunta crítica',
            'es_critico' => true,
            'orden' => 98,
            'activo' => true,
        ]);

        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $preguntas = $this->versionDemo->preguntas()->where('activo', true)->get();
        $escala0 = $this->versionDemo->escalas()->where('valor', 0)->first();
        $escala1 = $this->versionDemo->escalas()->where('valor', 1)->first();

        foreach ($preguntas as $preg) {
            // Asignar valor 1 al reactivo crítico, valor 0 a los demás
            $esc = ($preg->pregunta_id === $preguntaCritica->pregunta_id) ? $escala1 : $escala0;
            EvaluacionRespuesta::create([
                'intento_id' => $intento->intento_id,
                'pregunta_id' => $preg->pregunta_id,
                'escala_id' => $esc->escala_id,
                'puntos_obtenidos' => $esc->valor,
            ]);
        }

        $resultado = $this->scoringService->calcularPuntuacionIntento($intento);
        $this->assertTrue($resultado['tiene_alerta_critica']);
    }
}

