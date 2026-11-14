<?php

namespace Database\Factories;

use App\Models\BaremoRegla;
use App\Models\VersionInstrumento;
use Illuminate\Database\Eloquent\Factories\Factory;

class BaremoReglaFactory extends Factory
{
    protected $model = BaremoRegla::class;

    public function definition(): array
    {
        return [
            'version_id' => VersionInstrumento::factory(),
            'puntuacion_min' => 0,
            'puntuacion_max' => 4,
            'nivel' => 'Mínimo',
            'color_hex' => '#10b981',
            'mensaje_retroalimentacion' => 'Tu nivel reportado se encuentra dentro del rango habitual.',
            'sugiere_orientacion' => false,
            'orden' => 1,
        ];
    }
}

