<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas_profesionales', function (Blueprint $table) {
            $table->id('profesional_id');
            $table->foreignId('usuario_id')->unique()->constrained('users', 'usuario_id')->cascadeOnDelete();
            $table->string('cargo', 100);
            $table->string('cedula_profesional', 50)->nullable();
            $table->string('lugar_atencion', 150)->nullable();
            $table->enum('modalidades_permitidas', ['PRESENCIAL', 'VIRTUAL', 'AMBAS'])->default('AMBAS');
            $table->boolean('permite_citas')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'permite_citas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas_profesionales');
    }
};

