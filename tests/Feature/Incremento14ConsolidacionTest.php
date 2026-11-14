<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\EvaluacionesClinicasCompletasSeeder;
use Database\Seeders\SandboxAcademicoConciliacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Incremento14ConsolidacionTest extends TestCase
{
    use RefreshDatabase;

    protected Rol $rolAlumno;
    protected Rol $rolDocente;
    protected Rol $rolAdmin;
    protected User $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Admin']);
        $this->rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['descripcion' => 'Docente']);
        $this->rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Alumno']);

        $programa = ProgramaEducativo::firstOrCreate(
            ['clave' => 'IID'],
            ['nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital', 'activo' => true]
        );

        $this->alumno = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Manuel Antonio',
            'apellido_paterno' => 'Díaz',
            'apellido_materno' => 'Hernández',
            'email' => 'alumno.inc14@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => 'ADMO230179',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'saldo_puntos' => 20,
        ]);
    }

    public function test_evaluaciones_clinicas_completas_seeder_publica_todos_los_instrumentos(): void
    {
        $this->seed(EvaluacionesClinicasCompletasSeeder::class);

        $codigos = ['GAD-7', 'PHQ-9', 'PSS-10', 'WHO-5'];
        foreach ($codigos as $codigo) {
            $instrumento = InstrumentoEvaluacion::where('codigo', $codigo)->first();
            $this->assertNotNull($instrumento, "Instrumento {$codigo} debe existir.");
            $this->assertTrue((bool) $instrumento->activo);

            $version = $instrumento->versiones()->where('version', '1.0.0')->first();
            $this->assertNotNull($version, "Versión 1.0.0 de {$codigo} debe existir.");
            $this->assertEquals('PUBLICADA', $version->estado);
            $this->assertGreaterThan(0, $version->preguntas()->count(), "Debe tener reactivos cargados.");
            $this->assertGreaterThan(0, $version->baremos()->count(), "Debe tener baremos configurados.");
            $this->assertGreaterThan(0, $version->escalas()->count(), "Debe tener opciones de respuesta.");
        }
    }

    public function test_inicio_de_evaluacion_clinica_requiere_consentimiento_informado_obligatorio(): void
    {
        $this->seed(EvaluacionesClinicasCompletasSeeder::class);
        $gad7 = InstrumentoEvaluacion::where('codigo', 'GAD-7')->first();

        // 1. Envío sin consentimiento debe fallar la validación
        $responseFallo = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.iniciar', $gad7->instrumento_id), []);
        $responseFallo->assertSessionHasErrors('consentimiento');

        // 2. Envío con consentimiento explícito aceptado inicia el intento y registra el consentimiento
        $responseExito = $this->actingAs($this->alumno)->post(route('alumno.evaluaciones.iniciar', $gad7->instrumento_id), [
            'consentimiento' => 1,
            'consentimiento_version' => 'v1.0-institucional',
        ]);

        $this->assertDatabaseHas('evaluaciones_intentos', [
            'usuario_id' => $this->alumno->usuario_id,
            'estado' => 'EN_PROGRESO',
        ]);

        $this->assertDatabaseHas('evaluaciones_consentimientos', [
            'usuario_id' => $this->alumno->usuario_id,
        ]);

        $intento = EvaluacionIntento::where('usuario_id', $this->alumno->usuario_id)->first();
        $responseExito->assertRedirect(route('alumno.evaluaciones.cuestionario', $intento->intento_id));
    }

    public function test_instrucciones_vista_presenta_texto_institucional_de_consentimiento_y_boton_comenzar(): void
    {
        $this->seed(EvaluacionesClinicasCompletasSeeder::class);
        $phq9 = InstrumentoEvaluacion::where('codigo', 'PHQ-9')->first();

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.instrucciones', $phq9->instrumento_id));

        $response->assertOk();
        $response->assertSee('Consentimiento Informado Institucional');
        $response->assertSee('Este instrumento es una herramienta de tamizaje preventivo y formativo de la UPEMOR');
        $response->assertSee('He leído y acepto participar voluntariamente.');
        $response->assertSee('Comenzar evaluación');
    }

    public function test_sandbox_academico_conciliacion_seeder_crea_oferta_materias_grupos_y_recompensas(): void
    {
        // Crear docente para el seeder
        $docente = User::create([
            'rol_id' => $this->rolDocente->rol_id,
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Domínguez',
            'email' => 'docente@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->seed(SandboxAcademicoConciliacionSeeder::class);

        // 1. Materias cuatrimestre 7 y 10
        $this->assertDatabaseHas('asignaturas', ['clave' => 'IID-701', 'cuatrimestre' => 7]);
        $this->assertDatabaseHas('asignaturas', ['clave' => 'IID-1001', 'cuatrimestre' => 10]);

        // 2. Grupos A y B
        $this->assertDatabaseHas('grupos', ['cuatrimestre' => 7, 'nombre' => 'A']);
        $this->assertDatabaseHas('grupos', ['cuatrimestre' => 7, 'nombre' => 'B']);
        $this->assertDatabaseHas('grupos', ['cuatrimestre' => 10, 'nombre' => 'A']);
        $this->assertDatabaseHas('grupos', ['cuatrimestre' => 10, 'nombre' => 'B']);

        // 3. Asignaciones docentes
        $this->assertDatabaseHas('docente_asignaciones', ['docente_id' => $docente->usuario_id, 'activo' => true]);

        // 4. Recompensas del catálogo y activadas
        $this->assertDatabaseHas('recompensas_catalogo', ['nombre' => 'Décima extra en práctica de laboratorio']);
        $this->assertDatabaseHas('recompensas_catalogo', ['nombre' => 'Pase de entrega tardía 24h']);
        $this->assertDatabaseHas('recompensas_activadas', ['estado' => 'ACTIVA', 'costo_puntos' => 50]);
        $this->assertDatabaseHas('recompensas_activadas', ['estado' => 'ACTIVA', 'costo_puntos' => 30]);
    }
}

