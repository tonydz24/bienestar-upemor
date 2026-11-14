<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\BitacoraEmocional;
use App\Models\CitaDisponibilidad;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Emocion;
use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use App\Models\EncuestaRespuesta;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\Grupo;
use App\Models\InstrumentoEvaluacion;
use App\Models\Meditacion;
use App\Models\MeditacionSesion;
use App\Models\PeriodoAcademico;
use App\Models\PlanEstudio;
use App\Models\PreguntaEvaluacion;
use App\Models\ProgramaEducativo;
use App\Models\PuntoConfiguracion;
use App\Models\PuntoMovimiento;
use App\Models\Rol;
use App\Models\User;
use App\Models\VersionInstrumento;
use App\Services\Academico\CatedraAcademicaService;
use App\Services\Citas\CitaService;
use App\Services\Puntos\PuntoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreMaestroAdversasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $docente;
    private User $orientador;
    private User $alumno;
    private User $segundoAlumno;
    private ProgramaEducativo $programaIID;
    private PlanEstudio $plan;
    private PeriodoAcademico $periodo;
    private Grupo $grupo;
    private Asignatura $asignatura;
    private DocenteAsignacion $asignacion;
    private CitaProfesional $citaProfesional;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\EmocionesSeeder::class);

        $rolAdmin = Rol::firstOrCreate(['rol_id' => 1], ['nombre' => 'Administrador']);
        $rolDocente = Rol::firstOrCreate(['rol_id' => 2], ['nombre' => 'Docente']);
        $rolAlumno = Rol::firstOrCreate(['rol_id' => 3], ['nombre' => 'Alumno']);
        $rolOrientador = Rol::firstOrCreate(['rol_id' => 4], ['nombre' => 'Orientador']);

        // Usuarios
        $this->admin = User::factory()->create([
            'rol_id' => $rolAdmin->rol_id,
            'email' => 'admin.cierre@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);

        $this->docente = User::factory()->create([
            'rol_id' => $rolDocente->rol_id,
            'email' => 'docente.cierre@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $docenteModel = Docente::create([
            'usuario_id' => $this->docente->usuario_id,
            'numero_empleado' => 'EMP-CIERRE-01',
            'activo' => true,
        ]);

        $this->orientador = User::factory()->create([
            'rol_id' => $rolOrientador->rol_id,
            'email' => 'orientador.cierre@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $this->citaProfesional = CitaProfesional::create([
            'usuario_id' => $this->orientador->usuario_id,
            'cedula_profesional' => 'CED-PSI-9988',
            'cargo' => 'Psicólogo de Orientación Educativa',
            'lugar_atencion' => 'Edificio C - Cubículo 12',
            'modalidades_permitidas' => 'AMBAS',
            'activo' => true,
            'permite_citas' => true,
        ]);

        // Entorno académico
        $this->programaIID = ProgramaEducativo::create([
            'clave' => 'IID',
            'nombre' => 'Ingeniería en Informática y Desarrollo',
            'estado_operativo' => 'ACTIVO',
            'activo' => true,
        ]);

        $this->plan = PlanEstudio::create([
            'programa_id' => $this->programaIID->programa_id,
            'codigo' => 'IID-2026',
            'nombre' => 'Plan de Estudios 2026',
            'version' => '1.0',
            'anio_aprobacion' => 2026,
            'vigencia_inicio' => now()->toDateString(),
            'estado' => PlanEstudio::ESTADO_VIGENTE,
            'es_vigente' => true,
            'total_cuatrimestres' => 10,
        ]);

        $this->periodo = PeriodoAcademico::create([
            'clave' => '2026-3',
            'nombre' => 'Septiembre - Diciembre 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-18',
            'activo' => true,
        ]);

        $this->grupo = Grupo::create([
            'periodo_id' => $this->periodo->periodo_id,
            'programa_id' => $this->programaIID->programa_id,
            'cuatrimestre' => 4,
            'nombre' => 'B',
            'turno' => 'MATUTINO',
            'activo' => true,
        ]);

        $this->asignatura = Asignatura::create([
            'programa_id' => $this->programaIID->programa_id,
            'plan_id' => $this->plan->plan_id,
            'clave' => 'IID-401',
            'nombre' => 'Arquitectura de Software Institucional',
            'cuatrimestre' => 4,
            'creditos' => 6,
            'horas_semana' => 4,
            'tipo' => 'ORDINARIA',
            'activo' => true,
        ]);

        $this->asignacion = DocenteAsignacion::create([
            'docente_id' => $this->docente->usuario_id,
            'asignatura_id' => $this->asignatura->asignatura_id,
            'periodo_id' => $this->periodo->periodo_id,
            'grupo_id' => $this->grupo->grupo_id,
            'alcance_inscripcion' => 'ESPECIFICO',
            'cupo_maximo' => 30,
            'permite_inscripciones' => true,
            'estado' => DocenteAsignacion::ESTADO_APROBADA,
            'activo' => true,
        ]);

        // Alumno 1
        $this->alumno = User::factory()->create([
            'rol_id' => $rolAlumno->rol_id,
            'email' => 'alumno.cierre@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        $estudiante1 = Estudiante::create([
            'usuario_id' => $this->alumno->usuario_id,
            'programa_id' => $this->programaIID->programa_id,
            'matricula' => 'CIERRE202601',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'saldo_puntos' => 100,
        ]);

        // Alumno 2
        $this->segundoAlumno = User::factory()->create([
            'rol_id' => $rolAlumno->rol_id,
            'email' => 'alumno2.cierre@upemor.edu.mx',
            'estado' => 'ACTIVO',
        ]);
        Estudiante::create([
            'usuario_id' => $this->segundoAlumno->usuario_id,
            'programa_id' => $this->programaIID->programa_id,
            'matricula' => 'CIERRE202602',
            'cuatrimestre' => 4,
            'grupo' => 'B',
            'saldo_puntos' => 80,
        ]);

        EstudianteInscripcion::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'periodo_id' => $this->periodo->periodo_id,
            'grupo_id' => $this->grupo->grupo_id,
            'estado' => EstudianteInscripcion::ESTADO_ACTIVO,
        ]);
    }

    // =========================================================================
    // SECCIÓN 1: ELIMINACIÓN DE ENCUESTAS (Fase 2)
    // =========================================================================

    public function test_eliminacion_encuesta_bloqueada_sin_palabra_confirmacion_si_tiene_respuestas(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Evaluación de Servicio Psicológico',
            'tipo' => 'POST_CITA',
            'creado_por' => $this->admin->usuario_id,
            'activa' => true,
        ]);

        $pregunta = EncuestaPregunta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'texto_pregunta' => '¿Cómo calificas la atención?',
            'tipo' => 'ESCALA_1_5',
            'orden' => 1,
            'es_requerida' => true,
        ]);

        EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'usuario_id' => $this->alumno->usuario_id,
            'fecha_respuesta' => now(),
        ]);

        // Intento de eliminación sin confirmacion_eliminar="ELIMINAR"
        $response = $this->actingAs($this->admin)->delete(route('admin.encuestas.destroy', $encuesta->encuesta_id), [
            'confirmacion_eliminar' => 'INCORRECTO',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('encuestas', ['encuesta_id' => $encuesta->encuesta_id]);
    }

    public function test_eliminacion_encuesta_definitiva_atomica_con_palabra_eliminar(): void
    {
        $encuesta = Encuesta::create([
            'titulo' => 'Encuesta a Eliminar Definitivamente',
            'tipo' => 'POST_CITA',
            'creado_por' => $this->admin->usuario_id,
            'activa' => true,
        ]);

        $pregunta = EncuestaPregunta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'texto_pregunta' => 'Pregunta de prueba',
            'tipo' => 'ABIERTA',
            'orden' => 1,
            'es_requerida' => false,
        ]);

        $respuesta = EncuestaRespuesta::create([
            'encuesta_id' => $encuesta->encuesta_id,
            'usuario_id' => $this->alumno->usuario_id,
            'fecha_respuesta' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.encuestas.destroy', $encuesta->encuesta_id), [
            'confirmacion_eliminar' => 'ELIMINAR',
        ]);

        $response->assertRedirect(route('admin.encuestas.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('encuestas', ['encuesta_id' => $encuesta->encuesta_id]);
        $this->assertDatabaseMissing('encuestas_preguntas', ['pregunta_id' => $pregunta->pregunta_id]);
        $this->assertDatabaseMissing('encuestas_respuestas', ['respuesta_id' => $respuesta->respuesta_id]);
    }

    // =========================================================================
    // SECCIÓN 2: ELIMINACIÓN Y RETIRO DE MEDITACIONES (Fase 2)
    // =========================================================================

    public function test_eliminacion_meditacion_bloqueada_sin_palabra_confirmacion_si_tiene_sesiones(): void
    {
        $meditacion = Meditacion::create([
            'titulo' => 'Respiración Profunda y Consciente',
            'descripcion' => 'Guía de 5 minutos',
            'instrucciones' => 'Inhala suavemente y exhala lentamente.',
            'duracion_minutos' => 5,
            'tipo' => 'MINDFULNESS',
            'activa' => true,
        ]);

        MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $this->alumno->usuario_id,
            'duracion_segundos' => 300,
            'completada' => true,
            'puntos_otorgados' => 10,
            'fecha_sesion' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.meditaciones.destroy', $meditacion->meditacion_id), [
            'confirmacion_eliminar' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('meditaciones', ['meditacion_id' => $meditacion->meditacion_id]);
    }

    public function test_eliminacion_meditacion_definitiva_atomica_con_palabra_eliminar(): void
    {
        $meditacion = Meditacion::create([
            'titulo' => 'Meditación para Eliminar Definitivamente',
            'descripcion' => 'Sesión de prueba',
            'instrucciones' => 'Respira con calma y mantén la atención en el presente.',
            'duracion_minutos' => 10,
            'tipo' => 'RELAJACION',
            'activa' => true,
        ]);

        $sesion = MeditacionSesion::create([
            'meditacion_id' => $meditacion->meditacion_id,
            'usuario_id' => $this->alumno->usuario_id,
            'duracion_segundos' => 600,
            'completada' => true,
            'puntos_otorgados' => 15,
            'fecha_sesion' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.meditaciones.destroy', $meditacion->meditacion_id), [
            'confirmacion_eliminar' => 'ELIMINAR',
        ]);

        $response->assertRedirect(route('admin.meditaciones.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('meditaciones', ['meditacion_id' => $meditacion->meditacion_id]);
        $this->assertDatabaseMissing('meditaciones_sesiones', ['sesion_id' => $sesion->sesion_id]);
    }

    // =========================================================================
    // SECCIÓN 3: TESTS PSICOMÉTRICOS (Eliminación con intentos y Reaplicación) (Fase 2 y 6)
    // =========================================================================

    public function test_eliminacion_test_psicometrico_bloqueada_sin_palabra_confirmacion_si_tiene_intentos(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'TEST-ANSIEDAD-01',
            'nombre' => 'Escala de Ansiedad UPEMOR',
            'siglas' => 'EAU',
            'categoria' => 'ANSIEDAD',
            'activo' => true,
            'creado_por' => $this->admin->usuario_id,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => 1,
            'instrucciones' => 'Responde con sinceridad cada reactivo.',
            'es_vigente' => true,
            'creado_por' => $this->admin->usuario_id,
        ]);

        EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $version->version_id,
            'numero_intento' => 1,
            'estado' => 'FINALIZADO',
            'puntaje_total' => 12,
            'fecha_inicio' => now()->subMinutes(30),
            'fecha_finalizacion' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.tests.destroy', $instrumento->instrumento_id), [
            'confirmacion_eliminar' => 'NO_ELIMINAR',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('evaluaciones_instrumentos', ['instrumento_id' => $instrumento->instrumento_id]);
    }

    public function test_eliminacion_test_psicometrico_definitiva_atomica_con_palabra_eliminar(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'TEST-TEMP-02',
            'nombre' => 'Escala Rápida de Prueba',
            'siglas' => 'ERP',
            'categoria' => 'ESTRES',
            'activo' => true,
            'creado_por' => $this->admin->usuario_id,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => 1,
            'instrucciones' => 'Responde con sinceridad cada reactivo.',
            'es_vigente' => true,
            'creado_por' => $this->admin->usuario_id,
        ]);

        $intento = EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $version->version_id,
            'numero_intento' => 1,
            'estado' => 'FINALIZADO',
            'puntaje_total' => 8,
            'fecha_inicio' => now()->subMinutes(20),
            'fecha_finalizacion' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.tests.destroy', $instrumento->instrumento_id), [
            'confirmacion_eliminar' => 'ELIMINAR',
        ]);

        $response->assertRedirect(route('admin.tests.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('evaluaciones_instrumentos', ['instrumento_id' => $instrumento->instrumento_id]);
        $this->assertDatabaseMissing('evaluaciones_versiones', ['version_id' => $version->version_id]);
        $this->assertDatabaseMissing('evaluaciones_intentos', ['intento_id' => $intento->intento_id]);
    }

    public function test_test_psicometrico_con_dias_espera_cero_permite_reaplicacion_inmediata(): void
    {
        $instrumento = InstrumentoEvaluacion::create([
            'codigo' => 'TEST-INMEDIATO-01',
            'nombre' => 'Tamizaje de Estado Diario',
            'siglas' => 'TED',
            'categoria' => 'BIENESTAR',
            'dias_espera_reaplicacion' => 0, // Regla institucional: 0 = sin espera
            'activo' => true,
            'creado_por' => $this->admin->usuario_id,
        ]);

        $version = VersionInstrumento::create([
            'instrumento_id' => $instrumento->instrumento_id,
            'version' => 1,
            'instrucciones' => 'Responde con sinceridad cada reactivo.',
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
            'creado_por' => $this->admin->usuario_id,
        ]);

        // Intento previo finalizado hace 10 minutos
        EvaluacionIntento::create([
            'usuario_id' => $this->alumno->usuario_id,
            'version_id' => $version->version_id,
            'numero_intento' => 1,
            'estado' => 'FINALIZADO',
            'puntaje_total' => 14,
            'fecha_inicio' => now()->subMinutes(25),
            'fecha_finalizacion' => now()->subMinutes(10),
        ]);

        // Alumno intenta iniciar nuevo intento
        $response = $this->actingAs($this->alumno)->get(route('alumno.evaluaciones.iniciar', $instrumento->instrumento_id));

        // Debe permitir ver el cuestionario o consentimiento sin bloqueo de espera
        $this->assertNotEquals(302, $response->getStatusCode(), 'No debe redirigir por bloqueo de días de espera cuando dias_espera_reaplicacion = 0');
    }

    // =========================================================================
    // SECCIÓN 4: ESTRUCTURA CURRICULAR (Programas, Planes y Asignaturas) (Fase 2)
    // =========================================================================

    public function test_programa_y_plan_sin_historial_docente_ni_estudiantil_se_eliminan_en_cascada(): void
    {
        $programaVacio = ProgramaEducativo::create([
            'clave' => 'NUEVO_PROG',
            'nombre' => 'Programa Experimental',
            'estado_operativo' => 'EN_DISEÑO',
            'activo' => true,
        ]);

        $planVacio = PlanEstudio::create([
            'programa_id' => $programaVacio->programa_id,
            'codigo' => 'NUEVO-2027',
            'nombre' => 'Plan Experimental 2027',
            'version' => '1.0',
            'anio_aprobacion' => 2027,
            'vigencia_inicio' => now()->toDateString(),
            'estado' => PlanEstudio::ESTADO_BORRADOR,
            'es_vigente' => false,
            'total_cuatrimestres' => 9,
        ]);

        $materiaVacia = Asignatura::create([
            'programa_id' => $programaVacio->programa_id,
            'plan_id' => $planVacio->plan_id,
            'clave' => 'EXP-101',
            'nombre' => 'Introducción Experimental',
            'cuatrimestre' => 1,
            'creditos' => 4,
            'horas_semana' => 3,
            'tipo' => 'ORDINARIA',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.carreras.destroy', $programaVacio->programa_id));

        $response->assertRedirect(route('admin.carreras.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('programas_educativos', ['programa_id' => $programaVacio->programa_id]);
        $this->assertDatabaseMissing('planes_estudio', ['plan_id' => $planVacio->plan_id]);
        $this->assertDatabaseMissing('asignaturas', ['asignatura_id' => $materiaVacia->asignatura_id]);
    }

    public function test_programa_con_inscripciones_o_asignaciones_bloquea_eliminacion(): void
    {
        // $this->programaIID tiene asignación docente e inscripciones
        $response = $this->actingAs($this->admin)->delete(route('admin.carreras.destroy', $this->programaIID->programa_id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('programas_educativos', ['programa_id' => $this->programaIID->programa_id]);
    }

    public function test_eliminacion_asignatura_individual_sin_historial_es_exitosa(): void
    {
        $materiaAislada = Asignatura::create([
            'programa_id' => $this->programaIID->programa_id,
            'plan_id' => $this->plan->plan_id,
            'clave' => 'IID-OPT-99',
            'nombre' => 'Materia Optativa No Asignada',
            'cuatrimestre' => 9,
            'creditos' => 5,
            'horas_semana' => 4,
            'tipo' => 'OPTATIVA',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.planes.materias.destroy', $materiaAislada->asignatura_id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('asignaturas', ['asignatura_id' => $materiaAislada->asignatura_id]);
    }

    public function test_eliminacion_asignatura_con_asignacion_docente_es_bloqueada(): void
    {
        // $this->asignatura tiene $this->asignacion activa
        $response = $this->actingAs($this->admin)->delete(route('admin.planes.materias.destroy', $this->asignatura->asignatura_id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('asignaturas', ['asignatura_id' => $this->asignatura->asignatura_id]);
    }

    // =========================================================================
    // SECCIÓN 5: BITÁCORA EMOCIONAL Y REVERSIÓN DE PUNTOS (Fase 3)
    // =========================================================================

    public function test_bitacora_eliminar_emocion_revierte_monto_exacto_de_puntos_con_saldo_suficiente(): void
    {
        $emocion = Emocion::where('slug', 'alegria')->firstOrFail();

        $registro = BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'intensidad' => 5,
            'descripcion' => 'Excelente día de avance en el proyecto',
            'fecha' => now()->toDateString(),
        ]);

        // Se le otorgaron 15 puntos por este registro
        PuntoMovimiento::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'tipo' => PuntoMovimiento::TIPO_EMOCION_DIARIA,
            'puntos' => 15,
            'descripcion' => 'Registro en bitácora emocional',
            'referencia_tipo' => BitacoraEmocional::class,
            'referencia_id' => $registro->bitacora_id,
            'fecha' => now()->toDateString(),
            'saldo_anterior' => 100,
            'saldo_nuevo' => 115,
        ]);
        $this->alumno->estudiante->update(['saldo_puntos' => 115]);

        $response = $this->actingAs($this->alumno)->delete(route('alumno.bitacora.destroy', $registro->bitacora_id));

        $response->assertRedirect(route('alumno.bitacora.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('bitacoras_emocionales', ['bitacora_id' => $registro->bitacora_id]);

        // Verificar que el saldo bajó exactamente de 115 a 100 (-15)
        $this->assertEquals(100, $this->alumno->estudiante->fresh()->saldo_puntos);

        // Verificar que se registró el movimiento contable de reversión
        $this->assertDatabaseHas('estudiante_puntos_movimientos', [
            'estudiante_id' => $this->alumno->usuario_id,
            'tipo' => PuntoMovimiento::TIPO_AJUSTE_ADMINISTRATIVO,
            'puntos' => -15,
            'referencia_tipo' => BitacoraEmocional::class,
            'referencia_id' => $registro->bitacora_id,
        ]);
    }

    public function test_bitacora_eliminar_emocion_bloquea_si_alumno_gasto_puntos_y_saldo_es_insuficiente(): void
    {
        $emocion = Emocion::where('slug', 'alegria')->firstOrFail();

        $registro = BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'intensidad' => 4,
            'descripcion' => 'Entusiasta con el nuevo taller',
            'fecha' => now()->toDateString(),
        ]);

        // Otorgamos 20 puntos por este registro
        PuntoMovimiento::create([
            'estudiante_id' => $this->alumno->usuario_id,
            'tipo' => PuntoMovimiento::TIPO_EMOCION_DIARIA,
            'puntos' => 20,
            'descripcion' => 'Registro en bitácora emocional',
            'referencia_tipo' => BitacoraEmocional::class,
            'referencia_id' => $registro->bitacora_id,
            'fecha' => now()->toDateString(),
            'saldo_anterior' => 0,
            'saldo_nuevo' => 20,
        ]);

        // El alumno gastó sus puntos en recompensas y su saldo actual es de solo 5 puntos
        $this->alumno->estudiante->update(['saldo_puntos' => 5]);

        // Intentar eliminar la emoción
        $response = $this->actingAs($this->alumno)->delete(route('alumno.bitacora.destroy', $registro->bitacora_id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        // La emoción NO debe haberse borrado
        $this->assertDatabaseHas('bitacoras_emocionales', ['bitacora_id' => $registro->bitacora_id]);

        // El saldo del estudiante debe mantenerse íntegro en 5 (sin saldos negativos ni descontar a ciegas)
        $this->assertEquals(5, $this->alumno->estudiante->fresh()->saldo_puntos);
    }

    public function test_bitacora_eliminar_emocion_sin_movimiento_de_puntos_se_elimina_sin_descontar(): void
    {
        $emocion = Emocion::where('slug', 'calma')->firstOrFail();

        // Registro sin otorgamiento de puntos previo
        $registro = BitacoraEmocional::create([
            'usuario_id' => $this->alumno->usuario_id,
            'emocion_id' => $emocion->emocion_id,
            'intensidad' => 3,
            'descripcion' => 'Día regular y tranquilo',
            'fecha' => now()->toDateString(),
        ]);

        $this->alumno->estudiante->update(['saldo_puntos' => 50]);

        $response = $this->actingAs($this->alumno)->delete(route('alumno.bitacora.destroy', $registro->bitacora_id));

        $response->assertRedirect(route('alumno.bitacora.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('bitacoras_emocionales', ['bitacora_id' => $registro->bitacora_id]);
        $this->assertEquals(50, $this->alumno->estudiante->fresh()->saldo_puntos);
    }

    // =========================================================================
    // SECCIÓN 6: CITAS PSICOLÓGICAS Y REPROGRAMACIÓN (Fase 4)
    // =========================================================================

    public function test_cita_solicitar_reprogramacion_mantiene_cita_original_confirmada_hasta_resolucion(): void
    {
        $fechaCita = Carbon::tomorrow()->toDateString();
        $nuevaFecha = Carbon::tomorrow()->addDays(2)->toDateString();

        // Crear disponibilidad del orientador para la nueva fecha
        $diaSemanaNueva = Carbon::parse($nuevaFecha)->dayOfWeekIso;
        CitaDisponibilidad::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'dia_semana' => $diaSemanaNueva,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'duracion_cita_min' => 50,
            'modalidad' => 'PRESENCIAL',
            'activo' => true,
        ]);

        $cita = CitaOrientacion::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'estudiante_id' => $this->alumno->usuario_id,
            'fecha_cita' => $fechaCita,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:50:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now()->subDay(),
            'fecha_resolucion' => now()->subHours(12),
            'resuelto_por' => $this->orientador->usuario_id,
        ]);

        $response = $this->actingAs($this->alumno)->post(route('alumno.citas.reprogramar', $cita->cita_id), [
            'nueva_fecha' => $nuevaFecha,
            'nueva_hora_inicio' => '10:00',
            'motivo' => 'Cruce de horario con examen de Cálculo',
        ]);

        $response->assertRedirect(route('alumno.citas.show', $cita->cita_id));
        $response->assertSessionHas('success');

        $citaActualizada = $cita->fresh();

        // La cita original permanece en CONFIRMADA con la fecha original
        $this->assertEquals(CitaOrientacion::ESTADO_CONFIRMADA, $citaActualizada->estado);
        $this->assertEquals($fechaCita, $citaActualizada->fecha_cita->toDateString());
        $this->assertEquals('09:00:00', $citaActualizada->hora_inicio);

        // Los datos de reprogramación quedan en PENDIENTE
        $this->assertEquals(CitaOrientacion::REPROGRAMACION_PENDIENTE, $citaActualizada->reprogramacion_estado);
        $this->assertEquals($nuevaFecha, $citaActualizada->reprogramacion_fecha->toDateString());
        $this->assertEquals('10:00:00', $citaActualizada->reprogramacion_hora_inicio);
        $this->assertEquals('Cruce de horario con examen de Cálculo', $citaActualizada->reprogramacion_motivo);
    }

    public function test_cita_cancelar_reprogramacion_por_alumno_mantiene_cita_original_intacta(): void
    {
        $fechaCita = Carbon::tomorrow()->toDateString();
        $nuevaFecha = Carbon::tomorrow()->addDays(2)->toDateString();

        $cita = CitaOrientacion::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'estudiante_id' => $this->alumno->usuario_id,
            'fecha_cita' => $fechaCita,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:50:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'reprogramacion_estado' => CitaOrientacion::REPROGRAMACION_PENDIENTE,
            'reprogramacion_fecha' => $nuevaFecha,
            'reprogramacion_hora_inicio' => '10:00:00',
            'reprogramacion_hora_fin' => '10:50:00',
            'reprogramacion_motivo' => 'Motivo de prueba',
            'fecha_solicitud' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->alumno)->delete(route('alumno.citas.cancelar-reprogramacion', $cita->cita_id));

        $response->assertRedirect(route('alumno.citas.show', $cita->cita_id));
        $response->assertSessionHas('success');

        $citaActualizada = $cita->fresh();
        $this->assertEquals(CitaOrientacion::REPROGRAMACION_CANCELADA, $citaActualizada->reprogramacion_estado);
        $this->assertEquals(CitaOrientacion::ESTADO_CONFIRMADA, $citaActualizada->estado);
        $this->assertEquals($fechaCita, $citaActualizada->fecha_cita->toDateString());
    }

    public function test_cita_aprobar_reprogramacion_revalida_disponibilidad_y_actualiza_fecha_hora(): void
    {
        $fechaOriginal = Carbon::tomorrow()->toDateString();
        $nuevaFecha = Carbon::tomorrow()->addDays(3)->toDateString();

        $diaSemana = Carbon::parse($nuevaFecha)->dayOfWeekIso;
        CitaDisponibilidad::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '11:00:00',
            'hora_fin' => '12:00:00',
            'duracion_cita_min' => 50,
            'modalidad' => 'PRESENCIAL',
            'activo' => true,
        ]);

        $cita = CitaOrientacion::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'estudiante_id' => $this->alumno->usuario_id,
            'fecha_cita' => $fechaOriginal,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:50:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now()->subDay(),
        ]);

        $respReprog = $this->actingAs($this->alumno)->post(route('alumno.citas.reprogramar', $cita->cita_id), [
            'nueva_fecha' => $nuevaFecha,
            'nueva_hora_inicio' => '11:00',
            'motivo' => 'Solicito cambio de día por causa médica',
        ]);

        $response = $this->actingAs($this->orientador)->patch(route('docente.citas.reprogramacion.aprobar', $cita->cita_id));

        $response->assertRedirect(route('docente.citas.show', $cita->cita_id));
        $response->assertSessionHas('success');

        $citaActualizada = $cita->fresh();

        // La fecha y hora de la cita se actualizaron a las nuevas
        $this->assertEquals($nuevaFecha, $citaActualizada->fecha_cita->toDateString());
        $this->assertEquals('11:00:00', $citaActualizada->hora_inicio);
        $this->assertEquals('11:50:00', $citaActualizada->hora_fin);

        // Se conservó la fecha anterior como trazabilidad
        $this->assertEquals($fechaOriginal, $citaActualizada->fecha_cita_anterior->toDateString());
        $this->assertEquals('09:00:00', $citaActualizada->hora_inicio_anterior);

        // El estado de reprogramación cambió a APROBADA
        $this->assertEquals(CitaOrientacion::REPROGRAMACION_APROBADA, $citaActualizada->reprogramacion_estado);

        // Debe haberse creado un registro en la tabla de historial
        $this->assertDatabaseHas('citas_reprogramaciones_historial', [
            'cita_id' => $cita->cita_id,
            'estado' => 'APROBADA',
            'hora_inicio_propuesta' => '11:00:00',
        ]);
    }

    public function test_cita_aprobar_reprogramacion_bloquea_si_horario_ya_no_esta_disponible_por_concurrencia(): void
    {
        $fechaOriginal = Carbon::tomorrow()->toDateString();
        $nuevaFecha = Carbon::tomorrow()->addDays(3)->toDateString();

        $diaSemana = Carbon::parse($nuevaFecha)->dayOfWeekIso;
        CitaDisponibilidad::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '11:00:00',
            'hora_fin' => '12:00:00',
            'duracion_cita_min' => 50,
            'modalidad' => 'PRESENCIAL',
            'activo' => true,
        ]);

        // Cita con reprogramación pendiente
        $cita = CitaOrientacion::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'estudiante_id' => $this->alumno->usuario_id,
            'fecha_cita' => $fechaOriginal,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:50:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'reprogramacion_estado' => CitaOrientacion::REPROGRAMACION_PENDIENTE,
            'reprogramacion_fecha' => $nuevaFecha,
            'reprogramacion_hora_inicio' => '11:00:00',
            'reprogramacion_hora_fin' => '11:50:00',
            'reprogramacion_motivo' => 'Cambio de horario',
            'fecha_solicitud' => now()->subDay(),
        ]);

        // Simular concurrencia: Otro alumno reservó y confirmó exactamente esa nueva fecha y hora antes
        CitaOrientacion::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'estudiante_id' => $this->segundoAlumno->usuario_id,
            'fecha_cita' => $nuevaFecha,
            'hora_inicio' => '11:00:00',
            'hora_fin' => '11:50:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'fecha_solicitud' => now(),
        ]);

        // Orientador intenta aprobar la reprogramación
        $response = $this->actingAs($this->orientador)->patch(route('docente.citas.reprogramacion.aprobar', $cita->cita_id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // La cita original NO debió actualizarse a la nueva fecha
        $citaActualizada = $cita->fresh();
        $this->assertEquals($fechaOriginal, $citaActualizada->fecha_cita->toDateString());
        $this->assertEquals('09:00:00', $citaActualizada->hora_inicio);
    }

    public function test_cita_rechazar_reprogramacion_exige_motivo_y_mantiene_cita_original_confirmada(): void
    {
        $fechaOriginal = Carbon::tomorrow()->toDateString();
        $nuevaFecha = Carbon::tomorrow()->addDays(2)->toDateString();

        $cita = CitaOrientacion::create([
            'profesional_id' => $this->citaProfesional->profesional_id,
            'estudiante_id' => $this->alumno->usuario_id,
            'fecha_cita' => $fechaOriginal,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:50:00',
            'modalidad' => 'PRESENCIAL',
            'estado' => CitaOrientacion::ESTADO_CONFIRMADA,
            'reprogramacion_estado' => CitaOrientacion::REPROGRAMACION_PENDIENTE,
            'reprogramacion_fecha' => $nuevaFecha,
            'reprogramacion_hora_inicio' => '10:00:00',
            'reprogramacion_hora_fin' => '10:50:00',
            'fecha_solicitud' => now()->subDay(),
        ]);

        // Intento de rechazo sin motivo
        $responseSinMotivo = $this->actingAs($this->orientador)->patch(route('docente.citas.reprogramacion.rechazar', $cita->cita_id), [
            'motivo' => '',
        ]);
        $responseSinMotivo->assertSessionHasErrors('motivo');

        // Rechazo correcto con motivo
        $response = $this->actingAs($this->orientador)->patch(route('docente.citas.reprogramacion.rechazar', $cita->cita_id), [
            'motivo' => 'Ese bloque está asignado para sesión colegiada de directores.',
        ]);

        $response->assertRedirect(route('docente.citas.show', $cita->cita_id));
        $response->assertSessionHas('success');

        $citaActualizada = $cita->fresh();
        $this->assertEquals(CitaOrientacion::REPROGRAMACION_RECHAZADA, $citaActualizada->reprogramacion_estado);
        $this->assertEquals('Ese bloque está asignado para sesión colegiada de directores.', $citaActualizada->reprogramacion_respuesta_motivo);
        $this->assertEquals(CitaOrientacion::ESTADO_CONFIRMADA, $citaActualizada->estado);
        $this->assertEquals($fechaOriginal, $citaActualizada->fecha_cita->toDateString());
    }

    // =========================================================================
    // SECCIÓN 7: CÁTEDRAS ACADÉMICAS Y TRAZABILIDAD (Fase 5)
    // =========================================================================

    public function test_catedra_reintentar_inscripcion_tras_cancelacion_o_rechazo_no_da_error_1062_y_guarda_historial(): void
    {
        $service = app(CatedraAcademicaService::class);

        // Paso 1: El estudiante solicita inscripción
        $inscripcion = $service->solicitarInscripcion(
            usuario: $this->alumno,
            asignacion: $this->asignacion,
            miGrupo: $this->grupo,
            motivo: 'Solicitud ordinaria de materia'
        );

        $this->assertEquals('PENDIENTE', $inscripcion->estado);

        // Paso 2: El estudiante cancela su solicitud pendiente
        $inscripcionCancelada = $this->actingAs($this->alumno)
            ->post(route('alumno.academico.cancelar', $inscripcion->inscripcion_materia_id));

        $this->assertEquals(EstudianteAsignaturaInscripcion::ESTADO_CANCELADA, $inscripcion->fresh()->estado);

        // Paso 3: El estudiante vuelve a solicitar la misma cátedra
        // No debe lanzar 1062 Duplicate entry; debe reactivar y guardar historial completo
        $inscripcionReactivada = $service->solicitarInscripcion(
            usuario: $this->alumno,
            asignacion: $this->asignacion,
            miGrupo: $this->grupo,
            motivo: 'Nueva solicitud tras reevaluar carga académica'
        );

        $this->assertEquals($inscripcion->inscripcion_materia_id, $inscripcionReactivada->inscripcion_materia_id);
        $this->assertEquals('PENDIENTE', $inscripcionReactivada->estado);

        // Verificar el historial JSON
        $this->assertIsArray($inscripcionReactivada->historial_transiciones);
        $this->assertGreaterThanOrEqual(2, count($inscripcionReactivada->historial_transiciones));

        // Verificar que existe registro en la tabla relacional de trazabilidad
        $this->assertDatabaseHas('estudiante_asignatura_inscripciones_historial', [
            'inscripcion_materia_id' => $inscripcion->inscripcion_materia_id,
            'estado_nuevo' => 'PENDIENTE',
        ]);
    }
}
