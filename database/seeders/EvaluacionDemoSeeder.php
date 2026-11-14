<?php

namespace Database\Seeders;

use App\Models\BaremoRegla;
use App\Models\EscalaOpcion;
use App\Models\InstrumentoEvaluacion;
use App\Models\PreguntaEvaluacion;
use App\Models\VersionInstrumento;
use Illuminate\Database\Seeder;

class EvaluacionDemoSeeder extends Seeder
{
    /**
     * Cuestionario demostrativo con fines de verificación funcional e interactiva.
     * Preguntas no clínicas sobre hábitos de estudio y vida universitaria.
     * Exclusivo para entornos de prueba y sandbox (no clínico).
     */
    public function run(): void
    {
        $instrumento = InstrumentoEvaluacion::updateOrCreate(
            ['codigo' => 'DEMO-HABITOS'],
            [
                'nombre' => 'Cuestionario de Hábitos de Estudio y Autocuidado',
                'siglas' => 'HAB-AUT',
                'categoria' => 'Orientación y Bienestar General',
                'descripcion' => 'Instrumento psicométrico de orientación académica y autocuidado estudiantil para evaluar hábitos de estudio, organización y bienestar general.',
                'autor' => 'Área de Desarrollo y Bienestar Estudiantil UPEMOR',
                'anio_publicacion' => 2026,
                'dias_espera_reaplicacion' => 7,
                'activo' => true,
                'orden' => 5,
            ]
        );

        $version = VersionInstrumento::where('instrumento_id', $instrumento->instrumento_id)
            ->where('version', '1.0.0')
            ->first();

        if (! $version) {
            $version = VersionInstrumento::create([
                'instrumento_id' => $instrumento->instrumento_id,
                'version' => '1.0.0',
                'nombre_version' => 'Versión interactiva de verificación técnica',
                'descripcion_cambios' => 'Instrumento demostrativo para pruebas de flujo de usuario y persistencia.',
                'instrucciones' => 'A continuación encontrarás una serie de preguntas sobre tu rutina académica cotidiana. Selecciona la opción que mejor represente tu experiencia en el último mes de clases.',
                'marco_temporal' => 'Último mes de clases',
                'duracion_estimada_min' => 3,
                'puntos_recompensa' => 0,
                'estado' => 'BORRADOR',
                'es_vigente' => true,
                'vigente_desde' => now()->subDay(),
            ]);

            // Escala tipo Likert de 4 niveles
            $escalas = [
                ['valor' => 0, 'etiqueta' => 'Casi nunca', 'descripcion' => 'Ocurre en muy pocas ocasiones', 'orden' => 1],
                ['valor' => 1, 'etiqueta' => 'A veces', 'descripcion' => 'Ocurre ocasionalmente', 'orden' => 2],
                ['valor' => 2, 'etiqueta' => 'Frecuentemente', 'descripcion' => 'Ocurre de manera habitual', 'orden' => 3],
                ['valor' => 3, 'etiqueta' => 'Casi siempre', 'descripcion' => 'Es mi práctica cotidiana constante', 'orden' => 4],
            ];

            foreach ($escalas as $escalaData) {
                EscalaOpcion::create([
                    'version_id' => $version->version_id,
                    'valor' => $escalaData['valor'],
                    'etiqueta' => $escalaData['etiqueta'],
                    'descripcion' => $escalaData['descripcion'],
                    'orden' => $escalaData['orden'],
                ]);
            }

            // Preguntas no clínicas sobre organización académica
            $preguntas = [
                [
                    'numero_reactivo' => 1,
                    'enunciado' => '¿Planificas un horario fijo o calendario para tus sesiones de estudio antes de un examen?',
                    'orden' => 1,
                ],
                [
                    'numero_reactivo' => 2,
                    'enunciado' => '¿Tomas apuntes o realizas esquemas de estudio para organizar la información de tus materias?',
                    'orden' => 2,
                ],
                [
                    'numero_reactivo' => 3,
                    'enunciado' => '¿Eliminas distractores digitales (notificaciones, redes sociales) mientras estudias o realizas tareas?',
                    'orden' => 3,
                ],
                [
                    'numero_reactivo' => 4,
                    'enunciado' => '¿Haces pausas breves de descanso entre bloques de estudio prolongados?',
                    'orden' => 4,
                ],
                [
                    'numero_reactivo' => 5,
                    'enunciado' => '¿Revisas el material de clase o pides asesoría docente con anticipación ante dudas académicas?',
                    'orden' => 5,
                ],
            ];

            foreach ($preguntas as $pregData) {
                PreguntaEvaluacion::create([
                    'version_id' => $version->version_id,
                    'numero_reactivo' => $pregData['numero_reactivo'],
                    'enunciado' => $pregData['enunciado'],
                    'orden' => $pregData['orden'],
                    'activo' => true,
                ]);
            }

            // Baremos demostrativos orientados a técnicas de estudio (no clínicos)
            $baremos = [
                [
                    'puntuacion_min' => 0,
                    'puntuacion_max' => 5,
                    'nivel' => 'Hábitos en desarrollo',
                    'color_hex' => '#f59e0b',
                    'mensaje_retroalimentacion' => 'Identificas oportunidades importantes para estructurar un calendario de estudio, reducir distractores y organizar tus apuntes con mayor anticipación.',
                    'sugiere_orientacion' => false,
                    'orden' => 1,
                ],
                [
                    'puntuacion_min' => 6,
                    'puntuacion_max' => 10,
                    'nivel' => 'Hábitos intermedios',
                    'color_hex' => '#6366f1',
                    'mensaje_retroalimentacion' => 'Mantienes una rutina organizativa aceptable en varias materias. Puedes optimizar tu rendimiento incorporando pausas activas y solicitando asesorías docentes preventivas.',
                    'sugiere_orientacion' => false,
                    'orden' => 2,
                ],
                [
                    'puntuacion_min' => 11,
                    'puntuacion_max' => 15,
                    'nivel' => 'Hábitos consolidados',
                    'color_hex' => '#10b981',
                    'mensaje_retroalimentacion' => 'Demuestras excelentes estrategias de planificación, gestión de distractores digitales y constancia en la preparación de tus actividades académicas.',
                    'sugiere_orientacion' => false,
                    'orden' => 3,
                ],
            ];

            foreach ($baremos as $baremoData) {
                BaremoRegla::create([
                    'version_id' => $version->version_id,
                    'puntuacion_min' => $baremoData['puntuacion_min'],
                    'puntuacion_max' => $baremoData['puntuacion_max'],
                    'nivel' => $baremoData['nivel'],
                    'color_hex' => $baremoData['color_hex'],
                    'mensaje_retroalimentacion' => $baremoData['mensaje_retroalimentacion'],
                    'sugiere_orientacion' => $baremoData['sugiere_orientacion'],
                    'orden' => $baremoData['orden'],
                ]);
            }

            // Publicar formalmente la versión
            $version->update(['estado' => 'PUBLICADA']);
        } elseif ($version && $version->baremos()->count() === 0) {
            // Si la versión ya existía previamente sin baremos, registrarlos
            $baremos = [
                [
                    'puntuacion_min' => 0,
                    'puntuacion_max' => 5,
                    'nivel' => 'Hábitos en desarrollo',
                    'color_hex' => '#f59e0b',
                    'mensaje_retroalimentacion' => 'Identificas oportunidades importantes para estructurar un calendario de estudio, reducir distractores y organizar tus apuntes con mayor anticipación.',
                    'sugiere_orientacion' => false,
                    'orden' => 1,
                ],
                [
                    'puntuacion_min' => 6,
                    'puntuacion_max' => 10,
                    'nivel' => 'Hábitos intermedios',
                    'color_hex' => '#6366f1',
                    'mensaje_retroalimentacion' => 'Mantienes una rutina organizativa aceptable en varias materias. Puedes optimizar tu rendimiento incorporando pausas activas y solicitando asesorías docentes preventivas.',
                    'sugiere_orientacion' => false,
                    'orden' => 2,
                ],
                [
                    'puntuacion_min' => 11,
                    'puntuacion_max' => 15,
                    'nivel' => 'Hábitos consolidados',
                    'color_hex' => '#10b981',
                    'mensaje_retroalimentacion' => 'Demuestras excelentes estrategias de planificación, gestión de distractores digitales y constancia en la preparación de tus actividades académicas.',
                    'sugiere_orientacion' => false,
                    'orden' => 3,
                ],
            ];

            foreach ($baremos as $baremoData) {
                BaremoRegla::create([
                    'version_id' => $version->version_id,
                    'puntuacion_min' => $baremoData['puntuacion_min'],
                    'puntuacion_max' => $baremoData['puntuacion_max'],
                    'nivel' => $baremoData['nivel'],
                    'color_hex' => $baremoData['color_hex'],
                    'mensaje_retroalimentacion' => $baremoData['mensaje_retroalimentacion'],
                    'sugiere_orientacion' => $baremoData['sugiere_orientacion'],
                    'orden' => $baremoData['orden'],
                ]);
            }
        }
    }
}
