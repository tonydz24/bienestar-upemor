<?php

namespace Database\Factories;

use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\VersionInstrumento;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionIntentoFactory extends Factory
{
    protected $model = EvaluacionIntento::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Estudiante::factory(),
            'version_id' => VersionInstrumento::factory(),
            'estado' => 'EN_PROGRESO',
            'puntuacion_total' => null,
            'nivel_resultado' => null,
            'tiene_alerta_critica' => false,
            'fecha_inicio' => now(),
            'fecha_finalizacion' => null,
            'ip_registro' => '127.0.0.1',
        ];
    }

    public function finalizado(int $puntuacion = 5, string $nivel = 'Leve'): static
    {
        return $this->state(fn () => [
            'estado' => 'FINALIZADO',
            'puntuacion_total' => $puntuacion,
            'nivel_resultado' => $nivel,
            'fecha_finalizacion' => now(),
        ]);
    }
}

