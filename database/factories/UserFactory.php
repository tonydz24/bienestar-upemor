<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rol_id' => Rol::factory()->alumno(),
            'nombre' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make(env('TEST_USER_PASSWORD', 'testing-only-password')),
            'remember_token' => Str::random(10),
            'estado' => 'ACTIVO',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function administrador(): static
    {
        return $this->state([
            'rol_id' => Rol::factory()->administrador(),
        ]);
    }

    public function docente(): static
    {
        return $this->state([
            'rol_id' => Rol::factory()->docente(),
        ]);
    }

    public function alumno(): static
    {
        return $this->state([
            'rol_id' => Rol::factory()->alumno(),
        ]);
    }

    public function withRole(Rol $rol): static
    {
        return $this->for($rol, 'rol');
    }
}
