<?php

namespace Database\Seeders;

use App\Models\Meditacion;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeditacionesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('rol', fn($q) => $q->where('nombre', 'Administrador'))->first();
        $adminId = $admin?->usuario_id;

        $meditaciones = [
            [
                'titulo' => 'Respiración Cuadrada 4-4-4-4',
                'slug' => 'respiracion-cuadrada-4-4-4-4',
                'categoria' => Meditacion::CATEGORIA_RESPIRACION,
                'descripcion' => 'Técnica de regulación autonómica para reducir la ansiedad aguda, estabilizar el ritmo cardíaco y restaurar el enfoque mental antes de un examen o entrega de proyecto.',
                'duracion_minutos' => 4,
                'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
                'icono' => 'bi-lungs',
                'es_publicado' => true,
                'vistas_count' => 18,
                'creado_por' => $adminId,
                'instrucciones' => [
                    [
                        'paso' => 1,
                        'titulo' => 'Postura y Enfoque Inicial',
                        'duracion_segundos' => 15,
                        'fase' => 'preparacion',
                        'indicacion' => 'Siéntate con la espalda recta y los hombros relajados. Cierra los ojos o fija la mirada en el círculo guía.',
                    ],
                    [
                        'paso' => 2,
                        'titulo' => 'Inhalación Profunda',
                        'duracion_segundos' => 4,
                        'fase' => 'inhala',
                        'indicacion' => 'Inhala lentamente por la nariz llenando tus pulmones por completo.',
                    ],
                    [
                        'paso' => 3,
                        'titulo' => 'Sostén con Plenitud',
                        'duracion_segundos' => 4,
                        'fase' => 'sosten',
                        'indicacion' => 'Mantén el aire con calma y serenidad, sin forzar.',
                    ],
                    [
                        'paso' => 4,
                        'titulo' => 'Exhalación Continua',
                        'duracion_segundos' => 4,
                        'fase' => 'exhala',
                        'indicacion' => 'Exhala todo el aire suavemente por la boca, liberando toda tensión.',
                    ],
                    [
                        'paso' => 5,
                        'titulo' => 'Pausa en Vacío',
                        'duracion_segundos' => 4,
                        'fase' => 'pausa',
                        'indicacion' => 'Mantén los pulmones vacíos con tranquilidad antes del siguiente ciclo.',
                    ],
                    [
                        'paso' => 6,
                        'titulo' => 'Ciclo de Consolidación',
                        'duracion_segundos' => 20,
                        'fase' => 'cierre',
                        'indicacion' => 'Respira a tu ritmo natural y siente la calma en tu mente y tu cuerpo.',
                    ],
                ],
            ],
            [
                'titulo' => 'Relajación Muscular Progresiva',
                'slug' => 'relajacion-muscular-progresiva',
                'categoria' => Meditacion::CATEGORIA_RELAJACION_MUSCULAR,
                'descripcion' => 'Secuencia de tensión controlada y distensión consciente basada en el método Jacobson para aliviar la carga física producida por largas horas de estudio.',
                'duracion_minutos' => 6,
                'nivel' => Meditacion::NIVEL_INTERMEDIO,
                'icono' => 'bi-heart-pulse',
                'es_publicado' => true,
                'vistas_count' => 12,
                'creado_por' => $adminId,
                'instrucciones' => [
                    [
                        'paso' => 1,
                        'titulo' => 'Preparación Corporal',
                        'duracion_segundos' => 15,
                        'fase' => 'preparacion',
                        'indicacion' => 'Ubica una posición cómoda en tu silla o sillón, apoyando la planta de los pies firmemente en el suelo.',
                    ],
                    [
                        'paso' => 2,
                        'titulo' => 'Hombros y Cuello',
                        'duracion_segundos' => 30,
                        'fase' => 'tension_relajacion',
                        'indicacion' => 'Sube los hombros hacia las orejas durante 5 segundos sintiendo la tensión, y luego suéltalos de golpe dejando caer el peso.',
                    ],
                    [
                        'paso' => 3,
                        'titulo' => 'Manos y Antebrazos',
                        'duracion_segundos' => 30,
                        'fase' => 'tension_relajacion',
                        'indicacion' => 'Aprieta los puños con fuerza moderada durante 5 segundos y ábrelos despacio, observando la sensación de calor.',
                    ],
                    [
                        'paso' => 4,
                        'titulo' => 'Zona Abdominal y Espalda',
                        'duracion_segundos' => 30,
                        'fase' => 'tension_relajacion',
                        'indicacion' => 'Contrae el abdomen suavemente y libéralo exhalando un suspiro profundo.',
                    ],
                    [
                        'paso' => 5,
                        'titulo' => 'Integración Total',
                        'duracion_segundos' => 25,
                        'fase' => 'cierre',
                        'indicacion' => 'Permite que todo tu cuerpo descanse libre de cargas, disfrutando de la pesadez placentera.',
                    ],
                ],
            ],
            [
                'titulo' => 'Pausa de Estudio Pomodoro & Calma',
                'slug' => 'pausa-de-estudio-pomodoro-calma',
                'categoria' => Meditacion::CATEGORIA_PAUSA_ESTUDIO,
                'descripcion' => 'Reinicio atencional de 5 minutos diseñado para alternar entre bloques de estudio intensivo, reduciendo la fatiga visual y el agotamiento cognitivo.',
                'duracion_minutos' => 5,
                'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
                'icono' => 'bi-book-half',
                'es_publicado' => true,
                'vistas_count' => 24,
                'creado_por' => $adminId,
                'instrucciones' => [
                    [
                        'paso' => 1,
                        'titulo' => 'Distanciamiento de Pantalla',
                        'duracion_segundos' => 20,
                        'fase' => 'preparacion',
                        'indicacion' => 'Aparta la vista de pantallas y monitores. Mira hacia un punto lejano a más de 6 metros o hacia una ventana.',
                    ],
                    [
                        'paso' => 2,
                        'titulo' => 'Estiramiento Cervical y Parpadeo',
                        'duracion_segundos' => 35,
                        'fase' => 'movimiento',
                        'indicacion' => 'Gira suavemente la cabeza hacia la derecha e izquierda mientras parpadeas despacio para lubricar la vista.',
                    ],
                    [
                        'paso' => 3,
                        'titulo' => 'Respiración de Despeje',
                        'duracion_segundos' => 45,
                        'fase' => 'inhala_exhala',
                        'indicacion' => 'Inhala contando mentalmente hasta 4 y exhala contando hasta 6, soltando las ideas acumuladas del estudio.',
                    ],
                    [
                        'paso' => 4,
                        'titulo' => 'Retorno Renovado',
                        'duracion_segundos' => 20,
                        'fase' => 'cierre',
                        'indicacion' => 'Bebe un sorbo de agua y disponte a iniciar tu próximo bloque académico con lucidez.',
                    ],
                ],
            ],
            [
                'titulo' => 'Atención Plena al Momento Presente (5-4-3-2-1)',
                'slug' => 'atencion-plena-5-4-3-2-1',
                'categoria' => Meditacion::CATEGORIA_MINDFULNESS,
                'descripcion' => 'Ejercicio de anclaje sensorial en el aquí y el ahora. Ayuda a interrumpir pensamientos rumiantes y dispersión mental.',
                'duracion_minutos' => 5,
                'nivel' => Meditacion::NIVEL_PRINCIPIANTE,
                'icono' => 'bi-eye',
                'es_publicado' => true,
                'vistas_count' => 31,
                'creado_por' => $adminId,
                'instrucciones' => [
                    [
                        'paso' => 1,
                        'titulo' => '5 Cosas que Puedes Ver',
                        'duracion_segundos' => 25,
                        'fase' => 'observacion',
                        'indicacion' => 'Mira a tu alrededor e identifica con detalle 5 objetos concretos: sus colores, formas y reflejos.',
                    ],
                    [
                        'paso' => 2,
                        'titulo' => '4 Cosas que Puedes Tocar',
                        'duracion_segundos' => 25,
                        'fase' => 'tacto',
                        'indicacion' => 'Siente la textura de tu ropa, la superficie de la mesa, el suelo bajo tus zapatos o la temperatura del aire.',
                    ],
                    [
                        'paso' => 3,
                        'titulo' => '3 Sonidos que Puedes Escuchar',
                        'duracion_segundos' => 25,
                        'fase' => 'oido',
                        'indicacion' => 'Presta atención a sonidos sutiles a tu alrededor: el viento, un ventilador, pasos lejanos.',
                    ],
                    [
                        'paso' => 4,
                        'titulo' => '2 Aromas u Olores Presentes',
                        'duracion_segundos' => 20,
                        'fase' => 'olfato',
                        'indicacion' => 'Percibe los aromas del ambiente o la frescura de tu respiración.',
                    ],
                    [
                        'paso' => 5,
                        'titulo' => '1 Cualidad Positiva de Ti Mismo',
                        'duracion_segundos' => 25,
                        'fase' => 'autoafirmacion',
                        'indicacion' => 'Reconoce un esfuerzo o valor personal del día de hoy y agradece este momento para cuidarte.',
                    ],
                ],
            ],
            [
                'titulo' => 'Escaneo Corporal para el Descanso',
                'slug' => 'escaneo-corporal-para-el-descanso',
                'categoria' => Meditacion::CATEGORIA_DESCANSO,
                'descripcion' => 'Relajación guiada para desconectar el sistema nervioso antes de dormir, propiciando un descanso reparador y reduciendo el insomnio universitario.',
                'duracion_minutos' => 8,
                'nivel' => Meditacion::NIVEL_INTERMEDIO,
                'icono' => 'bi-moon-stars',
                'es_publicado' => true,
                'vistas_count' => 15,
                'creado_por' => $adminId,
                'instrucciones' => [
                    [
                        'paso' => 1,
                        'titulo' => 'Acomodo y Desaceleración',
                        'duracion_segundos' => 20,
                        'fase' => 'preparacion',
                        'indicacion' => 'Acuéstate o reclínate cómodamente. Deja que tu peso se hunda en la superficie de apoyo.',
                    ],
                    [
                        'paso' => 2,
                        'titulo' => 'Atención a los Pies y Piernas',
                        'duracion_segundos' => 30,
                        'fase' => 'escaneo',
                        'indicacion' => 'Lleva tu atención a los dedos de los pies, pantorrillas y muslos. Imagina que se vuelven cálidos y pesados.',
                    ],
                    [
                        'paso' => 3,
                        'titulo' => 'Pelvis, Espalda y Pecho',
                        'duracion_segundos' => 35,
                        'fase' => 'escaneo',
                        'indicacion' => 'Siente cómo el pecho se eleva y desciende suavemente al compás de tu respiración relajada.',
                    ],
                    [
                        'paso' => 4,
                        'titulo' => 'Brazos, Mandíbula y Rostro',
                        'duracion_segundos' => 35,
                        'fase' => 'escaneo',
                        'indicacion' => 'Despega la lengua del paladar, suelta la mandíbula y alisa el entrecejo liberando toda expresión.',
                    ],
                    [
                        'paso' => 5,
                        'titulo' => 'Transición hacia el Sueño',
                        'duracion_segundos' => 30,
                        'fase' => 'cierre',
                        'indicacion' => 'Permítete flotar en una sensación de seguridad y descanso profundo.',
                    ],
                ],
            ],
        ];

        foreach ($meditaciones as $data) {
            Meditacion::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}

