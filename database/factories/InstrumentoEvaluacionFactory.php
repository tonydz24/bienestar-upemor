<?php

namespace Database\Factories;

use App\Models\InstrumentoEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstrumentoEvaluacionFactory extends Factory
{
    protected $model = InstrumentoEvaluacion::class;

    public function definition(): array
    {
        return [
            'codigo' => 'TEST-' . fake()->unique()->numerify('###'),
            'nombre' => 'Instrumento de Prueba ' . fake()->words(2, true),
            'siglas' => 'ITP',
            'categoria' => 'Bienestar general',
            'descripcion' => fake()->sentence(),
            'autor' => fake()->name(),
            'anio_publicacion' => 2020,
            'dias_espera_reaplicacion' => 14,
            'activo' => true,
            'orden' => 1,
        ];
    }
}

