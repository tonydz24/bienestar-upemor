<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->foreignId('usuario_id')->primary()->constrained('users', 'usuario_id')->onDelete('cascade');
            $table->foreignId('programa_id')->constrained('programas_educativos', 'programa_id')->onDelete('cascade');
            $table->string('matricula', 25)->unique();
            $table->tinyInteger('cuatrimestre')->nullable();
            $table->string('grupo', 20)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->integer('saldo_puntos')->default(0);
            $table->boolean('permite_visualizacion')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};