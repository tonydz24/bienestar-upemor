<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recompensas_catalogo', function (Blueprint $table) {
            $table->id('recompensa_id');
            $table->string('nombre', 150);
            $table->text('descripcion');
            $table->enum('categoria', ['ACADEMICA', 'TIEMPO', 'PARTICIPACION', 'RECONOCIMIENTO'])->default('ACADEMICA');
            $table->unsignedInteger('costo_puntos_base')->default(10);
            $table->text('condiciones_generales')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('recompensas_activadas', function (Blueprint $table) {
            $table->id('activacion_id');
            $table->foreignId('recompensa_id')->constrained('recompensas_catalogo', 'recompensa_id')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('docente_asignaciones', 'asignacion_id')->cascadeOnDelete();
            $table->unsignedSmallInteger('cupo_maximo')->default(5);
            $table->unsignedSmallInteger('cupo_disponible')->default(5);
            $table->unsignedInteger('costo_puntos')->default(10);
            $table->date('vigencia_inicio');
            $table->date('vigencia_fin');
            $table->text('condiciones_docente')->nullable();
            $table->enum('estado', ['ACTIVA', 'SUSPENDIDA', 'FINALIZADA'])->default('ACTIVA');
            $table->timestamps();

            $table->index(['asignacion_id', 'estado']);
        });

        Schema::create('recompensas_canjes', function (Blueprint $table) {
            $table->id('canje_id');
            $table->foreignId('activacion_id')->constrained('recompensas_activadas', 'activacion_id')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('users', 'usuario_id')->cascadeOnDelete();
            $table->unsignedInteger('puntos_canjeados')->default(10);
            $table->enum('estado', ['SOLICITADO', 'APROBADO', 'RECHAZADO', 'CANCELADO'])->default('SOLICITADO');
            $table->text('motivo_solicitud')->nullable();
            $table->string('motivo_rechazo', 255)->nullable();
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_resolucion')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users', 'usuario_id')->nullOnDelete();
            $table->timestamps();

            $table->index(['activacion_id', 'estudiante_id', 'estado'], 'idx_canje_act_est_est');
            $table->index(['estudiante_id', 'estado'], 'idx_canje_est_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recompensas_canjes');
        Schema::dropIfExists('recompensas_activadas');
        Schema::dropIfExists('recompensas_catalogo');
    }
};

