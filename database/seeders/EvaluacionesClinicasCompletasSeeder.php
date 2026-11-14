<?php

namespace Database\Seeders;

use App\Models\BaremoRegla;
use App\Models\EscalaOpcion;
use App\Models\InstrumentoEvaluacion;
use App\Models\PreguntaEvaluacion;
use App\Models\VersionInstrumento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluacionesClinicasCompletasSeeder extends Seeder
{
    /**
     * Conciliación y activación de instrumentos psicométricos estandarizados (FN.3).
     * Pobla reactivos oficiales, escalas de respuesta, baremos psicométricos y activa el estado PUBLICADA.
     */
    public function run(): void
    {
        // 1. Escalas compartidas
        $escalaLikert4 = [
            ['valor' => 0, 'etiqueta' => 'Para nada', 'descripcion' => 'No se presentó en el periodo evaluado', 'orden' => 1],
            ['valor' => 1, 'etiqueta' => 'Varios días', 'descripcion' => 'Presente en algunos días aislados', 'orden' => 2],
            ['valor' => 2, 'etiqueta' => 'Más de la mitad de los días', 'descripcion' => 'Presente con alta recurrencia semanal', 'orden' => 3],
            ['valor' => 3, 'etiqueta' => 'Casi todos los días', 'descripcion' => 'Presente casi a diario en las últimas dos semanas', 'orden' => 4],
        ];

        $escalaLikert5 = [
            ['valor' => 0, 'etiqueta' => 'Nunca', 'descripcion' => 'Ninguna ocasión en el último mes', 'orden' => 1],
            ['valor' => 1, 'etiqueta' => 'Casi nunca', 'descripcion' => 'Ocurrió rara vez', 'orden' => 2],
            ['valor' => 2, 'etiqueta' => 'De vez en cuando', 'descripcion' => 'Ocurrió ocasionalmente', 'orden' => 3],
            ['valor' => 3, 'etiqueta' => 'A menudo', 'descripcion' => 'Ocurrió con frecuencia', 'orden' => 4],
            ['valor' => 4, 'etiqueta' => 'Muy a menudo', 'descripcion' => 'Ocurrió de forma constante', 'orden' => 5],
        ];

        $escalaLikert6 = [
            ['valor' => 0, 'etiqueta' => 'En ningún momento', 'descripcion' => 'No experimentado en las últimas dos semanas', 'orden' => 1],
            ['valor' => 1, 'etiqueta' => 'Menos de la mitad del tiempo', 'descripcion' => 'Rara vez experimentado', 'orden' => 2],
            ['valor' => 2, 'etiqueta' => 'Más o menos la mitad del tiempo', 'descripcion' => 'Frecuencia intermedia', 'orden' => 3],
            ['valor' => 3, 'etiqueta' => 'Más de la mitad del tiempo', 'descripcion' => 'Frecuencia habitual', 'orden' => 4],
            ['valor' => 4, 'etiqueta' => 'Casi todo el tiempo', 'descripcion' => 'Muy frecuente', 'orden' => 5],
            ['valor' => 5, 'etiqueta' => 'Todo el tiempo', 'descripcion' => 'Constante a lo largo de las dos semanas', 'orden' => 6],
        ];

        // ==========================================
        // 1. GAD-7: Escala de Ansiedad Generalizada
        // ==========================================
        $this->conciliarInstrumento([
            'codigo' => 'GAD-7',
            'nombre' => 'Escala de Ansiedad Generalizada GAD-7',
            'siglas' => 'GAD-7',
            'categoria' => 'Ansiedad',
            'descripcion' => 'Instrumento de tamizaje de 7 reactivos para evaluar sintomatología de tensión y ansiedad durante las últimas dos semanas.',
            'autor' => 'Robert L. Spitzer, Kurt Kroenke, Janet B. W. Williams, Bernd Löwe',
            'anio_publicacion' => 2006,
            'dias_espera_reaplicacion' => 14,
            'duracion_estimada_min' => 4,
            'puntos_recompensa' => 10,
            'instrucciones' => 'Durante las últimas 2 semanas, ¿con qué frecuencia te has sentido molesto/a por los siguientes problemas?',
            'marco_temporal' => 'Últimas 2 semanas',
            'escalas' => $escalaLikert4,
            'preguntas' => [
                ['numero' => 1, 'enunciado' => 'Sentirse nervioso/a, intranquilo/a o con los nervios de punta.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 2, 'enunciado' => 'No poder parar o controlar las preocupaciones.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 3, 'enunciado' => 'Preocuparse demasiado por diferentes cosas.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 4, 'enunciado' => 'Dificultad para relajarse.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 5, 'enunciado' => 'Estar tan inquieto/a que es difícil permanecer sentado/a.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 6, 'enunciado' => 'Molestarse o irritarse fácilmente.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 7, 'enunciado' => 'Sentir miedo como si algo terrible fuera a pasar.', 'es_inverso' => false, 'es_critico' => false],
            ],
            'baremos' => [
                ['min' => 0, 'max' => 4, 'nivel' => 'Mínimo', 'color' => '#10b981', 'orientacion' => false, 'msg' => 'Tu nivel de sintomatología ansiosa es mínimo o basal. Continúa fortaleciendo tus hábitos de autocuidado, pausas activas y descanso reparador.'],
                ['min' => 5, 'max' => 9, 'nivel' => 'Leve', 'color' => '#3b82f6', 'orientacion' => false, 'msg' => 'Presentas manifestaciones leves de tensión o inquietud. Se sugiere incorporar ejercicios guiados de respiración profunda y regular tus horas de sueño.'],
                ['min' => 10, 'max' => 14, 'nivel' => 'Moderado', 'color' => '#f59e0b', 'orientacion' => true, 'msg' => 'Se identifican niveles moderados de ansiedad que podrían interferir con tu concentración y bienestar académico. Te sugerimos agendar una sesión orientadora con el equipo de Bienestar UPEMOR.'],
                ['min' => 15, 'max' => 21, 'nivel' => 'Severo', 'color' => '#ef4444', 'orientacion' => true, 'msg' => 'Tus respuestas reflejan una carga ansiosa importante. Te recomendamos enfáticamente acudir con los profesionales de orientación psicológica de UPEMOR para recibir acompañamiento personalizado y confidencial.'],
            ],
        ]);

        // ==========================================
        // 2. PHQ-9: Cuestionario de Salud del Paciente
        // ==========================================
        $this->conciliarInstrumento([
            'codigo' => 'PHQ-9',
            'nombre' => 'Cuestionario de Salud del Paciente PHQ-9',
            'siglas' => 'PHQ-9',
            'categoria' => 'Estado de ánimo',
            'descripcion' => 'Cuestionario estandarizado de 9 reactivos para evaluar sintomatología del estado de ánimo y afectividad durante las últimas dos semanas.',
            'autor' => 'Kurt Kroenke, Robert L. Spitzer, Janet B. W. Williams',
            'anio_publicacion' => 2001,
            'dias_espera_reaplicacion' => 14,
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 10,
            'instrucciones' => 'Durante las últimas 2 semanas, ¿con qué frecuencia te has sentido molesto/a por los siguientes problemas?',
            'marco_temporal' => 'Últimas 2 semanas',
            'escalas' => $escalaLikert4,
            'preguntas' => [
                ['numero' => 1, 'enunciado' => 'Poco interés o placer en hacer las cosas.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 2, 'enunciado' => 'Sentirse desanimado/a, deprimido/a o sin esperanzas.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 3, 'enunciado' => 'Dificultades para conciliar el sueño, permanecer dormido/a, o dormir demasiado.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 4, 'enunciado' => 'Sentirse cansado/a o con poca energía.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 5, 'enunciado' => 'Tener poco apetito o comer en exceso.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 6, 'enunciado' => 'Sentirse mal consigo mismo/a — o sentirse fracasado/a o haber decepcionado a la familia o a uno mismo.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 7, 'enunciado' => 'Dificultad para concentrarse en cosas tales como leer o estudiar.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 8, 'enunciado' => 'Moverse o hablar tan lentamente que los demás lo noten, o lo contrario: estar tan inquieto/a que te mueves mucho más de lo habitual.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 9, 'enunciado' => 'Pensamientos de que sería mejor estar muerto/a o de hacerte daño de alguna manera.', 'es_inverso' => false, 'es_critico' => true],
            ],
            'baremos' => [
                ['min' => 0, 'max' => 4, 'nivel' => 'Mínimo', 'color' => '#10b981', 'orientacion' => false, 'msg' => 'Tus indicadores del estado de ánimo se sitúan en un rango de funcionamiento adaptativo y saludable.'],
                ['min' => 5, 'max' => 9, 'nivel' => 'Leve', 'color' => '#3b82f6', 'orientacion' => false, 'msg' => 'Presencia de variaciones afectivas leves o cansancio temporal. Mantén actividades recreativas, vínculos significativos y rutinas balanceadas.'],
                ['min' => 10, 'max' => 14, 'nivel' => 'Moderado', 'color' => '#f59e0b', 'orientacion' => true, 'msg' => 'Se aprecia un impacto perceptible en tu energía e interés cotidiano. Una charla de orientación en el Área de Bienestar puede brindarte estrategias prácticas de recuperación anímica.'],
                ['min' => 15, 'max' => 19, 'nivel' => 'Moderadamente severo', 'color' => '#f97316', 'orientacion' => true, 'msg' => 'Tus respuestas sugieren un periodo de desánimo notable y sobrecarga emocional. Es altamente recomendable acercarte al servicio de acompañamiento psicológico de la universidad.'],
                ['min' => 20, 'max' => 27, 'nivel' => 'Severo', 'color' => '#ef4444', 'orientacion' => true, 'msg' => 'Identificamos un nivel significativo de malestar emocional. La UPEMOR cuenta con espacios seguros, gratuitos y confidenciales para apoyarte. Recuerda que no estás solo/a.'],
            ],
        ]);

        // ==========================================
        // 3. PSS-10: Escala de Estrés Percibido
        // ==========================================
        $this->conciliarInstrumento([
            'codigo' => 'PSS-10',
            'nombre' => 'Escala de Estrés Percibido PSS-10',
            'siglas' => 'PSS-10',
            'categoria' => 'Estrés',
            'descripcion' => 'Medida psicométrica que evalúa el grado en que las situaciones de vida cotidiana son percibidas como impredecibles o sobrecargantes durante el último mes.',
            'autor' => 'Sheldon Cohen, Tom Kamarck, Robin Mermelstein',
            'anio_publicacion' => 1983,
            'dias_espera_reaplicacion' => 21,
            'duracion_estimada_min' => 6,
            'puntos_recompensa' => 10,
            'instrucciones' => 'Las siguientes preguntas indagan sobre tus sentimientos y pensamientos durante el último mes. Responde con qué frecuencia te has sentido de una determinada manera.',
            'marco_temporal' => 'Último mes',
            'escalas' => $escalaLikert5,
            'preguntas' => [
                ['numero' => 1, 'enunciado' => 'En el último mes, ¿con qué frecuencia has estado afectado/a por algo que ocurrió inesperadamente?', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 2, 'enunciado' => 'En el último mes, ¿con qué frecuencia has sentido que no podías controlar las cosas importantes en tu vida?', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 3, 'enunciado' => 'En el último mes, ¿con qué frecuencia has estado nervioso/a o estresado/a?', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 4, 'enunciado' => 'En el último mes, ¿con qué frecuencia has manejado con éxito los pequeños problemas de la vida cotidiana?', 'es_inverso' => true, 'es_critico' => false],
                ['numero' => 5, 'enunciado' => 'En el último mes, ¿con qué frecuencia has sentido que has afrontado con éxito los cambios importantes en tu vida?', 'es_inverso' => true, 'es_critico' => false],
                ['numero' => 6, 'enunciado' => 'En el último mes, ¿con qué frecuencia has estado seguro/a sobre tu capacidad para manejar tus problemas personales?', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 7, 'enunciado' => 'En el último mes, ¿con qué frecuencia has sentido que las cosas te van bien?', 'es_inverso' => true, 'es_critico' => false],
                ['numero' => 8, 'enunciado' => 'En el último mes, ¿con qué frecuencia has sentido que no podías afrontar todas las cosas que tenías que hacer?', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 9, 'enunciado' => 'En el último mes, ¿con qué frecuencia has podido controlar las dificultades de tu vida?', 'es_inverso' => true, 'es_critico' => false],
                ['numero' => 10, 'enunciado' => 'En el último mes, ¿con qué frecuencia has sentido que las dificultades se acumulaban tanto que no podías superarlas?', 'es_inverso' => false, 'es_critico' => false],
            ],
            'baremos' => [
                ['min' => 0, 'max' => 13, 'nivel' => 'Bajo', 'color' => '#10b981', 'orientacion' => false, 'msg' => 'Posees una percepción adecuada de control y autoeficacia para afrontar los desafíos académicos y personales.'],
                ['min' => 14, 'max' => 26, 'nivel' => 'Moderado', 'color' => '#f59e0b', 'orientacion' => false, 'msg' => 'Nivel de exigencia ambiental moderado, habitual durante periodos de entregas o exámenes. Se recomienda organizar tus tiempos y prioridades con el método Pomodoro.'],
                ['min' => 27, 'max' => 40, 'nivel' => 'Alto', 'color' => '#ef4444', 'orientacion' => true, 'msg' => 'Las demandas percibidas están superando tus recursos actuales de afrontamiento. Te sugerimos solicitar una sesión de tutoría u orientación para equilibrar tu carga académica.'],
            ],
        ]);

        // ==========================================
        // 4. WHO-5: Índice de Bienestar General de la OMS
        // ==========================================
        $this->conciliarInstrumento([
            'codigo' => 'WHO-5',
            'nombre' => 'Índice de Bienestar General de la OMS (WHO-5)',
            'siglas' => 'WHO-5',
            'categoria' => 'Bienestar general',
            'descripcion' => 'Cuestionario no patologizante de 5 reactivos que evalúa el bienestar subjetivo, vitalidad y descanso en las últimas dos semanas.',
            'autor' => 'Organización Mundial de la Salud (WHO Collaborating Centre)',
            'anio_publicacion' => 1998,
            'dias_espera_reaplicacion' => 14,
            'duracion_estimada_min' => 3,
            'puntos_recompensa' => 10,
            'instrucciones' => 'Indica para cada una de las 5 afirmaciones cómo te has sentido durante las últimas dos semanas.',
            'marco_temporal' => 'Últimas 2 semanas',
            'escalas' => $escalaLikert6,
            'preguntas' => [
                ['numero' => 1, 'enunciado' => 'Me he sentido alegre y de buen ánimo.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 2, 'enunciado' => 'Me he sentido tranquilo/a y relajado/a.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 3, 'enunciado' => 'Me he sentido activo/a y con energía.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 4, 'enunciado' => 'Me he despertado fresco/a y descansado/a.', 'es_inverso' => false, 'es_critico' => false],
                ['numero' => 5, 'enunciado' => 'Mi vida cotidiana ha estado llena de cosas que me interesan.', 'es_inverso' => false, 'es_critico' => false],
            ],
            'baremos' => [
                ['min' => 0, 'max' => 12, 'nivel' => 'Bienestar deficiente', 'color' => '#f59e0b', 'orientacion' => true, 'msg' => 'Tu índice de vitalidad y bienestar subjetivo refleja agotamiento reciente. Es una excelente oportunidad para consultar con el orientador estudiantil y recargar energías.'],
                ['min' => 13, 'max' => 25, 'nivel' => 'Bienestar adecuado', 'color' => '#10b981', 'orientacion' => false, 'msg' => 'Excelente nivel de bienestar general, vitalidad y armonía en tu vida universitaria cotidiana.'],
            ],
        ]);

        // ==========================================
        // 5. DEMO-HABITOS: Asegurar activo y publicado con denominación institucional
        // ==========================================
        $instDemo = InstrumentoEvaluacion::where('codigo', 'DEMO-HABITOS')->first();
        if ($instDemo) {
            $instDemo->update([
                'nombre' => 'Cuestionario de Hábitos de Estudio y Autocuidado',
                'siglas' => 'HAB-AUT',
                'categoria' => 'Orientación y Bienestar General',
                'descripcion' => 'Instrumento psicométrico de orientación académica y autocuidado estudiantil para evaluar hábitos de estudio, organización y bienestar general.',
                'activo' => true,
            ]);
            VersionInstrumento::where('instrumento_id', $instDemo->instrumento_id)
                ->where('version', '1.0.0')
                ->update(['estado' => 'PUBLICADA', 'es_vigente' => true]);
        }
    }

    private function conciliarInstrumento(array $datos): void
    {
        $instrumento = InstrumentoEvaluacion::updateOrCreate(
            ['codigo' => $datos['codigo']],
            [
                'nombre' => $datos['nombre'],
                'siglas' => $datos['siglas'],
                'categoria' => $datos['categoria'],
                'descripcion' => $datos['descripcion'],
                'autor' => $datos['autor'],
                'anio_publicacion' => $datos['anio_publicacion'],
                'dias_espera_reaplicacion' => $datos['dias_espera_reaplicacion'],
                'activo' => true,
            ]
        );

        $version = VersionInstrumento::where('instrumento_id', $instrumento->instrumento_id)
            ->where('version', '1.0.0')
            ->first();

        if (! $version) {
            // Se crea inicialmente en BORRADOR para poder asociarle preguntas y baremos sin violar inmutabilidad
            $version = VersionInstrumento::create([
                'instrumento_id' => $instrumento->instrumento_id,
                'version' => '1.0.0',
                'nombre_version' => 'Validación estándar institucional UPEMOR',
                'descripcion_cambios' => 'Versión psicométrica completa y autorizada para tamizajes estudiantiles.',
                'instrucciones' => $datos['instrucciones'],
                'marco_temporal' => $datos['marco_temporal'],
                'duracion_estimada_min' => $datos['duracion_estimada_min'],
                'puntos_recompensa' => $datos['puntos_recompensa'],
                'estado' => 'BORRADOR',
                'es_vigente' => true,
                'vigente_desde' => now(),
            ]);
        } else {
            // Si ya estaba en PUBLICADA, pasar temporalmente a BORRADOR para insertar reactivos faltantes si aplica
            DB::table('evaluaciones_versiones')
                ->where('version_id', $version->version_id)
                ->update(['estado' => 'BORRADOR']);
            $version->refresh();
        }

        // 1. Escalas
        if ($version->escalas()->count() === 0) {
            foreach ($datos['escalas'] as $esc) {
                EscalaOpcion::create([
                    'version_id' => $version->version_id,
                    'valor' => $esc['valor'],
                    'etiqueta' => $esc['etiqueta'],
                    'descripcion' => $esc['descripcion'],
                    'orden' => $esc['orden'],
                ]);
            }
        }

        // 2. Preguntas
        if ($version->preguntas()->count() === 0) {
            foreach ($datos['preguntas'] as $preg) {
                PreguntaEvaluacion::create([
                    'version_id' => $version->version_id,
                    'numero_reactivo' => $preg['numero'],
                    'enunciado' => $preg['enunciado'],
                    'es_inverso' => $preg['es_inverso'],
                    'es_critico' => $preg['es_critico'],
                    'orden' => $preg['numero'],
                    'activo' => true,
                ]);
            }
        }

        // 3. Baremos
        if ($version->baremos()->count() === 0) {
            $orden = 1;
            foreach ($datos['baremos'] as $bar) {
                BaremoRegla::create([
                    'version_id' => $version->version_id,
                    'puntuacion_min' => $bar['min'],
                    'puntuacion_max' => $bar['max'],
                    'nivel' => $bar['nivel'],
                    'color_hex' => $bar['color'],
                    'mensaje_retroalimentacion' => $bar['msg'],
                    'sugiere_orientacion' => $bar['orientacion'],
                    'orden' => $orden++,
                ]);
            }
        }

        // 4. Activar formalmente la versión como PUBLICADA
        DB::table('evaluaciones_versiones')
            ->where('version_id', $version->version_id)
            ->update([
                'estado' => 'PUBLICADA',
                'es_vigente' => true,
                'updated_at' => now(),
            ]);
    }
}

