<?php

namespace Database\Factories;

use App\Models\ProgramaEducativo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramaEducativo>
 */
class ProgramaEducativoFactory extends Factory
{
    protected $model = ProgramaEducativo::class;

    public function definition(): array
    {
        return [
            'clave' => fake()->unique()->lexify('???'),
            'nombre' => fake()->words(3, true),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
