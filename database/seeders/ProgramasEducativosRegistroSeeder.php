<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramasEducativosRegistroSeeder extends Seeder
{
    /**
     * Carreras permitidas en el registro público de alumnos.
     * Agrega las faltantes y actualiza nombres de IFI e IBT si es necesario.
     * No modifica ni elimina programas históricos.
     */
    public function run(): void
    {
        $permitidas = [
            ['clave' => 'IFI', 'nombre' => 'Ingeniería Financiera'],
            ['clave' => 'LAD', 'nombre' => 'Licenciatura en Administración'],
            ['clave' => 'IID', 'nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital'],
            ['clave' => 'ISE', 'nombre' => 'Ingeniería en Sistemas Electrónicos'],
            ['clave' => 'IBT', 'nombre' => 'Ingeniería en Biotecnología'],
            ['clave' => 'IAS', 'nombre' => 'Ingeniería Ambiental y Sustentabilidad'],
        ];

        foreach ($permitidas as $programa) {
            DB::table('programas_educativos')->updateOrInsert(
                ['clave' => $programa['clave']],
                [
                    'nombre' => $programa['nombre'],
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
