<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Meditacion;
use App\Models\MeditacionSesion;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\MeditacionesVideosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeditacionVideoInteractivoTest extends TestCase
{
    use RefreshDatabase;

    protected Rol $rolAlumno;
    protected Rol $rolDocente;
    protected Rol $rolAdmin;
    protected ProgramaEducativo $programa;
    protected User $alumno;
    protected User $docente;
    protected Meditacion $videoMeditacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Admin']);
        $this->rolDocente = Rol::firstOrCreate(['nombre' => 'Docente'], ['descripcion' => 'Docente']);
        $this->rolAlumno = Rol::firstOrCreate(['nombre' => 'Alumno'], ['descripcion' => 'Alumno']);

        $this->programa = ProgramaEducativo::firstOrCreate(
            ['clave' => 'IID'],
            ['nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital', 'activo' => true]
        );

        $this->alumno = User::create([
            'rol_id' => $this->rolAlumno->rol_id,
            'nombre' => 'Manuel Antonio',
            'apellido_paterno' => 'Díaz',
            'apellido_materno' => 'Hernández',
            'email' => 'alumno_multimedia@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->programa->programa_id,
            'matricula' => 'ADMO230179',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'saldo_puntos' => 10,
        ]);

        $this->docente = User::create([
            'rol_id' => $this->rolDocente->rol_id,
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Domínguez',
            'email' => 'docente_multimedia@upemor.edu.mx',
            'password' => bcrypt('Password123!'),
            'estado' => 'ACTIVO',
            'activo' => true,
        ]);

        Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'DOC-MM-01',
            'puesto' => 'Profesor de Tiempo Completo',
            'activo' => true,
        ]);

        $this->videoMeditacion = Meditacion::create([
            'titulo' => 'Respiración 4-7-8 para Reducir la Ansiedad',
            'slug' => 'respiracion-4-7-8-para-reducir-la-ansiedad',
            'categoria' => Meditacion::CATEGORIA_RESPIRACION,
            'descripcion' => 'Técnica guiada de respiración 4-7-8 para calmar el sistema nervioso y disminuir la ansiedad.',
            'duracion_minutos' => 5,
            'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
            'icono' => 'bi-lungs',
            'es_publicado' => true,
            'vistas_count' => 10,
            'instrucciones' => [
                'tipo_contenido' => Meditacion::TIPO_VIDEO,
                'media_url' => 'https://www.youtube.com/watch?v=EGO5m_DBzF8',
                'autor' => 'Gabriela Litschi',
                'puntos' => 2,
                'transcripcion' => 'Guía accesible de respiración 4-7-8.',
            ],
        ]);
    }

    public function test_alumno_autenticado_puede_ver_detalle_meditacion_con_reproductor_y_youtube_api(): void
    {
        $response = $this->actingAs($this->alumno)->get(route('alumno.meditaciones.show', $this->videoMeditacion));

        $response->assertOk();
        $response->assertSee('Respiración 4-7-8 para Reducir la Ansiedad');
        $response->assertSee('Gabriela Litschi');
        $response->assertSee('Ver en YouTube');
        $response->assertSee('https://www.youtube.com/watch?v=EGO5m_DBzF8');
        $response->assertSee('ytplayer-container');
        $response->assertSee('+2 pts');
    }

    public function test_ruta_completar_acredita_dos_puntos_la_primera_vez_del_dia(): void
    {
        $this->assertEquals(10, $this->alumno->estudiante->saldo_puntos);

        $response = $this->actingAs($this->alumno)->postJson(route('alumno.meditaciones.completar', $this->videoMeditacion), [
            'duracion_segundos' => 300,
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => '¡Has ganado 2 puntos de bienestar!',
            'puntos_otorgados' => 2,
            'nuevo_saldo' => 12,
        ]);

        $this->alumno->estudiante->refresh();
        $this->assertEquals(12, $this->alumno->estudiante->saldo_puntos);

        $this->assertDatabaseHas('meditaciones_sesiones', [
            'usuario_id' => $this->alumno->usuario_id,
            'meditacion_id' => $this->videoMeditacion->meditacion_id,
            'completada' => true,
        ]);
    }

    public function test_segunda_peticion_el_mismo_dia_no_duplica_puntos(): void
    {
        // Primera ejecución exitosa
        $this->actingAs($this->alumno)->postJson(route('alumno.meditaciones.completar', $this->videoMeditacion), [
            'duracion_segundos' => 300,
        ])->assertOk();

        $this->alumno->estudiante->refresh();
        $this->assertEquals(12, $this->alumno->estudiante->saldo_puntos);

        // Segunda ejecución el mismo día debe ser rechazada con status already_completed y sin puntos extra
        $response = $this->actingAs($this->alumno)->postJson(route('alumno.meditaciones.completar', $this->videoMeditacion), [
            'duracion_segundos' => 300,
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'already_completed',
            'puntos_otorgados' => 0,
            'nuevo_saldo' => 12,
        ]);

        $this->alumno->estudiante->refresh();
        $this->assertEquals(12, $this->alumno->estudiante->saldo_puntos);
    }

    public function test_usuario_no_autenticado_o_con_rol_docente_no_puede_completar_meditacion(): void
    {
        // 1. Invitado no autenticado es redirigido a login
        $responseGuest = $this->postJson(route('alumno.meditaciones.completar', $this->videoMeditacion));
        $responseGuest->assertUnauthorized();

        // 2. Docente sin rol Alumno no puede acceder a rutas de alumno
        $responseDocente = $this->actingAs($this->docente)->postJson(route('alumno.meditaciones.completar', $this->videoMeditacion));
        $responseDocente->assertForbidden();
    }

    public function test_seeder_videos_garantiza_los_dos_videos_institucionales_activos(): void
    {
        $this->seed(MeditacionesVideosSeeder::class);

        $this->assertDatabaseHas('meditaciones', [
            'slug' => 'respiracion-4-7-8-para-reducir-la-ansiedad',
            'categoria' => 'RESPIRACION',
            'es_publicado' => true,
        ]);

        $this->assertDatabaseHas('meditaciones', [
            'slug' => '6-pequenos-habitos-para-mejorar-tu-bienestar-mental',
            'categoria' => 'MINDFULNESS',
            'es_publicado' => true,
        ]);

        $video2 = Meditacion::where('slug', '6-pequenos-habitos-para-mejorar-tu-bienestar-mental')->first();
        $this->assertNotNull($video2);
        $this->assertEquals('0A3Dr2Sc02g', $video2->youtube_video_id);
        $this->assertEquals('Psych2Go', $video2->autor);
        $this->assertEquals(2, $video2->puntos);
    }
}

