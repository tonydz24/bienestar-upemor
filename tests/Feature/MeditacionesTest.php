<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Meditacion;
use App\Models\MeditacionSesion;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeditacionesTest extends TestCase
{
    use RefreshDatabase;

    protected Rol $rolAdmin;
    protected Rol $rolDocente;
    protected Rol $rolAlumno;
    protected Rol $rolOrientador;
    protected ProgramaEducativo $programa;
    protected User $admin;
    protected User $docente;
    protected User $alumno;
    protected User $otroAlumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Admin']);
        $this->rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['descripcion' => 'Docente']);
        $this->rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Alumno']);
        $this->rolOrientador = Rol::firstOrCreate(['nombre' => 'Orientador'], ['descripcion' => 'Orientador']);

        $this->programa = ProgramaEducativo::firstOrCreate(
            ['clave' => 'ITI'],
            ['nombre' => 'Ingeniería en Tecnologías de la Información', 'activo' => true]
        );

        $this->admin = User::create([
            'rol_id' => $this->rolAdmin->rol_id,
            'nombre' => 'Admin',
            'apellido_paterno' => 'Principal',
            'apellido_materno' => 'Sistemas',
            'email' => 'admin_med@upemor.edu.mx',
            'password' => bcrypt('AdminPassword123!'),
            'estado' => 'ACTIVO',
        ]);

        $this->docente = User::create([
            'rol_id' => $this->rolDocente->rol_id,
            'nombre' => 'Docente',
            'apellido_paterno' => 'Mendoza',
            'apellido_materno' => 'Gómez',
            'email' => 'docente_med@upemor.edu.mx',
            'password' => bcrypt('DocentePass123!'),
            'estado' => 'ACTIVO',
        ]);
        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'EMP-MED-01',
            'tipo_contrato' => 'TC',
        ]);

        $this->alumno = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Alumno',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'López',
            'email' => 'alumno_med@upemor.edu.mx',
            'password' => bcrypt('AlumnoPass123!'),
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => 'MED2026001',
            'cuatrimestre' => 4,
            'turno' => 'MATUTINO',
            'saldo_puntos' => 20,
        ]);

        $this->otroAlumno = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Otro',
            'apellido_paterno' => 'Santos',
            'apellido_materno' => 'Cruz',
            'email' => 'otro_med@upemor.edu.mx',
            'password' => bcrypt('OtroPass123!'),
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->otroAlumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => 'MED2026002',
            'cuatrimestre' => 5,
            'turno' => 'MATUTINO',
            'saldo_puntos' => 15,
        ]);
    }

    public function test_alumno_puede_ver_catalogo_de_meditaciones_publicadas(): void
    {
        Meditacion::create([
            'titulo' => 'Respiración Profunda de Calma',
            'slug' => 'respiracion-profunda-de-calma',
            'categoria' => Meditacion::CATEGORIA_RESPIRACION,
            'descripcion' => 'Ejercicio para calmar el ritmo cardíaco.',
            'duracion_minutos' => 5,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'es_publicado' => true,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Inhala', 'duracion_segundos' => 4, 'fase' => 'inhala', 'indicacion' => 'Inhala'],
            ],
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.meditaciones.index'));

        $response->assertOk();
        $response->assertSee('Respiración Profunda de Calma');
        $response->assertSee('Respiración Consciente');
    }

    public function test_alumno_no_puede_ver_meditaciones_en_borrador(): void
    {
        Meditacion::create([
            'titulo' => 'Meditación en Construcción',
            'slug' => 'meditacion-en-construccion',
            'categoria' => Meditacion::CATEGORIA_MINDFULNESS,
            'descripcion' => 'Borrador interno no disponible.',
            'duracion_minutos' => 10,
            'nivel' => Meditacion::NIVEL_AVANZADO,
            'es_publicado' => false,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Inicio', 'duracion_segundos' => 5, 'fase' => 'inhala', 'indicacion' => 'Inicio'],
            ],
        ]);

        $response = $this->actingAs($this->alumno)->get(route('alumno.meditaciones.index'));

        $response->assertOk();
        $response->assertDontSee('Meditación en Construcción');
    }

    public function test_alumno_puede_filtrar_por_categoria_y_buscar_texto(): void
    {
        Meditacion::create([
            'titulo' => 'Calma Somática',
            'slug' => 'calma-somatica',
            'categoria' => Meditacion::CATEGORIA_RELAJACION_MUSCULAR,
            'descripcion' => 'Relajación corporal.',
            'duracion_minutos' => 7,
            'nivel' => Meditacion::NIVEL_INTERMEDIO,
            'es_publicado' => true,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Hombros', 'duracion_segundos' => 10, 'fase' => 'tension_relajacion', 'indicacion' => 'Sube hombros'],
            ],
        ]);

        Meditacion::create([
            'titulo' => 'Enfoque Zen',
            'slug' => 'enfoque-zen',
            'categoria' => Meditacion::CATEGORIA_MINDFULNESS,
            'descripcion' => 'Atención plena al examen.',
            'duracion_minutos' => 3,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'es_publicado' => true,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Foco', 'duracion_segundos' => 5, 'fase' => 'inhala', 'indicacion' => 'Foco'],
            ],
        ]);

        $responseFiltro = $this->actingAs($this->alumno)->get(route('alumno.meditaciones.index', [
            'categoria' => Meditacion::CATEGORIA_RELAJACION_MUSCULAR,
        ]));

        $responseFiltro->assertOk();
        $responseFiltro->assertSee('Calma Somática');
        $responseFiltro->assertDontSee('Enfoque Zen');

        $responseBusqueda = $this->actingAs($this->alumno)->get(route('alumno.meditaciones.index', [
            'q' => 'Zen',
        ]));

        $responseBusqueda->assertOk();
        $responseBusqueda->assertSee('Enfoque Zen');
        $responseBusqueda->assertDontSee('Calma Somática');
    }

    public function test_alumno_puede_ver_detalle_y_sala_interactiva_de_sesion(): void
    {
        $med = Meditacion::create([
            'titulo' => 'Pausa de Estudio 4-7-8',
            'slug' => 'pausa-de-estudio-4-7-8',
            'categoria' => Meditacion::CATEGORIA_PAUSA_ESTUDIO,
            'descripcion' => 'Pausa cognitiva de 5 minutos.',
            'duracion_minutos' => 5,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'es_publicado' => true,
            'vistas_count' => 5,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Inhala', 'duracion_segundos' => 4, 'fase' => 'inhala', 'indicacion' => 'Inhala profundo'],
                ['paso' => 2, 'titulo' => 'Sostén', 'duracion_segundos' => 7, 'fase' => 'sosten', 'indicacion' => 'Mantén'],
            ],
        ]);

        $respDetail = $this->actingAs($this->alumno)->get(route('alumno.meditaciones.show', $med));
        $respDetail->assertOk();
        $respDetail->assertSee('Pausa de Estudio 4-7-8');
        $respDetail->assertSee('Iniciar Práctica');

        $respSesion = $this->actingAs($this->alumno)->get(route('alumno.meditaciones.sesion', $med));
        $respSesion->assertOk();
        $respSesion->assertSee('Finalizar y Guardar');

        // Incremento de vistas verificado
        $this->assertEquals(6, $med->fresh()->vistas_count);
    }

    public function test_alumno_puede_registrar_sesion_de_meditacion_completada(): void
    {
        $med = Meditacion::create([
            'titulo' => 'Descanso Reparador',
            'slug' => 'descanso-reparador',
            'categoria' => Meditacion::CATEGORIA_DESCANSO,
            'descripcion' => 'Para antes de dormir.',
            'duracion_minutos' => 8,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'es_publicado' => true,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Suelta', 'duracion_segundos' => 10, 'fase' => 'exhala', 'indicacion' => 'Suelta'],
            ],
        ]);

        $response = $this->actingAs($this->alumno)->postJson(route('alumno.meditaciones.guardar-sesion', $med), [
            'duracion_segundos' => 300,
            'completada' => 1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('meditaciones_sesiones', [
            'meditacion_id' => $med->meditacion_id,
            'usuario_id' => $this->alumno->usuario_id,
            'duracion_segundos' => 300,
            'completada' => true,
        ]);
    }

    public function test_historial_de_meditacion_es_estrictamente_confidencial_para_el_alumno(): void
    {
        $med = Meditacion::create([
            'titulo' => 'Escaneo Confidencial',
            'slug' => 'escaneo-confidencial',
            'categoria' => Meditacion::CATEGORIA_MINDFULNESS,
            'descripcion' => 'Práctica privada.',
            'duracion_minutos' => 6,
            'nivel' => Meditacion::NIVEL_INTERMEDIO,
            'es_publicado' => true,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Paso 1', 'duracion_segundos' => 5, 'fase' => 'inhala', 'indicacion' => 'Respira'],
            ],
        ]);

        // Sesión del alumno 1
        MeditacionSesion::create([
            'meditacion_id' => $med->meditacion_id,
            'usuario_id' => $this->alumno->usuario_id,
            'duracion_segundos' => 240,
            'completada' => true,
            'fecha_sesion' => now(),
        ]);

        // Sesión del alumno 2
        MeditacionSesion::create([
            'meditacion_id' => $med->meditacion_id,
            'usuario_id' => $this->otroAlumno->usuario_id,
            'duracion_segundos' => 600,
            'completada' => true,
            'fecha_sesion' => now(),
        ]);

        $responseAlumno1 = $this->actingAs($this->alumno)->get(route('alumno.meditaciones.historial'));
        $responseAlumno1->assertOk();
        $responseAlumno1->assertSee('Escaneo Confidencial');
        $responseAlumno1->assertSee('4m 0s'); // 240s
        $responseAlumno1->assertDontSee('10m 0s'); // 600s pertenece al otro alumno

        $responseAlumno2 = $this->actingAs($this->otroAlumno)->get(route('alumno.meditaciones.historial'));
        $responseAlumno2->assertOk();
        $responseAlumno2->assertSee('10m 0s');
        $responseAlumno2->assertDontSee('4m 0s');
    }

    public function test_docente_no_puede_acceder_al_historial_de_meditaciones_de_alumnos(): void
    {
        // El middleware role:Alumno bloquea el acceso de docentes al módulo de meditaciones de alumnos
        $response = $this->actingAs($this->docente)->get(route('alumno.meditaciones.historial'));
        $response->assertForbidden();
    }

    public function test_admin_puede_crear_nuevo_ejercicio_guiado(): void
    {
        $payload = [
            'titulo' => 'Técnica Diafragmática 3-3-3',
            'categoria' => Meditacion::CATEGORIA_RESPIRACION,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'duracion_minutos' => 3,
            'descripcion' => 'Técnica express para calma inmediata.',
            'icono' => 'bi-lungs',
            'es_publicado' => 1,
            'notificar_alumnos' => 0,
            'pasos' => [
                [
                    'titulo' => 'Inspiración abdominal',
                    'duracion_segundos' => 3,
                    'fase' => 'inhala',
                    'indicacion' => 'Llena el abdomen de aire',
                ],
                [
                    'titulo' => 'Exhalación suave',
                    'duracion_segundos' => 3,
                    'fase' => 'exhala',
                    'indicacion' => 'Vacía el abdomen despacio',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.meditaciones.store'), $payload);

        $response->assertRedirect(route('admin.meditaciones.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('meditaciones', [
            'titulo' => 'Técnica Diafragmática 3-3-3',
            'categoria' => Meditacion::CATEGORIA_RESPIRACION,
            'duracion_minutos' => 3,
            'es_publicado' => true,
        ]);
    }

    public function test_admin_puede_editar_y_alternar_publicacion(): void
    {
        $med = Meditacion::create([
            'titulo' => 'Pausa Muscular Básica',
            'slug' => 'pausa-muscular-basica',
            'categoria' => Meditacion::CATEGORIA_RELAJACION_MUSCULAR,
            'descripcion' => 'Descripción inicial.',
            'duracion_minutos' => 4,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'es_publicado' => true,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Paso 1', 'duracion_segundos' => 5, 'fase' => 'inhala', 'indicacion' => 'Inhala'],
            ],
        ]);

        // Toggle a borrador
        $respToggle = $this->actingAs($this->admin)->patch(route('admin.meditaciones.toggle-publicado', $med));
        $respToggle->assertRedirect();
        $this->assertFalse($med->fresh()->es_publicado);

        // Edición
        $respUpdate = $this->actingAs($this->admin)->put(route('admin.meditaciones.update', $med), [
            'titulo' => 'Pausa Muscular Avanzada',
            'categoria' => Meditacion::CATEGORIA_RELAJACION_MUSCULAR,
            'nivel' => Meditacion::NIVEL_AVANZADO,
            'duracion_minutos' => 6,
            'descripcion' => 'Descripción renovada.',
            'es_publicado' => 1,
            'pasos' => [
                ['titulo' => 'Fase 1', 'duracion_segundos' => 6, 'fase' => 'inhala', 'indicacion' => 'Inhala profundo'],
            ],
        ]);

        $respUpdate->assertRedirect(route('admin.meditaciones.index'));
        $this->assertDatabaseHas('meditaciones', [
            'meditacion_id' => $med->meditacion_id,
            'titulo' => 'Pausa Muscular Avanzada',
            'duracion_minutos' => 6,
            'es_publicado' => true,
        ]);
    }

    public function test_admin_no_puede_eliminar_meditacion_con_sesiones_registradas(): void
    {
        $med = Meditacion::create([
            'titulo' => 'Meditación con Historial Clínico',
            'slug' => 'meditacion-con-historial',
            'categoria' => Meditacion::CATEGORIA_RESPIRACION,
            'descripcion' => 'Cuenta con sesiones.',
            'duracion_minutos' => 5,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'es_publicado' => true,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Paso 1', 'duracion_segundos' => 5, 'fase' => 'inhala', 'indicacion' => 'Inhala'],
            ],
        ]);

        // Se registra una sesión
        MeditacionSesion::create([
            'meditacion_id' => $med->meditacion_id,
            'usuario_id' => $this->alumno->usuario_id,
            'duracion_segundos' => 120,
            'completada' => true,
            'fecha_sesion' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.meditaciones.destroy', $med));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Sigue existiendo en base de datos
        $this->assertDatabaseHas('meditaciones', [
            'meditacion_id' => $med->meditacion_id,
        ]);
    }

    public function test_admin_puede_eliminar_meditacion_sin_sesiones(): void
    {
        $med = Meditacion::create([
            'titulo' => 'Meditación Huérfana',
            'slug' => 'meditacion-huerfana',
            'categoria' => Meditacion::CATEGORIA_PAUSA_ESTUDIO,
            'descripcion' => 'Sin sesiones registradas.',
            'duracion_minutos' => 2,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'es_publicado' => false,
            'instrucciones' => [
                ['paso' => 1, 'titulo' => 'Paso 1', 'duracion_segundos' => 5, 'fase' => 'inhala', 'indicacion' => 'Inhala'],
            ],
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.meditaciones.destroy', $med));

        $response->assertRedirect(route('admin.meditaciones.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('meditaciones', [
            'meditacion_id' => $med->meditacion_id,
        ]);
    }
}
