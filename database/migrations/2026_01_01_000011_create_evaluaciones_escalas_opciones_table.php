<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_escalas_opciones', function (Blueprint $table) {
            $table->id('escala_id');
            $table->foreignId('version_id')
                ->constrained('evaluaciones_versiones', 'version_id')
                ->onDelete('restrict');
            $table->smallInteger('valor');
            $table->string('etiqueta', 100);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->timestamps();

            $table->unique(['version_id', 'valor'], 'uk_eval_escalas_ver_valor');
            $table->index('version_id', 'idx_eval_escalas_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_escalas_opciones');
    }
};

