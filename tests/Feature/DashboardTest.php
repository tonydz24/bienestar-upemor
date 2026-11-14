<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Rol $adminRole;
    private Rol $docenteRole;
    private Rol $alumnoRole;
    private ProgramaEducativo $programa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $this->docenteRole = Rol::factory()->docente()->create(['rol_id' => 2]);
        $this->alumnoRole = Rol::factory()->alumno()->create(['rol_id' => 3]);

        $this->programa = ProgramaEducativo::factory()->create([
            'clave' => 'IFI',
            'nombre' => 'Ingeniería Financiera',
            'activo' => true,
        ]);
    }

    public function test_admin_can_access_admin_dashboard_and_sees_real_metrics(): void
    {
        $admin = User::factory()->withRole($this->adminRole)->create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Administrador',
            'estado' => 'ACTIVO',
        ]);

        // Crear alumnos en diversos estados
        User::factory()->withRole($this->alumnoRole)->count(3)->create(['estado' => 'PENDIENTE']);
        User::factory()->withRole($this->alumnoRole)->count(4)->create(['estado' => 'ACTIVO']);
        User::factory()->withRole($this->alumnoRole)->count(2)->create(['estado' => 'RECHAZADO']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('¡Bienvenido, Roberto!');
        $response->assertSee('Pendientes');
        $response->assertSee('3'); // 3 pendientes
        $response->assertSee('Alumnos activos');
        $response->assertSee('4'); // 4 activos
        $response->assertSee('Rechazadas');
        $response->assertSee('2'); // 2 rechazados
        $response->assertSee(route('admin.solicitudes.index'));
    }

    public function test_alumno_can_access_alumno_dashboard_and_sees_personalized_greeting(): void
    {
        $alumno = User::factory()->withRole($this->alumnoRole)->create([
            'nombre' => 'Arantxa',
            'apellido_paterno' => 'Miranda',
            'estado' => 'ACTIVO',
        ]);

        Estudiante::create([
            'usuario_id' => $alumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => '20231005',
            'saldo_puntos' => 45,
            'permite_visualizacion' => false,
        ]);

        $response = $this->actingAs($alumno)->get(route('alumno.dashboard'));

        $response->assertOk();
        $response->assertSee('¡Hola, Arantxa!');
        $response->assertSee('Este es tu espacio para cuidar tu bienestar emocional.');
        $response->assertSee('Estado actual');
        $response->assertSee('Sin evaluar');
        $response->assertSee('45 pts');
        $response->assertSee(route('alumno.evaluaciones'));
        $response->assertSee('Próxima cita');
        $response->assertSee('Recomendado para ti');
        $response->assertSee('Reduce la ansiedad en minutos');
    }

    public function test_alumno_dashboard_handles_missing_bitacora_tables_gracefully(): void
    {
        $alumno = User::factory()->withRole($this->alumnoRole)->create([
            'nombre' => 'Mateo',
            'estado' => 'ACTIVO',
        ]);

        Estudiante::create([
            'usuario_id' => $alumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => '20231099',
            'saldo_puntos' => 20,
        ]);

        // Simula el estado en que la tabla aún no se ha creado en la base de datos
        \Illuminate\Support\Facades\Schema::dropIfExists('bitacoras_emocionales');

        $response = $this->actingAs($alumno)->get(route('alumno.dashboard'));

        $response->assertOk();
        $response->assertSee('¡Hola, Mateo!');
        $response->assertSee('Módulo no disponible');
        $response->assertSee('Próximamente disponible');
    }

    public function test_alumno_can_access_evaluaciones_view_marked_as_proximamente(): void
    {
        $this->seed(\Database\Seeders\EvaluacionesInstrumentosSeeder::class);

        $alumno = User::factory()->withRole($this->alumnoRole)->create([
            'estado' => 'ACTIVO',
        ]);

        $response = $this->actingAs($alumno)->get(route('alumno.evaluaciones'));

        $response->assertOk();
        $response->assertSee('Evaluaciones disponibles');
        $response->assertSee('GAD-7');
        $response->assertSee('PHQ-9');
        $response->assertSee('PSS-10');
        $response->assertSee('WHO-5');
        $response->assertSee('Próximamente');
    }

    public function test_docente_can_access_docente_dashboard_and_sees_confidentiality_notice(): void
    {
        $docente = User::factory()->withRole($this->docenteRole)->create([
            'nombre' => 'Sandra',
            'apellido_paterno' => 'León',
            'estado' => 'ACTIVO',
        ]);

        $response = $this->actingAs($docente)->get(route('docente.dashboard'));

        $response->assertOk();
        $response->assertSee('¡Bienvenido/a, Sandra!');
        $response->assertSee('Principio de confidencialidad y consentimiento explícito');
        $response->assertSee('no otorga acceso automático');
        $response->assertDontSee('expediente clínico individual');
    }

    public function test_each_role_cannot_access_other_role_dashboard(): void
    {
        $admin = User::factory()->withRole($this->adminRole)->create(['estado' => 'ACTIVO']);
        $docente = User::factory()->withRole($this->docenteRole)->create(['estado' => 'ACTIVO']);
        $alumno = User::factory()->withRole($this->alumnoRole)->create(['estado' => 'ACTIVO']);

        // Alumno no puede ver admin ni docente
        $this->actingAs($alumno)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($alumno)->get(route('docente.dashboard'))->assertForbidden();

        // Docente no puede ver admin ni alumno
        $this->actingAs($docente)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($docente)->get(route('alumno.dashboard'))->assertForbidden();

        // Admin no puede ver docente ni alumno
        $this->actingAs($admin)->get(route('docente.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('alumno.dashboard'))->assertForbidden();
    }

    public function test_unauthenticated_guests_are_redirected_to_login(): void
    {
        $routes = [
            'admin.dashboard',
            'docente.dashboard',
            'alumno.dashboard',
            'alumno.evaluaciones',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $response->assertRedirectToRoute('login');
        }
    }

    public function test_inactive_and_pending_users_are_kicked_from_dashboards(): void
    {
        $pendingUser = User::factory()->withRole($this->alumnoRole)->create(['estado' => 'PENDIENTE']);
        $inactiveUser = User::factory()->withRole($this->alumnoRole)->create(['estado' => 'INACTIVO']);
        $rejectedUser = User::factory()->withRole($this->alumnoRole)->create(['estado' => 'RECHAZADO']);

        $this->actingAs($pendingUser)->get(route('alumno.dashboard'))->assertRedirectToRoute('login');
        $this->actingAs($inactiveUser)->get(route('alumno.dashboard'))->assertRedirectToRoute('login');
        $this->actingAs($rejectedUser)->get(route('alumno.dashboard'))->assertRedirectToRoute('login');
    }

    public function test_admin_dashboard_renders_successfully_when_active_period_exists_with_real_schema(): void
    {
        $admin = User::factory()->withRole($this->adminRole)->create([
            'nombre' => 'Arantxa',
            'estado' => 'ACTIVO',
        ]);

        \App\Models\PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => now()->subMonth()->toDateString(),
            'fecha_fin' => now()->addMonth()->toDateString(),
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('2026-3');
        $response->assertSee('Septiembre - Diciembre 2026');
    }

    public function test_admin_dashboard_renders_gracefully_when_no_active_period_exists(): void
    {
        $admin = User::factory()->withRole($this->adminRole)->create([
            'nombre' => 'Arantxa',
            'estado' => 'ACTIVO',
        ]);

        // Sin periodos creados o todos concluidos
        \App\Models\PeriodoAcademico::query()->delete();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Sin periodo activo');
        $response->assertSee('Configurar en periodos');
    }
}
