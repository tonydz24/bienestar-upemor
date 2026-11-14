<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_versiones', function (Blueprint $table) {
            $table->id('version_id');
            $table->foreignId('instrumento_id')
                ->constrained('evaluaciones_instrumentos', 'instrumento_id')
                ->onDelete('restrict');
            $table->string('version', 20);
            $table->string('nombre_version', 100)->nullable();
            $table->text('descripcion_cambios')->nullable();
            $table->text('instrucciones');
            $table->string('marco_temporal', 100)->nullable();
            $table->unsignedSmallInteger('duracion_estimada_min')->default(5);
            $table->unsignedSmallInteger('puntos_recompensa')->default(10);
            $table->enum('estado', ['BORRADOR', 'PUBLICADA', 'OBSOLETA'])->default('PUBLICADA');
            $table->boolean('es_vigente')->default(true);
            $table->dateTime('vigente_desde')->nullable();
            $table->dateTime('vigente_hasta')->nullable();
            $table->timestamps();

            $table->unique(['instrumento_id', 'version'], 'uk_eval_versiones_inst_ver');
            $table->index(['instrumento_id', 'es_vigente'], 'idx_eval_versiones_vigente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_versiones');
    }
};

