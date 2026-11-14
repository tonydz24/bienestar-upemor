<?php

namespace Database\Seeders;

use App\Models\Meditacion;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeditacionesVideosSeeder extends Seeder
{
    /**
     * Precarga idempotente de recursos multimedia interactivos de meditación y mindfulness (FN.6 y FN.11).
     */
    public function run(): void
    {
        $admin = User::whereHas('rol', fn($q) => $q->where('nombre', 'Administrador'))->first();
        $adminId = $admin?->usuario_id;

        $videos = [
            [
                'titulo' => 'Respiración 4-7-8 para Reducir la Ansiedad',
                'slug' => 'respiracion-4-7-8-para-reducir-la-ansiedad',
                'categoria' => Meditacion::CATEGORIA_RESPIRACION,
                'descripcion' => 'Técnica guiada de respiración 4-7-8 para calmar el sistema nervioso y disminuir la ansiedad.',
                'duracion_minutos' => 5,
                'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
                'icono' => 'bi-lungs',
                'es_publicado' => true,
                'vistas_count' => 24,
                'creado_por' => $adminId,
                'instrucciones' => [
                    'tipo_contenido' => Meditacion::TIPO_VIDEO,
                    'media_url' => 'https://www.youtube.com/watch?v=EGO5m_DBzF8',
                    'autor' => 'Gabriela Litschi',
                    'puntos' => 2,
                    'transcripcion' => "En esta práctica guiada por Gabriela Litschi aprenderás el método 4-7-8 del Dr. Andrew Weil:\n"
                        . "1. Adopta una postura sentada con la espalda erguida.\n"
                        . "2. Inhala por la nariz suavemente durante 4 segundos.\n"
                        . "3. Retén el aire en tus pulmones durante 7 segundos con serenidad.\n"
                        . "4. Exhala completamente por la boca durante 8 segundos emitiendo un sonido suave.\n"
                        . "Este patrón rítmico activa el sistema nervioso parasimpático, desacelerando el ritmo cardíaco y disipando el estrés.",
                ],
            ],
            [
                'titulo' => '6 Pequeños Hábitos Para Mejorar tu Bienestar Mental',
                'slug' => '6-pequenos-habitos-para-mejorar-tu-bienestar-mental',
                'categoria' => Meditacion::CATEGORIA_MINDFULNESS,
                'descripcion' => 'Hábitos cotidianos fundamentados en psicología para fortalecer el equilibrio emocional.',
                'duracion_minutos' => 6,
                'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
                'icono' => 'bi-sun',
                'es_publicado' => true,
                'vistas_count' => 31,
                'creado_por' => $adminId,
                'instrucciones' => [
                    'tipo_contenido' => Meditacion::TIPO_VIDEO,
                    'media_url' => 'https://www.youtube.com/watch?v=0A3Dr2Sc02g',
                    'autor' => 'Psych2Go',
                    'puntos' => 2,
                    'transcripcion' => "Resumen psicoeducativo de hábitos de salud mental por Psych2Go:\n"
                        . "1. Validar y aceptar tus emociones sin juzgarte.\n"
                        . "2. Establecer pausas conscientes de respiración durante tu jornada académica.\n"
                        . "3. Mantener límites claros con el uso nocturno de pantallas y redes sociales.\n"
                        . "4. Practicar la gratitud activa reconociendo un momento positivo al día.\n"
                        . "5. Conectar de manera genuina con personas de confianza.\n"
                        . "6. Recordar que solicitar orientación psicológica es un acto de valentía y autocuidado.",
                ],
            ],
        ];

        foreach ($videos as $videoData) {
            Meditacion::updateOrCreate(
                ['slug' => $videoData['slug']],
                $videoData
            );
        }
    }
}

