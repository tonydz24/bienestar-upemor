<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_instrumentos', function (Blueprint $table) {
            $table->id('instrumento_id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->string('siglas', 20)->nullable();
            $table->string('categoria', 50);
            $table->text('descripcion')->nullable();
            $table->string('autor', 255)->nullable();
            $table->unsignedSmallInteger('anio_publicacion')->nullable();
            $table->unsignedSmallInteger('dias_espera_reaplicacion')->default(14);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_instrumentos');
    }
};

