<?php

namespace Database\Factories;

use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\User;
use App\Models\VersionInstrumento;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionConsentimientoFactory extends Factory
{
    protected $model = EvaluacionConsentimiento::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'version_id' => VersionInstrumento::factory(),
            'intento_id' => null,
            'consentimiento_version' => 'v1.0-provisional',
            'texto_consentimiento' => 'He leído las instrucciones y consiento en participar de forma voluntaria en este cuestionario de bienestar.',
            'fecha_consentimiento' => now(),
            'ip_registro' => '127.0.0.1',
        ];
    }
}

