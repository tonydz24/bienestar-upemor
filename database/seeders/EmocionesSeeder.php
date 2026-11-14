<?php

namespace Database\Seeders;

use App\Models\Emocion;
use Illuminate\Database\Seeder;

class EmocionesSeeder extends Seeder
{
    public const EMOCIONES_INICIALES = [
        [
            'nombre' => 'Alegría',
            'slug' => 'alegria',
            'descripcion' => 'Sensación de entusiasmo, bienestar, plenitud o satisfacción.',
            'icono' => '😊',
            'color_hex' => '#f59e0b', // Ámbar / Dorado institucional
            'activo' => true,
            'orden' => 1,
        ],
        [
            'nombre' => 'Calma',
            'slug' => 'calma',
            'descripcion' => 'Estado de tranquilidad, serenidad mental, sosiego y equilibrio.',
            'icono' => '😌',
            'color_hex' => '#06b6d4', // Cian pacífico
            'activo' => true,
            'orden' => 2,
        ],
        [
            'nombre' => 'Ansiedad',
            'slug' => 'ansiedad',
            'descripcion' => 'Inquietud, preocupación excesiva, nerviosismo o agitación.',
            'icono' => '😰',
            'color_hex' => '#f97316', // Naranja alerta
            'activo' => true,
            'orden' => 3,
        ],
        [
            'nombre' => 'Estrés',
            'slug' => 'estres',
            'descripcion' => 'Sensación de sobrecarga física o mental provocada por exigencias o presión.',
            'icono' => '😫',
            'color_hex' => '#8b5cf6', // Morado intenso
            'activo' => true,
            'orden' => 4,
        ],
        [
            'nombre' => 'Tristeza',
            'slug' => 'tristeza',
            'descripcion' => 'Decaimiento emocional, pena, nostalgia, abatimiento o desánimo.',
            'icono' => '😢',
            'color_hex' => '#3b82f6', // Azul suave
            'activo' => true,
            'orden' => 5,
        ],
        [
            'nombre' => 'Enojo',
            'slug' => 'enojo',
            'descripcion' => 'Frustración, enfado, disgusto o irritación ante una situación adversa.',
            'icono' => '😠',
            'color_hex' => '#ef4444', // Coral rojizo
            'activo' => true,
            'orden' => 6,
        ],
    ];

    public function run(): void
    {
        foreach (self::EMOCIONES_INICIALES as $data) {
            Emocion::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}

