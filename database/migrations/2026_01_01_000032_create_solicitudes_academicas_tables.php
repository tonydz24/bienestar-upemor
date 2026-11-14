<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ampliar docente_asignaciones para soportar solicitudes y trazabilidad
        Schema::table('docente_asignaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('docente_asignaciones', 'estado')) {
                $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])->default('APROBADA')->after('periodo_id');
            }
            if (!Schema::hasColumn('docente_asignaciones', 'solicitado_en')) {
                $table->timestamp('solicitado_en')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('docente_asignaciones', 'motivo_solicitud')) {
                $table->text('motivo_solicitud')->nullable()->after('solicitado_en');
            }
            if (!Schema::hasColumn('docente_asignaciones', 'resuelto_por')) {
                $table->foreignId('resuelto_por')->nullable()->after('motivo_solicitud')->constrained('users', 'usuario_id')->nullOnDelete();
            }
            if (!Schema::hasColumn('docente_asignaciones', 'resuelto_en')) {
                $table->timestamp('resuelto_en')->nullable()->after('resuelto_por');
            }
            if (!Schema::hasColumn('docente_asignaciones', 'motivo_resolucion')) {
                $table->text('motivo_resolucion')->nullable()->after('resuelto_en');
            }
        });

        // 2. Crear tabla estudiante_asignatura_inscripciones para la inscripción a materias de su grupo
        if (!Schema::hasTable('estudiante_asignatura_inscripciones')) {
            Schema::create('estudiante_asignatura_inscripciones', function (Blueprint $table) {
                $table->id('inscripcion_materia_id');
                $table->foreignId('estudiante_id')->constrained('users', 'usuario_id')->cascadeOnDelete();
                $table->foreignId('asignacion_id')->constrained('docente_asignaciones', 'asignacion_id')->cascadeOnDelete();
                $table->foreignId('grupo_id')->constrained('grupos', 'grupo_id')->cascadeOnDelete();
                $table->foreignId('asignatura_id')->constrained('asignaturas', 'asignatura_id')->cascadeOnDelete();
                $table->foreignId('periodo_id')->constrained('periodos_academicos', 'periodo_id')->cascadeOnDelete();
                $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA'])->default('PENDIENTE');
                $table->timestamp('solicitado_en')->nullable();
                $table->text('motivo_solicitud')->nullable();
                $table->foreignId('resuelto_por')->nullable()->constrained('users', 'usuario_id')->nullOnDelete();
                $table->timestamp('resuelto_en')->nullable();
                $table->text('motivo_resolucion')->nullable();
                $table->timestamps();

                $table->unique(['estudiante_id', 'asignacion_id'], 'idx_est_asig_inscrip_unique');
                $table->index(['estudiante_id', 'periodo_id'], 'idx_est_per_inscrip');
                $table->index(['grupo_id', 'periodo_id'], 'idx_grp_per_inscrip');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_asignatura_inscripciones');

        Schema::table('docente_asignaciones', function (Blueprint $table) {
            if (Schema::hasColumn('docente_asignaciones', 'motivo_resolucion')) {
                $table->dropColumn('motivo_resolucion');
            }
            if (Schema::hasColumn('docente_asignaciones', 'resuelto_en')) {
                $table->dropColumn('resuelto_en');
            }
            if (Schema::hasColumn('docente_asignaciones', 'resuelto_por')) {
                $table->dropForeign(['resuelto_por']);
                $table->dropColumn('resuelto_por');
            }
            if (Schema::hasColumn('docente_asignaciones', 'motivo_solicitud')) {
                $table->dropColumn('motivo_solicitud');
            }
            if (Schema::hasColumn('docente_asignaciones', 'solicitado_en')) {
                $table->dropColumn('solicitado_en');
            }
            if (Schema::hasColumn('docente_asignaciones', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};

