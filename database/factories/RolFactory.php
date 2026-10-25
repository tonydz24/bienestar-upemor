<?php

namespace Database\Factories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rol>
 */
class RolFactory extends Factory
{
    protected $model = Rol::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'descripcion' => fake()->optional()->sentence(10),
            'activo' => true,
        ];
    }

    public function administrador(): static
    {
        return $this->state([
            'nombre' => 'Administrador',
            'descripcion' => 'Administrador de pruebas',
        ]);
    }

    public function docente(): static
    {
        return $this->state([
            'nombre' => 'Docente',
            'descripcion' => 'Docente de pruebas',
        ]);
    }

    public function alumno(): static
    {
        return $this->state([
            'nombre' => 'Alumno',
            'descripcion' => 'Alumno de pruebas',
        ]);
    }
}
