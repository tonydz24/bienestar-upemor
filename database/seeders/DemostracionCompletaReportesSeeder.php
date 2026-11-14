<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemostracionCompletaReportesSeeder extends Seeder
{
    /**
     * Poblado de demostración integral para probar los 4 roles y los 6 reportes institucionales.
     * Ejecuta el catálogo curricular completo, las baterías clínicas y los datos sintéticos de prueba.
     */
    public function run(): void
    {
        $this->command?->info('Iniciando poblado de demostración integral (Escenario B)...');

        // 1. Estructura curricular institucional (Planes de estudio y asignaturas por cuatrimestre)
        $this->call(InstitucionalCurricularSeeder::class);

        // 2. Baterías clínicas y baremos psicométricos completos
        $this->call(EvaluacionesClinicasCompletasSeeder::class);

        // 3. Estudiantes y actividades sintéticas trazables para reportes
        $this->call(Incremento22DemostracionSeeder::class);

        $this->command?->info('Poblado de demostración completado con éxito.');
    }
}

