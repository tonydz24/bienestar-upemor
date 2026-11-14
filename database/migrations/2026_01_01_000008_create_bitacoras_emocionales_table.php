<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacoras_emocionales', function (Blueprint $table) {
            $table->id('bitacora_id');
            // Restricción referencial RESTRICT para evitar pérdidas accidentales de historial emocional
            $table->foreignId('usuario_id')->constrained('estudiantes', 'usuario_id')->onDelete('restrict');
            $table->foreignId('emocion_id')->constrained('emociones', 'emocion_id')->onDelete('restrict');
            $table->date('fecha');
            $table->unsignedTinyInteger('intensidad'); // 1 a 5
            $table->string('descripcion', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Un solo registro por alumno y fecha (3NF e integridad)
            $table->unique(['usuario_id', 'fecha'], 'uk_bitacoras_usuario_fecha');
            $table->index('emocion_id', 'idx_bitacoras_emocion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras_emocionales');
    }
};

