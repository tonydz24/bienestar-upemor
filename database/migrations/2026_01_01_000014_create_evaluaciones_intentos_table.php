<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_intentos', function (Blueprint $table) {
            $table->id('intento_id');
            // Vinculado a estudiantes con restricción para proteger el historial confidencial
            $table->foreignId('usuario_id')
                ->constrained('estudiantes', 'usuario_id')
                ->onDelete('restrict');
            // Vinculado a la versión EXACTA del instrumento con restricción
            $table->foreignId('version_id')
                ->constrained('evaluaciones_versiones', 'version_id')
                ->onDelete('restrict');
            $table->enum('estado', ['EN_PROGRESO', 'FINALIZADO', 'ABANDONADO'])->default('EN_PROGRESO');
            $table->unsignedSmallInteger('puntuacion_total')->nullable();
            $table->string('nivel_resultado', 60)->nullable();
            $table->boolean('tiene_alerta_critica')->default(false);
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_finalizacion')->nullable();
            $table->string('ip_registro', 45)->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'estado'], 'idx_eval_intentos_usr_est');
            $table->index(['version_id', 'estado'], 'idx_eval_intentos_ver_est');
            $table->index(['usuario_id', 'fecha_finalizacion'], 'idx_eval_intentos_usr_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_intentos');
    }
};

