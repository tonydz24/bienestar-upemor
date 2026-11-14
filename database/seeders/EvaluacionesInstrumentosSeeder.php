<?php

namespace Database\Seeders;

use App\Models\InstrumentoEvaluacion;
use App\Models\VersionInstrumento;
use Illuminate\Database\Seeder;

class EvaluacionesInstrumentosSeeder extends Seeder
{
    /**
     * Catálogo inicial de instrumentos psicométricos estandarizados con metadatos
     * académicos rigurosamente verificados. Los reactivos clínicos específicos
     * permanecen en estado BORRADOR a la espera de autorización colegiada.
     */
    public const INSTRUMENTOS_INICIALES = [
        [
            'codigo' => 'GAD-7',
            'nombre' => 'Escala de Ansiedad Generalizada',
            'siglas' => 'GAD-7',
            'categoria' => 'Ansiedad',
            'descripcion' => 'Instrumento breve de tamizaje de 7 reactivos para evaluar sintomatología de ansiedad generalizada en las últimas dos semanas.',
            'autor' => 'Robert L. Spitzer, Kurt Kroenke, Janet B. W. Williams, Bernd Löwe',
            'anio_publicacion' => 2006,
            'dias_espera_reaplicacion' => 14,
            'activo' => true,
            'orden' => 1,
            'version_inicial' => [
                'version' => '1.0.0',
                'nombre_version' => 'Validación estándar en español (García-Campayo et al., 2010)',
                'descripcion_cambios' => 'Alta inicial de metadatos del instrumento.',
                'instrucciones' => 'Durante las últimas 2 semanas, ¿con qué frecuencia te has sentido molesto/a por los siguientes problemas?',
                'marco_temporal' => 'Últimas 2 semanas',
                'duracion_estimada_min' => 4,
                'puntos_recompensa' => 10,
                'estado' => 'BORRADOR',
                'es_vigente' => true,
            ],
        ],
        [
            'codigo' => 'PHQ-9',
            'nombre' => 'Cuestionario de Salud del Paciente',
            'siglas' => 'PHQ-9',
            'categoria' => 'Estado de ánimo',
            'descripcion' => 'Cuestionario estandarizado de 9 reactivos para evaluar sintomatología del estado de ánimo y depresión durante las últimas dos semanas.',
            'autor' => 'Kurt Kroenke, Robert L. Spitzer, Janet B. W. Williams',
            'anio_publicacion' => 2001,
            'dias_espera_reaplicacion' => 14,
            'activo' => true,
            'orden' => 2,
            'version_inicial' => [
                'version' => '1.0.0',
                'nombre_version' => 'Validación en español para atención y población universitaria',
                'descripcion_cambios' => 'Alta inicial de metadatos del instrumento.',
                'instrucciones' => 'Durante las últimas 2 semanas, ¿con qué frecuencia te has sentido molesto/a por los siguientes problemas?',
                'marco_temporal' => 'Últimas 2 semanas',
                'duracion_estimada_min' => 5,
                'puntos_recompensa' => 10,
                'estado' => 'BORRADOR',
                'es_vigente' => true,
            ],
        ],
        [
            'codigo' => 'PSS-10',
            'nombre' => 'Escala de Estrés Percibido',
            'siglas' => 'PSS-10',
            'categoria' => 'Estrés',
            'descripcion' => 'Medida psicométrica que evalúa el grado en que las situaciones de vida son percibidas como impredecibles o sobrecargantes durante el último mes. No constituye una escala diagnóstica ni asigna categorías de trastorno clínico.',
            'autor' => 'Sheldon Cohen, Tom Kamarck, Robin Mermelstein',
            'anio_publicacion' => 1983,
            'dias_espera_reaplicacion' => 21,
            'activo' => true,
            'orden' => 3,
            'version_inicial' => [
                'version' => '1.0.0',
                'nombre_version' => 'Validación psicométrica mexicana (González & Landero, 2007)',
                'descripcion_cambios' => 'Alta inicial de metadatos del instrumento.',
                'instrucciones' => 'Las siguientes preguntas indagan sobre tus sentimientos y pensamientos durante el último mes. Responde con qué frecuencia te has sentido de una determinada manera.',
                'marco_temporal' => 'Último mes',
                'duracion_estimada_min' => 6,
                'puntos_recompensa' => 10,
                'estado' => 'BORRADOR',
                'es_vigente' => true,
            ],
        ],
        [
            'codigo' => 'WHO-5',
            'nombre' => 'Índice de Bienestar General de la OMS',
            'siglas' => 'WHO-5',
            'categoria' => 'Bienestar general',
            'descripcion' => 'Cuestionario positivo y no patologizante que mide el bienestar psicológico subjetivo, la vitalidad y el estado de ánimo general en las últimas dos semanas.',
            'autor' => 'World Health Organization (WHO Collaborating Centre for Mental Health)',
            'anio_publicacion' => 1998,
            'dias_espera_reaplicacion' => 14,
            'activo' => true,
            'orden' => 4,
            'version_inicial' => [
                'version' => '1.0.0',
                'nombre_version' => 'Versión oficial en español de la Organización Mundial de la Salud',
                'descripcion_cambios' => 'Alta inicial de metadatos del instrumento.',
                'instrucciones' => 'Indica para cada una de las 5 afirmaciones cómo te has sentido durante las últimas dos semanas.',
                'marco_temporal' => 'Últimas 2 semanas',
                'duracion_estimada_min' => 3,
                'puntos_recompensa' => 10,
                'estado' => 'BORRADOR',
                'es_vigente' => true,
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::INSTRUMENTOS_INICIALES as $data) {
            $versionData = $data['version_inicial'];
            unset($data['version_inicial']);

            $instrumento = InstrumentoEvaluacion::updateOrCreate(
                ['codigo' => $data['codigo']],
                $data
            );

            VersionInstrumento::updateOrCreate(
                [
                    'instrumento_id' => $instrumento->instrumento_id,
                    'version' => $versionData['version'],
                ],
                array_merge($versionData, [
                    'instrumento_id' => $instrumento->instrumento_id,
                    'vigente_desde' => now(),
                ])
            );
        }
    }
}

