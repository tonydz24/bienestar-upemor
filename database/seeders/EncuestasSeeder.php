<?php

namespace Database\Seeders;

use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use App\Models\User;
use Illuminate\Database\Seeder;

class EncuestasSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('rol', fn($q) => $q->where('nombre', 'Administrador'))->first();
        $adminId = $admin?->usuario_id;

        // 1. Encuesta de Satisfacción General
        $encuestaGeneral = Encuesta::updateOrCreate(
            ['slug' => 'satisfaccion-general-plataforma-bienestar'],
            [
                'titulo' => 'Encuesta de Satisfacción General de Bienestar UPEMOR',
                'descripcion' => 'Tu opinión es esencial para mejorar la experiencia institucional y el acompañamiento universitario. Esta encuesta es totalmente anónima.',
                'objetivo' => 'Evaluar la usabilidad de la plataforma, utilidad de los recursos emocionales y percepción general del estudiante.',
                'estado' => Encuesta::ESTADO_PUBLICADA,
                'tipo' => Encuesta::TIPO_GENERAL,
                'disponible_desde' => now()->startOfYear(),
                'disponible_hasta' => now()->addYear(),
                'es_anonima' => true,
                'permite_multiples_respuestas' => false,
                'creado_por' => $adminId,
            ]
        );

        // Preguntas Encuesta General
        $encuestaGeneral->preguntas()->delete();
        $preguntasGeneral = [
            [
                'orden' => 1,
                'texto_pregunta' => 'La plataforma Bienestar UPEMOR me resulta intuitiva, clara y fácil de navegar.',
                'tipo_pregunta' => EncuestaPregunta::TIPO_LIKERT,
                'es_obligatoria' => true,
                'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
            ],
            [
                'orden' => 2,
                'texto_pregunta' => 'Los recursos de bienestar (evaluaciones psicológicas, bitácora y relajación guiada) me ayudan en mi vida académica.',
                'tipo_pregunta' => EncuestaPregunta::TIPO_LIKERT,
                'es_obligatoria' => true,
                'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
            ],
            [
                'orden' => 3,
                'texto_pregunta' => '¿Qué tan satisfecho(a) te encuentras con la rapidez y diseño general de la plataforma? (Escala 1 al 10)',
                'tipo_pregunta' => EncuestaPregunta::TIPO_VALORACION,
                'es_obligatoria' => true,
                'opciones' => ['min' => 1, 'max' => 10],
            ],
            [
                'orden' => 4,
                'texto_pregunta' => '¿Con qué frecuencia utilizas o consultas las herramientas de Bienestar UPEMOR?',
                'tipo_pregunta' => EncuestaPregunta::TIPO_OPCION_UNICA,
                'es_obligatoria' => true,
                'opciones' => [
                    ['valor' => 'diario', 'etiqueta' => 'Diariamente'],
                    ['valor' => 'varias_semana', 'etiqueta' => 'Varias veces por semana'],
                    ['valor' => 'semanal', 'etiqueta' => 'Una vez por semana'],
                    ['valor' => 'quincenal_mensual', 'etiqueta' => 'Ocasionalmente (1 o 2 veces al mes)'],
                    ['valor' => 'primera_vez', 'etiqueta' => 'Es mi primera vez en la plataforma'],
                ],
            ],
            [
                'orden' => 5,
                'texto_pregunta' => '¿Qué funciones, herramientas o temas te gustaría que se agregaran en próximas versiones?',
                'tipo_pregunta' => EncuestaPregunta::TIPO_TEXTO_LIBRE,
                'es_obligatoria' => false,
                'opciones' => null,
            ],
        ];

        foreach ($preguntasGeneral as $p) {
            $encuestaGeneral->preguntas()->create($p);
        }

        // 2. Encuesta Post-Cita de Orientación
        $encuestaCitas = Encuesta::updateOrCreate(
            ['slug' => 'satisfaccion-servicio-orientacion-psicologica'],
            [
                'titulo' => 'Evaluación de Calidad del Servicio de Orientación',
                'descripcion' => 'Agradecemos tu tiempo para responder este breve cuestionario sobre el acompañamiento recibido. La información es estrictamente confidencial y se procesa de forma estadística.',
                'objetivo' => 'Conocer la percepción de calidad, empatía y utilidad de la atención brindada por el equipo de orientación.',
                'estado' => Encuesta::ESTADO_PUBLICADA,
                'tipo' => Encuesta::TIPO_POST_CITA,
                'disponible_desde' => now()->startOfYear(),
                'disponible_hasta' => now()->addYear(),
                'es_anonima' => true,
                'permite_multiples_respuestas' => true,
                'creado_por' => $adminId,
            ]
        );

        $encuestaCitas->preguntas()->delete();
        $preguntasCitas = [
            [
                'orden' => 1,
                'texto_pregunta' => 'El orientador me brindó un trato respetuoso, empático y profesional.',
                'tipo_pregunta' => EncuestaPregunta::TIPO_LIKERT,
                'es_obligatoria' => true,
                'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
            ],
            [
                'orden' => 2,
                'texto_pregunta' => 'La puntualidad y el espacio o modalidad destinada a la sesión fueron adecuados.',
                'tipo_pregunta' => EncuestaPregunta::TIPO_LIKERT,
                'es_obligatoria' => true,
                'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
            ],
            [
                'orden' => 3,
                'texto_pregunta' => 'Los acuerdos y reflexiones de la sesión resultaron claros y útiles para mi bienestar.',
                'tipo_pregunta' => EncuestaPregunta::TIPO_LIKERT,
                'es_obligatoria' => true,
                'opciones' => EncuestaPregunta::opcionesLikertEstandar(),
            ],
            [
                'orden' => 4,
                'texto_pregunta' => 'Calificación general de la sesión (1 al 5 estrellas)',
                'tipo_pregunta' => EncuestaPregunta::TIPO_VALORACION,
                'es_obligatoria' => true,
                'opciones' => ['min' => 1, 'max' => 5],
            ],
            [
                'orden' => 5,
                'texto_pregunta' => 'Sugerencias para fortalecer el área de orientación de UPEMOR',
                'tipo_pregunta' => EncuestaPregunta::TIPO_TEXTO_LIBRE,
                'es_obligatoria' => false,
                'opciones' => null,
            ],
        ];

        foreach ($preguntasCitas as $p) {
            $encuestaCitas->preguntas()->create($p);
        }
    }
}

