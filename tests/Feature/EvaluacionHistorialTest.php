<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\EscalaOpcion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\InstrumentoEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use Database\Seeders\EvaluacionDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluacionHistorialTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;
    private Estudiante $estudiante;
    private User $otroAlumno;
    private Estudiante $otroEstudiante;
    private User $docente;
    private User $admin;
    private InstrumentoEvaluacion $instrumentoDemo;
    private VersionInstrumento $versionDemo;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $programa = ProgramaEducativo::factory()->create(['clave' => 'ITI', 'activo' => true]);

        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'estudiante.historial@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023107777',
            'cuatrimestre' => 5,
            'grupo' => 'A',
            'saldo_puntos' => 0,
            'permite_visualizacion' => false,
        ]);

        $this->otroAlumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'otro.historial@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->otroEstudiante = Estudiante::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023106666',
            'cuatrimestre' => 5,
            'grupo' => 'B',
            'saldo_puntos' => 0,
            'permite_visualizacion' => false,
        ]);

        $this->docente = User::factory()->withRole($rolDocente)->create([
            'email' => 'profesor@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'EMP9999',
            'categoria' => 'Tiempo Completo',
            'cubiculo' => 'B-10',
        ]);

        $this->admin = User::factory()->withRole($rolAdmin)->create([
            'email' => 'admin@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->seed(EvaluacionDemoSeeder::class);

        $this->instrumentoDemo = InstrumentoEvaluacion::where('codigo', 'DEMO-HABITOS')->first();
        $this->versionDemo = $this->instrumentoDemo->versiones()->where('version', '1.0.0')->first();
    }

    public function test_active_student_can_view_evaluation_history_with_completed_and_in_progress_attempts(): void
    {
        // 1. Intento finalizado
        $intentoFinalizado = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 12,
            'nivel_resultado' => 'Hábitos consolidados',
            'fecha_inicio' => now()->subHours(2),
            'fecha_finalizacion' => now()->subHour(),
        ]);

        // 2. Intento en progreso
        $intentoEnProgreso = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'EN_PROGRESO',
            'fecha_inicio' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.historial'));

        $response->assertOk();
        $response->assertSee('Mi Historial de Evaluaciones');
        $response->assertSee($this->instrumentoDemo->nombre);
        $response->assertSee('Finalizado');
        $response->assertSee('En progreso');
        $response->assertSee('12 pts');
        $response->assertSee('Hábitos consolidados');
        $response->assertSee('Ver detalle &rarr;', false);
        $response->assertSee('Continuar &rarr;', false);
    }

    public function test_history_displays_empty_state_when_student_has_no_evaluations(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.historial'));

        $response->assertOk();
        $response->assertSee('Aún no tienes evaluaciones registradas');
        $response->assertSee('Explorar cuestionarios disponibles &rarr;', false);
    }

    public function test_history_displays_reapplication_availability_and_days_remaining(): void
    {
        // Intento finalizado hace 1 hora (con 7 días de espera, debe faltar 7 días)
        EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 10,
            'nivel_resultado' => 'Hábitos intermedios',
            'fecha_inicio' => now()->subHours(2),
            'fecha_finalizacion' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.historial'));

        $response->assertOk();
        $response->assertSee('Disponible en 7 días');

        // Intento finalizado hace 8 días (ya disponible para reaplicación)
        EvaluacionIntento::where('usuario_id', $this->alumno->usuario_id)->update([
            'fecha_finalizacion' => now()->subDays(8),
        ]);

        $response2 = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.historial'));
        $response2->assertOk();
        $response2->assertSee('Disponible ahora');
    }

    public function test_student_cannot_view_another_students_attempt_detail(): void
    {
        // Intento perteneciente al otro alumno
        $intentoAjeno = EvaluacionIntento::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 14,
            'nivel_resultado' => 'Hábitos consolidados',
            'fecha_inicio' => now()->subHour(),
            'fecha_finalizacion' => now(),
        ]);

        // Responder preguntas para el intento ajeno
        $preguntas = $this->versionDemo->preguntas()->orderBy('numero_reactivo')->get();
        $escala = $this->versionDemo->escalas()->first();
        foreach ($preguntas as $preg) {
            EvaluacionRespuesta::create([
                'intento_id' => $intentoAjeno->intento_id,
                'pregunta_id' => $preg->pregunta_id,
                'escala_id' => $escala->escala_id,
                'puntos_obtenidos' => 0,
            ]);
        }

        // El alumno intenta ver el resultado ajeno
        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.finalizado', $intentoAjeno->intento_id));

        $response->assertForbidden();
    }

    public function test_teacher_and_admin_cannot_access_evaluaciones_history_or_individual_results(): void
    {
        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $this->versionDemo->version_id,
            'estado' => 'FINALIZADO',
            'puntuacion_total' => 10,
            'nivel_resultado' => 'Hábitos intermedios',
            'fecha_inicio' => now()->subHour(),
            'fecha_finalizacion' => now(),
        ]);

        // Docente no puede acceder al historial ni al resultado individual
        $docenteHistorial = $this->actingAs($this->docente)->get(route('alumno.evaluaciones.historial'));
        $docenteHistorial->assertForbidden();

        $docenteResultado = $this->actingAs($this->docente)->get(route('alumno.evaluaciones.finalizado', $intento->intento_id));
        $docenteResultado->assertForbidden();

        // Admin no puede acceder al historial ni al resultado individual
        $adminHistorial = $this->actingAs($this->admin)->get(route('alumno.evaluaciones.historial'));
        $adminHistorial->assertForbidden();

        $adminResultado = $this->actingAs($this->admin)->get(route('alumno.evaluaciones.finalizado', $intento->intento_id));
        $adminResultado->assertForbidden();
    }
}

