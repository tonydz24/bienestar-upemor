<?php

namespace Database\Factories;

use App\Models\EscalaOpcion;
use App\Models\VersionInstrumento;
use Illuminate\Database\Eloquent\Factories\Factory;

class EscalaOpcionFactory extends Factory
{
    protected $model = EscalaOpcion::class;

    public function definition(): array
    {
        return [
            'version_id' => VersionInstrumento::factory(),
            'valor' => 0,
            'etiqueta' => 'Nunca',
            'descripcion' => 'Para nada',
            'orden' => 1,
        ];
    }
}

