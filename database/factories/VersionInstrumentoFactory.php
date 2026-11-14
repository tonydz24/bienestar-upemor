<?php

namespace Database\Factories;

use App\Models\InstrumentoEvaluacion;
use App\Models\VersionInstrumento;
use Illuminate\Database\Eloquent\Factories\Factory;

class VersionInstrumentoFactory extends Factory
{
    protected $model = VersionInstrumento::class;

    public function definition(): array
    {
        return [
            'instrumento_id' => InstrumentoEvaluacion::factory(),
            'version' => '1.0.0',
            'nombre_version' => 'Versión estándar de prueba',
            'descripcion_cambios' => 'Versión inicial',
            'instrucciones' => 'Responde con sinceridad según cómo te has sentido en las últimas semanas.',
            'marco_temporal' => 'Últimas 2 semanas',
            'duracion_estimada_min' => 5,
            'puntos_recompensa' => 10,
            'estado' => 'BORRADOR',
            'es_vigente' => false,
            'vigente_desde' => now()->subDay(),
        ];
    }

    public function publicada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'PUBLICADA',
            'es_vigente' => true,
        ]);
    }
}

