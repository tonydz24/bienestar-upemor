<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->id('periodo_id');
            $table->string('clave', 20)->unique();
            $table->string('nombre', 100);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('asignaturas', function (Blueprint $table) {
            $table->id('asignatura_id');
            $table->foreignId('programa_id')->constrained('programas_educativos', 'programa_id')->cascadeOnDelete();
            $table->string('clave', 30);
            $table->string('nombre', 150);
            $table->tinyInteger('cuatrimestre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['programa_id', 'clave']);
        });

        Schema::create('grupos', function (Blueprint $table) {
            $table->id('grupo_id');
            $table->foreignId('programa_id')->constrained('programas_educativos', 'programa_id')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos_academicos', 'periodo_id')->cascadeOnDelete();
            $table->string('nombre', 20);
            $table->tinyInteger('cuatrimestre');
            $table->enum('turno', ['MATUTINO', 'VESPERTINO'])->default('MATUTINO');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['programa_id', 'periodo_id', 'cuatrimestre', 'nombre'], 'idx_grupos_prog_per_cuat_nom');
        });

        Schema::create('docente_asignaciones', function (Blueprint $table) {
            $table->id('asignacion_id');
            $table->foreignId('docente_id')->constrained('users', 'usuario_id')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas', 'asignatura_id')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos', 'grupo_id')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos_academicos', 'periodo_id')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['docente_id', 'asignatura_id', 'grupo_id', 'periodo_id'], 'idx_docente_asig_unique');
        });

        Schema::create('estudiante_inscripciones', function (Blueprint $table) {
            $table->id('inscripcion_id');
            $table->foreignId('estudiante_id')->constrained('users', 'usuario_id')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos', 'grupo_id')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos_academicos', 'periodo_id')->cascadeOnDelete();
            $table->enum('estado', ['ACTIVO', 'BAJA', 'CONCLUIDO'])->default('ACTIVO');
            $table->timestamps();

            $table->unique(['estudiante_id', 'grupo_id', 'periodo_id'], 'idx_estudiante_inscrip_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_inscripciones');
        Schema::dropIfExists('docente_asignaciones');
        Schema::dropIfExists('grupos');
        Schema::dropIfExists('asignaturas');
        Schema::dropIfExists('periodos_academicos');
    }
};

