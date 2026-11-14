<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('estudiante_plan_historial')) {
            Schema::create('estudiante_plan_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('estudiante_id')
                    ->constrained('users', 'usuario_id', 'fk_eph_estudiante')
                    ->cascadeOnDelete();
                $table->foreignId('plan_anterior_id')
                    ->nullable()
                    ->constrained('planes_estudio', 'plan_id', 'fk_eph_plan_anterior')
                    ->nullOnDelete();
                $table->foreignId('plan_nuevo_id')
                    ->constrained('planes_estudio', 'plan_id', 'fk_eph_plan_nuevo')
                    ->cascadeOnDelete();
                $table->foreignId('admin_usuario_id')
                    ->constrained('users', 'usuario_id', 'fk_eph_admin')
                    ->cascadeOnDelete();
                $table->text('motivo');
                $table->string('resultado', 50)->default('EXITOSO');
                $table->timestamps();

                $table->index(['estudiante_id', 'created_at'], 'idx_eph_est_fecha');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_plan_historial');
    }
};

