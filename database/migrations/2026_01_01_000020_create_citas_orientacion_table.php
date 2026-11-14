<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas_orientacion', function (Blueprint $table) {
            $table->id('cita_id');
            $table->foreignId('estudiante_id')->constrained('users', 'usuario_id')->cascadeOnDelete();
            $table->foreignId('profesional_id')->constrained('citas_profesionales', 'profesional_id')->cascadeOnDelete();
            $table->date('fecha_cita');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->enum('modalidad', ['PRESENCIAL', 'VIRTUAL'])->default('PRESENCIAL');
            $table->string('lugar_o_enlace', 255)->nullable();
            $table->enum('estado', [
                'SOLICITADA',
                'CONFIRMADA',
                'RECHAZADA',
                'CANCELADA_ALUMNO',
                'CANCELADA_PROFESIONAL',
                'FINALIZADA',
                'NO_ASISTIO'
            ])->default('SOLICITADA');
            $table->text('motivo_consulta')->nullable();
            $table->string('motivo_cancelacion_rechazo', 255)->nullable();
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_resolucion')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users', 'usuario_id')->nullOnDelete();
            $table->string('ip_registro', 45)->nullable();
            $table->timestamps();

            $table->index(['profesional_id', 'fecha_cita', 'hora_inicio', 'estado'], 'idx_citas_prof_fecha_hora_estado');
            $table->index(['estudiante_id', 'fecha_cita', 'hora_inicio'], 'idx_citas_estudiante_fecha_hora');
            $table->index(['estado', 'fecha_cita'], 'idx_citas_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas_orientacion');
    }
};

