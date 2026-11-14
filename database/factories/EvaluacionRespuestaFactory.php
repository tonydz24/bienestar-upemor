<?php

namespace Database\Factories;

use App\Models\EscalaOpcion;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\PreguntaEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionRespuestaFactory extends Factory
{
    protected $model = EvaluacionRespuesta::class;

    public function definition(): array
    {
        return [
            'intento_id' => EvaluacionIntento::factory(),
            'pregunta_id' => PreguntaEvaluacion::factory(),
            'escala_id' => EscalaOpcion::factory(),
            'puntos_obtenidos' => 1,
        ];
    }
}

