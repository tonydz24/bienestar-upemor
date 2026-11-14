<?php

namespace Tests\Feature;

use App\Models\BitacoraEmocional;
use App\Models\Docente;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\EmocionesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BitacoraTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;
    private User $otroAlumno;
    private User $docente;
    private User $admin;
    private Emocion $emocionAlegria;
    private Emocion $emocionTristeza;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $rolAdmin = Rol::factory()->administrador()->create(['rol_id' => 1]);
        $rolDocente = Rol::factory()->docente()->create(['rol_id' => 2]);
        $rolAlumno = Rol::factory()->alumno()->create(['rol_id' => 3]);

        // 2. Programa educativo
        $programa = ProgramaEducativo::factory()->create([
            'clave' => 'IFI',
            'nombre' => 'Ingeniería Financiera',
            'activo' => true,
        ]);

        // 3. Usuarios
        $this->alumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'alumno.bitacora@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023100101',
            'cuatrimestre' => 4,
            'grupo' => 'A',
            'permite_visualizacion' => false,
        ]);

        $this->otroAlumno = User::factory()->withRole($rolAlumno)->create([
            'email' => 'otro.alumno@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'programa_id' => $programa->programa_id,
            'matricula' => '2023100202',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'permite_visualizacion' => false,
        ]);

        $this->docente = User::factory()->withRole($rolDocente)->create([
            'email' => 'docente@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'DOC1001',
            'puesto' => 'Profesor de Tiempo Completo',
            'activo' => true,
        ]);

        $this->admin = User::factory()->withRole($rolAdmin)->create([
            'email' => 'admin@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        // 4. Catálogo de emociones
        $this->seed(EmocionesSeeder::class);
        $this->emocionAlegria = Emocion::where('slug', 'alegria')->first();
        $this->emocionTristeza = Emocion::where('slug', 'tristeza')->first();
    }

    public function test_active_alumno_can_access_bitacora_module(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.index'));

        $response->assertOk();
        $response->assertSee('Mi bitácora emocional');
        $response->assertSee('Registrar emoción de hoy');
        $response->assertSee('Alegría');
        $response->assertSee('Calma');
        $response->assertSee('Ansiedad');
    }

    public function test_inactive_and_pending_alumnos_cannot_access_bitacora(): void
    {
        $this->alumno->update(['estado' => 'INACTIVO']);
        $this->actingAs($this->alumno)->get(route('alumno.bitacora.index'))
            ->assertRedirectToRoute('login');

        $this->alumno->update(['estado' => 'PENDIENTE']);
        $this->actingAs($this->alumno)->get(route('alumno.bitacora.index'))
            ->assertRedirectToRoute('login');
    }

    public function test_docente_and_admin_are_forbidden_from_student_bitacora(): void
    {
        // El docente no tiene acceso automático a los registros psicológicos privados
        $this->actingAs($this->docente)->get(route('alumno.bitacora.index'))
            ->assertForbidden();

        // El administrador institucional tampoco tiene acceso indiscriminado
        $this->actingAs($this->admin)->get(route('alumno.bitacora.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('alumno.bitacora.index'))
            ->assertRedirectToRoute('login');
    }

    public function test_alumno_can_create_valid_emotional_record(): void
    {
        $payload = [
            'fecha' => now()->toDateString(),
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'intensidad' => 4,
            'descripcion' => 'Aprobé mi examen de cálculo integral con excelente calificación',
            'observaciones' => 'Me preparé toda la semana y sentí que valió la pena el esfuerzo.',
        ];

        $response = $this->actingAs($this->alumno)->post(route('alumno.bitacora.store'), $payload);

        $response->assertRedirectToRoute('alumno.bitacora.index');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bitacoras_emocionales', [
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 4,
            'descripcion' => 'Aprobé mi examen de cálculo integral con excelente calificación',
        ]);
    }

    public function test_future_dates_are_rejected(): void
    {
        $payload = [
            'fecha' => now()->addDay()->toDateString(),
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'intensidad' => 3,
        ];

        $response = $this->actingAs($this->alumno)->post(route('alumno.bitacora.store'), $payload);

        $response->assertSessionHasErrors('fecha');
        $this->assertDatabaseEmpty('bitacoras_emocionales');
    }

    public function test_intensity_must_be_between_one_and_five(): void
    {
        // Intensidad 0 -> falla
        $payload0 = [
            'fecha' => now()->toDateString(),
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'intensidad' => 0,
        ];
        $this->actingAs($this->alumno)->post(route('alumno.bitacora.store'), $payload0)
            ->assertSessionHasErrors('intensidad');

        // Intensidad 6 -> falla
        $payload6 = [
            'fecha' => now()->toDateString(),
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'intensidad' => 6,
        ];
        $this->actingAs($this->alumno)->post(route('alumno.bitacora.store'), $payload6)
            ->assertSessionHasErrors('intensidad');
    }

    public function test_duplicate_record_on_same_date_for_same_student_is_rejected(): void
    {
        $fecha = now()->toDateString();

        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => $fecha,
            'intensidad' => 3,
            'descripcion' => 'Primer registro del día',
        ]);

        $duplicatePayload = [
            'fecha' => $fecha,
            'emocion_id' => $this->emocionTristeza->emocion_id,
            'intensidad' => 2,
            'descripcion' => 'Intento de segundo registro',
        ];

        $response = $this->actingAs($this->alumno)->post(route('alumno.bitacora.store'), $duplicatePayload);

        $response->assertSessionHasErrors('fecha');
        $this->assertDatabaseCount('bitacoras_emocionales', 1);
    }

    public function test_different_students_can_register_on_the_same_date(): void
    {
        $fecha = now()->toDateString();

        // Alumno 1 registra hoy
        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => $fecha,
            'intensidad' => 4,
        ]);

        // Alumno 2 también registra hoy (sin colisión)
        $response = $this->actingAs($this->otroAlumno)->post(route('alumno.bitacora.store'), [
            'fecha' => $fecha,
            'emocion_id' => $this->emocionTristeza->emocion_id,
            'intensidad' => 2,
        ]);

        $response->assertRedirectToRoute('alumno.bitacora.index');
        $this->assertDatabaseCount('bitacoras_emocionales', 2);
    }

    public function test_alumno_can_view_own_record_details(): void
    {
        $bitacora = BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 5,
            'descripcion' => 'Día de graduación',
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.show', $bitacora->bitacora_id));

        $response->assertOk();
        $response->assertJson([
            'bitacora_id' => $bitacora->bitacora_id,
            'intensidad' => 5,
            'descripcion' => 'Día de graduación',
        ]);
    }

    public function test_alumno_cannot_view_another_students_record(): void
    {
        $bitacoraAjena = BitacoraEmocional::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'emocion_id' => $this->emocionTristeza->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 3,
            'descripcion' => 'Confidencial de otro alumno',
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.show', $bitacoraAjena->bitacora_id));

        $response->assertForbidden();
    }

    public function test_alumno_can_update_own_record(): void
    {
        $bitacora = BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionTristeza->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 2,
            'descripcion' => 'Me sentía desanimado en la mañana',
        ]);

        $updatePayload = [
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'intensidad' => 4,
            'descripcion' => 'Mejoró mi estado de ánimo por la tarde al hablar con mis amigos',
            'observaciones' => 'El apoyo social me ayudó mucho.',
        ];

        $response = $this->actingAs($this->alumno)->put(route('alumno.bitacora.update', $bitacora->bitacora_id), $updatePayload);

        $response->assertRedirectToRoute('alumno.bitacora.index');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bitacoras_emocionales', [
            'bitacora_id' => $bitacora->bitacora_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'intensidad' => 4,
            'descripcion' => 'Mejoró mi estado de ánimo por la tarde al hablar con mis amigos',
        ]);
    }

    public function test_alumno_cannot_update_another_students_record(): void
    {
        $bitacoraAjena = BitacoraEmocional::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'emocion_id' => $this->emocionTristeza->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 3,
        ]);

        $response = $this->actingAs($this->alumno)->put(route('alumno.bitacora.update', $bitacoraAjena->bitacora_id), [
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'intensidad' => 5,
        ]);

        $response->assertForbidden();
        $this->assertSame(3, $bitacoraAjena->fresh()->intensidad);
    }

    public function test_alumno_can_delete_own_record_and_reverts_points(): void
    {
        $bitacora = BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 3,
        ]);

        // Otorgar punto inicial
        app(\App\Services\Puntos\PuntoService::class)->otorgarPorEmocionDiaria($this->alumno, $bitacora);
        $this->assertEquals(1, $this->alumno->estudiante->fresh()->saldo_puntos);

        $response = $this->actingAs($this->alumno)->delete(route('alumno.bitacora.destroy', $bitacora->bitacora_id));

        $response->assertRedirect(route('alumno.bitacora.index'));
        $this->assertDatabaseMissing('bitacoras_emocionales', [
            'bitacora_id' => $bitacora->bitacora_id,
        ]);

        // Verificar que el punto fue revertido contablemente
        $this->assertEquals(0, $this->alumno->estudiante->fresh()->saldo_puntos);
    }

    public function test_bitacora_view_contains_delete_buttons_and_modal(): void
    {
        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 4,
            'descripcion' => 'Registro de prueba para verificar presencia de eliminación segura',
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.index'));

        $response->assertOk();
        $response->assertSee('modalEliminarBitacoraConfirm');
        $response->assertSee('btnEliminarDesdeDetalle');
        $response->assertSee('btn-eliminar-fila');
    }

    public function test_bitacora_view_renders_compact_emotions_list(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.index'));

        $response->assertOk();
        $response->assertSee('emotions-list-compact');
        $response->assertSee('emotion-list-item');
        $response->assertSee('Significado de emociones');
        $response->assertSee('Sensación de entusiasmo, bienestar, plenitud o satisfacción.');
    }

    public function test_alumno_cannot_delete_record_associated_with_psychological_tracking_fn4(): void
    {
        $bitacoraConSeguimiento = BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionTristeza->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 5,
        ]);

        BitacoraEmocional::$simularSeguimientoActivoEnPruebas = true;

        try {
            $response = $this->actingAs($this->alumno)->delete(route('alumno.bitacora.destroy', $bitacoraConSeguimiento->bitacora_id));

            $response->assertForbidden();
            $this->assertDatabaseHas('bitacoras_emocionales', [
                'bitacora_id' => $bitacoraConSeguimiento->bitacora_id,
            ]);
        } finally {
            BitacoraEmocional::$simularSeguimientoActivoEnPruebas = false;
        }
    }

    public function test_alumno_cannot_delete_another_students_record(): void
    {
        $bitacoraAjena = BitacoraEmocional::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'emocion_id' => $this->emocionTristeza->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 3,
        ]);

        $response = $this->actingAs($this->alumno)->delete(route('alumno.bitacora.destroy', $bitacoraAjena->bitacora_id));

        $response->assertForbidden();
        $this->assertDatabaseHas('bitacoras_emocionales', [
            'bitacora_id' => $bitacoraAjena->bitacora_id,
        ]);
    }

    public function test_calendar_ajax_returns_month_records(): void
    {
        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => '2026-10-15',
            'intensidad' => 4,
        ]);

        $response = $this->actingAs($this->alumno)
            ->getJson(route('alumno.bitacora.index', ['mes' => 10, 'anio' => 2026]));

        $response->assertOk();
        $response->assertJsonStructure([
            'mes',
            'anio',
            'mes_nombre',
            'registros' => [
                '2026-10-15' => [
                    'bitacora_id',
                    'intensidad',
                    'emocion' => ['nombre', 'icono', 'color_hex'],
                ],
            ],
        ]);
    }

    public function test_foreign_key_restrict_prevents_deleting_emotion_with_active_logs(): void
    {
        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 4,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->emocionAlegria->delete();
    }

    public function test_foreign_key_restrict_prevents_deleting_student_with_active_logs(): void
    {
        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => now()->toDateString(),
            'intensidad' => 4,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->alumno->estudiante()->delete();
    }

    public function test_bitacora_redirects_gracefully_when_tables_are_missing(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('bitacoras_emocionales');

        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.index'));

        $response->assertRedirectToRoute('alumno.dashboard');
        $response->assertSessionHas('info');
    }

    public function test_history_filter_by_date_range_includes_boundaries(): void
    {
        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => '2026-10-10',
            'intensidad' => 3,
            'descripcion' => 'Registro fuera del rango temprano',
        ]);

        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => '2026-10-15',
            'intensidad' => 4,
            'descripcion' => 'Registro dentro del rango esperado',
        ]);

        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => '2026-10-25',
            'intensidad' => 5,
            'descripcion' => 'Registro fuera del rango tardio',
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.index', [
            'filtro_desde' => '2026-10-12',
            'filtro_hasta' => '2026-10-18',
        ]));

        $response->assertOk();
        $historial = $response->viewData('historial');
        $this->assertCount(1, $historial);
        $this->assertEquals('Registro dentro del rango esperado', $historial->first()->descripcion);
    }

    public function test_history_filter_with_inverted_date_range_redirects_with_warning(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.index', [
            'filtro_desde' => '2026-10-25',
            'filtro_hasta' => '2026-10-10',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('warning', 'La fecha inicial ("Desde") no puede ser posterior a la fecha final ("Hasta").');
    }

    public function test_distribucion_visual_y_responsiva_dos_columnas_contenedor_centrado(): void
    {
        // Registrar un caso con texto largo en la descripción y notas
        BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $this->emocionAlegria->emocion_id,
            'fecha' => now()->format('Y-m-d'),
            'intensidad' => 5,
            'descripcion' => 'Esta es una reflexión muy extensa donde se detallan situaciones académicas complejas, retos superados en equipo durante la presentación de proyectos y la satisfacción de alcanzar los objetivos planteados con esfuerzo constante.',
            'observaciones' => 'Nota privada con observaciones reflexivas profundas sobre el progreso personal y el balance emocional cotidiano.',
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.bitacora.index'));

        $response->assertOk();

        // 1. Contenedor principal centrado con ancho máximo
        $response->assertSee('bitacora-dashboard-container', false);

        // 2. Columna izquierda: Calendario emocional con proporción generosa (7/12)
        $response->assertSee('col-lg-7 col-xl-7', false);
        $response->assertSee('calendarNavTitle', false);
        $response->assertSee('btnViewMes', false);
        $response->assertSee('btnViewSemana', false);
        $response->assertSee('btnViewDia', false);

        // 3. Columna derecha: Información contextual apilada verticalmente (5/12)
        $response->assertSee('col-lg-5 col-xl-5', false);
        $response->assertSee('d-flex flex-column gap-4', false);
        $response->assertSee('panelDetalleDia', false);
        $response->assertSee('Significado de emociones', false);

        // 4. Zona inferior: Historial cronológico a ancho completo (col-12)
        $response->assertSee('Historial cronológico', false);
        $response->assertSee('col-12', false);

        // 5. Presencia del texto largo sin errores de renderizado
        $response->assertSee('Esta es una reflexión muy extensa', false);
    }
}

