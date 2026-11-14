<?php

namespace Database\Factories;

use App\Models\BitacoraEmocional;
use App\Models\Emocion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BitacoraEmocionalFactory extends Factory
{
    protected $model = BitacoraEmocional::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'emocion_id' => Emocion::factory(),
            'fecha' => fake()->date(),
            'intensidad' => fake()->numberBetween(1, 5),
            'descripcion' => fake()->sentence(),
            'observaciones' => fake()->paragraph(),
        ];
    }
}

