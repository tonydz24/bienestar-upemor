<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_preguntas', function (Blueprint $table) {
            $table->id('pregunta_id');
            $table->foreignId('version_id')
                ->constrained('evaluaciones_versiones', 'version_id')
                ->onDelete('restrict');
            $table->unsignedSmallInteger('numero_reactivo');
            $table->text('enunciado');
            $table->boolean('es_inverso')->default(false);
            $table->boolean('es_critico')->default(false);
            $table->unsignedSmallInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['version_id', 'numero_reactivo'], 'uk_eval_preguntas_ver_num');
            $table->index(['version_id', 'orden'], 'idx_eval_preguntas_ver_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_preguntas');
    }
};

