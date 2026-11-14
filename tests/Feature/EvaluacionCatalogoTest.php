<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\EscalaOpcion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\InstrumentoEvaluacion;
use App\Models\PreguntaEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use Database\Seeders\EvaluacionesInstrumentosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EvaluacionCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;
    private Estudiante $estudiante;
    private User $docente;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $programa = ProgramaEducativo::factory()->create([
            'clave' => 'IID',
            'activo' => true,
        ]);

        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'alumno.catalogo@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023107777',
            'cuatrimestre' => 4,
            'permite_visualizacion' => false,
        ]);

        $this->docente = User::factory()->withRole($rolDocente)->create([
            'email' => 'docente.catalogo@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'DOC3003',
            'puesto' => 'Profesor de Tiempo Completo',
            'activo' => true,
        ]);

        $this->admin = User::factory()->withRole($rolAdmin)->create([
            'email' => 'admin.catalogo@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
    }

    public function test_active_student_can_view_catalog_with_published_instruments(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'GAD-7',
            'nombre' => 'Escala de Ansiedad Generalizada',
            'siglas' => 'GAD-7',
            'categoria' => 'Ansiedad',
            'descripcion' => 'Prueba breve para identificar síntomas de ansiedad.',
            'activo' => true,
            'orden' => 1,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0.0',
            'instrucciones' => 'Responde con base en las últimas dos semanas.',
            'marco_temporal' => 'Últimas 2 semanas',
            'duracion_estimada_min' => 4,
            'puntos_recompensa' => 10,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
        ]);

        PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => '¿Te has sentido nervioso o intranquilo?',
            'orden' => 1,
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones'));

        $response->assertOk();
        $response->assertSee('Evaluaciones disponibles');
        $response->assertSee('GAD-7');
        $response->assertSee('Disponible');
        $response->assertSee(route('alumno.evaluaciones.instrucciones', $instrumento->instrumento_id));
    }

    public function test_catalog_shows_draft_instruments_as_in_preparation(): void
    {
        $this->seed(EvaluacionesInstrumentosSeeder::class);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones'));

        $response->assertOk();
        $response->assertSee('Evaluaciones disponibles');
        $response->assertSee('GAD-7');
        $response->assertSee('PHQ-9');
        $response->assertSee('PSS-10');
        $response->assertSee('WHO-5');
        $response->assertSee('Próximamente');
        $response->assertSee('En preparación (Próximamente)');
    }

    public function test_catalog_displays_elegant_empty_state_when_no_instruments_exist(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones'));

        $response->assertOk();
        $response->assertSee('No hay evaluaciones registradas en este momento');
    }

    public function test_catalog_handles_missing_tables_gracefully_without_500_error(): void
    {
        Schema::dropIfExists('evaluaciones_respuestas');
        Schema::dropIfExists('evaluaciones_intentos');
        Schema::dropIfExists('evaluaciones_baremos_reglas');
        Schema::dropIfExists('evaluaciones_preguntas');
        Schema::dropIfExists('evaluaciones_escalas_opciones');
        Schema::dropIfExists('evaluaciones_versiones');
        Schema::dropIfExists('evaluaciones_instrumentos');

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones'));

        $response->assertOk();
        $response->assertSee('Módulo en etapa de preparación metodológica');
        $response->assertSee('GAD-7');
        $response->assertSee('Próximamente');
    }

    public function test_guest_and_unauthorized_roles_are_forbidden_from_catalog(): void
    {
        $this->get(route('alumno.evaluaciones'))->assertRedirectToRoute('login');

        $this->actingAs($this->docente)->get(route('alumno.evaluaciones'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('alumno.evaluaciones'))->assertForbidden();

        $this->alumno->update(['estado' => 'INACTIVO']);
        $this->actingAs($this->alumno)->get(route('alumno.evaluaciones'))->assertRedirectToRoute('login');
    }

    public function test_student_can_view_instructions_for_published_instrument(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'GAD-7',
            'nombre' => 'Escala de Ansiedad Generalizada',
            'categoria' => 'Ansiedad',
            'descripcion' => 'Identificación de síntomas de ansiedad en las últimas semanas.',
            'autor' => 'Robert L. Spitzer et al.',
            'anio_publicacion' => 2006,
            'activo' => true,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0.0',
            'instrucciones' => 'Durante las últimas 2 semanas, indica la frecuencia del síntoma.',
            'marco_temporal' => 'Últimas 2 semanas',
            'duracion_estimada_min' => 4,
            'puntos_recompensa' => 10,
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
        ]);

        PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => '¿Sensación de temor o preocupación constante?',
        ]);

        EscalaOpcion::create([
            'version_id' => $version->version_id,
            'valor' => 0,
            'etiqueta' => 'Nunca',
            'orden' => 1,
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.instrucciones', $instrumento->instrumento_id));

        $response->assertOk();
        $response->assertSee('Instrucciones: GAD-7');
        $response->assertSee('Escala de Ansiedad Generalizada');
        $response->assertSee('Versión 1.0.0 (Vigente)');
        $response->assertSee('Durante las últimas 2 semanas, indica la frecuencia del síntoma.');
        $response->assertSee('Nunca');
        $response->assertSee('Aviso ético, orientador y confidencialidad');
        $response->assertSee('checkConsentimiento');
        $response->assertSee('Comenzar evaluación');
    }

    public function test_direct_url_access_to_draft_or_obsolete_version_instructions_is_blocked_with_404(): void
    {
        // Instrumento que solo tiene versión en BORRADOR (como en el seeder inicial)
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'PHQ-9',
            'nombre' => 'Cuestionario de Salud del Paciente',
            'categoria' => 'Estado de ánimo',
            'activo' => true,
        ]);

        VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => '1.0.0',
            'instrucciones' => 'Instrucciones en borrador.',
            'estado' => 'BORRADOR',
            'es_vigente' => true,
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.instrucciones', $instrumento->instrumento_id));

        $response->assertNotFound();
    }

    public function test_backend_immutability_prevents_modifying_or_deleting_questions_in_published_version(): void
    {
        $version = VersionInstrumento::factory()->create([
            'estado' => 'BORRADOR',
        ]);

        // Mientras es BORRADOR, sí se pueden agregar preguntas
        $pregunta = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Pregunta inicial en borrador',
        ]);

        // Se publica la versión formalmente
        $version->update(['estado' => 'PUBLICADA']);

        // 1. Intentar modificar el enunciado en una versión publicada debe lanzar excepción de dominio
        $this->expectException(\DomainException::class);
        $pregunta->update(['enunciado' => 'Intento de cambio en versión publicada']);
    }

    public function test_backend_immutability_prevents_deleting_questions_in_published_version(): void
    {
        $version = VersionInstrumento::factory()->create(['estado' => 'BORRADOR']);
        $pregunta = PreguntaEvaluacion::create([
            'version_id' => $version->version_id,
            'numero_reactivo' => 1,
            'enunciado' => 'Pregunta fija',
        ]);
        $version->update(['estado' => 'PUBLICADA']);

        $this->expectException(\DomainException::class);
        $pregunta->delete();
    }

    public function test_backend_immutability_prevents_altering_core_fields_of_published_version(): void
    {
        $version = VersionInstrumento::factory()->create([
            'estado' => 'PUBLICADA',
            'instrucciones' => 'Instrucciones publicadas.',
        ]);

        // Intentar alterar instrucciones en la versión publicada debe lanzar excepción
        $this->expectException(\DomainException::class);
        $version->update(['instrucciones' => 'Intento de cambio de consigna']);
    }

    public function test_finalized_evaluation_displays_clean_integer_days_remaining_without_decimals(): void
    {
        $this->seed(EvaluacionesInstrumentosSeeder::class);
        $instrumento = InstrumentoEvaluacion::where('codigo', 'GAD-7')->first();
        $instrumento->update(['dias_espera_reaplicacion' => 7]);

        $version = $instrumento->versiones()->where('estado', 'BORRADOR')->first();
        // Publicar la versión para que sea evaluable
        $version->update(['estado' => 'PUBLICADA']);

        // Simular intento finalizado hace 10 minutos (7 días de espera)
        EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $version->version_id,
            'estado' => 'FINALIZADO',
            'fecha_inicio' => now()->subMinutes(20),
            'fecha_finalizacion' => now()->subMinutes(10),
            'puntuacion_total' => 5,
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones'));

        $response->assertOk();
        $response->assertSee('Disponible en 7 días');
        // Asegurarse de que no aparezcan números decimales con 'd' o 'días'
        $this->assertDoesNotMatchRegularExpression('/Disponible en \d+\.\d+/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+d/', $response->getContent());

        // Simular intento finalizado hace 6 días y 2 horas (debe restar 1 día restante)
        EvaluacionIntento::where('usuario_id', $this->alumno->usuario_id)->update([
            'fecha_finalizacion' => now()->subDays(6)->subHours(2),
        ]);

        $response2 = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones'));
        $response2->assertOk();
        $response2->assertSee('Disponible en 1 día');
        $this->assertDoesNotMatchRegularExpression('/Disponible en \d+\.\d+/', $response2->getContent());
    }
}

