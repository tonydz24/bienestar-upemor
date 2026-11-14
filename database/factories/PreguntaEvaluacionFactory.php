<?php

namespace Database\Factories;

use App\Models\PreguntaEvaluacion;
use App\Models\VersionInstrumento;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreguntaEvaluacionFactory extends Factory
{
    protected $model = PreguntaEvaluacion::class;

    public function definition(): array
    {
        return [
            'version_id' => VersionInstrumento::factory(),
            'numero_reactivo' => 1,
            'enunciado' => '¿Con qué frecuencia te has sentido con energía?',
            'es_inverso' => false,
            'es_critico' => false,
            'orden' => 1,
            'activo' => true,
        ];
    }
}

