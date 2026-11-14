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
use Database\Seeders\EvaluacionesInstrumentosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluacionModelTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;
    private Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $programa = ProgramaEducativo::factory()->create([
            'clave' => 'IID',
            'nombre' => 'Ingeniería en Tecnologías de la Información',
            'activo' => true,
        ]);

        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'estudiante.eval@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023108888',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 0,
            'permite_visualizacion' => false,
        ]);
    }

    public function test_seeder_populates_four_standard_instruments_with_initial_versions(): void
    {
        $this->seed(EvaluacionesInstrumentosSeeder::class);

        $this->assertDatabaseCount('evaluaciones_instrumentos', 4);
        $this->assertDatabaseCount('evaluaciones_versiones', 4);

        $this->assertDatabaseHas('evaluaciones_instrumentos', ['codigo' => 'GAD-7']);
        $this->assertDatabaseHas('evaluaciones_instrumentos', ['codigo' => 'PHQ-9']);
        $this->assertDatabaseHas('evaluaciones_instrumentos', ['codigo' => 'PSS-10']);
        $this->assertDatabaseHas('evaluaciones_instrumentos', ['codigo' => 'WHO-5']);

        $gad7 = InstrumentoEvaluacion::where('codigo', 'GAD-7')->first();
        $this->assertNotNull($gad7);
        $this->assertEquals('Escala de Ansiedad Generalizada', $gad7->nombre);
        $this->assertEquals('Robert L. Spitzer, Kurt Kroenke, Janet B. W. Williams, Bernd Löwe', $gad7->autor);

        $version = $gad7->versiones()->first();
        $this->assertNotNull($version);
        $this->assertEquals('1.0.0', $version->version);
        $this->assertEquals('BORRADOR', $version->estado);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(EvaluacionesInstrumentosSeeder::class);
        $this->seed(EvaluacionesInstrumentosSeeder::class);

        $this->assertDatabaseCount('evaluaciones_instrumentos', 4);
        $this->assertDatabaseCount('evaluaciones_versiones', 4);
    }

    public function test_instrumento_has_versions_and_active_version_relationships(): void
    {
        $instrumento = InstrumentoEvaluacion::factory()->create(['codigo' => 'TEST-01']);

        $versionV1 = VersionInstrumento::factory()->create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0.0',
            'es_vigente' => false,
            'estado' => 'OBSOLETA',
        ]);

        $versionV2 = VersionInstrumento::factory()->create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '2.0.0',
            'es_vigente' => true,
            'estado' => 'PUBLICADA',
        ]);

        $this->assertCount(2, $instrumento->versiones);
        $this->assertEquals($versionV2->version_id, $instrumento->versionVigente->version_id);
    }

    public function test_version_instrumento_has_questions_scales_and_baremos_relationships(): void
    {
        $version = VersionInstrumento::factory()->create();

        $escala1 = EscalaOpcion::create([
            'version_id' => $version->version_id,
            'valor' => 0,
            'etiqueta' => 'Nunca',
            'orden' => 1,
        ]);

        $escala2 = EscalaOpcion::create([
            'version_id' => $version->version_id,
            'valor' => 1,
            'etiqueta' => 'A veces',
            'orden' => 2,
        ]);

        $pregunta1 = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => '¿Te has sentido tranquilo?',
            'orden' => 1,
            'activo' => true,
        ]);

        $baremo1 = BaremoRegla::create([
            'version_id' => $version->version_id,
            'puntuacion_min' => 0,
            'puntuacion_max' => 5,
            'nivel' => 'Nivel adecuado',
            'color_hex' => '#10b981',
            'mensaje_retroalimentacion' => 'Puntaje favorable',
            'sugiere_orientacion' => false,
            'orden' => 1,
        ]);

        $this->assertCount(2, $version->escalas);
        $this->assertCount(1, $version->preguntas);
        $this->assertCount(1, $version->baremos);
    }

    public function test_version_unique_constraint_prevents_duplicate_version_for_same_instrument(): void
    {
        $instrumento = InstrumentoEvaluacion::factory()->create();

        VersionInstrumento::factory()->create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0.0',
        ]);

        $this->expectException(QueryException::class);
        VersionInstrumento::factory()->create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0.0',
        ]);
    }

    public function test_question_unique_constraint_prevents_duplicate_item_number_in_same_version(): void
    {
        $version = VersionInstrumento::factory()->create();

        PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Reactivo 1 inicial',
        ]);

        $this->expectException(QueryException::class);
        PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Reactivo 1 duplicado',
        ]);
    }

    public function test_scale_unique_constraint_prevents_duplicate_value_in_same_version(): void
    {
        $version = VersionInstrumento::factory()->create();

        EscalaOpcion::create([
            'version_id' => $version->version_id,
            'valor' => 0,
            'etiqueta' => 'Nunca',
        ]);

        $this->expectException(QueryException::class);
        EscalaOpcion::create([
            'version_id' => $version->version_id,
            'valor' => 0,
            'etiqueta' => 'Jamás',
        ]);
    }

    public function test_attempt_is_tied_to_specific_version_of_instrument(): void
    {
        $instrumento = InstrumentoEvaluacion::factory()->create(['nombre' => 'Escala de Ansiedad']);
        $version = VersionInstrumento::factory()->create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0.0',
        ]);

        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
            'ip_registro' => '127.0.0.1',
        ]);

        $this->assertEquals($version->version_id, $intento->version->version_id);
        $this->assertEquals($instrumento->instrumento_id, $intento->instrumento->instrumento_id);
        $this->assertTrue($intento->estaEnProgreso());
        $this->assertFalse($intento->estaFinalizado());
    }

    public function test_foreign_key_restrict_prevents_deleting_version_with_active_student_attempts(): void
    {
        $version = VersionInstrumento::factory()->create();

        EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 8,
            'nivel_resultado' => 'Moderado',
            'fecha_inicio' => now()->subMinutes(10),
            'fecha_finalizacion' => now(),
        ]);

        $this->expectException(QueryException::class);
        $version->delete();
    }

    public function test_foreign_key_restrict_prevents_deleting_student_with_active_attempts(): void
    {
        $version = VersionInstrumento::factory()->create();

        EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now(),
        ]);

        $this->expectException(QueryException::class);
        $this->estudiante->delete();
    }

    public function test_versioning_isolation_allows_new_version_without_altering_previous_attempts(): void
    {
        $instrumento = InstrumentoEvaluacion::factory()->create(['codigo' => 'TEST-VER']);

        $version1 = VersionInstrumento::factory()->create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0.0',
            'es_vigente' => false,
        ]);

        $preguntaV1 = PreguntaEvaluacion::create([
            'version_id' => $version1->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Pregunta original v1',
        ]);

        $intentoV1 = EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version1->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 3,
            'nivel_resultado' => 'Leve',
            'fecha_inicio' => now()->subDay(),
            'fecha_finalizacion' => now()->subDay(),
        ]);

        // Se publica la versión 2.0 con otra redacción de preguntas
        $version2 = VersionInstrumento::factory()->create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '2.0.0',
            'es_vigente' => true,
        ]);

        $preguntaV2 = PreguntaEvaluacion::create([
            'version_id' => $version2->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Pregunta modificada v2',
        ]);

        // Verificamos que el intento histórico de la versión 1 permanece inalterado
        $intentoRecuperado = EvaluacionIntento::find($intentoV1->intento_id);
        $this->assertEquals('1.0.0', $intentoRecuperado->version->version);
        $this->assertEquals('Pregunta original v1', $intentoRecuperado->version->preguntas->first()->enunciado);
        $this->assertCount(1, $version2->preguntas);
        $this->assertEquals('Pregunta modificada v2', $version2->preguntas->first()->enunciado);
    }

    public function test_answers_are_recorded_per_question_in_attempt(): void
    {
        $version = VersionInstrumento::factory()->create();

        $escala = EscalaOpcion::create([
            'version_id' => $version->version_id,
            'valor' => 2,
            'etiqueta' => 'Frecuentemente',
            'orden' => 1,
        ]);

        $pregunta = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => '¿Te sientes tenso?',
        ]);

        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->estudiante->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now(),
        ]);

        $respuesta = EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => 2,
        ]);

        $this->assertDatabaseHas('evaluaciones_respuestas', [
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'puntos_obtenidos' => 2,
        ]);

        // La restricción unique(['intento_id', 'pregunta_id']) debe rechazar duplicados
        $this->expectException(QueryException::class);
        EvaluacionRespuesta::create([
            'intento_id' => $intento->intento_id,
            'pregunta_id' => $pregunta->pregunta_id,
            'escala_id' => $escala->escala_id,
            'puntos_obtenidos' => 3,
        ]);
    }
}
