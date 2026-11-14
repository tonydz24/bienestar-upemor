<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_respuestas', function (Blueprint $table) {
            $table->id('respuesta_id');
            $table->foreignId('intento_id')
                ->constrained('evaluaciones_intentos', 'intento_id')
                ->onDelete('cascade');
            $table->foreignId('pregunta_id')
                ->constrained('evaluaciones_preguntas', 'pregunta_id')
                ->onDelete('restrict');
            $table->foreignId('escala_id')
                ->constrained('evaluaciones_escalas_opciones', 'escala_id')
                ->onDelete('restrict');
            $table->smallInteger('puntos_obtenidos');
            $table->timestamps();

            $table->unique(['intento_id', 'pregunta_id'], 'uk_eval_respuestas_intento_preg');
            $table->index('intento_id', 'idx_eval_respuestas_intento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_respuestas');
    }
};

