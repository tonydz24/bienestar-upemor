<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('citas_orientacion')) {
            Schema::table('citas_orientacion', function (Blueprint $table) {
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_estado')) {
                    $table->enum('reprogramacion_estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA'])
                        ->nullable()
                        ->after('motivo_cancelacion_rechazo');
                }
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_fecha')) {
                    $table->date('reprogramacion_fecha')->nullable()->after('reprogramacion_estado');
                }
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_hora_inicio')) {
                    $table->time('reprogramacion_hora_inicio')->nullable()->after('reprogramacion_fecha');
                }
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_hora_fin')) {
                    $table->time('reprogramacion_hora_fin')->nullable()->after('reprogramacion_hora_inicio');
                }
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_motivo')) {
                    $table->text('reprogramacion_motivo')->nullable()->after('reprogramacion_hora_fin');
                }
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_respuesta_motivo')) {
                    $table->text('reprogramacion_respuesta_motivo')->nullable()->after('reprogramacion_motivo');
                }
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_fecha_solicitud')) {
                    $table->dateTime('reprogramacion_fecha_solicitud')->nullable()->after('reprogramacion_respuesta_motivo');
                }
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_fecha_resolucion')) {
                    $table->dateTime('reprogramacion_fecha_resolucion')->nullable()->after('reprogramacion_fecha_solicitud');
                }
                if (!Schema::hasColumn('citas_orientacion', 'reprogramacion_resuelto_por')) {
                    $table->foreignId('reprogramacion_resuelto_por')->nullable()->after('reprogramacion_fecha_resolucion')
                        ->constrained('users', 'usuario_id')->nullOnDelete();
                }
                if (!Schema::hasColumn('citas_orientacion', 'fecha_cita_anterior')) {
                    $table->date('fecha_cita_anterior')->nullable()->after('reprogramacion_resuelto_por');
                }
                if (!Schema::hasColumn('citas_orientacion', 'hora_inicio_anterior')) {
                    $table->time('hora_inicio_anterior')->nullable()->after('fecha_cita_anterior');
                }
                if (!Schema::hasColumn('citas_orientacion', 'hora_fin_anterior')) {
                    $table->time('hora_fin_anterior')->nullable()->after('hora_inicio_anterior');
                }
                if (!Schema::hasColumn('citas_orientacion', 'conteo_reprogramaciones')) {
                    $table->unsignedTinyInteger('conteo_reprogramaciones')->default(0)->after('hora_fin_anterior');
                }
            });
        }

        if (!Schema::hasTable('citas_reprogramaciones_historial')) {
            Schema::create('citas_reprogramaciones_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cita_id')->constrained('citas_orientacion', 'cita_id')->cascadeOnDelete();
                $table->foreignId('estudiante_id')->constrained('users', 'usuario_id')->cascadeOnDelete();
                $table->foreignId('profesional_id')->constrained('citas_profesionales', 'profesional_id')->cascadeOnDelete();
                $table->date('fecha_original');
                $table->time('hora_inicio_original');
                $table->time('hora_fin_original');
                $table->date('fecha_propuesta');
                $table->time('hora_inicio_propuesta');
                $table->time('hora_fin_propuesta');
                $table->text('motivo_solicitud')->nullable();
                $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA'])->default('PENDIENTE');
                $table->text('motivo_respuesta')->nullable();
                $table->foreignId('resuelto_por')->nullable()->constrained('users', 'usuario_id')->nullOnDelete();
                $table->dateTime('fecha_resolucion')->nullable();
                $table->timestamps();

                $table->index(['cita_id', 'estado'], 'idx_reprog_cita_estado');
                $table->index(['profesional_id', 'fecha_propuesta', 'estado'], 'idx_reprog_prof_fecha_estado');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('citas_reprogramaciones_historial');

        if (Schema::hasTable('citas_orientacion')) {
            Schema::table('citas_orientacion', function (Blueprint $table) {
                $columns = [
                    'reprogramacion_estado',
                    'reprogramacion_fecha',
                    'reprogramacion_hora_inicio',
                    'reprogramacion_hora_fin',
                    'reprogramacion_motivo',
                    'reprogramacion_respuesta_motivo',
                    'reprogramacion_fecha_solicitud',
                    'reprogramacion_fecha_resolucion',
                    'reprogramacion_resuelto_por',
                    'fecha_cita_anterior',
                    'hora_inicio_anterior',
                    'hora_fin_anterior',
                    'conteo_reprogramaciones',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('citas_orientacion', $column)) {
                        if ($column === 'reprogramacion_resuelto_por') {
                            $table->dropForeign(['reprogramacion_resuelto_por']);
                        }
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

