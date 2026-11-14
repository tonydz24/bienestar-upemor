<?php

namespace Tests\Feature;

use App\Models\BitacoraEmocional;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\InstrumentoEvaluacion;
use App\Models\LoginAcceso;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Services\Reportes\ReporteInstitucionalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesInstitucionalesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $alumno;
    protected User $docente;
    protected ProgramaEducativo $pe;
    protected Estudiante $estudiante;
    protected Emocion $emocion;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 1, 'nombre' => 'Administrador']);
        $rolDocente = Rol::create(['rol_id' => 2, 'nombre' => 'Docente']);
        $rolAlumno = Rol::create(['rol_id' => 3, 'nombre' => 'Alumno']);

        $this->pe = ProgramaEducativo::create([
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

        $this->docente = User::factory()->create([
            'rol_id' => $rolDocente->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'docente@upemor.edu.mx',
        ]);

        $this->alumno = User::factory()->create([
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'alumno@upemor.edu.mx',
        ]);

        $this->estudiante = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->pe->programa_id,
            'matricula' => 'ITI2026001',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'saldo_puntos' => 250,
        ]);

        $this->emocion = Emocion::create([
            'nombre' => 'Calma',
            'slug' => 'calma',
            'color_hex' => '#06b6d4',
            'activo' => true,
        ]);

        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocion->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 4,
            'descripcion' => 'Sesión de estudio productiva',
        ]);
    }

    public function test_admin_can_access_reportes_center(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reportes.index'));
        $response->assertStatus(200);
        $response->assertSee('Centro de Reportes Institucionales');
        $response->assertSee('6 Reportes Académicos Oficiales');
    }

    public function test_admin_can_view_all_six_reports(): void
    {
        $tipos = [
            'emociones-registradas',
            'emociones-por-programa',
            'historial-alumno',
            'premios-canjeados',
            'sesiones-por-dia',
            'resultados-tests',
        ];

        foreach ($tipos as $tipo) {
            $response = $this->actingAs($this->admin)->get(route('admin.reportes.show', $tipo));
            $response->assertStatus(200);
        }
    }

    public function test_admin_can_export_pdf_for_all_six_reports(): void
    {
        $tipos = [
            'emociones-registradas',
            'emociones-por-programa',
            'historial-alumno',
            'premios-canjeados',
            'sesiones-por-dia',
            'resultados-tests',
        ];

        foreach ($tipos as $tipo) {
            $response = $this->actingAs($this->admin)->get(route('admin.reportes.exportar-pdf', [
                'tipo' => $tipo,
                'estudiante_id' => $this->estudiante->estudiante_id,
            ]));
            $response->assertStatus(200);
            $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        }
    }

    public function test_admin_can_export_excel_for_all_six_reports(): void
    {
        $tipos = [
            'emociones-registradas',
            'emociones-por-programa',
            'historial-alumno',
            'premios-canjeados',
            'sesiones-por-dia',
            'resultados-tests',
        ];

        foreach ($tipos as $tipo) {
            $response = $this->actingAs($this->admin)->get(route('admin.reportes.exportar-excel', [
                'tipo' => $tipo,
                'estudiante_id' => $this->estudiante->estudiante_id,
            ]));
            $response->assertStatus(200);
            $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        }
    }

    public function test_emociones_report_guarantees_six_emotions_and_calma_without_records(): void
    {
        // Crear las otras 5 emociones institucionales
        Emocion::create(['nombre' => 'Alegría', 'slug' => 'alegria', 'color_hex' => '#f59e0b', 'activo' => true]);
        Emocion::create(['nombre' => 'Ansiedad', 'slug' => 'ansiedad', 'color_hex' => '#f97316', 'activo' => true]);
        Emocion::create(['nombre' => 'Estrés', 'slug' => 'estres', 'color_hex' => '#8b5cf6', 'activo' => true]);
        Emocion::create(['nombre' => 'Tristeza', 'slug' => 'tristeza', 'color_hex' => '#3b82f6', 'activo' => true]);
        Emocion::create(['nombre' => 'Enojo', 'slug' => 'enojo', 'color_hex' => '#ef4444', 'activo' => true]);

        // Eliminar bitácoras para que Calma tenga 0 registros
        BitacoraEmocional::query()->delete();

        $service = app(ReporteInstitucionalService::class);
        $resultado = $service->reporteEmocionesRegistradas([]);

        $this->assertEquals(6, $resultado['emociones_totales']);
        $this->assertEquals(0, $resultado['total_general']);

        $calma = collect($resultado['datos'])->firstWhere('emocion_nombre', 'Calma');
        $this->assertNotNull($calma);
        $this->assertEquals(0, $calma->total);
        $this->assertEquals(0.0, $calma->porcentaje);
        $this->assertNull($calma->intensidad_promedio);
        $this->assertEquals('#10b981', $calma->codigo_color);

        // Vista muestra "Sin registros"
        $response = $this->actingAs($this->admin)->get(route('admin.reportes.show', 'emociones-registradas'));
        $response->assertStatus(200);
        $response->assertSee('Sin registros');
        $response->assertSee('#10b981');
    }

    public function test_sesiones_report_metrics_and_temporal_series(): void
    {
        LoginAcceso::query()->delete();

        // 3 accesos en día 1 por usuario A
        LoginAcceso::create([
            'usuario_id' => $this->alumno->usuario_id,
            'email' => $this->alumno->email,
            'nombre_usuario' => $this->alumno->nombre,
            'rol_id' => 3,
            'rol_nombre' => 'Alumno',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'fecha_acceso' => now()->subDays(2),
        ]);
        LoginAcceso::create([
            'usuario_id' => $this->alumno->usuario_id,
            'email' => $this->alumno->email,
            'nombre_usuario' => $this->alumno->nombre,
            'rol_id' => 3,
            'rol_nombre' => 'Alumno',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'fecha_acceso' => now()->subDays(2),
        ]);

        // 1 acceso en día 2 por usuario A y 1 por usuario B
        LoginAcceso::create([
            'usuario_id' => $this->alumno->usuario_id,
            'email' => $this->alumno->email,
            'nombre_usuario' => $this->alumno->nombre,
            'rol_id' => 3,
            'rol_nombre' => 'Alumno',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'fecha_acceso' => now()->subDay(),
        ]);
        LoginAcceso::create([
            'usuario_id' => $this->admin->usuario_id,
            'email' => $this->admin->email,
            'nombre_usuario' => $this->admin->nombre,
            'rol_id' => 1,
            'rol_nombre' => 'Administrador',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'fecha_acceso' => now()->subDay(),
        ]);

        $service = app(ReporteInstitucionalService::class);
        $rep = $service->reporteSesionesPorDia([]);

        $this->assertEquals(4, $rep['total_general']);
        $this->assertEquals(2, $rep['total_usuarios_unicos']); // Alumno y Admin desduplicados
        $this->assertEquals(2, $rep['dias_con_actividad']);
        $this->assertEquals(2.0, $rep['promedio_diario']); // 4 accesos / 2 días = 2.0
        $this->assertCount(2, $rep['serie_temporal']);

        // Vista muestra nota metodológica y métricas
        $response = $this->actingAs($this->admin)->get(route('admin.reportes.show', 'sesiones-por-dia'));
        $response->assertStatus(200);
        $response->assertSee('Usuarios Únicos del Periodo');
        $response->assertSee('Días con Actividad');
        $response->assertSee('Promedio Diario');
        $response->assertSee('Nota metodológica sobre Usuarios Únicos');
    }

    public function test_excel_export_sanitizes_formula_injection(): void
    {
        $service = app(ReporteInstitucionalService::class);
        $streamed = $service->exportarExcel('test_sec', ['Columna'], [
            ['=1+1'],
            ['+SUM(A1:A10)'],
            ['-2*3'],
            ['@malicious_payload'],
            ['Texto Normal'],
        ]);

        ob_start();
        $streamed->sendContent();
        $output = ob_get_clean();

        // Verificar que los caracteres peligrosos fueron neutralizados con comilla simple
        $this->assertStringContainsString("'=1+1", $output);
        $this->assertStringContainsString("'+SUM", $output);
        $this->assertStringContainsString("'-2*3", $output);
        $this->assertStringContainsString("'@malicious_payload", $output);
        $this->assertStringContainsString('Texto Normal', $output);
    }

    public function test_non_admin_cannot_access_reportes(): void
    {
        // Alumno bloqueado
        $resAlumno = $this->actingAs($this->alumno)->get(route('admin.reportes.index'));
        $resAlumno->assertStatus(403);

        // Docente bloqueado
        $resDocente = $this->actingAs($this->docente)->get(route('admin.reportes.index'));
        $resDocente->assertStatus(403);

        // Invitado bloqueado con 403
        $resGuest = $this->get(route('admin.reportes.index'));
        $this->assertContains($resGuest->status(), [403, 302]);
    }

    public function test_emociones_report_has_neutral_labels_and_average_intensity(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reportes.show', 'emociones-registradas'));
        $response->assertStatus(200);
        $response->assertSee('Intensidad promedio (escala 1–5)');
        $response->assertDontSee('Desafiante / Negativa');
        $response->assertDontSee('Agradable / Positiva');
    }

    public function test_sesiones_report_distinguishes_total_logins_and_unique_users(): void
    {
        LoginAcceso::create([
            'usuario_id' => $this->alumno->usuario_id,
            'email' => $this->alumno->email,
            'nombre_usuario' => $this->alumno->nombre,
            'rol_id' => 3,
            'rol_nombre' => 'Alumno',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'fecha_acceso' => now(),
        ]);

        LoginAcceso::create([
            'usuario_id' => $this->alumno->usuario_id,
            'email' => $this->alumno->email,
            'nombre_usuario' => $this->alumno->nombre,
            'rol_id' => 3,
            'rol_nombre' => 'Alumno',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'fecha_acceso' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reportes.show', 'sesiones-por-dia'));
        $response->assertStatus(200);
        $response->assertSee('Total de Accesos');
        $response->assertSee('Usuarios Únicos');
    }
}
