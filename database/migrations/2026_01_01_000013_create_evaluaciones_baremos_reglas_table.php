<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_baremos_reglas', function (Blueprint $table) {
            $table->id('regla_id');
            $table->foreignId('version_id')
                ->constrained('evaluaciones_versiones', 'version_id')
                ->onDelete('restrict');
            $table->unsignedSmallInteger('puntuacion_min');
            $table->unsignedSmallInteger('puntuacion_max');
            $table->string('nivel', 60);
            $table->string('color_hex', 7)->default('#6f42c1');
            $table->text('mensaje_retroalimentacion');
            $table->boolean('sugiere_orientacion')->default(false);
            $table->unsignedSmallInteger('orden')->default(1);
            $table->timestamps();

            $table->index(['version_id', 'puntuacion_min', 'puntuacion_max'], 'idx_eval_baremos_rango');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_baremos_reglas');
    }
};

