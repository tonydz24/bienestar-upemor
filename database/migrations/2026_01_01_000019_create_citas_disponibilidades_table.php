<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas_disponibilidades', function (Blueprint $table) {
            $table->id('disponibilidad_id');
            $table->foreignId('profesional_id')->constrained('citas_profesionales', 'profesional_id')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 1 = Lunes, 2 = Martes, 3 = Miércoles, 4 = Jueves, 5 = Viernes, 6 = Sábado
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedSmallInteger('duracion_cita_min')->default(45);
            $table->enum('modalidad', ['PRESENCIAL', 'VIRTUAL', 'AMBAS'])->default('AMBAS');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['profesional_id', 'dia_semana', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas_disponibilidades');
    }
};

