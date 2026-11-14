<?php

namespace Database\Factories;

use App\Models\Emocion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmocionFactory extends Factory
{
    protected $model = Emocion::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'descripcion' => fake()->sentence(),
            'icono' => '😊',
            'color_hex' => fake()->hexColor(),
            'activo' => true,
            'orden' => fake()->numberBetween(1, 10),
        ];
    }
}

