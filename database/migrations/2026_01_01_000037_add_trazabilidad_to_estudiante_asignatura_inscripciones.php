<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            Schema::table('estudiante_asignatura_inscripciones', function (Blueprint $table) {
                if (!Schema::hasColumn('estudiante_asignatura_inscripciones', 'historial_transiciones')) {
                    $table->json('historial_transiciones')->nullable()->after('motivo_resolucion');
                }
            });
        }

        if (!Schema::hasTable('estudiante_asignatura_inscripciones_historial')) {
            Schema::create('estudiante_asignatura_inscripciones_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inscripcion_materia_id')
                    ->constrained('estudiante_asignatura_inscripciones', 'inscripcion_materia_id', 'fk_eaih_inscripcion')
                    ->cascadeOnDelete();
                $table->foreignId('estudiante_id')
                    ->constrained('users', 'usuario_id', 'fk_eaih_estudiante')
                    ->cascadeOnDelete();
                $table->foreignId('asignacion_id')
                    ->constrained('docente_asignaciones', 'asignacion_id', 'fk_eaih_asignacion')
                    ->cascadeOnDelete();
                $table->string('estado_anterior', 30)->nullable();
                $table->string('estado_nuevo', 30);
                $table->text('motivo')->nullable();
                $table->foreignId('resuelto_por')->nullable()
                    ->constrained('users', 'usuario_id', 'fk_eaih_resuelto_por')
                    ->nullOnDelete();
                $table->dateTime('resuelto_en')->nullable();
                $table->timestamps();

                $table->index(['inscripcion_materia_id', 'created_at'], 'idx_hist_asig_insc_fecha');
                $table->index(['estudiante_id', 'asignacion_id'], 'idx_hist_est_asig');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_asignatura_inscripciones_historial');

        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            Schema::table('estudiante_asignatura_inscripciones', function (Blueprint $table) {
                if (Schema::hasColumn('estudiante_asignatura_inscripciones', 'historial_transiciones')) {
                    $table->dropColumn('historial_transiciones');
                }
            });
        }
    }
};

